<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProductionThreeYearSnapshotSeeder extends Seeder
{
    private const COMPLETED_SCHOOL_YEARS = ['2023-2024', '2024-2025'];

    private const FILIPINO_FIRST_NAMES = [
        'Althea', 'Bianca', 'Carla', 'Danica', 'Elaine', 'Francesca', 'Gian', 'Hazel', 'Iana', 'Jessa',
        'Katrina', 'Lance', 'Mariel', 'Nico', 'Patricia', 'Rafael', 'Shaina', 'Trisha', 'Vince', 'Yvonne',
    ];

    private const FILIPINO_LAST_NAMES = [
        'Abella', 'Bautista', 'Castillo', 'Domingo', 'Enriquez', 'Flores', 'Gutierrez', 'Hernandez', 'Ibarra', 'Lorenzo',
        'Magsino', 'Navales', 'Ortega', 'Padilla', 'Reyes', 'Salazar', 'Torres', 'Valdez', 'Yumul', 'Zarate',
    ];

    public function run(): void
    {
        $this->call(ProductionQuarterOneDayFifteenSeeder::class);

        $this->seedNamedStaffAccounts();
        $this->normalizeStudentLrnsToTwelveDigits();
        $this->enforceAcademicYearTimeline();
        $this->normalizeCurrentYearStudentNames();
        $this->seedHistoricalCoverageForCompletedYears();
        $this->enforceCurrentYearQuarterOneSubmissionsOnly();
    }

    private function seedNamedStaffAccounts(): void
    {
        $namedStaffBlueprint = [
            'admin@marriott.edu' => ['first_name' => 'Alex', 'last_name' => 'Avellanosa', 'role' => UserRole::ADMIN],
            'registrar@marriott.edu' => ['first_name' => 'Jocelyn', 'last_name' => 'Cleofe', 'role' => UserRole::REGISTRAR],
            'finance@marriott.edu' => ['first_name' => 'Corrine', 'last_name' => 'Avellanosa', 'role' => UserRole::FINANCE],
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

    private function normalizeCurrentYearStudentNames(): void
    {
        $currentYear = AcademicYear::query()->where('name', '2025-2026')->first();
        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        Enrollment::query()
            ->with(['student.user'])
            ->where('academic_year_id', $currentYear->id)
            ->orderBy('section_id')
            ->orderBy('student_id')
            ->get()
            ->values()
            ->each(function (Enrollment $enrollment, int $index): void {
                $student = $enrollment->student;
                if (! $student instanceof Student) {
                    return;
                }

                $firstName = self::FILIPINO_FIRST_NAMES[$index % count(self::FILIPINO_FIRST_NAMES)];
                $lastName = self::FILIPINO_LAST_NAMES[($index * 3) % count(self::FILIPINO_LAST_NAMES)];
                $middleInitial = chr(65 + ($index % 26));

                $student->update([
                    'first_name' => $firstName,
                    'middle_name' => "{$middleInitial}.",
                    'last_name' => $lastName,
                    'guardian_name' => "Parent {$lastName}",
                ]);

                $student->user?->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => trim("{$firstName} {$lastName}"),
                ]);
            });
    }

    private function seedHistoricalCoverageForCompletedYears(): void
    {
        $currentYear = AcademicYear::query()->where('name', '2025-2026')->first();
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

            $historicalLimit = $schoolYearName === '2023-2024' ? 75 : 100;
            $this->seedHistoricalForYear($academicYear, $currentEnrollments->take($historicalLimit));
        }
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

            /** @var Section|null $targetSection */
            $targetSection = $sectionsByGrade->get($targetGradeOrder)?->sortBy('name')->first();
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

    private function seedHistoricalFinalOutcomes(
        AcademicYear $academicYear,
        Enrollment $enrollment,
        bool $hasConditionalOutcome,
        int $index
    ): void {
        $assignments = SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->limit(3)
            ->get();

        foreach ($assignments as $assignmentIndex => $assignment) {
            $baseGrade = $hasConditionalOutcome && $assignmentIndex === 0 ? 74 : 83 + (($index + $assignmentIndex) % 10);

            FinalGrade::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => 'final',
                ],
                [
                    'grade' => $baseGrade,
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
}
