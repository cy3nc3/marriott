<?php

namespace App\Services\Finance;

use App\Models\Discount;
use App\Models\StudentDiscount;

class DiscountBucketCalculator
{
    /**
     * @return array{bucket_totals: array<string, float>, total_discount_amount: float}
     */
    public function summarizeForStudent(int $studentId, int $academicYearId, float $assessmentFeeTotal): array
    {
        $bucketTotals = $this->emptyBucketTotals();
        $remainingAssessmentAmount = round(max($assessmentFeeTotal, 0), 2);

        if ($studentId <= 0 || $academicYearId <= 0 || $remainingAssessmentAmount <= 0) {
            return [
                'bucket_totals' => $bucketTotals,
                'total_discount_amount' => 0.0,
            ];
        }

        $studentDiscounts = StudentDiscount::query()
            ->with('discount:id,type,value,export_bucket')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->get();

        foreach ($studentDiscounts as $studentDiscount) {
            if ($remainingAssessmentAmount <= 0) {
                break;
            }

            $discount = $studentDiscount->discount;

            if (! $discount) {
                continue;
            }

            $discountAmount = $this->resolveDiscountAmount($discount->type, (float) $discount->value, $assessmentFeeTotal);
            $appliedAmount = round(min($remainingAssessmentAmount, max($discountAmount, 0)), 2);

            if ($appliedAmount <= 0) {
                continue;
            }

            $bucket = array_key_exists((string) $discount->export_bucket, $bucketTotals)
                ? (string) $discount->export_bucket
                : Discount::DEFAULT_EXPORT_BUCKET;

            $bucketTotals[$bucket] = round($bucketTotals[$bucket] + $appliedAmount, 2);
            $remainingAssessmentAmount = round(max($remainingAssessmentAmount - $appliedAmount, 0), 2);
        }

        return [
            'bucket_totals' => $bucketTotals,
            'total_discount_amount' => round($assessmentFeeTotal - $remainingAssessmentAmount, 2),
        ];
    }

    /**
     * @return array<string, float>
     */
    public function emptyBucketTotals(): array
    {
        return array_fill_keys(array_keys(Discount::exportBucketLabels()), 0.0);
    }

    private function resolveDiscountAmount(string $type, float $value, float $assessmentFeeTotal): float
    {
        return match ($type) {
            'percentage' => round($assessmentFeeTotal * ($value / 100), 2),
            'fixed' => round($value, 2),
            default => 0.0,
        };
    }
}
