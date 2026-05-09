<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeSubmission;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CohortAcademicRecordsSeeder extends Seeder
{
    private const HISTORICAL_YEARS = ['2023-2024', '2024-2025'];

    public function run(): void
    {
        StudentScore::query()->delete();
        GradedActivity::query()->delete();
        FinalGrade::query()->delete();
        Attendance::query()->delete();
        GradeSubmission::withoutEvents(fn (): int => GradeSubmission::query()->delete());

        $years = AcademicYear::query()
            ->whereIn('name', self::HISTORICAL_YEARS)
            ->orderBy('start_date')
            ->get();

        foreach ($years as $academicYear) {
            if (! $academicYear instanceof AcademicYear) {
                continue;
            }

            $this->seedHistoricalYear($academicYear);
        }
    }

    private function seedHistoricalYear(AcademicYear $academicYear): void
    {
        $attendanceDates = $this->attendanceDatesForYear($academicYear);
        $assignments = SubjectAssignment::query()
            ->whereHas('section', fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderBy('section_id')
            ->orderBy('id')
            ->get();

        foreach ($assignments as $assignmentIndex => $assignment) {
            $enrollments = Enrollment::query()
                ->where('academic_year_id', $academicYear->id)
                ->where('section_id', $assignment->section_id)
                ->where('status', 'enrolled')
                ->orderBy('student_id')
                ->get();

            $finalGradeRows = [];
            $scoreRows = [];
            $attendanceRows = [];
            $now = now();

            foreach (['1', '2', '3', '4'] as $quarter) {
                $activities = $this->seedActivities($assignment, $quarter);

                foreach ($enrollments as $studentIndex => $enrollment) {
                    $baseGrade = 84 + (($studentIndex + $assignmentIndex + (int) $quarter) % 10);

                    $finalGradeRows[] = [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => $quarter,
                        'grade' => $baseGrade,
                        'is_locked' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    foreach ($activities as $activityIndex => $activity) {
                        $ratio = max(0.68, min(0.99, ($baseGrade + (($activityIndex % 5) - 2)) / 100));
                        $scoreRows[] = [
                            'student_id' => $enrollment->student_id,
                            'graded_activity_id' => $activity->id,
                            'score' => round((float) $activity->max_score * $ratio, 2),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                GradeSubmission::withoutEvents(function () use ($academicYear, $assignment, $quarter): void {
                    GradeSubmission::query()->create([
                        'academic_year_id' => $academicYear->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => $quarter,
                        'status' => GradeSubmission::STATUS_VERIFIED,
                        'submitted_by' => null,
                        'submitted_at' => $academicYear->end_date,
                        'verified_by' => null,
                        'verified_at' => $academicYear->end_date,
                        'returned_by' => null,
                        'returned_at' => null,
                        'return_notes' => null,
                    ]);
                });
            }

            foreach ($enrollments as $studentIndex => $enrollment) {
                $finalGradeRows[] = [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => 'final',
                    'grade' => 85 + (($studentIndex + $assignmentIndex) % 8),
                    'is_locked' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                foreach ($attendanceDates as $dateIndex => $attendanceDate) {
                    $attendanceRows[] = [
                        'subject_assignment_id' => $assignment->id,
                        'enrollment_id' => $enrollment->id,
                        'date' => $attendanceDate,
                        'status' => $this->attendanceStatus($studentIndex, $assignmentIndex, $dateIndex),
                        'remarks' => 'Seeded cohort historical attendance.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($finalGradeRows, 1000) as $chunk) {
                DB::table('final_grades')->insert($chunk);
            }

            foreach (array_chunk($scoreRows, 1000) as $chunk) {
                DB::table('student_scores')->insert($chunk);
            }

            foreach (array_chunk($attendanceRows, 1000) as $chunk) {
                DB::table('attendances')->insert($chunk);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function attendanceDatesForYear(AcademicYear $academicYear): array
    {
        $startYear = CarbonImmutable::parse((string) $academicYear->start_date)->year;
        $endYear = CarbonImmutable::parse((string) $academicYear->end_date)->year;
        $cursor = CarbonImmutable::create($startYear, 6, 1);
        $end = CarbonImmutable::create($endYear, 3, 30);
        $dates = [];

        while ($cursor <= $end) {
            if (! $cursor->isWeekend()) {
                $dates[] = $cursor->toDateString();
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private function attendanceStatus(int $studentIndex, int $assignmentIndex, int $dateIndex): string
    {
        $seed = ($studentIndex + 1) * 17 + ($assignmentIndex + 1) * 13 + ($dateIndex + 1);

        if ($seed % 97 === 0) {
            return Attendance::STATUS_TARDY_CUTTING_CLASSES;
        }

        if ($seed % 53 === 0) {
            return Attendance::STATUS_TARDY_LATE_COMER;
        }

        if ($seed % 29 === 0) {
            return Attendance::STATUS_ABSENT;
        }

        return Attendance::STATUS_PRESENT;
    }

    /**
     * @return array<int, GradedActivity>
     */
    private function seedActivities(SubjectAssignment $assignment, string $quarter): array
    {
        $activities = [];

        foreach ([
            ['type' => 'WW', 'title' => 'Historical Quiz 1', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Quiz 2', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Seatwork 1', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Seatwork 2', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Seatwork 3', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Assignment 1', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Assignment 2', 'max_score' => 20],
            ['type' => 'WW', 'title' => 'Historical Assignment 3', 'max_score' => 20],
            ['type' => 'PT', 'title' => 'Historical Performance Task 1', 'max_score' => 40],
            ['type' => 'PT', 'title' => 'Historical Performance Task 2', 'max_score' => 40],
            ['type' => 'QA', 'title' => 'Historical Quarterly Assessment', 'max_score' => 50],
        ] as $row) {
            $activities[] = GradedActivity::query()->create([
                'subject_assignment_id' => $assignment->id,
                'quarter' => $quarter,
                'type' => $row['type'],
                'title' => $row['title'],
                'max_score' => $row['max_score'],
            ]);
        }

        return $activities;
    }
}
