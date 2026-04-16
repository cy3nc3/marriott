<?php

namespace App\Services\Finance;

use App\Models\OrNumberReservation;
use App\Models\OrNumberSequence;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrNumberReservationService
{
    private const EXPIRATION_MINUTES = 2;

    public function reserveForUser(int $userId, CarbonInterface $now): OrNumberReservation
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

    public function releaseForUser(string $token, int $userId, CarbonInterface $now): ?OrNumberReservation
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

    public function resolveForPosting(
        int $userId,
        ?string $reservationToken,
        string $submittedOrNumber,
        CarbonInterface $now,
    ): OrNumberReservation {
        $trimmedOrNumber = trim($submittedOrNumber);
        $activeReservation = null;

        if ($reservationToken !== null && trim($reservationToken) !== '') {
            $activeReservation = OrNumberReservation::query()
                ->where('token', $reservationToken)
                ->where('reserved_by', $userId)
                ->whereNull('used_at')
                ->whereNull('released_at')
                ->where('expires_at', '>', $now)
                ->lockForUpdate()
                ->first();

            if ($activeReservation === null) {
                throw ValidationException::withMessages([
                    'or_number' => 'The reserved OR number is no longer available. Please reserve a new one.',
                ]);
            }

            if ($activeReservation->or_number === $trimmedOrNumber) {
                return $activeReservation;
            }
        }

        $this->assertOrNumberAvailable($trimmedOrNumber, $userId, $now);

        if ($activeReservation !== null) {
            $activeReservation->forceFill([
                'released_at' => $now,
            ])->save();
        }

        return OrNumberReservation::query()->create([
            'token' => (string) Str::uuid(),
            'series_key' => $this->seriesKeyForOrNumber($trimmedOrNumber, $now),
            'or_number' => $trimmedOrNumber,
            'reserved_by' => $userId,
            'reserved_at' => $now,
            'expires_at' => $now->copy()->addMinutes(self::EXPIRATION_MINUTES),
        ]);
    }

    public function markAsUsed(OrNumberReservation $reservation, int $transactionId, CarbonInterface $now): void
    {
        $reservation->forceFill([
            'used_at' => $now,
            'transaction_id' => $transactionId,
        ])->save();
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

    private function assertOrNumberAvailable(string $orNumber, int $userId, CarbonInterface $now): void
    {
        if (Transaction::query()->where('or_number', $orNumber)->exists()) {
            throw ValidationException::withMessages([
                'or_number' => 'This OR number is already used.',
            ]);
        }

        $reservedByAnotherCashier = OrNumberReservation::query()
            ->where('or_number', $orNumber)
            ->whereNull('used_at')
            ->whereNull('released_at')
            ->where('expires_at', '>', $now)
            ->where('reserved_by', '!=', $userId)
            ->lockForUpdate()
            ->exists();

        if ($reservedByAnotherCashier) {
            throw ValidationException::withMessages([
                'or_number' => 'This OR number is currently reserved by another cashier.',
            ]);
        }
    }

    private function seriesKeyForOrNumber(string $orNumber, CarbonInterface $now): string
    {
        if (preg_match('/^OR-(\d{4})-\d+$/', $orNumber, $matches) === 1) {
            return $this->seriesKey((int) $matches[1]);
        }

        return $this->seriesKey((int) $now->year);
    }
}
