<?php

namespace App\Http\Controllers\Registrar;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $activeAcademicYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        $queueStatuses = ['pending', 'pending_intake', 'for_cashier_payment', 'partial_payment'];

        $baseQuery = Enrollment::query()
            ->when($activeAcademicYearId, function ($query) use ($activeAcademicYearId) {
                $query->where('academic_year_id', $activeAcademicYearId);
            })
            ->whereIn('status', $queueStatuses);

        $search = $request->input('search');

        $enrollments = (clone $baseQuery)
            ->with([
                'student:id,lrn,first_name,last_name,contact_number',
            ])
            ->when($search, function ($query, $search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery
                        ->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->get()
            ->map(function (Enrollment $enrollment) {
                return [
                    'id' => $enrollment->id,
                    'lrn' => $enrollment->student?->lrn ?? '',
                    'first_name' => $enrollment->student?->first_name ?? '',
                    'last_name' => $enrollment->student?->last_name ?? '',
                    'emergency_contact' => $enrollment->student?->contact_number ?? '',
                    'payment_term' => $enrollment->payment_term,
                    'downpayment' => (float) $enrollment->downpayment,
                    'status' => $enrollment->status,
                ];
            })
            ->values();

        return Inertia::render('registrar/enrollment/index', [
            'enrollments' => $enrollments,
            'summary' => [
                'pending_intake' => (clone $baseQuery)->whereIn('status', ['pending', 'pending_intake'])->count(),
                'for_cashier_payment' => (clone $baseQuery)->where('status', 'for_cashier_payment')->count(),
                'partial_payment' => (clone $baseQuery)->where('status', 'partial_payment')->count(),
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lrn' => ['required', 'string', 'regex:/^\d{12}$/'],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'emergency_contact' => 'required|string|max:255',
            'payment_term' => 'required|string|in:cash,full,monthly,quarterly,semi-annual',
            'downpayment' => 'nullable|numeric|min:0|max:999999.99',
        ]);

        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first() ?? AcademicYear::query()->latest('start_date')->first();

        if (! $activeAcademicYear) {
            return back()->with('error', 'No academic year found. Please configure one first.');
        }

        $gradeLevelId = GradeLevel::query()->orderBy('level_order')->value('id');
        if (! $gradeLevelId) {
            return back()->with('error', 'No grade levels found. Please set up grade levels first.');
        }

        try {
            DB::transaction(function () use ($validated, $activeAcademicYear, $gradeLevelId) {
                $student = Student::query()->updateOrCreate(
                    ['lrn' => $validated['lrn']],
                    [
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'contact_number' => $validated['emergency_contact'],
                    ]
                );

                $this->ensureAccounts($student);

                $existingEnrollment = Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->first();

                if ($existingEnrollment && $existingEnrollment->status === 'enrolled') {
                    throw new \RuntimeException('Student is already fully enrolled for the active school year.');
                }

                $paymentTerm = $this->normalizePaymentTerm($validated['payment_term']);
                $downpayment = $this->normalizeDownpayment($paymentTerm, $validated['downpayment'] ?? null);

                if ($existingEnrollment) {
                    $existingEnrollment->update([
                        'payment_term' => $paymentTerm,
                        'downpayment' => $downpayment,
                        'status' => 'pending_intake',
                    ]);

                    return;
                }

                Enrollment::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'grade_level_id' => $this->resolveGradeLevelId($student, $gradeLevelId),
                    'section_id' => null,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $downpayment,
                    'status' => 'pending_intake',
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Enrollment intake saved.');
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'enrolled') {
            return back()->with('error', 'Enrolled students can no longer be edited in the intake queue.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'emergency_contact' => 'required|string|max:255',
            'payment_term' => 'required|string|in:cash,full,monthly,quarterly,semi-annual',
            'downpayment' => 'nullable|numeric|min:0|max:999999.99',
            'status' => 'required|string|in:pending,pending_intake,for_cashier_payment,partial_payment',
        ]);

        $paymentTerm = $this->normalizePaymentTerm($validated['payment_term']);
        $downpayment = $this->normalizeDownpayment($paymentTerm, $validated['downpayment'] ?? null);

        DB::transaction(function () use ($enrollment, $validated, $paymentTerm, $downpayment) {
            $student = $enrollment->student;

            if ($student) {
                $student->update([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'contact_number' => $validated['emergency_contact'],
                ]);

                $this->ensureAccounts($student);
            }

            $enrollment->update([
                'payment_term' => $paymentTerm,
                'downpayment' => $downpayment,
                'status' => $validated['status'],
            ]);
        });

        return back()->with('success', 'Enrollment intake updated.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'enrolled') {
            return back()->with('error', 'Cannot remove a fully enrolled student from the intake queue.');
        }

        $enrollment->delete();

        return back()->with('success', 'Enrollment intake removed from queue.');
    }

    private function normalizePaymentTerm(string $paymentTerm): string
    {
        return $paymentTerm === 'full' ? 'cash' : $paymentTerm;
    }

    private function normalizeDownpayment(string $paymentTerm, mixed $downpayment): float
    {
        if ($paymentTerm === 'cash') {
            return 0;
        }

        return round((float) ($downpayment ?? 0), 2);
    }

    private function resolveGradeLevelId(Student $student, int $fallbackGradeLevelId): int
    {
        $lastEnrollmentGradeLevelId = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereNotNull('grade_level_id')
            ->latest('id')
            ->value('grade_level_id');

        return $lastEnrollmentGradeLevelId ?: $fallbackGradeLevelId;
    }

    private function ensureAccounts(Student $student): void
    {
        $studentUser = $student->user;

        if (! $studentUser) {
            $studentUser = User::query()->firstOrCreate(
                ['email' => "student.{$student->lrn}@marriott.edu"],
                [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                    'role' => UserRole::STUDENT->value,
                    'is_active' => true,
                    'password' => Hash::make($student->lrn),
                ]
            );
        } else {
            $studentUser->update([
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'name' => trim("{$student->first_name} {$student->last_name}"),
            ]);
        }

        if ($student->user_id !== $studentUser->id) {
            $student->update(['user_id' => $studentUser->id]);
        }

        $parentUser = User::query()->firstOrCreate(
            ['email' => "parent.{$student->lrn}@marriott.edu"],
            [
                'first_name' => 'Parent',
                'last_name' => $student->last_name,
                'name' => "Parent {$student->last_name}",
                'role' => UserRole::PARENT->value,
                'is_active' => true,
                'password' => Hash::make($student->lrn),
            ]
        );

        $pivotQuery = DB::table('parent_student')
            ->where('parent_id', $parentUser->id)
            ->where('student_id', $student->id);

        if ($pivotQuery->exists()) {
            $pivotQuery->update([
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('parent_student')->insert([
            'parent_id' => $parentUser->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
