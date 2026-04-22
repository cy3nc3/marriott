<?php

namespace App\Services\Imports;

class FinanceReconciliationService
{
    public function __construct(private ValueParser $valueParser) {}

    /**
     * Reconcile imported dues and payments against an expected delta.
     *
     * @param  array<int, array<string, mixed>|int|float>  $importDues
     * @param  array<int, array<string, mixed>|int|float>  $importPayments
     * @return array{net: float, expected_delta: float, valid: bool}
     */
    public function reconcile(array $importDues, array $importPayments, float $expectedDelta): array
    {
        $net = round($this->sumAmounts($importDues) - $this->sumAmounts($importPayments), 2);
        $expectedDelta = round($expectedDelta, 2);

        return [
            'net' => $net,
            'expected_delta' => $expectedDelta,
            'valid' => abs($net - $expectedDelta) < 0.01,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>|int|float>  $rows
     */
    private function sumAmounts(array $rows): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = $this->resolveAmount($row);
            if ($amount === null) {
                continue;
            }

            $total += $amount;
        }

        return round($total, 2);
    }

    private function resolveAmount(mixed $row): ?float
    {
        if (is_int($row) || is_float($row)) {
            return round((float) $row, 2);
        }

        if (! is_array($row)) {
            return null;
        }

        foreach (['amount', 'amount_due', 'due_amount', 'total_amount', 'payment_amount', 'installment_amount'] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $parsedAmount = $this->valueParser->parseDecimal($row[$key]);
            if ($parsedAmount !== null) {
                return round($parsedAmount, 2);
            }
        }

        return null;
    }
}
