<?php

namespace App\Http\Controllers\Finance;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexDailyReportsRequest;
use App\Models\AcademicYear;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\Finance\DailyReportsWorkbookExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyReportsController extends Controller
{
    public function index(IndexDailyReportsRequest $request): Response
    {
        $validated = $request->validated();
        $schoolYearOptions = $this->schoolYearOptions();
        $selectedAcademicYear = $this->resolveSelectedAcademicYear($schoolYearOptions, $validated);
        [$cashierId, $paymentMode, $dateFrom, $dateTo] = $this->resolveFilterValues($validated);

        $transactionQuery = $this->buildFilteredTransactionQuery(
            $selectedAcademicYear,
            $cashierId,
            $paymentMode,
            $dateFrom,
            $dateTo,
        );

        $summary = $this->buildSummary((clone $transactionQuery));
        $breakdownRows = $this->buildBreakdownRows((clone $transactionQuery)
            ->whereNotIn('status', $this->correctedStatuses())
            ->with([
                'items:id,transaction_id,fee_id,inventory_item_id,description,amount',
                'items.fee:id,type',
            ])
            ->get());
        $transactionRows = (clone $transactionQuery)
            ->with([
                'student:id,first_name,last_name,lrn',
                'cashier:id,first_name,last_name,name',
                'items:id,transaction_id,fee_id,inventory_item_id,description,amount',
                'items.fee:id,type',
            ])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Transaction $transaction): array => $this->mapTransactionRow($transaction));

        return Inertia::render('finance/daily-reports/index', [
            'cashiers' => $this->cashierOptions(),
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedAcademicYear?->id,
            'breakdown_rows' => $breakdownRows,
            'transaction_rows' => $transactionRows,
            'summary' => $summary,
            'filters' => [
                'academic_year_id' => $selectedAcademicYear?->id,
                'cashier_id' => $cashierId,
                'payment_mode' => $paymentMode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function export(
        IndexDailyReportsRequest $request,
        DailyReportsWorkbookExporter $exporter,
    ): BinaryFileResponse {
        $validated = $request->validated();
        $schoolYearOptions = $this->schoolYearOptions();
        $selectedAcademicYear = $this->resolveSelectedAcademicYear($schoolYearOptions, $validated);
        [$cashierId, $paymentMode, $dateFrom, $dateTo] = $this->resolveFilterValues($validated);

        $transactionQuery = $this->buildFilteredTransactionQuery(
            $selectedAcademicYear,
            $cashierId,
            $paymentMode,
            $dateFrom,
            $dateTo,
        );

        $summary = $this->buildSummary((clone $transactionQuery));
        $breakdownRows = $this->buildBreakdownRows((clone $transactionQuery)
            ->whereNotIn('status', $this->correctedStatuses())
            ->with([
                'items:id,transaction_id,fee_id,inventory_item_id,description,amount',
                'items.fee:id,type',
            ])
            ->get())
            ->all();
        $transactionRows = (clone $transactionQuery)
            ->with([
                'student:id,first_name,last_name,lrn',
                'cashier:id,first_name,last_name,name',
                'items:id,transaction_id,fee_id,inventory_item_id,description,amount',
                'items.fee:id,type',
            ])
            ->get()
            ->flatMap(function (Transaction $transaction): array {
                return $this->mapExportTransactionRows($transaction);
            })
            ->map(function (array $row): array {
                $row['posted_at'] = $row['posted_at']
                    ? Carbon::parse((string) $row['posted_at'])->format('Y-m-d h:i A')
                    : '-';

                return $row;
            })
            ->values()
            ->all();

        $outputPath = storage_path('app/temp/'.uniqid('daily-reports-', true).'.xlsx');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $exporter->export(
            $outputPath,
            [
                'generated_at' => now()->format('F j, Y h:i A'),
                'school_year' => $selectedAcademicYear?->name ?? 'All School Years',
                'cashier' => $this->resolveCashierLabel($cashierId),
                'payment_mode' => $paymentMode ? $this->formatPaymentMode($paymentMode) : 'All Payment Modes',
                'date_from' => $dateFrom ?? 'Any',
                'date_to' => $dateTo ?? 'Any',
            ],
            $summary,
            $breakdownRows,
            $transactionRows,
        );

        $reportDate = now()->format('Ymd-His');

        return response()
            ->download($outputPath, "daily-reports-{$reportDate}.xlsx")
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: int|null, 1: string|null, 2: string|null, 3: string|null}
     */
    private function resolveFilterValues(array $validated): array
    {
        return [
            isset($validated['cashier_id']) ? (int) $validated['cashier_id'] : null,
            $validated['payment_mode'] ?? null,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        ];
    }

    private function schoolYearOptions(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'status', 'start_date'])
            ->map(function (AcademicYear $academicYear): array {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveSelectedAcademicYear(Collection $schoolYearOptions, array $validated): ?AcademicYear
    {
        $selectedAcademicYearId = isset($validated['academic_year_id'])
            ? (int) $validated['academic_year_id']
            : 0;

        if (
            $selectedAcademicYearId <= 0
            || ! $schoolYearOptions->pluck('id')->contains($selectedAcademicYearId)
        ) {
            $selectedAcademicYearId = (int) ($schoolYearOptions->firstWhere('status', 'ongoing')['id']
                ?? ($schoolYearOptions->first()['id'] ?? 0));
        }

        return $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;
    }

    private function buildFilteredTransactionQuery(
        ?AcademicYear $selectedAcademicYear,
        ?int $cashierId,
        ?string $paymentMode,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        return Transaction::query()
            ->when($cashierId, function (Builder $query, int $cashierId): void {
                $query->where('cashier_id', $cashierId);
            })
            ->when($paymentMode, function (Builder $query, string $paymentMode): void {
                $query->where('payment_mode', $paymentMode);
            })
            ->when($selectedAcademicYear, function (Builder $query) use ($selectedAcademicYear): void {
                $hasDateBounds = filled($selectedAcademicYear?->start_date)
                    && filled($selectedAcademicYear?->end_date);

                $query->where(function (Builder $yearQuery) use ($selectedAcademicYear, $hasDateBounds): void {
                    if ($hasDateBounds) {
                        $yearQuery
                            ->whereBetween('created_at', [
                                "{$selectedAcademicYear->start_date} 00:00:00",
                                "{$selectedAcademicYear->end_date} 23:59:59",
                            ])
                            ->orWhereHas('ledgerEntries', function (Builder $ledgerQuery) use ($selectedAcademicYear): void {
                                $ledgerQuery
                                    ->where('academic_year_id', $selectedAcademicYear?->id)
                                    ->whereNotNull('credit');
                            });

                        return;
                    }

                    $yearQuery->whereHas('ledgerEntries', function (Builder $ledgerQuery) use ($selectedAcademicYear): void {
                        $ledgerQuery
                            ->where('academic_year_id', $selectedAcademicYear?->id)
                            ->whereNotNull('credit');
                    });
                });
            })
            ->when($dateFrom, function (Builder $query, string $dateFrom): void {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function (Builder $query, string $dateTo): void {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    private function correctedStatuses(): array
    {
        return ['voided', 'refunded', 'reissued'];
    }

    /**
     * @return array{
     *     transaction_count: int,
     *     gross_collection: float,
     *     cash_on_hand: float,
     *     digital_collection: float,
     *     void_adjustments: float
     * }
     */
    private function buildSummary(Builder $transactionQuery): array
    {
        $correctedStatuses = $this->correctedStatuses();
        $transactionCount = (int) (clone $transactionQuery)->count();
        $voidAdjustments = round((float) (clone $transactionQuery)
            ->whereIn('status', $correctedStatuses)
            ->sum('total_amount'), 2);
        $grossCollection = round((float) (clone $transactionQuery)
            ->whereNotIn('status', $correctedStatuses)
            ->sum('total_amount'), 2);
        $cashOnHand = round((float) (clone $transactionQuery)
            ->whereNotIn('status', $correctedStatuses)
            ->where('payment_mode', 'cash')
            ->sum('total_amount'), 2);
        $digitalCollection = round($grossCollection - $cashOnHand, 2);

        return [
            'transaction_count' => $transactionCount,
            'gross_collection' => $grossCollection,
            'cash_on_hand' => $cashOnHand,
            'digital_collection' => $digitalCollection,
            'void_adjustments' => $voidAdjustments,
        ];
    }

    private function cashierOptions(): Collection
    {
        return User::query()
            ->where('role', UserRole::FINANCE->value)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('name')
            ->get(['id', 'first_name', 'last_name', 'name'])
            ->map(function (User $cashier): array {
                $cashierName = trim("{$cashier->first_name} {$cashier->last_name}");

                return [
                    'id' => $cashier->id,
                    'name' => $cashierName !== '' ? $cashierName : ($cashier->name ?? 'Cashier'),
                ];
            })
            ->values();
    }

    private function resolveCashierLabel(?int $cashierId): string
    {
        if (! $cashierId) {
            return 'All Cashiers';
        }

        $cashier = User::query()->find($cashierId, ['first_name', 'last_name', 'name']);
        if (! $cashier) {
            return 'All Cashiers';
        }

        $cashierName = trim("{$cashier->first_name} {$cashier->last_name}");

        return $cashierName !== '' ? $cashierName : ($cashier->name ?? 'Cashier');
    }

    /**
     * @param  array<int, Transaction>  $transactions
     */
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
            ->map(function (array $category): array {
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

    /**
     * @return array{
     *     id: int,
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string|null
     * }
     */
    private function mapTransactionRow(Transaction $transaction): array
    {
        $studentName = trim("{$transaction->student?->first_name} {$transaction->student?->last_name}");
        $cashierName = trim("{$transaction->cashier?->first_name} {$transaction->cashier?->last_name}");

        return [
            'id' => $transaction->id,
            'or_number' => $transaction->or_number,
            'student_name' => $studentName !== '' ? $studentName : '-',
            'payment_type' => $this->resolvePaymentType($transaction),
            'payment_mode' => $transaction->payment_mode,
            'payment_mode_label' => $this->formatPaymentMode($transaction->payment_mode),
            'status' => $transaction->status ?: 'posted',
            'amount' => (float) $transaction->total_amount,
            'cashier_name' => $cashierName !== '' ? $cashierName : ($transaction->cashier?->name ?? '-'),
            'posted_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string|null
     * }>
     */
    private function mapExportTransactionRows(Transaction $transaction): array
    {
        $baseRow = $this->mapTransactionRow($transaction);
        $rows = $transaction->items
            ->sortBy('id')
            ->values()
            ->map(function (TransactionItem $item) use ($baseRow): array {
                return [
                    ...$baseRow,
                    'payment_type' => trim((string) $item->description) !== ''
                        ? (string) $item->description
                        : 'Payment',
                    'amount' => (float) $item->amount,
                ];
            })
            ->all();

        if (count($rows) > 0) {
            return $rows;
        }

        return [$baseRow];
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
