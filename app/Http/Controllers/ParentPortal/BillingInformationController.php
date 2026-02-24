<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BillingInformationController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $schoolYearOptions = $student
            ? $this->resolveSchoolYearOptions($student)
            : collect();
        $selectedSchoolYearId = $this->resolveSelectedSchoolYearId(
            $schoolYearOptions,
            $request->integer('academic_year_id')
        );
        $enrollment = $student
            ? $this->resolveCurrentEnrollment($student, $selectedSchoolYearId)
            : null;
        $academicYear = $enrollment?->academicYear ?: $this->resolveActiveAcademicYear();
        $isDepartedReadOnly = $enrollment
            ? in_array($enrollment->status, ['transferred_out', 'dropped_out', 'dropped'], true)
            : false;

        $defaultPlan = $this->normalizePlanKey($enrollment?->payment_term);

        $duesByPlan = [
            'monthly' => [],
            'quarterly' => [],
            'semi-annual' => [],
            'cash' => [],
        ];
        $recentPayments = collect();
        $outstandingBalance = 0.0;

        if ($student) {
            $ledgerQuery = LedgerEntry::query()
                ->where('student_id', $student->id)
                ->when($academicYear, function ($query) use ($academicYear) {
                    $query->where('academic_year_id', $academicYear->id);
                });

            $totalCharges = (float) (clone $ledgerQuery)->sum('debit');
            $totalCredits = (float) (clone $ledgerQuery)->sum('credit');
            $outstandingBalance = round($totalCharges - $totalCredits, 2);

            $dues = BillingSchedule::query()
                ->where('student_id', $student->id)
                ->when($academicYear, function ($query) use ($academicYear) {
                    $query->where('academic_year_id', $academicYear->id);
                })
                ->where(function ($query): void {
                    $query
                        ->whereColumn('amount_paid', '<', 'amount_due')
                        ->orWhereIn('status', ['unpaid', 'partially_paid']);
                })
                ->orderBy('due_date')
                ->orderBy('id')
                ->get()
                ->map(function (BillingSchedule $billingSchedule) {
                    $status = 'Unpaid';

                    if ((float) $billingSchedule->amount_paid >= (float) $billingSchedule->amount_due) {
                        $status = 'Paid';
                    } elseif ((float) $billingSchedule->amount_paid > 0) {
                        $status = 'Partially Paid';
                    }

                    $outstanding = max(
                        (float) $billingSchedule->amount_due - (float) $billingSchedule->amount_paid,
                        0
                    );

                    return [
                        'due_date' => $billingSchedule->due_date?->format('m/d/Y'),
                        'amount' => $this->formatCurrency((float) $billingSchedule->amount_due),
                        'outstanding_amount' => $this->formatCurrency($outstanding),
                        'status' => $status,
                    ];
                })
                ->reject(function (array $dueRow): bool {
                    return $dueRow['status'] === 'Paid';
                })
                ->values()
                ->all();

            $duesByPlan[$defaultPlan] = $dues;

            $recentPayments = Transaction::query()
                ->where('student_id', $student->id)
                ->latest('created_at')
                ->limit(20)
                ->get()
                ->map(function (Transaction $transaction) {
                    return [
                        'date' => $transaction->created_at?->format('m/d/Y'),
                        'or_number' => $transaction->or_number,
                        'payment_mode' => $this->formatPaymentMode($transaction->payment_mode),
                        'amount' => $this->formatCurrency((float) $transaction->total_amount),
                        'status' => 'Posted',
                    ];
                })
                ->values();
        }

        return Inertia::render('parent/billing-information/index', [
            'account_summary' => [
                'student_name' => $student ? trim("{$student->first_name} {$student->last_name}") : '-',
                'lrn' => $student?->lrn ?: '-',
                'payment_plan' => $defaultPlan,
                'payment_plan_label' => $this->formatPlanLabel($defaultPlan),
                'outstanding_balance' => $this->formatCurrency($outstandingBalance),
            ],
            'dues_by_plan' => $duesByPlan,
            'default_plan' => $defaultPlan,
            'recent_payments' => $recentPayments,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedSchoolYearId,
            'is_departed_read_only' => $isDepartedReadOnly,
        ]);
    }

    private function resolveStudent(?User $user): ?Student
    {
        if (! $user) {
            return null;
        }

        return $user->students()
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->first();
    }

    private function resolveCurrentEnrollment(Student $student, ?int $academicYearId = null): ?Enrollment
    {
        if ($academicYearId) {
            $selectedEnrollment = Enrollment::query()
                ->with('academicYear:id,name,status')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->whereIn('status', ['enrolled', 'transferred_out', 'dropped_out', 'dropped'])
                ->latest('id')
                ->first();

            if ($selectedEnrollment) {
                return $selectedEnrollment;
            }
        }

        $activeYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeYearId) {
            $activeEnrollment = Enrollment::query()
                ->with('academicYear:id,name,status')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'enrolled')
                ->first();

            if ($activeEnrollment) {
                return $activeEnrollment;
            }
        }

        return Enrollment::query()
            ->with('academicYear:id,name,status')
            ->where('student_id', $student->id)
            ->whereIn('status', ['enrolled', 'transferred_out', 'dropped_out', 'dropped'])
            ->latest('id')
            ->first();
    }

    private function resolveSchoolYearOptions(Student $student): Collection
    {
        return AcademicYear::query()
            ->select(['academic_years.id', 'academic_years.name', 'academic_years.status', 'academic_years.start_date'])
            ->join('enrollments', 'enrollments.academic_year_id', '=', 'academic_years.id')
            ->where('enrollments.student_id', $student->id)
            ->whereIn('enrollments.status', ['enrolled', 'transferred_out', 'dropped_out', 'dropped'])
            ->distinct()
            ->orderByDesc('academic_years.start_date')
            ->get()
            ->map(function (AcademicYear $academicYear): array {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();
    }

    private function resolveSelectedSchoolYearId(Collection $schoolYearOptions, ?int $requestedSchoolYearId): ?int
    {
        if ($requestedSchoolYearId && $schoolYearOptions->pluck('id')->contains($requestedSchoolYearId)) {
            return $requestedSchoolYearId;
        }

        $ongoingOption = $schoolYearOptions->firstWhere('status', 'ongoing');
        if ($ongoingOption) {
            return (int) $ongoingOption['id'];
        }

        return $schoolYearOptions->first()['id'] ?? null;
    }

    private function resolveActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->latest('start_date')->first();
    }

    private function normalizePlanKey(?string $paymentPlan): string
    {
        if (! $paymentPlan || ! in_array($paymentPlan, ['monthly', 'quarterly', 'semi-annual', 'cash'], true)) {
            return 'monthly';
        }

        return $paymentPlan;
    }

    private function formatPlanLabel(string $paymentPlan): string
    {
        return match ($paymentPlan) {
            'semi-annual' => 'Semi-Annual',
            'cash' => 'Cash',
            default => ucfirst($paymentPlan),
        };
    }

    private function formatPaymentMode(?string $paymentMode): string
    {
        if (! $paymentMode) {
            return '-';
        }

        return strtoupper($paymentMode) === 'GCASH'
            ? 'GCash'
            : ucfirst($paymentMode);
    }

    private function formatCurrency(float $value): string
    {
        return 'PHP '.number_format($value, 2);
    }
}
