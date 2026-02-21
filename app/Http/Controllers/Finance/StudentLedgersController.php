<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexStudentLedgersRequest;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class StudentLedgersController extends Controller
{
    public function index(IndexStudentLedgersRequest $request): Response
    {
        $validated = $request->validated();

        $search = trim((string) ($validated['search'] ?? ''));
        $entryType = $validated['entry_type'] ?? 'all';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $showPaidDues = $request->boolean('show_paid_dues');

        $students = Student::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id', 'lrn', 'first_name', 'last_name'])
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'lrn' => $student->lrn,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                ];
            })
            ->values();

        $selectedStudentId = (int) ($validated['student_id'] ?? 0);
        if (! $selectedStudentId && $students->count() === 1) {
            $selectedStudentId = (int) $students->first()['id'];
        }

        $selectedStudentPayload = null;
        $duesSchedule = collect();
        $ledgerEntries = collect();
        $summary = [
            'total_charges' => 0.0,
            'total_payments' => 0.0,
            'outstanding_balance' => 0.0,
        ];

        if ($selectedStudentId > 0) {
            $selectedStudent = Student::query()
                ->with(['enrollments' => function ($query) {
                    $query
                        ->with([
                            'gradeLevel:id,name',
                            'section:id,name',
                            'academicYear:id,name,status,start_date,end_date',
                        ])
                        ->latest('id');
                }])
                ->find($selectedStudentId);

            if ($selectedStudent) {
                $selectedEnrollment = $this->resolveCurrentEnrollment($selectedStudent);
                $selectedAcademicYear = $selectedEnrollment?->academicYear ?? $this->resolveActiveAcademicYear();

                $overallLedgerQuery = LedgerEntry::query()
                    ->where('student_id', $selectedStudent->id)
                    ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                        $query->where('academic_year_id', $selectedAcademicYear->id);
                    });

                $overallCharges = round((float) (clone $overallLedgerQuery)->sum('debit'), 2);
                $overallPayments = round((float) (clone $overallLedgerQuery)->sum('credit'), 2);
                $outstandingBalance = round($overallCharges - $overallPayments, 2);

                $gradeAndSection = 'Unassigned';
                if ($selectedEnrollment?->gradeLevel?->name && $selectedEnrollment?->section?->name) {
                    $gradeAndSection = "{$selectedEnrollment->gradeLevel->name} - {$selectedEnrollment->section->name}";
                } elseif ($selectedEnrollment?->gradeLevel?->name) {
                    $gradeAndSection = $selectedEnrollment->gradeLevel->name;
                }

                $selectedStudentPayload = [
                    'id' => $selectedStudent->id,
                    'name' => trim("{$selectedStudent->first_name} {$selectedStudent->last_name}"),
                    'lrn' => $selectedStudent->lrn,
                    'grade_and_section' => $gradeAndSection,
                    'guardian_name' => $selectedStudent->guardian_name,
                    'payment_plan' => $selectedEnrollment?->payment_term,
                    'payment_plan_label' => $this->formatPaymentPlan($selectedEnrollment?->payment_term),
                    'outstanding_balance' => $outstandingBalance,
                ];

                $duesSchedule = BillingSchedule::query()
                    ->where('student_id', $selectedStudent->id)
                    ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                        $query->where('academic_year_id', $selectedAcademicYear->id);
                    })
                    ->when(! $showPaidDues, function ($query) {
                        $query->where('status', '!=', 'paid');
                    })
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->get()
                    ->map(function (BillingSchedule $billingSchedule) {
                        return [
                            'id' => $billingSchedule->id,
                            'description' => $billingSchedule->description,
                            'due_date' => $billingSchedule->due_date?->toDateString(),
                            'due_date_label' => $billingSchedule->due_date?->format('M d, Y'),
                            'amount_due' => (float) $billingSchedule->amount_due,
                            'amount_paid' => (float) $billingSchedule->amount_paid,
                            'status' => $billingSchedule->status,
                            'status_label' => $this->formatDueStatus($billingSchedule->status),
                        ];
                    })
                    ->values();

                $ledgerEntriesCollection = LedgerEntry::query()
                    ->where('student_id', $selectedStudent->id)
                    ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                        $query->where('academic_year_id', $selectedAcademicYear->id);
                    })
                    ->when($dateFrom, function ($query, $dateFrom) {
                        $query->whereDate('date', '>=', $dateFrom);
                    })
                    ->when($dateTo, function ($query, $dateTo) {
                        $query->whereDate('date', '<=', $dateTo);
                    })
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get()
                    ->map(function (LedgerEntry $ledgerEntry) {
                        $resolvedEntryType = $this->resolveLedgerEntryType($ledgerEntry);

                        return [
                            'id' => $ledgerEntry->id,
                            'date' => $ledgerEntry->date?->toDateString(),
                            'date_label' => $ledgerEntry->date?->format('M d, Y'),
                            'reference' => $ledgerEntry->description,
                            'entry_type' => $resolvedEntryType,
                            'entry_type_label' => $this->formatEntryType($resolvedEntryType),
                            'charge' => (float) ($ledgerEntry->debit ?? 0),
                            'payment' => (float) ($ledgerEntry->credit ?? 0),
                            'running_balance' => (float) $ledgerEntry->running_balance,
                        ];
                    });

                if ($entryType !== 'all') {
                    $ledgerEntriesCollection = $ledgerEntriesCollection
                        ->filter(function (array $ledgerEntry) use ($entryType) {
                            return $ledgerEntry['entry_type'] === $entryType;
                        })
                        ->values();
                }

                $ledgerEntries = $ledgerEntriesCollection->values();

                $summary = [
                    'total_charges' => round((float) $ledgerEntries->sum('charge'), 2),
                    'total_payments' => round((float) $ledgerEntries->sum('payment'), 2),
                    'outstanding_balance' => $outstandingBalance,
                ];
            }
        }

        return Inertia::render('finance/student-ledgers/index', [
            'students' => $students,
            'selected_student' => $selectedStudentPayload,
            'dues_schedule' => $duesSchedule,
            'ledger_entries' => $ledgerEntries,
            'summary' => $summary,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'student_id' => $selectedStudentId > 0 ? $selectedStudentId : null,
                'entry_type' => $entryType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'show_paid_dues' => $showPaidDues,
            ],
        ]);
    }

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
        $ongoingEnrollment = $student->enrollments
            ->first(function (Enrollment $enrollment) {
                return $enrollment->academicYear?->status === 'ongoing';
            });

        return $ongoingEnrollment ?: $student->enrollments->first();
    }

    private function resolveActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();
    }

    private function formatPaymentPlan(?string $paymentPlan): string
    {
        if (! $paymentPlan) {
            return '-';
        }

        return match ($paymentPlan) {
            'semi-annual' => 'Semi-Annual',
            'cash' => 'Cash',
            default => ucfirst($paymentPlan),
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

    private function resolveLedgerEntryType(LedgerEntry $ledgerEntry): string
    {
        $description = strtolower((string) $ledgerEntry->description);

        if (str_contains($description, 'discount')) {
            return 'discount';
        }

        if (str_contains($description, 'adjustment')) {
            return 'adjustment';
        }

        if ((float) ($ledgerEntry->debit ?? 0) > 0) {
            return 'charge';
        }

        if ((float) ($ledgerEntry->credit ?? 0) > 0) {
            return 'payment';
        }

        return 'adjustment';
    }

    private function formatEntryType(string $entryType): string
    {
        return match ($entryType) {
            'charge' => 'Charge',
            'payment' => 'Payment',
            'discount' => 'Discount',
            'adjustment' => 'Adjustment',
            default => ucwords(str_replace('_', ' ', $entryType)),
        };
    }
}
