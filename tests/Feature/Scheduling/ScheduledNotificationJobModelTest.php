<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('scheduled notification job casts enum, datetime, and payload attributes', function () {
    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::FinanceDueReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 08:30:00',
        'dedupe_key' => 'finance-due:student-1:2026-04-20',
        'group_key' => 'finance-due:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => '1',
        'recipient_type' => User::class,
        'recipient_id' => '2',
        'payload' => ['channel' => 'database', 'attempt' => 1],
        'planned_by_type' => User::class,
        'planned_by_id' => '3',
        'dispatched_at' => '2026-04-20 08:35:00',
        'canceled_at' => '2026-04-20 08:40:00',
    ]);

    $job->refresh();

    expect($job->type)->toBe(ScheduledNotificationJobType::FinanceDueReminder)
        ->and($job->status)->toBe(ScheduledNotificationJobStatus::Pending)
        ->and($job->run_at)->toBeInstanceOf(\DateTimeInterface::class)
        ->and($job->dispatched_at)->toBeInstanceOf(\DateTimeInterface::class)
        ->and($job->canceled_at)->toBeInstanceOf(\DateTimeInterface::class)
        ->and($job->payload)->toBe([
            'channel' => 'database',
            'attempt' => 1,
        ]);
});

test('scheduled notification job enforces unique dedupe key', function () {
    ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::GradeDeadlineReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 09:00:00',
        'dedupe_key' => 'grade-deadline:teacher-1:q1',
        'group_key' => 'grade-deadline:q1',
        'subject_type' => User::class,
        'subject_id' => '1',
    ]);

    ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::AnnouncementEventReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-04-20 09:05:00',
        'dedupe_key' => 'grade-deadline:teacher-1:q1',
        'group_key' => 'announcement-event:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => '2',
    ]);
})->throws(QueryException::class);

test('scheduled notification job rejects invalid raw enum values', function (array $attributes) {
    DB::table('scheduled_notification_jobs')->insert(
        array_merge(scheduledNotificationJobAttributes(), $attributes)
    );
})->with([
    'invalid type' => [[
        'type' => 'invalid_type',
    ]],
    'invalid status' => [[
        'status' => 'invalid_status',
    ]],
])->throws(QueryException::class);

test('scheduled notification job rejects partially populated nullable polymorphic pairs', function (array $attributes) {
    DB::table('scheduled_notification_jobs')->insert(
        array_merge(scheduledNotificationJobAttributes(), $attributes)
    );
})->with([
    'recipient type without recipient id' => [[
        'recipient_type' => User::class,
    ]],
    'recipient id without recipient type' => [[
        'recipient_id' => 99,
    ]],
    'planned by type without planned by id' => [[
        'planned_by_type' => User::class,
    ]],
    'planned by id without planned by type' => [[
        'planned_by_id' => 99,
    ]],
])->throws(QueryException::class);

function scheduledNotificationJobAttributes(): array
{
    return [
        'type' => ScheduledNotificationJobType::FinanceDueReminder->value,
        'status' => ScheduledNotificationJobStatus::Pending->value,
        'run_at' => '2026-04-20 08:30:00',
        'dedupe_key' => fake()->unique()->slug(3),
        'group_key' => 'finance-due:2026-04-20',
        'subject_type' => User::class,
        'subject_id' => 1,
        'recipient_type' => null,
        'recipient_id' => null,
        'payload' => json_encode(['channel' => 'database']),
        'planned_by_type' => null,
        'planned_by_id' => null,
        'dispatched_at' => null,
        'canceled_at' => null,
        'skip_reason' => null,
        'failure_reason' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
