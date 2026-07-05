<?php

namespace App\Http\Requests\Admin;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
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
    }

    public function rules(): array
    {
        /** @var Section|null $section */
        $section = $this->route('section');
        $sectionId = $section?->id;
        $academicYearId = $section?->academic_year_id;
        $gradeLevelId = $section?->grade_level_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')
                    ->ignore($sectionId)
                    ->where(function ($query) use ($academicYearId, $gradeLevelId) {
                        return $query
                            ->where('academic_year_id', (int) $academicYearId)
                            ->where('grade_level_id', (int) $gradeLevelId);
                    }),
            ],
            'adviser_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
