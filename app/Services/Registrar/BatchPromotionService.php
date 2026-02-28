<?php

namespace App\Services\Registrar;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BatchPromotionService
{
    /**
     * @return array{
     *     source_year: array{id: int, name: string},
     *     target_year: array{id: int, name: string},
     *     run_at: string,
     *     processed_learners: int,
     *     promoted: int,
     *     conditional: int,
     *     retained: int,
     *     completed: int,
     *     conflicts: int,
     *     conflicts_list: array<int, array<string, mixed>>,
     *     conditional_queue: array<int, array<string, mixed>>,
     *     held_for_review_queue: array<int, array<string, mixed>>,
     *     grade_completeness_issues: array<int, array<string, mixed>>,
     *     grade_completeness_issue_count: int,
     *     held_for_review_count: int
     * }
     */
    public function run(AcademicYear $sourceAcademicYear, AcademicYear $targetAcademicYear, ?User $processedBy = null): array
    {
        return DB::transaction(function () use ($sourceAcademicYear, $targetAcademicYear, $processedBy): array {
            $sourceEnrollments = Enrollment::query()
                ->with([
                    'student:id,lrn,first_name,last_name',
                    'gradeLevel:id,name,level_order',
                ])
                ->where('academic_year_id', $sourceAcademicYear->id)
                ->where('status', 'enrolled')
                ->orderBy('id')
                ->get();

            $summary = [
                'source_year' => [
                    'id' => (int) $sourceAcademicYear->id,
                    'name' => $sourceAcademicYear->name,
                ],
                'target_year' => [
                    'id' => (int) $targetAcademicYear->id,
                    'name' => $targetAcademicYear->name,
                ],
                'run_at' => now()->toIso8601String(),
                'processed_learners' => 0,
                'promoted' => 0,
                'conditional' => 0,
                'retained' => 0,
                'completed' => 0,
                'conflicts' => 0,
                'conflicts_list' => [],
                'conditional_queue' => [],
                'held_for_review_queue' => [],
                'grade_completeness_issues' => [],
                'grade_completeness_issue_count' => 0,
                'held_for_review_count' => 0,
            ];

            $processableLearners = [];

            foreach ($sourceEnrollments as $sourceEnrollment) {
                $student = $sourceEnrollment->student;
                if (! $student) {
                    continue;
                }

                $annualGradeResult = $this->computeAnnualSubjectGrades($sourceEnrollment);
                if ($annualGradeResult['has_issue']) {
                    $summary['grade_completeness_issues'][] = [
                        'student_id' => (int) $student->id,
                        'student_name' => trim("{$student->first_name} {$student->last_name}"),
                        'lrn' => $student->lrn,
                        'issue' => $annualGradeResult['issue'],
                    ];

                    continue;
                }

                $annualSubjectGrades = collect($annualGradeResult['annual_subject_grades']);
                $failedSubjectCount = (int) $annualSubjectGrades
                    ->filter(fn (float $grade): bool => $grade < 75)
                    ->count();

                $generalAverage = $annualSubjectGrades->isNotEmpty()
                    ? round((float) $annualSubjectGrades->avg(), 2)
                    : null;

                $classification = $this->classifyLearner($failedSubjectCount);
                $finalStatus = $this->isTerminalGradeLevel($sourceEnrollment)
                    ? 'completed'
                    : $classification;

                $targetGradeLevelId = null;
                if ($finalStatus !== 'completed') {
                    $targetGradeLevelId = $this->resolveTargetGradeLevelId($sourceEnrollment, $finalStatus);

                    if ($targetGradeLevelId === null) {
                        $summary['grade_completeness_issues'][] = [
                            'student_id' => (int) $student->id,
                            'student_name' => trim("{$student->first_name} {$student->last_name}"),
                            'lrn' => $student->lrn,
                            'issue' => 'Target grade level could not be resolved.',
                        ];

                        continue;
                    }
                }

                $processableLearners[] = [
                    'student_id' => (int) $student->id,
                    'student_name' => trim("{$student->first_name} {$student->last_name}"),
                    'lrn' => $student->lrn,
                    'source_enrollment' => $sourceEnrollment,
                    'final_status' => $finalStatus,
                    'failed_subject_count' => $failedSubjectCount,
                    'general_average' => $generalAverage,
                    'target_grade_level_id' => $targetGradeLevelId,
                ];
            }

            $summary['grade_completeness_issue_count'] = count($summary['grade_completeness_issues']);
            $summary['held_for_review_queue'] = $this->buildHeldForReviewQueue($sourceAcademicYear);
            $summary['held_for_review_count'] = count($summary['held_for_review_queue']);

            if ($summary['grade_completeness_issue_count'] > 0) {
                Setting::set('registrar_batch_promotion_last_run', json_encode($summary), 'registrar');
                Setting::set('registrar_batch_promotion_last_run_by', $processedBy?->id, 'registrar');

                return $summary;
            }

            foreach ($processableLearners as $learnerPayload) {
                /** @var Enrollment $sourceEnrollment */
                $sourceEnrollment = $learnerPayload['source_enrollment'];
                $finalStatus = $learnerPayload['final_status'];

                $permanentRecord = PermanentRecord::query()->updateOrCreate(
                    [
                        'student_id' => $learnerPayload['student_id'],
                        'academic_year_id' => $sourceAcademicYear->id,
                    ],
                    [
                        'school_name' => 'Marriott School',
                        'grade_level_id' => $sourceEnrollment->grade_level_id,
                        'general_average' => $learnerPayload['general_average'],
                        'status' => $finalStatus,
                        'failed_subject_count' => $learnerPayload['failed_subject_count'],
                        'conditional_resolved_at' => null,
                        'conditional_resolution_notes' => null,
                        'remarks' => $this->buildStatusRemarks($finalStatus, $learnerPayload['failed_subject_count']),
                    ]
                );

                if ($finalStatus === 'promoted') {
                    $summary['promoted']++;
                } elseif ($finalStatus === 'conditional') {
                    $summary['conditional']++;
                } elseif ($finalStatus === 'retained') {
                    $summary['retained']++;
                } else {
                    $summary['completed']++;
                }

                if ($finalStatus !== 'completed' && $learnerPayload['target_grade_level_id'] !== null) {
                    $nextEnrollment = Enrollment::query()
                        ->where('student_id', $learnerPayload['student_id'])
                        ->where('academic_year_id', $targetAcademicYear->id)
                        ->orderByDesc('id')
                        ->first();

                    if ($nextEnrollment && $nextEnrollment->status === 'enrolled') {
                        $summary['conflicts']++;
                        $summary['conflicts_list'][] = [
                            'student_id' => $learnerPayload['student_id'],
                            'student_name' => $learnerPayload['student_name'],
                            'lrn' => $learnerPayload['lrn'],
                            'reason' => 'Target school year enrollment already finalized.',
                        ];
                    } elseif ($nextEnrollment) {
                        $nextEnrollment->update([
                            'grade_level_id' => $learnerPayload['target_grade_level_id'],
                            'section_id' => null,
                            'payment_term' => $sourceEnrollment->payment_term,
                            'downpayment' => 0,
                        ]);
                    } else {
                        Enrollment::query()->create([
                            'student_id' => $learnerPayload['student_id'],
                            'academic_year_id' => $targetAcademicYear->id,
                            'grade_level_id' => $learnerPayload['target_grade_level_id'],
                            'section_id' => null,
                            'payment_term' => $sourceEnrollment->payment_term,
                            'downpayment' => 0,
                            'status' => 'for_cashier_payment',
                        ]);
                    }
                }

                if ($finalStatus === 'conditional') {
                    $summary['conditional_queue'][] = [
                        'permanent_record_id' => (int) $permanentRecord->id,
                        'student_id' => $learnerPayload['student_id'],
                        'student_name' => $learnerPayload['student_name'],
                        'lrn' => $learnerPayload['lrn'],
                        'failed_subject_count' => $learnerPayload['failed_subject_count'],
                        'source_year' => $sourceAcademicYear->name,
                        'target_year' => $targetAcademicYear->name,
                    ];
                }

                $this->refreshStudentRemedialFlag($learnerPayload['student_id']);
                $summary['processed_learners']++;
            }

            Setting::set('registrar_batch_promotion_last_run', json_encode($summary), 'registrar');
            Setting::set('registrar_batch_promotion_last_run_by', $processedBy?->id, 'registrar');

            return $summary;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildHeldForReviewQueue(AcademicYear $referenceAcademicYear): array
    {
        return PermanentRecord::query()
            ->with([
                'student:id,first_name,last_name,lrn',
                'academicYear:id,name,start_date',
                'gradeLevel:id,name',
            ])
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->whereHas('academicYear', function ($query) use ($referenceAcademicYear) {
                $query->where('start_date', '<', $referenceAcademicYear->start_date);
            })
            ->orderBy('academic_year_id')
            ->orderBy('id')
            ->get()
            ->map(function (PermanentRecord $record): array {
                return [
                    'permanent_record_id' => (int) $record->id,
                    'student_id' => (int) $record->student_id,
                    'student_name' => trim("{$record->student?->first_name} {$record->student?->last_name}"),
                    'lrn' => $record->student?->lrn,
                    'school_year' => $record->academicYear?->name,
                    'grade_level' => $record->gradeLevel?->name,
                    'failed_subject_count' => (int) $record->failed_subject_count,
                    'recorded_at' => $record->created_at?->toDateString(),
                ];
            })
            ->values()
            ->all();
    }

    private function classifyLearner(int $failedSubjectCount): string
    {
        if ($failedSubjectCount <= 0) {
            return 'promoted';
        }

        if ($failedSubjectCount <= 2) {
            return 'conditional';
        }

        return 'retained';
    }

    private function isTerminalGradeLevel(Enrollment $sourceEnrollment): bool
    {
        $gradeLevel = $sourceEnrollment->gradeLevel
            ?? GradeLevel::query()->find($sourceEnrollment->grade_level_id);

        if (! $gradeLevel) {
            return true;
        }

        return ! GradeLevel::query()
            ->where('level_order', '>', $gradeLevel->level_order)
            ->exists();
    }

    private function resolveTargetGradeLevelId(Enrollment $sourceEnrollment, string $status): ?int
    {
        if ($status === 'retained') {
            return $sourceEnrollment->grade_level_id;
        }

        $gradeLevel = $sourceEnrollment->gradeLevel
            ?? GradeLevel::query()->find($sourceEnrollment->grade_level_id);

        if (! $gradeLevel) {
            return null;
        }

        return GradeLevel::query()
            ->where('level_order', '>', $gradeLevel->level_order)
            ->orderBy('level_order')
            ->value('id');
    }

    /**
     * @return array{
     *     has_issue: bool,
     *     issue: string,
     *     annual_subject_grades: array<int, float>
     * }
     */
    private function computeAnnualSubjectGrades(Enrollment $sourceEnrollment): array
    {
        if (! $sourceEnrollment->section_id) {
            return [
                'has_issue' => true,
                'issue' => 'Student section is not assigned.',
                'annual_subject_grades' => [],
            ];
        }

        $assignments = SubjectAssignment::query()
            ->with('teacherSubject.subject:id,subject_name')
            ->where('section_id', $sourceEnrollment->section_id)
            ->get(['id', 'teacher_subject_id']);

        if ($assignments->isEmpty()) {
            return [
                'has_issue' => true,
                'issue' => 'No subject assignments found for the student section.',
                'annual_subject_grades' => [],
            ];
        }

        $assignmentIds = $assignments->pluck('id');
        $grades = FinalGrade::query()
            ->where('enrollment_id', $sourceEnrollment->id)
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->whereIn('quarter', ['1', '2', '3', '4'])
            ->get(['subject_assignment_id', 'quarter', 'grade', 'is_locked']);

        $missingQuarterLabels = [];
        $unlockedQuarterLabels = [];
        $annualSubjectGrades = [];

        foreach ($assignments as $assignment) {
            $subjectName = $assignment->teacherSubject?->subject?->subject_name ?: "Subject {$assignment->id}";

            $quarterGrades = collect(['1', '2', '3', '4'])
                ->map(function (string $quarter) use ($grades, $assignment, $subjectName, &$missingQuarterLabels, &$unlockedQuarterLabels): ?float {
                    $gradeRow = $grades
                        ->first(function (FinalGrade $grade) use ($assignment, $quarter): bool {
                            return (int) $grade->subject_assignment_id === (int) $assignment->id
                                && (string) $grade->quarter === $quarter;
                        });

                    if (! $gradeRow) {
                        $missingQuarterLabels[] = "{$subjectName} (Q{$quarter})";

                        return null;
                    }

                    if (! $gradeRow->is_locked) {
                        $unlockedQuarterLabels[] = "{$subjectName} (Q{$quarter})";

                        return null;
                    }

                    return (float) $gradeRow->grade;
                })
                ->filter(fn (?float $grade): bool => $grade !== null)
                ->values();

            if ($quarterGrades->count() === 4) {
                $annualSubjectGrades[(int) $assignment->id] = round((float) $quarterGrades->avg(), 2);
            }
        }

        if ($missingQuarterLabels !== [] || $unlockedQuarterLabels !== []) {
            $parts = [];
            if ($missingQuarterLabels !== []) {
                $parts[] = 'Missing grades: '.implode(', ', $missingQuarterLabels);
            }

            if ($unlockedQuarterLabels !== []) {
                $parts[] = 'Unlocked grades: '.implode(', ', $unlockedQuarterLabels);
            }

            return [
                'has_issue' => true,
                'issue' => implode(' | ', $parts),
                'annual_subject_grades' => [],
            ];
        }

        return [
            'has_issue' => false,
            'issue' => '',
            'annual_subject_grades' => $annualSubjectGrades,
        ];
    }

    private function buildStatusRemarks(string $status, int $failedSubjectCount): string
    {
        if ($status === 'promoted') {
            return 'Passed annual requirements.';
        }

        if ($status === 'conditional') {
            return "Conditional promotion with {$failedSubjectCount} failed subject(s).";
        }

        if ($status === 'retained') {
            return "Retained due to {$failedSubjectCount} failed subjects.";
        }

        return 'Completed terminal grade level.';
    }

    private function refreshStudentRemedialFlag(int $studentId): void
    {
        $hasUnresolvedConditionals = PermanentRecord::query()
            ->where('student_id', $studentId)
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->exists();

        Student::query()
            ->whereKey($studentId)
            ->update([
                'is_for_remedial' => $hasUnresolvedConditionals,
            ]);
    }
}
