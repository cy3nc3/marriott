<?php

namespace App\Services\SchoolForms;

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\StudentDiscount;
use App\Models\Transaction;
use App\Services\Finance\DiscountBucketCalculator;
use Illuminate\Support\Collection;

class EnrollmentExportBuilder
{
    public function __construct(private DiscountBucketCalculator $discountBucketCalculator) {}

    /**
     * @return array{school_year_label: string, as_of: string}
     */
    public function buildMetadata(AcademicYear $academicYear): array
    {
        return [
            'school_year_label' => $this->formatSchoolYearLabel((string) $academicYear->name),
            'as_of' => now()->toDateString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(AcademicYear $academicYear): array
    {
        $enrollments = Enrollment::query()
            ->with([
                'student:id,first_name,middle_name,last_name',
                'gradeLevel:id,name',
                'section:id,name',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['for_cashier_payment', 'enrolled'])
            ->get()
            ->sortBy(function (Enrollment $enrollment): string {
                return strtolower(trim("{$enrollment->student?->last_name} {$enrollment->student?->first_name}"));
            })
            ->values();

        if ($enrollments->isEmpty()) {
            return [];
        }

        $studentIds = $enrollments->pluck('student_id')->map(fn ($id): int => (int) $id)->all();
        $feeTotalsByGrade = $this->resolveFeeTotalsByGrade($enrollments, (int) $academicYear->id);
        $latestTransactionsByStudent = Transaction::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotIn('status', ['voided', 'refunded', 'reissued'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'or_number', 'payment_mode', 'total_amount', 'created_at', 'remarks'])
            ->groupBy('student_id')
            ->map(fn (Collection $transactions) => $transactions->first());

        $billingSummaryByStudent = BillingSchedule::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYear->id)
            ->get(['student_id', 'amount_due', 'amount_paid'])
            ->groupBy('student_id')
            ->map(function (Collection $schedules): array {
                $totalDue = round((float) $schedules->sum('amount_due'), 2);
                $totalPaid = round((float) $schedules->sum('amount_paid'), 2);

                return [
                    'balance' => round(max($totalDue - $totalPaid, 0), 2),
                    'overpayment' => round(max($totalPaid - $totalDue, 0), 2),
                ];
            });

        $discountNamesByStudent = StudentDiscount::query()
            ->with('discount:id,name')
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYear->id)
            ->get(['student_id', 'discount_id'])
            ->groupBy('student_id')
            ->map(function (Collection $studentDiscounts): string {
                return $studentDiscounts
                    ->map(fn (StudentDiscount $studentDiscount): ?string => $studentDiscount->discount?->name)
                    ->filter()
                    ->implode(', ');
            });

        $existingStudentEnrollmentCounts = Enrollment::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', '!=', $academicYear->id)
            ->selectRaw('student_id, COUNT(*) as aggregate')
            ->groupBy('student_id')
            ->pluck('aggregate', 'student_id');

        return $enrollments
            ->map(function (Enrollment $enrollment) use (
                $academicYear,
                $feeTotalsByGrade,
                $latestTransactionsByStudent,
                $billingSummaryByStudent,
                $discountNamesByStudent,
                $existingStudentEnrollmentCounts
            ): array {
                $studentId = (int) $enrollment->student_id;
                $feeTotals = $feeTotalsByGrade->get((int) $enrollment->grade_level_id, [
                    'misc' => 0.0,
                    'tuition' => 0.0,
                ]);
                $assessmentFeeTotal = round((float) $feeTotals['misc'] + (float) $feeTotals['tuition'], 2);
                $discountSummary = $this->discountBucketCalculator->summarizeForStudent(
                    $studentId,
                    (int) $academicYear->id,
                    $assessmentFeeTotal
                );
                $latestTransaction = $latestTransactionsByStudent->get($studentId);
                $billingSummary = $billingSummaryByStudent->get($studentId, [
                    'balance' => 0.0,
                    'overpayment' => 0.0,
                ]);
                $oldNewStatus = ((int) ($existingStudentEnrollmentCounts[$studentId] ?? 0)) > 0 ? 'O' : 'N';

                return [
                    'name' => trim("{$enrollment->student?->last_name}, {$enrollment->student?->first_name}"),
                    'grade_level' => $enrollment->gradeLevel?->name ?? '',
                    'section' => $enrollment->section?->name ?? '',
                    'or_number' => $latestTransaction?->or_number ?? '',
                    'date' => $latestTransaction?->created_at?->toDateString() ?? $enrollment->created_at?->toDateString() ?? now()->toDateString(),
                    'total' => $assessmentFeeTotal,
                    'misc' => (float) $feeTotals['misc'],
                    'misc_discount' => $discountSummary['bucket_totals']['misc_discount'] ?? 0.0,
                    'misc_sibling_discount' => $discountSummary['bucket_totals']['misc_sibling_discount'] ?? 0.0,
                    'misc_mode' => $latestTransaction?->payment_mode ? ucfirst((string) $latestTransaction->payment_mode) : '',
                    'tuition' => (float) $feeTotals['tuition'],
                    'tuition_sibling_discount' => $discountSummary['bucket_totals']['tuition_sibling_discount'] ?? 0.0,
                    'tuition_mode' => $this->formatPaymentPlan((string) $enrollment->payment_term),
                    'payment_plan' => $this->paymentPlanCode((string) $enrollment->payment_term),
                    'early_enrollment_discount' => $discountSummary['bucket_totals']['early_enrollment_discount'] ?? 0.0,
                    'fape' => $discountSummary['bucket_totals']['fape'] ?? 0.0,
                    'fape_previous_year' => $discountSummary['bucket_totals']['fape_previous_year'] ?? 0.0,
                    'overall_discount' => $discountSummary['bucket_totals']['overall_discount'] ?? 0.0,
                    'special_discount' => $discountSummary['bucket_totals'][Discount::DEFAULT_EXPORT_BUCKET] ?? 0.0,
                    'balance' => (float) $billingSummary['balance'],
                    'overpayment' => (float) $billingSummary['overpayment'],
                    'reservation_status' => $enrollment->status === 'enrolled' ? 'E' : 'R',
                    'old_new_status' => $oldNewStatus,
                    'remarks' => $discountNamesByStudent->get($studentId, $latestTransaction?->remarks ?? ''),
                ];
            })
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Enrollment>  $enrollments
     * @return \Illuminate\Support\Collection<int, array{misc: float, tuition: float}>
     */
    private function resolveFeeTotalsByGrade(Collection $enrollments, int $academicYearId): Collection
    {
        return $enrollments
            ->pluck('grade_level_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->mapWithKeys(function (int $gradeLevelId) use ($academicYearId): array {
                $fees = Fee::query()
                    ->where('grade_level_id', $gradeLevelId)
                    ->whereIn('type', ['miscellaneous', 'tuition'])
                    ->when(
                        Fee::query()
                            ->where('grade_level_id', $gradeLevelId)
                            ->where('academic_year_id', $academicYearId)
                            ->exists(),
                        fn ($query) => $query->where('academic_year_id', $academicYearId),
                        fn ($query) => $query->whereNull('academic_year_id')
                    )
                    ->get(['type', 'amount']);

                return [
                    $gradeLevelId => [
                        'misc' => round((float) $fees->where('type', 'miscellaneous')->sum('amount'), 2),
                        'tuition' => round((float) $fees->where('type', 'tuition')->sum('amount'), 2),
                    ],
                ];
            });
    }

    private function formatSchoolYearLabel(string $schoolYearName): string
    {
        if (preg_match('/(\d{4})\D+(\d{4})/', $schoolYearName, $matches) === 1) {
            return sprintf('SY %s-%s', substr($matches[1], -2), substr($matches[2], -2));
        }

        return $schoolYearName;
    }

    private function paymentPlanCode(string $paymentTerm): string
    {
        return match (strtolower(trim($paymentTerm))) {
            'quarterly' => 'Q',
            'monthly' => 'M',
            'semi-annual', 'semiannual', 'semestral' => 'S',
            default => 'A',
        };
    }

    private function formatPaymentPlan(string $paymentTerm): string
    {
        return match (strtolower(trim($paymentTerm))) {
            'quarterly' => 'Quarterly',
            'monthly' => 'Monthly',
            'semi-annual', 'semiannual', 'semestral' => 'Semi-Annual',
            default => 'Annual',
        };
    }
}
