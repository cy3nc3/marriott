<?php

namespace App\Services\Imports;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class ValueParser
{
    /**
     * @var array<int, string>
     */
    private array $dateFormats = [
        'Y-m-d',
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
        'm/d/Y',
        'm/d/y',
    ];

    public function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    public function parseDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value >= 0 ? round((float) $value, 2) : null;
        }

        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = preg_replace('/[^0-9.,-]/', '', $normalized) ?? '';
        if ($normalized === '') {
            return null;
        }

        if (! preg_match('/^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/', $normalized)) {
            return null;
        }

        $decimal = (float) str_replace(',', '', $normalized);

        return $decimal >= 0 ? round($decimal, 2) : null;
    }

    public function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            return $this->parseExcelSerialDate($value);
        }

        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        foreach ($this->dateFormats as $format) {
            try {
                $parsedDate = CarbonImmutable::createFromFormat($format, $normalized);
            } catch (\Throwable) {
                continue;
            }

            if ($parsedDate === false) {
                continue;
            }

            $errors = \DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                continue;
            }

            if ($parsedDate->format($format) !== $normalized) {
                continue;
            }

            return $parsedDate->startOfDay();
        }

        return null;
    }

    private function parseExcelSerialDate(int|float|string $value): ?CarbonImmutable
    {
        $serial = is_string($value) ? trim($value) : $value;
        if (! is_numeric($serial)) {
            return null;
        }

        $serialNumber = (float) $serial;
        if ($serialNumber < 0) {
            return null;
        }

        $days = (int) floor($serialNumber);

        return CarbonImmutable::create(1899, 12, 30)->addDays($days)->startOfDay();
    }
}
