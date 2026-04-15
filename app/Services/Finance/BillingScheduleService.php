<?php

namespace App\Services\Finance;

use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use Illuminate\Support\Carbon;

class BillingScheduleService
{
    public function __construct(private DiscountBucketCalculator $discountBucketCalculator) {}

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
        $assessmentSummary = $this->resolveAssessmentSummary($enrollment);

        if (
            $paymentTerm === 'cash'
            || (
                $assessmentSummary['remaining_balance'] <= 0
                && $assessmentSummary['downpayment'] <= 0
            )
        ) {
            if ($existingSchedules->isNotEmpty()) {
                BillingSchedule::query()
                    ->where('student_id', $enrollment->student_id)
                    ->where('academic_year_id', $enrollment->academic_year_id)
                    ->delete();
            }

            return;
        }

        $scheduleRows = $this->buildScheduleRows(
            $enrollment,
            $paymentTerm,
            $assessmentSummary['remaining_balance'],
            $assessmentSummary['downpayment']
        );

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

    /**
     * @return array{
     *     assessment_fee_total: float,
     *     discount_amount: float,
     *     net_assessment_total: float,
     *     downpayment: float,
     *     remaining_balance: float
     * }
     */
    private function resolveAssessmentSummary(Enrollment $enrollment): array
    {
        if (! $enrollment->grade_level_id) {
            return [
                'assessment_fee_total' => 0.0,
                'discount_amount' => 0.0,
                'net_assessment_total' => 0.0,
                'downpayment' => 0.0,
                'remaining_balance' => 0.0,
            ];
        }

        $assessmentFeeTotal = $this->resolveAssessmentFeeTotal(
            (int) $enrollment->grade_level_id,
            (int) $enrollment->academic_year_id
        );
        $discountAmount = $this->resolveDiscountAmount($enrollment, $assessmentFeeTotal);
        $netAssessmentFeeTotal = max($assessmentFeeTotal - $discountAmount, 0);

        $downpayment = round(max((float) $enrollment->downpayment, 0), 2);
        $remainingBalance = round(max($netAssessmentFeeTotal - $downpayment, 0), 2);

        return [
            'assessment_fee_total' => $assessmentFeeTotal,
            'discount_amount' => $discountAmount,
            'net_assessment_total' => $netAssessmentFeeTotal,
            'downpayment' => $downpayment,
            'remaining_balance' => $remainingBalance,
        ];
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
            return 0.0;
        }

        return $this->discountBucketCalculator
            ->summarizeForStudent((int) $enrollment->student_id, (int) $enrollment->academic_year_id, $assessmentFeeTotal)['total_discount_amount'];
    }

    /**
     * @return array<int, array{description: string, due_date: string, amount_due: float}>
     */
    private function buildScheduleRows(
        Enrollment $enrollment,
        string $paymentTerm,
        float $remainingBalance,
        float $downpayment
    ): array {
        $startDate = Carbon::parse((string) $enrollment->academicYear?->start_date);
        $endDate = Carbon::parse((string) $enrollment->academicYear?->end_date);
        $normalizedDownpayment = round(max($downpayment, 0), 2);

        $firstDueMonth = $startDate->copy()->startOfMonth()->addMonth();
        $finalSchoolMonth = $endDate->copy()->startOfMonth();
        $scheduleRows = [];

        if ($normalizedDownpayment > 0) {
            $scheduleRows[] = [
                'description' => 'Upon Enrollment',
                'due_date' => $startDate->toDateString(),
                'amount_due' => $normalizedDownpayment,
            ];
        }

        if ($remainingBalance <= 0) {
            return $scheduleRows;
        }

        $windowMonths = $this->buildWindowMonths($firstDueMonth, $finalSchoolMonth);
        $windowMonthCount = count($windowMonths);

        if ($windowMonthCount === 0) {
            return $scheduleRows;
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
