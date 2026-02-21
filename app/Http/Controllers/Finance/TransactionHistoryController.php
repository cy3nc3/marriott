<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexTransactionHistoryRequest;
use App\Models\Transaction;
use Inertia\Inertia;
use Inertia\Response;

class TransactionHistoryController extends Controller
{
    public function index(IndexTransactionHistoryRequest $request): Response
    {
        $validated = $request->validated();

        $search = trim((string) ($validated['search'] ?? ''));
        $paymentMode = $validated['payment_mode'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $transactions = Transaction::query()
            ->with([
                'student:id,first_name,last_name,lrn',
                'cashier:id,first_name,last_name,name',
                'items:id,transaction_id,description',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('or_number', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery
                                ->where('lrn', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
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
            ->get()
            ->map(function (Transaction $transaction) {
                $studentName = trim("{$transaction->student?->first_name} {$transaction->student?->last_name}");
                $cashierName = trim("{$transaction->cashier?->first_name} {$transaction->cashier?->last_name}");

                return [
                    'id' => $transaction->id,
                    'or_number' => $transaction->or_number,
                    'student_name' => $studentName !== '' ? $studentName : '-',
                    'student_lrn' => $transaction->student?->lrn,
                    'entry_label' => $this->resolveEntryLabel($transaction),
                    'payment_mode' => $transaction->payment_mode,
                    'payment_mode_label' => $this->formatPaymentMode($transaction->payment_mode),
                    'status' => 'posted',
                    'status_label' => 'Posted',
                    'cashier_name' => $cashierName !== '' ? $cashierName : ($transaction->cashier?->name ?? '-'),
                    'amount' => (float) $transaction->total_amount,
                    'posted_at' => $transaction->created_at?->toIso8601String(),
                ];
            })
            ->values();

        $postedAmount = round((float) $transactions->sum('amount'), 2);

        return Inertia::render('finance/transaction-history/index', [
            'transactions' => $transactions,
            'summary' => [
                'count' => $transactions->count(),
                'posted_amount' => $postedAmount,
                'voided_amount' => 0.0,
                'net_amount' => $postedAmount,
            ],
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'payment_mode' => $paymentMode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
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

    private function resolveEntryLabel(Transaction $transaction): string
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
}
