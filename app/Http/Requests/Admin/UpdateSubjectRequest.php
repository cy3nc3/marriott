<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('subjects', 'subject_code')->ignore($this->subject),
            ],
            'subject_name' => ['required', 'string', 'max:255'],
        ];
    }
}
