<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexTransactionHistoryRequest;
use App\Http\Requests\Finance\RefundTransactionRequest;
use App\Http\Requests\Finance\ReissueTransactionRequest;
use App\Http\Requests\Finance\VoidTransactionRequest;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\RemedialCase;
use App\Models\Transaction;
use App\Models\TransactionDueAllocation;
use App\Models\User;
use App\Services\Auth\EnrollmentAccountClaimService;
use App\Services\DashboardCacheService;
use App\Services\Finance\TransactionHistoryWorkbookExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionHistoryController extends Controller
{
    public function __construct(
        private EnrollmentAccountClaimService $enrollmentAccountClaimService,
    ) {}

    public function index(IndexTransactionHistoryRequest $request): Response
    {
        $validated = $request->validated();

        $schoolYearOptions = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'status', 'start_date'])
            ->map(function (AcademicYear $academicYear) {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();

        $selectedAcademicYearId = (int) ($validated['academic_year_id'] ?? 0);
        if (
            $selectedAcademicYearId <= 0
            || ! $schoolYearOptions->pluck('id')->contains($selectedAcademicYearId)
        ) {
            $selectedAcademicYearId = (int) ($schoolYearOptions->firstWhere('status', 'ongoing')['id']
                ?? ($schoolYearOptions->first()['id'] ?? 0));
        }

        $selectedAcademicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;

        $search = trim((string) ($validated['search'] ?? ''));
        $paymentMode = $validated['payment_mode'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $transactionQuery = $this->buildTransactionHistoryQuery(
            $selectedAcademicYear,
            $search,
            $paymentMode,
            $dateFrom,
            $dateTo,
        );

        $correctedStatuses = ['voided', 'refunded', 'reissued'];
        $summaryQuery = clone $transactionQuery;
        $grossAmount = round((float) (clone $summaryQuery)->sum('total_amount'), 2);
        $voidedAmount = round((float) (clone $summaryQuery)->where('status', 'voided')->sum('total_amount'), 2);
        $correctedAmount = round((float) (clone $summaryQuery)
            ->whereIn('status', $correctedStatuses)
            ->sum('total_amount'), 2);
        $netAmount = round($grossAmount - $correctedAmount, 2);
        $totalCount = (int) (clone $transactionQuery)->count();

        $transactions = $transactionQuery
            ->paginate(15)
            ->withQueryString()
            ->through(function (Transaction $transaction) {
                $studentName = trim("{$transaction->student?->first_name} {$transaction->student?->last_name}");
                $cashierName = trim("{$transaction->cashier?->first_name} {$transaction->cashier?->last_name}");
                $status = $transaction->status ?: 'posted';
                $correctionReason = null;
                $correctedByName = null;

                if ($status === 'voided') {
                    $correctionReason = $transaction->void_reason;
                    $correctedByName = $this->resolveUserDisplayName($transaction->voidedBy);
                } elseif ($status === 'refunded') {
                    $correctionReason = $transaction->refund_reason;
                    $correctedByName = $this->resolveUserDisplayName($transaction->refundedBy);
                } elseif ($status === 'reissued') {
                    $correctionReason = $transaction->reissue_reason;
                    $correctedByName = $this->resolveUserDisplayName($transaction->reissuedBy);
                }

                return [
                    'id' => $transaction->id,
                    'or_number' => $transaction->or_number,
                    'student_name' => $studentName !== '' ? $studentName : '-',
                    'student_lrn' => $transaction->student?->lrn,
                    'entry_label' => $this->resolveEntryLabel($transaction),
                    'payment_mode' => $transaction->payment_mode,
                    'payment_mode_label' => $this->formatPaymentMode($transaction->payment_mode),
                    'status' => $status,
                    'status_label' => $this->formatStatusLabel($status),
                    'cashier_name' => $cashierName !== '' ? $cashierName : ($transaction->cashier?->name ?? '-'),
                    'amount' => (float) $transaction->total_amount,
                    'transaction_items' => $transaction->items
                        ->sortBy('id')
                        ->values()
                        ->map(function ($item): array {
                            return [
                                'description' => (string) $item->description,
                                'amount' => (float) $item->amount,
                                'fee_id' => $item->fee_id ? (int) $item->fee_id : null,
                                'inventory_item_id' => $item->inventory_item_id ? (int) $item->inventory_item_id : null,
                            ];
                        })
                        ->all(),
                    'posted_at' => $transaction->created_at?->toIso8601String(),
                    'voided_at' => $transaction->voided_at?->toIso8601String(),
                    'void_reason' => $transaction->void_reason,
                    'refunded_at' => $transaction->refunded_at?->toIso8601String(),
                    'refund_reason' => $transaction->refund_reason,
                    'reissued_at' => $transaction->reissued_at?->toIso8601String(),
                    'reissue_reason' => $transaction->reissue_reason,
                    'reissued_transaction_or_number' => $transaction->reissuedTransaction?->or_number,
                    'correction_reason' => $correctionReason,
                    'corrected_by_name' => $correctedByName,
                    'can_void' => $status === 'posted',
                    'can_refund' => $status === 'posted',
                    'can_reissue' => $status === 'posted',
                ];
            });

        return Inertia::render('finance/transaction-history/index', [
            'transactions' => $transactions,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedAcademicYear?->id,
            'summary' => [
                'count' => $totalCount,
                'posted_amount' => $grossAmount,
                'voided_amount' => $voidedAmount,
                'corrected_amount' => $correctedAmount,
                'net_amount' => $netAmount,
            ],
            'filters' => [
                'academic_year_id' => $selectedAcademicYear?->id,
                'search' => $search !== '' ? $search : null,
                'payment_mode' => $paymentMode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function export(
        IndexTransactionHistoryRequest $request,
        TransactionHistoryWorkbookExporter $exporter,
    ): BinaryFileResponse {
        $validated = $request->validated();

        $selectedAcademicYearId = isset($validated['academic_year_id'])
            ? (int) $validated['academic_year_id']
            : null;

        $selectedAcademicYear = $selectedAcademicYearId
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;

        $search = trim((string) ($validated['search'] ?? ''));
        $paymentMode = $validated['payment_mode'] ?? null;
        $exportRange = (string) ($validated['export_range'] ?? 'this_month');
        $requestedDateFrom = $validated['date_from'] ?? null;
        $requestedDateTo = $validated['date_to'] ?? null;

        if ($requestedDateFrom || $requestedDateTo) {
            $dateFrom = $requestedDateFrom ?? $requestedDateTo;
            $dateTo = $requestedDateTo ?? $requestedDateFrom;
        } else {
            [$dateFrom, $dateTo] = $this->resolveExportRangeDates(
                $this->buildTransactionHistoryQuery($selectedAcademicYear, $search, $paymentMode, null, null),
                $exportRange
            );
        }

        $transactionQuery = $this->buildTransactionHistoryQuery(
            $selectedAcademicYear,
            $search,
            $paymentMode,
            $dateFrom,
            $dateTo,
        );

        $correctedStatuses = ['voided', 'refunded', 'reissued'];
        $summaryQuery = clone $transactionQuery;
        $postedAmount = round((float) (clone $summaryQuery)->sum('total_amount'), 2);
        $correctedAmount = round((float) (clone $summaryQuery)
            ->whereIn('status', $correctedStatuses)
            ->sum('total_amount'), 2);
        $totalCount = (int) (clone $summaryQuery)->count();
        $netAmount = round($postedAmount - $correctedAmount, 2);

        $transactions = (clone $transactionQuery)->get();
        $segments = $this->buildMonthlySegments($dateFrom, $dateTo);
        $monthlyOverviewRows = [];
        $monthlyDetails = [];

        foreach ($segments as $segment) {
            $segmentRows = $transactions
                ->filter(function (Transaction $transaction) use ($segment): bool {
                    $createdAt = $transaction->created_at?->toDateString();

                    return $createdAt !== null
                        && $createdAt >= $segment['start']
                        && $createdAt <= $segment['end'];
                })
                ->values();

            $segmentPosted = round((float) $segmentRows->sum('total_amount'), 2);
            $segmentCorrected = round((float) $segmentRows
                ->whereIn('status', $correctedStatuses)
                ->sum('total_amount'), 2);

            $monthlyOverviewRows[] = [
                'label' => $segment['label'],
                'count' => (int) $segmentRows->count(),
                'posted_amount' => $segmentPosted,
                'corrected_amount' => $segmentCorrected,
                'net_amount' => round($segmentPosted - $segmentCorrected, 2),
            ];

            $monthlyDetails[$segment['label']] = $segmentRows
                ->map(function (Transaction $transaction): array {
                    $status = $transaction->status ?: 'posted';
                    $studentName = trim("{$transaction->student?->first_name} {$transaction->student?->last_name}");
                    $cashierName = trim("{$transaction->cashier?->first_name} {$transaction->cashier?->last_name}");
                    $correctionReason = null;
                    $correctedByName = null;

                    if ($status === 'voided') {
                        $correctionReason = $transaction->void_reason;
                        $correctedByName = $this->resolveUserDisplayName($transaction->voidedBy);
                    } elseif ($status === 'refunded') {
                        $correctionReason = $transaction->refund_reason;
                        $correctedByName = $this->resolveUserDisplayName($transaction->refundedBy);
                    } elseif ($status === 'reissued') {
                        $correctionReason = $transaction->reissue_reason;
                        $correctedByName = $this->resolveUserDisplayName($transaction->reissuedBy);
                    }

                    return [
                        'or_number' => (string) $transaction->or_number,
                        'student_name' => $studentName !== '' ? $studentName : '-',
                        'payment_mode_label' => $this->formatPaymentMode((string) $transaction->payment_mode),
                        'status_label' => $this->formatStatusLabel((string) $status),
                        'posted_at' => $transaction->created_at?->format('Y-m-d h:i A') ?? '-',
                        'cashier_name' => $cashierName !== '' ? $cashierName : ($transaction->cashier?->name ?? '-'),
                        'amount' => (float) $transaction->total_amount,
                        'correction_reason' => $correctionReason ? (string) $correctionReason : '',
                        'corrected_by_name' => $correctedByName ? (string) $correctedByName : '',
                    ];
                })
                ->all();
        }

        $outputPath = storage_path('app/temp/'.uniqid('transaction-history-', true).'.xlsx');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $exporter->export(
            $outputPath,
            [
                'generated_at' => now()->format('F j, Y h:i A'),
                'range_preset' => $this->resolveRangePresetLabel($exportRange),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'school_year' => $selectedAcademicYear?->name ?? 'All School Years',
                'payment_mode' => $paymentMode ? $this->formatPaymentMode((string) $paymentMode) : 'All Modes',
                'search' => $search !== '' ? $search : 'All',
            ],
            [
                'count' => $totalCount,
                'posted_amount' => $postedAmount,
                'corrected_amount' => $correctedAmount,
                'net_amount' => $netAmount,
            ],
            $monthlyOverviewRows,
            $monthlyDetails,
        );

        return response()
            ->download($outputPath, 'transaction-history-'.now()->format('Ymd-His').'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function void(VoidTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        if (($transaction->status ?? 'posted') !== 'posted') {
            return back()->with('error', 'Only posted transactions can be voided.');
        }

        DB::transaction(function () use ($request, $transaction): void {
            $lockedTransaction = Transaction::query()
                ->with('items')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($lockedTransaction->status ?? 'posted') !== 'posted') {
                return;
            }

            $academicYearId = $this->resolveAcademicYearIdForTransaction($lockedTransaction);

            $this->rollbackPaymentImpact($lockedTransaction, $academicYearId);
            $this->rollbackRemedialPaymentImpact($lockedTransaction, $academicYearId);

            if ($academicYearId) {
                $this->appendReverseLedgerEntry(
                    $lockedTransaction,
                    $academicYearId,
                    "Transaction Void ({$lockedTransaction->or_number})"
                );
            }

            $lockedTransaction->update([
                'status' => 'voided',
                'void_reason' => $request->validated('reason'),
                'voided_at' => now(),
                'voided_by' => auth()->id(),
            ]);

            if ($academicYearId) {
                $this->syncEnrollmentStatusAfterCorrection(
                    (int) $lockedTransaction->student_id,
                    $academicYearId
                );
            }
        });

        DashboardCacheService::bust();

        return back()->with('success', 'Transaction voided successfully.');
    }

    public function refund(RefundTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        if (($transaction->status ?? 'posted') !== 'posted') {
            return back()->with('error', 'Only posted transactions can be refunded.');
        }

        DB::transaction(function () use ($request, $transaction): void {
            $lockedTransaction = Transaction::query()
                ->with('items')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($lockedTransaction->status ?? 'posted') !== 'posted') {
                return;
            }

            $academicYearId = $this->resolveAcademicYearIdForTransaction($lockedTransaction);

            $this->rollbackPaymentImpact($lockedTransaction, $academicYearId);
            $this->rollbackRemedialPaymentImpact($lockedTransaction, $academicYearId);

            if ($academicYearId) {
                $this->appendReverseLedgerEntry(
                    $lockedTransaction,
                    $academicYearId,
                    "Transaction Refund ({$lockedTransaction->or_number})"
                );
            }

            $lockedTransaction->update([
                'status' => 'refunded',
                'refund_reason' => $request->validated('reason'),
                'refunded_at' => now(),
                'refunded_by' => auth()->id(),
            ]);

            if ($academicYearId) {
                $this->syncEnrollmentStatusAfterCorrection(
                    (int) $lockedTransaction->student_id,
                    $academicYearId
                );
            }
        });

        DashboardCacheService::bust();

        return back()->with('success', 'Transaction refunded successfully.');
    }

    public function reissue(ReissueTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        if (($transaction->status ?? 'posted') !== 'posted') {
            return back()->with('error', 'Only posted transactions can be reissued.');
        }

        DB::transaction(function () use ($request, $transaction): void {
            $lockedTransaction = Transaction::query()
                ->with('items')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($lockedTransaction->status ?? 'posted') !== 'posted') {
                return;
            }

            $academicYearId = $this->resolveAcademicYearIdForTransaction($lockedTransaction);

            $this->rollbackPaymentImpact($lockedTransaction, $academicYearId);
            $this->rollbackRemedialPaymentImpact($lockedTransaction, $academicYearId);

            if ($academicYearId) {
                $this->appendReverseLedgerEntry(
                    $lockedTransaction,
                    $academicYearId,
                    "Transaction Reissue Reversal ({$lockedTransaction->or_number})"
                );
            }

            $replacementTransaction = Transaction::query()->create([
                'or_number' => $request->validated('or_number'),
                'student_id' => $lockedTransaction->student_id,
                'cashier_id' => auth()->id(),
                'total_amount' => $lockedTransaction->total_amount,
                'payment_mode' => $request->validated('payment_mode'),
                'reference_no' => $request->validated('reference_no'),
                'remarks' => $request->validated('remarks'),
                'status' => 'posted',
            ]);

            $replacementTransaction->items()->createMany(
                $lockedTransaction->items
                    ->map(function ($item): array {
                        return [
                            'fee_id' => $item->fee_id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'description' => $item->description,
                            'amount' => $item->amount,
                        ];
                    })
                    ->all()
            );

            $replacementTransaction->load('items');

            if ($academicYearId) {
                $this->applyPaymentAcrossDues(
                    $replacementTransaction,
                    $this->resolveAssessmentFeeAmount($replacementTransaction)
                );
                $this->applyRemedialPayment(
                    $replacementTransaction,
                    $academicYearId,
                    $this->resolveRemedialFeeAmount($replacementTransaction)
                );

                $this->appendPaymentLedgerEntry(
                    $replacementTransaction,
                    $academicYearId,
                    "Payment Reissue ({$replacementTransaction->or_number})"
                );
            }

            $lockedTransaction->update([
                'status' => 'reissued',
                'reissue_reason' => $request->validated('reason'),
                'reissued_at' => now(),
                'reissued_by' => auth()->id(),
                'reissued_transaction_id' => $replacementTransaction->id,
            ]);

            if ($academicYearId) {
                $this->syncEnrollmentStatusAfterCorrection(
                    (int) $lockedTransaction->student_id,
                    $academicYearId
                );
            }
        });

        DashboardCacheService::bust();

        return back()->with('success', 'Transaction reissued successfully.');
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

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            'voided' => 'Voided',
            'refunded' => 'Refunded',
            'reissued' => 'Reissued',
            default => 'Posted',
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

    private function buildTransactionHistoryQuery(
        ?AcademicYear $selectedAcademicYear,
        string $search,
        ?string $paymentMode,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        return Transaction::query()
            ->with([
                'student:id,first_name,last_name,lrn',
                'cashier:id,first_name,last_name,name',
                'items:id,transaction_id,fee_id,inventory_item_id,description,amount',
                'reissuedTransaction:id,or_number',
                'voidedBy:id,first_name,last_name,name',
                'refundedBy:id,first_name,last_name,name',
                'reissuedBy:id,first_name,last_name,name',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('or_number', 'like', "%{$search}%")
                        ->orWhereHas('student', function (Builder $studentQuery) use ($search) {
                            $studentQuery
                                ->where('lrn', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($paymentMode, function (Builder $query, string $paymentMode) {
                $query->where('payment_mode', $paymentMode);
            })
            ->when($selectedAcademicYear, function (Builder $query) use ($selectedAcademicYear) {
                $hasDateBounds = filled($selectedAcademicYear->start_date)
                    && filled($selectedAcademicYear->end_date);

                $query->where(function (Builder $yearQuery) use ($selectedAcademicYear, $hasDateBounds) {
                    if ($hasDateBounds) {
                        $yearQuery
                            ->whereBetween('created_at', [
                                "{$selectedAcademicYear->start_date} 00:00:00",
                                "{$selectedAcademicYear->end_date} 23:59:59",
                            ])
                            ->orWhereHas('ledgerEntries', function (Builder $ledgerQuery) use ($selectedAcademicYear) {
                                $ledgerQuery
                                    ->where('academic_year_id', $selectedAcademicYear->id)
                                    ->whereNotNull('credit');
                            });

                        return;
                    }

                    $yearQuery->whereHas('ledgerEntries', function (Builder $ledgerQuery) use ($selectedAcademicYear) {
                        $ledgerQuery
                            ->where('academic_year_id', $selectedAcademicYear->id)
                            ->whereNotNull('credit');
                    });
                });
            })
            ->when($dateFrom, function (Builder $query, string $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function (Builder $query, string $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveExportRangeDates(Builder $baseQuery, string $exportRange): array
    {
        if ($exportRange === 'this_week') {
            return [
                now()->copy()->startOfWeek()->toDateString(),
                now()->copy()->endOfDay()->toDateString(),
            ];
        }

        if ($exportRange === 'all_time') {
            $rangeQuery = (clone $baseQuery)->reorder();
            $earliest = (clone $rangeQuery)->toBase()->min('created_at');
            $latest = (clone $rangeQuery)->toBase()->max('created_at');

            if (! $earliest || ! $latest) {
                $today = now()->toDateString();

                return [$today, $today];
            }

            return [
                Carbon::parse((string) $earliest)->toDateString(),
                Carbon::parse((string) $latest)->toDateString(),
            ];
        }

        return [
            now()->copy()->startOfMonth()->toDateString(),
            now()->copy()->endOfDay()->toDateString(),
        ];
    }

    private function resolveRangePresetLabel(string $exportRange): string
    {
        return match ($exportRange) {
            'this_week' => 'This Week',
            'all_time' => 'All Time',
            default => 'This Month',
        };
    }

    /**
     * @return array<int, array{start: string, end: string, label: string}>
     */
    private function buildMonthlySegments(string $dateFrom, string $dateTo): array
    {
        $segments = [];
        $cursor = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();

        while ($cursor->lte($end)) {
            $segmentStart = $cursor->copy();
            $segmentEnd = $cursor->copy()->endOfMonth();
            if ($segmentEnd->gt($end)) {
                $segmentEnd = $end->copy();
            }

            $segments[] = [
                'start' => $segmentStart->toDateString(),
                'end' => $segmentEnd->toDateString(),
                'label' => sprintf(
                    '%s %d-%d, %d',
                    $segmentStart->format('F'),
                    $segmentStart->day,
                    $segmentEnd->day,
                    $segmentEnd->year
                ),
            ];

            $cursor = $segmentEnd->copy()->addDay()->startOfDay();
        }

        if (count($segments) === 0) {
            $today = now();
            $segments[] = [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
                'label' => sprintf('%s %d-%d, %d', $today->format('F'), $today->day, $today->day, $today->year),
            ];
        }

        return $segments;
    }

    private function resolveUserDisplayName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim("{$user->first_name} {$user->last_name}");

        if ($name !== '') {
            return $name;
        }

        return $user->name ?: null;
    }

    private function rollbackPaymentImpact(Transaction $transaction, ?int $academicYearId): void
    {
        $allocations = TransactionDueAllocation::query()
            ->where('transaction_id', $transaction->id)
            ->lockForUpdate()
            ->get();

        if ($allocations->isNotEmpty()) {
            $this->rollbackUsingStoredAllocations($allocations);

            return;
        }

        $this->rollbackWithoutAllocations(
            (int) $transaction->student_id,
            $academicYearId,
            $this->resolveAssessmentFeeAmount($transaction)
        );
    }

    private function resolveAssessmentFeeAmount(Transaction $transaction): float
    {
        return round((float) $transaction->items
            ->filter(function ($item): bool {
                return trim((string) $item->description) === 'Assessment Fee';
            })
            ->sum('amount'), 2);
    }

    private function resolveRemedialFeeAmount(Transaction $transaction): float
    {
        return round((float) $transaction->items
            ->filter(function ($item): bool {
                return trim((string) $item->description) === 'Remedial Fee';
            })
            ->sum('amount'), 2);
    }

    private function appendReverseLedgerEntry(Transaction $transaction, int $academicYearId, string $description): void
    {
        $previousRunningBalance = (float) (LedgerEntry::query()
            ->where('student_id', $transaction->student_id)
            ->where('academic_year_id', $academicYearId)
            ->latest('date')
            ->latest('id')
            ->value('running_balance') ?? 0.0);

        $entryAmount = round((float) $transaction->total_amount, 2);
        $newRunningBalance = round($previousRunningBalance + $entryAmount, 2);

        LedgerEntry::query()->create([
            'student_id' => $transaction->student_id,
            'academic_year_id' => $academicYearId,
            'date' => now()->toDateString(),
            'description' => $description,
            'debit' => $entryAmount,
            'credit' => null,
            'running_balance' => $newRunningBalance,
            'reference_id' => $transaction->id,
        ]);
    }

    private function appendPaymentLedgerEntry(Transaction $transaction, int $academicYearId, string $description): void
    {
        $previousRunningBalance = (float) (LedgerEntry::query()
            ->where('student_id', $transaction->student_id)
            ->where('academic_year_id', $academicYearId)
            ->latest('date')
            ->latest('id')
            ->value('running_balance') ?? 0.0);

        $entryAmount = round((float) $transaction->total_amount, 2);
        $newRunningBalance = round($previousRunningBalance - $entryAmount, 2);

        LedgerEntry::query()->create([
            'student_id' => $transaction->student_id,
            'academic_year_id' => $academicYearId,
            'date' => now()->toDateString(),
            'description' => $description,
            'debit' => null,
            'credit' => $entryAmount,
            'running_balance' => $newRunningBalance,
            'reference_id' => $transaction->id,
        ]);
    }

    private function applyPaymentAcrossDues(Transaction $transaction, float $paymentAmount): void
    {
        $remainingPaymentCents = (int) round(max($paymentAmount, 0) * 100);

        if ($remainingPaymentCents <= 0) {
            return;
        }

        $billingSchedules = BillingSchedule::query()
            ->where('student_id', $transaction->student_id)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($billingSchedules as $billingSchedule) {
            if ($remainingPaymentCents <= 0) {
                break;
            }

            $amountDueCents = (int) round((float) $billingSchedule->amount_due * 100);
            $amountPaidCents = (int) round((float) $billingSchedule->amount_paid * 100);
            $outstandingCents = max($amountDueCents - $amountPaidCents, 0);

            if ($outstandingCents <= 0) {
                continue;
            }

            $appliedCents = min($remainingPaymentCents, $outstandingCents);
            $newPaidCents = $amountPaidCents + $appliedCents;

            $billingSchedule->update([
                'amount_paid' => round($newPaidCents / 100, 2),
                'status' => $newPaidCents >= $amountDueCents ? 'paid' : 'partially_paid',
            ]);

            $transaction->dueAllocations()->create([
                'billing_schedule_id' => $billingSchedule->id,
                'amount' => round($appliedCents / 100, 2),
            ]);

            $remainingPaymentCents -= $appliedCents;
        }
    }

    /**
     * @param  Collection<int, TransactionDueAllocation>  $allocations
     */
    private function rollbackUsingStoredAllocations(Collection $allocations): void
    {
        $billingSchedules = BillingSchedule::query()
            ->whereIn('id', $allocations->pluck('billing_schedule_id')->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($allocations as $allocation) {
            $billingSchedule = $billingSchedules->get((int) $allocation->billing_schedule_id);
            if (! $billingSchedule) {
                continue;
            }

            $currentPaidCents = (int) round((float) $billingSchedule->amount_paid * 100);
            $allocationCents = (int) round((float) $allocation->amount * 100);
            $newPaidCents = max($currentPaidCents - $allocationCents, 0);

            $billingSchedule->update([
                'amount_paid' => round($newPaidCents / 100, 2),
                'status' => $this->resolveBillingScheduleStatus(
                    $newPaidCents,
                    (int) round((float) $billingSchedule->amount_due * 100)
                ),
            ]);
        }
    }

    private function rollbackWithoutAllocations(int $studentId, ?int $academicYearId, float $amount): void
    {
        if (! $academicYearId || $amount <= 0) {
            return;
        }

        $remainingCents = (int) round($amount * 100);

        $billingSchedules = BillingSchedule::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($billingSchedules as $billingSchedule) {
            if ($remainingCents <= 0) {
                break;
            }

            $currentPaidCents = (int) round((float) $billingSchedule->amount_paid * 100);
            if ($currentPaidCents <= 0) {
                continue;
            }

            $rollbackCents = min($remainingCents, $currentPaidCents);
            $newPaidCents = max($currentPaidCents - $rollbackCents, 0);

            $billingSchedule->update([
                'amount_paid' => round($newPaidCents / 100, 2),
                'status' => $this->resolveBillingScheduleStatus(
                    $newPaidCents,
                    (int) round((float) $billingSchedule->amount_due * 100)
                ),
            ]);

            $remainingCents -= $rollbackCents;
        }
    }

    private function resolveBillingScheduleStatus(int $amountPaidCents, int $amountDueCents): string
    {
        if ($amountPaidCents <= 0) {
            return 'unpaid';
        }

        if ($amountPaidCents >= $amountDueCents) {
            return 'paid';
        }

        return 'partially_paid';
    }

    private function rollbackRemedialPaymentImpact(Transaction $transaction, ?int $academicYearId): void
    {
        if (! $academicYearId) {
            return;
        }

        $remedialPaymentAmount = $this->resolveRemedialFeeAmount($transaction);
        if ($remedialPaymentAmount <= 0) {
            return;
        }

        $remedialCase = RemedialCase::query()
            ->where('student_id', $transaction->student_id)
            ->where('academic_year_id', $academicYearId)
            ->lockForUpdate()
            ->first();

        if (! $remedialCase) {
            return;
        }

        $nextAmountPaid = round(max((float) $remedialCase->amount_paid - $remedialPaymentAmount, 0), 2);
        $nextStatus = $this->resolveRemedialCaseStatus($nextAmountPaid, (float) $remedialCase->total_amount);

        $remedialCase->update([
            'amount_paid' => $nextAmountPaid,
            'status' => $nextStatus,
            'paid_at' => $nextStatus === 'paid' ? now() : null,
        ]);
    }

    private function applyRemedialPayment(Transaction $transaction, ?int $academicYearId, float $remedialPaymentAmount): void
    {
        if (! $academicYearId || $remedialPaymentAmount <= 0) {
            return;
        }

        $remedialCase = RemedialCase::query()
            ->where('student_id', $transaction->student_id)
            ->where('academic_year_id', $academicYearId)
            ->lockForUpdate()
            ->first();

        if (! $remedialCase) {
            return;
        }

        $nextAmountPaid = round((float) $remedialCase->amount_paid + $remedialPaymentAmount, 2);
        $cappedAmountPaid = min($nextAmountPaid, (float) $remedialCase->total_amount);
        $nextStatus = $this->resolveRemedialCaseStatus($cappedAmountPaid, (float) $remedialCase->total_amount);

        $remedialCase->update([
            'amount_paid' => $cappedAmountPaid,
            'status' => $nextStatus,
            'paid_at' => $nextStatus === 'paid' ? now() : null,
        ]);
    }

    private function resolveRemedialCaseStatus(float $amountPaid, float $totalAmount): string
    {
        if ($totalAmount <= 0) {
            return 'paid';
        }

        if ($amountPaid <= 0) {
            return 'for_cashier_payment';
        }

        if ($amountPaid >= $totalAmount) {
            return 'paid';
        }

        return 'partial_payment';
    }

    private function resolveAcademicYearIdForTransaction(Transaction $transaction): ?int
    {
        $ledgerEntryAcademicYearId = LedgerEntry::query()
            ->where('reference_id', $transaction->id)
            ->whereNotNull('credit')
            ->orderByDesc('id')
            ->value('academic_year_id');

        if ($ledgerEntryAcademicYearId) {
            return (int) $ledgerEntryAcademicYearId;
        }

        $transactionDate = $transaction->created_at?->toDateString();
        if (! $transactionDate) {
            return null;
        }

        $academicYearId = AcademicYear::query()
            ->whereDate('start_date', '<=', $transactionDate)
            ->whereDate('end_date', '>=', $transactionDate)
            ->value('id');

        return $academicYearId ? (int) $academicYearId : null;
    }

    private function syncEnrollmentStatusAfterCorrection(int $studentId, int $academicYearId): void
    {
        $academicYear = AcademicYear::query()->find($academicYearId);
        if (! $academicYear) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->latest('id')
            ->first();

        if (! $enrollment) {
            return;
        }

        $totalPaidInYear = round((float) Transaction::query()
            ->where('student_id', $studentId)
            ->whereNotIn('status', ['voided', 'refunded', 'reissued'])
            ->whereHas('ledgerEntries', function ($query) use ($academicYearId) {
                $query
                    ->where('academic_year_id', $academicYearId)
                    ->whereNotNull('credit');
            })
            ->sum('total_amount'), 2);

        if ($enrollment->payment_term === 'cash') {
            $this->transitionEnrollmentStatus(
                $enrollment,
                $totalPaidInYear > 0 ? 'enrolled' : 'for_cashier_payment'
            );

            return;
        }

        if ($totalPaidInYear >= (float) $enrollment->downpayment) {
            $this->transitionEnrollmentStatus($enrollment, 'enrolled');

            return;
        }

        $this->transitionEnrollmentStatus($enrollment, 'for_cashier_payment');
    }

    private function transitionEnrollmentStatus(Enrollment $enrollment, string $newStatus): void
    {
        $previousStatus = (string) $enrollment->status;

        if ($previousStatus === $newStatus) {
            return;
        }

        $enrollment->update(['status' => $newStatus]);

        if ($previousStatus !== 'enrolled' && $newStatus === 'enrolled') {
            $this->enrollmentAccountClaimService->issueForEnrollment($enrollment);
        }
    }
}
