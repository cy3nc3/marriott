<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcements\CancelAnnouncementRequest;
use App\Http\Requests\SuperAdmin\StoreAnnouncementRequest;
use App\Http\Requests\SuperAdmin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use App\Services\AnnouncementAnalyticsService;
use App\Services\AnnouncementAudienceResolver;
use App\Services\AnnouncementEventService;
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
    public function __construct(
        private AnnouncementAnalyticsService $announcementAnalyticsService,
        private AnnouncementEventService $announcementEventService,
        private AnnouncementAudienceResolver $announcementAudienceResolver,
    ) {}

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
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($role && $role !== 'all', function (Builder $query) use ($role): void {
                $query->where(function (Builder $roleQuery) use ($role): void {
                    $roleQuery->whereNull('target_roles')
                        ->orWhereJsonLength('target_roles', 0)
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
                        'type' => (string) ($announcement->type ?? Announcement::TYPE_NOTICE),
                        'response_mode' => (string) ($announcement->response_mode ?? Announcement::RESPONSE_MODE_NONE),
                        'target_roles' => $announcement->target_roles,
                        'target_user_ids' => $announcement->target_user_ids,
                        'is_active' => (bool) $announcement->is_active,
                        'created_at' => $announcement->created_at?->toIso8601String(),
                        'publish_at' => $announcement->publish_at?->toIso8601String(),
                        'event_starts_at' => $announcement->event_starts_at?->toIso8601String(),
                        'event_ends_at' => $announcement->event_ends_at?->toIso8601String(),
                        'response_deadline_at' => $announcement->response_deadline_at?->toIso8601String(),
                        'expires_at' => $announcement->expires_at?->toIso8601String(),
                        'is_cancelled' => $announcement->cancelled_at !== null,
                        'cancelled_at' => $announcement->cancelled_at?->toIso8601String(),
                        'cancel_reason' => $announcement->cancel_reason,
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
                        'can_cancel' => $announcement->isEventType() && $announcement->cancelled_at === null,
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
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ]);

        $announcementData = collect($announcements->items());

        return Inertia::render('super_admin/announcements/index', [
            'announcements' => $announcements,
            'roles' => $roles->values()->all(),
            'audience' => $this->announcementAudienceResolver->resolveAudienceOptions(
                $user
            ),
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
        if (! $this->canViewAnnouncementReport($user, $announcement)) {
            abort(403);
        }

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');

        $allowedStatuses = [
            'all',
            'read',
            'unread',
            'pending',
            'acknowledged',
            'yes',
            'no',
            'maybe',
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $isEvent = $announcement->isEventType();

        $recipients = $this->announcementAnalyticsService
            ->reportQuery($announcement)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            });

        $this->applyReportStatusFilter($recipients, $status, $isEvent);

        $recipients = $recipients
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString()
            ->through(function (User $recipient): array {
                $roleValue = $this->resolveRoleValue($recipient);
                $role = UserRole::tryFrom($roleValue);
                $responseStatus = (string) ($recipient->getAttribute('response_status') ?? 'none');

                return [
                    'id' => (int) $recipient->id,
                    'name' => (string) $recipient->name,
                    'email' => (string) $recipient->email,
                    'role' => $roleValue,
                    'role_label' => $role?->label() ?? ucfirst(str_replace('_', ' ', $roleValue)),
                    'is_read' => (bool) $recipient->getAttribute('is_read'),
                    'read_at' => $recipient->getAttribute('announcement_read_at'),
                    'response_status' => $responseStatus,
                    'responded_at' => $recipient->getAttribute('response_responded_at'),
                ];
            });

        return Inertia::render('super_admin/announcements/report', [
            'announcement' => [
                'id' => (int) $announcement->id,
                'title' => (string) $announcement->title,
                'type' => (string) ($announcement->type ?? Announcement::TYPE_NOTICE),
                'response_mode' => (string) ($announcement->response_mode ?? Announcement::RESPONSE_MODE_NONE),
                'publish_at' => $announcement->publish_at?->toIso8601String(),
                'event_starts_at' => $announcement->event_starts_at?->toIso8601String(),
                'event_ends_at' => $announcement->event_ends_at?->toIso8601String(),
                'response_deadline_at' => $announcement->response_deadline_at?->toIso8601String(),
                'expires_at' => $announcement->expires_at?->toIso8601String(),
                'is_cancelled' => $announcement->cancelled_at !== null,
                'cancelled_at' => $announcement->cancelled_at?->toIso8601String(),
            ],
            'analytics' => $this->announcementAnalyticsService->buildSummary($announcement) + [
                'role_breakdown' => $this->announcementAnalyticsService->buildRoleBreakdown($announcement),
                'response_summary' => $this->announcementAnalyticsService->buildResponseSummary($announcement),
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

        $payload = $this->announcementEventService->normalizeAnnouncementPayload($validated);
        unset($payload['attachments']);

        $announcement = DB::transaction(function () use (
            $user,
            $payload,
            $uploadedAttachments
        ): Announcement {
            $announcement = $user->announcements()->create($payload);

            $this->announcementEventService->syncRecipients(
                $announcement,
                $user
            );
            $this->storeUploadedAttachments($announcement, $uploadedAttachments);

            return $announcement;
        });

        $announcement->loadCount('recipients');

        $auditLogService->log('announcement.created', $announcement, null, $announcement->only([
            'id',
            'title',
            'type',
            'response_mode',
            'target_roles',
            'target_user_ids',
            'publish_at',
            'event_starts_at',
            'event_ends_at',
            'response_deadline_at',
            'expires_at',
            'is_active',
        ]) + [
            'recipient_count' => (int) $announcement->recipients_count,
            'attachment_count' => $announcement->attachments()->count(),
        ]);

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function update(
        UpdateAnnouncementRequest $request,
        Announcement $announcement,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $user = $this->resolveRequestUser($request);
        if (! $this->canManageAnnouncement($user, $announcement)) {
            abort(403);
        }

        $validated = $request->validated();
        $uploadedAttachments = $request->file('attachments', []);
        $removedAttachmentIds = $validated['removed_attachment_ids'] ?? [];

        $payload = $this->announcementEventService->normalizeAnnouncementPayload($validated);
        unset(
            $payload['attachments'],
            $payload['removed_attachment_ids']
        );

        $oldValues = $announcement->only([
            'id',
            'title',
            'content',
            'type',
            'response_mode',
            'target_roles',
            'target_user_ids',
            'publish_at',
            'event_starts_at',
            'event_ends_at',
            'response_deadline_at',
            'cancelled_at',
            'cancel_reason',
            'expires_at',
            'is_active',
        ]) + [
            'attachment_count' => $announcement->attachments()->count(),
            'recipient_count' => $announcement->recipients()->count(),
        ];

        DB::transaction(function () use (
            $announcement,
            $payload,
            $removedAttachmentIds,
            $uploadedAttachments,
            $user
        ): void {
            $announcement->update($payload);
            $this->announcementEventService->syncRecipients(
                $announcement,
                $user,
                null,
                true
            );
            $this->removeAttachments($announcement, $removedAttachmentIds);
            $this->storeUploadedAttachments($announcement, $uploadedAttachments);
        });

        $announcement->refresh();

        $auditLogService->log('announcement.updated', $announcement, $oldValues, $announcement->only([
            'id',
            'title',
            'content',
            'type',
            'response_mode',
            'target_roles',
            'target_user_ids',
            'publish_at',
            'event_starts_at',
            'event_ends_at',
            'response_deadline_at',
            'cancelled_at',
            'cancel_reason',
            'expires_at',
            'is_active',
        ]) + [
            'attachment_count' => $announcement->attachments()->count(),
            'recipient_count' => $announcement->recipients()->count(),
        ]);

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function cancel(
        CancelAnnouncementRequest $request,
        Announcement $announcement,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $user = $this->resolveRequestUser($request);
        if (! $this->canManageAnnouncement($user, $announcement)) {
            abort(403);
        }

        if (! $announcement->isEventType()) {
            return back()->with('error', 'Only event announcements can be cancelled.');
        }

        $oldValues = $announcement->only([
            'id',
            'title',
            'cancelled_at',
            'cancel_reason',
        ]);

        $this->announcementEventService->cancelEvent(
            $announcement,
            $user,
            $request->validated('cancel_reason')
        );

        $announcement->refresh();

        $auditLogService->log('announcement.cancelled', $announcement, $oldValues, $announcement->only([
            'id',
            'title',
            'cancelled_at',
            'cancel_reason',
        ]));

        return back()->with('success', 'Event announcement was cancelled.');
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

    private function applyReportStatusFilter(Builder $query, string $status, bool $isEvent): void
    {
        if ($status === 'all') {
            return;
        }

        if ($status === 'read') {
            $query->whereNotNull('announcement_reads.read_at');

            return;
        }

        if ($status === 'unread') {
            $query->whereNull('announcement_reads.read_at');

            return;
        }

        if (! $isEvent) {
            return;
        }

        if ($status === 'acknowledged') {
            $query->whereNotNull('announcement_event_responses.responded_at');

            return;
        }

        if ($status === 'pending') {
            $query
                ->whereNull('announcement_event_responses.responded_at')
                ->where('announcement_recipients.role', '!=', UserRole::STUDENT->value);

            return;
        }

        if (in_array($status, ['yes', 'no', 'maybe'], true)) {
            $query->where('announcement_event_responses.response', $status);
        }
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

    private function canViewAnnouncementReport(User $user, Announcement $announcement): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isAdmin($user)
            || $announcement->user_id === $user->id;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $this->resolveRoleValue($user) === UserRole::SUPER_ADMIN->value;
    }

    private function isAdmin(User $user): bool
    {
        return $this->resolveRoleValue($user) === UserRole::ADMIN->value;
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
