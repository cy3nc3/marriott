<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCashierTransactionRequest;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\RemedialCase;
use App\Models\Student;
use App\Models\Transaction;
use App\Services\DashboardCacheService;
use App\Services\Finance\DiscountBucketCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashierPanelController extends Controller
{
    public function __construct(private DiscountBucketCalculator $discountBucketCalculator) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $activeAcademicYear = $this->resolveActiveAcademicYear();
        $this->reconcileEnrollmentQueueStatuses($activeAcademicYear);

        $students = $this->resolveStudentOptions($search, 20);

        $selectedStudentId = (int) $request->input('student_id', 0);

        $selectedStudentPayload = null;
        $selectedEnrollment = null;
        $selectedAcademicYear = null;
        $selectedRemedialCase = null;

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
                $selectedRemedialCase = $selectedAcademicYear
                    ? RemedialCase::query()
                        ->where('student_id', $selectedStudent->id)
                        ->where('academic_year_id', $selectedAcademicYear->id)
                        ->first()
                    : null;

                $ledgerQuery = LedgerEntry::query()
                    ->where('student_id', $selectedStudent->id)
                    ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                        $query->where('academic_year_id', $selectedAcademicYear->id);
                    });

                $totalCharges = (float) (clone $ledgerQuery)->sum('debit');
                $totalPayments = (float) (clone $ledgerQuery)->sum('credit');
                $remainingBalance = round($totalCharges - $totalPayments, 2);
                $assessmentTotalBeforeDownpayment = 0.0;

                if ($selectedEnrollment?->grade_level_id) {
                    $assessmentFeeTotal = $this->resolveAssessmentFeeTotal(
                        (int) $selectedEnrollment->grade_level_id,
                        (int) ($selectedEnrollment->academic_year_id ?? $selectedAcademicYear?->id ?? 0)
                    );
                    $discountAmount = $this->resolveDiscountAmount(
                        (int) $selectedStudent->id,
                        (int) ($selectedEnrollment->academic_year_id ?? $selectedAcademicYear?->id ?? 0),
                        $assessmentFeeTotal
                    );
                    $assessmentTotalBeforeDownpayment = round(
                        max($assessmentFeeTotal - $discountAmount, 0),
                        2
                    );
                }

                $gradeAndSection = 'Unassigned';
                if ($selectedEnrollment?->gradeLevel?->name && $selectedEnrollment?->section?->name) {
                    $gradeAndSection = "{$selectedEnrollment->gradeLevel->name} - {$selectedEnrollment->section->name}";
                } elseif ($selectedEnrollment?->gradeLevel?->name) {
                    $gradeAndSection = $selectedEnrollment->gradeLevel->name;
                }

                $selectedStudentPayload = [
                    'id' => $selectedStudent->id,
                    'lrn' => $selectedStudent->lrn,
                    'name' => trim("{$selectedStudent->first_name} {$selectedStudent->last_name}"),
                    'grade_and_section' => $gradeAndSection,
                    'enrollment_status' => $selectedEnrollment?->status,
                    'payment_plan' => $selectedEnrollment?->payment_term,
                    'stated_downpayment' => (float) ($selectedEnrollment?->downpayment ?? 0),
                    'remaining_balance' => $remainingBalance,
                    'assessment_total_before_downpayment' => $assessmentTotalBeforeDownpayment,
                    'remedial_case' => $selectedRemedialCase ? [
                        'id' => (int) $selectedRemedialCase->id,
                        'status' => $selectedRemedialCase->status,
                        'total_amount' => (float) $selectedRemedialCase->total_amount,
                        'amount_paid' => (float) $selectedRemedialCase->amount_paid,
                        'balance' => round(
                            max((float) $selectedRemedialCase->total_amount - (float) $selectedRemedialCase->amount_paid, 0),
                            2
                        ),
                    ] : null,
                ];
            }
        }

        $assessmentFeeTotal = 0.0;

        if ($selectedEnrollment?->grade_level_id) {
            $assessmentFeeTotal = $this->resolveAssessmentFeeTotal(
                (int) $selectedEnrollment->grade_level_id,
                (int) ($selectedEnrollment->academic_year_id ?? $selectedAcademicYear?->id ?? 0)
            );
        }

        $feeOptions = collect();
        if ($assessmentFeeTotal > 0) {
            $feeOptions->push([
                'id' => 1,
                'name' => 'Assessment Fee',
                'type' => 'assessment_fee',
                'amount' => round($assessmentFeeTotal, 2),
            ]);
        }

        if ($selectedRemedialCase) {
            $remedialBalance = round(
                max((float) $selectedRemedialCase->total_amount - (float) $selectedRemedialCase->amount_paid, 0),
                2
            );

            if ($remedialBalance > 0) {
                $feeOptions->push([
                    'id' => 1000000 + (int) $selectedRemedialCase->id,
                    'name' => 'Remedial Fee',
                    'type' => 'remedial_fee',
                    'amount' => $remedialBalance,
                ]);
            }
        }

        $inventoryOptions = InventoryItem::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'type'])
            ->map(function (InventoryItem $inventoryItem) {
                return [
                    'id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'type' => $inventoryItem->type,
                    'price' => (float) $inventoryItem->price,
                ];
            })
            ->values();

        $pendingIntakes = $this->resolvePendingCashierIntakes($activeAcademicYear);
        $pendingRemedialCases = $this->resolvePendingRemedialCases($activeAcademicYear);

        return Inertia::render('finance/cashier-panel/index', [
            'students' => $students,
            'selected_student' => $selectedStudentPayload,
            'fee_options' => $feeOptions,
            'inventory_options' => $inventoryOptions,
            'pending_intakes_count' => $pendingIntakes->count(),
            'pending_intakes' => $pendingIntakes,
            'pending_remedial_cases_count' => $pendingRemedialCases->count(),
            'pending_remedial_cases' => $pendingRemedialCases,
            'filters' => $request->only(['search', 'student_id']),
        ]);
    }

    public function studentSuggestions(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $this->reconcileEnrollmentQueueStatuses($this->resolveActiveAcademicYear());

        if ($search === '') {
            return response()->json([
                'students' => [],
            ]);
        }

        return response()->json([
            'students' => $this->resolveStudentOptions($search, 5),
        ]);
    }

    public function storeTransaction(StoreCashierTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::query()->findOrFail($validated['student_id']);
        $academicYear = $this->resolveAcademicYearForStudent($student);
        if (! $academicYear) {
            return back()->with('error', 'No school year available. Please configure one first.');
        }

        $items = collect($validated['items'])
            ->map(function (array $item) {
                return [
                    'type' => $item['type'],
                    'description' => $item['description'],
                    'amount' => round((float) $item['amount'], 2),
                    'fee_id' => $item['fee_id'] ?? null,
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                ];
            })
            ->values();

        $totalAmount = round((float) $items->sum('amount'), 2);

        DB::transaction(function () use ($validated, $student, $academicYear, $items, $totalAmount) {
            $allocatablePaymentAmount = round((float) $items
                ->filter(function (array $item): bool {
                    return $item['type'] === 'assessment_fee';
                })
                ->sum('amount'), 2);

            $remedialPaymentAmount = round((float) $items
                ->filter(function (array $item): bool {
                    return $item['type'] === 'remedial_fee';
                })
                ->sum('amount'), 2);

            $transaction = Transaction::query()->create([
                'or_number' => $validated['or_number'],
                'student_id' => $student->id,
                'cashier_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'payment_mode' => $validated['payment_mode'],
                'reference_no' => $validated['reference_no'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $transaction->items()->createMany(
                $items
                    ->map(function (array $item): array {
                        return [
                            'fee_id' => $item['fee_id'],
                            'inventory_item_id' => $item['inventory_item_id'],
                            'description' => $item['description'],
                            'amount' => $item['amount'],
                        ];
                    })
                    ->all()
            );

            $this->allocatePaymentAcrossDues($transaction, $student, $academicYear, $allocatablePaymentAmount);
            $this->applyPaymentToRemedialCase($student, $academicYear, $remedialPaymentAmount);

            $previousRunningBalance = (float) (LedgerEntry::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->latest('date')
                ->latest('id')
                ->value('running_balance') ?? 0);

            $runningBalance = round($previousRunningBalance - $totalAmount, 2);

            LedgerEntry::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'date' => now()->toDateString(),
                'description' => "Payment ({$transaction->or_number})",
                'debit' => null,
                'credit' => $totalAmount,
                'running_balance' => $runningBalance,
                'reference_id' => $transaction->id,
            ]);

            $this->syncEnrollmentStatusAfterPayment($student, $academicYear);
        });

        DashboardCacheService::bust();

        return back()->with('success', 'Transaction posted successfully.');
    }

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
        $ongoingEnrollment = $student->enrollments
            ->first(function (Enrollment $enrollment) {
                return $enrollment->academicYear?->status === 'ongoing';
            });

        return $ongoingEnrollment ?: $student->enrollments->first();
    }

    private function resolveStudentOptions(string $search, int $limit): Collection
    {
        $activeAcademicYearId = $this->resolveActiveAcademicYear()?->id;
        if (! $activeAcademicYearId) {
            return collect();
        }

        $normalizedSearch = strtolower($search);

        return Student::query()
            ->where(function ($query) use ($activeAcademicYearId) {
                $query
                    ->whereHas('enrollments', function ($enrollmentQuery) use ($activeAcademicYearId) {
                        $enrollmentQuery
                            ->where('academic_year_id', $activeAcademicYearId)
                            ->where('status', 'for_cashier_payment');
                    })
                    ->orWhereHas('remedialCases', function ($remedialCaseQuery) use ($activeAcademicYearId) {
                        $remedialCaseQuery
                            ->where('academic_year_id', $activeAcademicYearId)
                            ->whereIn('status', ['for_cashier_payment', 'partial_payment']);
                    });
            })
            ->when($search !== '', function ($query) use ($normalizedSearch) {
                $query->where(function ($searchQuery) use ($normalizedSearch) {
                    $searchQuery
                        ->whereRaw('LOWER(lrn) LIKE ?', ["%{$normalizedSearch}%"])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', ["%{$normalizedSearch}%"])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$normalizedSearch}%"]);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get(['id', 'lrn', 'first_name', 'last_name'])
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'lrn' => $student->lrn,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                ];
            })
            ->values();
    }

    private function resolvePendingCashierIntakes(?AcademicYear $academicYear): Collection
    {
        if (! $academicYear) {
            return collect();
        }

        return Enrollment::query()
            ->with([
                'student:id,lrn,first_name,last_name',
                'gradeLevel:id,name',
                'section:id,name',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'for_cashier_payment')
            ->latest('id')
            ->get([
                'id',
                'student_id',
                'grade_level_id',
                'section_id',
                'payment_term',
                'downpayment',
                'status',
            ])
            ->map(function (Enrollment $enrollment) {
                $gradeAndSection = 'Unassigned';
                if ($enrollment->gradeLevel?->name && $enrollment->section?->name) {
                    $gradeAndSection = "{$enrollment->gradeLevel->name} - {$enrollment->section->name}";
                } elseif ($enrollment->gradeLevel?->name) {
                    $gradeAndSection = $enrollment->gradeLevel->name;
                }

                return [
                    'id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'lrn' => $enrollment->student?->lrn,
                    'student_name' => trim("{$enrollment->student?->first_name} {$enrollment->student?->last_name}"),
                    'grade_and_section' => $gradeAndSection,
                    'payment_plan' => $enrollment->payment_term,
                    'downpayment' => (float) $enrollment->downpayment,
                ];
            })
            ->values();
    }

    private function resolvePendingRemedialCases(?AcademicYear $academicYear): Collection
    {
        if (! $academicYear) {
            return collect();
        }

        return RemedialCase::query()
            ->with([
                'student:id,lrn,first_name,last_name',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['for_cashier_payment', 'partial_payment'])
            ->latest('id')
            ->get([
                'id',
                'student_id',
                'failed_subject_count',
                'total_amount',
                'amount_paid',
                'status',
            ])
            ->map(function (RemedialCase $remedialCase) {
                return [
                    'id' => $remedialCase->id,
                    'student_id' => $remedialCase->student_id,
                    'lrn' => $remedialCase->student?->lrn,
                    'student_name' => trim("{$remedialCase->student?->first_name} {$remedialCase->student?->last_name}"),
                    'failed_subject_count' => (int) $remedialCase->failed_subject_count,
                    'total_amount' => (float) $remedialCase->total_amount,
                    'amount_paid' => (float) $remedialCase->amount_paid,
                    'balance' => round(
                        max((float) $remedialCase->total_amount - (float) $remedialCase->amount_paid, 0),
                        2
                    ),
                    'status' => $remedialCase->status,
                ];
            })
            ->values();
    }

    private function resolveActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();
    }

    private function resolveAcademicYearForStudent(Student $student): ?AcademicYear
    {
        $ongoingEnrollment = Enrollment::query()
            ->with('academicYear')
            ->where('student_id', $student->id)
            ->whereHas('academicYear', function ($query) {
                $query->where('status', 'ongoing');
            })
            ->latest('id')
            ->first();

        if ($ongoingEnrollment?->academicYear) {
            return $ongoingEnrollment->academicYear;
        }

        $latestEnrollment = Enrollment::query()
            ->with('academicYear')
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if ($latestEnrollment?->academicYear) {
            return $latestEnrollment->academicYear;
        }

        return $this->resolveActiveAcademicYear();
    }

    private function syncEnrollmentStatusAfterPayment(Student $student, AcademicYear $academicYear): void
    {
        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->latest('id')
            ->first();

        if (! $enrollment || $enrollment->status === 'enrolled') {
            return;
        }

        if ($enrollment->payment_term === 'cash') {
            $enrollment->update(['status' => 'enrolled']);

            return;
        }

        $totalPaidInYear = (float) Transaction::query()
            ->where('student_id', $student->id)
            ->whereNotIn('status', ['voided', 'refunded', 'reissued'])
            ->whereHas('ledgerEntries', function ($query) use ($academicYear) {
                $query
                    ->where('academic_year_id', $academicYear->id)
                    ->whereNotNull('credit');
            })
            ->sum('total_amount');

        $newStatus = $totalPaidInYear >= (float) $enrollment->downpayment
            ? 'enrolled'
            : 'for_cashier_payment';

        $enrollment->update(['status' => $newStatus]);
    }

    private function allocatePaymentAcrossDues(
        Transaction $transaction,
        Student $student,
        AcademicYear $academicYear,
        float $paymentAmount
    ): void {
        $remainingPaymentCents = (int) round(max($paymentAmount, 0) * 100);

        $transaction->dueAllocations()->delete();

        if ($remainingPaymentCents <= 0) {
            return;
        }

        $billingSchedules = BillingSchedule::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($billingSchedules as $billingSchedule) {
            if ($remainingPaymentCents <= 0) {
                break;
            }

            $amountDueCents = (int) round((float) $billingSchedule->amount_due * 100);
            $amountPaidCents = (int) round((float) $billingSchedule->amount_paid * 100);
            $outstandingCents = max($amountDueCents - $amountPaidCents, 0);

            if ($outstandingCents <= 0) {
                continue;
            }

            $appliedCents = min($remainingPaymentCents, $outstandingCents);
            $newPaidCents = $amountPaidCents + $appliedCents;

            $billingSchedule->update([
                'amount_paid' => round($newPaidCents / 100, 2),
                'status' => $newPaidCents >= $amountDueCents ? 'paid' : 'partially_paid',
            ]);

            $transaction->dueAllocations()->create([
                'billing_schedule_id' => $billingSchedule->id,
                'amount' => round($appliedCents / 100, 2),
            ]);

            $remainingPaymentCents -= $appliedCents;
        }
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

    private function resolveDiscountAmount(int $studentId, int $academicYearId, float $assessmentFeeTotal): float
    {
        return $this->discountBucketCalculator
            ->summarizeForStudent($studentId, $academicYearId, $assessmentFeeTotal)['total_discount_amount'];
    }

    private function applyPaymentToRemedialCase(
        Student $student,
        AcademicYear $academicYear,
        float $paymentAmount
    ): void {
        if ($paymentAmount <= 0) {
            return;
        }

        $remedialCase = RemedialCase::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['for_cashier_payment', 'partial_payment'])
            ->lockForUpdate()
            ->first();

        if (! $remedialCase) {
            return;
        }

        $nextPaidAmount = round((float) $remedialCase->amount_paid + $paymentAmount, 2);
        $cappedPaidAmount = min($nextPaidAmount, (float) $remedialCase->total_amount);
        $nextStatus = 'for_cashier_payment';

        if ($cappedPaidAmount <= 0) {
            $nextStatus = 'for_cashier_payment';
        } elseif ($cappedPaidAmount >= (float) $remedialCase->total_amount) {
            $nextStatus = 'paid';
        } else {
            $nextStatus = 'partial_payment';
        }

        $remedialCase->update([
            'amount_paid' => $cappedPaidAmount,
            'status' => $nextStatus,
            'paid_at' => $nextStatus === 'paid' ? now() : null,
        ]);
    }

    private function reconcileEnrollmentQueueStatuses(?AcademicYear $academicYear): void
    {
        if (! $academicYear) {
            return;
        }

        $pendingEnrollments = Enrollment::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'for_cashier_payment')
            ->get(['id', 'student_id', 'payment_term', 'downpayment']);

        if ($pendingEnrollments->isEmpty()) {
            return;
        }

        $paidByStudent = Transaction::query()
            ->selectRaw('student_id, SUM(total_amount) as total_paid')
            ->whereIn('student_id', $pendingEnrollments->pluck('student_id')->all())
            ->whereNotIn('status', ['voided', 'refunded', 'reissued'])
            ->whereHas('ledgerEntries', function ($query) use ($academicYear) {
                $query
                    ->where('academic_year_id', $academicYear->id)
                    ->whereNotNull('credit');
            })
            ->groupBy('student_id')
            ->pluck('total_paid', 'student_id');

        $idsToMarkEnrolled = [];

        foreach ($pendingEnrollments as $enrollment) {
            $totalPaid = (float) ($paidByStudent[(int) $enrollment->student_id] ?? 0);

            if ((string) $enrollment->payment_term === 'cash') {
                if ($totalPaid > 0) {
                    $idsToMarkEnrolled[] = (int) $enrollment->id;
                }

                continue;
            }

            if ($totalPaid >= (float) $enrollment->downpayment) {
                $idsToMarkEnrolled[] = (int) $enrollment->id;
            }
        }

        if ($idsToMarkEnrolled === []) {
            return;
        }

        Enrollment::query()
            ->whereIn('id', $idsToMarkEnrolled)
            ->update(['status' => 'enrolled']);
    }
}
