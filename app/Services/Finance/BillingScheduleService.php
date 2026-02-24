<?php

namespace App\Services\Finance;

use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\StudentDiscount;
use Illuminate\Support\Carbon;

class BillingScheduleService
{
    public function syncForEnrollment(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('academicYear');

        if (! $enrollment->academicYear) {
            return;
        }

        $existingSchedules = BillingSchedule::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->orderBy('id')
            ->get();

        $hasRecordedPayments = $existingSchedules->contains(function (BillingSchedule $billingSchedule): bool {
            return (float) $billingSchedule->amount_paid > 0
                || in_array($billingSchedule->status, ['paid', 'partially_paid'], true);
        });

        if ($hasRecordedPayments) {
            return;
        }

        $paymentTerm = $this->normalizePaymentTerm((string) $enrollment->payment_term);
        $remainingBalance = $this->resolveRemainingBalance($enrollment);

        if ($paymentTerm === 'cash' || $remainingBalance <= 0) {
            if ($existingSchedules->isNotEmpty()) {
                BillingSchedule::query()
                    ->where('student_id', $enrollment->student_id)
                    ->where('academic_year_id', $enrollment->academic_year_id)
                    ->delete();
            }

            return;
        }

        $scheduleRows = $this->buildScheduleRows($enrollment, $paymentTerm, $remainingBalance);

        BillingSchedule::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->delete();

        foreach ($scheduleRows as $scheduleRow) {
            BillingSchedule::query()->create([
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $enrollment->academic_year_id,
                'description' => $scheduleRow['description'],
                'due_date' => $scheduleRow['due_date'],
                'amount_due' => $scheduleRow['amount_due'],
                'amount_paid' => 0,
                'status' => 'unpaid',
            ]);
        }
    }

    private function normalizePaymentTerm(string $paymentTerm): string
    {
        if ($paymentTerm === 'full') {
            return 'cash';
        }

        return $paymentTerm;
    }

    private function resolveRemainingBalance(Enrollment $enrollment): float
    {
        if (! $enrollment->grade_level_id) {
            return 0;
        }

        $assessmentFeeTotal = $this->resolveAssessmentFeeTotal(
            (int) $enrollment->grade_level_id,
            (int) $enrollment->academic_year_id
        );
        $discountAmount = $this->resolveDiscountAmount($enrollment, $assessmentFeeTotal);
        $netAssessmentFeeTotal = max($assessmentFeeTotal - $discountAmount, 0);

        $downpayment = (float) $enrollment->downpayment;

        return round(max($netAssessmentFeeTotal - $downpayment, 0), 2);
    }

    private function resolveAssessmentFeeTotal(int $gradeLevelId, int $academicYearId): float
    {
        $baseQuery = Fee::query()
            ->where('grade_level_id', $gradeLevelId)
            ->whereIn('type', ['tuition', 'miscellaneous']);

        $hasVersionedRows = $academicYearId > 0
            ? (clone $baseQuery)
                ->where('academic_year_id', $academicYearId)
                ->exists()
            : false;

        if ($hasVersionedRows) {
            return round((float) (clone $baseQuery)
                ->where('academic_year_id', $academicYearId)
                ->sum('amount'), 2);
        }

        return round((float) (clone $baseQuery)
            ->whereNull('academic_year_id')
            ->sum('amount'), 2);
    }

    private function resolveDiscountAmount(Enrollment $enrollment, float $assessmentFeeTotal): float
    {
        if ($assessmentFeeTotal <= 0) {
            return 0;
        }

        $studentDiscounts = StudentDiscount::query()
            ->with('discount:id,type,value')
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->get();

        if ($studentDiscounts->isEmpty()) {
            return 0;
        }

        $totalDiscountAmount = $studentDiscounts->reduce(
            function (float $carry, StudentDiscount $studentDiscount) use ($assessmentFeeTotal): float {
                $discount = $studentDiscount->discount;

                if (! $discount) {
                    return $carry;
                }

                $discountAmount = match ($discount->type) {
                    'percentage' => round($assessmentFeeTotal * ((float) $discount->value / 100), 2),
                    'fixed' => round((float) $discount->value, 2),
                    default => 0.0,
                };

                return $carry + max($discountAmount, 0);
            },
            0.0
        );

        return round(min($assessmentFeeTotal, $totalDiscountAmount), 2);
    }

    /**
     * @return array<int, array{description: string, due_date: string, amount_due: float}>
     */
    private function buildScheduleRows(Enrollment $enrollment, string $paymentTerm, float $remainingBalance): array
    {
        $startDate = Carbon::parse((string) $enrollment->academicYear?->start_date);
        $endDate = Carbon::parse((string) $enrollment->academicYear?->end_date);

        $firstDueMonth = $startDate->copy()->startOfMonth()->addMonth();
        $finalSchoolMonth = $endDate->copy()->startOfMonth();

        $windowMonths = $this->buildWindowMonths($firstDueMonth, $finalSchoolMonth);
        $windowMonthCount = count($windowMonths);

        if ($windowMonthCount === 0) {
            return [];
        }

        $installmentCount = match ($paymentTerm) {
            'quarterly' => 4,
            'semi-annual' => 2,
            default => $windowMonthCount,
        };

        $installmentCount = min(max($installmentCount, 1), $windowMonthCount);

        $installmentAmounts = $this->splitAmountAcrossInstallments(
            $remainingBalance,
            $installmentCount
        );

        $dueMonths = $paymentTerm === 'monthly'
            ? $windowMonths
            : $this->selectAnchoredDueMonths($windowMonths, $installmentCount);

        $scheduleRows = [];

        for ($index = 0; $index < count($installmentAmounts); $index++) {
            $dueMonth = $dueMonths[$index];
            $sequence = $index + 1;

            $scheduleRows[] = [
                'description' => $this->buildDescription($paymentTerm, $sequence, $dueMonth),
                'due_date' => $dueMonth->toDateString(),
                'amount_due' => $installmentAmounts[$index],
            ];
        }

        return $scheduleRows;
    }

    /**
     * @return array<int, Carbon>
     */
    private function buildWindowMonths(Carbon $firstDueMonth, Carbon $finalSchoolMonth): array
    {
        if ($firstDueMonth->greaterThan($finalSchoolMonth)) {
            return [$firstDueMonth->copy()];
        }

        $windowMonths = [];
        $cursor = $firstDueMonth->copy();

        while ($cursor->lessThanOrEqualTo($finalSchoolMonth)) {
            $windowMonths[] = $cursor->copy();
            $cursor->addMonth();
        }

        return $windowMonths;
    }

    /**
     * @param  array<int, Carbon>  $windowMonths
     * @return array<int, Carbon>
     */
    private function selectAnchoredDueMonths(array $windowMonths, int $installmentCount): array
    {
        if ($installmentCount <= 1) {
            return [$windowMonths[0]];
        }

        $windowMonthCount = count($windowMonths);
        $selectedMonths = [];

        for ($index = 0; $index < $installmentCount; $index++) {
            $position = (int) round(
                $index * (($windowMonthCount - 1) / ($installmentCount - 1))
            );

            $selectedMonths[] = $windowMonths[$position];
        }

        return $selectedMonths;
    }

    /**
     * @return array<int, float>
     */
    private function splitAmountAcrossInstallments(float $amount, int $installmentCount): array
    {
        $totalCents = (int) round($amount * 100);
        $baseCents = intdiv($totalCents, $installmentCount);
        $remainderCents = $totalCents % $installmentCount;

        $splitAmounts = [];

        for ($index = 0; $index < $installmentCount; $index++) {
            $installmentCents = $baseCents + ($index < $remainderCents ? 1 : 0);
            $splitAmounts[] = round($installmentCents / 100, 2);
        }

        return $splitAmounts;
    }

    private function buildDescription(string $paymentTerm, int $sequence, Carbon $dueMonth): string
    {
        return match ($paymentTerm) {
            'monthly' => $dueMonth->format('F').' Installment',
            'quarterly' => "Quarterly Installment {$sequence}",
            'semi-annual' => "Semi-Annual Installment {$sequence}",
            default => "Installment {$sequence}",
        };
    }
}
