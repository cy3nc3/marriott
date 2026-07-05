<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CohortFinanceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $cashier = User::query()
            ->where('role', UserRole::FINANCE)
            ->orderBy('id')
            ->first();

        if (! $cashier instanceof User) {
            return;
        }

        $hasExisting = LedgerEntry::query()->exists();
        if (! $hasExisting) {
            $this->clearFinanceTables();
            $this->seedFees();
        } else {
            // Ensure fees exist even if we didn't clear
            $this->seedFees();
        }

        $inventoryItems = $this->seedInventoryItems();

        Enrollment::query()
            ->with(['academicYear', 'gradeLevel'])
            ->where('status', 'enrolled')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ledger_entries')
                    ->whereColumn('ledger_entries.student_id', 'enrollments.student_id')
                    ->whereColumn('ledger_entries.academic_year_id', 'enrollments.academic_year_id');
            })
            ->orderBy('academic_year_id')
            ->orderBy('student_id')
            ->chunk(500, function ($enrollments) use ($cashier, $inventoryItems): void {
                foreach ($enrollments as $index => $enrollment) {
                    if (! $enrollment->academicYear) {
                        continue;
                    }

                    $this->seedEnrollmentFinanceHistory($enrollment, $cashier, $inventoryItems, $index);
                }
            });
    }

    private function clearFinanceTables(): void
    {
        DB::table('transaction_due_allocations')->delete();
        DB::table('transaction_items')->delete();
        Transaction::query()->delete();
        LedgerEntry::query()->delete();
        BillingSchedule::withoutEvents(fn (): int => BillingSchedule::query()->delete());
    }

    private function seedFees(): void
    {
        $academicYears = AcademicYear::query()->orderBy('start_date')->get();

        foreach ($academicYears as $academicYear) {
            $yearOffset = match ((string) $academicYear->name) {
                '2023-2024' => -1600,
                '2024-2025' => -800,
                default => 0,
            };

            foreach (\App\Models\GradeLevel::query()->get() as $gradeLevel) {
                $gradeOrder = (int) $gradeLevel->level_order;

                foreach ([
                    ['type' => 'tuition', 'name' => 'Tuition Fee', 'amount' => 33000 + (($gradeOrder - 7) * 1200) + $yearOffset],
                    ['type' => 'miscellaneous', 'name' => 'Miscellaneous Fee', 'amount' => 7000 + (($gradeOrder - 7) * 300)],
                    ['type' => 'books_modules', 'name' => 'Books and Modules', 'amount' => 3200 + (($gradeOrder - 7) * 100)],
                    ['type' => 'other', 'name' => 'Energy and Facilities', 'amount' => 1500],
                ] as $feeRow) {
                    Fee::query()->updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'grade_level_id' => $gradeLevel->id,
                            'type' => $feeRow['type'],
                            'name' => $feeRow['name'],
                        ],
                        ['amount' => $feeRow['amount']]
                    );
                }
            }
        }
    }

    /**
     * @return array<int, InventoryItem>
     */
    private function seedInventoryItems(): array
    {
        $items = [];

        foreach ([
            ['name' => 'PE Uniform Set', 'price' => 850, 'type' => 'Uniform'],
            ['name' => 'School ID Replacement', 'price' => 250, 'type' => 'Others'],
            ['name' => 'Science Lab Manual', 'price' => 380, 'type' => 'Book'],
            ['name' => 'School Patch and Tie', 'price' => 420, 'type' => 'Merchandise'],
        ] as $row) {
            $items[] = InventoryItem::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'price' => $row['price'],
                    'type' => $row['type'],
                ]
            );
        }

        return $items;
    }

    /**
     * @param  array<int, InventoryItem>  $inventoryItems
     */
    private function seedEnrollmentFinanceHistory(
        Enrollment $enrollment,
        User $cashier,
        array $inventoryItems,
        int $seedIndex
    ): void {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear) {
            return;
        }

        $assessmentTotal = $this->assessmentTotal($enrollment);
        $runningBalance = $assessmentTotal;

        LedgerEntry::query()->create([
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $academicYear->id,
            'date' => $academicYear->start_date,
            'description' => 'Opening Balance (Seeded Assessment)',
            'debit' => $assessmentTotal,
            'credit' => null,
            'running_balance' => $runningBalance,
            'reference_id' => null,
        ]);

        $schedules = $this->createSchedules($enrollment, $assessmentTotal);
        $schedulesToPay = $this->determineSchedulesToPay($enrollment, $academicYear, $schedules);

        foreach (array_values($schedulesToPay) as $paymentIndex => $schedule) {
            $runningBalance = $this->postPayment(
                enrollment: $enrollment,
                cashier: $cashier,
                date: CarbonImmutable::parse((string) ($schedule->due_date ?: $academicYear->start_date))->addDays($paymentIndex),
                description: "Assessment Payment - {$schedule->description}",
                amount: (float) $schedule->amount_due,
                runningBalance: $runningBalance,
                sequence: $paymentIndex + 1,
                billingSchedule: $schedule
            );
        }

        $this->seedInventoryTransactions($enrollment, $cashier, $inventoryItems, $runningBalance, $seedIndex);
    }

    /**
     * @return array<int, BillingSchedule>
     */
    private function createSchedules(Enrollment $enrollment, float $assessmentTotal): array
    {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear) {
            return [];
        }

        $paymentTerm = (string) $enrollment->payment_term;
        $downpayment = max((float) $enrollment->downpayment, 0);

        if ($paymentTerm === 'cash') {
            return [$this->createSchedule($enrollment, 'Upon enrollment', (string) $academicYear->start_date, $assessmentTotal)];
        }

        $schedules = [];
        if ($downpayment > 0) {
            $schedules[] = $this->createSchedule($enrollment, 'Upon enrollment', (string) $academicYear->start_date, min($downpayment, $assessmentTotal));
        }

        $remaining = max($assessmentTotal - $downpayment, 0);
        $installments = match ($paymentTerm) {
            'quarterly' => 4,
            'semi-annual' => 2,
            default => 10,
        };

        if ($remaining <= 0 || $installments <= 0) {
            return $schedules;
        }

        $baseDate = CarbonImmutable::parse((string) $academicYear->start_date)->addMonth();
        $amount = round($remaining / $installments, 2);
        $allocated = 0.0;

        for ($i = 1; $i <= $installments; $i++) {
            $installmentAmount = $i === $installments
                ? round($remaining - $allocated, 2)
                : $amount;
            $allocated = round($allocated + $installmentAmount, 2);

            $schedules[] = $this->createSchedule(
                $enrollment,
                $installments === 10 ? "Monthly Due {$i}" : "Installment {$i}",
                $baseDate->addMonths($i - 1)->toDateString(),
                $installmentAmount
            );
        }

        return $schedules;
    }

    private function createSchedule(Enrollment $enrollment, string $description, ?string $dueDate, float $amount): BillingSchedule
    {
        return BillingSchedule::withoutEvents(fn (): BillingSchedule => BillingSchedule::query()->create([
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $enrollment->academic_year_id,
            'description' => $description,
            'due_date' => $dueDate,
            'amount_due' => $amount,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]));
    }

    private function postPayment(
        Enrollment $enrollment,
        User $cashier,
        CarbonImmutable $date,
        string $description,
        float $amount,
        float $runningBalance,
        int $sequence,
        ?BillingSchedule $billingSchedule = null,
        ?InventoryItem $inventoryItem = null
    ): float {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear) {
            return $runningBalance;
        }

        $transaction = Transaction::query()->create([
            'or_number' => $this->orNumber($academicYear, (int) $enrollment->student_id, $sequence),
            'student_id' => $enrollment->student_id,
            'cashier_id' => $cashier->id,
            'total_amount' => $amount,
            'payment_mode' => ['cash', 'gcash', 'bank_transfer'][(int) $enrollment->id % 3],
            'reference_no' => null,
            'remarks' => 'Seeded cohort finance history.',
            'status' => 'posted',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $transaction->items()->create([
            'fee_id' => $billingSchedule ? $this->tuitionFeeId($enrollment) : null,
            'inventory_item_id' => $inventoryItem?->id,
            'description' => $description,
            'amount' => $amount,
        ]);

        if ($billingSchedule instanceof BillingSchedule) {
            BillingSchedule::withoutEvents(function () use ($billingSchedule, $amount, $transaction): void {
                $billingSchedule->update([
                    'amount_paid' => $amount,
                    'status' => 'paid',
                ]);

                $transaction->dueAllocations()->create([
                    'billing_schedule_id' => $billingSchedule->id,
                    'amount' => $amount,
                ]);
            });
        }

        $nextBalance = round($runningBalance - $amount, 2);
        LedgerEntry::query()->create([
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $academicYear->id,
            'date' => $date->toDateString(),
            'description' => "Payment ({$transaction->or_number})",
            'debit' => null,
            'credit' => $amount,
            'running_balance' => $nextBalance,
            'reference_id' => $transaction->id,
        ]);

        return $nextBalance;
    }

    /**
     * @param  array<int, InventoryItem>  $inventoryItems
     */
    private function seedInventoryTransactions(
        Enrollment $enrollment,
        User $cashier,
        array $inventoryItems,
        float $runningBalance,
        int $seedIndex
    ): void {
        $academicYear = $enrollment->academicYear;
        if (! $academicYear instanceof AcademicYear || $inventoryItems === []) {
            return;
        }

        $itemsToBuy = (string) $academicYear->status === 'ongoing'
            ? array_slice($inventoryItems, 0, 1)
            : array_slice($inventoryItems, 0, 3);

        foreach ($itemsToBuy as $itemIndex => $item) {
            if (! $item instanceof InventoryItem) {
                continue;
            }

            $date = CarbonImmutable::parse((string) $academicYear->start_date)->addDays(12 + $itemIndex + ($seedIndex % 4));
            $amount = (float) $item->price;
            $runningBalance = round($runningBalance + $amount, 2);

            LedgerEntry::query()->create([
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $academicYear->id,
                'date' => $date->toDateString(),
                'description' => "Inventory Charge - {$item->name}",
                'debit' => $amount,
                'credit' => null,
                'running_balance' => $runningBalance,
                'reference_id' => null,
            ]);

            $runningBalance = $this->postPayment(
                enrollment: $enrollment,
                cashier: $cashier,
                date: $date->addMinutes(10),
                description: $item->name,
                amount: $amount,
                runningBalance: $runningBalance,
                sequence: 70 + $itemIndex,
                inventoryItem: $item
            );
        }
    }

    private function assessmentTotal(Enrollment $enrollment): float
    {
        return (float) Fee::query()
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('grade_level_id', $enrollment->grade_level_id)
            ->sum('amount');
    }

    private function tuitionFeeId(Enrollment $enrollment): ?int
    {
        $id = Fee::query()
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('grade_level_id', $enrollment->grade_level_id)
            ->where('type', 'tuition')
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function orNumber(AcademicYear $academicYear, int $studentId, int $sequence): string
    {
        $year = str_replace('-', '', (string) $academicYear->name);

        return sprintf('OR-%s-%05d-%02d', substr($year, 0, 4), $studentId, $sequence);
    }

    /**
     * @param  array<int, BillingSchedule>  $schedules
     * @return array<int, BillingSchedule>
     */
    private function determineSchedulesToPay(
        Enrollment $enrollment,
        AcademicYear $academicYear,
        array $schedules
    ): array {
        if ((string) $academicYear->status !== 'ongoing') {
            return $schedules;
        }

        $today = CarbonImmutable::today();
        $overdueCandidates = collect($schedules)
            ->filter(function (BillingSchedule $schedule) use ($today): bool {
                if ($schedule->description === 'Upon enrollment') {
                    return false;
                }

                if (! $schedule->due_date) {
                    return false;
                }

                return CarbonImmutable::parse((string) $schedule->due_date)->lt($today);
            })
            ->sortByDesc(fn (BillingSchedule $schedule): string => (string) $schedule->due_date)
            ->values();

        $leaveUnpaidCount = 0;
        if ($overdueCandidates->isNotEmpty() && ((int) $enrollment->student_id % 8 === 0)) {
            $leaveUnpaidCount = ((int) $enrollment->student_id % 24 === 0) ? 2 : 1;
        }

        $leaveUnpaidIds = $overdueCandidates
            ->take($leaveUnpaidCount)
            ->pluck('id')
            ->all();

        return array_values(array_filter(
            $schedules,
            function (BillingSchedule $schedule) use ($today, $leaveUnpaidIds): bool {
                if ($schedule->description === 'Upon enrollment') {
                    return true;
                }

                if (! $schedule->due_date) {
                    return false;
                }

                $isDueOrPastDue = CarbonImmutable::parse((string) $schedule->due_date)->lessThanOrEqualTo($today);
                if (! $isDueOrPastDue) {
                    return false;
                }

                return ! in_array((int) $schedule->id, $leaveUnpaidIds, true);
            }
        ));
    }
}
