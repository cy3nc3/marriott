<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->latest('start_date')->first();

        $ledgerScope = LedgerEntry::query()
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            });

        $billingScope = BillingSchedule::query()
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            });

        $totalCharges = round((float) (clone $ledgerScope)->sum('debit'), 2);
        $totalPayments = round((float) (clone $ledgerScope)->sum('credit'), 2);
        $outstandingBalance = round(max($totalCharges - $totalPayments, 0), 2);

        $collectionEfficiencyPercent = $totalCharges > 0
            ? round(min(($totalPayments / $totalCharges) * 100, 100), 2)
            : 0.0;

        $today = now()->toDateString();
        $rollingWindowStart = now()->subDays(29)->toDateString();
        $nextMonthStart = now()->addMonthNoOverflow()->startOfMonth();
        $nextMonthEnd = $nextMonthStart->copy()->endOfMonth();

        $transactionsScope = Transaction::query();

        $todayCollection = round((float) (clone $transactionsScope)
            ->whereDate('created_at', $today)
            ->sum('total_amount'), 2);

        $revenueForecastNextMonth = round((float) (clone $billingScope)
            ->whereBetween('due_date', [
                $nextMonthStart->toDateString(),
                $nextMonthEnd->toDateString(),
            ])
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
                    'label' => strtoupper((string) $row->payment_mode),
                    'value' => (int) $row->total,
                ];
            })
            ->values()
            ->all();

        $alerts = [];

        if ($collectionEfficiencyPercent < 55) {
            $alerts[] = [
                'id' => 'collection-efficiency',
                'title' => 'Collection efficiency is critical',
                'message' => "Current collection efficiency is {$collectionEfficiencyPercent}%.",
                'severity' => 'critical',
            ];
        } elseif ($collectionEfficiencyPercent < 75) {
            $alerts[] = [
                'id' => 'collection-efficiency',
                'title' => 'Collection efficiency needs follow-up',
                'message' => "Current collection efficiency is {$collectionEfficiencyPercent}%.",
                'severity' => 'warning',
            ];
        }

        if ($overdueConcentration >= 60) {
            $alerts[] = [
                'id' => 'overdue-concentration',
                'title' => 'Overdue concentration is high',
                'message' => "{$overdueConcentration}% of receivables are already overdue.",
                'severity' => 'critical',
            ];
        } elseif ($overdueConcentration >= 35) {
            $alerts[] = [
                'id' => 'overdue-concentration',
                'title' => 'Overdue concentration requires attention',
                'message' => "{$overdueConcentration}% of receivables are already overdue.",
                'severity' => 'warning',
            ];
        }

        if ($todayCollection <= 0) {
            $alerts[] = [
                'id' => 'today-collection',
                'title' => 'No collections posted today',
                'message' => 'No payment transactions have been posted in the current day.',
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'finance-stable',
                'title' => 'Finance metrics are within expected range',
                'message' => 'Collections, overdue concentration, and receivables are currently stable.',
                'severity' => 'info',
            ];
        }

        return Inertia::render('finance/dashboard', [
            'kpis' => [
                [
                    'id' => 'collection-efficiency',
                    'label' => 'Collection Efficiency',
                    'value' => number_format($collectionEfficiencyPercent, 2).'%',
                    'meta' => $this->formatCurrency($totalPayments).' collected',
                ],
                [
                    'id' => 'outstanding-receivables',
                    'label' => 'Outstanding Receivables',
                    'value' => $this->formatCurrency($outstandingBalance),
                    'meta' => $this->formatCurrency($totalCharges).' total charges',
                ],
                [
                    'id' => 'overdue-concentration',
                    'label' => 'Overdue Concentration',
                    'value' => number_format($overdueConcentration, 2).'%',
                    'meta' => $this->formatCurrency($overdueOutstanding).' overdue amount',
                ],
                [
                    'id' => 'next-month-forecast',
                    'label' => 'Next-Month Due Forecast',
                    'value' => $this->formatCurrency($revenueForecastNextMonth),
                    'meta' => $nextMonthStart->format('F Y'),
                ],
            ],
            'alerts' => array_values($alerts),
            'trends' => [
                [
                    'id' => 'daily-collection',
                    'label' => 'Daily Collection Trend (Last 7 Days)',
                    'summary' => 'Amount collected per day',
                    'points' => $dailyCollectionTrend,
                ],
                [
                    'id' => 'payment-mode-mix',
                    'label' => 'Payment-Mode Mix (Last 30 Days)',
                    'summary' => 'Distribution of transaction count by payment mode',
                    'points' => $paymentModeMix,
                ],
            ],
            'action_links' => [
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
                    'id' => 'open-daily-reports',
                    'label' => 'Open Daily Reports',
                    'href' => route('finance.daily_reports'),
                ],
            ],
        ]);
    }

    private function formatCurrency(float $amount): string
    {
        return 'PHP '.number_format($amount, 2);
    }
}
