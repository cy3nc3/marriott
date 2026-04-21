# Adaptive Student and Finance Import Design

**Date:** 2026-04-21  
**Status:** Draft for review

## Goal
Build a safe, flexible import system that accepts unpredictable school export files while preserving data integrity for:
- Student profiles (`LRN`, `Name`, `Contact Number`, `Guardian Name`, `Gender`)
- Finance records (historical transactions and outstanding balances/dues)

Section import is explicitly out of scope for now.

## Scope
In scope:
- Smart import adapter for variable file structures
- Mandatory manual column mapping before import
- Pre-apply preview with before/after financial impact
- Duplicate detection and controlled conflict handling
- Row-level corrections in preview
- Reusable mapping profiles plus batch-only corrections
- Full auditability and rollback by batch

Out of scope:
- Fully automatic no-review imports
- New school template mandates
- Section assignment/import

## Recommended Approach
Use a shared import pipeline with two import modules:
1. Student Profile Import
2. Finance Import

Both modules use the same guarded process:
1. Upload file
2. Parse and stage raw rows
3. Required manual column mapping
4. Validate and classify rows
5. Preview before/after results
6. Apply only after explicit user confirmation

Finance import uses stricter validation and reconciliation gates than student import.

## Architecture
### Core Components
- `ImportBatch` service: owns one upload run, status, and batch metadata
- `HeaderNormalizer`: resolves header aliases and candidate mappings
- `MappingResolver`: stores user-selected mapping for this batch and optional reusable profile
- `RowParser`: converts raw cell values into typed fields (date, decimal, string)
- `ValidationEngine`: applies field rules and confidence checks
- `DuplicateEngine`: checks existing records + within-file duplicates
- `PreviewBuilder`: computes create/update/skip results and before/after snapshots
- `ApplyEngine`: commits accepted rows transactionally and writes audit data
- `RollbackEngine`: reverts a batch by batch ID

### Finance Row Classification
Rows are classified during preview into:
- `payment` (historical transaction)
- `due` (open balance/installment)
- `mixed` (contains both due and payment indicators)
- `unresolved` (insufficient or ambiguous mapping)

`unresolved` rows block final apply.

## Data Rules
### Student Profile Import Rules
Required:
- `LRN`

Optional but supported:
- `Name` or `First Name` + `Last Name`
- `Contact Number`
- `Guardian Name`
- `Gender`

Key behavior:
- Match/create by `LRN` only
- Existing student records are updated only for mapped fields
- Unmapped fields remain unchanged

### Finance Import Rules
Required anchor:
- `LRN` for every finance row

Payment rows:
- Require amount > 0 and payment date (or explicitly user-mapped equivalent)
- OR number optional but strongly preferred

Due rows:
- Require amount due > 0 and at least one due anchor (`due_date` or billing period label)

No silent guessing on critical values:
- Ambiguous date/amount/identifier parsing marks row `unresolved`

## Duplicate Protection
### Payments
Primary duplicate key:
- `LRN + OR number`

Secondary fallback key (when OR not available):
- `LRN + payment_date + amount + reference_no`

### Dues
Duplicate key:
- `LRN + due_date + due_description + amount_due`

Duplicate statuses in preview:
- `existing_duplicate`
- `in_file_duplicate`

Default action for duplicates is `skip`.

## Preview and Corrections UX
Pre-apply preview is mandatory and shows:
- rows to create/update/skip
- all row errors and warnings
- unresolved and duplicate buckets
- before vs after totals per student:
  - total dues
  - total payments
  - outstanding balance
- projected ledger sequence sample by student/date

Corrections allowed in preview:
- Column remapping
- Per-row value edits
- Row action override where permitted by rules

Correction model:
- Batch-only row edits are always allowed
- Reusable profile save is optional and stores mapping/parsing rules only
- Row-specific edits are not global unless explicitly promoted into reusable rules

Every edit triggers re-validation, duplicate re-check, and preview recomputation.

## Commit and Rollback
Apply rules:
- Block apply if unresolved rows exist
- Block apply if finance reconciliation fails
- Block apply if required mappings are incomplete

Apply transaction:
- Single DB transaction per confirmed batch
- Writes audit snapshot including mapping, counts, user, timestamps, and row hash

Rollback:
- Rollback action available per batch ID
- Reverts rows created/updated by that batch and records reversal audit

## Reconciliation for Finance
Before apply, compute and validate:
- Imported total dues
- Imported total payments
- Net expected balance impact
- Per-student projected outstanding after import

Apply is blocked when reconciliation mismatches occur.

## Risk Assessment
This is feasible and not too risky if guardrails remain mandatory.

Main risks:
- Incorrect manual mapping
- Ambiguous source data (date/amount formats)
- Duplicate leakage

Mitigations:
- Required mapping review
- Hard validation gates
- Duplicate engine default-skip
- Before/after preview
- Batch audit + rollback

## Testing Strategy
### Unit tests
- Header alias resolution
- Data type parsing (dates, decimals, currency strings)
- Duplicate key generation and detection
- Row classification (`payment`, `due`, `mixed`, `unresolved`)

### Feature tests
- Student import with manual mapping flow
- Finance import with mixed rows split in preview
- Duplicate rows default to skip
- Unresolved rows block apply
- Reconciliation mismatch blocks apply
- Corrections update preview and allow apply once valid
- Batch rollback restores prior state

### Regression tests
- Existing registrar and finance import behavior remains intact for current CSV format

## Implementation Notes
- Reuse current import surfaces (`/registrar/data-import`, `/finance/data-import`) to avoid workflow disruption.
- Introduce adapter internals behind existing controllers first, then enhance UI preview controls.
- Preserve current audit history pages and extend metadata payloads for new batch details.
