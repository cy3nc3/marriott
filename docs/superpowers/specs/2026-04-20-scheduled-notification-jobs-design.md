# Scheduled Notification Jobs Design

## Goal

Replace minute-polling business reminder commands with a reconciliation-based scheduling layer that can schedule reminders ahead of time, then cancel, supersede, or skip them cleanly as business state changes.

The new design must cover business-timed reminder workflows across the system when that model is a better fit than cron-triggered evaluation:

- finance due reminders
- grade deadline reminders
- announcement event reminders

Operational jobs such as backups, maintenance, cleanup, imports, and exports remain outside this system unless they later prove to need the same cancellation/rescheduling semantics.

## Context

The current system uses Laravel scheduler entries that run reminder commands every minute for finance and grading, then decide inside the command whether the current minute matches the configured send time. Announcement event reminders run daily at a fixed time and compute recipients at runtime.

That works on a traditional server, but it couples delivery timing to polling and makes the following behaviors indirect:

- cancel a future reminder when the source condition is resolved
- reschedule future reminders when a rule or deadline changes
- disable future reminders when automation is turned off
- keep an auditable record of what was planned, canceled, superseded, skipped, or dispatched

The desired behavior is that reminders are scheduled in advance but remain adjustable as the situation changes. For example:

- a finance reminder for one due schedule should disappear if that due schedule gets fully paid, while unrelated due reminders remain
- a grade reminder should move when the submission deadline changes
- a grade reminder should disappear when all relevant submissions are complete
- automation toggles should invalidate pending reminders instead of allowing them to silently fire later

## Recommended Approach

Use a central scheduled notification layer built around explicit scheduled records, planner/reconciler services, and a lightweight dispatcher.

This introduces a new scheduling lifecycle separate from the actual send work:

- `pending`: a reminder is scheduled for a future `run_at`
- `canceled`: the reminder became invalid before dispatch
- `superseded`: an older reminder was replaced by a newer schedule version
- `dispatched`: the actual send job ran or was handed off successfully
- `skipped`: the reminder reached dispatch time but current-state validation said not to send
- `failed`: an unexpected send failure occurred

This approach is preferred over pure delayed queue jobs because the product requirements depend on routine cancellation and rescheduling as business state changes. Pure delayed jobs can model exact future execution, but they make auditability, invalidation, and rescheduling harder once multiple reminder types and mutable business state are involved.

## Scope Split

### Included

The new system should cover business-timed jobs whose purpose is "send or trigger something later if it still makes sense at that time."

Initial reminder types:

- finance due reminders
- grade deadline reminders
- announcement event reminders

### Excluded

The following should remain on standard cron / scheduler / ordinary queue patterns for now:

- backups
- maintenance tasks
- cleanup tasks
- imports and exports started by a user action
- health checks
- non-business housekeeping jobs

These jobs usually do not need first-class cancellation/rescheduling based on changing business state, so forcing them into the new scheduler would add complexity without benefit.

## Architecture

### Core pieces

The design introduces four layers:

1. **Scheduled notification records**
   - A central database table stores future reminder intents.
   - This is the source of truth for what should happen in the future.

2. **Planner / reconciler services**
   - One planner per reminder type computes which future reminders should exist right now.
   - Planners create missing records and cancel or supersede obsolete ones.

3. **Dispatcher**
   - A lightweight command runs frequently and claims due `pending` records where `run_at <= now()`.
   - It performs final validation against current state, then either dispatches the actual send work or marks the record skipped.

4. **Concrete send jobs**
   - These jobs perform the final action, such as creating the announcement or resetting announcement reads.
   - They no longer decide future timing.

### Why keep a dispatcher at all

The system still benefits from a small frequent dispatcher because:

- it centralizes locking and due-record claiming
- it allows final validation at execution time
- it avoids trying to mutate queue payloads already enqueued far in advance

This is no longer business polling by rule. It is infrastructure-level dispatch of explicit scheduled records.

## Data Model

Use one central table rather than one scheduling table per reminder type.

### `scheduled_notification_jobs`

Purpose: stores future reminder intents and their reconciliation lifecycle.

Proposed fields:

- `id`
- `type`
- `status`
- `run_at`
- `dedupe_key`
- `group_key`
- `subject_type`
- `subject_id`
- `recipient_type` nullable
- `recipient_id` nullable
- `payload` nullable JSON
- `planned_by_type` nullable
- `planned_by_id` nullable
- `dispatched_at` nullable
- `canceled_at` nullable
- `skip_reason` nullable
- `failure_reason` nullable
- `created_at`
- `updated_at`

Suggested indexes and constraints:

- unique index on `dedupe_key`
- index on `status`, `run_at`
- index on `type`, `status`
- index on `group_key`
- index on `subject_type`, `subject_id`
- index on `recipient_type`, `recipient_id`

### Key semantics

#### `dedupe_key`

Uniquely identifies one exact intended future dispatch.

Examples:

- finance reminder for one rule + one billing schedule + one target run timestamp
- grade reminder for one academic year + quarter + phase + deadline version
- announcement event reminder for one announcement + one user + one phase + one target run timestamp

This enforces idempotent reconciliation and prevents accidental duplicate scheduling.

#### `group_key`

Groups related reminder rows that should be reconciled together.

Examples:

- all pending reminders driven by one finance rule
- all pending reminders for one academic year + quarter deadline
- all pending event reminders for one announcement

This makes targeted superseding and cancellation easier when one configuration change invalidates a whole family of reminders.

#### `payload`

Stores lightweight render or context data if useful, but should not become the sole source of truth for business validity. Final dispatch still reloads current state before sending.

## Reminder Type Rules

### Finance due reminders

#### Current intent

Finance reminders are driven by:

- active reminder rules
- configured automation enabled flag
- configured send time
- unpaid or partially paid billing schedules
- active parent recipients

#### Scheduling model

For each active finance reminder rule and each matching billing schedule, create one pending scheduled notification for the configured reminder send time on the calculated reminder date.

The reminder date is derived from:

- billing schedule due date
- `days_before_due`
- configured send time

#### Cancellation / superseding rules

- if a billing schedule becomes fully paid, cancel only that schedule's pending finance reminders
- if a finance reminder rule changes, supersede pending reminders that no longer match and create replacements
- if a rule is deleted, cancel its pending reminders
- if finance reminder automation is disabled, cancel all pending finance reminder jobs
- if finance send time changes, supersede all still-pending finance reminder jobs and recreate them at the new time

#### Dispatch-time validation

At dispatch time, the job must still verify:

- the billing schedule exists
- the billing schedule remains unpaid or partially paid
- the outstanding amount is still greater than zero
- active parent recipients still exist
- finance reminder automation is still enabled

If any validation fails, mark the scheduled job `skipped` with a reason instead of sending.

### Grade deadline reminders

#### Current intent

Grade reminders are driven by:

- configured submission deadline per academic year and quarter
- configured automation enabled flag
- configured reminder send time
- pending teacher submissions for that academic year and quarter

Two phases currently matter:

- one day before the deadline
- day of the deadline

#### Scheduling model

For each configured academic year + quarter deadline, schedule pending reminder jobs for the supported phases at the configured send time.

Each scheduled row should represent one reminder phase for one academic year + quarter + deadline version.

#### Cancellation / superseding rules

- if the deadline changes, supersede pending rows linked to the old deadline version and create replacements for the new deadline
- if all relevant grade submissions are complete before dispatch, cancel or skip the pending grade reminder jobs
- if reminder automation is disabled, cancel all pending grade reminder jobs
- if the configured send time changes, supersede pending grade reminder jobs and recreate them at the new time

#### Dispatch-time validation

At dispatch time, the job must still verify:

- the deadline still exists and still matches the schedule version
- reminder automation is enabled
- pending teacher submissions still exist for that academic year and quarter

If no pending teacher submissions remain, mark the job `skipped` instead of sending.

### Announcement event reminders

#### Current intent

Event reminders are driven by:

- an active event announcement
- response deadline or event start time
- recipient list
- unresolved recipient state
- event publish / expiry / cancellation state

Current phases:

- one day before
- day of

#### Scheduling model

For each recipient who should receive an event reminder, create pending scheduled rows for the applicable phases.

Scheduling should use the event's reference point:

- `response_deadline_at` when present
- otherwise `event_starts_at`

#### Cancellation / superseding rules

- if the event is canceled, cancel all pending event reminder jobs
- if event timing changes, supersede pending jobs and recreate them
- if recipient membership changes, cancel obsolete recipient jobs and create new ones
- if a recipient responds before the reminder sends, cancel that recipient's future event reminder jobs
- if publish or expiry state makes the event no longer valid for reminders, cancel affected pending jobs

#### Dispatch-time validation

At dispatch time, the job must still verify:

- the announcement still exists and is active
- the event is not canceled
- the event is published and not expired
- the recipient still belongs to the intended audience
- the recipient still has not responded

If any of those checks fail, mark the row `skipped`.

## Reconciliation Flow

The planner for each reminder type computes the exact set of reminder rows that should exist now.

General algorithm:

1. Compute desired reminder intents from current business state.
2. Load current pending rows for the relevant `group_key`.
3. Compare desired vs current by `dedupe_key`.
4. Create rows that are missing.
5. Leave still-valid rows unchanged.
6. Mark rows that are no longer desired as `canceled` or `superseded`.

### Trigger points

#### Finance

- finance reminder rule create
- finance reminder rule update
- finance reminder rule delete
- finance reminder automation settings update
- billing schedule payment state changes

#### Grading

- deadline create / update
- grade reminder automation settings update
- grade submission status changes

#### Announcement events

- event announcement create
- event announcement update
- event cancellation
- recipient list changes
- event response create / update

## Dispatcher Flow

The dispatcher is responsible only for due pending rows.

General behavior:

1. Find `pending` rows with `run_at <= now()`.
2. Claim them transactionally to avoid double handling.
3. Re-validate current state using the reminder-type validator.
4. If invalid, mark `skipped` and write the reason.
5. If valid, dispatch the concrete send job.
6. Mark the row `dispatched` when handoff or send completes successfully.
7. Mark `failed` if an unexpected send failure occurs.

The dispatcher should run frequently enough to keep reminder timing tight on a normal server deployment. It no longer needs separate per-reminder-type scheduler entries for runtime date math.

## Concrete Send Jobs

Each reminder type should have a focused send job:

- finance due reminder send job
- grade deadline reminder send job
- announcement event reminder send job

These jobs:

- take a scheduled notification row or its ID
- assume timing has already been determined
- perform only the actual send / announcement creation / reminder side effect
- write any reminder-specific dispatch records if those records still serve useful analytics or history purposes

Existing dispatch-history tables can stay if they add value, but they should no longer be the primary mechanism for future scheduling.

## Cancellation And Superseding Policy

The system should treat cancellation and superseding as ordinary lifecycle behavior.

### Cancellation

Use `canceled` when the reminder is intentionally invalidated before dispatch and no replacement is required.

Examples:

- a due amount is fully paid
- automation is disabled
- an event is canceled

### Superseding

Use `superseded` when an older reminder plan is replaced by a newer one.

Examples:

- configured send time changes
- a grade deadline changes
- an event deadline changes

This preserves auditability of what was originally planned without leaving misleading `canceled` semantics for every ordinary schedule change.

## Error Handling

The system should expose clear reasons for non-delivery:

- `skipped_paid`
- `skipped_no_pending_teachers`
- `skipped_event_responded`
- `skipped_automation_disabled`
- `skipped_subject_missing`
- `failed_send_exception`

The implementation must define a stable application-level reason enum or constant set, and the stored reason must distinguish "no longer valid" from "unexpected failure."

The system should not silently dispatch a fallback or substitute reminder when the original scheduled reminder becomes invalid.

## Testing Strategy

Add focused feature and service tests that cover:

- planner creates the expected scheduled rows
- planner reconciliation is idempotent
- changing a finance rule supersedes or cancels only the correct pending rows
- paying one billing schedule cancels only that schedule's pending finance reminders
- changing grade deadlines recreates the correct future grade reminder rows
- completing all relevant grade submissions cancels or skips future grade reminder rows
- disabling automation cancels all pending rows of that reminder type
- event response submission cancels that recipient's future event reminder rows
- dispatcher claims due rows once
- dispatcher skips invalid rows with a reason
- dispatcher dispatches valid rows exactly once
- duplicate scheduling is blocked by `dedupe_key`

The send jobs themselves can be tested more lightly because correctness should mostly live in planning, reconciliation, and final validation.

## Implementation Boundaries

The implementation should stay aligned with the current Laravel structure:

- add one new central scheduling subsystem rather than rewriting unrelated finance, grading, or announcement modules wholesale
- keep existing reminder generation content logic where practical, but move future-timing concerns out of the commands
- remove or shrink minute-polling command behavior once the new scheduler is in place
- keep operational cron-driven jobs outside this system unless explicitly migrated later

## Rollout Plan

Implement in phases:

1. add central scheduled notification schema and model
2. implement finance planner + dispatcher + send job
3. migrate grade reminders onto the new layer
4. migrate announcement event reminders onto the new layer
5. remove obsolete polling behavior and legacy schedule entries

This keeps blast radius smaller while proving the model on one reminder type before the full migration.

## Open Decisions Resolved

These decisions are considered final for implementation:

- Business-timed reminders should use explicit scheduled records plus reconciliation.
- Operational and housekeeping jobs stay on standard scheduler / queue patterns.
- Future reminders must remain adjustable as source state changes.
- Final dispatch must re-validate current business state before sending.
- Cancellation and superseding are first-class lifecycle states, not special cases.
