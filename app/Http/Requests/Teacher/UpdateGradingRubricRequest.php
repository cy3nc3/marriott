<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGradingRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'ww_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'pt_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'qa_weight' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $totalWeight = (int) $this->input('ww_weight', 0)
                + (int) $this->input('pt_weight', 0)
                + (int) $this->input('qa_weight', 0);

            if ($totalWeight !== 100) {
                $validator->errors()->add(
                    'ww_weight',
                    'Rubric weights must total exactly 100%.'
                );
            }
        });
    }
}
