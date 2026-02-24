<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFeeRequest;
use App\Http\Requests\Finance\UpdateFeeRequest;
use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeeStructureController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolYearOptions = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'status', 'start_date'])
            ->map(function (AcademicYear $academicYear) {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();

        $selectedAcademicYearId = $request->integer('academic_year_id');
        if (
            $selectedAcademicYearId <= 0
            || ! $schoolYearOptions->pluck('id')->contains($selectedAcademicYearId)
        ) {
            $selectedAcademicYearId = (int) ($schoolYearOptions->firstWhere('status', 'ongoing')['id']
                ?? ($schoolYearOptions->first()['id'] ?? 0));
        }

        $selectedAcademicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;

        $hasVersionedFeesForSelectedYear = $selectedAcademicYear
            ? Fee::query()
                ->where('academic_year_id', $selectedAcademicYear->id)
                ->exists()
            : false;

        $gradeLevelFees = GradeLevel::query()
            ->with(['fees' => function ($query) use (
                $selectedAcademicYear,
                $hasVersionedFeesForSelectedYear
            ) {
                if ($selectedAcademicYear) {
                    if ($hasVersionedFeesForSelectedYear) {
                        $query->where('academic_year_id', $selectedAcademicYear->id);
                    } else {
                        $query->whereNull('academic_year_id');
                    }
                } else {
                    $query->whereNull('academic_year_id');
                }

                $query
                    ->orderBy('type')
                    ->orderBy('name')
                    ->orderBy('id');
            }])
            ->orderBy('level_order')
            ->get()
            ->map(function (GradeLevel $gradeLevel) {
                return [
                    'id' => $gradeLevel->id,
                    'name' => $gradeLevel->name,
                    'fee_items' => $gradeLevel->fees
                        ->map(function (Fee $fee) {
                            return [
                                'id' => $fee->id,
                                'grade_level_id' => $fee->grade_level_id,
                                'academic_year_id' => $fee->academic_year_id,
                                'label' => $fee->name,
                                'type' => $fee->type,
                                'category' => $this->formatCategory($fee->type),
                                'amount' => (float) $fee->amount,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return Inertia::render('finance/fee-structure/index', [
            'grade_level_fees' => $gradeLevelFees,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedAcademicYear?->id,
        ]);
    }

    public function store(StoreFeeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Fee::query()->create($validated);

        return redirect()
            ->route('finance.fee_structure', [
                'academic_year_id' => $validated['academic_year_id'],
            ])
            ->with('success', 'Fee item added.');
    }

    public function update(UpdateFeeRequest $request, Fee $fee): RedirectResponse
    {
        $validated = $request->validated();

        $fee->update($validated);

        return redirect()
            ->route('finance.fee_structure', [
                'academic_year_id' => $validated['academic_year_id'],
            ])
            ->with('success', 'Fee item updated.');
    }

    public function destroy(Request $request, Fee $fee): RedirectResponse
    {
        $selectedAcademicYearId = $request->integer('academic_year_id');

        $fee->delete();

        $redirect = redirect()->route('finance.fee_structure');

        if ($selectedAcademicYearId > 0) {
            $redirect = redirect()->route('finance.fee_structure', [
                'academic_year_id' => $selectedAcademicYearId,
            ]);
        }

        return $redirect->with('success', 'Fee item removed.');
    }

    private function formatCategory(string $type): string
    {
        return match ($type) {
            'tuition' => 'Tuition',
            'miscellaneous' => 'Miscellaneous',
            'books_modules' => 'Books and Modules',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
