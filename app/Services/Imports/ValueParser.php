<?php

namespace App\Services\Imports;

use Carbon\CarbonImmutable;
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

    public function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($normalized)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
