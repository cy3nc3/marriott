<?php

namespace App\Http\Requests\Teacher;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_assignment_id' => ['required', 'integer', 'exists:subject_assignments,id'],
            'month' => ['required', 'date_format:Y-m'],
            'entries' => ['required', 'array'],
            'entries.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'entries.*.date' => ['required', 'date_format:Y-m-d'],
            'entries.*.status' => ['required', Rule::in(Attendance::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.required' => 'Attendance entries are required.',
            'entries.array' => 'Attendance entries must be a valid list.',
            'entries.*.status.in' => 'Attendance status is invalid.',
        ];
    }
}
