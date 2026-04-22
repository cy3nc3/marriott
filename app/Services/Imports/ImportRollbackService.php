<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportRollbackService
{
    public function rollback(ImportBatch $batch, int $rolledBackBy): ImportBatch
    {
        return DB::transaction(function () use ($batch, $rolledBackBy): ImportBatch {
            if ($batch->status !== 'applied' || $batch->applied_at === null) {
                throw ValidationException::withMessages([
                    'batch' => 'Only applied batches can be rolled back.',
                ]);
            }

            $rolledBackAt = now();

            $batch->update([
                'summary' => array_merge($batch->summary ?? [], [
                    'rollback' => [
                        'status_before' => 'applied',
                        'rolled_back_by' => $rolledBackBy,
                        'rolled_back_at' => $rolledBackAt->toISOString(),
                    ],
                ]),
                'status' => 'rolled_back',
                'rolled_back_at' => $rolledBackAt,
            ]);

            return $batch->fresh();
        });
    }
}
