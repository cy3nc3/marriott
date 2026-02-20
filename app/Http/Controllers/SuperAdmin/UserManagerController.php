<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role && $role !== 'all', function ($query) use ($role) {
                $query->where('role', $role);
            }, function ($query) {
                $query->orderByRaw("CASE 
                    WHEN role = 'super_admin' THEN 1
                    WHEN role = 'admin' THEN 2
                    WHEN role = 'registrar' THEN 3
                    WHEN role = 'finance' THEN 4
                    WHEN role = 'teacher' THEN 5
                    WHEN role = 'student' THEN 6
                    WHEN role = 'parent' THEN 7
                    ELSE 8
                END");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('super_admin/user-manager/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'role' => ['required', 'string', Rule::in($this->roleValues())],
        ]);

        $firstName = strtolower(explode(' ', trim($validated['first_name']))[0]);
        $firstName = preg_replace('/[^a-z0-9]/', '', $firstName);

        $lastName = strtolower(str_replace(' ', '', trim($validated['last_name'])));
        $lastName = preg_replace('/[^a-z0-9]/', '', $lastName);

        $email = "{$firstName}.{$lastName}@marriott.edu";

        // Handle duplicate emails
        $originalEmail = $email;
        $count = 1;
        while (User::where('email', $email)->exists()) {
            $email = Str::before($originalEmail, '@').$count.'@marriott.edu';
            $count++;
        }

        $password = date('Ymd', strtotime($validated['birthday']));

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $email,
            'birthday' => $validated['birthday'],
            'role' => $validated['role'],
            'password' => Hash::make($password),
        ]);

        $auditLogService->log('user.created', $user, null, $user->only([
            'id',
            'name',
            'email',
            'role',
            'is_active',
        ]));

        return back()->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'role' => ['required', 'string', Rule::in($this->roleValues())],
        ]);

        $oldValues = $user->only([
            'id',
            'first_name',
            'last_name',
            'name',
            'birthday',
            'role',
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'birthday' => $validated['birthday'],
            'role' => $validated['role'],
        ]);

        $auditLogService->log('user.updated', $user, $oldValues, $user->only([
            'id',
            'first_name',
            'last_name',
            'name',
            'birthday',
            'role',
        ]));

        return back()->with('success', 'User account updated successfully.');
    }

    public function resetPassword(User $user, AuditLogService $auditLogService): RedirectResponse
    {
        if (! $user->birthday) {
            return back()->with('error', 'User birthday is not set. Cannot auto-generate password.');
        }

        $password = date('Ymd', strtotime($user->birthday));
        $user->update([
            'password' => Hash::make($password),
        ]);

        $auditLogService->log('user.password_reset', $user, null, [
            'reset_method' => 'birthday_default',
            'birthday' => date('Y-m-d', strtotime((string) $user->birthday)),
        ]);

        return back()->with('success', 'Password reset to default (birthday) successfully.');
    }

    public function toggleStatus(User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $oldStatus = $user->is_active;

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $auditLogService->log('user.status_toggled', $user, [
            'is_active' => $oldStatus,
        ], [
            'is_active' => $user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User account {$status} successfully.");
    }

    private function roleValues(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role) => $role->value)
            ->all();
    }
}
