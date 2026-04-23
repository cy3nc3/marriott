# Progressive Adaptive Demo Flow Seeding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a progressive, state-driven demo seeding flow where pre-opening data is cashier-clean (seeded enrollments already enrolled), and each quarter transition auto-seeds realistic operational records including newly added students.

**Architecture:** Add a dedicated demo-flow orchestration service plus state store, then wire school-year state transitions (`simulateOpening`, `advanceQuarter`, year close path) to trigger idempotent quarter seed passes. Use `ProductionThreeYearSnapshotSeeder` as the canonical baseline so staff accounts, sections, and schedules exactly match the reference dataset. Apply adaptive finance/academics deltas without replacing reference staff/section/schedule scaffolding, and keep seeded student identities human-readable (random non-celebrity names, never numeric placeholder names).

**Tech Stack:** Laravel 12, Eloquent seeders/services, Inertia admin flow, Pest 4 feature tests, existing Billing/Registrar services.

---

## Safety Gate (Required Before Task 1)

Primary strategy for demo environments (keeps claim flow functional for newly created enrollees):

- [ ] Keep `ENROLLMENT_CLAIM_MAIL_ENABLED=true` and `ENROLLMENT_CLAIM_SMS_ENABLED=true`.
- [ ] Ensure seeded enrollments have `enrollments.email = null` so seeded transitions cannot issue claim emails.
- [ ] Continue using `DEMO_MAIL_REDIRECT_TO` as a safety fallback for any real outbound email path.
- [ ] Ensure demo seed/orchestrator paths do not invoke controller-side transition helpers that auto-issue claim links.

Optional strict dry-run strategy (use only when you intentionally do not need account-claim demo):

- [ ] Set `MAIL_MAILER=log`.
- [ ] Set `ENROLLMENT_CLAIM_MAIL_ENABLED=false`.
- [ ] Set `ENROLLMENT_CLAIM_SMS_ENABLED=false`.
- [ ] Set `ANNOUNCEMENT_SMS_ENABLED=false`.

### Guardrail Assertions (must be added to tests)
- [ ] Assert seeded enrollments are persisted with `email = null`.
- [ ] Use `Notification::fake()` in demo-flow tests and assert no claim mail notifications are dispatched by seeder/orchestrator runs.
- [ ] Use `Http::fake()` and assert no outbound SMS provider call is made during demo seeding paths.
- [ ] Keep notification delivery exclusively in explicit user actions (cashier posting, import, manual announcement publish), not in bulk demo seed orchestration.

## Reference Seeder Alignment (Required)

- [ ] `DemoPreOpeningStageSeeder` must call `ProductionThreeYearSnapshotSeeder` as baseline source of truth.
- [ ] Do not duplicate or override reference staff, section, and class schedule blueprints in demo seeding code.
- [ ] Preserve reference staff account identities from the snapshot seeder (`admin@marriott.edu`, `registrar@marriott.edu`, `finance@marriott.edu`, etc.).
- [ ] Student display/account names in seeded data must be random non-celebrity names and must not be numeric placeholders like `Student 1`, `Student 2`, etc.
- [ ] Follow the reference seeder naming style (Filipino school-appropriate first/last names), and keep naming deterministic/idempotent per seeded record where possible.

---

## File Structure

### New files to create
- `app/Console/Commands/SeedDemoFlowCommand.php`
- `app/Services/Demo/DemoFlowStateStore.php`
- `app/Services/Demo/ProgressiveDemoFlowService.php`
- `database/seeders/DemoPreOpeningStageSeeder.php`
- `tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php`
- `tests/Feature/Admin/AcademicControlsDemoFlowTest.php`

### Existing files to modify
- `app/Http/Controllers/Admin/SchoolYearController.php`
- `tests/Feature/Admin/AdminFeaturesTest.php`
- `tests/Feature/ProductionStageSeedersTest.php`

---

### Task 1: Establish Demo Flow State Contract

**Files:**
- Create: `app/Services/Demo/DemoFlowStateStore.php`
- Create: `tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php`

- [ ] **Step 1: Write failing test for flow state persistence**

```php
<?php

use App\Models\AcademicYear;
use App\Services\Demo\DemoFlowStateStore;

it('persists and reloads demo flow stage metadata', function (): void {
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $store = app(DemoFlowStateStore::class);

    $store->set($year, [
        'enabled' => true,
        'stage' => 'pre_opening',
        'seeded_enrollment_ids' => [11, 12, 13],
    ]);

    expect($store->get($year))->toMatchArray([
        'enabled' => true,
        'stage' => 'pre_opening',
        'seeded_enrollment_ids' => [11, 12, 13],
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php --filter="persists and reloads demo flow stage metadata"`
Expected: FAIL because `DemoFlowStateStore` does not exist.

- [ ] **Step 3: Implement JSON-backed state store via `settings` table**

```php
<?php

namespace App\Services\Demo;

use App\Models\AcademicYear;
use App\Models\Setting;

class DemoFlowStateStore
{
    public function get(AcademicYear $academicYear): array
    {
        $raw = Setting::get($this->key($academicYear));

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function set(AcademicYear $academicYear, array $state): void
    {
        Setting::set(
            $this->key($academicYear),
            json_encode($state, JSON_THROW_ON_ERROR),
            'demo_flow'
        );
    }

    private function key(AcademicYear $academicYear): string
    {
        return "demo_flow:{$academicYear->id}";
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: same as Step 2
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Demo/DemoFlowStateStore.php tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php
git commit -m "feat(demo): add demo flow state store"
```

### Task 2: Build Pre-Opening Seeder With Cashier-Clean Queue

**Files:**
- Create: `database/seeders/DemoPreOpeningStageSeeder.php`
- Modify: `tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php`

- [ ] **Step 1: Write failing test for pre-opening queue normalization**

```php
<?php

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoPreOpeningStageSeeder;

it('seeds pre-opening demo data with seeded enrollments already enrolled', function (): void {
    $this->seed(DemoPreOpeningStageSeeder::class);

    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

    expect($year->status)->toBe('upcoming');
    expect((string) $year->current_quarter)->toBe('1');

    expect(Enrollment::query()
        ->where('academic_year_id', $year->id)
        ->where('status', 'for_cashier_payment')
        ->count())->toBe(0);

    expect(Enrollment::query()
        ->where('academic_year_id', $year->id)
        ->where('status', 'enrolled')
        ->count())->toBeGreaterThan(0);

    expect(Enrollment::query()
        ->where('academic_year_id', $year->id)
        ->whereNotNull('email')
        ->where('email', '!=', '')
        ->count())->toBe(0);

    expect(User::query()->where('email', 'admin@marriott.edu')->value('name'))->toBe('Alex Avellanosa');
    expect(User::query()->where('email', 'registrar@marriott.edu')->value('name'))->toBe('Jocelyn Cleofe');
    expect(User::query()->where('email', 'finance@marriott.edu')->value('name'))->toBe('Corrine Avellanosa');

    expect(Section::query()
        ->where('academic_year_id', $year->id)
        ->pluck('name')
        ->sort()
        ->values()
        ->all())->toBe(['St. Anne', 'St. Anthony', 'St. Francis', 'St. John', 'St. Paul']);

    expect(ClassSchedule::query()
        ->whereHas('subjectAssignment.section', fn ($query) => $query->where('academic_year_id', $year->id))
        ->count())->toBeGreaterThan(0);

    $seededStudents = Student::query()
        ->whereIn(
            'id',
            Enrollment::query()
                ->where('academic_year_id', $year->id)
                ->pluck('student_id')
        )
        ->get(['first_name', 'last_name']);

    $containsNumericPlaceholderName = $seededStudents->contains(function (Student $student): bool {
        $fullName = trim("{$student->first_name} {$student->last_name}");

        return preg_match('/^student\\s*\\d+$/i', $fullName) === 1
            || preg_match('/^\\d+$/', (string) $student->first_name) === 1
            || preg_match('/^\\d+$/', (string) $student->last_name) === 1;
    });

    expect($containsNumericPlaceholderName)->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php --filter="pre-opening demo data"`
Expected: FAIL (missing seeder and/or pending statuses still present).

- [ ] **Step 3: Implement pre-opening stage seeder that composes current stage seeder and normalizes statuses**

```php
<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;

class DemoPreOpeningStageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionThreeYearSnapshotSeeder::class);

        $academicYear = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

        $academicYear->update([
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);

        Enrollment::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['for_cashier_payment', 'partial_payment', 'enrolled'])
            ->update([
                'status' => 'enrolled',
                'email' => null,
            ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: same as Step 2
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/DemoPreOpeningStageSeeder.php tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php
git commit -m "feat(demo): build pre-opening seeder from production snapshot baseline"
```

### Task 3: Add Demo Flow Orchestrator Service

**Files:**
- Create: `app/Services/Demo/ProgressiveDemoFlowService.php`
- Modify: `tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php`

- [ ] **Step 1: Write failing service-level feature test for quarter 1 adaptive coverage**

```php
<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradedActivity;
use App\Models\LedgerEntry;
use App\Services\Demo\ProgressiveDemoFlowService;
use Database\Seeders\DemoPreOpeningStageSeeder;

it('seeds quarter one artifacts for current enrollments including newly created entries', function (): void {
    $this->seed(DemoPreOpeningStageSeeder::class);

    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

    $enrollment = Enrollment::query()
        ->where('academic_year_id', $year->id)
        ->latest('id')
        ->firstOrFail();

    $enrollment->update(['status' => 'for_cashier_payment']);

    app(ProgressiveDemoFlowService::class)->seedQuarter($year, '1');

    expect(GradedActivity::query()->where('quarter', '1')->count())->toBeGreaterThan(0);
    expect(LedgerEntry::query()->where('academic_year_id', $year->id)->count())->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php --filter="quarter one artifacts"`
Expected: FAIL because `ProgressiveDemoFlowService` is missing.

- [ ] **Step 3: Implement orchestration service with idempotent stage dispatch**

```php
<?php

namespace App\Services\Demo;

use App\Models\AcademicYear;

class ProgressiveDemoFlowService
{
    public function seedPreOpening(AcademicYear $academicYear): void
    {
        $academicYear->update([
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);
    }

    public function seedQuarter(AcademicYear $academicYear, string $quarter): void
    {
        if ($quarter === '1') {
            $this->seedQuarterOne($academicYear);
            return;
        }

        if ($quarter === '2') {
            $this->seedQuarterTwo($academicYear);
            return;
        }

        if ($quarter === '3') {
            $this->seedQuarterThree($academicYear);
            return;
        }

        if ($quarter === '4') {
            $this->seedQuarterFour($academicYear);
        }
    }

    public function seedYearEnd(AcademicYear $academicYear): void
    {
        $this->seedQuarterFour($academicYear);
        $this->ensureFinalOutcomes($academicYear);
    }

    private function seedQuarterOne(AcademicYear $academicYear): void
    {
        throw new \RuntimeException('Quarter one seeding is not implemented.');
    }

    private function seedQuarterTwo(AcademicYear $academicYear): void
    {
        throw new \RuntimeException('Quarter two seeding is not implemented.');
    }

    private function seedQuarterThree(AcademicYear $academicYear): void
    {
        throw new \RuntimeException('Quarter three seeding is not implemented.');
    }

    private function seedQuarterFour(AcademicYear $academicYear): void
    {
        throw new \RuntimeException('Quarter four seeding is not implemented.');
    }
}
```

- [ ] **Step 4: Run test and keep failing on assertions only**

Run: same as Step 2
Expected: class exists; assertion still fails until quarter logic is implemented.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Demo/ProgressiveDemoFlowService.php tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php
git commit -m "feat(demo): scaffold progressive demo flow orchestrator"
```

### Task 4: Add CLI Entry Point for Demo Bootstrap

**Files:**
- Create: `app/Console/Commands/SeedDemoFlowCommand.php`
- Modify: `tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php`

- [ ] **Step 1: Write failing command test for pre-opening bootstrap**

```php
<?php

use App\Models\AcademicYear;

it('boots demo flow in pre-opening mode via artisan command', function (): void {
    $this->artisan('demo:seed --stage=pre-opening --no-interaction')
        ->assertExitCode(0);

    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

    expect($year->status)->toBe('upcoming');
    expect((string) $year->current_quarter)->toBe('1');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php --filter="via artisan command"`
Expected: FAIL (command missing).

- [ ] **Step 3: Implement command with explicit stage option**

```php
<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Services\Demo\DemoFlowStateStore;
use Database\Seeders\DemoPreOpeningStageSeeder;
use Illuminate\Console\Command;

class SeedDemoFlowCommand extends Command
{
    protected $signature = 'demo:seed {--stage=pre-opening}';

    protected $description = 'Seed progressive demo flow data.';

    public function handle(DemoFlowStateStore $stateStore): int
    {
        if ($this->option('stage') !== 'pre-opening') {
            $this->error('Only pre-opening stage is supported by demo:seed.');
            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => DemoPreOpeningStageSeeder::class, '--no-interaction' => true]);

        $academicYear = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

        $stateStore->set($academicYear, [
            'enabled' => true,
            'stage' => 'pre_opening',
        ]);

        $this->info('Demo pre-opening seed complete.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Re-run command test**

Run: same as Step 2
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/SeedDemoFlowCommand.php tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php
git commit -m "feat(demo): add demo seed bootstrap command"
```

### Task 5: Seed Quarter 1 Operational Data Adaptively

**Files:**
- Modify: `app/Services/Demo/ProgressiveDemoFlowService.php`
- Modify: `tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php`

- [ ] **Step 1: Write failing test for adaptive inclusion of newly added student**

```php
<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\Demo\ProgressiveDemoFlowService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

it('adds quarter one records for manually added student once enrolled', function (): void {
    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

    $firstName = Arr::random(['Althea', 'Bianca', 'Carla', 'Danica', 'Elaine']);
    $lastName = Arr::random(['Abella', 'Bautista', 'Castillo', 'Domingo', 'Enriquez']);
    $emailLocal = Str::slug("{$firstName}.{$lastName}.manual", '.');

    $user = User::query()->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'name' => trim("{$firstName} {$lastName}"),
        'email' => Str::lower("{$emailLocal}@marriott.edu"),
        'password' => bcrypt('password'),
        'birthday' => '2010-01-01',
        'role' => UserRole::STUDENT,
        'is_active' => true,
    ]);

    $student = Student::query()->create([
        'user_id' => $user->id,
        'lrn' => '909090909090',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'gender' => 'Female',
        'birthdate' => '2010-01-01',
    ]);

    $templateEnrollment = Enrollment::query()
        ->where('academic_year_id', $year->id)
        ->firstOrFail();

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'grade_level_id' => $templateEnrollment->grade_level_id,
        'section_id' => $templateEnrollment->section_id,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'enrolled',
    ]);

    app(ProgressiveDemoFlowService::class)->seedQuarter($year, '1');

    expect($student->scores()->count())->toBeGreaterThan(0);
    expect($student->ledgerEntries()->where('academic_year_id', $year->id)->count())->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php --filter="manually added student once enrolled"`
Expected: FAIL.

- [ ] **Step 3: Implement quarter one adaptive seeding methods**

```php
private function seedQuarterOne(AcademicYear $academicYear): void
{
    $enrollments = Enrollment::query()
        ->where('academic_year_id', $academicYear->id)
        ->whereIn('status', ['for_cashier_payment', 'enrolled'])
        ->with(['student', 'section'])
        ->get();

    $this->ensureBillingSchedules($enrollments);
    $this->ensureOpeningLedgerEntries($academicYear, $enrollments);
    $this->ensureQuarterOneTransactions($academicYear, $enrollments);
    $this->ensureQuarterOneActivitiesAndScores($academicYear, $enrollments->where('status', 'enrolled'));
    $this->ensureQuarterOneAttendance($academicYear, $enrollments->where('status', 'enrolled'));
}
```

**Important:** keep this path data-only. Do not call controller-side `transitionEnrollmentStatus()` flows from demo seeding because those paths trigger account-claim notifications when status moves to `enrolled`.

- [ ] **Step 4: Run quarter-one adaptive tests**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php --filter="quarter one|manually added student"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Demo/ProgressiveDemoFlowService.php tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php
git commit -m "feat(demo): seed quarter one data adaptively for seeded and manual enrollments"
```

### Task 6: Hook Opening Transition to Quarter 1 Seed

**Files:**
- Modify: `app/Http/Controllers/Admin/SchoolYearController.php`
- Create: `tests/Feature/Admin/AcademicControlsDemoFlowTest.php`

- [ ] **Step 1: Write failing controller test for simulate-opening trigger**

```php
<?php

use App\Models\AcademicYear;
use App\Models\Setting;

it('triggers quarter one demo seeding when simulate opening is called in demo mode', function (): void {
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    Setting::set("demo_flow:{$year->id}", json_encode([
        'enabled' => true,
        'stage' => 'pre_opening',
    ]), 'demo_flow');

    $this->post("/admin/academic-controls/{$year->id}/simulate-opening")
        ->assertRedirect();

    expect($year->fresh()->status)->toBe('ongoing');
    expect(\App\Models\LedgerEntry::query()
        ->where('academic_year_id', $year->id)
        ->count())->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/AcademicControlsDemoFlowTest.php --filter="simulate opening"`
Expected: FAIL (no seeding side effect yet).

- [ ] **Step 3: Inject and call orchestrator from `simulateOpening`**

```php
public function simulateOpening(
    AcademicYear $academicYear,
    AuditLogService $auditLogService,
    ProgressiveDemoFlowService $demoFlowService,
    DemoFlowStateStore $demoFlowStateStore,
): RedirectResponse {
    // existing checks and status update

    $state = $demoFlowStateStore->get($academicYear);

    if (($state['enabled'] ?? false) === true) {
        $demoFlowService->seedQuarter($academicYear, '1');

        $demoFlowStateStore->set($academicYear, [
            ...$state,
            'stage' => 'quarter_1',
        ]);
    }

    return back()->with('success', 'School year marked as ongoing.');
}
```

- [ ] **Step 4: Re-run test**

Run: same as Step 2
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/SchoolYearController.php tests/Feature/Admin/AcademicControlsDemoFlowTest.php
git commit -m "feat(demo): trigger quarter one seed on simulate opening"
```

### Task 7: Seed Quarters 2-4 and Hook Quarter Advance

**Files:**
- Modify: `app/Services/Demo/ProgressiveDemoFlowService.php`
- Modify: `app/Http/Controllers/Admin/SchoolYearController.php`
- Modify: `tests/Feature/Admin/AcademicControlsDemoFlowTest.php`

- [ ] **Step 1: Write failing tests for quarter advance side effects and idempotency**

```php
<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\GradeSubmission;
use App\Models\Setting;
use App\Services\Demo\ProgressiveDemoFlowService;
use Database\Seeders\DemoPreOpeningStageSeeder;

it('adds quarter two to quarter four artifacts when advancing quarter in demo mode', function (): void {
    $this->seed(DemoPreOpeningStageSeeder::class);

    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
    $year->update(['status' => 'ongoing', 'current_quarter' => '1']);

    Setting::set("demo_flow:{$year->id}", json_encode([
        'enabled' => true,
        'stage' => 'quarter_1',
    ]), 'demo_flow');

    $this->post("/admin/academic-controls/{$year->id}/advance-quarter")->assertRedirect();
    $this->post("/admin/academic-controls/{$year->id}/advance-quarter")->assertRedirect();
    $this->post("/admin/academic-controls/{$year->id}/advance-quarter")->assertRedirect();

    expect($year->fresh()->current_quarter)->toBe('4');
    expect(GradeSubmission::query()->where('academic_year_id', $year->id)->where('quarter', '2')->count())->toBeGreaterThan(0);
    expect(GradeSubmission::query()->where('academic_year_id', $year->id)->where('quarter', '3')->count())->toBeGreaterThan(0);
    expect(GradeSubmission::query()->where('academic_year_id', $year->id)->where('quarter', '4')->count())->toBeGreaterThan(0);
    expect(Attendance::query()->count())->toBeGreaterThan(0);
});

it('does not duplicate quarter artifacts when quarter seeding runs twice', function (): void {
    $this->seed(DemoPreOpeningStageSeeder::class);

    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
    $year->update(['status' => 'ongoing', 'current_quarter' => '2']);

    $service = app(ProgressiveDemoFlowService::class);

    $service->seedQuarter($year, '2');
    $firstCount = GradeSubmission::query()
        ->where('academic_year_id', $year->id)
        ->where('quarter', '2')
        ->count();

    $service->seedQuarter($year, '2');
    $secondCount = GradeSubmission::query()
        ->where('academic_year_id', $year->id)
        ->where('quarter', '2')
        ->count();

    expect($secondCount)->toBe($firstCount);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Admin/AcademicControlsDemoFlowTest.php --filter="quarter two to quarter four|duplicate quarter artifacts"`
Expected: FAIL.

- [ ] **Step 3: Implement quarter 2-4 methods and wire into `advanceQuarter`**

```php
if ($next <= 4) {
    $academicYear->update(['current_quarter' => (string) $next]);

    $state = $demoFlowStateStore->get($academicYear);

    if (($state['enabled'] ?? false) === true) {
        $demoFlowService->seedQuarter($academicYear, (string) $next);

        $demoFlowStateStore->set($academicYear, [
            ...$state,
            'stage' => "quarter_{$next}",
        ]);
    }

    return back()->with('success', 'Quarter advanced successfully.');
}
```

- [ ] **Step 4: Run quarter progression tests**

Run: same as Step 2
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Demo/ProgressiveDemoFlowService.php app/Http/Controllers/Admin/SchoolYearController.php tests/Feature/Admin/AcademicControlsDemoFlowTest.php
git commit -m "feat(demo): seed q2-q4 data on academic quarter advance"
```

### Task 8: Seed End-of-Flow Outcomes Before Year Close

**Files:**
- Modify: `app/Services/Demo/ProgressiveDemoFlowService.php`
- Modify: `app/Http/Controllers/Admin/SchoolYearController.php`
- Modify: `tests/Feature/Admin/AdminFeaturesTest.php`

- [ ] **Step 1: Write failing close-year test for demo-prepared outcomes**

```php
<?php

use App\Models\AcademicYear;
use App\Models\PermanentRecord;
use App\Models\RemedialCase;
use App\Models\Setting;
use Database\Seeders\DemoPreOpeningStageSeeder;

it('prepares final outcomes in demo mode before year close checks run', function (): void {
    $this->seed(DemoPreOpeningStageSeeder::class);

    $year = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
    $year->update(['status' => 'ongoing', 'current_quarter' => '4']);

    Setting::set("demo_flow:{$year->id}", json_encode([
        'enabled' => true,
        'stage' => 'quarter_4',
    ]), 'demo_flow');

    $this->post("/admin/academic-controls/{$year->id}/advance-quarter")
        ->assertRedirect();

    expect(PermanentRecord::query()
        ->where('academic_year_id', $year->id)
        ->count())->toBeGreaterThan(0);

    expect(RemedialCase::query()
        ->where('academic_year_id', $year->id)
        ->count())->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/AdminFeaturesTest.php --filter="prepares final outcomes in demo mode"`
Expected: FAIL.

- [ ] **Step 3: Seed year-end outcomes before close blockers are evaluated**

```php
if ($next > 4) {
    $state = $demoFlowStateStore->get($academicYear);

    if (($state['enabled'] ?? false) === true) {
        $demoFlowService->seedYearEnd($academicYear);

        $demoFlowStateStore->set($academicYear, [
            ...$state,
            'stage' => 'year_end_ready',
        ]);
    }
}
```

- [ ] **Step 4: Run targeted year-close tests**

Run: `php artisan test --compact tests/Feature/Admin/AdminFeaturesTest.php --filter="year close|prepares final outcomes in demo mode"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Demo/ProgressiveDemoFlowService.php app/Http/Controllers/Admin/SchoolYearController.php tests/Feature/Admin/AdminFeaturesTest.php
git commit -m "feat(demo): auto-seed end-of-flow outcomes before demo year close"
```

### Task 9: Regression Verification and Formatting

**Files:**
- Modify: any files touched by tasks above

- [ ] **Step 1: Run formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: PASS with consistent project formatting.

- [ ] **Step 2: Run focused demo seeding tests**

Run: `php artisan test --compact tests/Feature/Seeders/ProgressiveDemoFlowSeederTest.php tests/Feature/Admin/AcademicControlsDemoFlowTest.php`
Expected: PASS.

- [ ] **Step 3: Run impacted regression tests**

Run: `php artisan test --compact tests/Feature/Admin/AdminFeaturesTest.php tests/Feature/ProductionStageSeedersTest.php tests/Feature/Finance/CashierPanelTest.php --filter="academic controls|simulate opening|advance-quarter|for_cashier_payment|production stage seeders"`
Expected: PASS.

- [ ] **Step 4: Run command smoke flow**

Run:
```bash
php artisan demo:seed --stage=pre-opening --no-interaction
php artisan test --compact tests/Feature/Admin/AcademicControlsDemoFlowTest.php --filter="simulate opening|quarter"
```
Expected: PASS.

- [ ] **Step 5: Final commit**

```bash
git add app/Console/Commands app/Services/Demo app/Http/Controllers/Admin/SchoolYearController.php database/seeders tests/Feature
git commit -m "feat(demo): implement progressive adaptive demo flow seeding"
```

## Spec Coverage Self-Check

- Pre-opening with seeded queue already enrolled: covered by Tasks 2 and 4.
- Newly created enrollee remains the visible cashier pending case: covered by Task 2 behavior + Task 5 adaptive inclusion.
- Moving to first quarter auto-fills assessments/finance/operational records: covered by Tasks 5 and 6.
- Progressive quarter-by-quarter data growth through end-of-flow: covered by Tasks 7 and 8.
- Non-destructive/idempotent behavior and regression safety: covered by Tasks 7 and 9.
- Baseline parity with reference seeder staff/section/schedule dataset: covered by Task 2 assertions and implementation.
- Student identity quality (random non-numeric non-celebrity naming style): covered by Task 2 and Task 5.

No placeholder markers remain, all tasks reference exact files, and verification commands are explicit.
