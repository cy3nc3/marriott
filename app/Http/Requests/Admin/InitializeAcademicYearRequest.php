<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InitializeAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/', 'unique:academic_years,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('name')) {
                return;
            }

            $name = (string) $this->input('name');
            if (preg_match('/^(\d{4})-(\d{4})$/', $name, $matches) !== 1) {
                return;
            }

            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];

            if ($endYear !== $startYear + 1) {
                $validator->errors()->add('name', 'School year name must use consecutive years (e.g., 2026-2027).');
            }
        });
    }
}
