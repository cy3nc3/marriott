<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $priority = $request->input('priority');
        $role = $request->input('role');

        $announcements = Announcement::query()
            ->with('user:id,name')
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($priority && $priority !== 'all', function ($query) use ($priority) {
                $query->where('priority', $priority);
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

        $roles = collect(UserRole::cases())->map(fn (UserRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ]);

        return Inertia::render('super_admin/announcements/index', [
            'announcements' => $announcements,
            'roles' => $roles,
            'filters' => $request->only(['search', 'priority', 'role']),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|string|in:low,normal,high,critical',
            'target_roles' => 'nullable|array',
            'target_roles.*' => ['string', Rule::in($this->roleValues())],
            'expires_at' => 'nullable|date',
        ]);

        $validated['target_roles'] = $this->normalizeTargetRoles($validated['target_roles'] ?? null);

        $announcement = $request->user()->announcements()->create($validated);

        $auditLogService->log('announcement.created', $announcement, null, $announcement->only([
            'id',
            'title',
            'priority',
            'target_roles',
            'expires_at',
            'is_active',
        ]));

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function update(Request $request, Announcement $announcement, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|string|in:low,normal,high,critical',
            'target_roles' => 'nullable|array',
            'target_roles.*' => ['string', Rule::in($this->roleValues())],
            'expires_at' => 'nullable|date',
        ]);

        $validated['target_roles'] = $this->normalizeTargetRoles($validated['target_roles'] ?? null);

        $oldValues = $announcement->only([
            'id',
            'title',
            'content',
            'priority',
            'target_roles',
            'expires_at',
            'is_active',
        ]);

        $announcement->update($validated);

        $auditLogService->log('announcement.updated', $announcement, $oldValues, $announcement->only([
            'id',
            'title',
            'content',
            'priority',
            'target_roles',
            'expires_at',
            'is_active',
        ]));

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement, AuditLogService $auditLogService): RedirectResponse
    {
        $oldValues = $announcement->only([
            'id',
            'title',
            'content',
            'priority',
            'target_roles',
            'expires_at',
            'is_active',
        ]);

        $announcement->delete();

        $auditLogService->log('announcement.deleted', $announcement, $oldValues, null);

        return back()->with('success', 'Announcement removed successfully.');
    }

    private function roleValues(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role) => $role->value)
            ->all();
    }

    private function normalizeTargetRoles(?array $targetRoles): ?array
    {
        if (! $targetRoles || count($targetRoles) === 0) {
            return null;
        }

        return array_values(array_unique($targetRoles));
    }
}
