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

    protected function prepareForValidation(): void
    {
        if (! $this->has('required_weekly_minutes')) {
            $this->merge(['required_weekly_minutes' => 200]);
        }
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
            'required_weekly_minutes' => ['required', 'integer', 'min:1', 'max:1200'],
        ];
    }
}
