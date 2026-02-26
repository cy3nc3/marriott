<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDueReminderAutomationSettingsRequest extends FormRequest
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
            'auto_send_enabled' => ['required', 'boolean'],
            'send_time' => ['required', 'date_format:H:i'],
            'max_announcements_per_run' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
