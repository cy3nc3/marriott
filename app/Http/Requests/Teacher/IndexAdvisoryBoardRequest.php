<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class IndexAdvisoryBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'quarter' => ['nullable', 'in:1,2,3,4'],
        ];
    }
}
