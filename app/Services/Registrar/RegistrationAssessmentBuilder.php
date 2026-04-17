<?php

namespace App\Services\Registrar;

use App\Enums\UserRole;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\User;
use Illuminate\Support\Collection;

class RegistrationAssessmentBuilder
{
    /**
     * @param  array{
     *     student_activation_code?: string|null,
     *     parent_activation_code?: string|null
     * }  $credentialSnapshot
     * @return array<string, mixed>
     */
    public function build(Enrollment $enrollment, array $credentialSnapshot = []): array
    {
        $enrollment->loadMissing([
            'student.user.activationCode',
            'gradeLevel:id,name',
            'academicYear:id,name',
            'section:id,name,adviser_id,grade_level_id',
            'section.adviser:id,first_name,last_name,name',
        ]);

        $student = $enrollment->student;
        $studentUser = $student?->user;
        $parentUser = $student
            ? User::query()
                ->with('activationCode')
                ->where('role', UserRole::PARENT->value)
                ->whereHas('students', function ($query) use ($student): void {
                    $query->where('students.id', $student->id);
                })
                ->orderBy('id')
                ->first()
            : null;

        $fees = $this->resolveAssessmentFees(
            (int) $enrollment->grade_level_id,
            (int) $enrollment->academic_year_id
        );

        $billingSchedule = BillingSchedule::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get(['description', 'due_date', 'amount_due', 'amount_paid', 'status'])
            ->map(function (BillingSchedule $billingSchedule): array {
                $amountDue = round((float) $billingSchedule->amount_due, 2);
                $amountPaid = round((float) $billingSchedule->amount_paid, 2);

                return [
                    'description' => (string) $billingSchedule->description,
                    'due_date' => $billingSchedule->due_date?->toDateString(),
                    'due_date_label' => $billingSchedule->due_date?->format('M d, Y'),
                    'amount_due' => $amountDue,
                    'amount_paid' => $amountPaid,
                    'balance' => round(max($amountDue - $amountPaid, 0), 2),
                    'status' => $this->formatDueStatus((string) $billingSchedule->status),
                ];
            })
            ->values();

        $studentActivationCode = isset($credentialSnapshot['student_activation_code'])
            ? (string) $credentialSnapshot['student_activation_code']
            : null;
        $parentActivationCode = isset($credentialSnapshot['parent_activation_code'])
            ? (string) $credentialSnapshot['parent_activation_code']
            : null;

        return [
            'generated_at' => now()->toDateTimeString(),
            'student' => [
                'lrn' => (string) ($student?->lrn ?? ''),
                'name' => $this->formatStudentName(
                    (string) ($student?->first_name ?? ''),
                    $student?->middle_name,
                    (string) ($student?->last_name ?? ''),
                ),
            ],
            'enrollment' => [
                'school_year' => (string) ($enrollment->academicYear?->name ?? ''),
                'grade_level' => (string) ($enrollment->gradeLevel?->name ?? ''),
                'section' => (string) ($enrollment->section?->name ?? 'Unassigned'),
                'adviser' => $this->formatAdviserName(
                    $enrollment->section?->adviser?->first_name,
                    $enrollment->section?->adviser?->last_name,
                    $enrollment->section?->adviser?->name,
                ),
                'payment_plan' => $this->formatPaymentPlan((string) $enrollment->payment_term),
                'downpayment' => round((float) $enrollment->downpayment, 2),
            ],
            'assessment' => [
                'tuition' => round((float) $fees['tuition'], 2),
                'miscellaneous' => round((float) $fees['miscellaneous'], 2),
                'total' => round((float) $fees['tuition'] + (float) $fees['miscellaneous'], 2),
            ],
            'dues' => [
                'rows' => $billingSchedule->all(),
                'total_due' => round((float) $billingSchedule->sum('amount_due'), 2),
                'total_paid' => round((float) $billingSchedule->sum('amount_paid'), 2),
                'balance' => round((float) $billingSchedule->sum('balance'), 2),
            ],
            'accounts' => [
                'student' => [
                    'email' => (string) ($studentUser?->email ?? ''),
                    'activation_code' => $studentActivationCode,
                    'activation_expires_at' => $studentUser?->activationCode?->expires_at?->toDateTimeString(),
                ],
                'parent' => [
                    'email' => (string) ($parentUser?->email ?? ''),
                    'activation_code' => $parentActivationCode,
                    'activation_expires_at' => $parentUser?->activationCode?->expires_at?->toDateTimeString(),
                ],
            ],
        ];
    }

    /**
     * @return array{tuition: float, miscellaneous: float}
     */
    private function resolveAssessmentFees(int $gradeLevelId, int $academicYearId): array
    {
        if ($gradeLevelId <= 0) {
            return [
                'tuition' => 0.0,
                'miscellaneous' => 0.0,
            ];
        }

        $baseQuery = Fee::query()
            ->where('grade_level_id', $gradeLevelId)
            ->whereIn('type', ['tuition', 'miscellaneous']);

        $hasVersionedRows = $academicYearId > 0
            ? (clone $baseQuery)
                ->where('academic_year_id', $academicYearId)
                ->exists()
            : false;

        /** @var Collection<int, Fee> $fees */
        $fees = $hasVersionedRows
            ? (clone $baseQuery)
                ->where('academic_year_id', $academicYearId)
                ->get(['type', 'amount'])
            : (clone $baseQuery)
                ->whereNull('academic_year_id')
                ->get(['type', 'amount']);

        return [
            'tuition' => round((float) $fees->where('type', 'tuition')->sum('amount'), 2),
            'miscellaneous' => round((float) $fees->where('type', 'miscellaneous')->sum('amount'), 2),
        ];
    }

    private function formatPaymentPlan(string $paymentPlan): string
    {
        return match ($paymentPlan) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi-annual' => 'Semi-Annual',
            'cash', 'full' => 'Cash',
            default => ucwords(str_replace('_', ' ', $paymentPlan)),
        };
    }

    private function formatDueStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'partially_paid' => 'Partially Paid',
            'unpaid' => 'Unpaid',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    private function formatStudentName(string $firstName, ?string $middleName, string $lastName): string
    {
        return trim(implode(' ', array_filter([
            trim($firstName),
            trim((string) $middleName),
            trim($lastName),
        ])));
    }

    private function formatAdviserName(?string $firstName, ?string $lastName, ?string $fallbackName): string
    {
        $name = trim(implode(' ', array_filter([
            trim((string) $firstName),
            trim((string) $lastName),
        ])));

        if ($name !== '') {
            return $name;
        }

        $fallback = trim((string) $fallbackName);

        return $fallback !== '' ? $fallback : 'TBA';
    }
}
