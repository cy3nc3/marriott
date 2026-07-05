<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\ResolvesTeacherAcademicYearAccess;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Services\DashboardCacheService;
use App\Services\DashboardDecisionService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesTeacherAcademicYearAccess;

    public function __construct(
        private readonly DashboardDecisionService $dashboardDecisionService,
    ) {}

    public function index(): Response
    {
        $teacherId = (int) auth()->id();
        $today = now()->format('l');
        $selectedAcademicYearId = $this->resolveCurrentAcademicYearId();

        $todaySchedules = collect(DashboardCacheService::remember(
            "teacher:dashboard:schedules:{$teacherId}:{$today}:".($selectedAcademicYearId ?? 'none'),
            function () use ($teacherId, $today, $selectedAcademicYearId): array {
                return ClassSchedule::query()
                    ->with([
                        'section:id,grade_level_id,name,adviser_id',
                        'section.gradeLevel:id,name',
                        'subjectAssignment:id,teacher_subject_id',
                        'subjectAssignment.teacherSubject:id,subject_id,teacher_id',
                        'subjectAssignment.teacherSubject.subject:id,subject_name',
                    ])
                    ->where('day', $today)
                    ->when($selectedAcademicYearId, function ($query) use ($selectedAcademicYearId) {
                        $query->whereHas('section', function ($sectionQuery) use ($selectedAcademicYearId) {
                            $sectionQuery->where('academic_year_id', $selectedAcademicYearId);
                        });
                    })
                    ->whereHas('subjectAssignment.teacherSubject', function ($teacherQuery) use ($teacherId) {
                        $teacherQuery->where('teacher_id', $teacherId);
                    })
                    ->orderBy('start_time')
                    ->get()
                    ->map(fn (ClassSchedule $schedule) => [
                        'id' => $schedule->id,
                        'title' => $schedule->subjectAssignment?->teacherSubject?->subject?->subject_name ?? $schedule->label ?? 'No Title',
                        'section' => ($schedule->section?->gradeLevel?->name ?? '').' - '.($schedule->section?->name ?? ''),
                        'time' => Carbon::parse((string) $schedule->start_time)->format('H:i').' - '.Carbon::parse((string) $schedule->end_time)->format('H:i'),
                        'is_academic' => $schedule->type === 'academic',
                        'is_advisory' => $schedule->section?->adviser_id === $teacherId,
                    ])
                    ->concat(
                        Section::query()
                            ->with('gradeLevel:id,name')
                            ->where('adviser_id', $teacherId)
                            ->when($selectedAcademicYearId, fn ($query) => $query->where('academic_year_id', $selectedAcademicYearId))
                            ->orderBy('grade_level_id')
                            ->orderBy('name')
                            ->get(['id', 'academic_year_id', 'grade_level_id', 'name', 'adviser_id'])
                            ->map(fn (Section $section) => [
                                'id' => -1 * (int) $section->id,
                                'title' => 'Advisory',
                                'section' => ($section->gradeLevel?->name ?? '').' - '.($section->name ?? ''),
                                'time' => 'Advisory section',
                                'is_academic' => false,
                                'is_advisory' => true,
                            ])
                    )
                    ->values()
                    ->all();
            }
        ));

        $activeYear = $selectedAcademicYearId
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : AcademicYear::query()->where('status', 'ongoing')->first();

        $cachedMetrics = [];
        $totalClassesCount = 0;
        $finalizedClassesCount = 0;
        $totalPendingGradeRows = 0;
        $pendingRowsByClass = [];
        $atRiskLearnersCount = 0;
        $academicRiskByClass = [];
        $attendanceRiskCount = 0;
        $attendanceRiskBySection = [];
        $submissionSlaRate = 0.0;
        $slaStatusRows = [];
        $unfinalizedClassesCount = 0;

        if ($activeYear) {
            $currentQuarter = (string) ($activeYear->current_quarter ?: '1');

            $cachedMetrics = DashboardCacheService::remember(
                "teacher:dashboard:metrics:{$teacherId}:{$activeYear->id}:{$currentQuarter}",
                function () use ($teacherId, $activeYear, $currentQuarter): array {
                    $classAssignments = SubjectAssignment::query()
                        ->with([
                            'section:id,grade_level_id,name',
                            'section.gradeLevel:id,name',
                            'teacherSubject:id,subject_id,teacher_id',
                            'teacherSubject.subject:id,subject_name',
                        ])
                        ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                            $query->where('teacher_id', $teacherId);
                        })
                        ->whereHas('section', function ($query) use ($activeYear) {
                            $query->where('academic_year_id', $activeYear->id);
                        })
                        ->get(['id', 'section_id', 'teacher_subject_id'])
                        ->values();

                    $totalClassesCount = $classAssignments->count();
                    $finalizedClassesCount = 0;
                    $totalPendingGradeRows = 0;
                    $pendingRowsByClass = [];
                    $academicRiskByClass = [];

                    if ($totalClassesCount > 0) {
                        $classAssignmentIds = $classAssignments->pluck('id')->all();

                        $enrolledCountBySection = Enrollment::query()
                            ->where('academic_year_id', $activeYear->id)
                            ->whereIn('section_id', $classAssignments->pluck('section_id')->unique())
                            ->where('status', 'enrolled')
                            ->selectRaw('section_id, count(*) as total')
                            ->groupBy('section_id')
                            ->pluck('total', 'section_id');

                        $finalGradeSummaryByClass = FinalGrade::query()
                            ->where('quarter', $currentQuarter)
                            ->whereIn('subject_assignment_id', $classAssignmentIds)
                            ->selectRaw('subject_assignment_id, count(*) as total, sum(case when is_locked then 1 else 0 end) as locked_total')
                            ->groupBy('subject_assignment_id')
                            ->get()
                            ->keyBy('subject_assignment_id');

                        foreach ($classAssignments as $classAssignment) {
                            $expectedRows = (int) ($enrolledCountBySection[$classAssignment->section_id] ?? 0);
                            $summaryRow = $finalGradeSummaryByClass->get($classAssignment->id);
                            $postedRows = (int) ($summaryRow?->total ?? 0);
                            $lockedRows = (int) ($summaryRow?->locked_total ?? 0);

                            $pendingRows = max($expectedRows - $postedRows, 0);
                            $totalPendingGradeRows += $pendingRows;

                            $gradeLevelName = $classAssignment->section?->gradeLevel?->name;
                            $sectionName = $classAssignment->section?->name;
                            $subjectName = $classAssignment->teacherSubject?->subject?->subject_name;

                            $classLabel = 'Unassigned Class';
                            if ($gradeLevelName && $sectionName && $subjectName) {
                                $classLabel = "{$gradeLevelName} - {$sectionName} ({$subjectName})";
                            }

                            $pendingRowsByClass[] = [
                                'label' => $classLabel,
                                'value' => $pendingRows,
                            ];

                            $isFinalized = $expectedRows === 0
                                || ($postedRows >= $expectedRows && $lockedRows >= $expectedRows);

                            if ($isFinalized) {
                                $finalizedClassesCount++;
                            }
                        }

                        $academicRiskByClass = FinalGrade::query()
                            ->join('subject_assignments', 'final_grades.subject_assignment_id', '=', 'subject_assignments.id')
                            ->join('sections', 'subject_assignments.section_id', '=', 'sections.id')
                            ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
                            ->join('teacher_subjects', 'subject_assignments.teacher_subject_id', '=', 'teacher_subjects.id')
                            ->join('subjects', 'teacher_subjects.subject_id', '=', 'subjects.id')
                            ->whereIn('final_grades.subject_assignment_id', $classAssignmentIds)
                            ->where('final_grades.quarter', $currentQuarter)
                            ->where('final_grades.grade', '<', 75)
                            ->selectRaw("CONCAT(grade_levels.name, ' - ', sections.name, ' (', subjects.subject_name, ')') as class_label, count(*) as at_risk_count")
                            ->groupBy('class_label')
                            ->orderByDesc('at_risk_count')
                            ->get()
                            ->map(fn ($row) => [
                                'label' => (string) $row->class_label,
                                'value' => (int) $row->at_risk_count,
                            ])
                            ->all();
                    }

                    $atRiskLearnersCount = FinalGrade::query()
                        ->where('quarter', $currentQuarter)
                        ->where('grade', '<', 75)
                        ->whereIn('subject_assignment_id', $classAssignments->pluck('id'))
                        ->distinct('enrollment_id')
                        ->count('enrollment_id');

                    return [
                        'total_classes_count' => $totalClassesCount,
                        'finalized_classes_count' => $finalizedClassesCount,
                        'total_pending_grade_rows' => $totalPendingGradeRows,
                        'pending_rows_by_class' => $pendingRowsByClass,
                        'at_risk_learners_count' => $atRiskLearnersCount,
                        'class_assignment_ids' => $classAssignments->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                        'academic_risk_by_class' => $academicRiskByClass,
                    ];
                }
            );

            $totalClassesCount = (int) $cachedMetrics['total_classes_count'];
            $finalizedClassesCount = (int) $cachedMetrics['finalized_classes_count'];
            $totalPendingGradeRows = (int) $cachedMetrics['total_pending_grade_rows'];
            $pendingRowsByClass = $cachedMetrics['pending_rows_by_class'];
            $atRiskLearnersCount = (int) $cachedMetrics['at_risk_learners_count'];
            $academicRiskByClass = $cachedMetrics['academic_risk_by_class'] ?? [];
            $unfinalizedClassesCount = max($totalClassesCount - $finalizedClassesCount, 0);

            $classAssignmentIds = collect($cachedMetrics['class_assignment_ids'] ?? [])
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->values();

            if ($classAssignmentIds->isNotEmpty()) {
                $attendanceLookbackStart = now()->subDays(13)->toDateString();
                $attendanceRows = Attendance::query()
                    ->join('enrollments', 'enrollments.id', '=', 'attendances.enrollment_id')
                    ->join('sections', 'sections.id', '=', 'enrollments.section_id')
                    ->join('grade_levels', 'grade_levels.id', '=', 'sections.grade_level_id')
                    ->whereIn('attendances.subject_assignment_id', $classAssignmentIds->all())
                    ->whereDate('attendances.date', '>=', $attendanceLookbackStart)
                    ->whereIn('attendances.status', ['absent', 'late'])
                    ->selectRaw("CONCAT(grade_levels.name, ' - ', sections.name) as section_label, enrollment_id, sum(case when attendances.status = 'absent' then 10 else 5 end) as risk_score")
                    ->groupBy('section_label', 'enrollment_id')
                    ->get();

                $attendanceRiskCount = $attendanceRows->where('risk_score', '>=', 70)->count();
                $attendanceRiskBySection = $attendanceRows->groupBy('section_label')
                    ->map(fn ($rows, $label) => [
                        'label' => $label,
                        'value' => $rows->where('risk_score', '>=', 70)->count(),
                    ])
                    ->sortByDesc('value')
                    ->values()
                    ->all();

                $submissions = GradeSubmission::query()
                    ->whereIn('subject_assignment_id', $classAssignmentIds->all())
                    ->where('quarter', $currentQuarter)
                    ->get(['status']);

                $totalSubmissions = $submissions->count();
                $verifiedSubmissions = $submissions->where('status', GradeSubmission::STATUS_VERIFIED)->count();
                $pendingSubmissions = $totalClassesCount - $totalSubmissions;

                $submissionSlaRate = $totalClassesCount > 0 ? ($verifiedSubmissions / $totalClassesCount) * 100 : 0;

                $slaStatusRows = [
                    ['status' => 'Verified', 'count' => $verifiedSubmissions],
                    ['status' => 'Submitted/Pending', 'count' => $totalSubmissions - $verifiedSubmissions],
                    ['status' => 'Not Yet Submitted', 'count' => $pendingSubmissions],
                ];
            }
        }

        $alerts = $this->dashboardDecisionService->teacherAlerts(
            $atRiskLearnersCount,
            $totalPendingGradeRows,
            $attendanceRiskCount,
        );

        $actionLinks = [
            [
                'id' => 'open-grading-sheet',
                'label' => 'Open Grading Sheet',
                'href' => route('teacher.grading_sheet'),
            ],
            [
                'id' => 'open-attendance',
                'label' => 'Open Attendance',
                'href' => route('teacher.attendance'),
            ],
            [
                'id' => 'open-advisory-board',
                'label' => 'Open Advisory Board',
                'href' => route('teacher.advisory_board'),
            ],
        ];

        $actionLinks = $this->dashboardDecisionService->prioritizeTeacherActionLinks(
            $actionLinks,
            $totalPendingGradeRows,
            $attendanceRiskCount,
            $atRiskLearnersCount,
        );

        $kpis = [
            [
                'id' => 'teacher-attendance-risk',
                'label' => 'Class Attendance Summary',
                'value' => $attendanceRiskCount,
                'meta' => 'Students with repeated absences or late marks',
            ],
            [
                'id' => 'teacher-grade-sla',
                'label' => 'Pending Grade Submissions',
                'value' => number_format($submissionSlaRate, 2).'%',
                'meta' => 'Current quarter submission coverage',
            ],
            [
                'id' => 'grade-rows-pending',
                'label' => 'Grade Encoding Status',
                'value' => $totalPendingGradeRows,
                'meta' => 'Unposted student grade rows',
            ],
            [
                'id' => 'at-risk-learners',
                'label' => 'Students With Low Grades',
                'value' => $atRiskLearnersCount,
                'meta' => 'Unique learners from current quarter grades',
            ],
        ];

        $kpis = $this->dashboardDecisionService->prioritizeTeacherKpis(
            $kpis,
            $totalPendingGradeRows,
            $submissionSlaRate,
            $atRiskLearnersCount,
            $attendanceRiskCount,
        );

        return Inertia::render('teacher/dashboard', [
            'today_schedules' => $todaySchedules,
            'kpis' => $kpis,
            'alerts' => $alerts,
            'quarter_grade_completion' => [
                'total_classes' => $totalClassesCount,
                'finalized_classes' => $finalizedClassesCount,
                'unfinalized_classes' => $unfinalizedClassesCount,
                'total_pending_rows' => $totalPendingGradeRows,
            ],
            'trends' => $this->dashboardDecisionService->teacherTrends(
                $academicRiskByClass,
                $pendingRowsByClass,
                $attendanceRiskBySection,
                $slaStatusRows,
            ),
            'action_links' => $actionLinks,
            'context' => [
                'current_quarter' => $activeYear?->current_quarter,
                'academic_year_name' => $activeYear?->name,
            ],
        ]);
    }
}
