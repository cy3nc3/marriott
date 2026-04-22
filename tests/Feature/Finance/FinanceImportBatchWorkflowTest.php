<?php

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();

    $this->actingAs($this->finance);
    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
});

function financeImportBatch(array $attributes = []): ImportBatch
{
    return ImportBatch::query()->create(array_merge([
        'module' => 'finance_transactions',
        'uploaded_by' => test()->finance->id,
        'file_name' => 'finance.csv',
        'file_hash' => str_repeat('a', 64),
        'summary' => [
            'uploaded_rows' => 0,
        ],
        'status' => 'uploaded',
    ], $attributes));
}

test('finance apply requires preview before batch apply', function () {
    $batch = financeImportBatch();

    $this->postJson("/finance/import-batches/{$batch->id}/apply")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    $batch->refresh();

    expect($batch->previewed_at)->toBeNull();
    expect($batch->applied_at)->toBeNull();
    expect($batch->status)->toBe('uploaded');
});

test('finance import batch mutations are limited to the uploading owner', function () {
    $batch = financeImportBatch();

    $otherFinanceUser = User::factory()->finance()->create();
    $this->actingAs($otherFinanceUser);

    $this->postJson("/finance/import-batches/{$batch->id}/preview")
        ->assertForbidden();
});

test('finance preview is blocked after apply', function () {
    $batch = financeImportBatch([
        'status' => 'applied',
        'previewed_at' => now()->subMinute(),
        'applied_at' => now(),
    ]);

    $this->postJson("/finance/import-batches/{$batch->id}/preview")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    $batch->refresh();

    expect($batch->status)->toBe('applied');
    expect($batch->rolled_back_at)->toBeNull();
});

test('finance rollback is blocked unless batch is applied', function () {
    $batch = financeImportBatch([
        'status' => 'previewed',
        'previewed_at' => now(),
    ]);

    $this->postJson("/finance/import-batches/{$batch->id}/rollback")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    expect($batch->fresh()->status)->toBe('previewed');
});

test('finance row updates are blocked for terminal batch states', function (string $status, array $timestamps) {
    $batch = financeImportBatch(array_merge([
        'status' => $status,
    ], $timestamps));

    $row = ImportBatchRow::query()->create([
        'import_batch_id' => $batch->id,
        'row_index' => 1,
        'raw_payload' => [
            'or_number' => 'OR-1',
        ],
        'classification' => 'payment',
        'action' => 'pending',
        'is_unresolved' => false,
    ]);

    $this->patchJson("/finance/import-batches/{$batch->id}/rows/{$row->id}", [
        'classification' => 'due',
        'action' => 'update',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    $row->refresh();

    expect($row->classification)->toBe('payment');
    expect($row->action)->toBe('pending');
})->with([
    'applied' => [
        'status' => 'applied',
        'timestamps' => [
            'previewed_at' => now()->subMinutes(2),
            'applied_at' => now()->subMinute(),
        ],
    ],
    'rolled_back' => [
        'status' => 'rolled_back',
        'timestamps' => [
            'previewed_at' => now()->subMinutes(3),
            'applied_at' => now()->subMinutes(2),
            'rolled_back_at' => now()->subMinute(),
        ],
    ],
]);
