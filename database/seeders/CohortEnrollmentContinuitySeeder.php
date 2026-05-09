<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedNameBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CohortEnrollmentContinuitySeeder extends Seeder
{
    private const SCHOOL_YEARS = ['2023-2024', '2024-2025', '2025-2026'];

    private const CURRENT_YEAR = '2025-2026';

    private const EXTERNAL_SCHOOL_NAMES = [
        'St. Bernadette Academy',
        'Quezon City Learning Center',
        'Northfields Integrated School',
        'San Isidro Catholic School',
        'Golden Horizon Academy',
        'Bayanihan Learning Academy',
    ];

    private string $passwordHash = '';

    public function run(): void
    {
        $this->passwordHash = Hash::make('password');

        $this->clearExistingStudentData();

        $years = AcademicYear::query()
            ->whereIn('name', self::SCHOOL_YEARS)
            ->orderBy('start_date')
            ->get()
            ->keyBy('name');

        $gradeLevels = GradeLevel::query()
            ->get()
            ->keyBy(fn (GradeLevel $gradeLevel): int => (int) $gradeLevel->level_order);

        $previousStudentsByGrade = [];
        $globalStudentIndex = 0;

        foreach (self::SCHOOL_YEARS as $yearIndex => $schoolYearName) {
            $academicYear = $years->get($schoolYearName);
            if (! $academicYear instanceof AcademicYear) {
                continue;
            }

            $studentsByGrade = $yearIndex === 0
                ? $this->buildOpeningYearStudents($schoolYearName, $globalStudentIndex)
                : $this->buildNextYearStudents($schoolYearName, $yearIndex, $previousStudentsByGrade, $globalStudentIndex);

            foreach ([7, 8, 9, 10] as $gradeOrder) {
                $gradeLevel = $gradeLevels->get($gradeOrder);
                if (! $gradeLevel instanceof GradeLevel) {
                    continue;
                }

                $sections = $this->sectionsForGrade($academicYear, $gradeOrder);
                if ($sections->isEmpty()) {
                    continue;
                }

                foreach (array_values($studentsByGrade[$gradeOrder] ?? []) as $studentIndex => $student) {
                    $section = $sections[$studentIndex % $sections->count()];
                    $enrollment = $this->createEnrollment($student, $academicYear, $gradeLevel, $section, $studentIndex);

                    if ($schoolYearName !== self::CURRENT_YEAR) {
                        $this->seedPermanentRecord($academicYear, $enrollment, $gradeOrder, $studentIndex);
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

    private function clearExistingStudentData(): void
    {
        DB::table('students')->delete();

        User::query()
            ->whereIn('role', [UserRole::STUDENT, UserRole::PARENT])
            ->delete();
    }

    /**
     * @return array<int, array<int, Student>>
     */
    private function buildOpeningYearStudents(string $schoolYearName, int &$globalStudentIndex): array
    {
        return [
            7 => $this->createStudents($schoolYearName, 7, 50, $globalStudentIndex),
            8 => $this->createStudents($schoolYearName, 8, 50, $globalStudentIndex),
            9 => $this->createStudents($schoolYearName, 9, 50, $globalStudentIndex),
            10 => $this->createStudents($schoolYearName, 10, 50, $globalStudentIndex),
        ];
    }

    /**
     * @param  array<int, array<int, Student>>  $previousStudentsByGrade
     * @return array<int, array<int, Student>>
     */
    private function buildNextYearStudents(
        string $schoolYearName,
        int $yearIndex,
        array $previousStudentsByGrade,
        int &$globalStudentIndex
    ): array {
        $studentsByGrade = [
            7 => $this->createStudents($schoolYearName, 7, 50, $globalStudentIndex),
        ];

        foreach ([7, 8, 9] as $sourceGradeOrder) {
            $targetGradeOrder = $sourceGradeOrder + 1;
            $previousStudents = array_values($previousStudentsByGrade[$sourceGradeOrder] ?? []);
            $lossCount = $this->nonContinuingCount($yearIndex, $sourceGradeOrder);
            $continuingStudents = array_slice($previousStudents, 0, max(count($previousStudents) - $lossCount, 0));
            $transferees = $this->createStudents($schoolYearName, $targetGradeOrder, $lossCount, $globalStudentIndex, true);

            $studentsByGrade[$targetGradeOrder] = array_values([
                ...($studentsByGrade[$targetGradeOrder] ?? []),
                ...$continuingStudents,
                ...$transferees,
            ]);
        }

        return $studentsByGrade;
    }

    /**
     * @return array<int, Student>
     */
    private function createStudents(
        string $schoolYearName,
        int $gradeOrder,
        int $count,
        int &$globalStudentIndex,
        bool $isTransferee = false
    ): array {
        $students = [];

        for ($index = 0; $index < $count; $index++) {
            $seedIndex = $globalStudentIndex++;
            $identity = SeedNameBank::studentIdentity($seedIndex);
            $lrn = $this->lrn($schoolYearName, $gradeOrder, $seedIndex);
            $emailLastName = Str::of($identity['student_last_name'])
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '');
            $birthdate = CarbonImmutable::create(2016 - ($gradeOrder - 7), (($seedIndex % 12) + 1), (($seedIndex % 27) + 1))->toDateString();

            $studentUser = User::query()->create([
                'first_name' => $identity['student_first_name'],
                'last_name' => $identity['student_last_name'],
                'name' => "{$identity['student_first_name']} {$identity['student_last_name']}",
                'email' => "{$emailLastName}.{$lrn}@marriott.edu",
                'password' => $this->passwordHash,
                'birthday' => $birthdate,
                'role' => UserRole::STUDENT,
                'is_active' => true,
                'must_change_password' => true,
                'password_updated_at' => now(),
            ]);

            $parentUser = User::query()->create([
                'first_name' => $identity['guardian_first_name'],
                'last_name' => $identity['student_last_name'],
                'name' => "{$identity['guardian_first_name']} {$identity['student_last_name']}",
                'email' => "parent.{$lrn}@marriott.edu",
                'password' => $this->passwordHash,
                'birthday' => '1983-01-01',
                'role' => UserRole::PARENT,
                'is_active' => true,
                'must_change_password' => true,
                'password_updated_at' => now(),
            ]);

            $student = Student::query()->create([
                'user_id' => $studentUser->id,
                'lrn' => $lrn,
                'first_name' => $identity['student_first_name'],
                'middle_name' => $identity['student_middle_name'],
                'last_name' => $identity['student_last_name'],
                'gender' => $seedIndex % 2 === 0 ? 'Male' : 'Female',
                'birthdate' => $birthdate,
                'contact_number' => '+639'.substr($lrn, 0, 9),
                'address' => $isTransferee
                    ? self::EXTERNAL_SCHOOL_NAMES[$seedIndex % count(self::EXTERNAL_SCHOOL_NAMES)].', Quezon City'
                    : 'San Francisco Del Monte, Quezon City',
                'guardian_name' => "{$identity['guardian_first_name']} {$identity['student_last_name']}",
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

    private function createEnrollment(
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

    private function seedPermanentRecord(AcademicYear $academicYear, Enrollment $enrollment, int $gradeOrder, int $index): void
    {
        PermanentRecord::query()->create([
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $academicYear->id,
            'school_name' => 'Marriott School',
            'grade_level_id' => $enrollment->grade_level_id,
            'general_average' => 84 + ($index % 10),
            'status' => $gradeOrder === 10 ? 'completed' : 'promoted',
            'failed_subject_count' => 0,
            'remarks' => $gradeOrder === 10
                ? 'Seeded completed terminal grade level.'
                : 'Seeded promoted completion record.',
        ]);
    }

    private function seedCompletedYearGradesAndAttendance(AcademicYear $academicYear, Enrollment $enrollment, int $index): void
    {
        $assignments = SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get();

        foreach ($assignments as $assignmentIndex => $assignment) {
            foreach (['1', '2', '3', '4', 'final'] as $quarter) {
                FinalGrade::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => $quarter,
                    'grade' => 84 + (($index + $assignmentIndex) % 10),
                    'is_locked' => true,
                ]);
            }

            Attendance::query()->create([
                'subject_assignment_id' => $assignment->id,
                'enrollment_id' => $enrollment->id,
                'date' => CarbonImmutable::parse((string) $academicYear->start_date)
                    ->addDays(($assignmentIndex * 7) % 90)
                    ->toDateString(),
                'status' => Attendance::STATUS_PRESENT,
                'remarks' => 'Seeded cohort attendance.',
            ]);
        }
    }

    private function seedCurrentYearGrades(AcademicYear $academicYear, Enrollment $enrollment, int $index): void
    {
        SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get()
            ->each(function (SubjectAssignment $assignment, int $assignmentIndex) use ($enrollment, $index): void {
                FinalGrade::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => '1',
                    'grade' => 84 + (($index + $assignmentIndex) % 9),
                    'is_locked' => false,
                ]);
            });
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

    private function nonContinuingCount(int $yearIndex, int $sourceGradeOrder): int
    {
        return 1 + (($yearIndex + $sourceGradeOrder) % 2);
    }

    private function lrn(string $schoolYearName, int $gradeOrder, int $seedIndex): string
    {
        $yearToken = substr(str_replace('-', '', $schoolYearName), 2, 4);

        return sprintf('%s%02d%06d', $yearToken, $gradeOrder, $seedIndex + 1);
    }
}
