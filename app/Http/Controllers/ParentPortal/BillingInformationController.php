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
use Inertia\Inertia;
use Inertia\Response;

class BillingInformationController extends Controller
{
    public function index(): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $enrollment = $student ? $this->resolveCurrentEnrollment($student) : null;
        $academicYear = $enrollment?->academicYear ?: $this->resolveActiveAcademicYear();

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
                ->orderBy('due_date')
                ->orderBy('id')
                ->get()
                ->map(function (BillingSchedule $billingSchedule) {
                    return [
                        'due_date' => $billingSchedule->due_date?->format('m/d/Y'),
                        'amount' => $this->formatCurrency((float) $billingSchedule->amount_due),
                        'status' => $billingSchedule->status === 'paid' ? 'Paid' : 'Unpaid',
                    ];
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

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
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
            ->where('status', 'enrolled')
            ->latest('id')
            ->first();
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
