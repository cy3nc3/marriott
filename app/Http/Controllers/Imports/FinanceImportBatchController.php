<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ApplyImportBatchRequest;
use App\Http\Requests\Imports\PreviewImportBatchRequest;
use App\Http\Requests\Imports\UpdateImportRowRequest;
use App\Http\Requests\Imports\UploadImportBatchRequest;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Services\Imports\FinanceImportApplyService;
use App\Services\Imports\FinanceImportPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FinanceImportBatchController extends Controller
{
    private const MODULE = 'finance_transactions';

    private const STATUS_UPLOADED = 'uploaded';

    private const STATUS_PREVIEWED = 'previewed';

    private const STATUS_APPLIED = 'applied';

    private const STATUS_ROLLED_BACK = 'rolled_back';

    public function __construct(
        private FinanceImportPreviewService $previewService,
        private FinanceImportApplyService $applyService,
    ) {}

    public function store(UploadImportBatchRequest $request): JsonResponse
    {
        $file = $request->file('import_file');

        $batch = ImportBatch::query()->create([
            'module' => self::MODULE,
            'uploaded_by' => $request->user()?->id,
            'file_name' => $file->getClientOriginalName(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'mapping' => $request->validated('mapping'),
            'summary' => [
                'uploaded_rows' => 0,
                'preview_required' => true,
            ],
            'status' => 'uploaded',
        ]);

        return response()->json([
            'message' => 'Finance import batch uploaded.',
            'batch' => $this->batchPayload($batch),
        ], 201);
    }

    public function preview(PreviewImportBatchRequest $request, ImportBatch $importBatch): JsonResponse
    {
        $batch = $this->batch($request, $importBatch);
        $this->ensureBatchState(
            $batch,
            [self::STATUS_UPLOADED],
            'Only uploaded batches can be previewed.'
        );
        $validated = $request->validated();
        $recomputedSummary = $this->previewService->recomputeBatch($batch);

        $batch->update([
            'mapping' => $validated['mapping'] ?? $batch->mapping,
            'summary' => array_merge($batch->summary ?? [], $validated['summary'] ?? [], [
                'preview_generated' => true,
                'preview' => $recomputedSummary,
            ]),
            'status' => self::STATUS_PREVIEWED,
            'previewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Finance import batch preview generated.',
            'batch' => $this->batchPayload($batch->fresh()),
            'preview' => $recomputedSummary,
        ]);
    }

    public function updateRow(
        UpdateImportRowRequest $request,
        ImportBatch $importBatch,
        ImportBatchRow $importBatchRow
    ): JsonResponse {
        $batch = $this->batch($request, $importBatch);
        $this->ensureBatchState(
            $batch,
            [self::STATUS_UPLOADED, self::STATUS_PREVIEWED],
            'Rows can only be edited before the batch is applied.'
        );

        abort_unless($importBatchRow->import_batch_id === $batch->id, 404);

        $validated = $request->validated();
        $importBatchRow = $this->previewService->updateRow(
            $batch,
            $importBatchRow,
            $validated,
            (int) $request->user()?->id,
        );

        return response()->json([
            'message' => 'Finance import row updated.',
            'row' => $importBatchRow,
        ]);
    }

    public function apply(ApplyImportBatchRequest $request, ImportBatch $importBatch): JsonResponse
    {
        $batch = $this->batch($request, $importBatch);
        $this->ensureBatchState(
            $batch,
            [self::STATUS_PREVIEWED],
            'Preview is required before applying this import batch.'
        );

        if ($batch->previewed_at === null) {
            throw ValidationException::withMessages([
                'batch' => 'Preview is required before applying this import batch.',
            ]);
        }

        $batch = $this->applyService->apply($batch);

        return response()->json([
            'message' => 'Finance import batch applied.',
            'batch' => $this->batchPayload($batch),
        ]);
    }

    private function batch(ApplyImportBatchRequest|PreviewImportBatchRequest|UpdateImportRowRequest $request, ImportBatch $importBatch): ImportBatch
    {
        abort_unless($importBatch->module === self::MODULE, 404);
        abort_if((int) $importBatch->uploaded_by !== (int) $request->user()?->id, 403);

        return $importBatch;
    }

    private function ensureBatchState(ImportBatch $batch, array $allowedStatuses, string $transitionMessage): void
    {
        $this->ensureConsistentState($batch);

        if (! in_array($batch->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'batch' => $transitionMessage,
            ]);
        }
    }

    private function ensureConsistentState(ImportBatch $batch): void
    {
        $isConsistent = match ($batch->status) {
            self::STATUS_UPLOADED => $batch->previewed_at === null
                && $batch->applied_at === null
                && $batch->rolled_back_at === null,
            self::STATUS_PREVIEWED => $batch->previewed_at !== null
                && $batch->applied_at === null
                && $batch->rolled_back_at === null,
            self::STATUS_APPLIED => $batch->previewed_at !== null
                && $batch->applied_at !== null
                && $batch->rolled_back_at === null,
            self::STATUS_ROLLED_BACK => $batch->previewed_at !== null
                && $batch->applied_at !== null
                && $batch->rolled_back_at !== null,
            default => false,
        };

        if (! $isConsistent) {
            throw ValidationException::withMessages([
                'batch' => 'Import batch state is inconsistent.',
            ]);
        }
    }

    private function batchPayload(ImportBatch $importBatch): array
    {
        return [
            'id' => $importBatch->id,
            'module' => $importBatch->module,
            'status' => $importBatch->status,
            'previewed_at' => $importBatch->previewed_at?->toISOString(),
            'applied_at' => $importBatch->applied_at?->toISOString(),
            'rolled_back_at' => $importBatch->rolled_back_at?->toISOString(),
        ];
    }
}
