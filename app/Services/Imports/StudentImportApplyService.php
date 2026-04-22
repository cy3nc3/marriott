<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentImportApplyService
{
    public function apply(ImportBatch $batch): ImportBatch
    {
        return DB::transaction(function () use ($batch): ImportBatch {
            if ($batch->rows()->where('is_unresolved', true)->exists()) {
                throw ValidationException::withMessages([
                    'batch' => 'Unresolved rows must be fixed before apply.',
                ]);
            }

            $appliedAt = now();
            $summary = array_merge($batch->summary ?? [], [
                'applied' => [
                    'applied_rows' => $batch->rows()->count(),
                    'unresolved_rows' => 0,
                    'applied_at' => $appliedAt->toISOString(),
                ],
            ]);

            $batch->update([
                'summary' => $summary,
                'status' => 'applied',
                'applied_at' => $appliedAt,
            ]);

            return $batch->fresh();
        });
    }
}
