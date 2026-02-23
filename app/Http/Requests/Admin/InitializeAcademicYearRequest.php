<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InitializeAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('academic_years', 'name'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('name') || ! $this->filled('start_date') || ! $this->filled('end_date')) {
                return;
            }

            $name = (string) $this->input('name');
            $startDate = (string) $this->input('start_date');
            $endDate = (string) $this->input('end_date');

            if (preg_match('/^(\d{4})-(\d{4})$/', $name, $matches) !== 1) {
                return;
            }

            $startYearName = (int) $matches[1];
            $endYearName = (int) $matches[2];

            if ($endYearName !== $startYearName + 1) {
                $validator->errors()->add('name', 'School year name must follow consecutive years (e.g., 2025-2026).');
            }

            try {
                $startYearDate = Carbon::parse($startDate)->year;
                $endYearDate = Carbon::parse($endDate)->year;

                if ($startYearDate !== $startYearName || $endYearDate !== $endYearName) {
                    $validator->errors()->add('name', 'School year name must match the years in the provided start and end dates.');
                }
            } catch (\Throwable) {
                return;
            }

            $hasOverlappingYear = AcademicYear::query()
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->exists();

            if ($hasOverlappingYear) {
                $validator->errors()->add('start_date', 'Date range overlaps with an existing school year.');
            }
        });
    }
}
