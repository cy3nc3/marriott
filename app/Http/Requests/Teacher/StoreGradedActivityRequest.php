<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradedActivityRequest extends FormRequest
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
            'type' => ['required', 'in:WW,PT,QA'],
            'title' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'gt:0', 'max:999.99'],
        ];
    }
}
