<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Jobs\DispatchScheduledNotificationJob;
use App\Models\ScheduledNotificationJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('dispatcher queues a concrete dispatch job and marks the scheduled row processing', function () {
    Queue::fake();

    $user = User::factory()->create();
    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => now()->subMinute(),
        'dedupe_key' => 'finance:e2e:processing',
        'group_key' => 'finance:e2e',
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    Queue::assertPushed(DispatchScheduledNotificationJob::class, fn (DispatchScheduledNotificationJob $queuedJob): bool => $queuedJob->scheduledNotificationJobId === $job->id);

    expect($job->fresh()->status)->toBe(ScheduledNotificationJobStatus::Processing);
});

test('dispatcher does not queue the same processing row twice', function () {
    Queue::fake();

    $user = User::factory()->create();
    ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Processing,
        'run_at' => now()->subMinute(),
        'dedupe_key' => 'finance:e2e:already-processing',
        'group_key' => 'finance:e2e',
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    Queue::assertNothingPushed();
});
