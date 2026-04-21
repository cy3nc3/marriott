<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeSubmissionDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_deadline' => ['required', 'date'],
            'send_time' => ['required', 'date_format:H:i'],
            'reminder_days' => ['required', 'array', 'min:1'],
            'reminder_days.*' => ['integer', 'between:1,14', 'distinct'],
        ];
    }
}
