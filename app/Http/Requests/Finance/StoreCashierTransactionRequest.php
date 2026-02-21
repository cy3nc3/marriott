<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCashierTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'or_number' => ['required', 'string', 'max:50', 'unique:transactions,or_number'],
            'payment_mode' => ['required', 'in:cash,gcash,bank_transfer'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'tendered_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:fee,inventory,custom'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'items.*.fee_id' => ['nullable', 'exists:fees,id'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = collect($this->input('items', []));

            $items->each(function (array $item, int $index) use ($validator) {
                $lineNumber = $index + 1;

                if (($item['type'] ?? null) === 'fee' && empty($item['fee_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.fee_id",
                        "Fee item is required for line {$lineNumber}."
                    );
                }

                if (($item['type'] ?? null) === 'inventory' && empty($item['inventory_item_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.inventory_item_id",
                        "Inventory item is required for line {$lineNumber}."
                    );
                }
            });

            $totalAmount = (float) $items->sum(function (array $item) {
                return (float) ($item['amount'] ?? 0);
            });

            if ($totalAmount <= 0) {
                $validator->errors()->add('items', 'Transaction must include at least one item with a valid amount.');

                return;
            }

            $tenderedAmount = (float) $this->input('tendered_amount', 0);
            if ($tenderedAmount < $totalAmount) {
                $validator->errors()->add('tendered_amount', 'Tendered amount must be at least the transaction total.');
            }
        });
    }
}
