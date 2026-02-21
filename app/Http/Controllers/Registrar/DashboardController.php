<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $queueStatuses = ['pending', 'pending_intake', 'for_cashier_payment', 'partial_payment'];

        $enrollmentScope = Enrollment::query()
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            });

        $queueScope = (clone $enrollmentScope)
            ->whereIn('status', $queueStatuses);

        $intakeQueuePressure = (clone $queueScope)
            ->whereIn('status', ['pending', 'pending_intake'])
            ->count();

        $forCashierPipeline = (clone $queueScope)
            ->whereIn('status', ['for_cashier_payment', 'partial_payment'])
            ->count();

        $studentScope = Student::query()
            ->whereHas('enrollments', function ($query) use ($activeYear) {
                if ($activeYear) {
                    $query->where('academic_year_id', $activeYear->id);
                }
            });

        $totalEnrolledStudents = (clone $studentScope)->count();
        $lisSyncedStudents = (clone $studentScope)
            ->where('is_lis_synced', true)
            ->count();
        $syncErrorBacklog = (clone $studentScope)
            ->where('sync_error_flag', true)
            ->count();

        $lisSyncRate = $totalEnrolledStudents > 0
            ? round(($lisSyncedStudents / $totalEnrolledStudents) * 100, 2)
            : 0.0;

        $today = now()->startOfDay();
        $queueAgingBuckets = [
            '0-2 days' => (clone $queueScope)
                ->whereDate('created_at', '>=', $today->copy()->subDays(2)->toDateString())
                ->count(),
            '3-7 days' => (clone $queueScope)
                ->whereDate('created_at', '>=', $today->copy()->subDays(7)->toDateString())
                ->whereDate('created_at', '<=', $today->copy()->subDays(3)->toDateString())
                ->count(),
            '8-14 days' => (clone $queueScope)
                ->whereDate('created_at', '>=', $today->copy()->subDays(14)->toDateString())
                ->whereDate('created_at', '<=', $today->copy()->subDays(8)->toDateString())
                ->count(),
            '15+ days' => (clone $queueScope)
                ->whereDate('created_at', '<=', $today->copy()->subDays(15)->toDateString())
                ->count(),
        ];

        $queueAgingPoints = collect($queueAgingBuckets)
            ->map(function (int $count, string $bucket): array {
                return [
                    'label' => $bucket,
                    'value' => $count,
                ];
            })
            ->values()
            ->all();

        $lisSyncDistributionPoints = [
            [
                'label' => 'Synced',
                'value' => $lisSyncedStudents,
            ],
            [
                'label' => 'Pending',
                'value' => max($totalEnrolledStudents - $lisSyncedStudents - $syncErrorBacklog, 0),
            ],
            [
                'label' => 'Errors',
                'value' => $syncErrorBacklog,
            ],
        ];

        $alerts = [];

        if ($intakeQueuePressure >= 60) {
            $alerts[] = [
                'id' => 'queue-pressure',
                'title' => 'High intake queue pressure',
                'message' => "{$intakeQueuePressure} intake records are waiting for registrar processing.",
                'severity' => 'critical',
            ];
        } elseif ($intakeQueuePressure >= 25) {
            $alerts[] = [
                'id' => 'queue-pressure',
                'title' => 'Intake queue is rising',
                'message' => "{$intakeQueuePressure} intake records are waiting for registrar processing.",
                'severity' => 'warning',
            ];
        }

        if ($lisSyncRate < 70) {
            $alerts[] = [
                'id' => 'lis-sync',
                'title' => 'Low LIS sync coverage',
                'message' => "Only {$lisSyncRate}% of enrolled students are marked as LIS synced.",
                'severity' => 'critical',
            ];
        } elseif ($lisSyncRate < 90) {
            $alerts[] = [
                'id' => 'lis-sync',
                'title' => 'LIS sync completion needs follow-up',
                'message' => "Current LIS sync rate is {$lisSyncRate}%.",
                'severity' => 'warning',
            ];
        }

        if ($syncErrorBacklog > 0) {
            $alerts[] = [
                'id' => 'sync-errors',
                'title' => 'LIS sync errors need review',
                'message' => "{$syncErrorBacklog} student records are flagged with sync errors.",
                'severity' => $syncErrorBacklog >= 15 ? 'critical' : 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'registrar-stable',
                'title' => 'Registrar dashboard is stable',
                'message' => 'Intake, cashier handoff, and LIS synchronization are within target thresholds.',
                'severity' => 'info',
            ];
        }

        return Inertia::render('registrar/dashboard', [
            'kpis' => [
                [
                    'id' => 'intake-queue',
                    'label' => 'Intake Queue Pressure',
                    'value' => $intakeQueuePressure,
                    'meta' => 'Pending registrar intake',
                ],
                [
                    'id' => 'cashier-pipeline',
                    'label' => 'For Cashier Pipeline',
                    'value' => $forCashierPipeline,
                    'meta' => 'Awaiting payment processing',
                ],
                [
                    'id' => 'lis-sync-rate',
                    'label' => 'LIS Sync Rate',
                    'value' => number_format($lisSyncRate, 2).'%',
                    'meta' => "{$lisSyncedStudents} / {$totalEnrolledStudents} students",
                ],
                [
                    'id' => 'sync-error-backlog',
                    'label' => 'Sync Error Backlog',
                    'value' => $syncErrorBacklog,
                    'meta' => 'Records requiring manual review',
                ],
            ],
            'alerts' => array_values($alerts),
            'trends' => [
                [
                    'id' => 'queue-aging-buckets',
                    'label' => 'Queue Aging Buckets',
                    'summary' => 'Current queue load by record age',
                    'display' => 'bar',
                    'points' => $queueAgingPoints,
                    'chart' => [
                        'x_key' => 'bucket',
                        'rows' => collect($queueAgingPoints)
                            ->map(function (array $point): array {
                                return [
                                    'bucket' => $point['label'],
                                    'records' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'records',
                                'label' => 'Records',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'lis-sync-distribution',
                    'label' => 'LIS Sync Distribution',
                    'summary' => 'Current student sync status mix',
                    'display' => 'bar',
                    'points' => $lisSyncDistributionPoints,
                    'chart' => [
                        'x_key' => 'status',
                        'rows' => collect($lisSyncDistributionPoints)
                            ->map(function (array $point): array {
                                return [
                                    'status' => $point['label'],
                                    'students' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'students',
                                'label' => 'Students',
                            ],
                        ],
                    ],
                ],
            ],
            'action_links' => [
                [
                    'id' => 'open-enrollment-queue',
                    'label' => 'Open Enrollment Queue',
                    'href' => route('registrar.enrollment'),
                ],
                [
                    'id' => 'open-student-directory',
                    'label' => 'Open Student Directory',
                    'href' => route('registrar.student_directory'),
                ],
                [
                    'id' => 'open-remedial-entry',
                    'label' => 'Open Remedial Entry',
                    'href' => route('registrar.remedial_entry'),
                ],
            ],
        ]);
    }
}
