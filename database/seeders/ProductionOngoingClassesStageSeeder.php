<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\GradedActivity;
use App\Models\GradeSubmission;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductionOngoingClassesStageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionEnrollmentStageSeeder::class);

        $academicYear = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
        $academicYear->update([
            'status' => 'ongoing',
            'current_quarter' => '2',
        ]);

        Enrollment::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['for_cashier_payment', 'enrolled'])
            ->whereHas('student', fn ($query) => $query->where('lrn', 'like', '100000%'))
            ->update(['status' => 'enrolled']);

        $this->seedAttendanceAndScores($academicYear);
    }

    private function seedAttendanceAndScores(AcademicYear $academicYear): void
    {
        $enrollments = Enrollment::query()
            ->with(['section', 'student'])
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'enrolled')
            ->whereHas('student', fn ($query) => $query->where('lrn', 'like', '100000%'))
            ->orderBy('student_id')
            ->limit(24)
            ->get();

        foreach ($enrollments as $enrollmentIndex => $enrollment) {
            $assignments = SubjectAssignment::query()
                ->where('section_id', $enrollment->section_id)
                ->orderBy('id')
                ->limit(3)
                ->get();

            foreach ($assignments as $assignmentIndex => $assignment) {
                $date = Carbon::parse((string) $academicYear->start_date)
                    ->addWeeks($assignmentIndex + 1)
                    ->addDays($enrollmentIndex % 5)
                    ->toDateString();

                Attendance::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'date' => $date,
                    ],
                    [
                        'status' => $this->attendanceStatus($enrollmentIndex, $assignmentIndex),
                        'remarks' => $enrollmentIndex % 9 === 0 ? 'Seeded attendance variance.' : null,
                    ]
                );

                $activity = GradedActivity::query()->updateOrCreate(
                    [
                        'subject_assignment_id' => $assignment->id,
                        'type' => $assignmentIndex === 2 ? 'PT' : 'WW',
                        'quarter' => '1',
                        'title' => 'Quarter 1 Demo Activity '.($assignmentIndex + 1),
                    ],
                    [
                        'max_score' => 100,
                    ]
                );

                StudentScore::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'graded_activity_id' => $activity->id,
                    ],
                    [
                        'score' => 78 + (($enrollmentIndex + $assignmentIndex) % 20),
                    ]
                );

                GradeSubmission::query()->updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => '1',
                    ],
                    [
                        'status' => $assignmentIndex === 0 ? GradeSubmission::STATUS_SUBMITTED : GradeSubmission::STATUS_DRAFT,
                        'submitted_by' => $assignment->teacherSubject?->teacher_id,
                        'submitted_at' => $assignmentIndex === 0 ? now()->subDays(3) : null,
                    ]
                );
            }
        }
    }

    private function attendanceStatus(int $enrollmentIndex, int $assignmentIndex): string
    {
        if (($enrollmentIndex + $assignmentIndex) % 11 === 0) {
            return Attendance::STATUS_ABSENT;
        }

        if (($enrollmentIndex + $assignmentIndex) % 7 === 0) {
            return Attendance::STATUS_TARDY_LATE_COMER;
        }

        return Attendance::STATUS_PRESENT;
    }
}
