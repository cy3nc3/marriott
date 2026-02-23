<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAcademicYearDatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('start_date') || ! $this->filled('end_date')) {
                return;
            }

            $academicYear = $this->route('academicYear');
            $startDate = (string) $this->input('start_date');
            $endDate = (string) $this->input('end_date');

            $hasOverlappingYear = AcademicYear::query()
                ->when($academicYear, function ($query) use ($academicYear) {
                    $query->whereKeyNot((int) $academicYear->id);
                })
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->exists();

            if ($hasOverlappingYear) {
                $validator->errors()->add('start_date', 'Date range overlaps with an existing school year.');
            }
        });
    }
}
