<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Enums\UserRole;
use App\Jobs\SendFinanceDueReminderJob;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderDispatch;
use App\Models\FinanceDueReminderRule;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\DueReminderNotificationService;
use App\Services\Scheduling\FinanceDueReminderPlanner;
use Illuminate\Support\Carbon;

test('finance planner creates one pending job per active rule and unpaid schedule', function () {
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $finance = User::factory()->finance()->create();
    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 3,
        'is_active' => true,
        'created_by' => $finance->id,
        'updated_by' => $finance->id,
    ]);
    $schedule = createFinanceReminderBillingSchedule([
        'due_date' => '2026-05-10',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    app(FinanceDueReminderPlanner::class)->reconcileRule($rule);

    $job = ScheduledNotificationJob::query()
        ->where('type', ScheduledNotificationJobType::FinanceDueReminder)
        ->first();

    expect($job)->not->toBeNull()
        ->and($job?->status)->toBe(ScheduledNotificationJobStatus::Pending)
        ->and($job?->run_at?->format('Y-m-d H:i'))->toBe('2026-05-07 07:30')
        ->and($job?->dedupe_key)->toBe("finance:rule-{$rule->id}:schedule-{$schedule->id}:202605070730")
        ->and($job?->group_key)->toBe("finance:rule-{$rule->id}")
        ->and($job?->subject_type)->toBe(BillingSchedule::class)
        ->and($job?->subject_id)->toBe($schedule->id)
        ->and($job?->payload)->toMatchArray([
            'rule_id' => $rule->id,
            'billing_schedule_id' => $schedule->id,
            'days_before_due' => 3,
        ]);
});

test('finance planner supersedes pending jobs when the configured send time changes', function () {
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 1,
        'is_active' => true,
    ]);
    createFinanceReminderBillingSchedule(['due_date' => '2026-05-10']);

    $planner = app(FinanceDueReminderPlanner::class);
    $planner->reconcileRule($rule);

    Setting::set('finance_due_reminder_send_time', '09:45', 'finance_due_reminders');
    $planner->reconcileRule($rule);

    $pendingJob = ScheduledNotificationJob::query()
        ->where('status', ScheduledNotificationJobStatus::Pending)
        ->first();

    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Superseded)->count())->toBe(1)
        ->and($pendingJob?->run_at?->format('Y-m-d H:i'))->toBe('2026-05-09 09:45');
});

test('finance planner does not create pending jobs for past reminder times', function () {
    Carbon::setTestNow('2026-05-09 08:00:00');
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 1,
        'is_active' => true,
    ]);
    createFinanceReminderBillingSchedule([
        'due_date' => '2026-05-10',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    app(FinanceDueReminderPlanner::class)->reconcileRule($rule);

    expect(ScheduledNotificationJob::query()->count())->toBe(0);

    Carbon::setTestNow();
});

test('finance planner cancels pending jobs when automation is disabled', function () {
    Carbon::setTestNow('2026-04-21 10:00:00');
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 1,
        'is_active' => true,
    ]);
    createFinanceReminderBillingSchedule(['due_date' => '2026-05-10']);

    $planner = app(FinanceDueReminderPlanner::class);
    $planner->reconcileRule($rule);

    Setting::set('finance_due_reminder_auto_send_enabled', false, 'finance_due_reminders');
    $planner->reconcileRule($rule);

    $job = ScheduledNotificationJob::query()->first();

    expect($job?->status)->toBe(ScheduledNotificationJobStatus::Canceled)
        ->and($job?->canceled_at?->toDateTimeString())->toBe('2026-04-21 10:00:00')
        ->and($job?->skip_reason)->toBe('automation_disabled');

    Carbon::setTestNow();
});

test('finance planner cancels only the paid schedule pending reminders', function () {
    Carbon::setTestNow('2026-04-21 10:00:00');
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 1,
        'is_active' => true,
    ]);
    $paidSchedule = createFinanceReminderBillingSchedule([
        'due_date' => '2026-05-10',
        'status' => 'unpaid',
    ]);
    $openSchedule = createFinanceReminderBillingSchedule([
        'due_date' => '2026-05-11',
        'status' => 'unpaid',
    ]);

    $planner = app(FinanceDueReminderPlanner::class);
    $planner->reconcileRule($rule);

    $paidSchedule->forceFill([
        'amount_paid' => 2500,
        'status' => 'paid',
    ])->save();

    $paidJob = ScheduledNotificationJob::query()
        ->where('subject_id', $paidSchedule->id)
        ->first();
    $openJob = ScheduledNotificationJob::query()
        ->where('subject_id', $openSchedule->id)
        ->first();

    expect($paidJob?->status)->toBe(ScheduledNotificationJobStatus::Canceled)
        ->and($paidJob?->skip_reason)->toBe('billing_schedule_paid')
        ->and($openJob?->status)->toBe(ScheduledNotificationJobStatus::Pending);

    Carbon::setTestNow();
});

test('finance due reminder send job sends a scheduled reminder once', function () {
    Carbon::setTestNow('2026-04-21 07:30:00');
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');

    User::factory()->finance()->create();
    $schedule = createFinanceReminderBillingSchedule([
        'due_date' => '2026-04-24',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);
    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 3,
        'is_active' => true,
    ]);
    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-21 07:30:00',
        'dedupe_key' => "finance:rule-{$rule->id}:schedule-{$schedule->id}:202604210730",
        'group_key' => "finance:rule-{$rule->id}",
        'subject_type' => BillingSchedule::class,
        'subject_id' => $schedule->id,
        'payload' => [
            'rule_id' => $rule->id,
            'billing_schedule_id' => $schedule->id,
            'days_before_due' => 3,
        ],
    ]);

    (new SendFinanceDueReminderJob($job->id))
        ->handle(app(DueReminderNotificationService::class));

    $job->refresh();

    expect(Announcement::query()->count())->toBe(1)
        ->and(FinanceDueReminderDispatch::query()->count())->toBe(1)
        ->and($job->status)->toBe(ScheduledNotificationJobStatus::Dispatched)
        ->and($job->dispatched_at?->toDateTimeString())->toBe('2026-04-21 07:30:00');

    (new SendFinanceDueReminderJob($job->id))
        ->handle(app(DueReminderNotificationService::class));

    expect(Announcement::query()->count())->toBe(1)
        ->and(FinanceDueReminderDispatch::query()->count())->toBe(1);

    Carbon::setTestNow();
});

function createFinanceReminderBillingSchedule(array $attributes = []): BillingSchedule
{
    $academicYear = AcademicYear::query()->create([
        'name' => fake()->unique()->numerify('2026-2027 ###'),
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => fake()->unique()->numerify('9###########'),
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
    ]);

    $parent = User::factory()->create([
        'role' => UserRole::PARENT,
        'is_active' => true,
    ]);
    $parent->students()->attach($student->id);

    return BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'Installment',
        'due_date' => '2026-05-10',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
        ...$attributes,
    ]);
}
