<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreAnnouncementRequest;
use App\Http\Requests\SuperAdmin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use App\Services\AnnouncementAnalyticsService;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementAnalyticsService $announcementAnalyticsService) {}

    public function index(Request $request): Response
    {
        $user = $this->resolveRequestUser($request);
        $search = $request->input('search');
        $role = $request->input('role');

        $announcementsQuery = Announcement::query()
            ->with([
                'user:id,name',
                'attachments:id,announcement_id,original_name,mime_type,file_size',
            ]);

        if (! $this->isSuperAdmin($user)) {
            $announcementsQuery->where('user_id', $user->id);
        }

        $announcements = $announcementsQuery
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($role && $role !== 'all', function ($query) use ($role) {
                $query->where(function ($roleQuery) use ($role) {
                    $roleQuery->whereNull('target_roles')
                        ->orWhere('target_roles', json_encode([]))
                        ->orWhereJsonContains('target_roles', $role);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $analyticsByAnnouncementId = $this->announcementAnalyticsService->buildSummaries(
            $announcements->getCollection()
        );
        $announcements->setCollection(
            $announcements->getCollection()
                ->map(function (Announcement $announcement) use ($analyticsByAnnouncementId): array {
                    $analytics = $analyticsByAnnouncementId[(int) $announcement->id] ?? [
                        'recipient_count' => 0,
                        'read_count' => 0,
                        'unread_count' => 0,
                        'read_rate' => 0.0,
                    ];

                    return [
                        'id' => (int) $announcement->id,
                        'title' => (string) $announcement->title,
                        'content' => (string) $announcement->content,
                        'target_roles' => $announcement->target_roles,
                        'is_active' => (bool) $announcement->is_active,
                        'created_at' => $announcement->created_at?->toIso8601String(),
                        'publish_at' => $announcement->publish_at?->toIso8601String(),
                        'expires_at' => $announcement->expires_at?->toIso8601String(),
                        'user' => [
                            'name' => (string) ($announcement->user?->name ?? ''),
                        ],
                        'attachments' => $announcement->attachments
                            ->map(fn (AnnouncementAttachment $attachment): array => [
                                'id' => (int) $attachment->id,
                                'original_name' => (string) $attachment->original_name,
                                'mime_type' => $attachment->mime_type,
                                'file_size' => (int) $attachment->file_size,
                            ])
                            ->values()
                            ->all(),
                        'analytics' => $analytics,
                        'report_url' => route('announcements.report', [
                            'announcement' => $announcement->id,
                        ]),
                    ];
                })
                ->values()
        );

        $roles = collect(UserRole::cases())
            ->when(
                ! $this->isSuperAdmin($user),
                fn ($roleCollection) => $roleCollection->reject(
                    fn (UserRole $role) => $role === UserRole::SUPER_ADMIN
                )
            )
            ->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]);

        $announcementData = collect($announcements->items());

        return Inertia::render('super_admin/announcements/index', [
            'announcements' => $announcements,
            'roles' => $roles->values()->all(),
            'filters' => $request->only(['search', 'role']),
            'summary' => [
                'visible_announcements' => (int) $announcements->total(),
                'scheduled_announcements' => (int) $announcementData
                    ->filter(function (array $announcement): bool {
                        $publishAt = $announcement['publish_at'] ?? null;

                        if (! is_string($publishAt)) {
                            return false;
                        }

                        return now()->lt($publishAt);
                    })
                    ->count(),
                'recipients' => (int) $announcementData
                    ->sum(fn (array $announcement): int => (int) ($announcement['analytics']['recipient_count'] ?? 0)),
                'unread' => (int) $announcementData
                    ->sum(fn (array $announcement): int => (int) ($announcement['analytics']['unread_count'] ?? 0)),
            ],
        ]);
    }

    public function showReport(Request $request, Announcement $announcement): Response
    {
        $user = $this->resolveRequestUser($request);
        if (! $this->canManageAnnouncement($user, $announcement)) {
            abort(403);
        }

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');
        if (! in_array($status, ['all', 'read', 'unread'], true)) {
            $status = 'all';
        }

        $recipients = $this->announcementAnalyticsService
            ->reportQuery($announcement)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'read', function (Builder $query): void {
                $query->whereNotNull('announcement_reads.read_at');
            })
            ->when($status === 'unread', function (Builder $query): void {
                $query->whereNull('announcement_reads.read_at');
            })
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString()
            ->through(function (User $recipient): array {
                $roleValue = $this->resolveRoleValue($recipient);
                $role = UserRole::tryFrom($roleValue);

                return [
                    'id' => (int) $recipient->id,
                    'name' => (string) $recipient->name,
                    'email' => (string) $recipient->email,
                    'role' => $roleValue,
                    'role_label' => $role?->label() ?? ucfirst(str_replace('_', ' ', $roleValue)),
                    'is_read' => (bool) $recipient->getAttribute('is_read'),
                    'read_at' => $recipient->getAttribute('announcement_read_at'),
                ];
            });

        return Inertia::render('super_admin/announcements/report', [
            'announcement' => [
                'id' => (int) $announcement->id,
                'title' => (string) $announcement->title,
                'publish_at' => $announcement->publish_at?->toIso8601String(),
                'expires_at' => $announcement->expires_at?->toIso8601String(),
            ],
            'analytics' => $this->announcementAnalyticsService->buildSummary($announcement) + [
                'role_breakdown' => $this->announcementAnalyticsService->buildRoleBreakdown($announcement),
            ],
            'recipients' => $recipients,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'status' => $status,
            ],
        ]);
    }

    public function store(StoreAnnouncementRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $this->resolveRequestUser($request);
        $validated = $request->validated();
        $uploadedAttachments = $request->file('attachments', []);

        $validated['target_roles'] = $this->normalizeTargetRoles($validated['target_roles'] ?? null);
        unset($validated['attachments']);

        $announcement = DB::transaction(function () use ($user, $validated, $uploadedAttachments): Announcement {
            $announcement = $user->announcements()->create($validated);

            $this->storeUploadedAttachments($announcement, $uploadedAttachments);

            return $announcement;
        });

        $auditLogService->log('announcement.created', $announcement, null, $announcement->only([
            'id',
            'title',
            'target_roles',
            'publish_at',
            'expires_at',
            'is_active',
        ]) + [
            'attachment_count' => $announcement->attachments()->count(),
        ]);

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $this->resolveRequestUser($request);
        if (! $this->canManageAnnouncement($user, $announcement)) {
            abort(403);
        }

        $validated = $request->validated();
        $uploadedAttachments = $request->file('attachments', []);
        $removedAttachmentIds = $validated['removed_attachment_ids'] ?? [];

        $validated['target_roles'] = $this->normalizeTargetRoles($validated['target_roles'] ?? null);
        unset($validated['attachments'], $validated['removed_attachment_ids']);

        $oldValues = $announcement->only([
            'id',
            'title',
            'content',
            'target_roles',
            'publish_at',
            'expires_at',
            'is_active',
        ]) + [
            'attachment_count' => $announcement->attachments()->count(),
        ];

        DB::transaction(function () use ($announcement, $validated, $removedAttachmentIds, $uploadedAttachments): void {
            $announcement->update($validated);
            $this->removeAttachments($announcement, $removedAttachmentIds);
            $this->storeUploadedAttachments($announcement, $uploadedAttachments);
        });

        $announcement->refresh();

        $auditLogService->log('announcement.updated', $announcement, $oldValues, $announcement->only([
            'id',
            'title',
            'content',
            'target_roles',
            'publish_at',
            'expires_at',
            'is_active',
        ]) + [
            'attachment_count' => $announcement->attachments()->count(),
        ]);

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Request $request, Announcement $announcement, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $this->resolveRequestUser($request);
        if (! $this->canManageAnnouncement($user, $announcement)) {
            abort(403);
        }

        $oldValues = $announcement->only([
            'id',
            'title',
            'content',
            'target_roles',
            'publish_at',
            'expires_at',
            'is_active',
        ]) + [
            'attachment_count' => $announcement->attachments()->count(),
        ];

        $announcement->loadMissing('attachments');
        $this->deleteAttachmentFiles($announcement->attachments);

        $announcement->delete();

        $auditLogService->log('announcement.deleted', $announcement, $oldValues, null);

        return back()->with('success', 'Announcement removed successfully.');
    }

    private function normalizeTargetRoles(?array $targetRoles): ?array
    {
        if (! $targetRoles || count($targetRoles) === 0) {
            return null;
        }

        return array_values(array_unique($targetRoles));
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeUploadedAttachments(Announcement $announcement, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $storedPath = $file->store("announcements/{$announcement->id}", 'local');

            $announcement->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize() ?? 0,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $removedAttachmentIds
     */
    private function removeAttachments(Announcement $announcement, array $removedAttachmentIds): void
    {
        if ($removedAttachmentIds === []) {
            return;
        }

        $attachments = $announcement->attachments()
            ->whereIn('id', $removedAttachmentIds)
            ->get();

        $this->deleteAttachmentFiles($attachments);
        $announcement->attachments()
            ->whereIn('id', $removedAttachmentIds)
            ->delete();
    }

    /**
     * @param  Collection<int, AnnouncementAttachment>  $attachments
     */
    private function deleteAttachmentFiles(Collection $attachments): void
    {
        foreach ($attachments as $attachment) {
            if ($attachment->stored_path) {
                Storage::disk('local')->delete($attachment->stored_path);
            }
        }
    }

    private function canManageAnnouncement(User $user, Announcement $announcement): bool
    {
        return $this->isSuperAdmin($user) || $announcement->user_id === $user->id;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $this->resolveRoleValue($user) === UserRole::SUPER_ADMIN->value;
    }

    private function resolveRequestUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function resolveRoleValue(User $user): string
    {
        if (is_string($user->role)) {
            return $user->role;
        }

        if ($user->role instanceof UserRole) {
            return $user->role->value;
        }

        if (is_object($user->role) && property_exists($user->role, 'value')) {
            return (string) $user->role->value;
        }

        return (string) $user->role;
    }
}
