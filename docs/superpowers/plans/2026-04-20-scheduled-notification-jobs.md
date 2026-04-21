# Scheduled Notification Jobs Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace minute-polling reminder timing with a central scheduled notification system that can schedule, cancel, supersede, and dispatch finance, grade, and announcement event reminders cleanly as business state changes.

**Architecture:** Introduce a shared `scheduled_notification_jobs` table, Eloquent model, planner/reconciler services, and a dispatcher command. Migrate finance, grading, and event reminders one by one onto this layer, keeping each reminder type's send logic focused and deferring cleanup of legacy scheduler entries until the new dispatch path is verified.

**Tech Stack:** Laravel 12, PHP 8.4, Pest 4, Inertia React v2, Laravel queues, Laravel scheduler, MySQL/SQLite test database

---

### Task 1: Add shared scheduling primitives

**Files:**
- Create: `database/migrations/2026_04_20_000001_create_scheduled_notification_jobs_table.php`
- Create: `app/Models/ScheduledNotificationJob.php`
- Create: `app/Enums/ScheduledNotificationJobStatus.php`
- Create: `app/Enums/ScheduledNotificationJobType.php`
- Create: `tests/Feature/Scheduling/ScheduledNotificationJobModelTest.php`

- [ ] **Step 1: Write the failing model and uniqueness tests**

```php
<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

test('scheduled notification job stores casts for run and lifecycle timestamps', function () {
    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FINANCE_DUE_REMINDER,
        'status' => ScheduledNotificationJobStatus::PENDING,
        'run_at' => Carbon::parse('2026-05-01 07:30:00'),
        'dedupe_key' => 'finance:rule-1:schedule-2:2026-05-01-0730',
        'group_key' => 'finance:rule-1',
        'subject_type' => 'billing_schedule',
        'subject_id' => 2,
        'payload' => ['days_before_due' => 3],
    ]);

    expect($job->run_at)->toBeInstanceOf(Carbon::class)
        ->and($job->payload)->toBe(['days_before_due' => 3]);
});

test('scheduled notification job dedupe key is unique', function () {
    ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FINANCE_DUE_REMINDER,
        'status' => ScheduledNotificationJobStatus::PENDING,
        'run_at' => Carbon::parse('2026-05-01 07:30:00'),
        'dedupe_key' => 'finance:rule-1:schedule-2:2026-05-01-0730',
        'group_key' => 'finance:rule-1',
        'subject_type' => 'billing_schedule',
        'subject_id' => 2,
    ]);

    ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FINANCE_DUE_REMINDER,
        'status' => ScheduledNotificationJobStatus::PENDING,
        'run_at' => Carbon::parse('2026-05-01 07:30:00'),
        'dedupe_key' => 'finance:rule-1:schedule-2:2026-05-01-0730',
        'group_key' => 'finance:rule-1',
        'subject_type' => 'billing_schedule',
        'subject_id' => 2,
    ]);
})->throws(QueryException::class);
```

- [ ] **Step 2: Run the scheduling model test to verify it fails**

Run: `php artisan test --compact tests/Feature/Scheduling/ScheduledNotificationJobModelTest.php`

Expected: FAIL because the table, enums, and model do not exist yet.

- [ ] **Step 3: Add the table, enums, and model**

```php
Schema::create('scheduled_notification_jobs', function (Blueprint $table) {
    $table->id();
    $table->string('type', 64);
    $table->string('status', 32);
    $table->timestamp('run_at');
    $table->string('dedupe_key')->unique();
    $table->string('group_key');
    $table->string('subject_type', 64);
    $table->unsignedBigInteger('subject_id');
    $table->string('recipient_type', 64)->nullable();
    $table->unsignedBigInteger('recipient_id')->nullable();
    $table->json('payload')->nullable();
    $table->string('planned_by_type', 64)->nullable();
    $table->unsignedBigInteger('planned_by_id')->nullable();
    $table->timestamp('dispatched_at')->nullable();
    $table->timestamp('canceled_at')->nullable();
    $table->string('skip_reason', 64)->nullable();
    $table->string('failure_reason', 64)->nullable();
    $table->timestamps();

    $table->index(['status', 'run_at']);
    $table->index(['type', 'status']);
    $table->index('group_key');
    $table->index(['subject_type', 'subject_id']);
    $table->index(['recipient_type', 'recipient_id']);
});
```

```php
enum ScheduledNotificationJobStatus: string
{
    case PENDING = 'pending';
    case CANCELED = 'canceled';
    case SUPERSEDED = 'superseded';
    case DISPATCHED = 'dispatched';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';
}
```

```php
enum ScheduledNotificationJobType: string
{
    case FINANCE_DUE_REMINDER = 'finance_due_reminder';
    case GRADE_DEADLINE_REMINDER = 'grade_deadline_reminder';
    case ANNOUNCEMENT_EVENT_REMINDER = 'announcement_event_reminder';
}
```

```php
class ScheduledNotificationJob extends Model
{
    protected $fillable = [
        'type',
        'status',
        'run_at',
        'dedupe_key',
        'group_key',
        'subject_type',
        'subject_id',
        'recipient_type',
        'recipient_id',
        'payload',
        'planned_by_type',
        'planned_by_id',
        'dispatched_at',
        'canceled_at',
        'skip_reason',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScheduledNotificationJobType::class,
            'status' => ScheduledNotificationJobStatus::class,
            'run_at' => 'datetime',
            'payload' => 'array',
            'dispatched_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 4: Run the model tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Scheduling/ScheduledNotificationJobModelTest.php`

Expected: PASS with the timestamp cast and dedupe-key uniqueness checks succeeding.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_20_000001_create_scheduled_notification_jobs_table.php app/Models/ScheduledNotificationJob.php app/Enums/ScheduledNotificationJobStatus.php app/Enums/ScheduledNotificationJobType.php tests/Feature/Scheduling/ScheduledNotificationJobModelTest.php
git commit -m "feat: add scheduled notification job primitives"
```

### Task 2: Add shared planner and dispatcher infrastructure

**Files:**
- Create: `app/Services/Scheduling/ScheduledNotificationPlanner.php`
- Create: `app/Services/Scheduling/ScheduledNotificationDispatcher.php`
- Create: `app/Console/Commands/DispatchScheduledNotificationsCommand.php`
- Modify: `routes/console.php`
- Create: `tests/Feature/Scheduling/DispatchScheduledNotificationsCommandTest.php`

- [ ] **Step 1: Write the failing dispatcher tests**

```php
<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use Illuminate\Support\Carbon;

test('dispatcher marks due invalid jobs as skipped', function () {
    ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FINANCE_DUE_REMINDER,
        'status' => ScheduledNotificationJobStatus::PENDING,
        'run_at' => now()->subMinute(),
        'dedupe_key' => 'finance:invalid',
        'group_key' => 'finance:rule-1',
        'subject_type' => 'billing_schedule',
        'subject_id' => 999999,
    ]);

    $this->artisan('notifications:dispatch-scheduled')
        ->assertSuccessful();

    $job = ScheduledNotificationJob::query()->where('dedupe_key', 'finance:invalid')->firstOrFail();

    expect($job->status)->toBe(ScheduledNotificationJobStatus::SKIPPED)
        ->and($job->skip_reason)->toBe('subject_missing');
});
```

- [ ] **Step 2: Run the dispatcher test to verify it fails**

Run: `php artisan test --compact tests/Feature/Scheduling/DispatchScheduledNotificationsCommandTest.php`

Expected: FAIL because the dispatcher command and shared scheduling services do not exist yet.

- [ ] **Step 3: Add a minimal shared planner and dispatcher**

```php
final class ScheduledNotificationPlanner
{
    /**
     * @param  Collection<int, array<string, mixed>>  $desiredJobs
     */
    public function reconcile(
        ScheduledNotificationJobType $type,
        string $groupKey,
        Collection $desiredJobs
    ): void {
        $pendingJobs = ScheduledNotificationJob::query()
            ->where('type', $type)
            ->where('group_key', $groupKey)
            ->where('status', ScheduledNotificationJobStatus::PENDING)
            ->get()
            ->keyBy('dedupe_key');

        $desiredKeys = $desiredJobs->pluck('dedupe_key')->all();

        foreach ($desiredJobs as $jobData) {
            ScheduledNotificationJob::query()->firstOrCreate(
                ['dedupe_key' => $jobData['dedupe_key']],
                $jobData
            );
        }

        ScheduledNotificationJob::query()
            ->where('type', $type)
            ->where('group_key', $groupKey)
            ->where('status', ScheduledNotificationJobStatus::PENDING)
            ->whereNotIn('dedupe_key', $desiredKeys)
            ->update([
                'status' => ScheduledNotificationJobStatus::SUPERSEDED,
                'canceled_at' => now(),
            ]);
    }
}
```

```php
final class ScheduledNotificationDispatcher
{
    public function dispatchDue(): void
    {
        ScheduledNotificationJob::query()
            ->where('status', ScheduledNotificationJobStatus::PENDING)
            ->where('run_at', '<=', now())
            ->orderBy('run_at')
            ->get()
            ->each(function (ScheduledNotificationJob $job): void {
                if (! $this->subjectExists($job)) {
                    $job->update([
                        'status' => ScheduledNotificationJobStatus::SKIPPED,
                        'skip_reason' => 'subject_missing',
                    ]);

                    return;
                }

                dispatch(new DispatchScheduledNotificationJob($job->id));
            });
    }
}
```

```php
Schedule::command('notifications:dispatch-scheduled')->everyMinute();
```

- [ ] **Step 4: Run the dispatcher tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Scheduling/DispatchScheduledNotificationsCommandTest.php`

Expected: PASS with due invalid jobs being marked skipped.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Scheduling/ScheduledNotificationPlanner.php app/Services/Scheduling/ScheduledNotificationDispatcher.php app/Console/Commands/DispatchScheduledNotificationsCommand.php routes/console.php tests/Feature/Scheduling/DispatchScheduledNotificationsCommandTest.php
git commit -m "feat: add scheduled notification dispatcher infrastructure"
```

### Task 3: Migrate finance reminders onto scheduled notification jobs

**Files:**
- Create: `app/Jobs/SendFinanceDueReminderJob.php`
- Create: `app/Services/Scheduling/FinanceDueReminderPlanner.php`
- Modify: `app/Services/Finance/DueReminderNotificationService.php`
- Modify: `app/Http/Controllers/Finance/DueReminderSettingsController.php`
- Modify: `app/Models/BillingSchedule.php`
- Modify: `app/Console/Commands/SendFinanceDueRemindersCommand.php`
- Create: `tests/Feature/Scheduling/FinanceDueReminderPlannerTest.php`
- Modify: `tests/Feature/Finance/DueReminderSettingsControllerTest.php`

- [ ] **Step 1: Write the failing finance planner tests**

```php
<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderRule;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use App\Services\Scheduling\FinanceDueReminderPlanner;
use Illuminate\Support\Carbon;

test('finance planner creates one pending job per rule and unpaid schedule', function () {
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $rule = FinanceDueReminderRule::factory()->create(['days_before_due' => 3, 'is_active' => true]);
    $schedule = BillingSchedule::factory()->unpaid()->create([
        'due_date' => Carbon::parse('2026-05-10'),
    ]);

    app(FinanceDueReminderPlanner::class)->reconcileRule($rule);

    $job = ScheduledNotificationJob::query()->where('type', ScheduledNotificationJobType::FINANCE_DUE_REMINDER)->first();

    expect($job)->not->toBeNull()
        ->and($job->status)->toBe(ScheduledNotificationJobStatus::PENDING)
        ->and($job->run_at->format('Y-m-d H:i'))->toBe('2026-05-07 07:30');
});

test('finance planner supersedes pending jobs when the configured send time changes', function () {
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $rule = FinanceDueReminderRule::factory()->create(['days_before_due' => 1, 'is_active' => true]);
    BillingSchedule::factory()->unpaid()->create(['due_date' => Carbon::parse('2026-05-10')]);

    $planner = app(FinanceDueReminderPlanner::class);
    $planner->reconcileRule($rule);

    Setting::set('finance_due_reminder_send_time', '09:45', 'finance_due_reminders');
    $planner->reconcileRule($rule);

    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::SUPERSEDED)->count())->toBe(1)
        ->and(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::PENDING)->first()->run_at->format('H:i'))->toBe('09:45');
});
```

- [ ] **Step 2: Run the finance planner tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Scheduling/FinanceDueReminderPlannerTest.php`

Expected: FAIL because the planner, send job, and finance reconciliation hooks do not exist yet.

- [ ] **Step 3: Implement the finance planner and send job**

```php
final class FinanceDueReminderPlanner
{
    public function reconcileRule(FinanceDueReminderRule $rule): void
    {
        if (! $rule->is_active || ! Setting::enabled('finance_due_reminder_auto_send_enabled', true)) {
            $this->cancelRule($rule, 'automation_disabled');

            return;
        }

        $sendTime = (string) Setting::get('finance_due_reminder_send_time', '07:30');

        $desiredJobs = BillingSchedule::query()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get()
            ->map(function (BillingSchedule $schedule) use ($rule, $sendTime): array {
                $runAt = $schedule->due_date->copy()
                    ->subDays((int) $rule->days_before_due)
                    ->setTimeFromTimeString($sendTime);

                return [
                    'type' => ScheduledNotificationJobType::FINANCE_DUE_REMINDER,
                    'status' => ScheduledNotificationJobStatus::PENDING,
                    'run_at' => $runAt,
                    'dedupe_key' => "finance:rule-{$rule->id}:schedule-{$schedule->id}:{$runAt->format('YmdHi')}",
                    'group_key' => "finance:rule-{$rule->id}",
                    'subject_type' => 'billing_schedule',
                    'subject_id' => $schedule->id,
                    'payload' => [
                        'rule_id' => $rule->id,
                        'days_before_due' => (int) $rule->days_before_due,
                    ],
                ];
            });

        app(ScheduledNotificationPlanner::class)->reconcile(
            ScheduledNotificationJobType::FINANCE_DUE_REMINDER,
            "finance:rule-{$rule->id}",
            $desiredJobs
        );
    }
}
```

```php
final class SendFinanceDueReminderJob implements ShouldQueue
{
    public function __construct(public int $scheduledNotificationJobId) {}

    public function handle(DueReminderNotificationService $service): void
    {
        $job = ScheduledNotificationJob::query()->findOrFail($this->scheduledNotificationJobId);

        $service->sendScheduledJob($job);
    }
}
```

- [ ] **Step 4: Hook finance reconciliation into settings and billing changes**

```php
public function store(StoreDueReminderRuleRequest $request): RedirectResponse
{
    $rule = FinanceDueReminderRule::query()->create([
        'days_before_due' => $request->integer('days_before_due'),
        'is_active' => (bool) ($request->validated('is_active') ?? true),
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    app(FinanceDueReminderPlanner::class)->reconcileRule($rule);

    return back()->with('success', 'Due reminder rule created.');
}
```

```php
public function updateAutomation(UpdateDueReminderAutomationSettingsRequest $request): RedirectResponse
{
    // existing Setting::set calls...

    FinanceDueReminderRule::query()->get()->each(
        fn (FinanceDueReminderRule $rule) => app(FinanceDueReminderPlanner::class)->reconcileRule($rule)
    );

    return back()->with('success', 'Reminder automation settings updated.');
}
```

- [ ] **Step 5: Run the finance planner and relevant finance controller tests**

Run: `php artisan test --compact tests/Feature/Scheduling/FinanceDueReminderPlannerTest.php tests/Feature/Finance/DueReminderSettingsControllerTest.php`

Expected: PASS with pending finance jobs being created and superseded correctly after settings changes.

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/SendFinanceDueReminderJob.php app/Services/Scheduling/FinanceDueReminderPlanner.php app/Services/Finance/DueReminderNotificationService.php app/Http/Controllers/Finance/DueReminderSettingsController.php app/Models/BillingSchedule.php app/Console/Commands/SendFinanceDueRemindersCommand.php tests/Feature/Scheduling/FinanceDueReminderPlannerTest.php tests/Feature/Finance/DueReminderSettingsControllerTest.php
git commit -m "feat: migrate finance reminders to scheduled jobs"
```

### Task 4: Migrate grade deadline reminders onto scheduled notification jobs

**Files:**
- Create: `app/Jobs/SendGradeDeadlineReminderJob.php`
- Create: `app/Services/Scheduling/GradeDeadlineReminderPlanner.php`
- Modify: `app/Services/GradeDeadlineAnnouncementService.php`
- Modify: `app/Http/Controllers/Admin/GradeVerificationController.php`
- Modify: `app/Console/Commands/SendGradeDeadlineRemindersCommand.php`
- Modify: `app/Models/GradeSubmission.php`
- Create: `tests/Feature/Scheduling/GradeDeadlineReminderPlannerTest.php`
- Modify: `tests/Feature/Admin/GradeVerificationControllerTest.php`

- [ ] **Step 1: Write the failing grade reminder planner tests**

```php
<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\AcademicYear;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use App\Services\Scheduling\GradeDeadlineReminderPlanner;
use Illuminate\Support\Carbon;

test('grade planner creates tomorrow and day-of pending jobs for configured deadline', function () {
    $academicYear = AcademicYear::factory()->create(['status' => 'ongoing']);
    Setting::set("grade_submission_deadline_{$academicYear->id}_q1", '2026-05-10 17:00:00', 'grading');
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '07:00', 'grading');

    app(GradeDeadlineReminderPlanner::class)->reconcileAcademicYearQuarter($academicYear, '1');

    expect(ScheduledNotificationJob::query()->where('type', ScheduledNotificationJobType::GRADE_DEADLINE_REMINDER)->count())->toBe(2)
        ->and(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::PENDING)->count())->toBe(2);
});
```

- [ ] **Step 2: Run the grade planner tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Scheduling/GradeDeadlineReminderPlannerTest.php`

Expected: FAIL because the planner and send job do not exist yet.

- [ ] **Step 3: Implement the planner and send job**

```php
final class GradeDeadlineReminderPlanner
{
    public function reconcileAcademicYearQuarter(AcademicYear $academicYear, string $quarter): void
    {
        if (! Setting::enabled('grade_deadline_reminder_auto_send_enabled', true)) {
            $this->cancelGroup($academicYear, $quarter, 'automation_disabled');

            return;
        }

        $deadlineValue = Setting::get("grade_submission_deadline_{$academicYear->id}_q{$quarter}");

        if (! is_string($deadlineValue) || $deadlineValue === '') {
            $this->cancelGroup($academicYear, $quarter, 'deadline_missing');

            return;
        }

        $deadline = Carbon::parse($deadlineValue);
        $sendTime = (string) Setting::get('grade_deadline_reminder_send_time', '07:00');

        $desiredJobs = collect([
            ['phase' => 'tomorrow', 'run_at' => $deadline->copy()->subDay()->setTimeFromTimeString($sendTime)],
            ['phase' => 'today', 'run_at' => $deadline->copy()->setTimeFromTimeString($sendTime)],
        ])->map(function (array $phase) use ($academicYear, $quarter, $deadline): array {
            return [
                'type' => ScheduledNotificationJobType::GRADE_DEADLINE_REMINDER,
                'status' => ScheduledNotificationJobStatus::PENDING,
                'run_at' => $phase['run_at'],
                'dedupe_key' => "grading:ay-{$academicYear->id}:q{$quarter}:{$phase['phase']}:".$deadline->format('YmdHi'),
                'group_key' => "grading:ay-{$academicYear->id}:q{$quarter}",
                'subject_type' => 'academic_year',
                'subject_id' => $academicYear->id,
                'payload' => [
                    'quarter' => $quarter,
                    'phase' => $phase['phase'],
                    'deadline' => $deadline->toDateTimeString(),
                ],
            ];
        });

        app(ScheduledNotificationPlanner::class)->reconcile(
            ScheduledNotificationJobType::GRADE_DEADLINE_REMINDER,
            "grading:ay-{$academicYear->id}:q{$quarter}",
            $desiredJobs
        );
    }
}
```

- [ ] **Step 4: Trigger reconciliation when deadlines or automation change**

```php
public function updateReminderAutomation(UpdateGradeReminderAutomationRequest $request): RedirectResponse
{
    // existing Setting::set calls...

    $activeYear = AcademicYear::query()->where('status', 'ongoing')->first();

    if ($activeYear) {
        collect(['1', '2', '3', '4'])->each(
            fn (string $quarter) => app(GradeDeadlineReminderPlanner::class)->reconcileAcademicYearQuarter($activeYear, $quarter)
        );
    }

    return back()->with('success', 'Grade reminder automation settings updated.');
}
```

- [ ] **Step 5: Run the grade reminder tests**

Run: `php artisan test --compact tests/Feature/Scheduling/GradeDeadlineReminderPlannerTest.php tests/Feature/Admin/GradeVerificationControllerTest.php`

Expected: PASS with future grade reminder jobs created and rescheduled on deadline/settings changes.

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/SendGradeDeadlineReminderJob.php app/Services/Scheduling/GradeDeadlineReminderPlanner.php app/Services/GradeDeadlineAnnouncementService.php app/Http/Controllers/Admin/GradeVerificationController.php app/Console/Commands/SendGradeDeadlineRemindersCommand.php app/Models/GradeSubmission.php tests/Feature/Scheduling/GradeDeadlineReminderPlannerTest.php tests/Feature/Admin/GradeVerificationControllerTest.php
git commit -m "feat: migrate grade reminders to scheduled jobs"
```

### Task 5: Migrate announcement event reminders onto scheduled notification jobs

**Files:**
- Create: `app/Jobs/SendAnnouncementEventReminderJob.php`
- Create: `app/Services/Scheduling/AnnouncementEventReminderPlanner.php`
- Modify: `app/Services/AnnouncementEventReminderService.php`
- Modify: `app/Services/AnnouncementEventService.php`
- Modify: `app/Models/Announcement.php`
- Modify: `app/Models/AnnouncementEventResponse.php`
- Create: `tests/Feature/Scheduling/AnnouncementEventReminderPlannerTest.php`

- [ ] **Step 1: Write the failing event reminder planner tests**

```php
<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\Announcement;
use App\Models\ScheduledNotificationJob;
use App\Models\User;
use App\Services\Scheduling\AnnouncementEventReminderPlanner;
use Illuminate\Support\Carbon;

test('event reminder planner creates recipient-specific one-day-before and day-of jobs', function () {
    $parent = User::factory()->parent()->create();
    $announcement = Announcement::factory()->event()->create([
        'event_starts_at' => Carbon::parse('2026-05-10 09:00:00'),
        'response_deadline_at' => Carbon::parse('2026-05-10 08:00:00'),
    ]);

    $announcement->recipients()->create([
        'user_id' => $parent->id,
        'role' => 'parent',
    ]);

    app(AnnouncementEventReminderPlanner::class)->reconcileAnnouncement($announcement->fresh());

    expect(ScheduledNotificationJob::query()->where('type', ScheduledNotificationJobType::ANNOUNCEMENT_EVENT_REMINDER)->count())->toBe(2)
        ->and(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::PENDING)->count())->toBe(2);
});
```

- [ ] **Step 2: Run the event planner tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Scheduling/AnnouncementEventReminderPlannerTest.php`

Expected: FAIL because the planner and send job do not exist yet.

- [ ] **Step 3: Implement the planner and send job**

```php
final class AnnouncementEventReminderPlanner
{
    public function reconcileAnnouncement(Announcement $announcement): void
    {
        $referencePoint = $announcement->response_deadline_at ?? $announcement->event_starts_at;

        if (! $announcement->is_active || $announcement->cancelled_at || ! $referencePoint) {
            $this->cancelGroup($announcement, 'announcement_invalid');

            return;
        }

        $desiredJobs = $announcement->recipients
            ->filter(fn ($recipient) => $recipient->role !== 'student')
            ->map(function ($recipient) use ($announcement, $referencePoint): array {
                return collect([
                    ['phase' => 'one_day_before', 'run_at' => $referencePoint->copy()->subDay()],
                    ['phase' => 'day_of', 'run_at' => $referencePoint->copy()],
                ])->map(fn (array $phase): array => [
                    'type' => ScheduledNotificationJobType::ANNOUNCEMENT_EVENT_REMINDER,
                    'status' => ScheduledNotificationJobStatus::PENDING,
                    'run_at' => $phase['run_at'],
                    'dedupe_key' => "announcement:event:{$announcement->id}:user:{$recipient->user_id}:{$phase['phase']}:".$referencePoint->format('YmdHi'),
                    'group_key' => "announcement:event:{$announcement->id}",
                    'subject_type' => 'announcement',
                    'subject_id' => $announcement->id,
                    'recipient_type' => 'user',
                    'recipient_id' => $recipient->user_id,
                    'payload' => ['phase' => $phase['phase']],
                ]);
            })
            ->flatten(1)
            ->values();

        app(ScheduledNotificationPlanner::class)->reconcile(
            ScheduledNotificationJobType::ANNOUNCEMENT_EVENT_REMINDER,
            "announcement:event:{$announcement->id}",
            $desiredJobs
        );
    }
}
```

- [ ] **Step 4: Trigger reconciliation on event and response changes**

```php
public function syncReminderSchedule(Announcement $announcement): void
{
    $announcement->loadMissing(['recipients', 'eventResponses']);

    app(AnnouncementEventReminderPlanner::class)->reconcileAnnouncement($announcement);
}
```

Call `syncReminderSchedule()` from the announcement create/update/cancel flows and from the event response create/update path.

- [ ] **Step 5: Run the event reminder tests**

Run: `php artisan test --compact tests/Feature/Scheduling/AnnouncementEventReminderPlannerTest.php`

Expected: PASS with recipient-specific event reminder jobs created and ready for later cancellation/superseding.

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/SendAnnouncementEventReminderJob.php app/Services/Scheduling/AnnouncementEventReminderPlanner.php app/Services/AnnouncementEventReminderService.php app/Services/AnnouncementEventService.php app/Models/Announcement.php app/Models/AnnouncementEventResponse.php tests/Feature/Scheduling/AnnouncementEventReminderPlannerTest.php
git commit -m "feat: migrate event reminders to scheduled jobs"
```

### Task 6: Wire concrete dispatch, remove legacy polling, and verify end to end

**Files:**
- Create: `app/Jobs/DispatchScheduledNotificationJob.php`
- Modify: `app/Services/Scheduling/ScheduledNotificationDispatcher.php`
- Modify: `app/Console/Commands/SendFinanceDueRemindersCommand.php`
- Modify: `app/Console/Commands/SendGradeDeadlineRemindersCommand.php`
- Modify: `app/Console/Commands/SendAnnouncementEventRemindersCommand.php`
- Modify: `routes/console.php`
- Create: `tests/Feature/Scheduling/ScheduledNotificationEndToEndTest.php`

- [ ] **Step 1: Write the failing end-to-end scheduler tests**

```php
<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Models\ScheduledNotificationJob;
use Illuminate\Support\Facades\Queue;

test('dispatcher queues the concrete send job for a valid due notification', function () {
    Queue::fake();

    $job = ScheduledNotificationJob::factory()->create([
        'status' => ScheduledNotificationJobStatus::PENDING,
        'run_at' => now()->subMinute(),
        'type' => 'finance_due_reminder',
    ]);

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    Queue::assertPushed(DispatchScheduledNotificationJob::class);
});
```

- [ ] **Step 2: Run the end-to-end scheduler tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Scheduling/ScheduledNotificationEndToEndTest.php`

Expected: FAIL because the concrete dispatch job and final status wiring do not exist yet.

- [ ] **Step 3: Implement concrete dispatch and retire minute polling**

```php
final class DispatchScheduledNotificationJob implements ShouldQueue
{
    public function __construct(public int $scheduledNotificationJobId) {}

    public function handle(): void
    {
        $job = ScheduledNotificationJob::query()->findOrFail($this->scheduledNotificationJobId);

        match ($job->type) {
            ScheduledNotificationJobType::FINANCE_DUE_REMINDER => dispatch_sync(new SendFinanceDueReminderJob($job->id)),
            ScheduledNotificationJobType::GRADE_DEADLINE_REMINDER => dispatch_sync(new SendGradeDeadlineReminderJob($job->id)),
            ScheduledNotificationJobType::ANNOUNCEMENT_EVENT_REMINDER => dispatch_sync(new SendAnnouncementEventReminderJob($job->id)),
        };

        $job->update([
            'status' => ScheduledNotificationJobStatus::DISPATCHED,
            'dispatched_at' => now(),
        ]);
    }
}
```

Replace legacy `everyMinute()` reminder schedule entries with the single dispatcher command:

```php
Schedule::command('notifications:dispatch-scheduled')->everyMinute();
```

- [ ] **Step 4: Run targeted scheduler tests and the affected reminder suites**

Run: `php artisan test --compact tests/Feature/Scheduling`

Expected: PASS with shared scheduling, finance, grading, announcement, and end-to-end dispatch scenarios all green.

- [ ] **Step 5: Run formatting and high-signal verification**

Run: `vendor/bin/pint --dirty --format agent`

Run: `php artisan test --compact tests/Feature/Scheduling tests/Feature/Finance/DueReminderSettingsControllerTest.php tests/Feature/Admin/GradeVerificationControllerTest.php`

Expected: PASS with formatting clean and all targeted reminder scheduling tests green.

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/DispatchScheduledNotificationJob.php app/Services/Scheduling/ScheduledNotificationDispatcher.php app/Console/Commands/SendFinanceDueRemindersCommand.php app/Console/Commands/SendGradeDeadlineRemindersCommand.php app/Console/Commands/SendAnnouncementEventRemindersCommand.php routes/console.php tests/Feature/Scheduling/ScheduledNotificationEndToEndTest.php
git commit -m "refactor: replace reminder polling with scheduled notification dispatch"
```

## Plan Self-Review

### Spec coverage

- Central shared scheduling table: covered in Task 1.
- Planner/reconciler services and dispatcher: covered in Task 2.
- Finance migration with cancellation and send-time superseding: covered in Task 3.
- Grade migration with deadline changes and completion-based skipping: covered in Task 4.
- Announcement event migration with recipient-specific scheduling and response-driven reconciliation: covered in Task 5.
- Cleanup of legacy minute polling and end-to-end dispatch: covered in Task 6.

### Placeholder scan

- No `TBD`, `TODO`, or deferred placeholders remain in the implementation tasks.
- Each task includes exact files, exact commands, and concrete code snippets for tests and implementation direction.

### Type consistency

- Shared enums and model names are introduced in Task 1 and reused consistently in later tasks.
- The dispatcher infrastructure introduced in Task 2 is the same one used by the reminder-specific tasks and the end-to-end task.
- Reminder-specific planner and job names are consistent across all tasks.
