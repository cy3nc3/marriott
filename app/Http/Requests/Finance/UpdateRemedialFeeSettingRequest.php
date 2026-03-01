<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRemedialFeeSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fee_per_subject' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'fee_per_subject.required' => 'Remedial fee per subject is required.',
            'fee_per_subject.numeric' => 'Remedial fee per subject must be a valid amount.',
            'fee_per_subject.min' => 'Remedial fee per subject cannot be negative.',
            'fee_per_subject.max' => 'Remedial fee per subject must not exceed 999,999.99.',
        ];
    }
}
