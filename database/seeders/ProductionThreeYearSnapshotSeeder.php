<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BillingSchedule;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\Finance\BillingScheduleService;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedNameBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionThreeYearSnapshotSeeder extends Seeder
{
    private const COMPLETED_SCHOOL_YEARS = ['2023-2024', '2024-2025'];
    private const LATEST_HISTORICAL_YEAR = '2024-2025';
    private const CURRENT_YEAR = '2025-2026';
    private const COHORT_SCHOOL_YEARS = ['2023-2024', '2024-2025', '2025-2026'];
    private const STUDENTS_PER_MAIN_SECTION = 50;
    private const STUDENTS_PER_GRADE_TEN_SECTION = 25;
    private const CASHIER_QUEUE_SEED_COUNT = 5;
    private const MAX_EXTERNAL_PREVIOUS_SCHOOL_RECORDS = 25;
    private const EXTERNAL_SCHOOL_NAMES = [
        'St. Bernadette Academy',
        'Quezon City Learning Center',
        'Northfields Integrated School',
        'San Isidro Catholic School',
        'Golden Horizon Academy',
        'Mabini Community School',
        'Our Lady of Grace Institute',
        'Bayanihan Learning Academy',
    ];

    public function run(): void
    {
        $this->call(ProductionQuarterOneDayFifteenSeeder::class);

        $this->seedNamedStaffAccounts();
        $this->enforceAcademicYearTimeline();
        $this->call(CohortEnrollmentContinuitySeeder::class);
        $this->call(CohortAcademicRecordsSeeder::class);
        $this->call(CohortFinanceHistorySeeder::class);
        $this->normalizeStudentLrnsToTwelveDigits();
        $this->enforceNoCurrentYearPermanentRecordsForCurrentEnrollments();
        $this->enforceHistoricalGradeProgressionForCurrentEnrollments();
        $this->enforceCurrentYearQuarterOneSubmissionsOnly();
        $this->enforceStudentIdentityAndEmailFormats();
        $this->enforceRequirementsSubmittedForAllEnrollments();
        $this->enforceSeededAccountPasswords();
    }

    private function enforceRequirementsSubmittedForAllEnrollments(): void
    {
        Enrollment::query()->update([
            'report_card_submitted' => true,
            'birth_certificate_submitted' => true,
        ]);
    }

    private function seedNamedStaffAccounts(): void
    {
        $namedStaffBlueprint = [
            'alex.avellanosa@marriott.edu' => ['first_name' => 'Alex', 'last_name' => 'Avellanosa', 'role' => UserRole::ADMIN],
            'jocelyn.cleofe@marriott.edu' => ['first_name' => 'Jocelyn', 'last_name' => 'Cleofe', 'role' => UserRole::REGISTRAR],
            'corrine.avellanosa@marriott.edu' => ['first_name' => 'Corrine', 'last_name' => 'Avellanosa', 'role' => UserRole::FINANCE],
        ];

        foreach ($namedStaffBlueprint as $email => $staffData) {
            User::query()->where('email', $email)->update([
                'first_name' => $staffData['first_name'],
                'last_name' => $staffData['last_name'],
                'name' => trim("{$staffData['first_name']} {$staffData['last_name']}"),
                'role' => $staffData['role'],
                'is_active' => true,
            ]);
        }
    }

    private function normalizeStudentLrnsToTwelveDigits(): void
    {
        $students = Student::query()->orderBy('id')->get();
        $usedLrns = [];
        $sequence = 1;

        foreach ($students as $student) {
            $digitsOnlyLrn = preg_replace('/\D/', '', (string) $student->lrn) ?? '';

            if (strlen($digitsOnlyLrn) === 12 && ! in_array($digitsOnlyLrn, $usedLrns, true)) {
                $usedLrns[] = $digitsOnlyLrn;

                continue;
            }

            do {
                $candidateLrn = sprintf('2300%08d', $sequence++);
            } while (in_array($candidateLrn, $usedLrns, true));

            $student->update(['lrn' => $candidateLrn]);
            $usedLrns[] = $candidateLrn;
        }
    }

    private function enforceAcademicYearTimeline(): void
    {
        AcademicYear::query()->where('name', '2023-2024')->update([
            'status' => 'completed',
            'current_quarter' => '4',
            'start_date' => '2023-06-05',
            'end_date' => '2024-03-29',
        ]);

        AcademicYear::query()->where('name', '2024-2025')->update([
            'status' => 'completed',
            'current_quarter' => '4',
            'start_date' => '2024-06-03',
            'end_date' => '2025-03-28',
        ]);

        AcademicYear::query()->where('name', '2025-2026')->update([
            'status' => 'ongoing',
            'current_quarter' => '1',
            'start_date' => '2025-06-02',
            'end_date' => '2026-03-27',
        ]);
    }

    private function enforceStudentIdentityAndEmailFormats(): void
    {
        Student::query()
            ->with('user')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (Student $student, int $index): void {
                $nameSet = $this->studentNameSetForIndex($index);
                $firstName = $nameSet['student_first_name'];
                $lastName = $nameSet['student_last_name'];
                $normalizedLrn = preg_replace('/\D/', '', (string) $student->lrn) ?? '';
                $lastNameToken = Str::of($lastName)
                    ->ascii()
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]+/', '');
                $studentEmail = "{$lastNameToken}.{$normalizedLrn}@marriott.edu";

                $student->update([
                    'first_name' => $firstName,
                    'middle_name' => $nameSet['student_middle_name'],
                    'last_name' => $lastName,
                    'lrn' => $normalizedLrn,
                    'guardian_name' => "{$nameSet['guardian_first_name']} {$lastName}",
                ]);

                if ($student->user) {
                    $this->releaseConflictingEmail($studentEmail, (int) $student->user->id);
                }

                $student->user?->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => trim("{$firstName} {$lastName}"),
                    'email' => $studentEmail,
                ]);

                $parentIds = DB::table('parent_student')
                    ->where('student_id', $student->id)
                    ->pluck('parent_id');

                foreach ($parentIds->values() as $parentIndex => $parentId) {
                    $parentEmail = $parentIndex === 0
                        ? "parent.{$normalizedLrn}@marriott.edu"
                        : "parent.{$normalizedLrn}.{$parentId}@marriott.edu";

                    $this->releaseConflictingEmail($parentEmail, (int) $parentId);

                    User::query()
                        ->whereKey($parentId)
                        ->update([
                            'email' => $parentEmail,
                        ]);
                }
            });
    }

    private function releaseConflictingEmail(string $email, int $intendedUserId): void
    {
        User::query()
            ->where('email', $email)
            ->whereKeyNot($intendedUserId)
            ->get(['id', 'email'])
            ->each(function (User $user): void {
                $user->update([
                    'email' => "archived.seed.{$user->id}.{$user->email}",
                ]);
            });
    }

    private function rebuildCohortEnrollmentContinuity(): void
    {
        $this->clearStudentCohortData();

        $years = AcademicYear::query()
            ->whereIn('name', self::COHORT_SCHOOL_YEARS)
            ->orderBy('start_date')
            ->get()
            ->keyBy('name');

        $gradeLevels = GradeLevel::query()
            ->get()
            ->keyBy(fn (GradeLevel $gradeLevel): int => (int) $gradeLevel->level_order);

        /** @var array<int, array<int, Student>> $previousStudentsByGrade */
        $previousStudentsByGrade = [];
        $globalStudentIndex = 0;

        foreach (self::COHORT_SCHOOL_YEARS as $yearIndex => $schoolYearName) {
            /** @var AcademicYear|null $academicYear */
            $academicYear = $years->get($schoolYearName);
            if (! $academicYear instanceof AcademicYear) {
                continue;
            }

            /** @var array<int, array<int, Student>> $studentsByGrade */
            $studentsByGrade = [];

            if ($yearIndex === 0) {
                foreach ([7, 8, 9, 10] as $gradeOrder) {
                    $studentsByGrade[$gradeOrder] = $this->createNewCohortStudents(
                        $schoolYearName,
                        $gradeOrder,
                        $this->targetStudentCountForGrade($gradeOrder),
                        $globalStudentIndex
                    );
                }
            } else {
                $studentsByGrade[7] = $this->createNewCohortStudents(
                    $schoolYearName,
                    7,
                    self::STUDENTS_PER_MAIN_SECTION,
                    $globalStudentIndex
                );

                foreach ([7, 8, 9] as $sourceGradeOrder) {
                    $targetGradeOrder = $sourceGradeOrder + 1;
                    $promotedStudents = $previousStudentsByGrade[$sourceGradeOrder] ?? [];
                    $lossCount = $this->nonContinuingCount($yearIndex, $sourceGradeOrder);
                    $continuingStudents = array_slice($promotedStudents, 0, max(count($promotedStudents) - $lossCount, 0));
                    $transferees = $this->createNewCohortStudents(
                        $schoolYearName,
                        $targetGradeOrder,
                        min($lossCount, $this->targetStudentCountForGrade($targetGradeOrder)),
                        $globalStudentIndex,
                        true
                    );

                    $studentsByGrade[$targetGradeOrder] = array_values([
                        ...($studentsByGrade[$targetGradeOrder] ?? []),
                        ...$continuingStudents,
                        ...$transferees,
                    ]);
                }
            }

            foreach ([7, 8, 9, 10] as $gradeOrder) {
                $gradeLevel = $gradeLevels->get($gradeOrder);
                if (! $gradeLevel instanceof GradeLevel) {
                    continue;
                }

                $sections = $this->sectionsForGrade($academicYear, $gradeOrder);
                if ($sections->isEmpty()) {
                    continue;
                }

                $students = array_values($studentsByGrade[$gradeOrder] ?? []);
                foreach ($students as $studentIndex => $student) {
                    $section = $sections[$studentIndex % $sections->count()];
                    $enrollment = $this->enrollCohortStudent(
                        $student,
                        $academicYear,
                        $gradeLevel,
                        $section,
                        $studentIndex
                    );

                    if ($academicYear->name !== self::CURRENT_YEAR) {
                        $this->seedPermanentRecordForCompletedEnrollment($academicYear, $enrollment, $gradeOrder, $studentIndex);
                        $this->seedHistoricalFinalOutcomes($academicYear, $enrollment, false, $studentIndex);
                    } else {
                        $this->seedCurrentQuarterOneRecords($academicYear, $enrollment, $studentIndex);
                    }
                }
            }

            $previousStudentsByGrade = $studentsByGrade;
        }

        AcademicYear::query()
            ->where('name', self::CURRENT_YEAR)
            ->update([
                'status' => 'ongoing',
                'current_quarter' => '1',
            ]);
    }

    private function clearStudentCohortData(): void
    {
        DB::statement('TRUNCATE TABLE students RESTART IDENTITY CASCADE');

        User::query()
            ->whereIn('role', [UserRole::STUDENT, UserRole::PARENT])
            ->delete();

        GradeSubmission::query()->delete();
        GradedActivity::query()->delete();
    }

    /**
     * @return array<int, Student>
     */
    private function createNewCohortStudents(
        string $schoolYearName,
        int $gradeOrder,
        int $count,
        int &$globalStudentIndex,
        bool $isTransferee = false
    ): array {
        $students = [];

        for ($index = 0; $index < $count; $index++) {
            $seedIndex = $globalStudentIndex++;
            $nameSet = $this->studentNameSetForIndex($seedIndex);
            $lrn = $this->cohortLrn($schoolYearName, $gradeOrder, $seedIndex);
            $emailLastNameToken = Str::of($nameSet['student_last_name'])
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '');
            $studentEmail = "{$emailLastNameToken}.{$lrn}@marriott.edu";
            $parentEmail = "parent.{$lrn}@marriott.edu";

            $studentUser = User::query()->create([
                'first_name' => $nameSet['student_first_name'],
                'last_name' => $nameSet['student_last_name'],
                'name' => "{$nameSet['student_first_name']} {$nameSet['student_last_name']}",
                'email' => $studentEmail,
                'password' => Hash::make('password'),
                'birthday' => CarbonImmutable::create(2016 - ($gradeOrder - 7), (($seedIndex % 12) + 1), (($seedIndex % 27) + 1))->toDateString(),
                'role' => UserRole::STUDENT,
                'is_active' => true,
                'must_change_password' => true,
                'password_updated_at' => now(),
            ]);

            $parentUser = User::query()->create([
                'first_name' => $nameSet['guardian_first_name'],
                'last_name' => $nameSet['student_last_name'],
                'name' => "{$nameSet['guardian_first_name']} {$nameSet['student_last_name']}",
                'email' => $parentEmail,
                'password' => Hash::make('password'),
                'birthday' => '1983-01-01',
                'role' => UserRole::PARENT,
                'is_active' => true,
                'must_change_password' => true,
                'password_updated_at' => now(),
            ]);

            $student = Student::query()->create([
                'user_id' => $studentUser->id,
                'lrn' => $lrn,
                'first_name' => $nameSet['student_first_name'],
                'middle_name' => $nameSet['student_middle_name'],
                'last_name' => $nameSet['student_last_name'],
                'gender' => $seedIndex % 2 === 0 ? 'Male' : 'Female',
                'birthdate' => CarbonImmutable::create(2016 - ($gradeOrder - 7), (($seedIndex % 12) + 1), (($seedIndex % 27) + 1))->toDateString(),
                'contact_number' => '+639'.substr($lrn, 0, 9),
                'address' => $isTransferee
                    ? self::EXTERNAL_SCHOOL_NAMES[$seedIndex % count(self::EXTERNAL_SCHOOL_NAMES)].', Quezon City'
                    : 'San Francisco Del Monte, Quezon City',
                'guardian_name' => "{$nameSet['guardian_first_name']} {$nameSet['student_last_name']}",
                'is_lis_synced' => true,
                'sync_error_flag' => false,
                'sync_error_notes' => $isTransferee ? 'Seeded transferee student.' : null,
                'is_for_remedial' => false,
            ]);

            DB::table('parent_student')->insert([
                'parent_id' => $parentUser->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $students[] = $student;
        }

        return $students;
    }

    private function enrollCohortStudent(
        Student $student,
        AcademicYear $academicYear,
        GradeLevel $gradeLevel,
        Section $section,
        int $studentIndex
    ): Enrollment {
        $paymentTerms = ['cash', 'monthly', 'quarterly', 'semi-annual'];
        $paymentTerm = $paymentTerms[$studentIndex % count($paymentTerms)];

        return Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'section_id' => $section->id,
            'payment_term' => $paymentTerm,
            'downpayment' => match ($paymentTerm) {
                'monthly' => 4000,
                'quarterly' => 5000,
                'semi-annual' => 6500,
                default => 0,
            },
            'status' => 'enrolled',
            'report_card_submitted' => true,
            'birth_certificate_submitted' => true,
            'created_at' => $academicYear->start_date,
            'updated_at' => $academicYear->start_date,
        ]);
    }

    private function seedPermanentRecordForCompletedEnrollment(
        AcademicYear $academicYear,
        Enrollment $enrollment,
        int $gradeOrder,
        int $studentIndex
    ): void {
        PermanentRecord::query()->updateOrCreate(
            [
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $academicYear->id,
            ],
            [
                'school_name' => 'Marriott School',
                'grade_level_id' => $enrollment->grade_level_id,
                'general_average' => 84 + ($studentIndex % 10),
                'status' => $gradeOrder === 10 ? 'completed' : 'promoted',
                'failed_subject_count' => 0,
                'conditional_resolved_at' => null,
                'conditional_resolution_notes' => null,
                'remarks' => $gradeOrder === 10
                    ? 'Seeded completed terminal grade level.'
                    : 'Seeded promoted completion record.',
            ]
        );
    }

    private function seedCurrentQuarterOneRecords(AcademicYear $academicYear, Enrollment $enrollment, int $index): void
    {
        $assignments = SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get();

        foreach ($assignments as $assignmentIndex => $assignment) {
            $grade = 84 + (($index + $assignmentIndex) % 9);
            FinalGrade::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => '1',
                ],
                [
                    'grade' => $grade,
                    'is_locked' => false,
                ]
            );

                GradeSubmission::withoutEvents(function () use ($academicYear, $assignment): void {
                    GradeSubmission::query()->updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'subject_assignment_id' => $assignment->id,
                            'quarter' => '1',
                        ],
                        [
                            'status' => GradeSubmission::STATUS_SUBMITTED,
                            'submitted_by' => null,
                            'submitted_at' => CarbonImmutable::parse((string) $academicYear->start_date)->addDays(15),
                            'verified_by' => null,
                            'verified_at' => null,
                            'returned_by' => null,
                            'returned_at' => null,
                            'return_notes' => null,
                        ]
                    );
                });
        }
    }

    private function targetStudentCountForGrade(int $gradeOrder): int
    {
        return $gradeOrder === 10
            ? self::STUDENTS_PER_GRADE_TEN_SECTION * 2
            : self::STUDENTS_PER_MAIN_SECTION;
    }

    private function nonContinuingCount(int $yearIndex, int $sourceGradeOrder): int
    {
        return 1 + (($yearIndex + $sourceGradeOrder) % 2);
    }

    /**
     * @return Collection<int, Section>
     */
    private function sectionsForGrade(AcademicYear $academicYear, int $gradeOrder): Collection
    {
        return Section::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('gradeLevel', fn ($query) => $query->where('level_order', $gradeOrder))
            ->orderBy('name')
            ->get()
            ->values();
    }

    private function cohortLrn(string $schoolYearName, int $gradeOrder, int $seedIndex): string
    {
        $yearToken = substr(str_replace('-', '', $schoolYearName), 2, 4);

        return sprintf('%s%02d%06d', $yearToken, $gradeOrder, $seedIndex + 1);
    }

    private function seedHistoricalCoverageForCompletedYears(): void
    {
        $currentYear = AcademicYear::query()->where('name', self::CURRENT_YEAR)->first();
        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        $currentEnrollments = Enrollment::query()
            ->where('academic_year_id', $currentYear->id)
            ->orderBy('student_id')
            ->get();

        foreach (self::COMPLETED_SCHOOL_YEARS as $schoolYearName) {
            $academicYear = AcademicYear::query()->where('name', $schoolYearName)->first();
            if (! $academicYear instanceof AcademicYear) {
                continue;
            }

            $this->seedHistoricalForYear($academicYear, $currentEnrollments);
            $this->backfillHistoricalSectionCoverage($academicYear);
        }
    }

    private function seedExternalPreviousSchoolPermanentRecords(): void
    {
        $previousYear = AcademicYear::query()->where('name', '2023-2024')->first();
        $latestHistoricalYear = AcademicYear::query()->where('name', self::LATEST_HISTORICAL_YEAR)->first();
        $currentYear = AcademicYear::query()->where('name', self::CURRENT_YEAR)->first();

        if (
            ! $previousYear instanceof AcademicYear
            || ! $latestHistoricalYear instanceof AcademicYear
            || ! $currentYear instanceof AcademicYear
        ) {
            return;
        }

        $eligibleStudentIds = Enrollment::query()
            ->from('enrollments as latest_enrollments')
            ->leftJoin('enrollments as current_enrollments', function ($join) use ($currentYear): void {
                $join
                    ->on('current_enrollments.student_id', '=', 'latest_enrollments.student_id')
                    ->where('current_enrollments.academic_year_id', '=', $currentYear->id)
                    ->where('current_enrollments.status', '=', 'enrolled');
            })
            ->where('latest_enrollments.academic_year_id', $latestHistoricalYear->id)
            ->where('latest_enrollments.status', 'enrolled')
            ->whereNull('current_enrollments.id')
            ->orderBy('latest_enrollments.student_id')
            ->pluck('latest_enrollments.student_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $studentId): bool => $studentId > 0)
            ->unique()
            ->values();

        if ($eligibleStudentIds->isEmpty()) {
            return;
        }

        $externalStudentIds = $eligibleStudentIds
            ->take(self::MAX_EXTERNAL_PREVIOUS_SCHOOL_RECORDS)
            ->values();

        foreach ($eligibleStudentIds->values() as $index => $studentId) {
            $latestEnrollment = Enrollment::query()
                ->where('academic_year_id', $latestHistoricalYear->id)
                ->where('student_id', $studentId)
                ->latest('id')
                ->first();

            if (! $latestEnrollment instanceof Enrollment) {
                continue;
            }

            $latestGradeOrder = $this->resolveGradeOrder((int) $latestEnrollment->grade_level_id);
            if ($latestGradeOrder === null) {
                continue;
            }

            $previousGradeLevelId = GradeLevel::query()
                ->where('level_order', max(7, $latestGradeOrder - 1))
                ->value('id');

            if (! $previousGradeLevelId) {
                continue;
            }

            $isExternalPreviousSchool = $externalStudentIds->contains($studentId);

            if ($isExternalPreviousSchool) {
                Enrollment::query()
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $previousYear->id)
                    ->delete();
            }

            PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $previousYear->id,
                ],
                [
                    'school_name' => $isExternalPreviousSchool
                        ? self::EXTERNAL_SCHOOL_NAMES[$index % count(self::EXTERNAL_SCHOOL_NAMES)]
                        : 'Marriott School',
                    'grade_level_id' => (int) $previousGradeLevelId,
                    'general_average' => 84 + ($index % 9),
                    'status' => 'promoted',
                    'failed_subject_count' => 0,
                    'conditional_resolved_at' => null,
                    'conditional_resolution_notes' => null,
                    'remarks' => $isExternalPreviousSchool
                        ? 'Seeded previous-school permanent record before Marriott transfer-in.'
                        : 'Seeded promoted completion record.',
                ]
            );
        }
    }

    private function enforceNoCurrentYearPermanentRecordsForCurrentEnrollments(): void
    {
        $currentYear = AcademicYear::query()->where('name', self::CURRENT_YEAR)->first();
        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        $currentEnrolledStudentIds = Enrollment::query()
            ->where('academic_year_id', $currentYear->id)
            ->where('status', 'enrolled')
            ->pluck('student_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $studentId): bool => $studentId > 0)
            ->unique()
            ->values();

        if ($currentEnrolledStudentIds->isEmpty()) {
            return;
        }

        PermanentRecord::query()
            ->where('academic_year_id', $currentYear->id)
            ->whereIn('student_id', $currentEnrolledStudentIds->all())
            ->delete();
    }

    private function enforceHistoricalGradeProgressionForCurrentEnrollments(): void
    {
        $currentYear = AcademicYear::query()->where('name', self::CURRENT_YEAR)->first();
        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        $currentEnrollments = Enrollment::query()
            ->where('academic_year_id', $currentYear->id)
            ->where('status', 'enrolled')
            ->get();

        foreach ($currentEnrollments as $currentEnrollment) {
            if (! $currentEnrollment instanceof Enrollment) {
                continue;
            }

            $currentGradeOrder = $this->resolveGradeOrder((int) $currentEnrollment->grade_level_id);
            if ($currentGradeOrder === null) {
                continue;
            }

            $disallowedGradeLevelIds = GradeLevel::query()
                ->where('level_order', '>=', $currentGradeOrder)
                ->pluck('id');

            if ($disallowedGradeLevelIds->isEmpty()) {
                continue;
            }

            PermanentRecord::query()
                ->where('student_id', $currentEnrollment->student_id)
                ->whereIn('grade_level_id', $disallowedGradeLevelIds->all())
                ->delete();
        }
    }

    private function seedCurrentYearCashierQueueFromLatestHistoricalYear(): void
    {
        $latestHistoricalYear = AcademicYear::query()
            ->where('name', self::LATEST_HISTORICAL_YEAR)
            ->first();
        $currentYear = AcademicYear::query()
            ->where('name', self::CURRENT_YEAR)
            ->first();

        if (! $latestHistoricalYear instanceof AcademicYear || ! $currentYear instanceof AcademicYear) {
            return;
        }

        $studentIds = Enrollment::query()
            ->from('enrollments as historical_enrollments')
            ->join('enrollments as current_enrollments', function ($join) use ($currentYear): void {
                $join
                    ->on('current_enrollments.student_id', '=', 'historical_enrollments.student_id')
                    ->where('current_enrollments.academic_year_id', '=', $currentYear->id)
                    ->where('current_enrollments.status', '=', 'enrolled');
            })
            ->where('historical_enrollments.academic_year_id', $latestHistoricalYear->id)
            ->orderBy('historical_enrollments.student_id')
            ->limit(self::CASHIER_QUEUE_SEED_COUNT)
            ->pluck('historical_enrollments.student_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $studentId): bool => $studentId > 0)
            ->values();

        if ($studentIds->isEmpty()) {
            return;
        }

        Enrollment::query()
            ->where('academic_year_id', $currentYear->id)
            ->whereIn('student_id', $studentIds->all())
            ->where('status', 'enrolled')
            ->update([
                'payment_term' => 'cash',
                'downpayment' => 0,
                'status' => 'for_cashier_payment',
            ]);
    }

    private function seedCompleteFinanceHistory(): void
    {
        $cashier = User::query()
            ->where('role', UserRole::FINANCE)
            ->orderBy('id')
            ->first();

        if (! $cashier instanceof User) {
            return;
        }

        $academicYears = AcademicYear::query()
            ->whereIn('name', [...self::COMPLETED_SCHOOL_YEARS, self::CURRENT_YEAR])
            ->orderBy('start_date')
            ->get()
            ->keyBy('name');

        $this->seedFeeStructureForYears($academicYears);

        $inventoryItems = InventoryItem::query()
            ->orderBy('id')
            ->get()
            ->values();

        if ($inventoryItems->isEmpty()) {
            return;
        }

        $enrollments = Enrollment::query()
            ->with(['academicYear', 'gradeLevel'])
            ->whereIn('academic_year_id', $academicYears->pluck('id')->all())
            ->where(function ($query): void {
                $query
                    ->whereHas('academicYear', function ($academicYearQuery): void {
                        $academicYearQuery->whereIn('name', self::COMPLETED_SCHOOL_YEARS);
                    })
                    ->orWhere(function ($currentYearQuery): void {
                        $currentYearQuery
                            ->whereHas('academicYear', function ($academicYearQuery): void {
                                $academicYearQuery->where('name', self::CURRENT_YEAR);
                            })
                            ->where('status', 'enrolled');
                    });
            })
            ->orderBy('academic_year_id')
            ->orderBy('student_id')
            ->get();

        $billingScheduleService = app(BillingScheduleService::class);

        foreach ($enrollments->values() as $index => $enrollment) {
            if (! $enrollment instanceof Enrollment || ! $enrollment->academicYear) {
                continue;
            }

            $this->rebuildFinanceHistoryForEnrollment(
                $enrollment,
                $cashier,
                $inventoryItems,
                $billingScheduleService,
                $index
            );
        }
    }

    /**
     * @param  Collection<string, AcademicYear>  $academicYears
     */
    private function seedFeeStructureForYears(Collection $academicYears): void
    {
        $gradeLevels = GradeLevel::query()->get();

        foreach ($academicYears as $academicYear) {
            if (! $academicYear instanceof AcademicYear) {
                continue;
            }

            foreach ($gradeLevels as $gradeLevel) {
                $gradeOrder = (int) $gradeLevel->level_order;
                $yearOffset = match ((string) $academicYear->name) {
                    '2023-2024' => -1600,
                    '2024-2025' => -800,
                    default => 0,
                };

                $tuition = 33000 + (($gradeOrder - 7) * 1200) + $yearOffset;
                $miscellaneous = 7000 + (($gradeOrder - 7) * 300);
                $booksAndModules = 3200 + (($gradeOrder - 7) * 100);
                $energyFee = 1500;

                foreach ([
                    ['type' => 'tuition', 'name' => 'Tuition Fee', 'amount' => $tuition],
                    ['type' => 'miscellaneous', 'name' => 'Miscellaneous Fee', 'amount' => $miscellaneous],
                    ['type' => 'books_modules', 'name' => 'Books and Modules', 'amount' => $booksAndModules],
                    ['type' => 'other', 'name' => 'Energy and Facilities', 'amount' => $energyFee],
                ] as $feeRow) {
                    Fee::query()->updateOrCreate(
                        [
                            'grade_level_id' => $gradeLevel->id,
                            'academic_year_id' => $academicYear->id,
                            'type' => $feeRow['type'],
                            'name' => $feeRow['name'],
                        ],
                        [
                            'amount' => $feeRow['amount'],
                        ]
                    );
                }
            }
        }
    }

    /**
     * @param  Collection<int, InventoryItem>  $inventoryItems
     */
    private function rebuildFinanceHistoryForEnrollment(
        Enrollment $enrollment,
        User $cashier,
        Collection $inventoryItems,
        BillingScheduleService $billingScheduleService,
        int $seedIndex
    ): void {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear) {
            return;
        }

        $this->clearSeededFinanceRecords((int) $enrollment->student_id, (int) $academicYear->id);

        $assessmentTotal = $this->resolveNetAssessmentTotal($enrollment);
        $runningBalance = $assessmentTotal;

        LedgerEntry::query()->create([
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $academicYear->id,
            'date' => $academicYear->start_date,
            'description' => 'Opening Balance (Seeded Finance History)',
            'debit' => $assessmentTotal,
            'credit' => null,
            'running_balance' => $runningBalance,
            'reference_id' => null,
        ]);

        BillingSchedule::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $academicYear->id)
            ->delete();

        $billingScheduleService->syncForEnrollment($enrollment);

        $schedules = BillingSchedule::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $schedulesToPay = $this->isCurrentSchoolYear($academicYear)
            ? $schedules->filter(function (BillingSchedule $schedule): bool {
                return Str::of((string) $schedule->description)->lower()->contains('upon enrollment');
            })->values()
            : $schedules->values();

        foreach ($schedulesToPay as $scheduleIndex => $schedule) {
            if (! $schedule instanceof BillingSchedule) {
                continue;
            }

            $paymentDate = CarbonImmutable::parse((string) $schedule->due_date)->addDays(($scheduleIndex % 3) + 1);
            $runningBalance = $this->createFinanceTransaction(
                enrollment: $enrollment,
                cashier: $cashier,
                orNumber: $this->financeOrNumber($academicYear, (int) $enrollment->student_id, $scheduleIndex + 1),
                timestamp: $paymentDate,
                items: [[
                    'fee_id' => $this->tuitionFeeId($enrollment),
                    'inventory_item_id' => null,
                    'description' => "Assessment Payment - {$schedule->description}",
                    'amount' => (float) $schedule->amount_due,
                ]],
                allocationSchedules: collect([$schedule]),
                runningBalance: $runningBalance
            );
        }

        $this->seedInventoryPurchaseHistory($enrollment, $cashier, $inventoryItems, $runningBalance, $seedIndex);
    }

    private function isCurrentSchoolYear(AcademicYear $academicYear): bool
    {
        return (string) $academicYear->name === self::CURRENT_YEAR;
    }

    private function clearSeededFinanceRecords(int $studentId, int $academicYearId): void
    {
        $transactionIds = LedgerEntry::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereNotNull('reference_id')
            ->pluck('reference_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($transactionIds->isNotEmpty()) {
            DB::table('transaction_due_allocations')
                ->whereIn('transaction_id', $transactionIds->all())
                ->delete();
            DB::table('transaction_items')
                ->whereIn('transaction_id', $transactionIds->all())
                ->delete();
            Transaction::query()
                ->whereIn('id', $transactionIds->all())
                ->delete();
        }

        LedgerEntry::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->delete();

        BillingSchedule::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->delete();
    }

    /**
     * @param  array<int, array{fee_id: int|null, inventory_item_id: int|null, description: string, amount: float}>  $items
     * @param  Collection<int, BillingSchedule>  $allocationSchedules
     */
    private function createFinanceTransaction(
        Enrollment $enrollment,
        User $cashier,
        string $orNumber,
        CarbonImmutable $timestamp,
        array $items,
        Collection $allocationSchedules,
        float $runningBalance
    ): float {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear) {
            return $runningBalance;
        }

        $totalAmount = round(array_sum(array_map(fn (array $item): float => (float) $item['amount'], $items)), 2);

        $transaction = Transaction::query()->updateOrCreate(
            ['or_number' => $orNumber],
            [
                'student_id' => $enrollment->student_id,
                'cashier_id' => $cashier->id,
                'total_amount' => $totalAmount,
                'payment_mode' => ['cash', 'gcash', 'bank_transfer'][(int) $enrollment->id % 3],
                'reference_no' => null,
                'remarks' => 'Seeded complete finance history.',
                'status' => 'posted',
            ]
        );

        $transaction->timestamps = false;
        $transaction->created_at = $timestamp;
        $transaction->updated_at = $timestamp;
        $transaction->save();

        $transaction->items()->delete();
        $transaction->dueAllocations()->delete();
        $transaction->items()->createMany($items);

        foreach ($allocationSchedules as $schedule) {
            if (! $schedule instanceof BillingSchedule) {
                continue;
            }

            $schedule->update([
                'amount_paid' => (float) $schedule->amount_due,
                'status' => 'paid',
            ]);

            $transaction->dueAllocations()->create([
                'billing_schedule_id' => $schedule->id,
                'amount' => (float) $schedule->amount_due,
            ]);
        }

        $nextRunningBalance = round($runningBalance - $totalAmount, 2);

        LedgerEntry::query()->create([
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $academicYear->id,
            'date' => $timestamp->toDateString(),
            'description' => "Payment ({$orNumber})",
            'debit' => null,
            'credit' => $totalAmount,
            'running_balance' => $nextRunningBalance,
            'reference_id' => $transaction->id,
        ]);

        return $nextRunningBalance;
    }

    /**
     * @param  Collection<int, InventoryItem>  $inventoryItems
     */
    private function seedInventoryPurchaseHistory(
        Enrollment $enrollment,
        User $cashier,
        Collection $inventoryItems,
        float $runningBalance,
        int $seedIndex
    ): void {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear) {
            return;
        }

        $uniformItem = $this->inventoryItemMatching($inventoryItems, 'uniform') ?? $inventoryItems->first();
        $bookItem = $this->inventoryItemMatching($inventoryItems, 'manual')
            ?? $this->inventoryItemMatching($inventoryItems, 'book')
            ?? $inventoryItems->get(($seedIndex + 1) % $inventoryItems->count());
        $otherItem = $inventoryItems->first(function (InventoryItem $inventoryItem) use ($uniformItem, $bookItem): bool {
            return (int) $inventoryItem->id !== (int) ($uniformItem?->id ?? 0)
                && (int) $inventoryItem->id !== (int) ($bookItem?->id ?? 0);
        }) ?? $inventoryItems->get(($seedIndex + 2) % $inventoryItems->count());

        $selectedItems = collect([$uniformItem, $bookItem, $otherItem])
            ->filter(fn ($item): bool => $item instanceof InventoryItem)
            ->unique('id')
            ->values();

        foreach ($selectedItems as $itemIndex => $inventoryItem) {
            if (! $inventoryItem instanceof InventoryItem) {
                continue;
            }

            $timestamp = CarbonImmutable::parse((string) $academicYear->start_date)
                ->addDays(12 + ($itemIndex * 17) + ($seedIndex % 5));
            $chargeAmount = (float) $inventoryItem->price;
            $orNumber = $this->financeOrNumber($academicYear, (int) $enrollment->student_id, 70 + $itemIndex);

            $runningBalance = round($runningBalance + $chargeAmount, 2);
            LedgerEntry::query()->create([
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $academicYear->id,
                'date' => $timestamp->toDateString(),
                'description' => "Inventory Charge - {$inventoryItem->name}",
                'debit' => $chargeAmount,
                'credit' => null,
                'running_balance' => $runningBalance,
                'reference_id' => null,
            ]);

            $runningBalance = $this->createFinanceTransaction(
                enrollment: $enrollment,
                cashier: $cashier,
                orNumber: $orNumber,
                timestamp: $timestamp->addMinutes(10),
                items: [[
                    'fee_id' => null,
                    'inventory_item_id' => $inventoryItem->id,
                    'description' => $inventoryItem->name,
                    'amount' => $chargeAmount,
                ]],
                allocationSchedules: collect(),
                runningBalance: $runningBalance
            );
        }
    }

    private function inventoryItemMatching(Collection $inventoryItems, string $needle): ?InventoryItem
    {
        return $inventoryItems->first(function (InventoryItem $inventoryItem) use ($needle): bool {
            return str_contains(strtolower((string) $inventoryItem->name), $needle)
                || str_contains(strtolower((string) $inventoryItem->type), $needle);
        });
    }

    private function financeOrNumber(AcademicYear $academicYear, int $studentId, int $sequence): string
    {
        $yearToken = str_replace('-', '', (string) $academicYear->name);

        return sprintf('OR-FH-%s-%05d-%02d', $yearToken, $studentId, $sequence);
    }

    private function resolveNetAssessmentTotal(Enrollment $enrollment): float
    {
        $assessmentTotal = (float) Fee::query()
            ->where('grade_level_id', $enrollment->grade_level_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->whereIn('type', ['tuition', 'miscellaneous'])
            ->sum('amount');

        $discountTotal = 0.0;
        $discountRows = DB::table('student_discounts')
            ->join('discounts', 'discounts.id', '=', 'student_discounts.discount_id')
            ->where('student_discounts.student_id', $enrollment->student_id)
            ->where('student_discounts.academic_year_id', $enrollment->academic_year_id)
            ->get(['discounts.type', 'discounts.value']);

        foreach ($discountRows as $discountRow) {
            $discountTotal += match ((string) $discountRow->type) {
                'percentage' => round($assessmentTotal * ((float) $discountRow->value / 100), 2),
                'fixed' => round((float) $discountRow->value, 2),
                default => 0.0,
            };
        }

        return round(max($assessmentTotal - min($assessmentTotal, $discountTotal), 0), 2);
    }

    private function tuitionFeeId(Enrollment $enrollment): ?int
    {
        $feeId = Fee::query()
            ->where('grade_level_id', $enrollment->grade_level_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('type', 'tuition')
            ->value('id');

        return $feeId ? (int) $feeId : null;
    }

    /**
     * @param  Collection<int, Enrollment>  $referenceEnrollments
     */
    private function seedHistoricalForYear(AcademicYear $academicYear, Collection $referenceEnrollments): void
    {
        $sectionsByGrade = Section::query()
            ->where('academic_year_id', $academicYear->id)
            ->with('gradeLevel:id,level_order')
            ->get()
            ->groupBy(fn (Section $section): int => (int) ($section->gradeLevel?->level_order ?? 0));

        foreach ($referenceEnrollments->values() as $index => $referenceEnrollment) {
            if (! $referenceEnrollment instanceof Enrollment) {
                continue;
            }

            $studentId = (int) $referenceEnrollment->student_id;
            $currentGradeOrder = $this->resolveGradeOrder((int) $referenceEnrollment->grade_level_id);
            if ($currentGradeOrder === null) {
                continue;
            }

            $targetGradeOrder = $academicYear->name === '2024-2025'
                ? max(7, $currentGradeOrder - 1)
                : max(7, $currentGradeOrder - 2);

            $referenceSectionName = Section::query()
                ->whereKey($referenceEnrollment->section_id)
                ->value('name');

            /** @var Section|null $targetSection */
            $targetSection = $sectionsByGrade->get($targetGradeOrder)
                ?->first(function (Section $section) use ($referenceSectionName): bool {
                    return $referenceSectionName !== null
                        && (string) $section->name === (string) $referenceSectionName;
                });

            if (! $targetSection instanceof Section) {
                $targetSection = $sectionsByGrade->get($targetGradeOrder)?->sortBy('name')->first();
            }

            if (! $targetSection instanceof Section) {
                continue;
            }

            $paymentTerm = $index % 2 === 0 ? 'monthly' : 'quarterly';

            $enrollment = Enrollment::query()->updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'grade_level_id' => $targetSection->grade_level_id,
                    'section_id' => $targetSection->id,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $paymentTerm === 'monthly' ? 3000 : 5000,
                    'status' => 'enrolled',
                    'created_at' => $academicYear->start_date,
                ]
            );

            $hasConditionalOutcome = $index % 18 === 0;
            PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'school_name' => 'Marriott School',
                    'grade_level_id' => $targetSection->grade_level_id,
                    'general_average' => $hasConditionalOutcome ? 77 : 86 + ($index % 7),
                    'status' => $hasConditionalOutcome ? 'conditional' : 'promoted',
                    'failed_subject_count' => $hasConditionalOutcome ? 1 : 0,
                    'conditional_resolved_at' => null,
                    'conditional_resolution_notes' => null,
                    'remarks' => $hasConditionalOutcome
                        ? 'Seeded conditional completion record.'
                        : 'Seeded promoted completion record.',
                ]
            );

            $this->seedHistoricalFinalOutcomes($academicYear, $enrollment, $hasConditionalOutcome, $index);
        }
    }

    private function backfillHistoricalSectionCoverage(AcademicYear $academicYear): void
    {
        $sections = Section::query()
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $baseIndex = ((int) $academicYear->id * 10_000) + 500_000;
        $fallbackSequence = 0;

        foreach ($sections as $section) {
            if (! $section instanceof Section) {
                continue;
            }

            $assignmentIds = SubjectAssignment::query()
                ->where('section_id', $section->id)
                ->pluck('id');
            if ($assignmentIds->isEmpty()) {
                continue;
            }

            $sectionHasRecords = FinalGrade::query()
                ->whereIn('subject_assignment_id', $assignmentIds->all())
                ->exists();
            if ($sectionHasRecords) {
                continue;
            }

            $identity = SeedNameBank::studentIdentity($baseIndex + $fallbackSequence);
            $student = Student::query()->create([
                'lrn' => $this->nextSyntheticHistoricalLrn((int) $academicYear->id, (int) $section->id, $fallbackSequence),
                'first_name' => $identity['student_first_name'],
                'middle_name' => $identity['student_middle_name'],
                'last_name' => $identity['student_last_name'],
                'gender' => $fallbackSequence % 2 === 0 ? 'Male' : 'Female',
                'birthdate' => CarbonImmutable::parse((string) $academicYear->start_date)
                    ->subYears(13 + ($fallbackSequence % 4))
                    ->toDateString(),
                'contact_number' => '+639'.str_pad((string) (915000000 + ($fallbackSequence % 99999999)), 9, '0', STR_PAD_LEFT),
                'address' => "{$section->name}, Quezon City",
                'guardian_name' => "{$identity['guardian_first_name']} {$identity['student_last_name']}",
                'is_lis_synced' => true,
                'sync_error_flag' => false,
            ]);

            $enrollment = Enrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'grade_level_id' => (int) $section->grade_level_id,
                    'section_id' => (int) $section->id,
                    'payment_term' => 'monthly',
                    'downpayment' => 3000,
                    'status' => 'enrolled',
                    'created_at' => $academicYear->start_date,
                ]
            );

            PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'school_name' => 'Marriott School',
                    'grade_level_id' => (int) $section->grade_level_id,
                    'general_average' => 85,
                    'status' => 'promoted',
                    'failed_subject_count' => 0,
                    'conditional_resolved_at' => null,
                    'conditional_resolution_notes' => null,
                    'remarks' => 'Seeded historical coverage record.',
                ]
            );

            $this->seedHistoricalFinalOutcomes($academicYear, $enrollment, false, $baseIndex + $fallbackSequence);
            $fallbackSequence++;
        }
    }

    private function nextSyntheticHistoricalLrn(int $academicYearId, int $sectionId, int $sequence): string
    {
        $candidateSequence = max(0, $sequence);

        do {
            $lrn = sprintf(
                '99%02d%03d%05d',
                $academicYearId % 100,
                $sectionId % 1000,
                $candidateSequence % 100000
            );
            $candidateSequence++;
        } while (Student::query()->where('lrn', $lrn)->exists());

        return $lrn;
    }

    private function seedHistoricalFinalOutcomes(
        AcademicYear $academicYear,
        Enrollment $enrollment,
        bool $hasConditionalOutcome,
        int $index
    ): void {
        $assignments = SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get();

        foreach ($assignments as $assignmentIndex => $assignment) {
            foreach (['1', '2', '3', '4'] as $quarter) {
                $baseGrade = $hasConditionalOutcome && $assignmentIndex === 0
                    ? 74
                    : 83 + (($index + $assignmentIndex + (int) $quarter) % 10);

                FinalGrade::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => $quarter,
                    ],
                    [
                        'grade' => $baseGrade,
                        'is_locked' => true,
                    ]
                );

                $assessmentBlueprint = [
                    ['type' => 'WW', 'title' => 'Historical Quiz 1', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Quiz 2', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Seatwork 1', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Seatwork 2', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Seatwork 3', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Assignment 1', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Assignment 2', 'max_score' => 20.0],
                    ['type' => 'WW', 'title' => 'Historical Assignment 3', 'max_score' => 20.0],
                    ['type' => 'PT', 'title' => 'Historical Performance Task 1', 'max_score' => 40.0],
                    ['type' => 'PT', 'title' => 'Historical Performance Task 2', 'max_score' => 40.0],
                    ['type' => 'QA', 'title' => 'Historical Quarterly Assessment', 'max_score' => 50.0],
                ];

                foreach ($assessmentBlueprint as $activityIndex => $assessmentRow) {
                    $activity = GradedActivity::query()->updateOrCreate(
                        [
                            'subject_assignment_id' => $assignment->id,
                            'quarter' => $quarter,
                            'type' => $assessmentRow['type'],
                            'title' => $assessmentRow['title'],
                        ],
                        [
                            'max_score' => $assessmentRow['max_score'],
                        ]
                    );

                    $variation = (($index + $assignmentIndex + $activityIndex + (int) $quarter) % 7) - 3;
                    $ratio = max(0.65, min(0.99, ($baseGrade + $variation) / 100));
                    $scoreValue = round((float) $assessmentRow['max_score'] * $ratio, 2);

                    StudentScore::query()->updateOrCreate(
                        [
                            'student_id' => $enrollment->student_id,
                            'graded_activity_id' => $activity->id,
                        ],
                        [
                            'score' => $scoreValue,
                        ]
                    );
                }

                GradeSubmission::withoutEvents(function () use ($academicYear, $assignment, $quarter): void {
                    GradeSubmission::query()->updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'subject_assignment_id' => $assignment->id,
                            'quarter' => $quarter,
                        ],
                        [
                            'status' => GradeSubmission::STATUS_VERIFIED,
                            'submitted_by' => null,
                            'submitted_at' => $academicYear->end_date,
                            'verified_by' => null,
                            'verified_at' => $academicYear->end_date,
                            'returned_by' => null,
                            'returned_at' => null,
                            'return_notes' => null,
                        ]
                    );
                });

                $attendanceDate = CarbonImmutable::parse((string) $academicYear->start_date)
                    ->addDays((($assignmentIndex + (int) $quarter) * 14) % 120)
                    ->toDateString();
                if ($attendanceDate <= (string) $academicYear->end_date) {
                    Attendance::query()->updateOrCreate(
                        [
                            'subject_assignment_id' => $assignment->id,
                            'enrollment_id' => $enrollment->id,
                            'date' => $attendanceDate,
                        ],
                        [
                            'status' => Attendance::STATUS_PRESENT,
                            'remarks' => 'Seeded historical attendance.',
                        ]
                    );
                }
            }

            FinalGrade::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => 'final',
                ],
                [
                    'grade' => $hasConditionalOutcome && $assignmentIndex === 0 ? 74 : 85 + (($index + $assignmentIndex) % 8),
                    'is_locked' => true,
                ]
            );
        }

        foreach (['1', '2', '3', '4'] as $quarter) {
            ConductRating::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'quarter' => $quarter,
                ],
                [
                    'maka_diyos' => 'AO',
                    'makatao' => 'AO',
                    'makakalikasan' => 'AO',
                    'makabansa' => 'AO',
                    'remarks' => 'Seeded historical conduct rating.',
                    'is_locked' => true,
                ]
            );
        }

        AcademicYear::query()->whereKey($academicYear->id)->update([
            'status' => 'completed',
            'current_quarter' => '4',
        ]);
    }

    private function enforceCurrentYearQuarterOneSubmissionsOnly(): void
    {
        $currentYear = AcademicYear::query()->where('name', '2025-2026')->first();
        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        GradeSubmission::query()
            ->where('academic_year_id', $currentYear->id)
            ->where('quarter', '!=', '1')
            ->delete();
    }

    private function resolveGradeOrder(int $gradeLevelId): ?int
    {
        return GradeLevel::query()
            ->whereKey($gradeLevelId)
            ->value('level_order');
    }

    private function enforceSeededAccountPasswords(): void
    {
        User::query()->update([
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * @return array{student_first_name: string, student_last_name: string, guardian_first_name: string}
     */
    private function studentNameSetForIndex(int $index): array
    {
        return SeedNameBank::studentIdentity($index);
    }
}
