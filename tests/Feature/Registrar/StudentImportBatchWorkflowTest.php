<?php

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\ImportRowEdit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->registrar = User::factory()->registrar()->create();

    $this->actingAs($this->registrar);
    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
});

function registrarImportBatch(array $attributes = []): ImportBatch
{
    return ImportBatch::query()->create(array_merge([
        'module' => 'registrar_students',
        'uploaded_by' => test()->registrar->id,
        'file_name' => 'students.csv',
        'file_hash' => str_repeat('b', 64),
        'summary' => [
            'uploaded_rows' => 0,
        ],
        'status' => 'uploaded',
    ], $attributes));
}

test('registrar can run the staged student import batch workflow', function () {
    $uploadResponse = $this->postJson('/registrar/import-batches', [
        'import_file' => UploadedFile::fake()->createWithContent('students.csv', "lrn,first_name\n100000000001,Ana\n"),
    ]);

    $uploadResponse
        ->assertCreated()
        ->assertJsonPath('batch.module', 'registrar_students')
        ->assertJsonPath('batch.status', 'uploaded');

    $batch = ImportBatch::query()->findOrFail($uploadResponse->json('batch.id'));

    $row = ImportBatchRow::query()->create([
        'import_batch_id' => $batch->id,
        'row_index' => 1,
        'raw_payload' => [
            'lrn' => '100000000001',
            'first_name' => 'Ana',
        ],
        'action' => 'pending',
        'is_unresolved' => true,
    ]);

    $this->patchJson("/registrar/import-batches/{$batch->id}/rows/{$row->id}", [
        'normalized_payload' => [
            'lrn' => '100000000001',
            'first_name' => 'Ana Marie',
        ],
        'validation_errors' => [],
        'duplicate_flags' => [],
        'classification' => 'payment',
        'action' => 'create',
        'is_unresolved' => false,
    ])->assertOk()
        ->assertJsonPath('row.action', 'create')
        ->assertJsonPath('row.is_unresolved', false);

    expect(ImportRowEdit::query()
        ->where('import_batch_row_id', $row->id)
        ->where('edited_by', $this->registrar->id)
        ->exists())->toBeTrue();

    $this->postJson("/registrar/import-batches/{$batch->id}/preview")
        ->assertOk()
        ->assertJsonPath('batch.status', 'previewed');

    $this->postJson("/registrar/import-batches/{$batch->id}/apply")
        ->assertOk()
        ->assertJsonPath('batch.status', 'applied');

    $this->postJson("/registrar/import-batches/{$batch->id}/rollback")
        ->assertOk()
        ->assertJsonPath('batch.status', 'rolled_back');

    $batch->refresh();
    $row->refresh();

    expect($batch->module)->toBe('registrar_students');
    expect($batch->uploaded_by)->toBe($this->registrar->id);
    expect($batch->previewed_at)->not->toBeNull();
    expect($batch->applied_at)->not->toBeNull();
    expect($batch->rolled_back_at)->not->toBeNull();
    expect($batch->status)->toBe('rolled_back');
    expect($row->normalized_payload)->toBe([
        'lrn' => '100000000001',
        'first_name' => 'Ana Marie',
    ]);
});

test('registrar apply requires preview before batch apply', function () {
    $batch = registrarImportBatch();

    $this->postJson("/registrar/import-batches/{$batch->id}/apply")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    $batch->refresh();

    expect($batch->previewed_at)->toBeNull();
    expect($batch->applied_at)->toBeNull();
    expect($batch->status)->toBe('uploaded');
});

test('registrar import batch mutations are limited to the uploading owner', function () {
    $batch = registrarImportBatch();

    $row = ImportBatchRow::query()->create([
        'import_batch_id' => $batch->id,
        'row_index' => 1,
        'raw_payload' => [
            'lrn' => '100000000001',
        ],
        'classification' => 'unresolved',
        'action' => 'pending',
        'is_unresolved' => true,
    ]);

    $otherRegistrar = User::factory()->registrar()->create();
    $this->actingAs($otherRegistrar);

    $this->patchJson("/registrar/import-batches/{$batch->id}/rows/{$row->id}", [
        'classification' => 'mixed',
        'action' => 'blocked',
    ])->assertForbidden();
});

test('registrar preview is blocked after apply', function () {
    $batch = registrarImportBatch([
        'status' => 'applied',
        'previewed_at' => now()->subMinute(),
        'applied_at' => now(),
    ]);

    $this->postJson("/registrar/import-batches/{$batch->id}/preview")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    expect($batch->fresh()->status)->toBe('applied');
});

test('registrar rollback is blocked unless batch is applied', function () {
    $batch = registrarImportBatch([
        'status' => 'previewed',
        'previewed_at' => now(),
    ]);

    $this->postJson("/registrar/import-batches/{$batch->id}/rollback")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['batch']);

    expect($batch->fresh()->status)->toBe('previewed');
});

test('registrar row updates are blocked for terminal batch states', function (string $status, array $timestamps) {
    $batch = registrarImportBatch(array_merge([
        'status' => $status,
    ], $timestamps));

    $row = ImportBatchRow::query()->create([
        'import_batch_id' => $batch->id,
        'row_index' => 1,
        'raw_payload' => [
            'lrn' => '100000000001',
        ],
        'classification' => 'payment',
        'action' => 'pending',
        'is_unresolved' => false,
    ]);

    $this->patchJson("/registrar/import-batches/{$batch->id}/rows/{$row->id}", [
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
