<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeReminderAutomationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'send_time' => ['required', 'date_format:H:i'],
            'reminder_days' => ['sometimes', 'array', 'min:1'],
            'reminder_days.*' => ['integer', 'between:1,14', 'distinct'],
        ];
    }
}
