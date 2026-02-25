<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AnnouncementEventResponse;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AnnouncementResponseService
{
    public function __construct(private AnnouncementNotificationService $announcementNotificationService) {}

    public function acknowledge(User $user, Announcement $announcement): AnnouncementEventResponse
    {
        return $this->storeResponse($user, $announcement, 'ack_only', null);
    }

    public function respond(
        User $user,
        Announcement $announcement,
        string $response,
        ?string $note = null
    ): AnnouncementEventResponse {
        if (! in_array($response, ['yes', 'no', 'maybe'], true)) {
            throw new AuthorizationException('Invalid response option.');
        }

        return $this->storeResponse($user, $announcement, $response, $note);
    }

    private function storeResponse(
        User $user,
        Announcement $announcement,
        string $response,
        ?string $note
    ): AnnouncementEventResponse {
        $this->ensureCanRespond($user, $announcement);

        $savedResponse = AnnouncementEventResponse::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            [
                'response' => $response,
                'responded_at' => now(),
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]
        );

        $this->announcementNotificationService->markAsRead($user, $announcement);

        return $savedResponse;
    }

    private function ensureCanRespond(User $user, Announcement $announcement): void
    {
        if (! $announcement->isEventType()) {
            throw new AuthorizationException('Responses are only available for event announcements.');
        }

        if ($announcement->response_mode !== Announcement::RESPONSE_MODE_ACK_RSVP) {
            throw new AuthorizationException('Responses are not enabled for this event.');
        }

        if ($announcement->cancelled_at !== null) {
            throw new AuthorizationException('This event was cancelled and can no longer accept responses.');
        }

        if ($this->resolveRoleValue($user) === UserRole::STUDENT->value) {
            throw new AuthorizationException('Student accounts cannot submit event responses in this version.');
        }

        $isRecipient = $announcement->recipients()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isRecipient) {
            throw new AuthorizationException('You are not part of the recipient list for this event.');
        }
    }

    private function resolveRoleValue(User $user): string
    {
        if ($user->role instanceof UserRole) {
            return $user->role->value;
        }

        if (is_object($user->role) && property_exists($user->role, 'value')) {
            return (string) $user->role->value;
        }

        return (string) $user->role;
    }
}
