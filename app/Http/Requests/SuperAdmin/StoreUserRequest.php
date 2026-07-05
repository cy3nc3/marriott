<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'personal_email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'personal_email')],
            'role' => ['required', 'string', Rule::in([
                UserRole::TEACHER->value,
                UserRole::REGISTRAR->value,
                UserRole::FINANCE->value,
                UserRole::ADMIN->value,
            ])],
        ];
    }
}
