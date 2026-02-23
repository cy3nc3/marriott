<?php

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class ResolveBatchPromotionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permanent_record_id' => ['required', 'integer', 'exists:permanent_records,id'],
            'decision' => ['required', 'in:promoted,retained'],
            'note' => ['required', 'string', 'max:1000'],
        ];
    }
}
