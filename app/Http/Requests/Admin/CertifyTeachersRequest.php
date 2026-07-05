<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CertifyTeachersRequest extends FormRequest
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
            'teacher_details' => ['required', 'array'],
            'teacher_details.*.id' => ['required', 'integer', 'exists:users,id'],
            'teacher_details.*.qualification_status' => ['nullable', 'string', 'in:fully_qualified,provisionally_qualified,not_qualified'],
            'teacher_details.*.retained_documents' => ['nullable', 'array'],
            'teacher_details.*.retained_documents.*' => ['string'],
            'teacher_details.*.new_documents' => ['nullable', 'array'],
            'teacher_details.*.new_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
