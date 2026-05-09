<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeSubmission;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedNameBank;
use Illuminate\Database\Seeder;

class BackfillHistoricalSectionRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $years = AcademicYear::query()
            ->whereIn('name', ['2023-2024', '2024-2025'])
            ->get();

        $sequence = 0;

        foreach ($years as $year) {
            $sections = Section::query()
                ->where('academic_year_id', $year->id)
                ->orderBy('grade_level_id')
                ->orderBy('name')
                ->get();

            foreach ($sections as $section) {
                $assignmentIds = SubjectAssignment::query()
                    ->where('section_id', $section->id)
                    ->pluck('id');

                if ($assignmentIds->isEmpty()) {
                    continue;
                }

                $hasSectionRecords = FinalGrade::query()
                    ->whereIn('subject_assignment_id', $assignmentIds->all())
                    ->exists();

                if ($hasSectionRecords) {
                    continue;
                }

                $identity = SeedNameBank::studentIdentity((int) $year->id * 10000 + $sequence);
                $student = Student::query()->create([
                    'lrn' => $this->nextSyntheticLrn((int) $year->id, (int) $section->id, $sequence),
                    'first_name' => $identity['student_first_name'],
                    'middle_name' => $identity['student_middle_name'],
                    'last_name' => $identity['student_last_name'],
                    'gender' => $sequence % 2 === 0 ? 'Male' : 'Female',
                    'birthdate' => CarbonImmutable::parse((string) $year->start_date)->subYears(14)->toDateString(),
                    'contact_number' => '+639'.str_pad((string) (910000000 + ($sequence % 99999999)), 9, '0', STR_PAD_LEFT),
                    'address' => "{$section->name}, Quezon City",
                    'guardian_name' => "{$identity['guardian_first_name']} {$identity['student_last_name']}",
                    'is_lis_synced' => true,
                    'sync_error_flag' => false,
                ]);

                $enrollment = Enrollment::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $year->id,
                    ],
                    [
                        'grade_level_id' => $section->grade_level_id,
                        'section_id' => $section->id,
                        'payment_term' => 'monthly',
                        'downpayment' => 3000,
                        'status' => 'enrolled',
                    ]
                );

                PermanentRecord::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $year->id,
                    ],
                    [
                        'school_name' => 'Marriott School',
                        'grade_level_id' => $section->grade_level_id,
                        'general_average' => 85,
                        'status' => 'promoted',
                        'failed_subject_count' => 0,
                        'remarks' => 'Backfilled historical section coverage record.',
                    ]
                );

                $this->seedRecords($year, $enrollment, $sequence);
                $sequence++;
            }
        }
    }

    private function seedRecords(AcademicYear $year, Enrollment $enrollment, int $seed): void
    {
        $assignments = SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get();

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

        foreach ($assignments as $assignmentIndex => $assignment) {
            foreach (['1', '2', '3', '4'] as $quarter) {
                $gradeValue = 84 + (($seed + $assignmentIndex + (int) $quarter) % 7);

                FinalGrade::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => $quarter,
                    ],
                    [
                        'grade' => $gradeValue,
                        'is_locked' => true,
                    ]
                );

                foreach ($assessmentBlueprint as $activityIndex => $assessment) {
                    $activity = GradedActivity::query()->updateOrCreate(
                        [
                            'subject_assignment_id' => $assignment->id,
                            'quarter' => $quarter,
                            'type' => $assessment['type'],
                            'title' => $assessment['title'],
                        ],
                        [
                            'max_score' => $assessment['max_score'],
                        ]
                    );

                    $scoreRatio = 0.8 + ((($seed + $assignmentIndex + $activityIndex) % 6) * 0.02);
                    StudentScore::query()->updateOrCreate(
                        [
                            'student_id' => $enrollment->student_id,
                            'graded_activity_id' => $activity->id,
                        ],
                        [
                            'score' => round((float) $assessment['max_score'] * $scoreRatio, 2),
                        ]
                    );
                }

                GradeSubmission::query()->updateOrCreate(
                    [
                        'academic_year_id' => $year->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => $quarter,
                    ],
                    [
                        'status' => GradeSubmission::STATUS_VERIFIED,
                        'submitted_at' => $year->end_date,
                        'verified_at' => $year->end_date,
                    ]
                );

                Attendance::query()->updateOrCreate(
                    [
                        'subject_assignment_id' => $assignment->id,
                        'enrollment_id' => $enrollment->id,
                        'date' => CarbonImmutable::parse((string) $year->start_date)
                            ->addDays((($assignmentIndex + (int) $quarter) * 7) % 90)
                            ->toDateString(),
                    ],
                    [
                        'status' => Attendance::STATUS_PRESENT,
                        'remarks' => 'Backfilled historical attendance.',
                    ]
                );
            }

            FinalGrade::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => 'final',
                ],
                [
                    'grade' => 86,
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
                    'remarks' => 'Backfilled historical conduct rating.',
                    'is_locked' => true,
                ]
            );
        }
    }

    private function nextSyntheticLrn(int $yearId, int $sectionId, int $sequence): string
    {
        $candidate = max(0, $sequence);

        do {
            $lrn = sprintf('98%02d%03d%05d', $yearId % 100, $sectionId % 1000, $candidate % 100000);
            $candidate++;
        } while (Student::query()->where('lrn', $lrn)->exists());

        return $lrn;
    }
}

