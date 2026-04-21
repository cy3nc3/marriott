# Three-Year Seed Snapshot (Q1-Consistent) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a deterministic 3-year dataset snapshot where SY 2025-2026 is currently running in early Quarter 1, with complete data coverage across registrar, finance, academic, and system pages.

**Architecture:** Introduce one orchestrator seeder (`ProductionThreeYearSnapshotSeeder`) that composes existing baseline seeders, then applies a strict data-shaping pass for accounts, sections, schedules, students, enrollments, finance, and academics. Keep current-year data Q1-only (no Q2/Q3/Q4 artifacts), while completed years retain complete historical outcomes. Use deterministic generators for repeatable outputs.

**Tech Stack:** Laravel 12, Eloquent seeders, Pest 4 feature tests, BillingScheduleService, existing models/services.

---

### Task 1: Lock Snapshot Scope and Seeder Entry

**Files:**
- Create: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Write the failing test for academic year timeline + entry seeder**

```php
it('creates a 3-year snapshot with 2025-2026 as ongoing quarter 1', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    expect(\App\Models\AcademicYear::query()->where('name', '2023-2024')->value('status'))->toBe('completed');
    expect(\App\Models\AcademicYear::query()->where('name', '2024-2025')->value('status'))->toBe('completed');

    $current = \App\Models\AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
    expect($current->status)->toBe('ongoing');
    expect((string) $current->current_quarter)->toBe('1');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=creates\ a\ 3-year\ snapshot`
Expected: FAIL (seeder/class not found).

- [ ] **Step 3: Create seeder skeleton + wire in DatabaseSeeder (if needed for local default)**

```php
class ProductionThreeYearSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        // orchestrator body implemented in later tasks
    }
}
```

- [ ] **Step 4: Re-run the test**

Run: same command as Step 2.
Expected: still FAIL on assertions until timeline logic is implemented.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): scaffold three-year snapshot seeder and timeline test"
```

### Task 2: Seed Required Default Staff Accounts (Named)

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing test for named defaults**

```php
it('seeds required default staff accounts', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    $admin = \App\Models\User::query()->where('role', \App\Enums\UserRole::ADMIN)->firstOrFail();
    $registrar = \App\Models\User::query()->where('role', \App\Enums\UserRole::REGISTRAR)->firstOrFail();
    $finance = \App\Models\User::query()->where('role', \App\Enums\UserRole::FINANCE)->firstOrFail();

    expect($admin->first_name.' '.$admin->last_name)->toBe('Alex Avellanosa');
    expect($registrar->first_name.' '.$registrar->last_name)->toBe('Jocelyn Cleofe');
    expect($finance->first_name.' '.$finance->last_name)->toBe('Corrine Avellanosa');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=required\ default\ staff`
Expected: FAIL.

- [ ] **Step 3: Implement deterministic staff upsert in seeder**

```php
private function seedNamedStaffAccounts(): void
{
    $this->upsertStaffAccount(UserRole::ADMIN, 'admin@marriott.edu', 'Alex', 'Avellanosa');
    $this->upsertStaffAccount(UserRole::REGISTRAR, 'registrar@marriott.edu', 'Jocelyn', 'Cleofe');
    $this->upsertStaffAccount(UserRole::FINANCE, 'finance@marriott.edu', 'Corrine', 'Avellanosa');
}
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): seed required named admin, registrar, finance accounts"
```

### Task 3: Seed Grade 7-10 Sections from Provided Image Scope

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing test for section set**

```php
it('seeds only requested JHS sections for grade 7 to 10 in current year', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    $year = \App\Models\AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
    $pairs = \App\Models\Section::query()
        ->where('academic_year_id', $year->id)
        ->with('gradeLevel:id,name,level_order')
        ->get()
        ->map(fn ($section) => $section->gradeLevel->level_order.'|'.$section->name)
        ->sort()
        ->values()
        ->all();

    expect($pairs)->toBe([
        '10|St. Anne',
        '10|St. John',
        '7|St. Paul',
        '8|St. Anthony',
        '9|St. Francis',
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=requested\ JHS\ sections`
Expected: FAIL.

- [ ] **Step 3: Implement section blueprint + replace current-year sections before insert**

```php
private const SECTION_BLUEPRINT = [
    7 => ['St. Paul'],
    8 => ['St. Anthony'],
    9 => ['St. Francis'],
    10 => ['St. John', 'St. Anne'],
];
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): enforce grade 7-10 section blueprint for current year"
```

### Task 4: Seed Teachers and Schedules from Workbook-Constrained Blueprint

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing test for teacher schedule coverage**

```php
it('seeds workbook-aligned teacher schedules and assignments', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    $teacher = \App\Models\User::query()->where('email', 'rocelle.delacruz@marriott.edu')->firstOrFail();

    $hasAssignment = \App\Models\TeacherSubject::query()
        ->where('teacher_id', $teacher->id)
        ->exists();

    $hasSchedule = \App\Models\ClassSchedule::query()
        ->where('day', 'Monday')
        ->exists();

    expect($hasAssignment)->toBeTrue();
    expect($hasSchedule)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=workbook-aligned\ teacher\ schedules`
Expected: FAIL.

- [ ] **Step 3: Implement explicit teacher blueprint from workbook**

```php
private const TEACHER_SCHEDULE_BLUEPRINT = [
    ['first_name' => 'Rowell', 'last_name' => 'Almonte', 'email' => 'rowell.almonte@marriott.edu', 'subject' => 'Filipino', 'slots' => [/* 5 section slots */]],
    ['first_name' => 'Rocelle', 'last_name' => 'De la Cruz', 'email' => 'rocelle.delacruz@marriott.edu', 'subject' => 'Math', 'slots' => [/* 5 */]],
    ['first_name' => 'Fe Mercedes', 'last_name' => 'Cavitt', 'email' => 'fe.cavitt@marriott.edu', 'subject' => 'Edukasyon sa Pagpapakatao', 'slots' => [/* 5 */]],
    ['first_name' => 'Elenor', 'last_name' => 'Cendana', 'email' => 'elenor.cendana@marriott.edu', 'subject' => 'English', 'slots' => [/* 5 */]],
    ['first_name' => 'Ma Nimfa', 'last_name' => 'Guinacaran', 'email' => 'manimfa.guinacaran@marriott.edu', 'subject' => 'MAPEH', 'slots' => [/* 5 */]],
    ['first_name' => 'Mary Joyce', 'last_name' => 'Guira', 'email' => 'maryjoyce.guira@marriott.edu', 'subject' => 'Araling Panlipunan', 'slots' => [/* 5 */]],
    ['first_name' => 'Racquel', 'last_name' => 'Vergara', 'email' => 'racquel.vergara@marriott.edu', 'subject' => 'Technology and Livelihood Education', 'slots' => [/* 5 */]],
    ['first_name' => 'Beronica', 'last_name' => 'Renton', 'email' => 'beronica.renton@marriott.edu', 'subject' => 'Science', 'slots' => [/* 5 */]],
];
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): add workbook-aligned teacher subject assignments and schedules"
```

### Task 5: Seed 25 Students per Section with Uniform LRN + Filipino Names

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing test for student volume and LRN format**

```php
it('seeds exactly 25 students per current-year section with uniform 12-digit LRN', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    $year = \App\Models\AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
    $sections = \App\Models\Section::query()->where('academic_year_id', $year->id)->get();

    foreach ($sections as $section) {
        $count = \App\Models\Enrollment::query()
            ->where('academic_year_id', $year->id)
            ->where('section_id', $section->id)
            ->count();

        expect($count)->toBe(25);
    }

    $lrn = \App\Models\Student::query()->value('lrn');
    expect((bool) preg_match('/^\\d{12}$/', (string) $lrn))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=25\ students\ per\ current-year\ section`
Expected: FAIL.

- [ ] **Step 3: Implement deterministic generators**

```php
private const STUDENTS_PER_SECTION = 25;
private const LRN_PREFIX_BY_YEAR = ['2023-2024' => '2324', '2024-2025' => '2425', '2025-2026' => '2526'];

private function makeLrn(string $schoolYear, int $gradeOrder, int $sectionIndex, int $studentIndex): string
{
    return sprintf('%s%02d%02d%04d', self::LRN_PREFIX_BY_YEAR[$schoolYear], $gradeOrder, $sectionIndex, $studentIndex);
}
```

```php
private const FILIPINO_FIRST_NAMES = ['Alon', 'Bituin', 'Diwa', 'Ligaya', 'Mayumi', 'Sinta', 'Bayani', 'Himig', 'Lualhati', 'Sampaguita'];
private const FILIPINO_LAST_NAMES = ['Abad', 'Bautista', 'Castillo', 'Dela Cruz', 'Domingo', 'Lacsamana', 'Magsino', 'Panganiban', 'Salonga', 'Valdez'];
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): add deterministic 25-per-section students with uniform 12-digit LRNs"
```

### Task 6: Enroll Across 3 Years with Progression + Current-Year Intake Mix

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing tests for 3-year enrollment shape**

```php
it('seeds historical years as complete and current year with mixed intake statuses', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    $y2324 = \App\Models\AcademicYear::query()->where('name', '2023-2024')->firstOrFail();
    $y2425 = \App\Models\AcademicYear::query()->where('name', '2024-2025')->firstOrFail();
    $y2526 = \App\Models\AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

    expect(\App\Models\Enrollment::query()->where('academic_year_id', $y2324->id)->count())->toBeGreaterThan(0);
    expect(\App\Models\Enrollment::query()->where('academic_year_id', $y2425->id)->count())->toBeGreaterThan(0);

    $currentStatuses = \App\Models\Enrollment::query()->where('academic_year_id', $y2526->id)->pluck('status')->unique()->all();
    expect($currentStatuses)->toContain('enrolled');
    expect($currentStatuses)->toContain('for_cashier_payment');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=historical\ years\ as\ complete`
Expected: FAIL.

- [ ] **Step 3: Implement enrollment policy**

```php
// 2023-2024: all seeded enrollments enrolled + final outcomes present
// 2024-2025: all seeded enrollments enrolled + final outcomes present
// 2025-2026: mostly enrolled, controlled portion for_cashier_payment for registrar/finance queues
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): implement three-year enrollment progression and current-year intake mix"
```

### Task 7: Finance Data Coverage (Fees, Billing, Ledger, Transactions)

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing finance coverage test**

```php
it('seeds finance artifacts needed by cashier and ledger screens', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    expect(\App\Models\Fee::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\BillingSchedule::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\LedgerEntry::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\Transaction::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\Discount::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\InventoryItem::query()->count())->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=finance\ artifacts`
Expected: FAIL.

- [ ] **Step 3: Implement finance seeding pass**

```php
private function seedFinanceArtifacts(AcademicYear $currentYear, Collection $enrollments): void
{
    // fee structure by grade
    // discounts + student_discounts
    // inventory catalog
    // billing schedules via BillingScheduleService
    // opening balances + day-based posted transactions
}
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): add finance coverage for fees billing ledger and transactions"
```

### Task 8: Academic Coverage Rule (Q1-Only for Current Year)

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing test for Q1-only current-year academics**

```php
it('seeds only quarter 1 academic artifacts for current year', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    $year = \App\Models\AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

    $nonQ1Activities = \App\Models\GradedActivity::query()->where('quarter', '!=', '1')->count();
    $nonQ1Submissions = \App\Models\GradeSubmission::query()
        ->where('academic_year_id', $year->id)
        ->where('quarter', '!=', '1')
        ->count();

    expect($nonQ1Submissions)->toBe(0);
    expect($nonQ1Activities)->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=only\ quarter\ 1\ academic`
Expected: FAIL.

- [ ] **Step 3: Implement academics phase split**

```php
// completed years: final grades + conduct + permanent records
// current year: attendance for first 45 school days, Q1 graded activities/scores, submissions mostly draft
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): enforce q1-only current-year academic dataset"
```

### Task 9: System-Level Data Completeness

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php`
- Test: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`

- [ ] **Step 1: Add failing test for non-empty system pages**

```php
it('seeds announcements, audit logs, and permissions for system pages', function (): void {
    $this->seed(\Database\Seeders\ProductionThreeYearSnapshotSeeder::class);

    expect(\App\Models\Announcement::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\AuditLog::query()->count())->toBeGreaterThan(0);
    expect(\App\Models\Permission::query()->count())->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php --filter=system\ pages`
Expected: FAIL.

- [ ] **Step 3: Ensure calls and post-processing are wired**

```php
$this->call([
    GradeLevelSeeder::class,
    SubjectSeeder::class,
    PermissionSeeder::class,
    SuperAdminSeeder::class,
]);
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "feat(seeding): ensure system-level seed data coverage"
```

### Task 10: Verification, Formatting, and Seed Smoke Run

**Files:**
- Modify: `database/seeders/ProductionThreeYearSnapshotSeeder.php` (if final fixes)
- Modify: `tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php` (if assertion tuning)

- [ ] **Step 1: Run formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: code style fixes applied or no changes.

- [ ] **Step 2: Run targeted seeder test file**

Run: `php artisan test --compact tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php`
Expected: PASS.

- [ ] **Step 3: Run seed smoke command**

Run: `php artisan db:seed --class=Database\\Seeders\\ProductionThreeYearSnapshotSeeder --no-interaction`
Expected: exits successfully with no exceptions.

- [ ] **Step 4: Verify key counts quickly**

Run:
```bash
php artisan tinker --execute="echo App\\Models\\Section::count();"
php artisan tinker --execute="echo App\\Models\\Enrollment::whereHas('academicYear', fn($q)=>$q->where('name','2025-2026'))->count();"
```

Expected:
- Current year sections should include exactly 5 requested JHS sections.
- Current year enrollments should be `125` if strict 25-per-section is applied.

- [ ] **Step 5: Commit final polish**

```bash
git add database/seeders/ProductionThreeYearSnapshotSeeder.php tests/Feature/Seeders/ProductionThreeYearSnapshotSeederTest.php
git commit -m "test(seeding): verify three-year snapshot consistency and q1 timeline"
```

## Data Decisions Locked by This Plan

- Required named defaults:
  - Admin: `Alex Avellanosa`
  - Registrar: `Jocelyn Cleofe`
  - Finance: `Corrine Avellanosa`
- Sections (from provided image, Grade 7-10 only):
  - Grade 7: `St. Paul`
  - Grade 8: `St. Anthony`
  - Grade 9: `St. Francis`
  - Grade 10: `St. John`, `St. Anne`
- Teachers and schedules:
  - Seeded from workbook-aligned blueprint for listed faculty and section loads.
- Student load:
  - `25` students per section in current year (`125` total current-year seats).
- Names:
  - Randomized Filipino name pools only.
- LRN:
  - Uniform 12-digit deterministic format across all seeded years.
- Academic timeline constraint:
  - Current SY `2025-2026` is Q1 only (no Q2 artifacts seeded).

## Spec Coverage Check (Self-Review)

- 3-year running system snapshot: covered by Tasks 1, 6, 7, 8, 9.
- Current year fixed to 2025-2026 with Q1 consistency: covered by Tasks 1 and 8.
- Required named default staff accounts: covered by Task 2.
- Required sections from `image.png` (Grade 7-10 only): covered by Task 3.
- Teachers/schedules from workbook: covered by Task 4.
- 25 students/section + Filipino names + uniform LRN: covered by Task 5.
- "Everything needed to be shown in the system has data": covered by Tasks 7, 8, 9 plus verification in Task 10.

No placeholders (`TBD/TODO`) are left in executable steps.
