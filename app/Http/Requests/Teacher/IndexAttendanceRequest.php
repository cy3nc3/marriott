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
            'subject_assignment_id' => ['nullable', 'integer', 'exists:subject_assignments,id'],
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
