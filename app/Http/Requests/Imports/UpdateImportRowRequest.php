<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImportRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'normalized_payload' => ['nullable', 'array'],
            'validation_errors' => ['nullable', 'array'],
            'duplicate_flags' => ['nullable', 'array'],
            'classification' => ['nullable', 'in:payment,due,mixed,unresolved'],
            'action' => ['nullable', 'in:pending,create,update,skip,blocked'],
            'is_unresolved' => ['nullable', 'boolean'],
        ];
    }
}
