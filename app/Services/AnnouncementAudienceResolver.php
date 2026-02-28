<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnouncementAudienceResolver
{
    /**
     * @param  array<int, string>  $targetRoles
     * @param  array<int, int|string>  $targetUserIds
     * @return Collection<int, User>
     */
    public function resolveRecipients(
        User $organizer,
        array $targetRoles = [],
        array $targetUserIds = [],
        ?int $academicYearId = null
    ): Collection {
        $allowedUsers = $this->resolveAllowedUsers($organizer, $academicYearId);
        $normalizedTargetRoles = $this->normalizeTargetRoles($targetRoles);
        $normalizedTargetUserIds = $this->normalizeTargetUserIds($targetUserIds);

        $allowedRoleValues = $allowedUsers
            ->map(fn (User $user): string => $this->resolveRoleValue($user))
            ->unique()
            ->values();
        $allowedUserIds = $allowedUsers
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->values();

        $outOfScopeRoles = collect($normalizedTargetRoles)->diff($allowedRoleValues)->values();
        if ($outOfScopeRoles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'target_roles' => 'Selected roles are outside your allowed audience scope.',
            ]);
        }

        $outOfScopeUserIds = collect($normalizedTargetUserIds)->diff($allowedUserIds)->values();
        if ($outOfScopeUserIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'target_user_ids' => 'Selected users are outside your allowed audience scope.',
            ]);
        }

        $recipients = $allowedUsers
            ->when(
                $normalizedTargetRoles !== [],
                fn (Collection $collection) => $collection
                    ->filter(fn (User $user): bool => in_array($this->resolveRoleValue($user), $normalizedTargetRoles, true))
            )
            ->when(
                $normalizedTargetUserIds !== [],
                fn (Collection $collection) => $collection
                    ->filter(fn (User $user): bool => in_array((int) $user->id, $normalizedTargetUserIds, true))
            )
            ->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'target_roles' => 'No recipients matched the selected role or user filters.',
            ]);
        }

        return $recipients;
    }

    /**
     * @return Collection<int, User>
     */
    public function resolveAllowedUsers(User $organizer, ?int $academicYearId = null): Collection
    {
        $roleValue = $this->resolveRoleValue($organizer);
        $resolvedAcademicYearId = $this->resolveAcademicYearId($academicYearId);

        if ($roleValue === UserRole::TEACHER->value) {
            $teacherScopeUserIds = $this->resolveTeacherScopeUserIds($organizer, $resolvedAcademicYearId);

            if ($teacherScopeUserIds->isEmpty()) {
                return new Collection;
            }

            return $this->baseActiveUsersQuery()
                ->whereIn('id', $teacherScopeUserIds->all())
                ->get(['id', 'name', 'email', 'role']);
        }

        if ($roleValue === UserRole::REGISTRAR->value) {
            $registrarScopeUserIds = $this->resolveRegistrarScopeUserIds($resolvedAcademicYearId);

            if ($registrarScopeUserIds->isEmpty()) {
                return new Collection;
            }

            return $this->baseActiveUsersQuery()
                ->whereIn('id', $registrarScopeUserIds->all())
                ->get(['id', 'name', 'email', 'role']);
        }

        if ($roleValue === UserRole::FINANCE->value) {
            $financeScopeUserIds = $this->resolveFinanceScopeUserIds($resolvedAcademicYearId);

            if ($financeScopeUserIds->isEmpty()) {
                return new Collection;
            }

            return $this->baseActiveUsersQuery()
                ->whereIn('id', $financeScopeUserIds->all())
                ->get(['id', 'name', 'email', 'role']);
        }

        return $this->baseActiveUsersQuery()
            ->whereIn('role', $this->allowedRoleValuesForPublisher($organizer))
            ->get(['id', 'name', 'email', 'role']);
    }

    /**
     * @return array{
     *     roles: array<int, array{value: string, label: string}>,
     *     users: array<int, array{id: int, label: string, role: string, role_label: string}>,
     *     selected_academic_year_id: int|null,
     *     academic_year_options: array<int, array{id: int, name: string, status: string}>
     * }
     */
    public function resolveAudienceOptions(User $organizer, ?int $academicYearId = null): array
    {
        $resolvedAcademicYearId = $this->resolveAcademicYearId($academicYearId);
        $allowedUsers = $this->resolveAllowedUsers($organizer, $resolvedAcademicYearId);
        $roleOrder = collect(UserRole::cases())
            ->map(fn (UserRole $role): string => $role->value);

        $roleValues = $allowedUsers
            ->map(fn (User $user): string => $this->resolveRoleValue($user))
            ->unique()
            ->sortBy(fn (string $roleValue) => $roleOrder->search($roleValue))
            ->values();

        $roles = $roleValues
            ->map(function (string $roleValue): array {
                $role = UserRole::tryFrom($roleValue);

                return [
                    'value' => $roleValue,
                    'label' => $role?->label() ?? ucfirst(str_replace('_', ' ', $roleValue)),
                ];
            })
            ->all();

        $users = $allowedUsers
            ->map(function (User $user): array {
                $roleValue = $this->resolveRoleValue($user);
                $role = UserRole::tryFrom($roleValue);
                $displayName = trim((string) $user->name) !== '' ? (string) $user->name : (string) $user->email;

                return [
                    'id' => (int) $user->id,
                    'label' => $displayName,
                    'role' => $roleValue,
                    'role_label' => $role?->label() ?? ucfirst(str_replace('_', ' ', $roleValue)),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        $academicYearOptions = AcademicYear::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'status'])
            ->map(fn (AcademicYear $academicYear): array => [
                'id' => (int) $academicYear->id,
                'name' => (string) $academicYear->name,
                'status' => (string) $academicYear->status,
            ])
            ->all();

        return [
            'roles' => $roles,
            'users' => $users,
            'selected_academic_year_id' => $resolvedAcademicYearId,
            'academic_year_options' => $academicYearOptions,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveTeacherScopeUserIds(User $organizer, ?int $academicYearId): Collection
    {
        $advisorySectionIds = Section::query()
            ->where('adviser_id', $organizer->id)
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('academic_year_id', $academicYearId)
            )
            ->pluck('id');

        $teachingSectionIds = SubjectAssignment::query()
            ->join('teacher_subjects', 'teacher_subjects.id', '=', 'subject_assignments.teacher_subject_id')
            ->join('sections', 'sections.id', '=', 'subject_assignments.section_id')
            ->where('teacher_subjects.teacher_id', $organizer->id)
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('sections.academic_year_id', $academicYearId)
            )
            ->pluck('sections.id');

        $sectionIds = $advisorySectionIds
            ->merge($teachingSectionIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($sectionIds->isEmpty()) {
            return new Collection;
        }

        $studentIds = Enrollment::query()
            ->whereIn('section_id', $sectionIds->all())
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('academic_year_id', $academicYearId)
            )
            ->where('status', 'enrolled')
            ->pluck('student_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        return $this->resolveStudentAndParentUserIds($studentIds);
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveRegistrarScopeUserIds(?int $academicYearId): Collection
    {
        $studentIds = Enrollment::query()
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('academic_year_id', $academicYearId)
            )
            ->pluck('student_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        return $this->resolveStudentAndParentUserIds($studentIds);
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveFinanceScopeUserIds(?int $academicYearId): Collection
    {
        $financeActiveStatuses = [
            'for_cashier_payment',
            'partial_payment',
            'enrolled',
        ];

        $candidateStudentIds = Enrollment::query()
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('academic_year_id', $academicYearId)
            )
            ->whereIn('status', $financeActiveStatuses)
            ->pluck('student_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($candidateStudentIds->isEmpty()) {
            return new Collection;
        }

        $studentsWithBilling = BillingSchedule::query()
            ->whereIn('student_id', $candidateStudentIds->all())
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('academic_year_id', $academicYearId)
            )
            ->pluck('student_id')
            ->map(fn (int|string $id): int => (int) $id);

        $studentsWithLedgerActivity = LedgerEntry::query()
            ->whereIn('student_id', $candidateStudentIds->all())
            ->when(
                $academicYearId !== null,
                fn (Builder $query) => $query->where('academic_year_id', $academicYearId)
            )
            ->pluck('student_id')
            ->map(fn (int|string $id): int => (int) $id);

        $studentIds = $studentsWithBilling
            ->merge($studentsWithLedgerActivity)
            ->unique()
            ->values();

        return $this->resolveStudentAndParentUserIds($studentIds);
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @return Collection<int, int>
     */
    private function resolveStudentAndParentUserIds(Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return new Collection;
        }

        $studentUserIds = Student::query()
            ->whereIn('id', $studentIds->all())
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn (int|string $id): int => (int) $id);

        $parentUserIds = DB::table('parent_student')
            ->whereIn('student_id', $studentIds->all())
            ->pluck('parent_id')
            ->map(fn (int|string $id): int => (int) $id);

        return $studentUserIds
            ->merge($parentUserIds)
            ->unique()
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoleValuesForPublisher(User $organizer): array
    {
        $allowedRoleValues = collect(UserRole::cases())
            ->map(fn (UserRole $role): string => $role->value);

        if ($this->resolveRoleValue($organizer) !== UserRole::SUPER_ADMIN->value) {
            $allowedRoleValues = $allowedRoleValues
                ->reject(fn (string $roleValue): bool => $roleValue === UserRole::SUPER_ADMIN->value)
                ->values();
        }

        return $allowedRoleValues->all();
    }

    private function baseActiveUsersQuery(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            });
    }

    /**
     * @param  array<int, string>  $targetRoles
     * @return array<int, string>
     */
    private function normalizeTargetRoles(array $targetRoles): array
    {
        $knownRoleValues = collect(UserRole::cases())
            ->map(fn (UserRole $role): string => $role->value);

        return collect($targetRoles)
            ->filter(fn (mixed $role): bool => is_string($role))
            ->map(fn (string $role): string => trim($role))
            ->filter(fn (string $role): bool => $knownRoleValues->contains($role))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int|string>  $targetUserIds
     * @return array<int, int>
     */
    private function normalizeTargetUserIds(array $targetUserIds): array
    {
        return collect($targetUserIds)
            ->map(function (mixed $targetUserId): ?int {
                if (is_int($targetUserId)) {
                    return $targetUserId;
                }

                if (is_string($targetUserId) && is_numeric($targetUserId)) {
                    return (int) $targetUserId;
                }

                return null;
            })
            ->filter(fn (?int $targetUserId): bool => $targetUserId !== null && $targetUserId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveAcademicYearId(?int $academicYearId): ?int
    {
        if (
            $academicYearId !== null
            && AcademicYear::query()->whereKey($academicYearId)->exists()
        ) {
            return $academicYearId;
        }

        $ongoingAcademicYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($ongoingAcademicYearId !== null) {
            return (int) $ongoingAcademicYearId;
        }

        $latestAcademicYearId = AcademicYear::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->value('id');

        return $latestAcademicYearId !== null ? (int) $latestAcademicYearId : null;
    }

    private function resolveRoleValue(User $user): string
    {
        if ($user->role instanceof UserRole) {
            return $user->role->value;
        }

        if (is_object($user->role) && property_exists($user->role, 'value')) {
            return (string) $user->role->value;
        }

        return (string) $user->role;
    }
}
