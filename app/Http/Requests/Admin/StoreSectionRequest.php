<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
