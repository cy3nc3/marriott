<?php

namespace App\Services\Imports;

class DuplicateEngine
{
    public function __construct(private ValueParser $valueParser) {}

    /**
     * Build a stable duplicate key for imported payment rows.
     *
     * Primary key:
     * - lrn + or_number when both are present
     *
     * Fallback key:
     * - lrn + payment_date + amount + reference_no
     *
     * @param  array<string, mixed>  $payment
     */
    public function paymentDuplicateKey(array $payment): ?string
    {
        $lrn = $this->normalizeLearnerReferenceNumber($this->firstAvailable($payment, [
            'lrn',
            'learner_reference_number',
        ]));
        if ($lrn === null) {
            return null;
        }

        $orNumber = $this->normalizeString($this->firstAvailable($payment, [
            'or_number',
            'or_no',
            'receipt_no',
            'receipt_number',
        ]));
        if ($orNumber !== null) {
            return $this->buildKey([$lrn, $orNumber]);
        }

        $paymentDate = $this->firstParseableDate($payment, [
            'payment_date',
            'date',
            'transaction_date',
            'posted_at',
        ]);
        $amount = $this->firstParseableAmount($payment, [
            'amount',
            'payment_amount',
            'total_amount',
        ]);
        $referenceNo = $this->firstParseableString($payment, [
            'reference_no',
            'reference',
            'reference_number',
        ]);

        if ($paymentDate === null || $amount === null || $referenceNo === null) {
            return null;
        }

        return $this->buildKey([$lrn, $paymentDate, $amount, $referenceNo]);
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function buildKey(array $parts): string
    {
        return implode('|', $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstAvailable(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];
            if (trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstParseableDate(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];
            $parsedDate = $this->valueParser->parseDate($value);
            if ($parsedDate !== null) {
                return $parsedDate->toDateString();
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstParseableAmount(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];
            $parsedAmount = $this->valueParser->parseDecimal($value);
            if ($parsedAmount !== null) {
                return number_format($parsedAmount, 2, '.', '');
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstParseableString(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];
            $normalized = $this->valueParser->normalizeString($value);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeLearnerReferenceNumber(mixed $value): ?string
    {
        $normalized = preg_replace('/\D/', '', (string) $value) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeString(mixed $value): ?string
    {
        return $this->valueParser->normalizeString($value);
    }
}
