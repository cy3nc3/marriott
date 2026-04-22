<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;

class PreviewImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mapping' => ['nullable', 'array'],
            'summary' => ['nullable', 'array'],
        ];
    }
}
