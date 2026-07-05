<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpsertTeacherProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (string) $this->route('user')?->role === UserRole::TEACHER->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'qualification_status' => ['required', 'string', 'in:fully_qualified,provisionally_qualified,not_qualified'],
            'is_let_passer' => ['required', 'boolean'],
            'prc_license_no' => ['nullable', 'string', 'max:255'],
            'license_valid_until' => ['nullable', 'date'],
            'degree' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'professional_education_units' => ['nullable', 'integer', 'min:0', 'max:255'],
            'exception_basis' => ['nullable', 'string', 'max:255'],
            'provisional_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'retained_documents' => ['nullable', 'array'],
            'retained_documents.*' => ['string', 'regex:/^teacher-documents\/[A-Za-z0-9._\-\/]+$/'],
            'new_documents' => ['nullable', 'array'],
            'new_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
