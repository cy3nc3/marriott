<?php

namespace App\Services\Finance;

use App\Models\OrNumberReservation;
use App\Models\OrNumberSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrNumberReservationService
{
    private const EXPIRATION_MINUTES = 15;

    public function reserveForUser(int $userId, Carbon $now): OrNumberReservation
    {
        $seriesKey = $this->seriesKey();
        $prefix = $this->prefix();
        $year = (int) $now->year;

        return DB::transaction(function () use ($userId, $now, $seriesKey, $prefix, $year): OrNumberReservation {
            $activeReservation = OrNumberReservation::query()
                ->where('series_key', $seriesKey)
                ->where('reserved_by', $userId)
                ->whereNull('used_at')
                ->whereNull('released_at')
                ->where('expires_at', '>', $now)
                ->latest('id')
                ->first();

            if ($activeReservation !== null) {
                return $activeReservation;
            }

            $reusableReservation = OrNumberReservation::query()
                ->where('series_key', $seriesKey)
                ->whereNull('used_at')
                ->where(function ($query) use ($now): void {
                    $query->whereNotNull('released_at')
                        ->orWhere('expires_at', '<=', $now);
                })
                ->orderBy('or_number')
                ->lockForUpdate()
                ->first();

            if ($reusableReservation !== null) {
                $reusableReservation->forceFill([
                    'token' => (string) Str::uuid(),
                    'reserved_by' => $userId,
                    'reserved_at' => $now,
                    'expires_at' => $now->copy()->addMinutes(self::EXPIRATION_MINUTES),
                    'released_at' => null,
                    'used_at' => null,
                ])->save();

                return $reusableReservation;
            }

            $sequence = OrNumberSequence::query()
                ->where('series_key', $seriesKey)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $allocatedNumber = 1;

                OrNumberSequence::query()->create([
                    'series_key' => $seriesKey,
                    'prefix' => $prefix,
                    'year' => $year,
                    'next_number' => 2,
                ]);
            } else {
                $allocatedNumber = (int) $sequence->next_number;

                $sequence->forceFill([
                    'next_number' => $allocatedNumber + 1,
                ])->save();
            }

            return OrNumberReservation::query()->create([
                'token' => (string) Str::uuid(),
                'series_key' => $seriesKey,
                'or_number' => $this->buildOrNumber($prefix, $year, $allocatedNumber),
                'reserved_by' => $userId,
                'reserved_at' => $now,
                'expires_at' => $now->copy()->addMinutes(self::EXPIRATION_MINUTES),
            ]);
        });
    }

    private function seriesKey(): string
    {
        return 'finance-or';
    }

    private function prefix(): string
    {
        return 'OR';
    }

    private function buildOrNumber(string $prefix, int $year, int $number): string
    {
        return sprintf('%s-%d-%04d', $prefix, $year, $number);
    }
}
