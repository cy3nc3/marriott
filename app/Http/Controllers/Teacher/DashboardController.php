<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\SubjectAssignment;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $teacherId = (int) auth()->id();
        $today = now()->format('l');

        $todaySchedules = ClassSchedule::query()
            ->with([
                'section:id,grade_level_id,name,adviser_id',
                'section.gradeLevel:id,name',
                'subjectAssignment:id,teacher_subject_id',
                'subjectAssignment.teacherSubject:id,subject_id,teacher_id',
                'subjectAssignment.teacherSubject.subject:id,subject_name',
            ])
            ->where('day', $today)
            ->where(function ($query) use ($teacherId) {
                $query
                    ->whereHas('subjectAssignment.teacherSubject', function ($teacherQuery) use ($teacherId) {
                        $teacherQuery->where('teacher_id', $teacherId);
                    })
                    ->orWhere(function ($advisoryQuery) use ($teacherId) {
                        $advisoryQuery
                            ->whereNull('subject_assignment_id')
                            ->whereHas('section', function ($sectionQuery) use ($teacherId) {
                                $sectionQuery->where('adviser_id', $teacherId);
                            });
                    });
            })
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get()
            ->map(function (ClassSchedule $classSchedule): array {
                $title = $classSchedule->subjectAssignment?->teacherSubject?->subject?->subject_name
                    ?? ($classSchedule->label ?: 'Advisory');

                $gradeLevelName = $classSchedule->section?->gradeLevel?->name;
                $sectionName = $classSchedule->section?->name;
                $sectionLabel = $sectionName;
                if ($gradeLevelName && $sectionName) {
                    $sectionLabel = "{$gradeLevelName} - {$sectionName}";
                }

                return [
                    'id' => $classSchedule->id,
                    'start' => $this->toHourMinute($classSchedule->start_time),
                    'end' => $this->toHourMinute($classSchedule->end_time),
                    'time_label' => $this->toTimeLabel($classSchedule->start_time, $classSchedule->end_time),
                    'title' => $title,
                    'section' => $sectionLabel ?: 'Unassigned',
                    'duration_minutes' => Carbon::createFromFormat('H:i:s', $classSchedule->end_time)
                        ->diffInMinutes(Carbon::createFromFormat('H:i:s', $classSchedule->start_time)),
                ];
            })
            ->values();

        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $totalClassesCount = 0;
        $finalizedClassesCount = 0;
        $unfinalizedClassesCount = 0;
        $totalPendingGradeRows = 0;
        $atRiskLearnersCount = 0;
        $pendingRowsByClass = [];

        if ($activeYear) {
            $currentQuarter = (string) ($activeYear->current_quarter ?: '1');

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

            if ($totalClassesCount > 0) {
                $enrolledCountBySection = Enrollment::query()
                    ->where('academic_year_id', $activeYear->id)
                    ->whereIn('section_id', $classAssignments->pluck('section_id')->unique())
                    ->where('status', 'enrolled')
                    ->selectRaw('section_id, count(*) as total')
                    ->groupBy('section_id')
                    ->pluck('total', 'section_id');

                $finalGradeSummaryByClass = FinalGrade::query()
                    ->where('quarter', $currentQuarter)
                    ->whereIn('subject_assignment_id', $classAssignments->pluck('id'))
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
            }

            $unfinalizedClassesCount = max($totalClassesCount - $finalizedClassesCount, 0);

            $atRiskLearnersCount = FinalGrade::query()
                ->where('quarter', $currentQuarter)
                ->where('grade', '<', 75)
                ->whereIn('subject_assignment_id', $classAssignments->pluck('id'))
                ->distinct('enrollment_id')
                ->count('enrollment_id');
        }

        $alerts = $this->buildAlerts(
            $unfinalizedClassesCount,
            $totalClassesCount,
            $atRiskLearnersCount,
            $totalPendingGradeRows
        );

        return Inertia::render('teacher/dashboard', [
            'kpis' => [
                [
                    'id' => 'classes-today',
                    'label' => 'Classes Today',
                    'value' => $todaySchedules->count(),
                    'meta' => 'Scheduled blocks for current day',
                ],
                [
                    'id' => 'quarter-grade-completion',
                    'label' => 'Quarter Grade Completion',
                    'value' => "{$finalizedClassesCount} / {$totalClassesCount}",
                    'meta' => 'Finalized classes for current quarter',
                ],
                [
                    'id' => 'grade-rows-pending',
                    'label' => 'Grade Rows Pending',
                    'value' => $totalPendingGradeRows,
                    'meta' => 'Unposted student grade rows',
                ],
                [
                    'id' => 'at-risk-learners',
                    'label' => 'At-Risk Learners (<75)',
                    'value' => $atRiskLearnersCount,
                    'meta' => 'Unique learners from current quarter grades',
                ],
            ],
            'alerts' => $alerts,
            'trends' => [
                [
                    'id' => 'today-classes',
                    'label' => 'Today Class Snapshot',
                    'summary' => 'Scheduled class duration per time block',
                    'display' => 'bar',
                    'points' => $todaySchedules
                        ->map(function (array $scheduleItem): array {
                            return [
                                'label' => $scheduleItem['start'].'-'.$scheduleItem['end'],
                                'value' => $scheduleItem['duration_minutes'],
                            ];
                        })
                        ->values()
                        ->all(),
                    'chart' => [
                        'x_key' => 'time_slot',
                        'rows' => $todaySchedules
                            ->map(function (array $scheduleItem): array {
                                return [
                                    'time_slot' => $scheduleItem['start'].'-'.$scheduleItem['end'],
                                    'minutes' => $scheduleItem['duration_minutes'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'minutes',
                                'label' => 'Minutes',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'pending-grade-rows-by-class',
                    'label' => 'Pending Grade Rows by Class',
                    'summary' => 'Outstanding grade rows per class assignment',
                    'display' => 'bar',
                    'points' => $pendingRowsByClass,
                    'chart' => [
                        'x_key' => 'class',
                        'rows' => collect($pendingRowsByClass)
                            ->map(function (array $point): array {
                                return [
                                    'class' => $point['label'],
                                    'pending_rows' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'pending_rows',
                                'label' => 'Pending Rows',
                            ],
                        ],
                    ],
                ],
            ],
            'action_links' => [
                [
                    'id' => 'open-grading-sheet',
                    'label' => 'Open Grading Sheet',
                    'href' => route('teacher.grading_sheet'),
                ],
                [
                    'id' => 'open-advisory-board',
                    'label' => 'Open Advisory Board',
                    'href' => route('teacher.advisory_board'),
                ],
                [
                    'id' => 'open-teacher-schedule',
                    'label' => 'Open My Schedule',
                    'href' => route('teacher.schedule'),
                ],
            ],
            'quarter_grade_completion' => [
                'total_classes' => $totalClassesCount,
                'finalized_classes' => $finalizedClassesCount,
                'unfinalized_classes' => $unfinalizedClassesCount,
            ],
        ]);
    }

    private function toHourMinute(string $timeValue): string
    {
        return substr($timeValue, 0, 5);
    }

    private function toTimeLabel(string $startTime, string $endTime): string
    {
        $start = Carbon::createFromFormat('H:i:s', $startTime)->format('h:i A');
        $end = Carbon::createFromFormat('H:i:s', $endTime)->format('h:i A');

        return "{$start} - {$end}";
    }

    /**
     * @return array<int, array{id: string, title: string, message: string, severity: string}>
     */
    private function buildAlerts(
        int $unfinalizedClassesCount,
        int $totalClassesCount,
        int $atRiskLearnersCount,
        int $totalPendingGradeRows
    ): array {
        $alerts = [];

        if ($unfinalizedClassesCount > 0) {
            $severity = $totalClassesCount > 0
                && ($unfinalizedClassesCount / $totalClassesCount) >= 0.5
                ? 'critical'
                : 'warning';

            $alerts[] = [
                'id' => 'grade-finalization',
                'title' => 'Quarter grades are not fully finalized',
                'message' => "{$unfinalizedClassesCount} class(es) are still unlocked for the current quarter.",
                'severity' => $severity,
            ];
        }

        if ($totalPendingGradeRows > 0) {
            $alerts[] = [
                'id' => 'pending-grade-rows',
                'title' => 'Pending grade rows require encoding',
                'message' => "{$totalPendingGradeRows} grade row(s) are still missing.",
                'severity' => $totalPendingGradeRows >= 20 ? 'critical' : 'warning',
            ];
        }

        if ($atRiskLearnersCount >= 15) {
            $alerts[] = [
                'id' => 'at-risk-learners',
                'title' => 'High number of at-risk learners',
                'message' => "{$atRiskLearnersCount} learner(s) currently have quarter grade below 75.",
                'severity' => 'critical',
            ];
        } elseif ($atRiskLearnersCount >= 5) {
            $alerts[] = [
                'id' => 'at-risk-learners',
                'title' => 'At-risk learners require intervention',
                'message' => "{$atRiskLearnersCount} learner(s) currently have quarter grade below 75.",
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'teacher-stable',
                'title' => 'Teaching dashboard is stable',
                'message' => 'Class finalization, grade encoding, and learner risk signals are within target thresholds.',
                'severity' => 'info',
            ];
        }

        return $alerts;
    }
}
