<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisoryConductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'quarter' => ['required', 'in:1,2,3,4'],
            'save_mode' => ['required', 'in:draft,locked'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'rows.*.maka_diyos' => ['required', 'in:AO,SO,RO,NO'],
            'rows.*.makatao' => ['required', 'in:AO,SO,RO,NO'],
            'rows.*.makakalikasan' => ['required', 'in:AO,SO,RO,NO'],
            'rows.*.makabansa' => ['required', 'in:AO,SO,RO,NO'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
