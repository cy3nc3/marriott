<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\PermanentRecord;
use App\Models\RemedialCase;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionEndOfYearStageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionOngoingClassesStageSeeder::class);

        $academicYear = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();

        $this->seedFinalOutcomes($academicYear);

        $academicYear->update([
            'status' => 'completed',
            'current_quarter' => '4',
        ]);
    }

    private function seedFinalOutcomes(AcademicYear $academicYear): void
    {
        $registrar = User::query()->where('role', UserRole::REGISTRAR)->first();

        $enrollments = Enrollment::query()
            ->with('student')
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'enrolled')
            ->whereHas('student', fn ($query) => $query->where('lrn', 'like', '100000%'))
            ->orderBy('student_id')
            ->limit(24)
            ->get();

        foreach ($enrollments as $index => $enrollment) {
            $assignments = SubjectAssignment::query()
                ->where('section_id', $enrollment->section_id)
                ->orderBy('id')
                ->limit(3)
                ->get();

            $hasRemedialOutcome = $index < 3;

            foreach ($assignments as $assignmentIndex => $assignment) {
                FinalGrade::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => 'final',
                    ],
                    [
                        'grade' => $hasRemedialOutcome && $assignmentIndex === 0 ? 73 : 84 + (($index + $assignmentIndex) % 12),
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
                        'makatao' => 'SO',
                        'makakalikasan' => 'AO',
                        'makabansa' => 'AO',
                        'remarks' => $hasRemedialOutcome ? 'Needs academic follow-through.' : 'Meets expected behavior.',
                        'is_locked' => true,
                    ]
                );
            }

            PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $enrollment->student_id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'school_name' => 'Marriott School',
                    'grade_level_id' => $enrollment->grade_level_id,
                    'general_average' => $hasRemedialOutcome ? 76 : 86 + ($index % 9),
                    'status' => $hasRemedialOutcome ? 'conditional' : 'promoted',
                    'failed_subject_count' => $hasRemedialOutcome ? 1 : 0,
                    'conditional_resolved_at' => null,
                    'conditional_resolution_notes' => null,
                    'remarks' => $hasRemedialOutcome
                        ? 'For remedial class before promotion finalization.'
                        : 'Promoted to the next grade level.',
                ]
            );

            if ($hasRemedialOutcome) {
                RemedialCase::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'created_by' => $registrar?->id,
                        'failed_subject_count' => 1,
                        'fee_per_subject' => 750,
                        'total_amount' => 750,
                        'amount_paid' => 0,
                        'status' => 'for_cashier_payment',
                        'paid_at' => null,
                        'notes' => 'Seeded end-of-year remedial case.',
                    ]
                );
            }
        }
    }
}
