<?php

use App\Models\ImportBatch;
use App\Models\ImportMappingProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::dropIfExists('import_row_edits');
    Schema::dropIfExists('import_batch_rows');
    Schema::dropIfExists('import_mapping_profiles');
    Schema::dropIfExists('import_batches');

    Schema::create('import_batches', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->string('source_file_name')->nullable();
        $table->string('status')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('started_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('import_batch_rows', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
        $table->unsignedInteger('row_number')->nullable();
        $table->string('status')->nullable();
        $table->json('raw_values')->nullable();
        $table->json('normalized_values')->nullable();
        $table->json('validation_errors')->nullable();
        $table->json('validation_warnings')->nullable();
        $table->timestamp('processed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('import_row_edits', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('import_batch_row_id')->constrained('import_batch_rows')->cascadeOnDelete();
        $table->string('field_name')->nullable();
        $table->json('original_values')->nullable();
        $table->json('edited_values')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('edited_at')->nullable();
        $table->timestamps();
    });

    Schema::create('import_mapping_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->string('name')->nullable();
        $table->string('source_type')->nullable();
        $table->json('column_mapping')->nullable();
        $table->json('transform_rules')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamps();
    });
});

test('import batch has many rows and rows belong to a batch', function (): void {
    $user = User::factory()->create();

    $batch = ImportBatch::query()->create([
        'created_by' => $user->id,
        'source_file_name' => 'finance-import.csv',
        'status' => 'pending',
        'metadata' => [
            'source' => 'finance',
        ],
        'started_at' => now(),
        'completed_at' => now()->addMinute(),
    ]);

    $row = $batch->rows()->create([
        'row_number' => 1,
        'status' => 'pending',
        'raw_values' => [
            'lrn' => '123456789012',
        ],
        'normalized_values' => [
            'lrn' => '123456789012',
        ],
        'validation_errors' => [
            'lrn' => [],
        ],
        'validation_warnings' => [
            'amount' => ['Rounded to two decimals'],
        ],
        'processed_at' => now(),
    ]);

    $row->edits()->create([
        'field_name' => 'amount',
        'original_values' => [
            'amount' => '1000',
        ],
        'edited_values' => [
            'amount' => '1200',
        ],
        'metadata' => [
            'reason' => 'manual correction',
        ],
        'edited_at' => now(),
    ]);

    expect($batch->rows)->toHaveCount(1);
    expect($batch->rows->first()?->is($row))->toBeTrue();
    expect($row->batch->is($batch))->toBeTrue();
    expect($row->edits)->toHaveCount(1);
    expect($row->edits->first()?->row->is($row))->toBeTrue();
    expect($batch->metadata)->toBeArray();
    expect($batch->started_at)->toBeInstanceOf(CarbonImmutable::class);
    expect($row->raw_values)->toBeArray();
    expect($row->processed_at)->toBeInstanceOf(CarbonImmutable::class);
    expect($row->edits->first()?->metadata)->toBeArray();
});

test('import mapping profile belongs to the creating user', function (): void {
    $user = User::factory()->create();

    $profile = ImportMappingProfile::query()->create([
        'created_by' => $user->id,
        'name' => 'Finance default profile',
        'source_type' => 'finance',
        'column_mapping' => [
            'lrn' => 'Student ID',
        ],
        'transform_rules' => [
            'amount' => 'decimal',
        ],
        'metadata' => [
            'shared' => false,
        ],
        'last_used_at' => now(),
    ]);

    expect($profile->createdBy->is($user))->toBeTrue();
    expect($profile->column_mapping)->toBeArray();
    expect($profile->transform_rules)->toBeArray();
    expect($profile->metadata)->toBeArray();
    expect($profile->last_used_at)->toBeInstanceOf(CarbonImmutable::class);
});
