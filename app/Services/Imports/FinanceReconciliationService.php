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
        $net = round($this->sumAmounts($importDues, true) - $this->sumAmounts($importPayments, false), 2);
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
    private function sumAmounts(array $rows, bool $isDueContext): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = $this->resolveAmount($row, $isDueContext);
            if ($amount === null) {
                continue;
            }

            $total += $amount;
        }

        return round($total, 2);
    }

    private function resolveAmount(mixed $row, bool $isDueContext): ?float
    {
        if (is_int($row) || is_float($row)) {
            return round((float) $row, 2);
        }

        if (! is_array($row)) {
            return null;
        }

        $aliasPriority = $isDueContext
            ? ['amount_due', 'due_amount', 'installment_amount']
            : ['amount', 'payment_amount', 'total_amount', 'amount_due', 'due_amount', 'installment_amount'];

        foreach ($aliasPriority as $key) {
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
