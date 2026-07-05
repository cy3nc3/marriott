<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateViewAsRoleRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViewAsRoleController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_VIEW_AS_ROLES = [
        UserRole::ADMIN->value,
        UserRole::REGISTRAR->value,
        UserRole::FINANCE->value,
        UserRole::TEACHER->value,
        UserRole::STUDENT->value,
        UserRole::PARENT->value,
    ];

    public function update(UpdateViewAsRoleRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validated();

        $role = (string) $validated['role'];
        $request->session()->put('view_as_role', $role);

        $auditLogService->log(
            'super_admin.view_as.enabled',
            'App\\Models\\SystemEvent',
            null,
            ['role' => $role]
        );

        return back()->with('success', "Viewing as {$role}.");
    }

    public function destroy(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $previousRole = (string) ($request->session()->get('view_as_role') ?? '');
        $request->session()->forget('view_as_role');

        $auditLogService->log(
            'super_admin.view_as.disabled',
            'App\\Models\\SystemEvent',
            ['role' => $previousRole !== '' ? $previousRole : null],
            null
        );

        return back()->with('success', 'Returned to Super Admin workspace.');
    }
}
