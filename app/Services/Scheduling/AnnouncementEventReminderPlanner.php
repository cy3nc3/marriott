<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\ScheduledNotificationJob;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnnouncementEventReminderPlanner
{
    public function __construct(
        private readonly ScheduledNotificationPlanner $planner
    ) {}

    public function reconcileAnnouncement(Announcement $announcement): void
    {
        $announcement->loadMissing(['recipients', 'eventResponses']);

        $referencePoint = $announcement->response_deadline_at ?? $announcement->event_starts_at;

        if (
            ! $announcement->isEventType()
            || $announcement->response_mode !== Announcement::RESPONSE_MODE_ACK_RSVP
            || ! $announcement->is_active
            || $announcement->cancelled_at !== null
            || $referencePoint === null
        ) {
            $this->cancelGroup($announcement, 'announcement_invalid');

            return;
        }

        $this->planner->reconcile(
            ScheduledNotificationJobType::AnnouncementEventReminder,
            $this->groupKey($announcement),
            $this->desiredJobs($announcement, $referencePoint)->all()
        );
    }

    public function cancelGroup(Announcement $announcement, string $reason): void
    {
        ScheduledNotificationJob::query()
            ->where('type', ScheduledNotificationJobType::AnnouncementEventReminder)
            ->where('group_key', $this->groupKey($announcement))
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->update([
                'status' => ScheduledNotificationJobStatus::Canceled,
                'canceled_at' => now(),
                'skip_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function cancelRecipient(Announcement $announcement, int $recipientId, string $reason): void
    {
        ScheduledNotificationJob::query()
            ->where('type', ScheduledNotificationJobType::AnnouncementEventReminder)
            ->where('group_key', $this->groupKey($announcement))
            ->where('recipient_type', User::class)
            ->where('recipient_id', $recipientId)
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->update([
                'status' => ScheduledNotificationJobStatus::Canceled,
                'canceled_at' => now(),
                'skip_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return Collection<int, array{
     *     dedupe_key: string,
     *     run_at: \Carbon\CarbonInterface,
     *     subject_type: class-string,
     *     subject_id: int,
     *     recipient_type: class-string,
     *     recipient_id: int,
     *     payload: array<string, mixed>
     * }>
     */
    private function desiredJobs(Announcement $announcement, mixed $referencePoint): Collection
    {
        $respondedUserIds = $announcement->eventResponses
            ->pluck('user_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        return $announcement->recipients
            ->filter(fn ($recipient): bool => $recipient->role !== UserRole::STUDENT->value)
            ->reject(fn ($recipient): bool => in_array((int) $recipient->user_id, $respondedUserIds, true))
            ->flatMap(function ($recipient) use ($announcement, $referencePoint): Collection {
                return collect([
                    'one_day_before' => $referencePoint->copy()->subDay(),
                    'day_of' => $referencePoint->copy(),
                ])->map(function (CarbonInterface $runAt, string $phase) use ($announcement, $recipient, $referencePoint): array {
                    return [
                        'dedupe_key' => $this->dedupeKey($announcement, (int) $recipient->user_id, $phase, $referencePoint),
                        'run_at' => $runAt,
                        'subject_type' => Announcement::class,
                        'subject_id' => (int) $announcement->id,
                        'recipient_type' => User::class,
                        'recipient_id' => (int) $recipient->user_id,
                        'payload' => [
                            'phase' => $phase,
                            'reference_point' => $referencePoint->toDateTimeString(),
                        ],
                    ];
                });
            })
            ->filter(fn (array $job): bool => $job['run_at']->greaterThanOrEqualTo(now()))
            ->values();
    }

    private function groupKey(Announcement $announcement): string
    {
        return "announcement:event:{$announcement->id}";
    }

    private function dedupeKey(Announcement $announcement, int $recipientId, string $phase, mixed $referencePoint): string
    {
        return "announcement:event:{$announcement->id}:user:{$recipientId}:{$phase}:{$referencePoint->format('YmdHi')}";
    }
}
