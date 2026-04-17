<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentPasswordRules = $this->user()?->must_change_password
            ? ['nullable', 'string']
            : $this->currentPasswordRules();

        return [
            'current_password' => $currentPasswordRules,
            'password' => $this->passwordRules(),
        ];
    }
}
