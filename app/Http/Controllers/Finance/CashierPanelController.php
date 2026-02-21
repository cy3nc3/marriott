<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCashierTransactionRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashierPanelController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $students = Student::query()
            ->when($search, function ($query, $search) {
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

        $selectedStudentId = (int) $request->input('student_id', 0);
        if (! $selectedStudentId && $students->count() === 1) {
            $selectedStudentId = (int) $students->first()['id'];
        }

        $selectedStudentPayload = null;
        $selectedEnrollment = null;
        $selectedAcademicYear = null;

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

                $ledgerQuery = LedgerEntry::query()
                    ->where('student_id', $selectedStudent->id)
                    ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                        $query->where('academic_year_id', $selectedAcademicYear->id);
                    });

                $totalCharges = (float) (clone $ledgerQuery)->sum('debit');
                $totalPayments = (float) (clone $ledgerQuery)->sum('credit');
                $remainingBalance = round($totalCharges - $totalPayments, 2);

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
                    'payment_plan' => $selectedEnrollment?->payment_term,
                    'stated_downpayment' => (float) ($selectedEnrollment?->downpayment ?? 0),
                    'remaining_balance' => $remainingBalance,
                ];
            }
        }

        $feeOptions = Fee::query()
            ->when($selectedEnrollment?->grade_level_id, function ($query) use ($selectedEnrollment) {
                $query->where('grade_level_id', $selectedEnrollment->grade_level_id);
            })
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'amount'])
            ->map(function (Fee $fee) {
                return [
                    'id' => $fee->id,
                    'name' => $fee->name,
                    'type' => $fee->type,
                    'amount' => (float) $fee->amount,
                ];
            })
            ->values();

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

        return Inertia::render('finance/cashier-panel/index', [
            'students' => $students,
            'selected_student' => $selectedStudentPayload,
            'fee_options' => $feeOptions,
            'inventory_options' => $inventoryOptions,
            'filters' => $request->only(['search', 'student_id']),
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
            $transaction = Transaction::query()->create([
                'or_number' => $validated['or_number'],
                'student_id' => $student->id,
                'cashier_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'payment_mode' => $validated['payment_mode'],
                'reference_no' => $validated['reference_no'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($items as $item) {
                $transaction->items()->create([
                    'fee_id' => $item['fee_id'],
                    'inventory_item_id' => $item['inventory_item_id'],
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                ]);
            }

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
            ->whereBetween('created_at', [
                "{$academicYear->start_date} 00:00:00",
                "{$academicYear->end_date} 23:59:59",
            ])
            ->sum('total_amount');

        $newStatus = $totalPaidInYear >= (float) $enrollment->downpayment
            ? 'enrolled'
            : 'partial_payment';

        $enrollment->update(['status' => $newStatus]);
    }
}
