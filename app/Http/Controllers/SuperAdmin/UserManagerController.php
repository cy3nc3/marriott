<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardCacheService;
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
                $normalizedSearch = Str::lower(trim((string) $search));
                $searchPattern = "%{$normalizedSearch}%";

                $query->where(function ($searchQuery) use ($searchPattern) {
                    $searchQuery->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern]);
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

    public function store(Request $request): RedirectResponse
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

        $password = $this->buildDefaultPassword(
            (string) $validated['first_name'],
            (string) $validated['birthday']
        );

        User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $email,
            'birthday' => $validated['birthday'],
            'role' => $validated['role'],
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        DashboardCacheService::bust();

        return back()->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'role' => ['required', 'string', Rule::in($this->roleValues())],
        ]);

        if (
            $user->is_active
            && $user->role === UserRole::SUPER_ADMIN
            && $validated['role'] !== UserRole::SUPER_ADMIN->value
            && $this->activeSuperAdminCount() <= 1
        ) {
            return back()->with('error', 'At least one active super admin account must remain active.');
        }

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'birthday' => $validated['birthday'],
            'role' => $validated['role'],
        ]);

        DashboardCacheService::bust();

        return back()->with('success', 'User account updated successfully.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        if (! $user->birthday) {
            return back()->with('error', 'User birthday is not set. Cannot auto-generate password.');
        }

        $password = $this->buildDefaultPassword(
            (string) ($user->first_name ?: $user->name),
            (string) $user->birthday
        );
        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
            'password_updated_at' => now(),
        ]);

        DashboardCacheService::bust();

        return back()->with('success', 'Password reset to default successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if (
            $user->is_active
            && $user->role === UserRole::SUPER_ADMIN
            && $this->activeSuperAdminCount() <= 1
        ) {
            return back()->with('error', 'At least one active super admin account must remain active.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        DashboardCacheService::bust();

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User account {$status} successfully.");
    }

    private function roleValues(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role) => $role->value)
            ->all();
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()
            ->where('role', UserRole::SUPER_ADMIN->value)
            ->where('is_active', true)
            ->count();
    }

    private function buildDefaultPassword(string $rawFirstName, string $birthday): string
    {
        $firstToken = trim(explode(' ', trim($rawFirstName))[0] ?? '');
        $normalizedToken = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $firstToken));

        if ($normalizedToken === '') {
            $normalizedToken = 'user';
        }

        $birthdaySegment = date('mdY', strtotime($birthday));

        return "{$normalizedToken}@{$birthdaySegment}";
    }
}
