<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Services\DashboardCacheService;
use App\Services\DashboardDecisionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDecisionService $dashboardDecisionService,
    ) {}

    public function index(): Response
    {
        $allYears = AcademicYear::query()
            ->orderBy('start_date')
            ->get(['id', 'name', 'status', 'start_date']);

        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()
                ->where('status', 'upcoming')
                ->orderBy('start_date')
                ->first()
            ?? $allYears->last();

        $enrollmentCountsByYear = Enrollment::query()
            ->where('status', 'enrolled')
            ->selectRaw('academic_year_id, count(*) as total')
            ->groupBy('academic_year_id')
            ->pluck('total', 'academic_year_id');

        $currentEnrolledCount = $activeYear
            ? (int) ($enrollmentCountsByYear[$activeYear->id] ?? 0)
            : 0;

        $previousYear = null;
        if ($activeYear) {
            $previousYear = $allYears
                ->filter(function (AcademicYear $year) use ($activeYear): bool {
                    return $year->start_date < $activeYear->start_date;
                })
                ->last();
        }

        $previousEnrolledCount = $previousYear
            ? (int) ($enrollmentCountsByYear[$previousYear->id] ?? 0)
            : 0;

        $enrollmentYoYGrowth = $previousEnrolledCount > 0
            ? round((($currentEnrolledCount - $previousEnrolledCount) / $previousEnrolledCount) * 100, 2)
            : 0.0;

        $sectionsWithoutAdviser = Section::query()
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            })
            ->whereNull('adviser_id')
            ->count();

        $cachedAnalytics = DashboardCacheService::remember(
            'admin:dashboard:year-'.($activeYear?->id ?? 'none'),
            function () use ($allYears, $activeYear, $enrollmentCountsByYear): array {
                return [
                    'grade_level_trend_points' => $this->buildGradeLevelTrend($activeYear?->id),
                    'enrollment_forecast' => $this->buildEnrollmentForecast(
                        $allYears,
                        $activeYear,
                        $enrollmentCountsByYear
                    ),
                ];
            }
        );

        $gradeLevelTrendPoints = $cachedAnalytics['grade_level_trend_points'];
        $enrollmentForecast = $cachedAnalytics['enrollment_forecast'];
        $forecastRows = collect($enrollmentForecast['rows'] ?? []);
        $nextYearForecastCount = (int) ($forecastRows->last()['forecast'] ?? 0);

        $currentSectionCount = Section::query()
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->count();
        $targetSectionSize = 50;
        $projectedSectionsNeeded = $nextYearForecastCount > 0
            ? (int) ceil($nextYearForecastCount / $targetSectionSize)
            : 0;
        $sectionCapacityGap = $projectedSectionsNeeded - $currentSectionCount;

        $subjectDemandRows = Subject::query()
            ->withCount([
                'teachers as assigned_teacher_count' => function ($query) {
                    $query
                        ->where('users.is_active', true)
                        ->where(function ($qualificationQuery) {
                            $qualificationQuery
                                ->whereIn('teacher_subjects.qualification_status', ['fully_qualified', 'provisionally_qualified'])
                                ->orWhereHas('teacherProfile', function ($profileQuery): void {
                                    $profileQuery->whereIn('qualification_status', ['fully_qualified', 'provisionally_qualified']);
                                });
                        });
                },
            ])
            ->orderBy('subject_name')
            ->get(['id', 'subject_name'])
            ->map(function (Subject $subject): array {
                $assignedTeacherCount = (int) ($subject->assigned_teacher_count ?? 0);
                $minimumNeeded = 1;
                $gap = max($minimumNeeded - $assignedTeacherCount, 0);

                return [
                    'subject' => (string) $subject->subject_name,
                    'assigned_teachers' => $assignedTeacherCount,
                    'minimum_needed' => $minimumNeeded,
                    'gap' => $gap,
                ];
            })
            ->sortByDesc('gap')
            ->values()
            ->all();

        $pendingTeacherDemandCount = collect($subjectDemandRows)->where('gap', '>', 0)->count();
        $subjectDemandRows = collect($subjectDemandRows)->take(12)->values()->all();

        $scheduledMinutesBySectionSubject = ClassSchedule::query()
            ->where('class_schedules.type', 'academic')
            ->whereNotNull('class_schedules.subject_assignment_id')
            ->when($activeYear, fn ($query) => $query->where('sections.academic_year_id', $activeYear->id))
            ->join('sections', 'class_schedules.section_id', '=', 'sections.id')
            ->get(['class_schedules.subject_assignment_id', 'class_schedules.start_time', 'class_schedules.end_time'])
            ->groupBy('subject_assignment_id')
            ->map(function (Collection $rows): float {
                return $rows->sum(function (ClassSchedule $schedule): float {
                    $start = strtotime((string) $schedule->start_time);
                    $end = strtotime((string) $schedule->end_time);

                    return $start !== false && $end !== false && $end > $start
                        ? ($end - $start) / 60
                        : 0.0;
                });
            });

        $subjectAssignments = SubjectAssignment::query()
            ->with('teacherSubject.subject:id,required_weekly_minutes')
            ->when($activeYear, fn ($query) => $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('academic_year_id', $activeYear->id)))
            ->get(['id', 'section_id', 'teacher_subject_id']);

        $incompleteSchedulesCount = $subjectAssignments
            ->filter(function (SubjectAssignment $assignment) use ($scheduledMinutesBySectionSubject): bool {
                $requiredMinutes = (int) ($assignment->teacherSubject?->subject?->required_weekly_minutes ?? 200);
                $scheduledMinutes = (float) ($scheduledMinutesBySectionSubject[$assignment->id] ?? 0);

                return $scheduledMinutes < $requiredMinutes;
            })
            ->count();

        $gradeSlaBase = GradeSubmission::query()
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->where('quarter', (string) ($activeYear?->current_quarter ?: '1'));
        $totalSubmissionRows = (clone $gradeSlaBase)->count();
        $verifiedSubmissionRows = (clone $gradeSlaBase)->where('status', 'verified')->count();
        $gradeVerificationSla = $totalSubmissionRows > 0
            ? round(($verifiedSubmissionRows / $totalSubmissionRows) * 100, 2)
            : 100.0;

        $alerts = $this->dashboardDecisionService->adminAlerts(
            $enrollmentYoYGrowth,
            $sectionsWithoutAdviser,
            $pendingTeacherDemandCount,
            $sectionCapacityGap,
        );

        $gradeVerificationPipelineRows = collect(['pending', 'submitted', 'verified', 'returned'])
            ->map(function (string $status) use ($gradeSlaBase): array {
                return [
                    'status' => ucfirst($status),
                    'count' => (int) (clone $gradeSlaBase)->where('status', $status)->count(),
                ];
            })
            ->values()
            ->all();

        $actionLinks = [
            [
                'id' => 'open-grade-verification',
                'label' => 'Open Grade Verification',
                'href' => route('admin.grade_verification'),
            ],
            [
                'id' => 'open-section-manager',
                'label' => 'Open Section Manager',
                'href' => route('admin.section_manager'),
            ],
            [
                'id' => 'open-teacher-profiles',
                'label' => 'Open Teacher Profiles',
                'href' => route('admin.teacher_profiles'),
            ],
        ];
        $actionLinks = $this->dashboardDecisionService->prioritizeAdminActionLinks(
            $actionLinks,
            $sectionCapacityGap,
            $pendingTeacherDemandCount,
            $gradeVerificationSla,
        );

        $kpis = [
            [
                'id' => 'admin-capacity-gap',
                'label' => 'Needed Sections',
                'value' => $sectionCapacityGap > 0 ? '+'.$sectionCapacityGap : (string) $sectionCapacityGap,
                'meta' => "{$projectedSectionsNeeded} projected from {$nextYearForecastCount} students at {$targetSectionSize}/section",
            ],
            [
                'id' => 'admin-teacher-demand',
                'label' => 'Subjects Needing Teachers',
                'value' => $pendingTeacherDemandCount,
                'meta' => 'Subjects without qualified teacher coverage',
            ],
            [
                'id' => 'admin-incomplete-schedules',
                'label' => 'Incomplete Schedules',
                'value' => $incompleteSchedulesCount,
                'meta' => 'Compared with each subject weekly minutes',
            ],
            [
                'id' => 'admin-grade-verification-sla',
                'label' => 'Grade Verification Status',
                'value' => number_format($gradeVerificationSla, 2).'%',
                'meta' => "{$verifiedSubmissionRows} of {$totalSubmissionRows} verified",
            ],
            [
                'id' => 'admin-next-sy-forecast',
                'label' => 'Next School Year Enrollment',
                'value' => $nextYearForecastCount,
                'meta' => $this->buildNextAcademicYearName((string) ($activeYear?->name ?? 'Upcoming')),
            ],
        ];

        $kpis = $this->dashboardDecisionService->prioritizeAdminKpis(
            $kpis,
            $sectionCapacityGap,
            $pendingTeacherDemandCount,
            $gradeVerificationSla,
        );

        return Inertia::render('admin/dashboard', [
            'kpis' => $kpis,
            'alerts' => array_values($alerts),
            'trends' => $this->dashboardDecisionService->adminTrends(
                $currentSectionCount,
                $projectedSectionsNeeded,
                $sectionCapacityGap,
                $subjectDemandRows,
                $gradeVerificationPipelineRows,
            ),
            'action_links' => $actionLinks,
        ]);
    }

    /**
     * @return array{rows: array<int, array<string, int|string|null>>, points: array<int, array{label: string, value: int}>}
     */
    private function buildEnrollmentForecast(
        Collection $allYears,
        ?AcademicYear $activeYear,
        Collection $enrollmentCountsByYear
    ): array {
        if (! $activeYear) {
            return [
                'rows' => [],
                'points' => [],
            ];
        }

        $historyYears = $allYears
            ->filter(function (AcademicYear $year) use ($activeYear): bool {
                return $year->start_date < $activeYear->start_date;
            })
            ->values();

        $historyYears = $historyYears->slice(max($historyYears->count() - 5, 0))->values();

        /** @var Collection<int, AcademicYear> $forecastYears */
        $forecastYears = $historyYears->push($activeYear)->unique('id')->values();

        $actualRows = $forecastYears
            ->map(function (AcademicYear $year) use ($activeYear, $enrollmentCountsByYear): array {
                $actual = (int) ($enrollmentCountsByYear[$year->id] ?? 0);
                $isActiveYear = (int) $year->id === (int) $activeYear->id;

                return [
                    'school_year' => $year->name,
                    'actual' => $actual,
                    'forecast' => $isActiveYear ? $actual : null,
                    'is_forecast' => false,
                ];
            })
            ->values();

        $growthRate = $this->resolveAverageGrowthRate($allYears, $activeYear, $enrollmentCountsByYear);
        $clampedGrowthRate = max(min($growthRate, 0.20), -0.15);

        $currentActual = (int) ($enrollmentCountsByYear[$activeYear->id] ?? 0);
        $forecastNext = (int) round($currentActual * (1 + $clampedGrowthRate));

        $rows = $actualRows
            ->push([
                'school_year' => $this->buildNextAcademicYearName($activeYear->name),
                'actual' => null,
                'forecast' => $forecastNext,
                'is_forecast' => true,
            ])
            ->values()
            ->all();

        $points = collect($rows)
            ->map(function (array $row): array {
                $value = $row['actual'] ?? $row['forecast'] ?? 0;

                return [
                    'label' => (string) $row['school_year'],
                    'value' => (int) $value,
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'points' => $points,
        ];
    }

    private function resolveAverageGrowthRate(
        Collection $allYears,
        AcademicYear $activeYear,
        Collection $enrollmentCountsByYear
    ): float {
        $yearsForGrowth = $allYears
            ->filter(function (AcademicYear $year) use ($activeYear): bool {
                return $year->start_date <= $activeYear->start_date;
            })
            ->values();

        $rates = [];
        for ($index = 1; $index < $yearsForGrowth->count(); $index++) {
            $previousYear = $yearsForGrowth[$index - 1];
            $currentYear = $yearsForGrowth[$index];

            $previousCount = (int) ($enrollmentCountsByYear[$previousYear->id] ?? 0);
            $currentCount = (int) ($enrollmentCountsByYear[$currentYear->id] ?? 0);

            if ($previousCount <= 0) {
                continue;
            }

            $rates[] = ($currentCount - $previousCount) / $previousCount;
        }

        if ($rates === []) {
            return 0.0;
        }

        $recentRates = array_slice($rates, -3);

        return (float) (array_sum($recentRates) / count($recentRates));
    }

    /**
     * @return array<int, array{label: string, value: int, male: int, female: int}>
     */
    private function buildGradeLevelTrend(?int $academicYearId): array
    {
        if (! $academicYearId) {
            return [];
        }

        return Enrollment::query()
            ->where('enrollments.academic_year_id', $academicYearId)
            ->where('enrollments.status', 'enrolled')
            ->join('grade_levels', 'enrollments.grade_level_id', '=', 'grade_levels.id')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->select(
                'grade_levels.name',
                'grade_levels.level_order',
                DB::raw('count(*) as total_count'),
                DB::raw("sum(case when lower(coalesce(students.gender, '')) = 'male' then 1 else 0 end) as male_count"),
                DB::raw("sum(case when lower(coalesce(students.gender, '')) = 'female' then 1 else 0 end) as female_count"),
            )
            ->groupBy('grade_levels.name', 'grade_levels.level_order')
            ->orderBy('grade_levels.level_order')
            ->get()
            ->map(function ($row): array {
                return [
                    'label' => $row->name,
                    'value' => (int) $row->total_count,
                    'male' => (int) $row->male_count,
                    'female' => (int) $row->female_count,
                ];
            })
            ->values()
            ->all();
    }

    private function buildNextAcademicYearName(string $currentYearName): string
    {
        if (preg_match('/^(\d{4})-(\d{4})$/', $currentYearName, $matches) === 1) {
            $start = (int) $matches[1] + 1;
            $end = (int) $matches[2] + 1;

            return "{$start}-{$end}";
        }

        return 'Upcoming Forecast';
    }

    private function formatSignedPercent(float $value): string
    {
        if ($value > 0) {
            return '+'.number_format($value, 2).'%';
        }

        return number_format($value, 2).'%';
    }
}
