<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BillingSchedule;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $totalCharges = round((float) LedgerEntry::query()->sum('debit'), 2);
        $totalPayments = round((float) LedgerEntry::query()->sum('credit'), 2);
        $outstandingBalance = round(max($totalCharges - $totalPayments, 0), 2);
        $collectionEfficiencyPercent = $totalCharges > 0
            ? round(min(($totalPayments / $totalCharges) * 100, 100), 2)
            : 0.0;

        $today = now()->startOfDay();
        $nextMonthStart = now()->addMonthNoOverflow()->startOfMonth();
        $nextMonthEnd = $nextMonthStart->copy()->endOfMonth();

        $cashInDrawerToday = round((float) Transaction::query()
            ->where('payment_mode', 'cash')
            ->whereDate('created_at', $today->toDateString())
            ->sum('total_amount'), 2);

        $revenueForecastNextMonth = round((float) BillingSchedule::query()
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

        return Inertia::render('finance/dashboard', [
            'metrics' => [
                'collection_efficiency_percent' => $collectionEfficiencyPercent,
                'total_charges' => $totalCharges,
                'total_payments' => $totalPayments,
                'outstanding_balance' => $outstandingBalance,
                'cash_in_drawer_today' => $cashInDrawerToday,
                'revenue_forecast_next_month' => $revenueForecastNextMonth,
                'forecast_month_label' => $nextMonthStart->format('F Y'),
            ],
        ]);
    }
}
