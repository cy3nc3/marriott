<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use App\Models\User;
use App\Services\Scheduling\ScheduledNotificationPlanner;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('scheduled notification planner creates missing jobs and supersedes stale pending jobs within a group', function () {
    Carbon::setTestNow('2026-04-20 08:00:00');

    $keptJob = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 08:30:00',
        'dedupe_key' => 'finance-due:keep',
        'group_key' => 'finance-due:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => 1,
        'payload' => ['channel' => 'database'],
    ]);

    $staleJob = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 08:45:00',
        'dedupe_key' => 'finance-due:stale',
        'group_key' => 'finance-due:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => 2,
        'payload' => ['channel' => 'database'],
    ]);

    app(ScheduledNotificationPlanner::class)->reconcile(
        ScheduledNotificationJobType::FinanceDueReminder,
        'finance-due:2026-04-20',
        [
            [
                'dedupe_key' => 'finance-due:keep',
                'run_at' => Carbon::parse('2026-04-20 08:30:00'),
                'subject_type' => User::class,
                'subject_id' => 1,
                'payload' => ['channel' => 'database'],
            ],
            [
                'dedupe_key' => 'finance-due:new',
                'run_at' => Carbon::parse('2026-04-20 09:00:00'),
                'subject_type' => User::class,
                'subject_id' => 3,
                'payload' => ['channel' => 'database'],
            ],
        ]
    );

    $keptJob->refresh();
    $staleJob->refresh();

    $newJob = ScheduledNotificationJob::query()
        ->where('dedupe_key', 'finance-due:new')
        ->first();

    expect($keptJob->status)->toBe(ScheduledNotificationJobStatus::Pending)
        ->and($staleJob->status)->toBe(ScheduledNotificationJobStatus::Superseded)
        ->and($staleJob->canceled_at?->toDateTimeString())->toBe('2026-04-20 08:00:00')
        ->and($newJob)->not->toBeNull()
        ->and($newJob?->status)->toBe(ScheduledNotificationJobStatus::Pending)
        ->and($newJob?->group_key)->toBe('finance-due:2026-04-20');

    Carbon::setTestNow();
});

test('scheduled notification dispatcher command skips due jobs whose subject is missing', function () {
    Carbon::setTestNow('2026-04-20 08:00:00');

    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 07:59:00',
        'dedupe_key' => 'finance-due:missing-subject',
        'group_key' => 'finance-due:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => 999999,
        'payload' => ['channel' => 'database'],
    ]);

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    $job->refresh();

    expect($job->status)->toBe(ScheduledNotificationJobStatus::Skipped)
        ->and($job->skip_reason)->toBe('subject_missing')
        ->and($job->dispatched_at)->toBeNull();

    Carbon::setTestNow();
});

test('scheduled notification dispatcher command dispatches a valid due job only once', function () {
    Carbon::setTestNow('2026-04-20 08:00:00');

    $user = User::factory()->create();

    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 07:59:00',
        'dedupe_key' => 'finance-due:valid-subject',
        'group_key' => 'finance-due:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'payload' => ['channel' => 'database'],
    ]);

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    $job->refresh();

    expect($job->status)->toBe(ScheduledNotificationJobStatus::Dispatched)
        ->and($job->dispatched_at?->toDateTimeString())->toBe('2026-04-20 08:00:00')
        ->and($job->skip_reason)->toBeNull();

    $dispatchedAt = $job->dispatched_at;

    Carbon::setTestNow('2026-04-20 08:01:00');

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    $job->refresh();

    expect($job->status)->toBe(ScheduledNotificationJobStatus::Dispatched)
        ->and($job->dispatched_at?->toDateTimeString())->toBe($dispatchedAt?->toDateTimeString());

    Carbon::setTestNow();
});

test('scheduled notification dispatcher command is registered to run every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($scheduledEvent) => str_contains($scheduledEvent->command, 'notifications:dispatch-scheduled'));

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe('* * * * *');
});
