<?php

namespace App\Services\Imports;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class DuplicateEngine
{
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

        $paymentDate = $this->normalizeDate($this->firstAvailable($payment, [
            'payment_date',
            'date',
            'transaction_date',
            'posted_at',
        ]));
        $amount = $this->normalizeAmount($this->firstAvailable($payment, [
            'amount',
            'payment_amount',
            'total_amount',
        ]));
        $referenceNo = $this->normalizeString($this->firstAvailable($payment, [
            'reference_no',
            'reference',
            'reference_number',
        ]));

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

    private function normalizeLearnerReferenceNumber(mixed $value): ?string
    {
        $normalized = preg_replace('/\D/', '', (string) $value) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->startOfDay()->toDateString();
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($normalized)->startOfDay()->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
