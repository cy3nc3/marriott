<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Imports\ImportRollbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImportBatchRollbackController extends Controller
{
    public function __construct(private ImportRollbackService $rollbackService) {}

    public function store(Request $request, ImportBatch $importBatch): JsonResponse
    {
        $expectedModule = $this->expectedModule($request);

        abort_unless($expectedModule !== null && $importBatch->module === $expectedModule, 404);
        abort_if((int) $importBatch->uploaded_by !== (int) $request->user()?->id, 403);
        $this->ensureConsistentState($importBatch);

        $importBatch = $this->rollbackService->rollback(
            $importBatch,
            (int) $request->user()?->id,
        );

        return response()->json([
            'message' => 'Import batch rollback recorded.',
            'batch' => [
                'id' => $importBatch->id,
                'module' => $importBatch->module,
                'status' => $importBatch->status,
                'rolled_back_at' => $importBatch->rolled_back_at?->toISOString(),
            ],
        ]);
    }

    private function expectedModule(Request $request): ?string
    {
        if ($request->routeIs('finance.*')) {
            return 'finance_transactions';
        }

        if ($request->routeIs('registrar.*')) {
            return 'registrar_students';
        }

        return null;
    }

    private function ensureConsistentState(ImportBatch $batch): void
    {
        $isConsistent = match ($batch->status) {
            'uploaded' => $batch->previewed_at === null
                && $batch->applied_at === null
                && $batch->rolled_back_at === null,
            'previewed' => $batch->previewed_at !== null
                && $batch->applied_at === null
                && $batch->rolled_back_at === null,
            'applied' => $batch->previewed_at !== null
                && $batch->applied_at !== null
                && $batch->rolled_back_at === null,
            'rolled_back' => $batch->previewed_at !== null
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
}
