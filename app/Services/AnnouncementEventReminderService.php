<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\AnnouncementReminderDispatch;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AnnouncementEventReminderService
{
    /**
     * @return array{
     *     processed_events: int,
     *     matched_events: int,
     *     unresolved_recipients: int,
     *     dispatched: int,
     *     skipped_duplicates: int
     * }
     */
    public function sendForDate(Carbon $referenceDate): array
    {
        $runDate = $referenceDate->copy()->startOfDay();

        $events = Announcement::query()
            ->with([
                'recipients:id,announcement_id,user_id,role',
                'eventResponses:id,announcement_id,user_id,response,responded_at',
            ])
            ->where('type', Announcement::TYPE_EVENT)
            ->where('response_mode', Announcement::RESPONSE_MODE_ACK_RSVP)
            ->where('is_active', true)
            ->whereNull('cancelled_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->get();

        $summary = [
            'processed_events' => (int) $events->count(),
            'matched_events' => 0,
            'unresolved_recipients' => 0,
            'dispatched' => 0,
            'skipped_duplicates' => 0,
        ];

        foreach ($events as $event) {
            $referencePoint = $event->response_deadline_at ?? $event->event_starts_at;
            if ($referencePoint === null) {
                continue;
            }

            $targetDate = $referencePoint->copy()->startOfDay();
            $phase = $this->resolvePhase($runDate, $targetDate);
            if ($phase === null) {
                continue;
            }

            $summary['matched_events']++;

            $respondedUserIds = $event->eventResponses
                ->pluck('user_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->unique()
                ->values();

            $unresolvedRecipientIds = $event->recipients
                ->filter(fn ($recipient): bool => $recipient->role !== UserRole::STUDENT->value)
                ->pluck('user_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->unique()
                ->diff($respondedUserIds)
                ->values();

            $summary['unresolved_recipients'] += (int) $unresolvedRecipientIds->count();

            foreach ($unresolvedRecipientIds as $recipientId) {
                $alreadyDispatched = AnnouncementReminderDispatch::query()
                    ->where('announcement_id', $event->id)
                    ->where('user_id', $recipientId)
                    ->where('phase', $phase)
                    ->whereDate('target_date', $runDate->toDateString())
                    ->exists();

                if ($alreadyDispatched) {
                    $summary['skipped_duplicates']++;

                    continue;
                }

                AnnouncementReminderDispatch::query()->create([
                    'announcement_id' => $event->id,
                    'user_id' => $recipientId,
                    'phase' => $phase,
                    'target_date' => $runDate->toDateString(),
                    'sent_at' => now(),
                ]);

                AnnouncementRead::query()
                    ->where('announcement_id', $event->id)
                    ->where('user_id', $recipientId)
                    ->delete();

                $summary['dispatched']++;
            }
        }

        return $summary;
    }

    private function resolvePhase(Carbon $runDate, CarbonInterface $targetDate): ?string
    {
        if ($runDate->isSameDay($targetDate->copy()->subDay())) {
            return 'one_day_before';
        }

        if ($runDate->isSameDay($targetDate)) {
            return 'day_of';
        }

        return null;
    }
}
