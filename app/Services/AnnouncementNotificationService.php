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
     *         expires_at: string|null,
     *         is_read: bool
     *     }>,
     *     unread_count: int
     * }
     */
    public function buildPayload(User $user, int $limit = 8): array
    {
        $visibleAnnouncementsQuery = $this->visibleAnnouncementsForUserQuery($user);

        $announcements = (clone $visibleAnnouncementsQuery)
            ->latest()
            ->limit($limit)
            ->get([
                'id',
                'title',
                'content',
                'target_roles',
                'expires_at',
                'publish_at',
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
            ->map(function (Announcement $announcement) use ($readByAnnouncementId): array {
                return [
                    'id' => (int) $announcement->id,
                    'title' => (string) $announcement->title,
                    'content_preview' => Str::limit(trim((string) $announcement->content), 120),
                    'created_at' => $announcement->created_at?->toIso8601String(),
                    'expires_at' => $announcement->expires_at?->toIso8601String(),
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
        if (! $announcement->is_active) {
            return false;
        }

        if ($announcement->expires_at && $announcement->expires_at->isPast()) {
            return false;
        }

        if ($announcement->publish_at && $announcement->publish_at->isFuture()) {
            return false;
        }

        $targetRoles = $announcement->target_roles;
        if (! is_array($targetRoles) || $targetRoles === []) {
            return true;
        }

        return in_array($this->resolveUserRole($user), $targetRoles, true);
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
            ->where(function (Builder $query) use ($userRole): void {
                $query
                    ->whereNull('target_roles')
                    ->orWhereJsonLength('target_roles', 0)
                    ->orWhereJsonContains('target_roles', $userRole);
            })
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->whereNull('target_user_ids')
                    ->orWhereJsonLength('target_user_ids', 0)
                    ->orWhereJsonContains('target_user_ids', $userId);
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
