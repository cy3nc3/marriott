<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'exists:sections,id'],
            'subject_assignment_id' => ['nullable', 'exists:subject_assignments,id'],
            'subject_id' => ['nullable', 'required_if:type,academic', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'required_if:type,academic', 'exists:users,id'],
            'type' => ['required', 'string', 'in:academic,break,ceremony'],
            'label' => ['nullable', 'string', 'max:255'],
            'day' => ['required', Rule::in([
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
