<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AnnouncementAnalyticsService
{
    /**
     * @param  Collection<int, Announcement>  $announcements
     * @return array<int, array{
     *     recipient_count: int,
     *     read_count: int,
     *     unread_count: int,
     *     read_rate: float
     * }>
     */
    public function buildSummaries(Collection $announcements): array
    {
        if ($announcements->isEmpty()) {
            return [];
        }

        return $announcements
            ->mapWithKeys(function (Announcement $announcement): array {
                return [
                    (int) $announcement->id => $this->buildSummary($announcement),
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *     recipient_count: int,
     *     read_count: int,
     *     unread_count: int,
     *     read_rate: float
     * }
     */
    public function buildSummary(Announcement $announcement): array
    {
        $recipientScopeQuery = $this->eligibleRecipientsQuery($announcement);

        $recipientCount = (clone $recipientScopeQuery)->count();
        $readCount = AnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->whereIn('user_id', (clone $recipientScopeQuery)->select('users.id'))
            ->count();
        $unreadCount = max($recipientCount - $readCount, 0);

        return [
            'recipient_count' => (int) $recipientCount,
            'read_count' => (int) $readCount,
            'unread_count' => (int) $unreadCount,
            'read_rate' => $recipientCount > 0
                ? round(($readCount / $recipientCount) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * @return array<int, array{
     *     role: string,
     *     label: string,
     *     recipient_count: int,
     *     read_count: int,
     *     unread_count: int,
     *     read_rate: float
     * }>
     */
    public function buildRoleBreakdown(Announcement $announcement): array
    {
        $recipientScopeQuery = $this->eligibleRecipientsQuery($announcement);

        $recipientCountsByRole = (clone $recipientScopeQuery)
            ->selectRaw('users.role as role, COUNT(*) as aggregate')
            ->groupBy('users.role')
            ->pluck('aggregate', 'role');

        $readCountsByRole = AnnouncementRead::query()
            ->join('users', 'users.id', '=', 'announcement_reads.user_id')
            ->where('announcement_reads.announcement_id', $announcement->id)
            ->whereIn('users.id', (clone $recipientScopeQuery)->select('users.id'))
            ->selectRaw('users.role as role, COUNT(*) as aggregate')
            ->groupBy('users.role')
            ->pluck('aggregate', 'role');

        $orderedRoleValues = collect(UserRole::cases())
            ->map(fn (UserRole $role): string => $role->value);
        $presentRoleValues = $orderedRoleValues
            ->filter(fn (string $roleValue): bool => (int) ($recipientCountsByRole[$roleValue] ?? 0) > 0);

        return $presentRoleValues
            ->map(function (string $roleValue) use ($recipientCountsByRole, $readCountsByRole): array {
                $recipientCount = (int) ($recipientCountsByRole[$roleValue] ?? 0);
                $readCount = min((int) ($readCountsByRole[$roleValue] ?? 0), $recipientCount);
                $unreadCount = max($recipientCount - $readCount, 0);

                return [
                    'role' => $roleValue,
                    'label' => $this->resolveRoleLabel($roleValue),
                    'recipient_count' => $recipientCount,
                    'read_count' => $readCount,
                    'unread_count' => $unreadCount,
                    'read_rate' => $recipientCount > 0
                        ? round(($readCount / $recipientCount) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    public function reportQuery(Announcement $announcement): Builder
    {
        return $this->eligibleRecipientsQuery($announcement)
            ->leftJoin('announcement_reads', function ($join) use ($announcement): void {
                $join->on('announcement_reads.user_id', '=', 'users.id')
                    ->where('announcement_reads.announcement_id', '=', $announcement->id);
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'announcement_reads.read_at as announcement_read_at',
            ])
            ->selectRaw('CASE WHEN announcement_reads.id IS NULL THEN 0 ELSE 1 END as is_read');
    }

    private function eligibleRecipientsQuery(Announcement $announcement): Builder
    {
        $roleValues = $this->normalizeRoleValues($announcement->target_roles);
        $targetUserIds = $this->normalizeTargetUserIds($announcement->target_user_ids);

        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->when($roleValues !== [], function (Builder $query) use ($roleValues): void {
                $query->whereIn('role', $roleValues);
            })
            ->when($targetUserIds !== [], function (Builder $query) use ($targetUserIds): void {
                $query->whereIn('id', $targetUserIds);
            });
    }

    /**
     * @return array<int, string>
     */
    private function normalizeRoleValues(mixed $targetRoles): array
    {
        if (! is_array($targetRoles) || $targetRoles === []) {
            return [];
        }

        $allowedValues = collect(UserRole::cases())
            ->map(fn (UserRole $role): string => $role->value);

        return collect($targetRoles)
            ->filter(fn (mixed $role): bool => is_string($role))
            ->map(fn (string $role): string => trim($role))
            ->filter(fn (string $role): bool => $allowedValues->contains($role))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function normalizeTargetUserIds(mixed $targetUserIds): array
    {
        if (! is_array($targetUserIds) || $targetUserIds === []) {
            return [];
        }

        return collect($targetUserIds)
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

    private function resolveRoleLabel(string $roleValue): string
    {
        $role = UserRole::tryFrom($roleValue);

        return $role?->label() ?? ucfirst(str_replace('_', ' ', $roleValue));
    }
}
