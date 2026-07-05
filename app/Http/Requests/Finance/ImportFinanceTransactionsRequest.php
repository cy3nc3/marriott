<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ImportFinanceTransactionsRequest extends FormRequest
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
            'import_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Please choose an Excel workbook template to import.',
            'import_file.mimes' => 'Import file must be an XLS or XLSX workbook template.',
            'import_file.max' => 'Import file must not exceed 10MB.',
        ];
    }
}
