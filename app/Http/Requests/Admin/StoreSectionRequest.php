<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('academic_year_id')) {
            return;
        }

        $activeAcademicYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeAcademicYearId === null) {
            $activeAcademicYearId = AcademicYear::query()
                ->where('status', '!=', 'completed')
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
            'name' => ['required', 'string', 'max:255'],
            'adviser_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
