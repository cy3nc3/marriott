<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use App\Services\Scheduling\AnnouncementEventReminderPlanner;
use Illuminate\Support\Collection;

class AnnouncementEventService
{
    public function __construct(private AnnouncementAudienceResolver $announcementAudienceResolver) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalizeAnnouncementPayload(array $validated): array
    {
        $normalizedType = $validated['type'] ?? Announcement::TYPE_NOTICE;
        if (! in_array($normalizedType, [Announcement::TYPE_NOTICE, Announcement::TYPE_EVENT], true)) {
            $normalizedType = Announcement::TYPE_NOTICE;
        }

        $targetRoles = $this->normalizeStringArray($validated['target_roles'] ?? null);
        $targetUserIds = $this->normalizeIntegerArray($validated['target_user_ids'] ?? null);
        $deliveryChannels = $this->normalizeDeliveryChannels($validated['delivery_channels'] ?? null);

        $payload = [
            ...$validated,
            'type' => $normalizedType,
            'target_roles' => $targetRoles !== [] ? $targetRoles : null,
            'target_user_ids' => $targetUserIds !== [] ? $targetUserIds : null,
            'delivery_channels' => $deliveryChannels,
        ];

        if ($normalizedType === Announcement::TYPE_EVENT) {
            $payload['response_mode'] = Announcement::RESPONSE_MODE_ACK_RSVP;

            return $payload;
        }

        $payload['response_mode'] = Announcement::RESPONSE_MODE_NONE;
        $payload['event_starts_at'] = null;
        $payload['event_ends_at'] = null;
        $payload['response_deadline_at'] = null;
        $payload['cancelled_at'] = null;
        $payload['cancelled_by'] = null;
        $payload['cancel_reason'] = null;

        return $payload;
    }

    public function syncRecipients(
        Announcement $announcement,
        User $organizer,
        ?int $audienceAcademicYearId = null,
        bool $markUnread = false
    ): void {
        if (! $announcement->isEventType()) {
            $this->clearEventWorkflowState($announcement);

            return;
        }

        $recipients = $this->announcementAudienceResolver->resolveRecipients(
            $organizer,
            $this->normalizeStringArray($announcement->target_roles),
            $this->normalizeIntegerArray($announcement->target_user_ids),
            $audienceAcademicYearId
        );

        $timestamp = now();
        $recipientRows = $recipients
            ->map(function (User $recipient) use ($announcement, $timestamp): array {
                return [
                    'announcement_id' => (int) $announcement->id,
                    'user_id' => (int) $recipient->id,
                    'role' => $this->resolveRoleValue($recipient),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        $announcement->recipients()->upsert(
            $recipientRows,
            ['announcement_id', 'user_id'],
            ['role', 'updated_at']
        );

        $recipientUserIds = $recipients
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->values();

        $announcement->recipients()
            ->whereNotIn('user_id', $recipientUserIds->all())
            ->delete();

        $announcement->eventResponses()
            ->whereNotIn('user_id', $recipientUserIds->all())
            ->delete();

        $announcement->reminderDispatches()->delete();

        if ($markUnread) {
            $this->markRecipientsUnread($announcement, $recipientUserIds);
        }

        $this->syncReminderSchedule($announcement);
    }

    public function cancelEvent(Announcement $announcement, User $actor, ?string $cancelReason = null): void
    {
        if (! $announcement->isEventType()) {
            return;
        }

        $announcement->update([
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancel_reason' => $cancelReason !== null && trim($cancelReason) !== '' ? trim($cancelReason) : null,
        ]);

        $this->syncReminderSchedule($announcement->fresh());

        $recipientUserIds = $announcement->recipients()
            ->pluck('user_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->values();

        if ($recipientUserIds->isNotEmpty()) {
            $this->markRecipientsUnread($announcement, $recipientUserIds);
        }
    }

    public function syncReminderSchedule(Announcement $announcement): void
    {
        $announcement->loadMissing(['recipients', 'eventResponses']);

        app(AnnouncementEventReminderPlanner::class)->reconcileAnnouncement($announcement);
    }

    /**
     * @param  Collection<int, int>  $recipientUserIds
     */
    private function markRecipientsUnread(Announcement $announcement, Collection $recipientUserIds): void
    {
        if ($recipientUserIds->isEmpty()) {
            return;
        }

        AnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->whereIn('user_id', $recipientUserIds->all())
            ->delete();
    }

    private function clearEventWorkflowState(Announcement $announcement): void
    {
        app(AnnouncementEventReminderPlanner::class)->cancelGroup($announcement, 'announcement_invalid');

        $announcement->recipients()->delete();
        $announcement->eventResponses()->delete();
        $announcement->reminderDispatches()->delete();
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return array<int, string>
     */
    private function normalizeStringArray(?array $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return array<int, int>
     */
    private function normalizeIntegerArray(?array $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(function (mixed $value): ?int {
                if (is_int($value)) {
                    return $value;
                }

                if (is_string($value) && is_numeric($value)) {
                    return (int) $value;
                }

                return null;
            })
            ->filter(fn (?int $value): bool => $value !== null && $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveRoleValue(User $user): string
    {
        if (is_string($user->role)) {
            return $user->role;
        }

        if (is_object($user->role) && property_exists($user->role, 'value')) {
            return (string) $user->role->value;
        }

        return (string) $user->role;
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return array<int, string>
     */
    private function normalizeDeliveryChannels(?array $values): array
    {
        $channels = collect($values ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim(strtolower($value)))
            ->filter(
                fn (string $value): bool => in_array($value, Announcement::allowedDeliveryChannels(), true)
            )
            ->unique()
            ->values();

        if (! $channels->contains(Announcement::DELIVERY_CHANNEL_IN_APP)) {
            $channels->prepend(Announcement::DELIVERY_CHANNEL_IN_APP);
        }

        return $channels->all();
    }
}
