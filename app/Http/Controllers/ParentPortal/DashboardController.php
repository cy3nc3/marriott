<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $activeYear = $this->resolveActiveAcademicYear();
        $enrollment = $student ? $this->resolveCurrentEnrollment($student) : null;
        $currentQuarter = (string) ($activeYear?->current_quarter ?: '1');

        $sectionLabel = 'Unassigned';
        $generalAverage = null;
        $generalAverageTrend = null;
        $adviserName = 'Not assigned';
        $outstandingBalance = 0.0;
        $dueRiskRate = 0.0;
        $dueRiskLevel = 'Low';
        $recentPaymentTrend = [];
        $upcomingDuesTimeline = [];
        $nextDueLabel = 'No upcoming due';

        if ($student && $enrollment) {
            if ($enrollment->gradeLevel?->name && $enrollment->section?->name) {
                $sectionLabel = "{$enrollment->gradeLevel->name} - {$enrollment->section->name}";
            } elseif ($enrollment->gradeLevel?->name) {
                $sectionLabel = $enrollment->gradeLevel->name;
            }

            $academicYear = $enrollment->academicYear ?: $activeYear;
            $ledgerQuery = LedgerEntry::query()
                ->where('student_id', $student->id)
                ->when($academicYear, function ($query) use ($academicYear) {
                    $query->where('academic_year_id', $academicYear->id);
                });

            $totalCharges = (float) (clone $ledgerQuery)->sum('debit');
            $totalCredits = (float) (clone $ledgerQuery)->sum('credit');
            $outstandingBalance = round(max($totalCharges - $totalCredits, 0), 2);

            $grades = FinalGrade::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('quarter', ['1', '2', '3', '4'])
                ->get();

            $quarterAverages = collect(['1', '2', '3', '4'])
                ->map(function (string $quarter) use ($grades): array {
                    $quarterRows = $grades->where('quarter', $quarter);
                    $average = $quarterRows->isNotEmpty()
                        ? round((float) $quarterRows->avg('grade'), 2)
                        : null;

                    return [
                        'quarter' => $quarter,
                        'average' => $average,
                    ];
                });

            $currentQuarterEntry = $quarterAverages->firstWhere('quarter', $currentQuarter);
            $previousQuarterEntry = $quarterAverages->firstWhere('quarter', (string) max((int) $currentQuarter - 1, 1));

            $generalAverage = is_array($currentQuarterEntry)
                ? $currentQuarterEntry['average']
                : null;

            $previousQuarterAverage = is_array($previousQuarterEntry)
                ? $previousQuarterEntry['average']
                : null;

            if ($generalAverage !== null && $previousQuarterAverage !== null) {
                $generalAverageTrend = round($generalAverage - $previousQuarterAverage, 2);
            }

            $adviserName = $enrollment->section?->adviser
                ? trim("{$enrollment->section->adviser->first_name} {$enrollment->section->adviser->last_name}")
                : 'Not assigned';

            $billingSchedules = BillingSchedule::query()
                ->where('student_id', $student->id)
                ->when($academicYear, function ($query) use ($academicYear) {
                    $query->where('academic_year_id', $academicYear->id);
                })
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->orderBy('due_date')
                ->get(['due_date', 'amount_due', 'amount_paid']);

            $upcomingDueRows = $billingSchedules
                ->map(function (BillingSchedule $billingSchedule): ?array {
                    $outstanding = max(
                        (float) $billingSchedule->amount_due - (float) $billingSchedule->amount_paid,
                        0
                    );

                    if ($outstanding <= 0) {
                        return null;
                    }

                    return [
                        'due_date' => $billingSchedule->due_date?->toDateString(),
                        'label' => $billingSchedule->due_date?->format('M d') ?? 'No date',
                        'outstanding' => round($outstanding, 2),
                    ];
                })
                ->filter()
                ->values();

            $upcomingDuesTimeline = $upcomingDueRows
                ->take(4)
                ->map(function (array $row): array {
                    return [
                        'label' => $row['label'],
                        'value' => $row['outstanding'],
                    ];
                })
                ->values()
                ->all();

            $nextDueRow = $upcomingDueRows->first();
            if (is_array($nextDueRow)) {
                $nextDueLabel = "{$nextDueRow['label']} · ".$this->formatCurrency((float) $nextDueRow['outstanding']);
            }

            $totalOutstandingDues = (float) $upcomingDueRows->sum('outstanding');
            $overdueOutstandingDues = (float) $upcomingDueRows
                ->filter(function (array $row): bool {
                    return $row['due_date'] !== null
                        && $row['due_date'] < now()->toDateString();
                })
                ->sum('outstanding');

            $dueRiskRate = $totalOutstandingDues > 0
                ? round(($overdueOutstandingDues / $totalOutstandingDues) * 100, 2)
                : 0.0;

            if ($dueRiskRate >= 60) {
                $dueRiskLevel = 'Critical';
            } elseif ($dueRiskRate >= 30) {
                $dueRiskLevel = 'Warning';
            }

            $transactionByDay = Transaction::query()
                ->where('student_id', $student->id)
                ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
                ->selectRaw('DATE(created_at) as day, sum(total_amount) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day');

            $recentPaymentTrend = collect(range(6, 0, -1))
                ->map(function (int $daysAgo) use ($transactionByDay): array {
                    $day = now()->subDays($daysAgo)->toDateString();

                    return [
                        'label' => now()->subDays($daysAgo)->format('M d'),
                        'value' => round((float) ($transactionByDay[$day] ?? 0), 2),
                    ];
                })
                ->values()
                ->all();
        }

        $alerts = [];

        if ($dueRiskLevel === 'Critical') {
            $alerts[] = [
                'id' => 'due-risk',
                'title' => 'Outstanding balance has high due risk',
                'message' => "{$dueRiskRate}% of dues are already overdue.",
                'severity' => 'critical',
            ];
        } elseif ($dueRiskLevel === 'Warning') {
            $alerts[] = [
                'id' => 'due-risk',
                'title' => 'Outstanding dues need follow-up',
                'message' => "{$dueRiskRate}% of dues are already overdue.",
                'severity' => 'warning',
            ];
        }

        if ($generalAverage !== null && $generalAverage < 75) {
            $alerts[] = [
                'id' => 'academic-risk',
                'title' => 'Academic performance is below passing threshold',
                'message' => 'Current quarter general average is below 75.',
                'severity' => 'critical',
            ];
        } elseif ($generalAverage !== null && $generalAverage < 80) {
            $alerts[] = [
                'id' => 'academic-risk',
                'title' => 'Academic performance needs support',
                'message' => 'Current quarter general average is below 80.',
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'parent-stable',
                'title' => 'Parent dashboard is stable',
                'message' => 'Academic, balance, and due timeline indicators are within expected thresholds.',
                'severity' => 'info',
            ];
        }

        return Inertia::render('parent/dashboard', [
            'kpis' => [
                [
                    'id' => 'child-section',
                    'label' => 'Child Section',
                    'value' => $sectionLabel,
                    'meta' => "Adviser: {$adviserName}",
                ],
                [
                    'id' => 'child-general-average',
                    'label' => 'Child General Average',
                    'value' => $generalAverage !== null ? $this->formatGrade($generalAverage) : '-',
                    'meta' => $generalAverageTrend === null
                        ? 'Trend unavailable'
                        : ($generalAverageTrend >= 0
                            ? '+'.number_format($generalAverageTrend, 2).' vs previous quarter'
                            : number_format($generalAverageTrend, 2).' vs previous quarter'),
                ],
                [
                    'id' => 'outstanding-balance',
                    'label' => 'Outstanding Balance',
                    'value' => $this->formatCurrency($outstandingBalance),
                    'meta' => "Due risk: {$dueRiskLevel}",
                ],
                [
                    'id' => 'next-due',
                    'label' => 'Next Due',
                    'value' => $nextDueLabel,
                    'meta' => $dueRiskRate > 0
                        ? number_format($dueRiskRate, 2).'% overdue share'
                        : 'No overdue due share',
                ],
            ],
            'alerts' => array_values($alerts),
            'trends' => [
                [
                    'id' => 'recent-payment-trend',
                    'label' => 'Recent Payment Trend (Last 7 Days)',
                    'summary' => 'Daily payment amount posted for the child account',
                    'display' => 'line',
                    'points' => $recentPaymentTrend,
                    'chart' => [
                        'x_key' => 'day',
                        'rows' => collect($recentPaymentTrend)
                            ->map(function (array $point): array {
                                return [
                                    'day' => $point['label'],
                                    'payments' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'payments',
                                'label' => 'Payments',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'upcoming-dues-timeline',
                    'label' => 'Upcoming Dues Timeline',
                    'summary' => 'Next 4 unpaid or partially paid due items',
                    'display' => 'bar',
                    'points' => $upcomingDuesTimeline,
                    'chart' => [
                        'x_key' => 'due_date',
                        'rows' => collect($upcomingDuesTimeline)
                            ->map(function (array $point): array {
                                return [
                                    'due_date' => $point['label'],
                                    'amount_outstanding' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'amount_outstanding',
                                'label' => 'Outstanding',
                            ],
                        ],
                    ],
                ],
            ],
            'action_links' => [
                [
                    'id' => 'open-parent-grades',
                    'label' => 'Open Child Grades',
                    'href' => route('parent.grades'),
                ],
                [
                    'id' => 'open-parent-schedule',
                    'label' => 'Open Child Schedule',
                    'href' => route('parent.schedule'),
                ],
                [
                    'id' => 'open-parent-billing',
                    'label' => 'Open Billing Information',
                    'href' => route('parent.billing_information'),
                ],
            ],
            'child_context' => [
                'student_name' => $student ? trim("{$student->first_name} {$student->last_name}") : null,
                'section_label' => $sectionLabel,
                'adviser_name' => $adviserName,
                'next_due_label' => $nextDueLabel,
                'due_risk_level' => $dueRiskLevel,
                'due_risk_rate' => number_format($dueRiskRate, 2).'%',
            ],
        ]);
    }

    private function resolveStudent(?User $user): ?Student
    {
        if (! $user) {
            return null;
        }

        return $user->students()
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->first();
    }

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
        $activeYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeYearId) {
            $activeEnrollment = Enrollment::query()
                ->with([
                    'academicYear:id,name,status',
                    'gradeLevel:id,name',
                    'section:id,name,adviser_id',
                    'section.adviser:id,first_name,last_name',
                ])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYearId)
                ->first();

            if ($activeEnrollment) {
                return $activeEnrollment;
            }
        }

        return Enrollment::query()
            ->with([
                'academicYear:id,name,status',
                'gradeLevel:id,name',
                'section:id,name,adviser_id',
                'section.adviser:id,first_name,last_name',
            ])
            ->where('student_id', $student->id)
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

    private function formatCurrency(float $value): string
    {
        return 'PHP '.number_format($value, 2);
    }

    private function formatGrade(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
