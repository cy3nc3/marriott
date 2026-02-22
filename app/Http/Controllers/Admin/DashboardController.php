<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
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

        $unassignedSubjects = Subject::query()
            ->doesntHave('teachers')
            ->count();

        $sectionsWithoutAdviser = Section::query()
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            })
            ->whereNull('adviser_id')
            ->count();

        $conflictExposure = $this->calculateScheduleConflictExposure($activeYear?->id);
        $gradeLevelTrendPoints = $this->buildGradeLevelTrend($activeYear?->id);
        $enrollmentForecast = $this->buildEnrollmentForecast(
            $allYears,
            $activeYear,
            $enrollmentCountsByYear
        );

        $alerts = [];

        if ($enrollmentYoYGrowth <= -10) {
            $alerts[] = [
                'id' => 'enrollment-yoy',
                'title' => 'Enrollment trend dropped significantly',
                'message' => "YoY enrollment moved {$enrollmentYoYGrowth}% compared to the previous school year.",
                'severity' => 'critical',
            ];
        } elseif ($enrollmentYoYGrowth < 0) {
            $alerts[] = [
                'id' => 'enrollment-yoy',
                'title' => 'Enrollment trend is declining',
                'message' => "YoY enrollment moved {$enrollmentYoYGrowth}% compared to the previous school year.",
                'severity' => 'warning',
            ];
        }

        if ($sectionsWithoutAdviser > 0) {
            $alerts[] = [
                'id' => 'adviser-gap',
                'title' => 'Sections without advisers detected',
                'message' => "{$sectionsWithoutAdviser} section(s) are still unassigned.",
                'severity' => $sectionsWithoutAdviser >= 5 ? 'critical' : 'warning',
            ];
        }

        if ($unassignedSubjects > 0) {
            $alerts[] = [
                'id' => 'subject-gap',
                'title' => 'Unassigned subjects require staffing',
                'message' => "{$unassignedSubjects} subject(s) have no qualified teacher assignment.",
                'severity' => $unassignedSubjects >= 10 ? 'critical' : 'warning',
            ];
        }

        if ($conflictExposure['total_conflicts'] > 0) {
            $alerts[] = [
                'id' => 'schedule-conflict',
                'title' => 'Schedule conflict exposure detected',
                'message' => "{$conflictExposure['total_conflicts']} active conflict pair(s) found in schedule grid.",
                'severity' => $conflictExposure['total_conflicts'] >= 10 ? 'critical' : 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'admin-stable',
                'title' => 'Academic operations are stable',
                'message' => 'Enrollment trend, staffing, and schedule health are within expected thresholds.',
                'severity' => 'info',
            ];
        }

        return Inertia::render('admin/dashboard', [
            'kpis' => [
                [
                    'id' => 'enrollment-yoy-growth',
                    'label' => 'Enrollment YoY Growth',
                    'value' => $this->formatSignedPercent($enrollmentYoYGrowth),
                    'meta' => $previousYear
                        ? "Current school year vs {$previousYear->name}"
                        : 'No previous school year baseline',
                ],
                [
                    'id' => 'unassigned-subjects',
                    'label' => 'Unassigned Subjects',
                    'value' => $unassignedSubjects,
                    'meta' => 'Subjects without teacher mapping',
                ],
                [
                    'id' => 'sections-without-adviser',
                    'label' => 'Sections Without Adviser',
                    'value' => $sectionsWithoutAdviser,
                    'meta' => 'Current school year',
                ],
                [
                    'id' => 'schedule-conflicts',
                    'label' => 'Schedule Conflict Exposure',
                    'value' => $conflictExposure['total_conflicts'],
                    'meta' => "{$conflictExposure['section_conflicts']} section conflicts, {$conflictExposure['teacher_conflicts']} teacher conflicts",
                ],
            ],
            'alerts' => array_values($alerts),
            'trends' => [
                [
                    'id' => 'grade-level-enrollment',
                    'label' => 'Grade-Level Enrollment',
                    'summary' => 'Current male and female enrollment distribution by grade level',
                    'display' => 'bar',
                    'points' => $gradeLevelTrendPoints,
                    'chart' => [
                        'x_key' => 'grade_level',
                        'rows' => collect($gradeLevelTrendPoints)
                            ->map(function (array $point): array {
                                return [
                                    'grade_level' => $point['label'],
                                    'male' => $point['male'],
                                    'female' => $point['female'],
                                    'total' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'male',
                                'label' => 'Male',
                            ],
                            [
                                'key' => 'female',
                                'label' => 'Female',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'enrollment-forecast',
                    'label' => 'Enrollment Forecast (SY)',
                    'summary' => 'Past 5 school years, current school year, and upcoming forecast',
                    'display' => 'area',
                    'points' => $enrollmentForecast['points'],
                    'chart' => [
                        'x_key' => 'school_year',
                        'rows' => $enrollmentForecast['rows'],
                        'series' => [
                            [
                                'key' => 'actual',
                                'label' => 'Actual',
                            ],
                            [
                                'key' => 'forecast',
                                'label' => 'Forecast',
                                'dashed' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'action_links' => [
                [
                    'id' => 'open-schedule-builder',
                    'label' => 'Open Schedule Builder',
                    'href' => route('admin.schedule_builder'),
                ],
                [
                    'id' => 'open-section-manager',
                    'label' => 'Open Section Manager',
                    'href' => route('admin.section_manager'),
                ],
                [
                    'id' => 'open-curriculum-manager',
                    'label' => 'Open Curriculum Manager',
                    'href' => route('admin.curriculum_manager'),
                ],
            ],
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

    /**
     * @return array{section_conflicts: int, teacher_conflicts: int, total_conflicts: int}
     */
    private function calculateScheduleConflictExposure(?int $academicYearId): array
    {
        if (! $academicYearId) {
            return [
                'section_conflicts' => 0,
                'teacher_conflicts' => 0,
                'total_conflicts' => 0,
            ];
        }

        $schedules = ClassSchedule::query()
            ->with([
                'section:id,adviser_id,academic_year_id',
                'subjectAssignment.teacherSubject:id,teacher_id',
            ])
            ->whereHas('section', function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->get([
                'id',
                'section_id',
                'subject_assignment_id',
                'type',
                'day',
                'start_time',
                'end_time',
            ]);

        $sectionConflicts = [];
        $teacherConflicts = [];

        $groupedByDay = $schedules->groupBy('day');
        foreach ($groupedByDay as $daySchedules) {
            $scheduleRows = $daySchedules->values();
            $totalRows = $scheduleRows->count();

            for ($left = 0; $left < $totalRows; $left++) {
                $leftSchedule = $scheduleRows[$left];
                $leftTeacherId = $leftSchedule->subjectAssignment?->teacherSubject?->teacher_id
                    ?? $leftSchedule->section?->adviser_id;

                for ($right = $left + 1; $right < $totalRows; $right++) {
                    $rightSchedule = $scheduleRows[$right];
                    if (! $this->hasOverlap(
                        (string) $leftSchedule->start_time,
                        (string) $leftSchedule->end_time,
                        (string) $rightSchedule->start_time,
                        (string) $rightSchedule->end_time
                    )) {
                        continue;
                    }

                    if ((int) $leftSchedule->section_id === (int) $rightSchedule->section_id) {
                        $sectionConflicts[] = $this->conflictPairKey((int) $leftSchedule->id, (int) $rightSchedule->id);
                    }

                    $rightTeacherId = $rightSchedule->subjectAssignment?->teacherSubject?->teacher_id
                        ?? $rightSchedule->section?->adviser_id;

                    if ($leftTeacherId && $rightTeacherId && (int) $leftTeacherId === (int) $rightTeacherId) {
                        $teacherConflicts[] = $this->conflictPairKey((int) $leftSchedule->id, (int) $rightSchedule->id);
                    }
                }
            }
        }

        $sectionConflicts = array_values(array_unique($sectionConflicts));
        $teacherConflicts = array_values(array_unique($teacherConflicts));

        return [
            'section_conflicts' => count($sectionConflicts),
            'teacher_conflicts' => count($teacherConflicts),
            'total_conflicts' => count(array_unique(array_merge($sectionConflicts, $teacherConflicts))),
        ];
    }

    private function hasOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    private function conflictPairKey(int $leftId, int $rightId): string
    {
        $ordered = [$leftId, $rightId];
        sort($ordered);

        return $ordered[0].'-'.$ordered[1];
    }
}
