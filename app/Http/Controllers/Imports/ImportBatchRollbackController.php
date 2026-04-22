<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImportBatchRollbackController extends Controller
{
    public function store(Request $request, ImportBatch $importBatch): JsonResponse
    {
        $expectedModule = $this->expectedModule($request);

        abort_unless($expectedModule !== null && $importBatch->module === $expectedModule, 404);

        if ($importBatch->applied_at === null) {
            throw ValidationException::withMessages([
                'batch' => 'Apply is required before rolling back this import batch.',
            ]);
        }

        $importBatch->update([
            'summary' => array_merge($importBatch->summary ?? [], [
                'rollback_stub' => true,
            ]),
            'status' => 'rolled_back',
            'rolled_back_at' => now(),
        ]);

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
}
