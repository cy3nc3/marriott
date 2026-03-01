<?php

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemedialIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'student_id' => ['required', 'exists:students,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'School year is required.',
            'student_id.required' => 'Student is required.',
        ];
    }
}
