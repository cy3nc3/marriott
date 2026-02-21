<?php

namespace App\Http\Controllers\Finance;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexDailyReportsRequest;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DailyReportsController extends Controller
{
    public function index(IndexDailyReportsRequest $request): Response
    {
        $validated = $request->validated();

        $cashierId = isset($validated['cashier_id'])
            ? (int) $validated['cashier_id']
            : null;
        $paymentMode = $validated['payment_mode'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $transactions = Transaction::query()
            ->with([
                'student:id,first_name,last_name,lrn',
                'cashier:id,first_name,last_name,name',
                'items:id,transaction_id,fee_id,inventory_item_id,description,amount',
                'items.fee:id,type',
            ])
            ->when($cashierId, function ($query, $cashierId) {
                $query->where('cashier_id', $cashierId);
            })
            ->when($paymentMode, function ($query, $paymentMode) {
                $query->where('payment_mode', $paymentMode);
            })
            ->when($dateFrom, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest('created_at')
            ->latest('id')
            ->get();

        $cashiers = User::query()
            ->where('role', UserRole::FINANCE->value)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('name')
            ->get(['id', 'first_name', 'last_name', 'name'])
            ->map(function (User $cashier) {
                $cashierName = trim("{$cashier->first_name} {$cashier->last_name}");

                return [
                    'id' => $cashier->id,
                    'name' => $cashierName !== '' ? $cashierName : ($cashier->name ?? 'Cashier'),
                ];
            })
            ->values();

        $breakdownRows = $this->buildBreakdownRows($transactions);

        $grossCollection = round((float) $transactions->sum('total_amount'), 2);
        $cashOnHand = round((float) $transactions
            ->where('payment_mode', 'cash')
            ->sum('total_amount'), 2);
        $digitalCollection = round($grossCollection - $cashOnHand, 2);

        $transactionRows = $transactions
            ->map(function (Transaction $transaction) {
                $studentName = trim("{$transaction->student?->first_name} {$transaction->student?->last_name}");
                $cashierName = trim("{$transaction->cashier?->first_name} {$transaction->cashier?->last_name}");

                return [
                    'id' => $transaction->id,
                    'or_number' => $transaction->or_number,
                    'student_name' => $studentName !== '' ? $studentName : '-',
                    'payment_type' => $this->resolvePaymentType($transaction),
                    'payment_mode' => $transaction->payment_mode,
                    'payment_mode_label' => $this->formatPaymentMode($transaction->payment_mode),
                    'amount' => (float) $transaction->total_amount,
                    'cashier_name' => $cashierName !== '' ? $cashierName : ($transaction->cashier?->name ?? '-'),
                    'posted_at' => $transaction->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return Inertia::render('finance/daily-reports/index', [
            'cashiers' => $cashiers,
            'breakdown_rows' => $breakdownRows,
            'transaction_rows' => $transactionRows,
            'summary' => [
                'transaction_count' => $transactions->count(),
                'gross_collection' => $grossCollection,
                'cash_on_hand' => $cashOnHand,
                'digital_collection' => $digitalCollection,
                'void_adjustments' => 0.0,
            ],
            'filters' => [
                'cashier_id' => $cashierId,
                'payment_mode' => $paymentMode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    private function buildBreakdownRows(Collection $transactions): Collection
    {
        $categoryAggregates = [
            'tuition_fees' => [
                'category' => 'Tuition Fees',
                'transaction_ids' => [],
                'total_amount' => 0.0,
            ],
            'enrollment_downpayment' => [
                'category' => 'Enrollment Downpayment',
                'transaction_ids' => [],
                'total_amount' => 0.0,
            ],
            'products' => [
                'category' => 'Products (Uniform/Books)',
                'transaction_ids' => [],
                'total_amount' => 0.0,
            ],
            'other' => [
                'category' => 'Other Collections',
                'transaction_ids' => [],
                'total_amount' => 0.0,
            ],
        ];

        foreach ($transactions as $transaction) {
            foreach ($transaction->items as $item) {
                $categoryKey = $this->resolveBreakdownCategory($item);

                $categoryAggregates[$categoryKey]['transaction_ids'][$transaction->id] = true;
                $categoryAggregates[$categoryKey]['total_amount'] += (float) $item->amount;
            }
        }

        return collect($categoryAggregates)
            ->map(function (array $category) {
                return [
                    'category' => $category['category'],
                    'transaction_count' => count($category['transaction_ids']),
                    'total_amount' => round((float) $category['total_amount'], 2),
                ];
            })
            ->values();
    }

    private function resolveBreakdownCategory(TransactionItem $transactionItem): string
    {
        $description = strtolower($transactionItem->description);

        if (str_contains($description, 'downpayment')) {
            return 'enrollment_downpayment';
        }

        if ($transactionItem->inventory_item_id) {
            return 'products';
        }

        if (
            str_contains($description, 'uniform') ||
            str_contains($description, 'book') ||
            str_contains($description, 'module')
        ) {
            return 'products';
        }

        if ($transactionItem->fee?->type === 'tuition') {
            return 'tuition_fees';
        }

        return 'other';
    }

    private function resolvePaymentType(Transaction $transaction): string
    {
        $descriptions = $transaction->items
            ->pluck('description')
            ->filter()
            ->values();

        if ($descriptions->isEmpty()) {
            return 'Payment';
        }

        if ($descriptions->count() === 1) {
            return (string) $descriptions->first();
        }

        $remaining = $descriptions->count() - 1;

        return "{$descriptions->first()} + {$remaining} more";
    }

    private function formatPaymentMode(string $paymentMode): string
    {
        return match ($paymentMode) {
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'bank_transfer' => 'Bank Transfer',
            default => ucwords(str_replace('_', ' ', $paymentMode)),
        };
    }
}
