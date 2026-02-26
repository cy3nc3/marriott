<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AnnouncementNotificationService
{
    /**
     * @return array{
     *     announcements: array<int, array{
     *         id: int,
     *         title: string,
     *         content_preview: string,
     *         created_at: string|null,
     *         publish_at: string|null,
     *         expires_at: string|null,
     *         type: string,
     *         response_mode: string,
     *         event_starts_at: string|null,
     *         event_ends_at: string|null,
     *         response_deadline_at: string|null,
     *         is_cancelled: bool,
     *         cancelled_at: string|null,
     *         cancel_reason: string|null,
     *         viewer_response_status: string,
     *         requires_action: bool,
     *         is_read: bool
     *     }>,
     *     unread_count: int
     * }
     */
    public function buildPayload(User $user, int $limit = 8): array
    {
        $visibleAnnouncementsQuery = $this->visibleAnnouncementsForUserQuery($user);

        $announcements = (clone $visibleAnnouncementsQuery)
            ->with([
                'eventResponses' => function ($query) use ($user): void {
                    $query
                        ->where('user_id', $user->id)
                        ->select(['id', 'announcement_id', 'user_id', 'response', 'responded_at']);
                },
            ])
            ->latest()
            ->limit($limit)
            ->get([
                'id',
                'title',
                'content',
                'target_roles',
                'target_user_ids',
                'type',
                'response_mode',
                'publish_at',
                'event_starts_at',
                'event_ends_at',
                'response_deadline_at',
                'cancelled_at',
                'cancel_reason',
                'expires_at',
                'created_at',
            ]);

        if ($announcements->isEmpty()) {
            return [
                'announcements' => [],
                'unread_count' => 0,
            ];
        }

        $readByAnnouncementId = AnnouncementRead::query()
            ->where('user_id', $user->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('read_at', 'announcement_id');

        $serializedAnnouncements = $announcements
            ->map(function (Announcement $announcement) use ($readByAnnouncementId, $user): array {
                $viewerResponseStatus = $this->resolveViewerResponseStatus($announcement, $user);

                return [
                    'id' => (int) $announcement->id,
                    'title' => (string) $announcement->title,
                    'content_preview' => Str::limit(trim((string) $announcement->content), 120),
                    'created_at' => $announcement->created_at?->toIso8601String(),
                    'publish_at' => $announcement->publish_at?->toIso8601String(),
                    'expires_at' => $announcement->expires_at?->toIso8601String(),
                    'type' => (string) ($announcement->type ?? Announcement::TYPE_NOTICE),
                    'response_mode' => (string) ($announcement->response_mode ?? Announcement::RESPONSE_MODE_NONE),
                    'event_starts_at' => $announcement->event_starts_at?->toIso8601String(),
                    'event_ends_at' => $announcement->event_ends_at?->toIso8601String(),
                    'response_deadline_at' => $announcement->response_deadline_at?->toIso8601String(),
                    'is_cancelled' => $announcement->cancelled_at !== null,
                    'cancelled_at' => $announcement->cancelled_at?->toIso8601String(),
                    'cancel_reason' => $announcement->cancel_reason,
                    'viewer_response_status' => $viewerResponseStatus,
                    'requires_action' => $this->requiresAction($user, $announcement, $viewerResponseStatus),
                    'is_read' => $readByAnnouncementId->has((int) $announcement->id),
                ];
            })
            ->values();

        $unreadCount = (clone $visibleAnnouncementsQuery)
            ->whereDoesntHave('reads', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->count();

        return [
            'announcements' => $serializedAnnouncements->all(),
            'unread_count' => $unreadCount,
        ];
    }

    public function isVisibleToUser(User $user, Announcement $announcement): bool
    {
        return $this->visibleAnnouncementsForUserQuery($user)
            ->whereKey($announcement->id)
            ->exists();
    }

    public function resolveViewerResponseStatus(Announcement $announcement, User $user): string
    {
        if (! $announcement->isEventType()) {
            return 'none';
        }

        $response = $announcement->relationLoaded('eventResponses')
            ? $announcement->eventResponses->first()
            : $announcement->eventResponses()
                ->where('user_id', $user->id)
                ->first();

        return is_string($response?->response) ? $response->response : 'none';
    }

    public function requiresAction(
        User $user,
        Announcement $announcement,
        ?string $viewerResponseStatus = null
    ): bool {
        if (! $announcement->isEventType()) {
            return false;
        }

        if ($announcement->response_mode !== Announcement::RESPONSE_MODE_ACK_RSVP) {
            return false;
        }

        if ($announcement->cancelled_at !== null) {
            return false;
        }

        if (! $announcement->relationLoaded('recipients')) {
            $isRecipient = $announcement->recipients()
                ->where('user_id', $user->id)
                ->exists();

            if (! $isRecipient) {
                return false;
            }
        }

        $status = $viewerResponseStatus ?? $this->resolveViewerResponseStatus($announcement, $user);

        return $status === 'none';
    }

    public function markAsRead(User $user, Announcement $announcement): void
    {
        AnnouncementRead::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    public function markAllAsRead(User $user): void
    {
        $announcementIds = $this->visibleAnnouncementsForUserQuery($user)
            ->pluck('id');

        if ($announcementIds->isEmpty()) {
            return;
        }

        $timestamp = now();
        $payload = $announcementIds
            ->map(function (int $announcementId) use ($timestamp, $user): array {
                return [
                    'announcement_id' => $announcementId,
                    'user_id' => $user->id,
                    'read_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->values()
            ->all();

        AnnouncementRead::query()->upsert(
            $payload,
            ['announcement_id', 'user_id'],
            ['read_at', 'updated_at']
        );
    }

    public function visibleAnnouncementsForUserQuery(User $user): Builder
    {
        $userRole = $this->resolveUserRole($user);
        $userId = (int) $user->id;

        return Announcement::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function (Builder $query) use ($userRole, $userId): void {
                $query
                    ->where(function (Builder $noticeQuery) use ($userRole, $userId): void {
                        $noticeQuery
                            ->where(function (Builder $typeQuery): void {
                                $typeQuery
                                    ->whereNull('type')
                                    ->orWhere('type', Announcement::TYPE_NOTICE);
                            })
                            ->where(function (Builder $roleQuery) use ($userRole): void {
                                $roleQuery
                                    ->whereNull('target_roles')
                                    ->orWhereJsonLength('target_roles', 0)
                                    ->orWhereJsonContains('target_roles', $userRole);
                            })
                            ->where(function (Builder $userQuery) use ($userId): void {
                                $userQuery
                                    ->whereNull('target_user_ids')
                                    ->orWhereJsonLength('target_user_ids', 0)
                                    ->orWhereJsonContains('target_user_ids', $userId);
                            });
                    })
                    ->orWhere(function (Builder $eventQuery) use ($userId): void {
                        $eventQuery
                            ->where('type', Announcement::TYPE_EVENT)
                            ->whereHas('recipients', function (Builder $recipientQuery) use ($userId): void {
                                $recipientQuery->where('user_id', $userId);
                            });
                    });
            });
    }

    private function resolveUserRole(User $user): string
    {
        $role = $user->role;
        if (is_string($role)) {
            return $role;
        }

        if (is_object($role) && property_exists($role, 'value')) {
            return (string) $role->value;
        }

        return (string) $role;
    }
}
