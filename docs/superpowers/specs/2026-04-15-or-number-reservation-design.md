# OR Number Reservation Design

## Goal

Add auto-generated, prefilled OR numbers to the cashier posting flow while preserving manual override and keeping a single school-wide OR sequence safe under simultaneous cashier activity.

## Context

The current cashier flow requires the cashier to type `transactions.or_number` manually. The value is only validated as unique at submit time. That works for single-user entry, but it fails as soon as multiple cashiers operate concurrently because a naive "latest OR plus one" prefill can produce duplicate suggestions.

The new design must satisfy these constraints:

- One shared OR sequence for the whole school.
- Visible format stays aligned with the current examples such as `OR-2026-0001`.
- The OR number field remains editable.
- An OR number is marked as used only when the transaction is successfully posted.
- If a transaction does not proceed, the OR number must become available again.
- The system should recover abandoned reservations automatically without manual cleanup.

## Recommended Approach

Use a dedicated OR number reservation layer instead of deriving the next OR number directly from the `transactions` table.

This introduces temporary reservation state that is separate from real posted transactions:

- `reserved`: a cashier is temporarily holding an OR number.
- `used`: the OR number was consumed by a successfully posted transaction.
- `released`: the cashier explicitly abandoned the reservation.
- `expired`: the reservation timed out and can be reused.

This approach is preferred because it serializes allocation safely for multiple cashiers, preserves auditability of reservation activity, and keeps finance records clean by avoiding draft or placeholder transactions.

## Data Model

### `or_number_sequences`

Purpose: stores the authoritative state for each OR series.

Proposed fields:

- `id`
- `series_key`
- `prefix`
- `year`
- `next_number`
- `created_at`
- `updated_at`

Notes:

- For the current requirement, there is one shared school-wide series per year.
- `series_key` can initially be the formatted year-based series identifier for the current OR format.
- `next_number` is the next candidate integer for reservation generation, not proof that all lower numbers are permanently consumed.

### `or_number_reservations`

Purpose: stores temporary and final reservation lifecycle records.

Proposed fields:

- `id`
- `series_key`
- `or_number`
- `reserved_by`
- `reserved_at`
- `expires_at`
- `released_at`
- `used_at`
- `transaction_id`
- `created_at`
- `updated_at`

Notes:

- `reserved_by` references the cashier who holds the reservation.
- `transaction_id` stays null until the OR number is actually used.
- Rows should be retained for audit purposes even after release or expiry.

### Existing `transactions`

The `transactions.or_number` column remains the final authoritative field for used OR numbers and keeps its uniqueness guarantee.

## OR Format

The visible OR number format remains aligned with current examples:

- `OR-2026-0001`

The year component continues to be embedded in the visible value. The format should be encapsulated in one formatter so the application does not duplicate OR-number string building in multiple places.

## Reservation Timeout

Use a two-minute reservation timeout.

Rationale:

- One minute is too short for normal cashier confirmation steps.
- Five minutes holds the shared sequence too long when a tab is abandoned.
- Two minutes is a practical balance between recovery speed and normal cashier workflow.

## Lifecycle Flow

### 1. Open Process Transaction dialog

When the cashier opens the posting dialog:

- the frontend calls a backend endpoint to reserve an OR number
- the backend determines the active series for the current year
- the backend allocates the next available OR number under database locking
- the backend records a reservation row with a two-minute expiry
- the backend returns the reserved OR number and reservation token

The returned OR number is shown as the prefilled value in the form.

### 2. Edit or keep the OR number

The cashier may either:

- keep the reserved OR number, or
- manually overwrite the value

If the cashier keeps the reserved value, the submit payload includes the reservation token.

If the cashier overwrites the value, the backend still validates the chosen OR number against:

- existing used OR numbers in `transactions`
- active reservations owned by other cashiers

Manual overrides remain allowed, but they are still subject to availability checks.

### 3. Cancel or close the dialog

When the cashier explicitly cancels or closes the dialog:

- the frontend calls a release endpoint
- the backend marks the reservation as released immediately

That OR number becomes available for reuse right away.

### 4. Abandonment, crash, or lost connection

If the browser cannot notify the server because the tab is abandoned, the device sleeps, or the connection is interrupted:

- the reservation is not released instantly
- the reservation becomes reusable after the two-minute timeout expires

The system should also attempt a best-effort release on page unload, but correctness must not depend on the client successfully sending that request.

### 5. Confirm and post transaction

When the cashier confirms the transaction:

- the backend runs the existing posting workflow inside a database transaction
- inside that same database transaction, the backend revalidates the OR number and reservation state
- the transaction row is created
- the reservation row is marked as used and linked to `transaction_id`

If any part of posting fails, the database transaction rolls back and the OR number is not marked as used.

## Allocation Rules

### Locking

Allocation must happen on the server under database locking. The application must never rely on a frontend-computed "latest OR plus one" value.

The sequence row should be locked during allocation so simultaneous cashiers cannot receive the same generated OR number.

### Reusing released or expired numbers

Allocation must choose the lowest currently available OR number in the active series.

That means released or expired reservations may be reused before moving forward to higher numbers. This preserves the requirement that numbers from abandoned transactions become available again.

### Advancing `next_number`

When the generated path allocates a brand-new number beyond all previously known numbers:

- reserve that number
- advance `next_number`

When the generated path reuses a released or expired number below the current `next_number`:

- reserve that number
- leave `next_number` unchanged

This keeps the forward cursor efficient without losing the ability to fill gaps.

## Manual Override Rules

Manual override remains enabled, with these rules:

- allowed if the OR number is not already used
- allowed if the OR number is not actively reserved by another cashier
- disallowed if it is already used by a posted transaction
- disallowed if another active reservation currently holds it

Manual override can target:

- a lower unused number that became available through release or expiry
- a higher unused number above the current sequence cursor

If a manual override uses a higher number than the current `next_number`, the sequence logic must ensure future generated allocations skip past that number to avoid later collisions.

## Same-Cashier Refresh Behavior

If the same cashier reopens or refreshes the dialog while their reservation is still active, the system should reuse the same reservation instead of issuing a new one.

This reduces needless churn and avoids artificial gaps caused by repeated open-close cycles from the same user.

## Reissue Scope

This design is limited to the cashier posting flow for new transactions.

The transaction reissue flow already accepts a manual replacement OR number and should remain unchanged for now. If needed later, the same reservation mechanism can be applied to reissue OR allocation as a separate enhancement.

## Error Handling

The system should expose clear outcomes for these cases:

- reservation expired before submit
- manually entered OR number is already used
- manually entered OR number is reserved by another cashier
- reservation token does not match the submitted OR number
- reservation token belongs to another cashier

The expected user experience is:

- ask the cashier to refresh or fetch a new OR number when the reservation expired
- preserve the rest of the transaction form state
- never silently consume a different OR number during final post

## Testing Strategy

Add focused feature tests that cover:

- generated reservations return distinct OR numbers for near-simultaneous requests
- released reservations become reusable
- expired reservations become reusable
- OR numbers are marked used only after a successful post
- failed posting does not consume the OR number
- manual override succeeds when the OR number is available
- manual override fails when the OR number is already used
- manual override fails when another cashier actively reserves the OR number
- same cashier can reuse an active reservation on reopen

Frontend behavior can be tested lightly because the backend owns the correctness guarantees.

## Implementation Boundaries

The implementation should stay within the existing finance structure:

- extend the finance cashier backend rather than introducing a separate subsystem
- keep the OR input in the current cashier modal and prefill it from a reservation endpoint
- preserve existing transaction posting behavior aside from OR-allocation changes
- avoid changing unrelated finance reporting screens unless required to surface validation or lifecycle outcomes

## Open Decisions Resolved

These decisions are considered final for implementation:

- shared school-wide OR sequence
- current `OR-YYYY-NNNN` style retained for now
- OR field remains manually editable
- OR number is marked used only after the transaction really posts
- explicit cancel releases immediately when possible
- abandoned reservations expire automatically after two minutes

