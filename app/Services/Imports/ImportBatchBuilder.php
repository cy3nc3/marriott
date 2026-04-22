<?php

namespace App\Services\Imports;

use App\Models\ImportBatchRow;
use Illuminate\Support\Collection;

class ImportBatchBuilder
{
    public function __construct(private ValueParser $valueParser, private DuplicateEngine $duplicateEngine) {}

    /**
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>|null  $incoming
     * @return array<string, mixed>
     */
    public function mergeNormalizedPayload(?array $existing, ?array $incoming): array
    {
        return array_merge($existing ?? [], $incoming ?? []);
    }

    /**
     * @param  array<string, mixed>  $normalizedPayload
     * @return array{validation_errors: array<int, string>, duplicate_flags: array<int, string>, classification: string, action: string, is_unresolved: bool}
     */
    public function buildStudentRowState(array $normalizedPayload): array
    {
        $validationErrors = [];

        if ($this->valueParser->normalizeString($normalizedPayload['lrn'] ?? null) === null) {
            $validationErrors[] = 'missing_lrn';
        }

        if ($this->valueParser->normalizeString($normalizedPayload['first_name'] ?? null) === null) {
            $validationErrors[] = 'missing_first_name';
        }

        return $this->buildRowState($validationErrors, []);
    }

    /**
     * @param  array<string, mixed>  $normalizedPayload
     * @param  array<int, string>  $duplicateFlags
     * @return array{validation_errors: array<int, string>, duplicate_flags: array<int, string>, classification: string, action: string, is_unresolved: bool}
     */
    public function buildFinanceRowState(array $normalizedPayload, array $duplicateFlags = []): array
    {
        $validationErrors = [];

        if ($this->valueParser->normalizeString($normalizedPayload['or_number'] ?? null) === null) {
            $validationErrors[] = 'missing_or_number';
        }

        if ($this->valueParser->parseDecimal($normalizedPayload['amount'] ?? null) === null) {
            $validationErrors[] = 'invalid_amount';
        }

        return $this->buildRowState($validationErrors, $duplicateFlags, 'payment', 'create');
    }

    /**
     * @param  Collection<int, ImportBatchRow>  $rows
     * @return array<int, string>
     */
    public function financeDuplicateRowIds(Collection $rows): array
    {
        $countsByKey = [];

        foreach ($rows as $row) {
            $key = $this->duplicateEngine->paymentDuplicateKey($row->normalized_payload ?? []);
            if ($key === null) {
                continue;
            }

            $countsByKey[$key] = ($countsByKey[$key] ?? 0) + 1;
        }

        $duplicateRowIds = [];

        foreach ($rows as $row) {
            $key = $this->duplicateEngine->paymentDuplicateKey($row->normalized_payload ?? []);
            if ($key === null) {
                continue;
            }

            if (($countsByKey[$key] ?? 0) > 1) {
                $duplicateRowIds[] = (string) $row->id;
            }
        }

        return $duplicateRowIds;
    }

    /**
     * @param  Collection<int, ImportBatchRow>  $rows
     * @return array{total_rows: int, unresolved_rows: int, ready_rows: int}
     */
    public function buildSummary(Collection $rows): array
    {
        $totalRows = $rows->count();
        $unresolvedRows = $rows->where('is_unresolved', true)->count();

        return [
            'total_rows' => $totalRows,
            'unresolved_rows' => $unresolvedRows,
            'ready_rows' => $totalRows - $unresolvedRows,
        ];
    }

    /**
     * @param  array<int, string>  $validationErrors
     * @param  array<int, string>  $duplicateFlags
     * @return array{validation_errors: array<int, string>, duplicate_flags: array<int, string>, classification: string, action: string, is_unresolved: bool}
     */
    private function buildRowState(array $validationErrors, array $duplicateFlags, string $resolvedClassification = 'mixed', string $resolvedAction = 'update'): array
    {
        $isUnresolved = count($validationErrors) > 0 || count($duplicateFlags) > 0;

        return [
            'validation_errors' => $validationErrors,
            'duplicate_flags' => $duplicateFlags,
            'classification' => $isUnresolved ? 'unresolved' : $resolvedClassification,
            'action' => $isUnresolved ? 'blocked' : $resolvedAction,
            'is_unresolved' => $isUnresolved,
        ];
    }
}
