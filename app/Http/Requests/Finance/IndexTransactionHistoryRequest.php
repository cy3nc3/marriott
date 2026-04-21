<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class IndexTransactionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['nullable', 'in:cash,gcash,bank_transfer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'export_range' => ['nullable', 'in:this_week,this_month,all_time'],
        ];
    }
}
