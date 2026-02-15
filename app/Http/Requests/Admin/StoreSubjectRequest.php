<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'subject_code' => ['required', 'string', 'max:20', 'unique:subjects,subject_code'],
            'subject_name' => ['required', 'string', 'max:255'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['exists:users,id'],
        ];
    }
}
