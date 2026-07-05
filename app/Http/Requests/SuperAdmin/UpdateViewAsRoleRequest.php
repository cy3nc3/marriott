<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateViewAsRoleRequest extends FormRequest
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
            'role' => ['required', 'string', Rule::in([
                UserRole::SUPER_ADMIN->value,
                UserRole::ADMIN->value,
                UserRole::REGISTRAR->value,
                UserRole::FINANCE->value,
                UserRole::TEACHER->value,
            ])],
        ];
    }
}
