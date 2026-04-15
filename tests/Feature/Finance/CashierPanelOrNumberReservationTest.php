<?php

use App\Models\OrNumberReservation;
use App\Models\OrNumberSequence;
use App\Models\User;
use App\Services\Finance\OrNumberReservationService;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->service = new OrNumberReservationService;
    $this->now = Carbon::parse('2026-04-15 09:00:00');
});

test('cashier can reserve next available OR number', function () {
    $cashier = User::factory()->finance()->create();

    $reservation = $this->service->reserveForUser($cashier->id, $this->now);

    expect($reservation)->toBeInstanceOf(OrNumberReservation::class);
    expect($reservation->or_number)->toBe('OR-2026-0001');
    expect($reservation->reserved_by)->toBe($cashier->id);
    expect($reservation->expires_at?->equalTo($this->now->copy()->addMinutes(2)))->toBeTrue();
    expect(OrNumberSequence::query()->where('series_key', 'finance-or-2026')->where('year', 2026)->value('next_number'))->toBe(2);
});

test('released reservation becomes reusable', function () {
    $firstCashier = User::factory()->finance()->create();
    $secondCashier = User::factory()->finance()->create();

    $reservation = $this->service->reserveForUser($firstCashier->id, $this->now);

    $reservation->forceFill([
        'released_at' => $this->now->copy()->addMinutes(5),
    ])->save();

    $reusedReservation = $this->service->reserveForUser($secondCashier->id, $this->now->copy()->addMinutes(10));

    expect($reusedReservation->id)->toBe($reservation->id);
    expect($reusedReservation->or_number)->toBe('OR-2026-0001');
    expect($reusedReservation->reserved_by)->toBe($secondCashier->id);
    expect(OrNumberSequence::query()->where('series_key', 'finance-or-2026')->where('year', 2026)->value('next_number'))->toBe(2);
});

test('same cashier reuses active reservation', function () {
    $cashier = User::factory()->finance()->create();

    $firstReservation = $this->service->reserveForUser($cashier->id, $this->now);
    $secondReservation = $this->service->reserveForUser($cashier->id, $this->now->copy()->addMinutes(1));

    expect($secondReservation->id)->toBe($firstReservation->id);
    expect($secondReservation->token)->toBe($firstReservation->token);
    expect($secondReservation->or_number)->toBe('OR-2026-0001');
});

test('released reservation is not reused across a different year series', function () {
    $firstCashier = User::factory()->finance()->create();
    $secondCashier = User::factory()->finance()->create();

    $reservation = $this->service->reserveForUser($firstCashier->id, $this->now);

    $reservation->forceFill([
        'released_at' => $this->now->copy()->addMinute(),
    ])->save();

    $nextYearReservation = $this->service->reserveForUser(
        $secondCashier->id,
        Carbon::parse('2027-01-03 10:00:00'),
    );

    expect($nextYearReservation->id)->not->toBe($reservation->id);
    expect($nextYearReservation->or_number)->toBe('OR-2027-0001');
    expect(OrNumberSequence::query()->where('series_key', 'finance-or-2027')->where('year', 2027)->value('next_number'))->toBe(2);
});
