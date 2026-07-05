<?php

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
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
            'lrn' => ['required', 'string', 'digits:12'],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:Male,Female',
            'birthdate' => 'required|date|before_or_equal:today',
            'guardian_name' => 'required|string|max:255',
            'guardian_contact_number' => 'required_without:emergency_contact|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'student_personal_email' => 'nullable|email|max:255',
            'payment_term' => 'required|string|in:cash,full,monthly,quarterly,semi-annual',
            'downpayment' => 'nullable|numeric|min:0|max:999999.99',
            'report_card_submitted' => 'nullable|boolean',
            'birth_certificate_submitted' => 'nullable|boolean',
            'section_id' => 'required|integer|exists:sections,id',
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'resolve_older_conditional' => 'nullable|boolean',
            'resolve_older_retained' => 'nullable|boolean',
            'conditional_resolution_notes' => 'nullable|string|max:1000',
            'retained_resolution_notes' => 'nullable|string|max:1000',
            'discount_id' => 'nullable|integer|exists:discounts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'lrn.digits' => 'LRN must be exactly 12 digits.',
            'gender.required' => 'Gender is required.',
            'grade_level_id.required' => 'Grade level is required before enrollment.',
            'section_id.required' => 'Section is required before enrollment.',
        ];
    }
}
