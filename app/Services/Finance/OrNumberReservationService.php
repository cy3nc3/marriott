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
        $usedOrNumbers = $this->usedOrNumbers($prefix, $year);

        return DB::transaction(function () use ($userId, $now, $seriesKey, $prefix, $year, $usedOrNumbers): OrNumberReservation {
            $sequence = $this->lockSequence($seriesKey, $prefix, $year, $usedOrNumbers);

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

            $reusableReservation = OrNumberReservation::query()
                ->where('series_key', $seriesKey)
                ->whereNull('used_at')
                ->where(function ($query) use ($now): void {
                    $query->whereNotNull('released_at')
                        ->orWhere('expires_at', '<=', $now);
                })
                ->when(
                    $usedOrNumbers->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('or_number', $usedOrNumbers->all())
                )
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

            $allocatedNumber = (int) $sequence->next_number;

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

    private function lockSequence(
        string $seriesKey,
        string $prefix,
        int $year,
        Collection $usedOrNumbers,
    ): OrNumberSequence {
        $sequence = OrNumberSequence::query()
            ->where('series_key', $seriesKey)
            ->lockForUpdate()
            ->first();

        if ($sequence !== null) {
            $nextAvailableNumber = max(
                (int) $sequence->next_number,
                $this->highestUsedNumber($usedOrNumbers, $prefix, $year) + 1,
            );

            if ($nextAvailableNumber !== (int) $sequence->next_number) {
                $sequence->forceFill([
                    'next_number' => $nextAvailableNumber,
                ])->save();
            }

            return $sequence;
        }

        $nextNumber = max(
            1,
            $this->highestUsedNumber($usedOrNumbers, $prefix, $year) + 1,
        );

        try {
            return OrNumberSequence::query()->create([
                'series_key' => $seriesKey,
                'prefix' => $prefix,
                'year' => $year,
                'next_number' => $nextNumber,
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            $sequence = OrNumberSequence::query()
                ->where('series_key', $seriesKey)
                ->lockForUpdate()
                ->firstOrFail();

            $nextAvailableNumber = max(
                (int) $sequence->next_number,
                $this->highestUsedNumber($usedOrNumbers, $prefix, $year) + 1,
            );

            if ($nextAvailableNumber !== (int) $sequence->next_number) {
                $sequence->forceFill([
                    'next_number' => $nextAvailableNumber,
                ])->save();
            }

            return $sequence;
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
        $pattern = sprintf('/^%s-%d-(\d{4,})$/', preg_quote($prefix, '/'), $year);

        return (int) $usedOrNumbers
            ->map(function (string $orNumber) use ($pattern): int {
                if (preg_match($pattern, $orNumber, $matches) !== 1) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max();
    }
}
