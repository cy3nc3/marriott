<?php

use App\Models\ImportBatch;
use App\Models\User;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();

    $this->actingAs($this->finance);
    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
});

function appliedFinanceBatch(array $attributes = []): ImportBatch
{
    return ImportBatch::query()->create(array_merge([
        'module' => 'finance_transactions',
        'uploaded_by' => test()->finance->id,
        'file_name' => 'finance.csv',
        'file_hash' => str_repeat('a', 64),
        'summary' => [
            'applied' => [
                'applied_rows' => 2,
            ],
        ],
        'status' => 'applied',
        'previewed_at' => now()->subMinutes(2),
        'applied_at' => now()->subMinute(),
    ], $attributes));
}

test('finance rollback records rollback summary entry for applied batches', function () {
    $batch = appliedFinanceBatch();

    $this->postJson("/finance/import-batches/{$batch->id}/rollback")
        ->assertOk()
        ->assertJsonPath('batch.status', 'rolled_back');

    $batch->refresh();

    expect($batch->status)->toBe('rolled_back');
    expect($batch->rolled_back_at)->not->toBeNull();
    expect($batch->summary)->toHaveKey('rollback');
    expect($batch->summary['rollback'])->toMatchArray([
        'rolled_back_by' => $this->finance->id,
        'status_before' => 'applied',
    ]);
});

test('finance rollback requires applied status', function () {
    $batch = appliedFinanceBatch([
        'status' => 'previewed',
        'applied_at' => null,
    ]);

    $this->postJson("/finance/import-batches/{$batch->id}/rollback")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    $batch->refresh();

    expect($batch->status)->toBe('previewed');
    expect($batch->rolled_back_at)->toBeNull();
});
