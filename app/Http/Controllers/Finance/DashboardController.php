<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Services\DashboardCacheService;
use App\Services\DashboardDecisionService;
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
        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->latest('start_date')->first();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $cachedSummary = DashboardCacheService::remember(
            'finance:dashboard:year-'.($activeYear?->id ?? 'none').':'.$today,
            function () use ($activeYear, $today, $monthStart, $monthEnd): array {
                $ledgerScope = LedgerEntry::query()
                    ->when($activeYear, function ($query) use ($activeYear) {
                        $query->where('ledger_entries.academic_year_id', $activeYear->id);
                    });

                $billingScope = BillingSchedule::query()
                    ->when($activeYear, function ($query) use ($activeYear) {
                        $query->where('billing_schedules.academic_year_id', $activeYear->id);
                    });

                $transactionsScope = Transaction::query();
                $rollingWindowStart = now()->subDays(29)->toDateString();

                $totalCharges = round((float) (clone $ledgerScope)->sum('debit'), 2);
                $totalPayments = round((float) (clone $ledgerScope)->sum('credit'), 2);
                $outstandingBalance = round(max($totalCharges - $totalPayments, 0), 2);

                $collectionEfficiencyPercent = $totalCharges > 0
                    ? round(min(($totalPayments / $totalCharges) * 100, 100), 2)
                    : 0.0;

                $todayCollection = round((float) (clone $transactionsScope)
                    ->whereDate('created_at', $today)
                    ->sum('total_amount'), 2);

                $collectibleThisMonth = round((float) (clone $billingScope)
                    ->whereBetween('due_date', [$monthStart, $monthEnd])
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->get(['amount_due', 'amount_paid'])
                    ->sum(function (BillingSchedule $billingSchedule): float {
                        return max(
                            (float) $billingSchedule->amount_due - (float) $billingSchedule->amount_paid,
                            0
                        );
                    }), 2);

                $overdueOutstanding = round((float) (clone $billingScope)
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->get(['amount_due', 'amount_paid'])
                    ->sum(function (BillingSchedule $billingSchedule): float {
                        return max(
                            (float) $billingSchedule->amount_due - (float) $billingSchedule->amount_paid,
                            0
                        );
                    }), 2);

                $overdueConcentration = $outstandingBalance > 0
                    ? round(($overdueOutstanding / $outstandingBalance) * 100, 2)
                    : 0.0;

                $thisMonthCollection = round((float) (clone $transactionsScope)
                    ->whereBetween('created_at', [
                        now()->startOfMonth()->startOfDay()->toDateTimeString(),
                        now()->endOfMonth()->endOfDay()->toDateTimeString(),
                    ])
                    ->sum('total_amount'), 2);

                $dbDriver = DB::connection()->getDriverName();
                $monthlyCollectionQuery = (clone $transactionsScope)
                    ->whereDate('created_at', '>=', now()->subMonths(5)->startOfMonth()->toDateString());

                if ($dbDriver === 'sqlite') {
                    $monthlyCollectionQuery->selectRaw("strftime('%m %Y', created_at) as month_label, strftime('%Y-%m', created_at) as month_key, sum(total_amount) as total")
                        ->groupBy('month_label', 'month_key')
                        ->orderBy('month_key');
                } else {
                    $monthlyCollectionQuery->selectRaw("to_char(created_at, 'Mon YYYY') as month_label, to_char(created_at, 'YYYY-MM') as month_key, sum(total_amount) as total")
                        ->groupBy('month_label', 'month_key')
                        ->orderBy('month_key');
                }

                $monthlyCollectionTotals = $monthlyCollectionQuery
                    ->get()
                    ->map(fn ($row): array => [
                        'month' => $dbDriver === 'sqlite'
                            ? now()->createFromFormat('m Y', (string) $row->month_label)->format('M Y')
                            : (string) $row->month_label,
                        'collected' => round((float) $row->total, 2),
                    ])
                    ->values()
                    ->all();

                $dailyCollectionTotals = (clone $transactionsScope)
                    ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
                    ->selectRaw('DATE(created_at) as day, sum(total_amount) as total')
                    ->groupBy('day')
                    ->orderBy('day')
                    ->pluck('total', 'day');

                $dailyCollectionTrend = collect(range(6, 0, -1))
                    ->map(function (int $daysAgo) use ($dailyCollectionTotals): array {
                        $day = now()->subDays($daysAgo)->toDateString();

                        return [
                            'label' => now()->subDays($daysAgo)->format('M d'),
                            'value' => round((float) ($dailyCollectionTotals[$day] ?? 0), 2),
                        ];
                    })
                    ->values()
                    ->all();

                $paymentModeMix = (clone $transactionsScope)
                    ->whereDate('created_at', '>=', $rollingWindowStart)
                    ->selectRaw('payment_mode, count(*) as total')
                    ->groupBy('payment_mode')
                    ->orderBy('payment_mode')
                    ->get()
                    ->map(function ($row): array {
                        return [
                            'label' => ucwords(str_replace('_', ' ', (string) $row->payment_mode)),
                            'value' => (int) $row->total,
                        ];
                    })
                    ->values()
                    ->all();

                $overdueItems = (clone $billingScope)
                    ->whereDate('due_date', '<', $today)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->get(['due_date', 'amount_due', 'amount_paid', 'student_id', 'academic_year_id']);

                $agingBuckets = [
                    '1-7 Days' => 0.0,
                    '8-14 Days' => 0.0,
                    '15-30 Days' => 0.0,
                    '31+ Days' => 0.0,
                ];

                foreach ($overdueItems as $item) {
                    $daysOverdue = (int) now()->diffInDays($item->due_date, false) * -1;
                    $balance = max((float) $item->amount_due - (float) $item->amount_paid, 0);

                    if ($daysOverdue >= 31) {
                        $agingBuckets['31+ Days'] += $balance;
                    } elseif ($daysOverdue >= 15) {
                        $agingBuckets['15-30 Days'] += $balance;
                    } elseif ($daysOverdue >= 8) {
                        $agingBuckets['8-14 Days'] += $balance;
                    } else {
                        $agingBuckets['1-7 Days'] += $balance;
                    }
                }

                $overdueAgingRows = collect($agingBuckets)
                    ->map(fn ($total, $label) => [
                        'bucket' => $label,
                        'amount' => round($total, 2),
                    ])
                    ->values()
                    ->all();

                return [
                    'total_charges' => $totalCharges,
                    'total_payments' => $totalPayments,
                    'outstanding_balance' => $outstandingBalance,
                    'collection_efficiency_percent' => $collectionEfficiencyPercent,
                    'today_collection' => $todayCollection,
                    'collectible_this_month' => $collectibleThisMonth,
                    'this_month_collection' => $thisMonthCollection,
                    'overdue_outstanding' => $overdueOutstanding,
                    'overdue_concentration' => $overdueConcentration,
                    'daily_collection_trend' => $dailyCollectionTrend,
                    'payment_mode_mix' => $paymentModeMix,
                    'monthly_collection_totals' => $monthlyCollectionTotals,
                    'overdue_aging_rows' => $overdueAgingRows,
                ];
            }
        );

        $totalCharges = (float) $cachedSummary['total_charges'];
        $totalPayments = (float) $cachedSummary['total_payments'];
        $outstandingBalance = (float) $cachedSummary['outstanding_balance'];
        $collectionEfficiencyPercent = (float) $cachedSummary['collection_efficiency_percent'];
        $todayCollection = (float) $cachedSummary['today_collection'];
        $collectibleThisMonth = (float) $cachedSummary['collectible_this_month'];
        $thisMonthCollection = (float) $cachedSummary['this_month_collection'];
        $overdueOutstanding = (float) $cachedSummary['overdue_outstanding'];
        $overdueConcentration = (float) $cachedSummary['overdue_concentration'];
        $dailyCollectionTrend = $cachedSummary['daily_collection_trend'];
        $paymentModeMix = $cachedSummary['payment_mode_mix'];
        $monthlyCollectionTotals = $cachedSummary['monthly_collection_totals'];

        $monthlyCollectionEfficiency = $collectibleThisMonth > 0
            ? round(min(($thisMonthCollection / $collectibleThisMonth) * 100, 100), 2)
            : 0.0;

        $cashierQueueItems = Enrollment::query()
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->where('status', 'for_cashier_payment')
            ->get(['id', 'created_at']);
        $cashierQueueCount = $cashierQueueItems->count();
        $cashierQueueAverageAgeHours = $cashierQueueCount > 0
            ? round($cashierQueueItems->avg(fn (Enrollment $enrollment): float => (float) ($enrollment->created_at?->diffInHours(now()) ?? 0)), 1)
            : 0.0;

        $alerts = $this->dashboardDecisionService->financeAlerts(
            $monthlyCollectionEfficiency,
            $overdueConcentration,
            $todayCollection,
            $cashierQueueCount,
        );

        $decisionCards = [];

        $overdueScheduleQueue = BillingSchedule::query()
            ->with('student:id,first_name,last_name,lrn')
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get(['id', 'student_id', 'description', 'due_date', 'amount_due', 'amount_paid'])
            ->map(function (BillingSchedule $schedule): array {
                $balance = max((float) $schedule->amount_due - (float) $schedule->amount_paid, 0);
                $daysOverdue = max((int) now()->diffInDays($schedule->due_date, false) * -1, 0);
                $priorityScore = $this->dashboardDecisionService->financeOverduePriorityScore($balance, $daysOverdue);
                $studentName = trim((string) ($schedule->student?->first_name ?? '').' '.(string) ($schedule->student?->last_name ?? ''));

                return [
                    'id' => 'billing-'.$schedule->id,
                    'title' => "Collect overdue: {$studentName}",
                    'impact' => $this->formatCurrency($balance).' recoverable',
                    'urgency' => $this->dashboardDecisionService->financeOverdueUrgency($daysOverdue),
                    'priority_score' => $priorityScore,
                    'reason' => "{$daysOverdue} day(s) overdue on ".($schedule->description ?: 'billing schedule').' item.',
                    'href' => route('finance.student_ledgers'),
                ];
            })
            ->sortByDesc('priority_score')
            ->take(10)
            ->values()
            ->all();

        $criticalQueueCount = collect($overdueScheduleQueue)->where('urgency', 'Critical')->count();
        $highQueueCount = collect($overdueScheduleQueue)->where('urgency', 'High')->count();
        $mediumQueueCount = collect($overdueScheduleQueue)->where('urgency', 'Medium')->count();
        $interventionQueueCount = $criticalQueueCount + $highQueueCount + $mediumQueueCount;

        $monthlyStabilityRows = collect(range(5, 0))
            ->map(function (int $monthsAgo) use ($activeYear): array {
                $monthStart = now()->subMonths($monthsAgo)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();
                $monthDue = (float) BillingSchedule::query()
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->sum('amount_due');
                $monthPaid = (float) BillingSchedule::query()
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->sum('amount_paid');
                $efficiency = $monthDue > 0 ? round(min(($monthPaid / $monthDue) * 100, 100), 2) : 0.0;

                return [
                    'month' => $monthStart->format('M Y'),
                    'efficiency' => $efficiency,
                ];
            })
            ->values();
        $efficiencyValues = $monthlyStabilityRows->pluck('efficiency')->map(fn ($value): float => (float) $value)->all();
        $efficiencyMean = count($efficiencyValues) > 0 ? array_sum($efficiencyValues) / count($efficiencyValues) : 0.0;
        $efficiencyVariance = count($efficiencyValues) > 0
            ? array_sum(array_map(fn (float $value): float => ($value - $efficiencyMean) ** 2, $efficiencyValues)) / count($efficiencyValues)
            : 0.0;
        $efficiencyStdDev = sqrt($efficiencyVariance);
        $revenueStabilityScore = min(100, max(0, (int) round(100 - ($efficiencyStdDev * 1.8))));

        $receivableRiskCompositionRows = [
            ['bucket' => 'Overdue Balance', 'amount' => $overdueOutstanding],
            ['bucket' => 'Non-Overdue Outstanding', 'amount' => max($outstandingBalance - $overdueOutstanding, 0)],
        ];

        $kpis = [
            [
                'id' => 'collection-efficiency',
                'label' => 'Collected This Month',
                'value' => $this->formatCurrency($thisMonthCollection),
                'meta' => $this->formatCurrency($thisMonthCollection).' collected of '.$this->formatCurrency($collectibleThisMonth),
            ],
            [
                'id' => 'overdue-recovery-target',
                'label' => 'Payments Needing Follow-up',
                'value' => $this->formatCurrency($overdueOutstanding),
                'meta' => "{$interventionQueueCount} account(s) with overdue dues",
            ],
            [
                'id' => 'finance-cashier-queue',
                'label' => 'Cashier Queue',
                'value' => $cashierQueueCount,
                'meta' => $cashierQueueAverageAgeHours > 0
                    ? "Average wait: {$cashierQueueAverageAgeHours}h"
                    : 'No enrollment payments waiting',
            ],
            [
                'id' => 'finance-revenue-stability',
                'label' => 'Revenue Stability Score',
                'value' => $revenueStabilityScore.'/100',
                'meta' => 'Based on 6-month collection-efficiency volatility',
            ],
        ];

        $kpis = $this->dashboardDecisionService->prioritizeFinanceKpis(
            $kpis,
            $overdueConcentration,
            $criticalQueueCount,
            $monthlyCollectionEfficiency,
            $cashierQueueCount,
        );

        $actionLinks = [
            [
                'id' => 'open-cashier-panel',
                'label' => 'Open Cashier Panel',
                'href' => route('finance.cashier_panel'),
            ],
            [
                'id' => 'open-student-ledgers',
                'label' => 'Open Student Ledgers',
                'href' => route('finance.student_ledgers'),
            ],
            [
                'id' => 'review-overdue-accounts',
                'label' => 'Review Overdue Accounts',
                'href' => route('finance.student_ledgers', ['overdue_only' => 1]),
            ],
            [
                'id' => 'open-due-reminder-scheduling',
                'label' => 'Open Reminder Scheduling',
                'href' => route('finance.due_reminder_settings'),
            ],
            [
                'id' => 'open-daily-reports',
                'label' => 'Open Daily Reports',
                'href' => route('finance.daily_reports'),
            ],
        ];

        $actionLinks = $this->dashboardDecisionService->prioritizeFinanceActionLinks(
            $actionLinks,
            $overdueConcentration,
            $criticalQueueCount,
            $monthlyCollectionEfficiency,
            $cashierQueueCount,
        );

        $trends = $this->dashboardDecisionService->financeTrends(
            $cachedSummary['overdue_aging_rows'],
            $monthlyCollectionTotals,
            $receivableRiskCompositionRows,
            $monthlyStabilityRows->all(),
        );

        return Inertia::render('finance/dashboard', [
            'kpis' => $kpis,
            'alerts' => array_values($alerts),
            'trends' => $trends,
            'action_links' => $actionLinks,
            'decision_cards' => $decisionCards,
            'action_queue' => [],
        ]);
    }

    private function formatCurrency(float $amount): string
    {
        return 'PHP '.number_format($amount, 2);
    }
}
