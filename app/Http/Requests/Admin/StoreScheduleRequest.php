<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string', 'in:academic,break,ceremony'],
            'label' => ['nullable', 'string', 'max:255'],
            'day' => ['required', 'string'],
            'start_time' => ['required'],
            'end_time' => ['required'],
        ];
    }
}
