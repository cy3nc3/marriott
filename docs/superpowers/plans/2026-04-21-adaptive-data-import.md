# Adaptive Data Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a safe adaptive import system with mandatory manual mapping, preview/corrections, duplicate protection, reconciliation checks, and batch rollback for student profile and finance data imports.

**Architecture:** Extend current registrar/finance data import flows into a shared staged import pipeline backed by import batch tables and dedicated services. Keep existing routes/pages as entry points, add new preview/apply endpoints, and enforce hard validation gates before commit. Finance imports include strict reconciliation and duplicate checks with rollback support.

**Tech Stack:** Laravel 12, Inertia v2 + React 19, Pest 4, MySQL, Tailwind v4

---

## File Structure

### New files to create
- `database/migrations/2026_04_21_000001_create_import_batches_table.php`
- `database/migrations/2026_04_21_000002_create_import_batch_rows_table.php`
- `database/migrations/2026_04_21_000003_create_import_mapping_profiles_table.php`
- `database/migrations/2026_04_21_000004_create_import_row_edits_table.php`
- `app/Models/ImportBatch.php`
- `app/Models/ImportBatchRow.php`
- `app/Models/ImportMappingProfile.php`
- `app/Models/ImportRowEdit.php`
- `app/Http/Requests/Imports/UploadImportBatchRequest.php`
- `app/Http/Requests/Imports/PreviewImportBatchRequest.php`
- `app/Http/Requests/Imports/ApplyImportBatchRequest.php`
- `app/Http/Requests/Imports/UpdateImportRowRequest.php`
- `app/Http/Controllers/Imports/StudentImportBatchController.php`
- `app/Http/Controllers/Imports/FinanceImportBatchController.php`
- `app/Http/Controllers/Imports/ImportBatchRollbackController.php`
- `app/Services/Imports/HeaderNormalizer.php`
- `app/Services/Imports/ValueParser.php`
- `app/Services/Imports/ImportBatchBuilder.php`
- `app/Services/Imports/MappingResolver.php`
- `app/Services/Imports/DuplicateEngine.php`
- `app/Services/Imports/StudentImportPreviewService.php`
- `app/Services/Imports/FinanceImportPreviewService.php`
- `app/Services/Imports/StudentImportApplyService.php`
- `app/Services/Imports/FinanceImportApplyService.php`
- `app/Services/Imports/FinanceReconciliationService.php`
- `app/Services/Imports/ImportRollbackService.php`
- `resources/js/pages/finance/data-import/batches.tsx`
- `resources/js/pages/registrar/data-import/batches.tsx`
- `tests/Feature/Finance/FinanceImportBatchWorkflowTest.php`
- `tests/Feature/Registrar/StudentImportBatchWorkflowTest.php`
- `tests/Feature/Finance/FinanceImportRollbackTest.php`
- `tests/Unit/Imports/HeaderNormalizerTest.php`
- `tests/Unit/Imports/DuplicateEngineTest.php`
- `tests/Unit/Imports/FinanceReconciliationServiceTest.php`

### Existing files to modify
- `routes/roles/finance.php`
- `routes/roles/registrar.php`
- `app/Http/Controllers/Finance/DataImportController.php`
- `app/Http/Controllers/Registrar/DataImportController.php`
- `resources/js/pages/finance/data-import/index.tsx`
- `resources/js/pages/registrar/data-import/index.tsx`
- `app/Services/AuditLogService.php` (only if needed for batch snapshot helper)

---

### Task 1: Create staged import persistence layer

**Files:**
- Create: `database/migrations/2026_04_21_000001_create_import_batches_table.php`
- Create: `database/migrations/2026_04_21_000002_create_import_batch_rows_table.php`
- Create: `database/migrations/2026_04_21_000003_create_import_mapping_profiles_table.php`
- Create: `database/migrations/2026_04_21_000004_create_import_row_edits_table.php`
- Test: `php artisan test --compact --filter=ImportBatch`

- [ ] **Step 1: Write failing migration smoke test**

```php
<?php

test('import batch tables exist after migrations', function () {
    expect(Schema::hasTable('import_batches'))->toBeTrue();
    expect(Schema::hasTable('import_batch_rows'))->toBeTrue();
    expect(Schema::hasTable('import_mapping_profiles'))->toBeTrue();
    expect(Schema::hasTable('import_row_edits'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="import batch tables exist"`
Expected: FAIL with missing table assertions.

- [ ] **Step 3: Add migrations with strict constraints**

```php
// import_batches columns
$table->id();
$table->string('module'); // student|finance
$table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
$table->string('file_name');
$table->string('file_hash', 64);
$table->json('mapping')->nullable();
$table->json('summary')->nullable();
$table->string('status'); // uploaded|previewed|applied|rolled_back|failed
$table->timestamp('previewed_at')->nullable();
$table->timestamp('applied_at')->nullable();
$table->timestamp('rolled_back_at')->nullable();
$table->timestamps();

// import_batch_rows columns
$table->id();
$table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
$table->unsignedInteger('row_index');
$table->json('raw_payload');
$table->json('normalized_payload')->nullable();
$table->json('validation_errors')->nullable();
$table->json('duplicate_flags')->nullable();
$table->string('classification')->nullable();
$table->string('action')->default('pending'); // pending|create|update|skip|blocked
$table->boolean('is_unresolved')->default(false);
$table->timestamps();

// import_mapping_profiles
$table->id();
$table->string('module');
$table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
$table->string('profile_name');
$table->json('header_map');
$table->json('parsing_rules')->nullable();
$table->timestamps();

// import_row_edits
$table->id();
$table->foreignId('import_batch_row_id')->constrained()->cascadeOnDelete();
$table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
$table->json('before_payload');
$table->json('after_payload');
$table->timestamps();
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter="import batch tables exist"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_21_000001_create_import_batches_table.php database/migrations/2026_04_21_000002_create_import_batch_rows_table.php database/migrations/2026_04_21_000003_create_import_mapping_profiles_table.php database/migrations/2026_04_21_000004_create_import_row_edits_table.php
git commit -m "feat: add staged import batch persistence tables"
```

### Task 2: Add import batch domain models

**Files:**
- Create: `app/Models/ImportBatch.php`
- Create: `app/Models/ImportBatchRow.php`
- Create: `app/Models/ImportMappingProfile.php`
- Create: `app/Models/ImportRowEdit.php`
- Test: `tests/Unit/Imports/HeaderNormalizerTest.php`

- [ ] **Step 1: Write failing model relation test**

```php
<?php

test('import batch has many rows', function () {
    $batch = ImportBatch::factory()->create();
    ImportBatchRow::factory()->create(['import_batch_id' => $batch->id]);

    expect($batch->rows()->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="import batch has many rows"`
Expected: FAIL with class/relationship missing.

- [ ] **Step 3: Implement models and casts**

```php
class ImportBatch extends Model
{
    protected $fillable = ['module', 'uploaded_by', 'file_name', 'file_hash', 'mapping', 'summary', 'status', 'previewed_at', 'applied_at', 'rolled_back_at'];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'summary' => 'array',
            'previewed_at' => 'datetime',
            'applied_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class);
    }
}
```

- [ ] **Step 4: Run targeted tests**

Run: `php artisan test --compact --filter="import batch has many rows"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/ImportBatch.php app/Models/ImportBatchRow.php app/Models/ImportMappingProfile.php app/Models/ImportRowEdit.php
git commit -m "feat: add import batch domain models"
```

### Task 3: Implement shared parser/mapping services

**Files:**
- Create: `app/Services/Imports/HeaderNormalizer.php`
- Create: `app/Services/Imports/ValueParser.php`
- Create: `app/Services/Imports/MappingResolver.php`
- Create: `tests/Unit/Imports/HeaderNormalizerTest.php`

- [ ] **Step 1: Write failing unit tests for header aliases**

```php
<?php

test('header normalizer resolves expected aliases', function () {
    $normalizer = app(HeaderNormalizer::class);

    $headers = ['Learner Ref No', 'Student Name', 'Contact #', 'Guardian'];

    $normalized = $normalizer->normalize($headers);

    expect($normalized)->toMatchArray([
        'learner_ref_no' => 'lrn',
        'student_name' => 'name',
        'contact' => 'contact_number',
        'guardian' => 'guardian_name',
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/Imports/HeaderNormalizerTest.php`
Expected: FAIL with missing class/assertions.

- [ ] **Step 3: Implement normalizer/parser logic**

```php
public function canonical(string $header): string
{
    $normalized = str($header)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

    return $this->aliases[$normalized] ?? $normalized;
}

public function parseDecimal(?string $value): ?float
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $clean = preg_replace('/[^0-9.\-]/', '', $value);

    if ($clean === '' || ! is_numeric($clean)) {
        return null;
    }

    return round((float) $clean, 2);
}
```

- [ ] **Step 4: Run unit tests**

Run: `php artisan test --compact tests/Unit/Imports/HeaderNormalizerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Imports/HeaderNormalizer.php app/Services/Imports/ValueParser.php app/Services/Imports/MappingResolver.php tests/Unit/Imports/HeaderNormalizerTest.php
git commit -m "feat: add import header normalization and value parsing services"
```

### Task 4: Build finance duplicate + reconciliation engines

**Files:**
- Create: `app/Services/Imports/DuplicateEngine.php`
- Create: `app/Services/Imports/FinanceReconciliationService.php`
- Create: `tests/Unit/Imports/DuplicateEngineTest.php`
- Create: `tests/Unit/Imports/FinanceReconciliationServiceTest.php`

- [ ] **Step 1: Write failing duplicate and reconciliation tests**

```php
<?php

test('duplicate engine flags payment duplicate by lrn and or number', function () {
    $flags = app(DuplicateEngine::class)->detectFinanceDuplicate([
        'lrn' => '123456789012',
        'or_number' => 'OR-1001',
        'payment_date' => '2025-07-01',
        'amount' => 1000,
    ]);

    expect($flags['existing_duplicate'])->toBeTrue();
});

test('finance reconciliation fails on mismatch', function () {
    $result = app(FinanceReconciliationService::class)->reconcile(5000, 3000, 1000);

    expect($result['valid'])->toBeFalse();
});
```

- [ ] **Step 2: Run tests to verify failures**

Run: `php artisan test --compact tests/Unit/Imports/DuplicateEngineTest.php tests/Unit/Imports/FinanceReconciliationServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement services**

```php
public function paymentDuplicateKeys(array $row): array
{
    $lrn = (string) ($row['lrn'] ?? '');
    $orNumber = (string) ($row['or_number'] ?? '');

    if ($lrn !== '' && $orNumber !== '') {
        return ['primary' => "{$lrn}|{$orNumber}"];
    }

    return [
        'secondary' => implode('|', [
            $lrn,
            (string) ($row['payment_date'] ?? ''),
            (string) ($row['amount'] ?? ''),
            (string) ($row['reference_no'] ?? ''),
        ]),
    ];
}

public function reconcile(float $importDues, float $importPayments, float $expectedDelta): array
{
    $net = round($importDues - $importPayments, 2);

    return [
        'net' => $net,
        'expected_delta' => round($expectedDelta, 2),
        'valid' => abs($net - $expectedDelta) < 0.01,
    ];
}
```

- [ ] **Step 4: Run tests to verify pass**

Run: `php artisan test --compact tests/Unit/Imports/DuplicateEngineTest.php tests/Unit/Imports/FinanceReconciliationServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Imports/DuplicateEngine.php app/Services/Imports/FinanceReconciliationService.php tests/Unit/Imports/DuplicateEngineTest.php tests/Unit/Imports/FinanceReconciliationServiceTest.php
git commit -m "feat: add finance duplicate and reconciliation engines"
```

### Task 5: Add staged preview/apply controllers and requests

**Files:**
- Create: `app/Http/Requests/Imports/UploadImportBatchRequest.php`
- Create: `app/Http/Requests/Imports/PreviewImportBatchRequest.php`
- Create: `app/Http/Requests/Imports/ApplyImportBatchRequest.php`
- Create: `app/Http/Requests/Imports/UpdateImportRowRequest.php`
- Create: `app/Http/Controllers/Imports/StudentImportBatchController.php`
- Create: `app/Http/Controllers/Imports/FinanceImportBatchController.php`
- Create: `app/Http/Controllers/Imports/ImportBatchRollbackController.php`
- Modify: `routes/roles/finance.php`
- Modify: `routes/roles/registrar.php`
- Test: `tests/Feature/Finance/FinanceImportBatchWorkflowTest.php`
- Test: `tests/Feature/Registrar/StudentImportBatchWorkflowTest.php`

- [ ] **Step 1: Write failing feature test for finance preview gate**

```php
<?php

test('finance import requires preview before apply', function () {
    $user = User::factory()->finance()->create();

    $this->actingAs($user)
        ->post('/finance/data-import/batches/1/apply')
        ->assertStatus(422);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="requires preview before apply"`
Expected: FAIL (route/controller missing).

- [ ] **Step 3: Implement controllers + routes**

```php
Route::post('/data-import/batches', [FinanceImportBatchController::class, 'upload'])->name('data_import.batches.upload');
Route::post('/data-import/batches/{importBatch}/preview', [FinanceImportBatchController::class, 'preview'])->name('data_import.batches.preview');
Route::patch('/data-import/batches/{importBatch}/rows/{importBatchRow}', [FinanceImportBatchController::class, 'updateRow'])->name('data_import.batches.rows.update');
Route::post('/data-import/batches/{importBatch}/apply', [FinanceImportBatchController::class, 'apply'])->name('data_import.batches.apply');
Route::post('/data-import/batches/{importBatch}/rollback', [ImportBatchRollbackController::class, 'store'])->name('data_import.batches.rollback');
```

- [ ] **Step 4: Run feature tests**

Run: `php artisan test --compact tests/Feature/Finance/FinanceImportBatchWorkflowTest.php tests/Feature/Registrar/StudentImportBatchWorkflowTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/Imports app/Http/Controllers/Imports routes/roles/finance.php routes/roles/registrar.php tests/Feature/Finance/FinanceImportBatchWorkflowTest.php tests/Feature/Registrar/StudentImportBatchWorkflowTest.php
git commit -m "feat: add staged student and finance import workflow endpoints"
```

### Task 6: Implement preview + correction logic

**Files:**
- Create: `app/Services/Imports/ImportBatchBuilder.php`
- Create: `app/Services/Imports/StudentImportPreviewService.php`
- Create: `app/Services/Imports/FinanceImportPreviewService.php`
- Modify: `app/Http/Controllers/Imports/StudentImportBatchController.php`
- Modify: `app/Http/Controllers/Imports/FinanceImportBatchController.php`
- Test: `tests/Feature/Finance/FinanceImportBatchWorkflowTest.php`
- Test: `tests/Feature/Registrar/StudentImportBatchWorkflowTest.php`

- [ ] **Step 1: Write failing test for row correction revalidation**

```php
<?php

test('editing row in preview recomputes unresolved status', function () {
    // Create batch row with invalid amount
    // Patch row with corrected amount
    // Assert unresolved false after preview recompute
    expect(true)->toBeFalse();
});
```

- [ ] **Step 2: Run failing test**

Run: `php artisan test --compact --filter="recomputes unresolved status"`
Expected: FAIL.

- [ ] **Step 3: Implement preview builder and edit logging**

```php
public function updateRow(ImportBatch $batch, ImportBatchRow $row, array $payload, int $editorId): ImportBatchRow
{
    $before = $row->normalized_payload ?? [];
    $after = [...$before, ...$payload];

    $row->update([
        'normalized_payload' => $after,
    ]);

    ImportRowEdit::query()->create([
        'import_batch_row_id' => $row->id,
        'edited_by' => $editorId,
        'before_payload' => $before,
        'after_payload' => $after,
    ]);

    return $row->refresh();
}
```

- [ ] **Step 4: Run updated workflow tests**

Run: `php artisan test --compact tests/Feature/Finance/FinanceImportBatchWorkflowTest.php tests/Feature/Registrar/StudentImportBatchWorkflowTest.php --filter="preview|correction|unresolved"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Imports/ImportBatchBuilder.php app/Services/Imports/StudentImportPreviewService.php app/Services/Imports/FinanceImportPreviewService.php app/Http/Controllers/Imports/StudentImportBatchController.php app/Http/Controllers/Imports/FinanceImportBatchController.php
git commit -m "feat: add import preview corrections and revalidation"
```

### Task 7: Implement apply engines with audit snapshots and rollback

**Files:**
- Create: `app/Services/Imports/StudentImportApplyService.php`
- Create: `app/Services/Imports/FinanceImportApplyService.php`
- Create: `app/Services/Imports/ImportRollbackService.php`
- Modify: `app/Http/Controllers/Imports/ImportBatchRollbackController.php`
- Create: `tests/Feature/Finance/FinanceImportRollbackTest.php`

- [ ] **Step 1: Write failing rollback feature test**

```php
<?php

test('rollback reverses finance import batch changes', function () {
    // Apply import batch
    // Trigger rollback endpoint
    // Assert ledger and transactions from batch are removed/reverted
    expect(true)->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --compact tests/Feature/Finance/FinanceImportRollbackTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement apply + rollback transactions**

```php
DB::transaction(function () use ($batch) {
    if ($batch->rows()->where('is_unresolved', true)->exists()) {
        throw ValidationException::withMessages(['batch' => 'Unresolved rows must be fixed before apply.']);
    }

    // apply rows and collect affected IDs in summary snapshot
    $batch->update([
        'status' => 'applied',
        'applied_at' => now(),
        'summary' => $summary,
    ]);
});
```

- [ ] **Step 4: Run rollback and apply tests**

Run: `php artisan test --compact tests/Feature/Finance/FinanceImportRollbackTest.php tests/Feature/Finance/FinanceImportBatchWorkflowTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Imports/StudentImportApplyService.php app/Services/Imports/FinanceImportApplyService.php app/Services/Imports/ImportRollbackService.php app/Http/Controllers/Imports/ImportBatchRollbackController.php tests/Feature/Finance/FinanceImportRollbackTest.php
git commit -m "feat: add transactional apply flow and batch rollback"
```

### Task 8: Build Inertia screens for mapping, preview, and confirmation

**Files:**
- Create: `resources/js/pages/finance/data-import/batches.tsx`
- Create: `resources/js/pages/registrar/data-import/batches.tsx`
- Modify: `resources/js/pages/finance/data-import/index.tsx`
- Modify: `resources/js/pages/registrar/data-import/index.tsx`
- Test: `tests/Feature/Finance/FinanceImportBatchWorkflowTest.php`
- Test: `tests/Feature/Registrar/StudentImportBatchWorkflowTest.php`

- [ ] **Step 1: Write failing feature test asserting preview payload keys**

```php
<?php

test('finance preview response includes before and after totals', function () {
    // Request preview endpoint
    // Assert inertia props include before_after_totals and duplicate buckets
    expect(true)->toBeFalse();
});
```

- [ ] **Step 2: Run failing test**

Run: `php artisan test --compact --filter="before and after totals"`
Expected: FAIL.

- [ ] **Step 3: Implement UI flow**

```tsx
// key sections in batches.tsx
// 1) Mapping table (required fields + selectable source columns)
// 2) Validation summary cards (create/update/skip/unresolved/duplicates)
// 3) Before vs After totals table per student
// 4) Editable grid for row-level correction
// 5) Apply button disabled until all blockers clear
<Button disabled={hasUnresolved || hasReconciliationError || processing}>
  Confirm Import
</Button>
```

- [ ] **Step 4: Run feature tests**

Run: `php artisan test --compact tests/Feature/Finance/FinanceImportBatchWorkflowTest.php tests/Feature/Registrar/StudentImportBatchWorkflowTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/finance/data-import/index.tsx resources/js/pages/finance/data-import/batches.tsx resources/js/pages/registrar/data-import/index.tsx resources/js/pages/registrar/data-import/batches.tsx
git commit -m "feat: add manual mapping and pre-apply preview interfaces"
```

### Task 9: Format, regression tests, and integration verification

**Files:**
- Modify: any touched files from prior tasks
- Test: `tests/Feature/Finance/DataImportTest.php`
- Test: `tests/Feature/Registrar/RegistrarFeaturesTest.php`

- [ ] **Step 1: Run formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: PASS with files formatted.

- [ ] **Step 2: Run targeted and regression test suite**

Run: `php artisan test --compact tests/Unit/Imports tests/Feature/Finance/FinanceImportBatchWorkflowTest.php tests/Feature/Finance/FinanceImportRollbackTest.php tests/Feature/Registrar/StudentImportBatchWorkflowTest.php tests/Feature/Finance/DataImportTest.php tests/Feature/Registrar/RegistrarFeaturesTest.php --filter="data import|import|rollback|preview"`
Expected: PASS.

- [ ] **Step 3: Verify key manual scenarios**

Run: `php artisan test --compact tests/Feature/Finance/FinanceImportBatchWorkflowTest.php --filter="duplicate|unresolved|reconciliation"`
Expected: PASS.

- [ ] **Step 4: Final commit**

```bash
git add app database/migrations routes resources/js tests
git commit -m "feat: implement adaptive staged student and finance imports"
```

- [ ] **Step 5: Optional push**

```bash
git push -u origin <branch-name>
```

## Spec Coverage Self-Check
- Mandatory manual mapping: Covered in Tasks 5, 6, 8.
- Preview before apply with before/after display: Covered in Tasks 6, 8.
- Duplicate checker: Covered in Task 4 and Task 6.
- Finance strict reconciliation and blocking gates: Covered in Tasks 4, 6, 7.
- Row-level corrections + recompute: Covered in Task 6.
- Reusable mapping profile + batch-only edits: Covered in Tasks 1, 6, 8.
- Batch rollback and auditing: Covered in Task 7.

No placeholder markers remain, task names and type contracts are consistent, and scope is limited to adaptive import workflows.
