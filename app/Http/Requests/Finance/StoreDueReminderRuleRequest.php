<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDueReminderRuleRequest extends FormRequest
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
            'days_before_due' => [
                'required',
                'integer',
                'min:0',
                'max:60',
                Rule::unique('finance_due_reminder_rules', 'days_before_due'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
