<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderRule;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('finance can view due reminder settings page', function () {
    $finance = User::factory()->finance()->create();

    $this->actingAs($finance)
        ->get('/finance/due-reminder-settings')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/due-reminder-settings/index')
            ->has('rules', 0)
            ->where('automation.auto_send_enabled', true)
            ->where('automation.send_time', '07:30')
            ->where('automation.max_announcements_per_run', null)
        );
});

test('finance can create update and delete due reminder rules', function () {
    $finance = User::factory()->finance()->create();
    createDueReminderSettingsBillingSchedule([
        'due_date' => '2026-05-10',
    ]);
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');

    $this->actingAs($finance)
        ->post('/finance/due-reminder-settings', [
            'days_before_due' => 3,
            'is_active' => true,
        ])
        ->assertRedirect();

    $rule = FinanceDueReminderRule::query()->first();

    expect($rule)->not()->toBeNull();
    expect($rule?->days_before_due)->toBe(3);
    expect($rule?->is_active)->toBeTrue();
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Pending)->count())->toBe(1);

    $this->actingAs($finance)
        ->patch("/finance/due-reminder-settings/{$rule->id}", [
            'days_before_due' => 1,
            'is_active' => false,
        ])
        ->assertRedirect();

    $rule->refresh();

    expect($rule->days_before_due)->toBe(1);
    expect($rule->is_active)->toBeFalse();
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Canceled)->count())->toBe(1);

    $this->actingAs($finance)
        ->delete("/finance/due-reminder-settings/{$rule->id}")
        ->assertRedirect();

    expect(FinanceDueReminderRule::query()->count())->toBe(0);
});

test('non finance users cannot access due reminder settings page', function () {
    $student = User::factory()->create([
        'role' => UserRole::STUDENT,
    ]);

    $this->actingAs($student)
        ->get('/finance/due-reminder-settings')
        ->assertForbidden();
});

test('finance can update due reminder automation settings', function () {
    $finance = User::factory()->finance()->create();
    createDueReminderSettingsBillingSchedule([
        'due_date' => '2026-05-10',
    ]);
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');
    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 1,
        'is_active' => true,
        'created_by' => $finance->id,
        'updated_by' => $finance->id,
    ]);
    app(\App\Services\Scheduling\FinanceDueReminderPlanner::class)->reconcileRule($rule);

    $this->actingAs($finance)
        ->patch('/finance/due-reminder-settings/automation', [
            'auto_send_enabled' => true,
            'send_time' => '09:15',
            'max_announcements_per_run' => 25,
        ])
        ->assertRedirect();

    $pendingJob = ScheduledNotificationJob::query()
        ->where('status', ScheduledNotificationJobStatus::Pending)
        ->first();

    expect(Setting::get('finance_due_reminder_auto_send_enabled'))->toBe('1');
    expect(Setting::get('finance_due_reminder_send_time'))->toBe('09:15');
    expect(Setting::get('finance_due_reminder_max_announcements_per_run'))->toBe('25');
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Superseded)->count())->toBe(1);
    expect($pendingJob?->run_at?->format('Y-m-d H:i'))->toBe('2026-05-09 09:15');
});

test('finance can disable due reminder automation and cancel pending jobs', function () {
    $finance = User::factory()->finance()->create();
    createDueReminderSettingsBillingSchedule([
        'due_date' => '2026-05-10',
    ]);
    Setting::set('finance_due_reminder_auto_send_enabled', true, 'finance_due_reminders');
    Setting::set('finance_due_reminder_send_time', '07:30', 'finance_due_reminders');
    $rule = FinanceDueReminderRule::factory()->create([
        'days_before_due' => 1,
        'is_active' => true,
        'created_by' => $finance->id,
        'updated_by' => $finance->id,
    ]);
    app(\App\Services\Scheduling\FinanceDueReminderPlanner::class)->reconcileRule($rule);

    $this->actingAs($finance)
        ->patch('/finance/due-reminder-settings/automation', [
            'auto_send_enabled' => false,
            'send_time' => '07:30',
            'max_announcements_per_run' => null,
        ])
        ->assertRedirect();

    $job = ScheduledNotificationJob::query()->first();

    expect(Setting::get('finance_due_reminder_auto_send_enabled'))->toBe('0');
    expect($job?->status)->toBe(ScheduledNotificationJobStatus::Canceled);
    expect($job?->skip_reason)->toBe('automation_disabled');
});

function createDueReminderSettingsBillingSchedule(array $attributes = []): BillingSchedule
{
    $academicYear = AcademicYear::query()->create([
        'name' => fake()->unique()->numerify('2026-2027 ###'),
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => fake()->unique()->numerify('8###########'),
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
    ]);

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
