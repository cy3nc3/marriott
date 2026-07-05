<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        $allPermissions = Permission::all();
        $grouped = [];

        foreach ($allPermissions as $perm) {
            $grouped[$perm->module][$perm->feature][$perm->role] = $perm->access_level;
        }

        return Inertia::render('super_admin/permissions/index', [
            'permissions' => $grouped,
            'roles' => array_map(fn ($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ], UserRole::cases()),
        ]);
    }

    public function update(UpdatePermissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($request->matrix as $category => $features) {
            foreach ($features as $feature => $roleLevels) {
                foreach ($roleLevels as $role => $level) {
                    Permission::updateOrCreate(
                        ['role' => $role, 'feature' => $feature],
                        ['module' => $category, 'access_level' => $level]
                    );
                }
            }
        }

        return back()->with('success', 'Permissions updated successfully.');
    }
}
