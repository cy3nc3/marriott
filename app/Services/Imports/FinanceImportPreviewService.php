<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\ImportRowEdit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceImportPreviewService
{
    public function __construct(private ImportBatchBuilder $batchBuilder, private ValueParser $valueParser) {}

    /**
     * @return array{total_rows: int, unresolved_rows: int, ready_rows: int, before_after_totals: array{before_total: float, after_total: float, net_delta: float}, duplicate_buckets: array{in_batch: int, existing: int}}
     */
    public function recomputeBatch(ImportBatch $batch): array
    {
        $duplicateRowIds = [];

        DB::transaction(function () use ($batch, &$duplicateRowIds): void {
            $rows = $batch->rows()->orderBy('id')->get();
            $duplicateRowIds = $this->applyFinanceState($rows);
        });

        $rows = $batch->rows()->get();
        $summary = $this->batchBuilder->buildSummary($rows);
        $beforeTotal = 0.0;
        $afterTotal = 0.0;
        $existingDuplicateCount = 0;

        foreach ($rows as $row) {
            $amount = $this->valueParser->parseDecimal($row->normalized_payload['amount'] ?? null) ?? 0.0;
            $beforeTotal += $amount;

            if ((bool) $row->is_unresolved === false && ! in_array($row->action, ['skip', 'blocked'], true)) {
                $afterTotal += $amount;
            }

            if (in_array('existing_duplicate', $row->duplicate_flags ?? [], true)) {
                $existingDuplicateCount++;
            }
        }

        return array_merge($summary, [
            'before_after_totals' => [
                'before_total' => round($beforeTotal, 2),
                'after_total' => round($afterTotal, 2),
                'net_delta' => round($afterTotal - $beforeTotal, 2),
            ],
            'duplicate_buckets' => [
                'in_batch' => count($duplicateRowIds),
                'existing' => $existingDuplicateCount,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateRow(ImportBatch $batch, ImportBatchRow $row, array $payload, int $editorId): ImportBatchRow
    {
        $beforePayload = $this->rowAuditPayload($row);

        DB::transaction(function () use ($batch, $row, $payload): void {
            $normalizedPayload = $this->batchBuilder->mergeNormalizedPayload(
                $row->normalized_payload,
                $payload['normalized_payload'] ?? null,
            );

            $row->update([
                'normalized_payload' => $normalizedPayload,
            ]);

            $rows = $batch->rows()->orderBy('id')->get();
            $this->applyFinanceState($rows);
        });

        $freshRow = $row->fresh();

        ImportRowEdit::query()->create([
            'import_batch_row_id' => $row->id,
            'edited_by' => $editorId,
            'before_payload' => $beforePayload,
            'after_payload' => $this->rowAuditPayload($freshRow),
        ]);

        return $freshRow;
    }

    /**
     * @param  Collection<int, ImportBatchRow>  $rows
     * @return array<int, string>
     */
    private function applyFinanceState(Collection $rows): array
    {
        $duplicateRowIds = $this->batchBuilder->financeDuplicateRowIds($rows);

        foreach ($rows as $row) {
            $duplicateFlags = in_array((string) $row->id, $duplicateRowIds, true)
                ? ['duplicate_payment_key']
                : [];

            $state = $this->batchBuilder->buildFinanceRowState($row->normalized_payload ?? [], $duplicateFlags);

            $row->update([
                'validation_errors' => $state['validation_errors'],
                'duplicate_flags' => $state['duplicate_flags'],
                'classification' => $state['classification'],
                'action' => $state['action'],
                'is_unresolved' => $state['is_unresolved'],
            ]);
        }

        return $duplicateRowIds;
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
