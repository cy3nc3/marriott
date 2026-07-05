<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }

        if ($this->filled('academic_year_id')) {
            return;
        }

        $activeAcademicYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeAcademicYearId === null) {
            $activeAcademicYearId = AcademicYear::query()
                ->where('status', 'upcoming')
                ->orderBy('start_date')
                ->value('id');
        }

        if ($activeAcademicYearId === null) {
            $activeAcademicYearId = AcademicYear::query()
                ->where('status', '!=', 'completed')
                ->orderBy('start_date')
                ->value('id');
        }

        if ($activeAcademicYearId !== null) {
            $this->merge([
                'academic_year_id' => $activeAcademicYearId,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')->where(function ($query) {
                    return $query
                        ->where('academic_year_id', (int) $this->input('academic_year_id'))
                        ->where('grade_level_id', (int) $this->input('grade_level_id'));
                }),
            ],
            'adviser_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
