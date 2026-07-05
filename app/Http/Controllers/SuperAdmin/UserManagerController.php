<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreUserRequest;
use App\Http\Requests\SuperAdmin\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\EnrollmentAccountClaimService;
use App\Services\DashboardCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserManagerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $sort = $request->input('sort', 'newest');
        $claimStatus = (string) $request->input('claim_status', 'all');
        $loginActivity = (string) $request->input('login_activity', 'all');
        $allowedSorts = ['newest', 'oldest', 'az', 'za'];
        $allowedClaimStatuses = ['all', 'claimed', 'unclaimed'];
        $allowedLoginActivities = ['all', 'never', 'stale_90', 'recent_30'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'newest';
        }
        if (! in_array($claimStatus, $allowedClaimStatuses, true)) {
            $claimStatus = 'all';
        }
        if (! in_array($loginActivity, $allowedLoginActivities, true)) {
            $loginActivity = 'all';
        }

        $users = User::query()
            ->when($search, function ($query, $search) {
                $normalizedSearch = Str::lower(trim((string) $search));
                $searchPattern = "%{$normalizedSearch}%";

                $query->where(function ($searchQuery) use ($searchPattern) {
                    $searchQuery->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(personal_email) LIKE ?', [$searchPattern]);
                });
            })
            ->when($role && $role !== 'all', function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($claimStatus !== 'all', function ($query) use ($claimStatus) {
                if ($claimStatus === 'claimed') {
                    $query->where('must_change_password', false);

                    return;
                }

                $query->where('must_change_password', true);
            })
            ->when($loginActivity !== 'all', function ($query) use ($loginActivity) {
                if ($loginActivity === 'never') {
                    $query->whereNull('last_login_at');

                    return;
                }

                if ($loginActivity === 'stale_90') {
                    $query->where(function ($activityQuery) {
                        $activityQuery
                            ->whereNull('last_login_at')
                            ->orWhere('last_login_at', '<', now()->subDays(90));
                    });

                    return;
                }

                $query->whereNotNull('last_login_at')
                    ->where('last_login_at', '>=', now()->subDays(30));
            })
            ->when($sort === 'oldest', function ($query) {
                $query
                    ->orderBy('created_at')
                    ->orderBy('id');
            }, function ($query) use ($sort) {
                if ($sort === 'az') {
                    $query
                        ->orderBy('name')
                        ->orderBy('id');

                    return;
                }

                if ($sort === 'za') {
                    $query
                        ->orderByDesc('name')
                        ->orderByDesc('id');

                    return;
                }

                $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('super_admin/user-manager/index', [
            'users' => $users,
            'filters' => [
                'search' => $search !== null ? (string) $search : null,
                'role' => $role !== null ? (string) $role : null,
                'sort' => $sort,
                'claim_status' => $claimStatus,
                'login_activity' => $loginActivity,
            ],
            'role_limits' => [
                UserRole::SUPER_ADMIN->value => [
                    'limit' => 1,
                    'count' => $this->roleAccountCount(UserRole::SUPER_ADMIN),
                ],
                UserRole::ADMIN->value => [
                    'limit' => 1,
                    'count' => $this->roleAccountCount(UserRole::ADMIN),
                ],
            ],
        ]);
    }

    public function store(
        StoreUserRequest $request,
        AuditLogService $auditLogService,
        EnrollmentAccountClaimService $accountClaimService,
    ): RedirectResponse {
        $validated = $request->validated();

        if ($this->roleLimitReached((string) $validated['role'])) {
            return back()
                ->withErrors(['role' => $this->roleLimitMessage((string) $validated['role'])])
                ->withInput();
        }

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

        $personalEmail = Str::lower(trim((string) $validated['personal_email']));

        $managedUser = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $email,
            'personal_email' => $personalEmail,
            'birthday' => null,
            'role' => $validated['role'],
            'password' => Hash::make(Str::random(40)),
            'must_change_password' => true,
        ]);

        $accountClaimService->issueForStaffUser($managedUser, $personalEmail);

        $auditLogService->log('user.created', $managedUser, null, $this->auditValues($managedUser));

        DashboardCacheService::bust();

        return back()->with('success', 'Staff account created. Claim email sent to the personal email address.');
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validated();

        if (
            $user->is_active
            && $user->role === UserRole::SUPER_ADMIN
            && $validated['role'] !== UserRole::SUPER_ADMIN->value
            && $this->activeSuperAdminCount() <= 1
        ) {
            return back()->with('error', 'At least one active super admin account must remain active.');
        }

        if ($this->roleLimitReached((string) $validated['role'], $user)) {
            return back()
                ->withErrors(['role' => $this->roleLimitMessage((string) $validated['role'])])
                ->withInput();
        }

        $oldValues = $this->auditValues($user);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'personal_email' => isset($validated['personal_email'])
                ? Str::lower(trim((string) $validated['personal_email']))
                : null,
            'birthday' => $validated['birthday'] ?? null,
            'role' => $validated['role'],
        ]);

        $auditLogService->log('user.updated', $user, $oldValues, $this->auditValues($user));

        DashboardCacheService::bust();

        return back()->with('success', 'User account updated successfully.');
    }

    public function resetPassword(
        User $user,
        AuditLogService $auditLogService,
        EnrollmentAccountClaimService $accountClaimService,
    ): RedirectResponse {
        if ($this->isStaffRole($user)) {
            $personalEmail = trim((string) $user->personal_email);

            if ($personalEmail === '') {
                return back()->with('error', 'Personal email is not set. Cannot send staff claim email.');
            }

            $oldValues = $this->auditValues($user);

            $user->update([
                'password' => Hash::make(Str::random(40)),
                'must_change_password' => true,
                'password_updated_at' => null,
            ]);

            $accountClaimService->issueForStaffUser($user->fresh() ?? $user, $personalEmail);

            $auditLogService->log('user.claim_email_sent', $user, $oldValues, $this->auditValues($user));

            DashboardCacheService::bust();

            return back()->with('success', 'Claim email sent to the staff personal email address.');
        }

        $passwordIdentity = $this->resolvePasswordIdentity($user);

        if ($passwordIdentity === null) {
            return back()->with('error', 'User birthday is not set. Cannot auto-generate password.');
        }

        $password = $this->buildDefaultPassword(
            $passwordIdentity['first_name'],
            $passwordIdentity['birthday']
        );
        $oldValues = $this->auditValues($user);

        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
            'password_updated_at' => now(),
        ]);

        $auditLogService->log('user.password_reset', $user, $oldValues, $this->auditValues($user));

        DashboardCacheService::bust();

        return back()->with('success', 'Password reset to default successfully.');
    }

    /**
     * @return array{first_name: string, birthday: string}|null
     */
    private function resolvePasswordIdentity(User $user): ?array
    {
        if ($user->role === UserRole::PARENT) {
            $student = $user->students()
                ->whereNotNull('birthdate')
                ->orderBy('parent_student.student_id')
                ->first(['students.first_name', 'students.birthdate']);

            if ($student) {
                return [
                    'first_name' => (string) $student->first_name,
                    'birthday' => (string) $student->birthdate,
                ];
            }
        }

        if (! $user->birthday) {
            return null;
        }

        return [
            'first_name' => (string) ($user->first_name ?: $user->name),
            'birthday' => (string) $user->birthday,
        ];
    }

    public function toggleStatus(User $user, AuditLogService $auditLogService): RedirectResponse
    {
        if (
            $user->is_active
            && $user->role === UserRole::SUPER_ADMIN
            && $this->activeSuperAdminCount() <= 1
        ) {
            return back()->with('error', 'At least one active super admin account must remain active.');
        }

        $oldValues = $this->auditValues($user);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $auditLogService->log('user.status_toggled', $user, $oldValues, $this->auditValues($user));

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

    private function staffRoleValues(): array
    {
        return [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::TEACHER->value,
        ];
    }

    private function creatableStaffRoleValues(): array
    {
        return [
            UserRole::ADMIN->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::TEACHER->value,
        ];
    }

    private function isStaffRole(User $user): bool
    {
        $role = $user->role;
        $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

        return in_array($roleValue, $this->staffRoleValues(), true);
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()
            ->where('role', UserRole::SUPER_ADMIN->value)
            ->where('is_active', true)
            ->count();
    }

    private function roleAccountCount(UserRole $role, ?User $exceptUser = null): int
    {
        return User::query()
            ->where('role', $role->value)
            ->when($exceptUser, fn ($query) => $query->whereKeyNot($exceptUser->id))
            ->count();
    }

    private function roleLimitReached(string $role, ?User $exceptUser = null): bool
    {
        $limitedRole = UserRole::tryFrom($role);

        if (! $limitedRole || ! in_array($limitedRole, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true)) {
            return false;
        }

        return $this->roleAccountCount($limitedRole, $exceptUser) >= 1;
    }

    private function roleLimitMessage(string $role): string
    {
        $label = match ($role) {
            UserRole::SUPER_ADMIN->value => 'Super Admin',
            UserRole::ADMIN->value => 'Admin',
            default => 'This role',
        };

        return "Only one {$label} account is allowed.";
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(User $user): array
    {
        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'personal_email' => $user->personal_email,
            'birthday' => $user->birthday?->format('Y-m-d'),
            'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'password_updated_at' => $user->password_updated_at,
        ];
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
