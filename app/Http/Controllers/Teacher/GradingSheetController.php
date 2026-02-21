<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\IndexGradingSheetRequest;
use App\Http\Requests\Teacher\StoreGradedActivityRequest;
use App\Http\Requests\Teacher\StoreGradingScoresRequest;
use App\Http\Requests\Teacher\UpdateGradingRubricRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradingRubric;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GradingSheetController extends Controller
{
    public function index(IndexGradingSheetRequest $request): Response
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();

        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $selectedQuarter = (string) ($validated['quarter']
            ?? ($activeYear?->current_quarter ?: '1'));

        $teacherAssignments = SubjectAssignment::query()
            ->with([
                'section:id,academic_year_id,grade_level_id,name',
                'section.gradeLevel:id,name',
                'teacherSubject:id,teacher_id,subject_id',
                'teacherSubject.subject:id,subject_name',
            ])
            ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->whereHas('section', function ($sectionQuery) use ($activeYear) {
                    $sectionQuery->where('academic_year_id', $activeYear->id);
                });
            })
            ->orderBy('section_id')
            ->orderBy('id')
            ->get();

        $sectionOptions = $teacherAssignments
            ->map(function (SubjectAssignment $subjectAssignment) {
                $sectionId = $subjectAssignment->section_id;
                $gradeLevelName = $subjectAssignment->section?->gradeLevel?->name;
                $sectionName = $subjectAssignment->section?->name;
                $label = $sectionName ? "{$gradeLevelName} - {$sectionName}" : 'Unassigned Section';

                return [
                    'id' => $sectionId,
                    'label' => $label,
                ];
            })
            ->unique('id')
            ->values();

        $selectedSectionId = (int) ($validated['section_id'] ?? ($sectionOptions->first()['id'] ?? 0));

        $subjectOptions = $teacherAssignments
            ->filter(function (SubjectAssignment $subjectAssignment) use ($selectedSectionId) {
                return $subjectAssignment->section_id === $selectedSectionId;
            })
            ->map(function (SubjectAssignment $subjectAssignment) {
                return [
                    'id' => $subjectAssignment->teacherSubject?->subject_id,
                    'name' => $subjectAssignment->teacherSubject?->subject?->subject_name ?? 'Unnamed Subject',
                ];
            })
            ->filter(function (array $subjectOption) {
                return ! empty($subjectOption['id']);
            })
            ->unique('id')
            ->values();

        $selectedSubjectId = (int) ($validated['subject_id'] ?? ($subjectOptions->first()['id'] ?? 0));

        $selectedAssignment = $teacherAssignments->first(function (SubjectAssignment $subjectAssignment) use ($selectedSectionId, $selectedSubjectId) {
            return $subjectAssignment->section_id === $selectedSectionId
                && (int) $subjectAssignment->teacherSubject?->subject_id === $selectedSubjectId;
        });

        $rubric = $selectedSubjectId > 0
            ? GradingRubric::query()->where('subject_id', $selectedSubjectId)->first()
            : null;

        $rubricWeights = [
            'ww_weight' => $rubric?->ww_weight ?? 40,
            'pt_weight' => $rubric?->pt_weight ?? 40,
            'qa_weight' => $rubric?->qa_weight ?? 20,
        ];

        $assessments = collect();
        if ($selectedAssignment) {
            $assessments = GradedActivity::query()
                ->where('subject_assignment_id', $selectedAssignment->id)
                ->where('quarter', $selectedQuarter)
                ->orderByRaw("CASE type WHEN 'WW' THEN 1 WHEN 'PT' THEN 2 WHEN 'QA' THEN 3 ELSE 4 END")
                ->orderBy('id')
                ->get();
        }

        $writtenWorks = $assessments
            ->where('type', 'WW')
            ->values();
        $performanceTasks = $assessments
            ->where('type', 'PT')
            ->values();
        $quarterlyExams = $assessments
            ->where('type', 'QA')
            ->values();

        $enrollments = collect();
        if ($selectedSectionId > 0) {
            $enrollments = Enrollment::query()
                ->with('student:id,first_name,last_name')
                ->where('section_id', $selectedSectionId)
                ->when($activeYear, function ($query) use ($activeYear) {
                    $query->where('academic_year_id', $activeYear->id);
                })
                ->where('status', 'enrolled')
                ->orderBy('id')
                ->get(['id', 'student_id']);
        }

        $studentIds = $enrollments
            ->pluck('student_id')
            ->values();
        $assessmentIds = $assessments
            ->pluck('id')
            ->values();

        $scoreMapByStudent = collect();
        if ($studentIds->isNotEmpty() && $assessmentIds->isNotEmpty()) {
            $scoreMapByStudent = StudentScore::query()
                ->whereIn('student_id', $studentIds)
                ->whereIn('graded_activity_id', $assessmentIds)
                ->get(['student_id', 'graded_activity_id', 'score'])
                ->groupBy('student_id')
                ->map(function (Collection $studentScores) {
                    return $studentScores->mapWithKeys(function (StudentScore $studentScore) {
                        return [
                            (int) $studentScore->graded_activity_id => (float) $studentScore->score,
                        ];
                    });
                });
        }

        $students = $enrollments
            ->map(function (Enrollment $enrollment) use ($writtenWorks, $performanceTasks, $quarterlyExams, $scoreMapByStudent, $rubricWeights) {
                $studentScores = (array) ($scoreMapByStudent->get($enrollment->student_id)?->all() ?? []);

                $computedGrade = $this->calculateComputedGrade(
                    $writtenWorks,
                    $performanceTasks,
                    $quarterlyExams,
                    $studentScores,
                    $rubricWeights
                );

                return [
                    'id' => (int) $enrollment->student_id,
                    'enrollment_id' => (int) $enrollment->id,
                    'name' => trim("{$enrollment->student?->last_name}, {$enrollment->student?->first_name}"),
                    'scores' => collect($studentScores)
                        ->mapWithKeys(function (float $score, int $activityId) {
                            return [(string) $activityId => $score];
                        })
                        ->all(),
                    'computed_grade' => number_format($computedGrade, 2, '.', ''),
                ];
            })
            ->values();

        $isSubmitted = false;
        if ($selectedAssignment && $enrollments->isNotEmpty()) {
            $lockedCount = FinalGrade::query()
                ->where('subject_assignment_id', $selectedAssignment->id)
                ->where('quarter', $selectedQuarter)
                ->whereIn('enrollment_id', $enrollments->pluck('id'))
                ->where('is_locked', true)
                ->count();

            $isSubmitted = $lockedCount === $enrollments->count();
        }

        return Inertia::render('teacher/grading-sheet/index', [
            'context' => [
                'section_options' => $sectionOptions,
                'subject_options' => $subjectOptions,
                'selected_section_id' => $selectedSectionId > 0 ? $selectedSectionId : null,
                'selected_subject_id' => $selectedSubjectId > 0 ? $selectedSubjectId : null,
                'selected_assignment_id' => $selectedAssignment?->id,
                'selected_quarter' => $selectedQuarter,
                'has_assignment' => (bool) $selectedAssignment,
            ],
            'rubric_weights' => $rubricWeights,
            'grouped_assessments' => [
                [
                    'component' => 'Written Works',
                    'weight' => (int) $rubricWeights['ww_weight'],
                    'assessments' => $writtenWorks->map(function (GradedActivity $gradedActivity) {
                        return [
                            'id' => $gradedActivity->id,
                            'title' => $gradedActivity->title,
                            'max_points' => (float) $gradedActivity->max_score,
                        ];
                    })->values(),
                ],
                [
                    'component' => 'Performance Tasks',
                    'weight' => (int) $rubricWeights['pt_weight'],
                    'assessments' => $performanceTasks->map(function (GradedActivity $gradedActivity) {
                        return [
                            'id' => $gradedActivity->id,
                            'title' => $gradedActivity->title,
                            'max_points' => (float) $gradedActivity->max_score,
                        ];
                    })->values(),
                ],
            ],
            'quarterly_exam_assessment' => $quarterlyExams->first()
                ? [
                    'id' => $quarterlyExams->first()->id,
                    'title' => $quarterlyExams->first()->title,
                    'max_points' => (float) $quarterlyExams->first()->max_score,
                ]
                : null,
            'students' => $students,
            'status' => $isSubmitted ? 'submitted' : 'draft',
        ]);
    }

    public function updateRubric(UpdateGradingRubricRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $teacherOwnsSubject = TeacherSubject::query()
            ->where('teacher_id', auth()->id())
            ->where('subject_id', $validated['subject_id'])
            ->exists();

        if (! $teacherOwnsSubject) {
            abort(403);
        }

        GradingRubric::query()->updateOrCreate(
            [
                'subject_id' => $validated['subject_id'],
            ],
            [
                'ww_weight' => $validated['ww_weight'],
                'pt_weight' => $validated['pt_weight'],
                'qa_weight' => $validated['qa_weight'],
            ]
        );

        return back()->with('success', 'Rubric updated successfully.');
    }

    public function storeAssessment(StoreGradedActivityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $subjectAssignment = SubjectAssignment::query()
            ->whereKey($validated['subject_assignment_id'])
            ->whereHas('teacherSubject', function ($query) {
                $query->where('teacher_id', auth()->id());
            })
            ->firstOrFail();

        GradedActivity::query()->create([
            'subject_assignment_id' => $subjectAssignment->id,
            'quarter' => $validated['quarter'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'max_score' => $validated['max_score'],
        ]);

        return back()->with('success', 'Assessment added.');
    }

    public function storeScores(StoreGradingScoresRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $subjectAssignment = SubjectAssignment::query()
            ->with([
                'teacherSubject:id,teacher_id,subject_id',
                'section:id,academic_year_id',
            ])
            ->whereKey($validated['subject_assignment_id'])
            ->whereHas('teacherSubject', function ($query) {
                $query->where('teacher_id', auth()->id());
            })
            ->firstOrFail();

        $quarterActivities = GradedActivity::query()
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->where('quarter', $validated['quarter'])
            ->get()
            ->keyBy('id');

        if ($quarterActivities->isEmpty()) {
            return back()->with('error', 'Add at least one assessment before saving scores.');
        }

        $enrollments = Enrollment::query()
            ->where('section_id', $subjectAssignment->section_id)
            ->where('academic_year_id', $subjectAssignment->section?->academic_year_id)
            ->where('status', 'enrolled')
            ->get(['id', 'student_id']);

        $validStudentIds = $enrollments->pluck('student_id');

        foreach ($validated['scores'] as $scoreRow) {
            $gradedActivity = $quarterActivities->get((int) $scoreRow['graded_activity_id']);
            if (! $gradedActivity) {
                continue;
            }

            if (! $validStudentIds->contains((int) $scoreRow['student_id'])) {
                continue;
            }

            if (! array_key_exists('score', $scoreRow) || $scoreRow['score'] === null || $scoreRow['score'] === '') {
                continue;
            }

            $normalizedScore = min(
                (float) $gradedActivity->max_score,
                max((float) $scoreRow['score'], 0)
            );

            StudentScore::query()->updateOrCreate(
                [
                    'student_id' => $scoreRow['student_id'],
                    'graded_activity_id' => $gradedActivity->id,
                ],
                [
                    'score' => $normalizedScore,
                ]
            );
        }

        $rubric = GradingRubric::query()
            ->where('subject_id', $subjectAssignment->teacherSubject?->subject_id)
            ->first();

        $rubricWeights = [
            'ww_weight' => $rubric?->ww_weight ?? 40,
            'pt_weight' => $rubric?->pt_weight ?? 40,
            'qa_weight' => $rubric?->qa_weight ?? 20,
        ];

        $writtenWorks = $quarterActivities->where('type', 'WW')->values();
        $performanceTasks = $quarterActivities->where('type', 'PT')->values();
        $quarterlyExams = $quarterActivities->where('type', 'QA')->values();

        $scoreMapByStudent = StudentScore::query()
            ->whereIn('student_id', $validStudentIds)
            ->whereIn('graded_activity_id', $quarterActivities->keys())
            ->get(['student_id', 'graded_activity_id', 'score'])
            ->groupBy('student_id')
            ->map(function (Collection $studentScores) {
                return $studentScores->mapWithKeys(function (StudentScore $studentScore) {
                    return [
                        (int) $studentScore->graded_activity_id => (float) $studentScore->score,
                    ];
                })->all();
            });

        foreach ($enrollments as $enrollment) {
            $computedGrade = $this->calculateComputedGrade(
                $writtenWorks,
                $performanceTasks,
                $quarterlyExams,
                (array) ($scoreMapByStudent->get($enrollment->student_id) ?? []),
                $rubricWeights
            );

            FinalGrade::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $subjectAssignment->id,
                    'quarter' => $validated['quarter'],
                ],
                [
                    'grade' => $computedGrade,
                    'is_locked' => $validated['save_mode'] === 'submitted',
                ]
            );
        }

        $message = $validated['save_mode'] === 'submitted'
            ? 'Quarter grades submitted and locked.'
            : 'Scores saved as draft.';

        return back()->with('success', $message);
    }

    private function calculateComputedGrade(
        Collection $writtenWorks,
        Collection $performanceTasks,
        Collection $quarterlyExams,
        array $studentScores,
        array $rubricWeights
    ): float {
        $writtenWeighted = $this->calculateComponentWeightedScore(
            $writtenWorks,
            $studentScores,
            (int) $rubricWeights['ww_weight']
        );
        $performanceWeighted = $this->calculateComponentWeightedScore(
            $performanceTasks,
            $studentScores,
            (int) $rubricWeights['pt_weight']
        );
        $examWeighted = $this->calculateComponentWeightedScore(
            $quarterlyExams,
            $studentScores,
            (int) $rubricWeights['qa_weight']
        );

        return round($writtenWeighted + $performanceWeighted + $examWeighted, 2);
    }

    private function calculateComponentWeightedScore(
        Collection $activities,
        array $studentScores,
        int $componentWeight
    ): float {
        if ($activities->isEmpty() || $componentWeight <= 0) {
            return 0.0;
        }

        $totalMaxScore = (float) $activities->sum(function (GradedActivity $gradedActivity) {
            return (float) $gradedActivity->max_score;
        });

        if ($totalMaxScore <= 0) {
            return 0.0;
        }

        $studentTotalScore = (float) $activities->sum(function (GradedActivity $gradedActivity) use ($studentScores) {
            return (float) ($studentScores[$gradedActivity->id] ?? 0);
        });

        $componentPercentage = ($studentTotalScore / $totalMaxScore) * 100;

        return ($componentPercentage * $componentWeight) / 100;
    }
}
