<?php

use App\Models\ImportBatch;
use App\Models\ImportMappingProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('import batch relationships persist against the migrated schema', function (): void {
    $uploadedBy = User::factory()->create();
    $editedBy = User::factory()->create();

    $batch = ImportBatch::query()->create([
        'module' => 'finance',
        'uploaded_by' => $uploadedBy->id,
        'file_name' => 'finance-import.csv',
        'file_hash' => str_repeat('a', 64),
        'mapping' => [
            'lrn' => 'Student ID',
        ],
        'summary' => [
            'rows' => 1,
        ],
        'status' => 'pending',
        'previewed_at' => now(),
        'applied_at' => null,
        'rolled_back_at' => null,
    ]);

    $row = $batch->rows()->create([
        'row_index' => 1,
        'raw_payload' => [
            'Student ID' => '123456789012',
        ],
        'normalized_payload' => [
            'lrn' => '123456789012',
        ],
        'validation_errors' => [
            'lrn' => [],
        ],
        'duplicate_flags' => [
            'existing_duplicate' => false,
        ],
        'classification' => 'payment',
        'action' => 'pending',
        'is_unresolved' => true,
    ]);

    $edit = $row->edits()->create([
        'edited_by' => $editedBy->id,
        'before_payload' => [
            'amount' => 1000,
        ],
        'after_payload' => [
            'amount' => 1200,
        ],
    ]);

    $profile = ImportMappingProfile::query()->create([
        'module' => 'finance',
        'created_by' => $uploadedBy->id,
        'profile_name' => 'Finance default profile',
        'header_map' => [
            'Student ID' => 'lrn',
        ],
        'parsing_rules' => [
            'amount' => 'decimal',
        ],
    ]);

    expect($batch->fresh()->uploadedBy->is($uploadedBy))->toBeTrue();
    expect($batch->rows)->toHaveCount(1);
    expect($row->batch->is($batch))->toBeTrue();
    expect($row->edits)->toHaveCount(1);
    expect($edit->row->is($row))->toBeTrue();
    expect($edit->editedBy->is($editedBy))->toBeTrue();
    expect($profile->createdBy->is($uploadedBy))->toBeTrue();
    expect($batch->mapping)->toBeArray();
    expect($batch->previewed_at)->toBeInstanceOf(CarbonImmutable::class);
    expect($row->raw_payload)->toBeArray();
    expect($row->is_unresolved)->toBeTrue();
    expect($edit->before_payload)->toBeArray();
});
