<?php

namespace App\Services\Registrar;

use App\Enums\UserRole;
use App\Models\BillingSchedule;
use App\Models\ClassSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\LedgerEntry;
use App\Models\StudentDiscount;
use App\Models\User;
use App\Services\Finance\DiscountBucketCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RegistrationAssessmentBuilder
{
    public function __construct(private DiscountBucketCalculator $discountBucketCalculator) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Enrollment $enrollment): array
    {
        $enrollment->loadMissing([
            'student.user',
            'gradeLevel:id,name',
            'academicYear:id,name',
            'section:id,name,adviser_id,grade_level_id',
            'section.adviser:id,first_name,last_name,name',
        ]);

        $student = $enrollment->student;
        $studentUser = $student?->user;
        $parentUser = $student
            ? User::query()
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
        $academicSchedule = $this->resolveAcademicSchedule((int) ($enrollment->section_id ?? 0));
        $adjustments = $this->resolveAdjustments($enrollment, (float) $fees['total']);
        $netAssessment = round(
            max(
                (float) $fees['total']
                - (float) $adjustments['discounts_scholarships']
                + (float) $adjustments['other_charges']
                - (float) $adjustments['credit_adjustment'],
                0
            ),
            2
        );

        $billingSchedule = BillingSchedule::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get(['description', 'due_date', 'amount_due', 'amount_paid'])
            ->map(function (BillingSchedule $billingSchedule): array {
                $amountDue = round((float) $billingSchedule->amount_due, 2);
                $amountPaid = round((float) $billingSchedule->amount_paid, 2);
                $isUponEnrollment = $this->isUponEnrollmentSchedule((string) $billingSchedule->description);

                return [
                    'description' => (string) $billingSchedule->description,
                    'due_date' => $billingSchedule->due_date?->toDateString(),
                    'due_date_label' => $isUponEnrollment
                        ? 'Upon Enrollment'
                        : ($billingSchedule->due_date?->format('M d, Y') ?? 'N/A'),
                    'amount_due' => $amountDue,
                    'amount_paid' => $amountPaid,
                    'balance' => round(max($amountDue - $amountPaid, 0), 2),
                    'is_upon_enrollment' => $isUponEnrollment,
                ];
            })
            ->values();

        $downpaymentAmount = round((float) $billingSchedule->filter(fn ($due) => $due['is_upon_enrollment'])->sum('amount_due'), 2);
        $duesRows = $billingSchedule
            ->map(function (array $due): array {
                if (! $due['is_upon_enrollment']) {
                    return $due;
                }

                $due['amount_due'] = round((float) $due['amount_due'] * -1, 2);
                $due['balance'] = 0.0;

                return $due;
            })
            ->values();

        return [
            'generated_at' => now()->toDateTimeString(),
            'student' => [
                'lrn' => (string) ($student?->lrn ?? ''),
                'first_name' => (string) ($student?->first_name ?? ''),
                'middle_name' => (string) ($student?->middle_name ?? ''),
                'last_name' => (string) ($student?->last_name ?? ''),
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
                'email' => (string) ($enrollment->email ?? ''),
            ],
            'academic' => [
                'schedule_rows' => $academicSchedule,
                'schedule_compact_rows' => $this->compactScheduleRows($academicSchedule),
            ],
            'assessment' => [
                'tuition' => round((float) $fees['tuition'], 2),
                'miscellaneous_other_total' => round((float) $fees['miscellaneous_other_total'], 2),
                'breakdown' => $fees['breakdown'],
                'total' => round((float) $fees['total'], 2),
                'adjustments' => $adjustments,
                'downpayment' => $downpaymentAmount,
                'net_assessment' => $netAssessment,
            ],
            'dues' => [
                'rows' => $duesRows->all(),
                'total_due' => round((float) $duesRows->sum(function (array $due): float {
                    $amountDue = (float) ($due['amount_due'] ?? 0);

                    return $amountDue > 0 ? $amountDue : 0.0;
                }), 2),
                'total_paid' => round((float) $duesRows->sum('amount_paid'), 2),
                'balance' => round((float) $duesRows->sum('balance'), 2),
            ],
            'accounts' => [
                'student' => [
                    'email' => (string) ($studentUser?->email ?? ''),
                ],
                'parent' => [
                    'email' => (string) ($parentUser?->email ?? ''),
                ],
            ],
        ];
    }

    private function isUponEnrollmentSchedule(string $description): bool
    {
        $normalized = Str::of($description)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return false;
        }

        return Str::contains($normalized, [
            'upon enrollment',
            'upon enrolment',
            'downpayment',
            'down payment',
            'enrollment payment',
            'enrolment payment',
        ]);
    }

    /**
     * @return array{
     *     tuition: float,
     *     miscellaneous_other_total: float,
     *     total: float,
     *     breakdown: array<int, array{name: string, amount: float}>
     * }
     */
    private function resolveAssessmentFees(int $gradeLevelId, int $academicYearId): array
    {
        if ($gradeLevelId <= 0) {
            return [
                'tuition' => 0.0,
                'miscellaneous_other_total' => 0.0,
                'total' => 0.0,
                'breakdown' => [],
            ];
        }

        $baseQuery = Fee::query()
            ->where('grade_level_id', $gradeLevelId)
            ->whereIn('type', ['tuition', 'miscellaneous', 'books_modules', 'other']);

        $hasVersionedRows = $academicYearId > 0
            ? (clone $baseQuery)
                ->where('academic_year_id', $academicYearId)
                ->exists()
            : false;

        /** @var Collection<int, Fee> $fees */
        $fees = $hasVersionedRows
            ? (clone $baseQuery)
                ->where('academic_year_id', $academicYearId)
                ->get(['type', 'name', 'amount'])
            : (clone $baseQuery)
                ->whereNull('academic_year_id')
                ->get(['type', 'name', 'amount']);

        $breakdownRows = $fees
            ->where('type', '!=', 'tuition')
            ->values()
            ->map(function (Fee $fee): array {
                return [
                    'name' => (string) $fee->name,
                    'amount' => round((float) $fee->amount, 2),
                ];
            })
            ->all();

        $tuition = round((float) $fees->where('type', 'tuition')->sum('amount'), 2);
        $miscellaneousAndOther = round((float) $fees->where('type', '!=', 'tuition')->sum('amount'), 2);

        return [
            'tuition' => $tuition,
            'miscellaneous_other_total' => $miscellaneousAndOther,
            'total' => round($tuition + $miscellaneousAndOther, 2),
            'breakdown' => $breakdownRows,
        ];
    }

    /**
     * @return array<int, array{day: string, time: string, subject: string, teacher: string}>
     */
    private function resolveAcademicSchedule(int $sectionId): array
    {
        if ($sectionId <= 0) {
            return [];
        }

        $dayOrder = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];

        return ClassSchedule::query()
            ->with([
                'subjectAssignment.teacherSubject.subject:id,subject_name',
                'subjectAssignment.teacherSubject.teacher:id,first_name,last_name,name',
            ])
            ->where('section_id', $sectionId)
            ->where('type', 'academic')
            ->get()
            ->sort(function (ClassSchedule $left, ClassSchedule $right) use ($dayOrder): int {
                $leftDay = $dayOrder[(string) $left->day] ?? 99;
                $rightDay = $dayOrder[(string) $right->day] ?? 99;

                if ($leftDay !== $rightDay) {
                    return $leftDay <=> $rightDay;
                }

                return strcmp((string) $left->start_time, (string) $right->start_time);
            })
            ->values()
            ->map(function (ClassSchedule $schedule): array {
                $subjectName = (string) ($schedule->subjectAssignment?->teacherSubject?->subject?->subject_name ?? 'Subject');
                $teacher = $this->formatAdviserName(
                    $schedule->subjectAssignment?->teacherSubject?->teacher?->first_name,
                    $schedule->subjectAssignment?->teacherSubject?->teacher?->last_name,
                    $schedule->subjectAssignment?->teacherSubject?->teacher?->name
                );

                return [
                    'day' => (string) $schedule->day,
                    'time' => $this->formatTimeRange((string) $schedule->start_time, (string) $schedule->end_time),
                    'subject' => $subjectName,
                    'teacher' => $teacher,
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *     discounts_scholarships: float,
     *     applied_discounts: array<int, array{name: string, amount: float}>,
     *     other_charges: float,
     *     credit_adjustment: float
     * }
     */
    private function resolveAdjustments(Enrollment $enrollment, float $assessmentTotal): array
    {
        $discountSummary = $this->discountBucketCalculator->summarizeForStudent(
            (int) $enrollment->student_id,
            (int) $enrollment->academic_year_id,
            $assessmentTotal
        );
        $discountTotal = round((float) ($discountSummary['total_discount_amount'] ?? 0), 2);
        $appliedDiscounts = $discountSummary['applied_discounts'] ?? [];

        $otherCharges = round((float) LedgerEntry::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('debit', '>', 0)
            ->where(function ($query): void {
                $query
                    ->whereRaw('LOWER(description) like ?', ['%charge%'])
                    ->orWhereRaw('LOWER(description) like ?', ['%other%']);
            })
            ->sum('debit'), 2);

        $creditAdjustment = round((float) LedgerEntry::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('credit', '>', 0)
            ->whereRaw('LOWER(description) like ?', ['%adjustment%'])
            ->sum('credit'), 2);

        return [
            'discounts_scholarships' => $discountTotal,
            'applied_discounts' => $appliedDiscounts,
            'other_charges' => $otherCharges,
            'credit_adjustment' => $creditAdjustment,
        ];
    }

    private function formatTimeRange(string $startTime, string $endTime): string
    {
        if ($startTime === '' || $endTime === '') {
            return 'N/A';
        }

        return sprintf(
            '%s - %s',
            date('g:i A', strtotime($startTime)),
            date('g:i A', strtotime($endTime))
        );
    }

    /**
     * @param  array<int, array{day: string, time: string, subject: string, teacher: string}>  $rows
     * @return array<int, array{subject: string, teacher: string, day: string, time: string}>
     */
    private function compactScheduleRows(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $subject = trim((string) ($row['subject'] ?? ''));
            $teacher = trim((string) ($row['teacher'] ?? ''));
            $day = trim((string) ($row['day'] ?? ''));
            $time = trim((string) ($row['time'] ?? ''));

            if ($subject === '' || $teacher === '' || $day === '' || $time === '') {
                continue;
            }

            $key = mb_strtolower($subject.'|'.$teacher);
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'subject' => $subject,
                    'teacher' => $teacher,
                    'slots' => [],
                ];
            }

            $grouped[$key]['slots'][] = [
                'day' => $day,
                'time' => $time,
            ];
        }

        return collect($grouped)
            ->flatMap(function (array $entry): array {
                $slotsByTime = [];
                foreach ($entry['slots'] as $slot) {
                    $time = (string) ($slot['time'] ?? '');
                    $day = (string) ($slot['day'] ?? '');
                    if ($time === '' || $day === '') {
                        continue;
                    }
                    $slotsByTime[$time] ??= [];
                    $slotsByTime[$time][] = $day;
                }

                $resultRows = [];
                foreach ($slotsByTime as $time => $days) {
                    $resultRows[] = [
                        'subject' => $entry['subject'],
                        'teacher' => $entry['teacher'],
                        'day' => $this->compactDayLabel($days),
                        'time' => $time,
                    ];
                }

                return $resultRows;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $days
     */
    private function compactDayLabel(array $days): string
    {
        $orderedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $tokens = [
            'Monday' => 'M',
            'Tuesday' => 'T',
            'Wednesday' => 'W',
            'Thursday' => 'TH',
            'Friday' => 'F',
            'Saturday' => 'SAT',
            'Sunday' => 'SUN',
        ];

        $uniqueDays = collect($days)
            ->map(fn (string $day): string => trim($day))
            ->filter(fn (string $day): bool => in_array($day, $orderedDays, true))
            ->unique()
            ->values();

        if ($uniqueDays->isEmpty()) {
            return 'N/A';
        }

        $isWeekdayOnly = $uniqueDays->every(fn (string $day): bool => in_array($day, array_slice($orderedDays, 0, 5), true));
        if ($isWeekdayOnly) {
            $weekdayToken = '';
            foreach (array_slice($orderedDays, 0, 5) as $day) {
                if ($uniqueDays->contains($day)) {
                    $weekdayToken .= $tokens[$day];
                }
            }

            return $weekdayToken;
        }

        return $uniqueDays
            ->map(fn (string $day): string => $tokens[$day] ?? $day)
            ->implode('/');
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
