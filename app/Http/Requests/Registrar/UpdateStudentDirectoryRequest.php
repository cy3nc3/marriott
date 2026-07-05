<?php

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentDirectoryRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_contact_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'report_card_submitted' => ['nullable', 'boolean'],
            'birth_certificate_submitted' => ['nullable', 'boolean'],
            'enrollment_status' => ['nullable', 'string', 'in:enrolled,transferred,dropped,withdrawn'],
            'send_claim_email_confirmation' => ['nullable', 'boolean'],
        ];
    }
}
