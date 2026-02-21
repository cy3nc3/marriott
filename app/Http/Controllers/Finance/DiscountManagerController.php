<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreDiscountRequest;
use App\Http\Requests\Finance\StoreStudentDiscountRequest;
use App\Http\Requests\Finance\UpdateDiscountRequest;
use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Student;
use App\Models\StudentDiscount;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiscountManagerController extends Controller
{
    public function index(): Response
    {
        $activeAcademicYear = $this->resolveActiveAcademicYear();

        $discountPrograms = Discount::query()
            ->orderBy('name')
            ->get()
            ->map(function (Discount $discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'type' => $discount->type,
                    'calculation' => $this->formatCalculationLabel($discount->type),
                    'value' => (float) $discount->value,
                    'value_label' => $this->formatValueLabel($discount->type, (float) $discount->value),
                ];
            })
            ->values();

        $taggedStudents = StudentDiscount::query()
            ->with([
                'student:id,lrn,first_name,last_name',
                'discount:id,name',
                'academicYear:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (StudentDiscount $studentDiscount) {
                return [
                    'id' => $studentDiscount->id,
                    'student_name' => trim("{$studentDiscount->student?->first_name} {$studentDiscount->student?->last_name}"),
                    'lrn' => $studentDiscount->student?->lrn,
                    'program' => $studentDiscount->discount?->name,
                    'school_year' => $studentDiscount->academicYear?->name,
                    'tagged_on' => $studentDiscount->created_at?->toDateString(),
                ];
            })
            ->values();

        $students = Student::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'lrn', 'first_name', 'last_name'])
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'lrn' => $student->lrn,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                ];
            })
            ->values();

        return Inertia::render('finance/discount-manager/index', [
            'discount_programs' => $discountPrograms,
            'tagged_students' => $taggedStudents,
            'students' => $students,
            'active_academic_year' => $activeAcademicYear
                ? [
                    'id' => $activeAcademicYear->id,
                    'name' => $activeAcademicYear->name,
                ]
                : null,
        ]);
    }

    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        Discount::query()->create($request->validated());

        return back()->with('success', 'Discount program added.');
    }

    public function update(
        UpdateDiscountRequest $request,
        Discount $discount
    ): RedirectResponse {
        $discount->update($request->validated());

        return back()->with('success', 'Discount program updated.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return back()->with('success', 'Discount program removed.');
    }

    public function tagStudent(StoreStudentDiscountRequest $request): RedirectResponse
    {
        $activeAcademicYear = $this->resolveActiveAcademicYear();
        if (! $activeAcademicYear) {
            return back()->with('error', 'No active school year found. Please set up school year first.');
        }

        $validated = $request->validated();

        $alreadyTagged = StudentDiscount::query()
            ->where('student_id', $validated['student_id'])
            ->where('discount_id', $validated['discount_id'])
            ->where('academic_year_id', $activeAcademicYear->id)
            ->exists();

        if ($alreadyTagged) {
            return back()->with('error', 'Student is already tagged to this discount for the active school year.');
        }

        StudentDiscount::query()->create([
            'student_id' => $validated['student_id'],
            'discount_id' => $validated['discount_id'],
            'academic_year_id' => $activeAcademicYear->id,
        ]);

        return back()->with('success', 'Student discount tagged.');
    }

    public function untagStudent(StudentDiscount $studentDiscount): RedirectResponse
    {
        $studentDiscount->delete();

        return back()->with('success', 'Student discount removed.');
    }

    private function resolveActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();
    }

    private function formatCalculationLabel(string $type): string
    {
        return match ($type) {
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function formatValueLabel(string $type, float $value): string
    {
        if ($type === 'percentage') {
            return number_format($value, 2).' %';
        }

        return 'PHP '.number_format($value, 2);
    }
}
