<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $activeYear = $this->resolveActiveAcademicYear();
        $currentQuarter = (string) ($activeYear?->current_quarter ?: '1');
        $enrollment = $student ? $this->resolveCurrentEnrollment($student) : null;

        $happeningNow = [
            'title' => 'No active class',
            'teacher' => 'TBA',
            'time_label' => '-',
            'section_label' => 'Unassigned',
            'schedule_state' => 'none',
        ];

        if ($enrollment?->section_id) {
            $today = now()->format('l');
            $nowTime = now()->format('H:i:s');

            $todaySchedules = ClassSchedule::query()
                ->with([
                    'section:id,grade_level_id,name,adviser_id',
                    'section.gradeLevel:id,name',
                    'section.adviser:id,first_name,last_name',
                    'subjectAssignment:id,teacher_subject_id',
                    'subjectAssignment.teacherSubject:id,teacher_id,subject_id',
                    'subjectAssignment.teacherSubject.subject:id,subject_name',
                    'subjectAssignment.teacherSubject.teacher:id,first_name,last_name',
                ])
                ->where('section_id', $enrollment->section_id)
                ->where('day', $today)
                ->whereIn('type', ['academic', 'advisory'])
                ->orderBy('start_time')
                ->orderBy('end_time')
                ->get();

            $currentSchedule = $todaySchedules->first(function (ClassSchedule $classSchedule) use ($nowTime) {
                return $classSchedule->start_time <= $nowTime
                    && $classSchedule->end_time >= $nowTime;
            });

            $selectedSchedule = $currentSchedule
                ?: $todaySchedules->first(function (ClassSchedule $classSchedule) use ($nowTime) {
                    return $classSchedule->start_time > $nowTime;
                });

            if ($selectedSchedule) {
                $happeningNow = [
                    'title' => $this->resolveScheduleTitle($selectedSchedule),
                    'teacher' => $this->resolveTeacherName($selectedSchedule),
                    'time_label' => $this->toTimeLabel($selectedSchedule->start_time, $selectedSchedule->end_time),
                    'section_label' => $this->resolveSectionLabel($selectedSchedule),
                    'schedule_state' => $currentSchedule ? 'ongoing' : 'upcoming',
                ];
            }
        }

        $latestScore = [
            'score_label' => '-',
            'assessment_title' => 'No score recorded yet.',
            'subject_name' => '',
            'updated_at' => null,
        ];

        if ($student && $enrollment?->section_id) {
            $studentScore = StudentScore::query()
                ->with('gradedActivity.subjectAssignment.teacherSubject.subject:id,subject_name')
                ->where('student_id', $student->id)
                ->whereHas('gradedActivity.subjectAssignment', function ($query) use ($enrollment) {
                    $query->where('section_id', $enrollment->section_id);
                })
                ->whereHas('gradedActivity', function ($query) use ($currentQuarter) {
                    $query->where('quarter', $currentQuarter);
                })
                ->latest('updated_at')
                ->first();

            if ($studentScore) {
                $maxScore = (float) ($studentScore->gradedActivity?->max_score ?? 0);
                $subjectName = $studentScore->gradedActivity?->subjectAssignment?->teacherSubject?->subject?->subject_name;

                $latestScore = [
                    'score_label' => $maxScore > 0
                        ? $this->formatWholeNumber((float) $studentScore->score).'/'.$this->formatWholeNumber($maxScore)
                        : $this->formatWholeNumber((float) $studentScore->score),
                    'assessment_title' => $studentScore->gradedActivity?->title ?: 'Assessment',
                    'subject_name' => $subjectName ?: '',
                    'updated_at' => $studentScore->updated_at?->toDateString(),
                ];
            }
        }

        $quarterAverages = [];
        $currentQuarterAverage = null;
        $previousQuarterAverage = null;
        if ($enrollment) {
            $finalGrades = FinalGrade::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('quarter', ['1', '2', '3', '4'])
                ->get();

            $quarterAverages = collect(['1', '2', '3', '4'])
                ->map(function (string $quarter) use ($finalGrades): array {
                    $quarterRows = $finalGrades->where('quarter', $quarter);
                    $average = $quarterRows->isNotEmpty()
                        ? round((float) $quarterRows->avg('grade'), 2)
                        : null;

                    return [
                        'quarter' => $quarter,
                        'label' => "Q{$quarter}",
                        'average' => $average,
                    ];
                })
                ->all();

            $currentQuarterEntry = collect($quarterAverages)
                ->firstWhere('quarter', $currentQuarter);
            $currentQuarterAverage = is_array($currentQuarterEntry)
                ? $currentQuarterEntry['average']
                : null;

            $previousQuarter = max((int) $currentQuarter - 1, 1);
            $previousQuarterEntry = collect($quarterAverages)
                ->firstWhere('quarter', (string) $previousQuarter);
            $previousQuarterAverage = is_array($previousQuarterEntry)
                ? $previousQuarterEntry['average']
                : null;

            if ($currentQuarterAverage === null) {
                $currentQuarterAverage = collect($quarterAverages)
                    ->pluck('average')
                    ->filter()
                    ->last();
            }
        }

        $generalAverageTrendValue = null;
        if ($currentQuarterAverage !== null && $previousQuarterAverage !== null) {
            $generalAverageTrendValue = round($currentQuarterAverage - $previousQuarterAverage, 2);
        }

        $recentScoreAverage = null;
        $recentScoreTrendDelta = null;
        $recentScoreRecordsCount = 0;
        $recentScoreTrendPoints = [];
        $latestScoreDate = null;

        if ($student && $enrollment?->section_id) {
            $recentScores = StudentScore::query()
                ->with([
                    'gradedActivity:id,subject_assignment_id,title,max_score,quarter',
                    'gradedActivity.subjectAssignment:id,section_id',
                ])
                ->where('student_id', $student->id)
                ->whereHas('gradedActivity.subjectAssignment', function ($query) use ($enrollment) {
                    $query->where('section_id', $enrollment->section_id);
                })
                ->whereHas('gradedActivity', function ($query) use ($currentQuarter) {
                    $query->where('quarter', $currentQuarter);
                })
                ->latest('updated_at')
                ->limit(5)
                ->get();

            $latestScoreDate = $recentScores->first()?->updated_at;

            $scoredPoints = $recentScores
                ->map(function (StudentScore $studentScore, int $index): ?array {
                    $maxScore = (float) ($studentScore->gradedActivity?->max_score ?? 0);
                    if ($maxScore <= 0) {
                        return null;
                    }

                    $percentage = round(((float) $studentScore->score / $maxScore) * 100, 2);
                    $assessmentTitle = $studentScore->gradedActivity?->title ?: 'Assessment';
                    $dateLabel = $studentScore->updated_at?->format('M d') ?? 'Date N/A';

                    return [
                        'label' => "{$dateLabel} · {$assessmentTitle}",
                        'value' => $percentage,
                        'sequence' => $index + 1,
                    ];
                })
                ->filter()
                ->values();

            $recentScoreRecordsCount = $scoredPoints->count();
            if ($recentScoreRecordsCount > 0) {
                $recentScoreAverage = round((float) $scoredPoints->avg('value'), 2);

                $newestValue = (float) ($scoredPoints->first()['value'] ?? 0);
                $oldestValue = (float) ($scoredPoints->last()['value'] ?? 0);
                if ($recentScoreRecordsCount >= 2) {
                    $recentScoreTrendDelta = round($newestValue - $oldestValue, 2);
                }

                $recentScoreTrendPoints = $scoredPoints
                    ->reverse()
                    ->values()
                    ->map(function (array $point): array {
                        return [
                            'label' => $point['label'],
                            'value' => $point['value'],
                        ];
                    })
                    ->all();
            }
        }

        $upcomingItems = $enrollment?->section_id
            ? $this->resolveUpcomingAcademicItems((int) $enrollment->section_id)
            : [];
        $upcomingItemsByDay = collect($upcomingItems)
            ->groupBy('date_label')
            ->map(function (Collection $items, string $dateLabel): array {
                return [
                    'label' => $dateLabel,
                    'value' => $items->count(),
                ];
            })
            ->values()
            ->all();

        $alerts = [];

        if ($recentScoreAverage !== null && $recentScoreAverage < 75) {
            $alerts[] = [
                'id' => 'score-risk-critical',
                'title' => 'Recent score average is below passing threshold',
                'message' => "Current recent score average is {$recentScoreAverage}%.",
                'severity' => 'critical',
            ];
        } elseif ($recentScoreAverage !== null && $recentScoreAverage < 80) {
            $alerts[] = [
                'id' => 'score-risk-warning',
                'title' => 'Recent score average needs attention',
                'message' => "Current recent score average is {$recentScoreAverage}%.",
                'severity' => 'warning',
            ];
        }

        if (! $latestScoreDate || $latestScoreDate->lt(now()->subDays(14))) {
            $alerts[] = [
                'id' => 'score-updates',
                'title' => 'No recent score updates in the last 14 days',
                'message' => 'Score updates may be delayed. Check with your subject teachers.',
                'severity' => 'warning',
            ];
        }

        if (count($upcomingItems) === 0) {
            $alerts[] = [
                'id' => 'upcoming-items',
                'title' => 'No upcoming academic schedule items',
                'message' => 'No class schedules were found for the next few days.',
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'student-stable',
                'title' => 'Learning dashboard is stable',
                'message' => 'Class flow, grade averages, and score trends are within expected thresholds.',
                'severity' => 'info',
            ];
        }

        $generalAverageLabel = $currentQuarterAverage !== null
            ? number_format($currentQuarterAverage, 2, '.', '')
            : '-';

        $recentScoreAverageLabel = $recentScoreAverage !== null
            ? number_format($recentScoreAverage, 2, '.', '').'%'
            : '-';

        return Inertia::render('student/dashboard', [
            'kpis' => [
                [
                    'id' => 'current-upcoming-class',
                    'label' => 'Current / Upcoming Class',
                    'value' => $happeningNow['title'],
                    'meta' => $happeningNow['time_label'],
                ],
                [
                    'id' => 'general-average',
                    'label' => 'General Average',
                    'value' => $generalAverageLabel,
                    'meta' => "Quarter {$currentQuarter}",
                ],
                [
                    'id' => 'latest-score',
                    'label' => 'Latest Score',
                    'value' => $latestScore['score_label'],
                    'meta' => $latestScore['assessment_title'],
                ],
                [
                    'id' => 'recent-score-average',
                    'label' => 'Recent Score Average',
                    'value' => $recentScoreAverageLabel,
                    'meta' => "{$recentScoreRecordsCount} recent assessment(s)",
                ],
            ],
            'alerts' => array_values($alerts),
            'trends' => [
                [
                    'id' => 'recent-score-trend',
                    'label' => 'Recent Score Trend (Last 5 Assessments)',
                    'summary' => 'Score percentages from your most recent assessments',
                    'display' => 'line',
                    'points' => $recentScoreTrendPoints,
                    'chart' => [
                        'x_key' => 'assessment',
                        'rows' => collect($recentScoreTrendPoints)
                            ->map(function (array $point): array {
                                return [
                                    'assessment' => $point['label'],
                                    'score_percentage' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'score_percentage',
                                'label' => 'Score %',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'upcoming-academic-items',
                    'label' => 'Upcoming Academic Schedule Items',
                    'summary' => 'Upcoming class count by day',
                    'display' => 'bar',
                    'points' => $upcomingItemsByDay,
                    'chart' => [
                        'x_key' => 'day',
                        'rows' => collect($upcomingItemsByDay)
                            ->map(function (array $point): array {
                                return [
                                    'day' => $point['label'],
                                    'class_count' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'class_count',
                                'label' => 'Classes',
                            ],
                        ],
                    ],
                ],
            ],
            'action_links' => [
                [
                    'id' => 'open-grades',
                    'label' => 'Open Grades',
                    'href' => route('student.grades'),
                ],
                [
                    'id' => 'open-schedule',
                    'label' => 'Open Schedule',
                    'href' => route('student.schedule'),
                ],
            ],
            'learning_summary' => [
                'current_or_upcoming_class' => $happeningNow['title'],
                'general_average' => $generalAverageLabel,
                'general_average_trend' => $generalAverageTrendValue,
                'latest_score' => $latestScore['score_label'],
                'recent_score_average' => $recentScoreAverageLabel,
                'recent_score_trend_delta' => $recentScoreTrendDelta,
                'recent_score_records_count' => $recentScoreRecordsCount,
                'upcoming_items_count' => count($upcomingItems),
            ],
        ]);
    }

    private function resolveStudent(?User $user): ?Student
    {
        if (! $user) {
            return null;
        }

        return Student::query()
            ->where('user_id', $user->id)
            ->first();
    }

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
        $activeYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeYearId) {
            $activeEnrollment = Enrollment::query()
                ->with('academicYear:id,name,status')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'enrolled')
                ->first();

            if ($activeEnrollment) {
                return $activeEnrollment;
            }
        }

        return Enrollment::query()
            ->with('academicYear:id,name,status')
            ->where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->latest('id')
            ->first();
    }

    private function resolveActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->latest('start_date')->first();
    }

    private function resolveScheduleTitle(ClassSchedule $classSchedule): string
    {
        if ($classSchedule->type === 'advisory') {
            return $classSchedule->label ?: 'Advisory';
        }

        return $classSchedule->subjectAssignment?->teacherSubject?->subject?->subject_name ?: 'Class';
    }

    private function resolveTeacherName(ClassSchedule $classSchedule): string
    {
        $teacher = $classSchedule->subjectAssignment?->teacherSubject?->teacher;
        if ($teacher) {
            return trim("{$teacher->first_name} {$teacher->last_name}");
        }

        $adviser = $classSchedule->section?->adviser;
        if ($adviser) {
            return trim("{$adviser->first_name} {$adviser->last_name}");
        }

        return 'TBA';
    }

    private function resolveSectionLabel(ClassSchedule $classSchedule): string
    {
        $gradeLevelName = $classSchedule->section?->gradeLevel?->name;
        $sectionName = $classSchedule->section?->name;

        if ($gradeLevelName && $sectionName) {
            return "{$gradeLevelName} - {$sectionName}";
        }

        return $sectionName ?: 'Unassigned';
    }

    private function toTimeLabel(string $startTime, string $endTime): string
    {
        $start = Carbon::createFromFormat('H:i:s', $startTime)->format('h:i A');
        $end = Carbon::createFromFormat('H:i:s', $endTime)->format('h:i A');

        return "{$start} - {$end}";
    }

    private function formatWholeNumber(float $value): string
    {
        return number_format($value, 0, '.', '');
    }

    private function resolveUpcomingAcademicItems(int $sectionId): array
    {
        $weekDayOrder = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];

        $schedules = ClassSchedule::query()
            ->with([
                'section:id,grade_level_id,name,adviser_id',
                'section.gradeLevel:id,name',
                'section.adviser:id,first_name,last_name',
                'subjectAssignment:id,teacher_subject_id',
                'subjectAssignment.teacherSubject:id,teacher_id,subject_id',
                'subjectAssignment.teacherSubject.subject:id,subject_name',
                'subjectAssignment.teacherSubject.teacher:id,first_name,last_name',
            ])
            ->where('section_id', $sectionId)
            ->whereIn('day', $weekDayOrder)
            ->whereIn('type', ['academic', 'advisory'])
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();

        $schedulesByDay = $schedules->groupBy('day');
        $now = now();
        $upcomingItems = [];

        for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
            if (count($upcomingItems) >= 3) {
                break;
            }

            $date = $now->copy()->addDays($dayOffset);
            $dayName = $date->format('l');
            /** @var Collection<int, ClassSchedule> $daySchedules */
            $daySchedules = $schedulesByDay->get($dayName, collect());

            foreach ($daySchedules as $daySchedule) {
                if (count($upcomingItems) >= 3) {
                    break;
                }

                if ($dayOffset === 0 && $daySchedule->start_time <= $now->format('H:i:s')) {
                    continue;
                }

                $upcomingItems[] = [
                    'date_label' => $date->format('M d'),
                    'time_label' => $this->toTimeLabel((string) $daySchedule->start_time, (string) $daySchedule->end_time),
                    'title' => $this->resolveScheduleTitle($daySchedule),
                ];
            }
        }

        return $upcomingItems;
    }
}
