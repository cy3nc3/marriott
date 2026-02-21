<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFeeRequest;
use App\Http\Requests\Finance\UpdateFeeRequest;
use App\Models\Fee;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeeStructureController extends Controller
{
    public function index(): Response
    {
        $gradeLevelFees = GradeLevel::query()
            ->with(['fees' => function ($query) {
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
        ]);
    }

    public function store(StoreFeeRequest $request): RedirectResponse
    {
        Fee::query()->create($request->validated());

        return back()->with('success', 'Fee item added.');
    }

    public function update(UpdateFeeRequest $request, Fee $fee): RedirectResponse
    {
        $fee->update($request->validated());

        return back()->with('success', 'Fee item updated.');
    }

    public function destroy(Fee $fee): RedirectResponse
    {
        $fee->delete();

        return back()->with('success', 'Fee item removed.');
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
