<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('import batch tables exist after migrations', function (): void {
    expect(Schema::hasTable('import_batches'))->toBeTrue();
    expect(Schema::hasTable('import_batch_rows'))->toBeTrue();
    expect(Schema::hasTable('import_mapping_profiles'))->toBeTrue();
    expect(Schema::hasTable('import_row_edits'))->toBeTrue();

    expect(Schema::hasColumns('import_batches', [
        'module',
        'uploaded_by',
        'file_name',
        'file_hash',
        'mapping',
        'summary',
        'status',
        'previewed_at',
        'applied_at',
        'rolled_back_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('import_batch_rows', [
        'import_batch_id',
        'row_index',
        'raw_payload',
        'normalized_payload',
        'validation_errors',
        'duplicate_flags',
        'classification',
        'action',
        'is_unresolved',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('import_mapping_profiles', [
        'module',
        'created_by',
        'profile_name',
        'header_map',
        'parsing_rules',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('import_row_edits', [
        'import_batch_row_id',
        'edited_by',
        'before_payload',
        'after_payload',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
