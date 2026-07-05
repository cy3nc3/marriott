<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Services\DashboardCacheService;
use App\Services\DashboardDecisionService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDecisionService $dashboardDecisionService,
    ) {}

    public function index(): Response
    {
        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $cachedSummary = DashboardCacheService::remember(
            'registrar:dashboard:year-'.($activeYear?->id ?? 'none'),
            function () use ($activeYear): array {
                $queueStatuses = ['for_cashier_payment'];

                $enrollmentScope = Enrollment::query()
                    ->when($activeYear, function ($query) use ($activeYear) {
                        $query->where('academic_year_id', $activeYear->id);
                    });

                $queueScope = (clone $enrollmentScope)
                    ->whereIn('status', $queueStatuses);

                $intakeQueuePressure = (clone $queueScope)
                    ->where('status', 'for_cashier_payment')
                    ->count();

                $forCashierPipeline = (clone $queueScope)->count();

                $studentScope = Student::query()
                    ->whereHas('enrollments', function ($query) use ($activeYear) {
                        if ($activeYear) {
                            $query->where('academic_year_id', $activeYear->id);
                        }
                    });

                $totalEnrolledStudents = (clone $studentScope)->count();

                $sectionCapacityRows = Section::query()
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->withCount(['enrollments' => fn ($query) => $query->where('status', 'enrolled')])
                    ->get(['id', 'name'])
                    ->map(function ($section) {
                        $targetCapacity = 40;
                        $count = (int) $section->enrollments_count;
                        $utilization = $targetCapacity > 0 ? round(($count / $targetCapacity) * 100, 2) : 0;

                        return [
                            'section' => $section->name,
                            'enrolled' => $count,
                            'capacity' => $targetCapacity,
                            'utilization' => $utilization,
                        ];
                    })
                    ->sortByDesc('utilization')
                    ->values()
                    ->all();

                $bottleneckCount = collect($sectionCapacityRows)->where('utilization', '>=', 90)->count();
                $waitingForSectionCount = (clone $enrollmentScope)
                    ->whereIn('status', ['for_cashier_payment', 'enrolled'])
                    ->whereNotNull('grade_level_id')
                    ->whereNull('section_id')
                    ->count();

                return [
                    'intake_queue_pressure' => $intakeQueuePressure,
                    'for_cashier_pipeline' => $forCashierPipeline,
                    'total_enrolled_students' => $totalEnrolledStudents,
                    'section_capacity_rows' => $sectionCapacityRows,
                    'bottleneck_count' => $bottleneckCount,
                    'waiting_for_section_count' => $waitingForSectionCount,
                ];
            }
        );

        $intakeQueuePressure = (int) $cachedSummary['intake_queue_pressure'];
        $forCashierPipeline = (int) $cachedSummary['for_cashier_pipeline'];
        $totalEnrolledStudents = (int) $cachedSummary['total_enrolled_students'];

        $alerts = $this->dashboardDecisionService->registrarAlerts($intakeQueuePressure);

        $decisionCards = [];

        $enrollmentScope = Enrollment::query()
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id));

        $queueItems = (clone $enrollmentScope)
            ->where('status', 'for_cashier_payment')
            ->get(['id', 'created_at', 'section_id']);

        $avgQueueAgeHours = $queueItems->count() > 0
            ? round($queueItems->avg(fn (Enrollment $enrollment): float => (float) $enrollment->created_at?->diffInHours(now()) ?: 0), 1)
            : 0.0;

        $complianceBase = (clone $enrollmentScope)
            ->whereIn('status', ['for_cashier_payment', 'enrolled'])
            ->get(['id', 'report_card_submitted', 'birth_certificate_submitted', 'grade_level_id']);
        $requirementsMissingCount = $complianceBase
            ->filter(fn (Enrollment $enrollment): bool => ! $enrollment->report_card_submitted || ! $enrollment->birth_certificate_submitted)
            ->count();
        $requirementsCompleteCount = max($complianceBase->count() - $requirementsMissingCount, 0);
        $requirementsComplianceRate = $complianceBase->count() > 0
            ? round(($requirementsCompleteCount / $complianceBase->count()) * 100, 2)
            : 0.0;

        $sectionQueueRows = Enrollment::query()
            ->with('section:id,name')
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->where('status', 'for_cashier_payment')
            ->get(['id', 'section_id'])
            ->groupBy('section_id')
            ->map(function ($records, $sectionId): array {
                $sectionName = optional($records->first()?->section)->name ?: 'Unassigned';

                return [
                    'section' => $sectionName,
                    'items' => $records->count(),
                ];
            })
            ->sortByDesc('items')
            ->take(8)
            ->values()
            ->all();

        $recentAcademicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->limit(4)
            ->get(['id', 'name', 'start_date'])
            ->sortBy('start_date')
            ->values();

        $continuityRows = [];
        $returningContinuityRate = 0.0;
        for ($index = 1; $index < $recentAcademicYears->count(); $index++) {
            $previousYear = $recentAcademicYears[$index - 1];
            $currentYear = $recentAcademicYears[$index];
            $previousStudentIds = Enrollment::query()
                ->where('academic_year_id', $previousYear->id)
                ->whereIn('status', ['enrolled', 'for_cashier_payment'])
                ->pluck('student_id')
                ->unique();
            $currentStudentIds = Enrollment::query()
                ->where('academic_year_id', $currentYear->id)
                ->whereIn('status', ['enrolled', 'for_cashier_payment'])
                ->pluck('student_id')
                ->unique();
            $returningCount = $currentStudentIds->intersect($previousStudentIds)->count();
            $nonReturningCount = max($previousStudentIds->diff($currentStudentIds)->count(), 0);
            $newOrTransfereeCount = max($currentStudentIds->diff($previousStudentIds)->count(), 0);
            $rate = $previousStudentIds->count() > 0
                ? round(($returningCount / $previousStudentIds->count()) * 100, 2)
                : 0.0;
            $continuityRows[] = [
                'transition' => "{$previousYear->name} -> {$currentYear->name}",
                'rate' => $rate,
                're_enrolled' => $returningCount,
                'did_not_enroll' => $nonReturningCount,
                'new_or_transferee' => $newOrTransfereeCount,
            ];
            if ($activeYear && (int) $currentYear->id === (int) $activeYear->id) {
                $returningContinuityRate = $rate;
            }
        }

        $actionLinks = [
            [
                'id' => 'open-enrollment-queue',
                'label' => 'Process Oldest Queue First',
                'href' => route('registrar.enrollment', [
                    'status' => 'for_cashier_payment',
                    'sort' => 'oldest',
                ]),
            ],
            [
                'id' => 'open-missing-requirements',
                'label' => 'Review Missing Requirements',
                'href' => route('registrar.student_directory', [
                    'status' => 'enrolled_with_missing_requirements',
                    'sort' => 'a_z',
                ]),
            ],
            [
                'id' => 'open-conditional-records',
                'label' => 'Review Conditional Records',
                'href' => route('registrar.permanent_records', [
                    'record_status' => 'conditional',
                ]),
            ],
        ];
        $actionLinks = $this->dashboardDecisionService->prioritizeRegistrarActionLinks(
            $actionLinks,
            $requirementsMissingCount,
            $requirementsComplianceRate,
            $intakeQueuePressure,
        );

        $kpis = [
            [
                'id' => 'registrar-capacity-bottlenecks',
                'label' => 'Students Waiting for Section',
                'value' => (int) $cachedSummary['waiting_for_section_count'],
                'meta' => 'Students with grade level but no section',
            ],
            [
                'id' => 'requirements-compliance',
                'label' => 'Students With Missing Requirements',
                'value' => $requirementsMissingCount,
                'meta' => "{$requirementsMissingCount} with missing requirements",
            ],
            [
                'id' => 'for-cashier-pipeline',
                'label' => 'Enrollment Queue',
                'value' => $forCashierPipeline,
                'meta' => 'Enrollments waiting for cashier payment',
            ],
            [
                'id' => 'intake-queue-age',
                'label' => 'Average Queue Wait',
                'value' => $avgQueueAgeHours.'h',
                'meta' => "{$intakeQueuePressure} records waiting",
            ],
        ];

        $kpis = $this->dashboardDecisionService->prioritizeRegistrarKpis(
            $kpis,
            $requirementsMissingCount,
            $requirementsComplianceRate,
            $intakeQueuePressure,
        );

        return Inertia::render('registrar/dashboard', [
            'kpis' => $kpis,
            'alerts' => array_values($alerts),
            'trends' => $this->dashboardDecisionService->registrarTrends(
                $cachedSummary['section_capacity_rows'],
                $sectionQueueRows,
                $requirementsCompleteCount,
                $requirementsMissingCount,
                $continuityRows,
            ),
            'action_links' => $actionLinks,
            'decision_cards' => $decisionCards,
            'action_queue' => [],
        ]);
    }
}
