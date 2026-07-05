<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $staffRoleValues = [
            UserRole::TEACHER->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ];

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'personal_email' => [
                Rule::requiredIf(fn (): bool => in_array((string) $this->input('role'), $staffRoleValues, true)),
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'personal_email')->ignore($this->route('user')),
            ],
            'birthday' => 'nullable|date',
            'role' => ['required', 'string', Rule::in([
                UserRole::TEACHER->value,
                UserRole::REGISTRAR->value,
                UserRole::FINANCE->value,
                UserRole::ADMIN->value,
                UserRole::STUDENT->value,
                UserRole::PARENT->value,
            ])],
            'is_active' => 'required|boolean',
        ];
    }
}
