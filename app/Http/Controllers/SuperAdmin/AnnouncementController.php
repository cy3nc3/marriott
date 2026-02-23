<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreAnnouncementRequest;
use App\Http\Requests\SuperAdmin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use App\Services\AuditLogService;
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

        return Inertia::render('super_admin/announcements/index', [
            'announcements' => $announcements,
            'roles' => $roles->values()->all(),
            'filters' => $request->only(['search', 'role']),
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
