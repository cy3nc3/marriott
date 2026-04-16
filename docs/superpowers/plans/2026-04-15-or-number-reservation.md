# OR Number Reservation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add shared, auto-generated OR number reservations to the cashier flow so multiple cashiers can work concurrently while only marking an OR number as used after a successful transaction post.

**Architecture:** Introduce a dedicated reservation layer in Laravel for year-based OR sequences, expose reservation/release endpoints for the cashier modal, and integrate final OR consumption into the existing cashier posting transaction. Keep `transactions.or_number` as the final source of truth while React manages reservation lifecycle and preserves manual override.

**Tech Stack:** Laravel 12, PHP 8.4, Pest 4, Inertia React v2, Wayfinder, Tailwind CSS, MySQL/SQLite test database

---

### Task 1: Build OR reservation backend primitives

**Files:**
- Create: `database/migrations/2026_04_15_000001_create_or_number_sequences_table.php`
- Create: `database/migrations/2026_04_15_000002_create_or_number_reservations_table.php`
- Create: `app/Models/OrNumberSequence.php`
- Create: `app/Models/OrNumberReservation.php`
- Create: `app/Services/Finance/OrNumberReservationService.php`
- Modify: `app/Models/Transaction.php`
- Test: `tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

- [ ] **Step 1: Write the failing reservation tests**

```php
<?php

use App\Models\User;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('cashier can reserve the next available OR number', function () {
    $response = $this->postJson('/finance/cashier-panel/or-number-reservations');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.or_number', 'OR-2026-0001')
        ->assertJsonPath('data.status', 'reserved');
});

test('released reservation becomes the next generated OR number again', function () {
    $first = $this->postJson('/finance/cashier-panel/or-number-reservations')->json('data');

    $this->deleteJson("/finance/cashier-panel/or-number-reservations/{$first['token']}")
        ->assertSuccessful();

    $second = $this->postJson('/finance/cashier-panel/or-number-reservations')
        ->assertSuccessful()
        ->json('data');

    expect($second['or_number'])->toBe($first['or_number']);
});

test('same cashier reuses their active reservation while it is still valid', function () {
    $first = $this->postJson('/finance/cashier-panel/or-number-reservations')
        ->assertSuccessful()
        ->json('data');

    $second = $this->postJson('/finance/cashier-panel/or-number-reservations')
        ->assertSuccessful()
        ->json('data');

    expect($second['token'])->toBe($first['token']);
    expect($second['or_number'])->toBe($first['or_number']);
});
```

- [ ] **Step 2: Run the reservation tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

Expected: FAIL because the reservation routes, models, tables, and service do not exist yet.

- [ ] **Step 3: Add the database tables and Eloquent models**

```php
Schema::create('or_number_sequences', function (Blueprint $table) {
    $table->id();
    $table->string('series_key')->unique();
    $table->string('prefix');
    $table->unsignedSmallInteger('year');
    $table->unsignedInteger('next_number')->default(1);
    $table->timestamps();
});

Schema::create('or_number_reservations', function (Blueprint $table) {
    $table->id();
    $table->uuid('token')->unique();
    $table->string('series_key');
    $table->string('or_number');
    $table->foreignId('reserved_by')->constrained('users')->cascadeOnDelete();
    $table->timestamp('reserved_at');
    $table->timestamp('expires_at');
    $table->timestamp('released_at')->nullable();
    $table->timestamp('used_at')->nullable();
    $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();

    $table->index(['series_key', 'or_number']);
    $table->index(['reserved_by', 'expires_at']);
});
```

```php
class OrNumberReservation extends Model
{
    protected $fillable = [
        'token',
        'series_key',
        'or_number',
        'reserved_by',
        'reserved_at',
        'expires_at',
        'released_at',
        'used_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 4: Implement `OrNumberReservationService`**

```php
public function reserveForUser(int $userId, CarbonInterface $now): OrNumberReservation
{
    return DB::transaction(function () use ($userId, $now): OrNumberReservation {
        $series = OrNumberSequence::query()->lockForUpdate()->firstOrCreate(
            ['series_key' => $this->seriesKey($now)],
            ['prefix' => 'OR', 'year' => (int) $now->format('Y'), 'next_number' => 1],
        );

        $activeReservation = OrNumberReservation::query()
            ->where('reserved_by', $userId)
            ->whereNull('used_at')
            ->whereNull('released_at')
            ->where('expires_at', '>', $now)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($activeReservation) {
            return $activeReservation;
        }

        $orNumber = $this->findNextAvailableOrNumber($series, $now);

        return OrNumberReservation::query()->create([
            'token' => (string) Str::uuid(),
            'series_key' => $series->series_key,
            'or_number' => $orNumber,
            'reserved_by' => $userId,
            'reserved_at' => $now,
            'expires_at' => $now->copy()->addMinutes(2),
        ]);
    });
}
```

- [ ] **Step 5: Run the reservation tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

Expected: PASS for reservation, release, and same-cashier reuse scenarios.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_04_15_000001_create_or_number_sequences_table.php database/migrations/2026_04_15_000002_create_or_number_reservations_table.php app/Models/OrNumberSequence.php app/Models/OrNumberReservation.php app/Services/Finance/OrNumberReservationService.php app/Models/Transaction.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php
git commit -m "feat: add OR number reservation backend"
```

### Task 2: Integrate reservation validation into cashier posting

**Files:**
- Modify: `app/Http/Controllers/Finance/CashierPanelController.php`
- Modify: `app/Http/Requests/Finance/StoreCashierTransactionRequest.php`
- Modify: `routes/roles/finance.php`
- Modify: `tests/Feature/Finance/CashierPanelTest.php`
- Test: `tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

- [ ] **Step 1: Write the failing posting tests**

```php
test('transaction posting marks the reserved OR number as used only after success', function () {
    $reservation = $this->postJson('/finance/cashier-panel/or-number-reservations')
        ->assertSuccessful()
        ->json('data');

    $this->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'reservation_token' => $reservation['token'],
        'or_number' => $reservation['or_number'],
        'payment_mode' => 'cash',
        'tendered_amount' => 3000,
        'items' => [
            ['type' => 'custom', 'description' => 'Enrollment Downpayment', 'amount' => 3000],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('transactions', ['or_number' => $reservation['or_number']]);
    $this->assertDatabaseHas('or_number_reservations', [
        'token' => $reservation['token'],
        'or_number' => $reservation['or_number'],
    ]);
});

test('posting failure does not consume the reserved OR number', function () {
    $reservation = $this->postJson('/finance/cashier-panel/or-number-reservations')
        ->assertSuccessful()
        ->json('data');

    $this->from('/finance/cashier-panel')->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'reservation_token' => $reservation['token'],
        'or_number' => $reservation['or_number'],
        'payment_mode' => 'cash',
        'tendered_amount' => 500,
        'items' => [
            ['type' => 'custom', 'description' => 'Enrollment Downpayment', 'amount' => 1000],
        ],
    ])->assertSessionHasErrors(['tendered_amount']);

    $this->assertDatabaseMissing('transactions', ['or_number' => $reservation['or_number']]);
});
```

- [ ] **Step 2: Run the posting tests to verify they fail**

Run: `php artisan test --compact --filter=reserved OR tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

Expected: FAIL because the post request does not yet understand reservation tokens or OR-reservation state.

- [ ] **Step 3: Add reservation-aware validation and controller actions**

```php
Route::post('/cashier-panel/or-number-reservations', [CashierPanelController::class, 'reserveOrNumber'])
    ->middleware('desktop_only')
    ->name('cashier_panel.reserve_or_number');

Route::delete('/cashier-panel/or-number-reservations/{token}', [CashierPanelController::class, 'releaseOrNumber'])
    ->middleware('desktop_only')
    ->name('cashier_panel.release_or_number');
```

```php
'reservation_token' => ['nullable', 'uuid'],
'or_number' => ['required', 'string', 'max:50'],
```

```php
$transaction = DB::transaction(function () use ($validated, $student, $academicYear, $items, $totalAmount) {
    $resolvedOrNumber = $this->orNumberReservationService->resolveForPosting(
        userId: (int) auth()->id(),
        reservationToken: $validated['reservation_token'] ?? null,
        submittedOrNumber: $validated['or_number'],
    );

    $transaction = Transaction::query()->create([
        'or_number' => $resolvedOrNumber->or_number,
        // existing fields...
    ]);

    $this->orNumberReservationService->markAsUsed(
        reservation: $resolvedOrNumber,
        transactionId: (int) $transaction->id,
    );

    return $transaction;
});
```

- [ ] **Step 4: Add validation cases for manual override**

```php
test('manual override fails when another cashier actively reserves the OR number', function () {
    $otherCashier = User::factory()->finance()->create();

    $reservation = $this->actingAs($otherCashier)
        ->postJson('/finance/cashier-panel/or-number-reservations')
        ->assertSuccessful()
        ->json('data');

    $this->actingAs($this->finance)
        ->from('/finance/cashier-panel')
        ->post('/finance/cashier-panel/transactions', [
            'student_id' => $student->id,
            'or_number' => $reservation['or_number'],
            'payment_mode' => 'cash',
            'tendered_amount' => 1000,
            'items' => [
                ['type' => 'custom', 'description' => 'Payment', 'amount' => 1000],
            ],
        ])->assertSessionHasErrors(['or_number']);
});
```

- [ ] **Step 5: Run the affected tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

Expected: PASS for existing cashier posting behavior plus the new OR reservation and override rules.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Finance/CashierPanelController.php app/Http/Requests/Finance/StoreCashierTransactionRequest.php routes/roles/finance.php tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php
git commit -m "feat: enforce OR reservation rules during posting"
```

### Task 3: Prefill and release OR reservations in the cashier modal

**Files:**
- Modify: `resources/js/pages/finance/cashier-panel/index.tsx`
- Modify: `resources/js/routes/finance/cashier_panel.ts` or generated Wayfinder output
- Test: `tests/Feature/Finance/CashierPanelTest.php`

- [ ] **Step 1: Write the failing page-prop and request lifecycle tests**

```php
test('cashier panel page includes reservation endpoints for the posting dialog', function () {
    $this->get('/finance/cashier-panel')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('or_number_reservation_timeout_seconds', 120)
        );
});
```

- [ ] **Step 2: Run the targeted test to verify it fails**

Run: `php artisan test --compact --filter=reservation\ endpoints tests/Feature/Finance/CashierPanelTest.php`

Expected: FAIL because the page does not yet expose reservation metadata and the frontend does not request reservations.

- [ ] **Step 3: Add reservation lifecycle handling in React**

```tsx
const [reservationToken, setReservationToken] = useState('');
const [reservedOrNumber, setReservedOrNumber] = useState('');

const reserveOrNumber = async () => {
    const response = await fetch(reserve_or_number().url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
    });

    const payload = await response.json();

    setReservationToken(payload.data.token);
    setReservedOrNumber(payload.data.or_number);
    transactionForm.setData((current) => ({
        ...current,
        reservation_token: payload.data.token,
        or_number: current.or_number.trim() === '' ? payload.data.or_number : current.or_number,
    }));
};
```

```tsx
const releaseReservation = async () => {
    if (reservationToken === '') {
        return;
    }

    await fetch(release_or_number(reservationToken).url, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
    });
};
```

- [ ] **Step 4: Regenerate Wayfinder routes if needed and run the affected tests**

Run: `php artisan wayfinder:generate --with-form --no-interaction`

Run: `php artisan test --compact tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

Expected: PASS with the process dialog reserving on open, releasing on cancel, preserving manual override, and keeping existing cashier workflow intact.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/finance/cashier-panel/index.tsx resources/js/routes/finance/cashier_panel.ts tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php
git commit -m "feat: prefill cashier OR numbers from reservations"
```

### Task 4: Final formatting and verification

**Files:**
- Modify: `app/Http/Controllers/Finance/CashierPanelController.php`
- Modify: `app/Http/Requests/Finance/StoreCashierTransactionRequest.php`
- Modify: `app/Models/Transaction.php`
- Modify: `resources/js/pages/finance/cashier-panel/index.tsx`
- Modify: `routes/roles/finance.php`
- Modify: `tests/Feature/Finance/CashierPanelTest.php`
- Create/Modify: `tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

- [ ] **Step 1: Run Laravel Pint on the changed PHP files**

Run: `vendor/bin/pint --dirty --format agent`

Expected: PASS with formatted PHP files and no syntax regressions.

- [ ] **Step 2: Run the full affected verification suite**

Run: `php artisan test --compact tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php`

Expected: PASS with all OR-reservation and cashier flow tests green.

- [ ] **Step 3: Run the frontend build if Wayfinder or TypeScript changed**

Run: `npm run build`

Expected: PASS with no TypeScript or Vite build errors.

- [ ] **Step 4: Commit the final cleanups if formatting changed tracked files**

```bash
git add app/Http/Controllers/Finance/CashierPanelController.php app/Http/Requests/Finance/StoreCashierTransactionRequest.php app/Models/Transaction.php resources/js/pages/finance/cashier-panel/index.tsx routes/roles/finance.php tests/Feature/Finance/CashierPanelTest.php tests/Feature/Finance/CashierPanelOrNumberReservationTest.php
git commit -m "chore: polish OR reservation implementation"
```
