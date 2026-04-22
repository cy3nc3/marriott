<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;

class UploadImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'mapping' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'Please choose a CSV file to upload.',
            'import_file.mimes' => 'Import file must be a CSV file.',
            'import_file.max' => 'Import file must not exceed 10MB.',
        ];
    }
}
