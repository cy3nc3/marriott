<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Enums\UserRole;
use App\Jobs\SendAnnouncementEventReminderJob;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\AnnouncementReminderDispatch;
use App\Models\ScheduledNotificationJob;
use App\Models\User;
use App\Services\AnnouncementEventReminderService;
use App\Services\Scheduling\AnnouncementEventReminderPlanner;
use Illuminate\Support\Carbon;

test('event reminder planner creates recipient-specific one-day-before and day-of jobs', function () {
    Carbon::setTestNow('2026-05-01 08:00:00');

    [$announcement, $recipient] = createScheduledEventAnnouncement();

    app(AnnouncementEventReminderPlanner::class)->reconcileAnnouncement($announcement->fresh());

    $jobs = ScheduledNotificationJob::query()
        ->where('type', ScheduledNotificationJobType::AnnouncementEventReminder)
        ->orderBy('run_at')
        ->get();

    expect($jobs)->toHaveCount(2)
        ->and($jobs->pluck('status')->all())->toBe([
            ScheduledNotificationJobStatus::Pending,
            ScheduledNotificationJobStatus::Pending,
        ])
        ->and($jobs->pluck('recipient_id')->all())->toBe([$recipient->id, $recipient->id])
        ->and($jobs->pluck('payload')->pluck('phase')->all())->toBe(['one_day_before', 'day_of']);

    Carbon::setTestNow();
});

test('event reminder planner cancels pending recipient jobs after a response is submitted', function () {
    Carbon::setTestNow('2026-05-01 08:00:00');

    [$announcement, $recipient] = createScheduledEventAnnouncement();

    app(AnnouncementEventReminderPlanner::class)->reconcileAnnouncement($announcement->fresh());

    $announcement->eventResponses()->create([
        'user_id' => $recipient->id,
        'response' => 'yes',
        'responded_at' => now(),
    ]);

    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Canceled)->count())->toBe(2)
        ->and(ScheduledNotificationJob::query()->first()?->skip_reason)->toBe('recipient_responded');

    Carbon::setTestNow();
});

test('event reminder send job dispatches one reminder once', function () {
    Carbon::setTestNow('2026-05-09 08:00:00');

    [$announcement, $recipient] = createScheduledEventAnnouncement();
    AnnouncementRead::query()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
        'read_at' => now(),
    ]);

    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::AnnouncementEventReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-05-09 08:00:00',
        'dedupe_key' => "announcement:event:{$announcement->id}:user:{$recipient->id}:one_day_before:202605100800",
        'group_key' => "announcement:event:{$announcement->id}",
        'subject_type' => Announcement::class,
        'subject_id' => $announcement->id,
        'recipient_type' => User::class,
        'recipient_id' => $recipient->id,
        'payload' => [
            'phase' => 'one_day_before',
            'reference_point' => '2026-05-10 08:00:00',
        ],
    ]);

    (new SendAnnouncementEventReminderJob($job->id))
        ->handle(app(AnnouncementEventReminderService::class));

    $job->refresh();

    expect(AnnouncementReminderDispatch::query()->count())->toBe(1)
        ->and($job->status)->toBe(ScheduledNotificationJobStatus::Dispatched)
        ->and($job->dispatched_at?->toDateTimeString())->toBe('2026-05-09 08:00:00');

    $this->assertDatabaseMissing('announcement_reads', [
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
    ]);

    (new SendAnnouncementEventReminderJob($job->id))
        ->handle(app(AnnouncementEventReminderService::class));

    expect(AnnouncementReminderDispatch::query()->count())->toBe(1);

    Carbon::setTestNow();
});

function createScheduledEventAnnouncement(): array
{
    $publisher = User::factory()->admin()->create();
    $recipient = User::factory()->parent()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $publisher->id,
        'title' => 'Parent Orientation',
        'content' => 'Please respond to this event notice.',
        'type' => Announcement::TYPE_EVENT,
        'response_mode' => Announcement::RESPONSE_MODE_ACK_RSVP,
        'target_roles' => [UserRole::PARENT->value],
        'is_active' => true,
        'event_starts_at' => '2026-05-10 09:00:00',
        'response_deadline_at' => '2026-05-10 08:00:00',
        'expires_at' => '2026-05-15 00:00:00',
    ]);

    $announcement->recipients()->create([
        'user_id' => $recipient->id,
        'role' => UserRole::PARENT->value,
    ]);

    return [$announcement, $recipient];
}
