<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradingScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_assignment_id' => ['required', 'integer', 'exists:subject_assignments,id'],
            'quarter' => ['required', 'in:1,2,3,4'],
            'save_mode' => ['required', 'in:draft,submitted'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'scores.*.graded_activity_id' => ['required', 'integer', 'exists:graded_activities,id'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
        ];
    }
}
