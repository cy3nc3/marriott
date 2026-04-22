<?php

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();

    $this->actingAs($this->finance);
    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
});

test('finance apply requires preview before batch apply', function () {
    $batch = ImportBatch::query()->create([
        'module' => 'finance_transactions',
        'uploaded_by' => $this->finance->id,
        'file_name' => 'finance.csv',
        'file_hash' => str_repeat('a', 64),
        'summary' => [
            'uploaded_rows' => 0,
        ],
        'status' => 'uploaded',
    ]);

    $this->postJson("/finance/import-batches/{$batch->id}/apply")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    $batch->refresh();

    expect($batch->previewed_at)->toBeNull();
    expect($batch->applied_at)->toBeNull();
    expect($batch->status)->toBe('uploaded');
});
