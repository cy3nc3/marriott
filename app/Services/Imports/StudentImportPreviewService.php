<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\ImportRowEdit;
use Illuminate\Support\Facades\DB;

class StudentImportPreviewService
{
    public function __construct(private ImportBatchBuilder $batchBuilder) {}

    /**
     * @return array{total_rows: int, unresolved_rows: int, ready_rows: int}
     */
    public function recomputeBatch(ImportBatch $batch): array
    {
        DB::transaction(function () use ($batch): void {
            $batch->rows()
                ->orderBy('id')
                ->each(function (ImportBatchRow $row): void {
                    $state = $this->batchBuilder->buildStudentRowState($row->normalized_payload ?? []);

                    $row->update([
                        'validation_errors' => $state['validation_errors'],
                        'duplicate_flags' => $state['duplicate_flags'],
                        'classification' => $state['classification'],
                        'action' => $state['action'],
                        'is_unresolved' => $state['is_unresolved'],
                    ]);
                });
        });

        return $this->batchBuilder->buildSummary($batch->rows()->get());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateRow(ImportBatchRow $row, array $payload, int $editorId): ImportBatchRow
    {
        $beforePayload = $this->rowAuditPayload($row);

        $normalizedPayload = $this->batchBuilder->mergeNormalizedPayload(
            $row->normalized_payload,
            $payload['normalized_payload'] ?? null,
        );
        $state = $this->batchBuilder->buildStudentRowState($normalizedPayload);

        $row->update([
            'normalized_payload' => $normalizedPayload,
            'validation_errors' => $state['validation_errors'],
            'duplicate_flags' => $state['duplicate_flags'],
            'classification' => $state['classification'],
            'action' => $state['action'],
            'is_unresolved' => $state['is_unresolved'],
        ]);

        $afterPayload = $this->rowAuditPayload($row->fresh());

        ImportRowEdit::query()->create([
            'import_batch_row_id' => $row->id,
            'edited_by' => $editorId,
            'before_payload' => $beforePayload,
            'after_payload' => $afterPayload,
        ]);

        return $row->fresh();
    }

    /**
     * @return array{normalized_payload: array<string, mixed>|null, validation_errors: array<int, string>|null, duplicate_flags: array<int, string>|null, classification: string|null, action: string|null, is_unresolved: bool}
     */
    private function rowAuditPayload(ImportBatchRow $row): array
    {
        return [
            'normalized_payload' => $row->normalized_payload,
            'validation_errors' => $row->validation_errors,
            'duplicate_flags' => $row->duplicate_flags,
            'classification' => $row->classification,
            'action' => $row->action,
            'is_unresolved' => (bool) $row->is_unresolved,
        ];
    }
}
