<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'subject_assignment_id' => ['nullable', 'integer', 'exists:subject_assignments,id'],
            'month' => ['nullable', 'date_format:Y-m'],
            'format' => ['nullable', 'string', 'in:xlsx,xls,csv'],
        ];
    }
}
