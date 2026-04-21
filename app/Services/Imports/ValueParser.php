<?php

namespace App\Services\Imports;

use DateTimeImmutable;
use DateTimeInterface;

class ValueParser
{
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

    public function parseDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        $formats = [
            '!Y-m-d',
            '!Y/m/d',
            '!Y.m.d',
            '!m/d/Y',
            '!n/j/Y',
            '!m/d/y',
            '!n/j/y',
            '!F j, Y',
            '!M j, Y',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $normalized);
            if ($date === false) {
                continue;
            }

            $errors = DateTimeImmutable::getLastErrors();
            if ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                continue;
            }

            $expectedFormat = str_starts_with($format, '!') ? substr($format, 1) : $format;
            if ($date->format($expectedFormat) !== $normalized) {
                continue;
            }

            return $date->format('Y-m-d');
        }

        return null;
    }
}
