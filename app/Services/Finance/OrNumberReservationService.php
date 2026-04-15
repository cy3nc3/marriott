<?php

namespace App\Services\Finance;

use App\Models\OrNumberReservation;
use App\Models\OrNumberSequence;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrNumberReservationService
{
    private const EXPIRATION_MINUTES = 2;

    public function reserveForUser(int $userId, Carbon $now): OrNumberReservation
    {
        $year = (int) $now->year;
        $seriesKey = $this->seriesKey($year);
        $prefix = $this->prefix();

        return DB::transaction(function () use ($userId, $now, $seriesKey, $prefix, $year): OrNumberReservation {
            $sequence = $this->lockSequence($seriesKey, $prefix, $year);

            $activeReservation = OrNumberReservation::query()
                ->where('series_key', $seriesKey)
                ->where('reserved_by', $userId)
                ->whereNull('used_at')
                ->whereNull('released_at')
                ->where('expires_at', '>', $now)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($activeReservation !== null) {
                return $activeReservation;
            }

            $usedOrNumbers = $this->usedOrNumbers($prefix, $year);

            $reusableReservation = OrNumberReservation::query()
                ->where('series_key', $seriesKey)
                ->whereNull('used_at')
                ->where(function ($query) use ($now): void {
                    $query->whereNotNull('released_at')
                        ->orWhere('expires_at', '<=', $now);
                })
                ->lockForUpdate()
                ->get()
                ->sortBy(fn (OrNumberReservation $reservation): int => $this->extractNumber($reservation->or_number, $prefix, $year))
                ->first(fn (OrNumberReservation $reservation): bool => ! $usedOrNumbers->contains($reservation->or_number));

            if ($reusableReservation !== null) {
                return OrNumberReservation::query()->create([
                    'token' => (string) Str::uuid(),
                    'series_key' => $seriesKey,
                    'or_number' => $reusableReservation->or_number,
                    'reserved_by' => $userId,
                    'reserved_at' => $now,
                    'expires_at' => $now->copy()->addMinutes(self::EXPIRATION_MINUTES),
                ]);
            }

            $allocatedNumber = max(
                (int) $sequence->next_number,
                $this->highestUsedNumber($usedOrNumbers, $prefix, $year) + 1,
            );

            $sequence->forceFill([
                'next_number' => $allocatedNumber + 1,
            ])->save();

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

    public function releaseForUser(string $token, int $userId, Carbon $now): ?OrNumberReservation
    {
        return DB::transaction(function () use ($token, $userId, $now): ?OrNumberReservation {
            $reservation = OrNumberReservation::query()
                ->where('token', $token)
                ->where('reserved_by', $userId)
                ->whereNull('used_at')
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            if ($reservation === null) {
                return null;
            }

            $reservation->forceFill([
                'released_at' => $now,
            ])->save();

            return $reservation;
        });
    }

    private function lockSequence(string $seriesKey, string $prefix, int $year): OrNumberSequence
    {
        $sequence = OrNumberSequence::query()
            ->where('series_key', $seriesKey)
            ->lockForUpdate()
            ->first();

        if ($sequence !== null) {
            return $sequence;
        }

        try {
            return OrNumberSequence::query()->create([
                'series_key' => $seriesKey,
                'prefix' => $prefix,
                'year' => $year,
                'next_number' => 1,
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            return OrNumberSequence::query()
                ->where('series_key', $seriesKey)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }

    private function seriesKey(int $year): string
    {
        return sprintf('finance-or-%d', $year);
    }

    private function prefix(): string
    {
        return 'OR';
    }

    private function buildOrNumber(string $prefix, int $year, int $number): string
    {
        return sprintf('%s-%d-%04d', $prefix, $year, $number);
    }

    private function usedOrNumbers(string $prefix, int $year): Collection
    {
        return Transaction::query()
            ->where('or_number', 'like', sprintf('%s-%d-%%', $prefix, $year))
            ->pluck('or_number');
    }

    private function highestUsedNumber(Collection $usedOrNumbers, string $prefix, int $year): int
    {
        return (int) $usedOrNumbers
            ->map(fn (string $orNumber): int => $this->extractHighestUsedNumber($orNumber, $prefix, $year))
            ->max();
    }

    private function extractNumber(string $orNumber, string $prefix, int $year): int
    {
        $pattern = sprintf('/^%s-%d-(\d+)$/', preg_quote($prefix, '/'), $year);

        if (preg_match($pattern, $orNumber, $matches) !== 1) {
            return PHP_INT_MAX;
        }

        return (int) $matches[1];
    }

    private function extractHighestUsedNumber(string $orNumber, string $prefix, int $year): int
    {
        $pattern = sprintf('/^%s-%d-(\d+)$/', preg_quote($prefix, '/'), $year);

        if (preg_match($pattern, $orNumber, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }
}
