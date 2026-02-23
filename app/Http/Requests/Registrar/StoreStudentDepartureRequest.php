<?php

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentDepartureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'reason' => ['required', 'in:transfer_out,dropped_out'],
            'effective_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => 'Reason must be Transfer Out or Dropped Out only.',
        ];
    }
}
