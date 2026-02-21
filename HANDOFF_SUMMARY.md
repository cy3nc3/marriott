# Project Handoff Summary

## 1. Product Context and Core Flow

This project is a school management system for a DepEd-recognized school in the Philippines.

### 1.1 Enrollment + LIS Strategy

1. DepEd LIS remains the primary enrollment source of truth.
2. Staff should avoid double encoding.
3. During enrollment period:
   - Registrar collects only minimal intake fields:
     - `LRN`
     - `First Name`
     - `Last Name`
     - `Emergency Contact`
     - `Payment Plan` (`cash`, `monthly`, `quarterly`, `semi-annual`)
     - `Downpayment` (if not cash)
   - System creates/updates:
     - student account
     - parent account (linked)
     - floating/queue enrollment transaction
4. Cashier processes payment by searching student/LRN.
5. Later, SF1 upload auto-enriches student records by matching `LRN`.
6. SF1 already includes school year context; upload flow should not ask school year manually.
7. LIS reconciliation is automatic; no manual matching queue input.

## 2. Non-Negotiable Instructions Set by User

## 2.1 UI and Component Rules

1. Use shadcn components throughout the project.
2. Do not override/hack shadcn internals.
3. Use Tailwind CSS primarily for layout/positioning.
4. Keep production-style UI, not process/demo/stepper style mockups.
5. Remove unnecessary card-header descriptions in production views.
6. Prefer simple, low-scroll workflows for staff-heavy pages.
7. Keep code readable/basic (student-friendly syntax style).
8. Keep visual and interaction consistency across modules.

### 2.2 Specific Product Decisions Confirmed

1. Enrollment queue removes entries once payment is completed.
2. LIS matching concerns belong in Student Directory flow, not enrollment queue.
3. SF1 upload should be a direct file trigger/button pattern.
4. Cashier panel should be one-page flow.
5. Transaction processing should be done through a confirmation dialog.
6. Finance product inventory is for pricing only (no stock management).
7. Discount programs table should use actions (`Edit`, `Delete`) instead of status.
8. Date range display format should be `MM/DD/YYYY`.

### 2.3 Deferred/Skipped by Direction

1. Skip finance print/export work for now (keep for backlog).
2. Ignore/defer these modules for now:
   - registrar batch promotion
   - registrar permanent records
   - registrar student departure
   - admin DepEd reports
   - admin SF9 generator

## 3. What Has Been Implemented

## 3.1 Registrar (Backend + Frontend Wiring Completed)

### Backend

- `app/Http/Controllers/Registrar/StudentDirectoryController.php`
  - SF1 upload + automatic LRN reconciliation
  - LIS sync status mapping and summary counters
  - upload metadata persistence
- `app/Http/Controllers/Registrar/EnrollmentController.php`
  - enrollment queue listing + filters
  - create/update/delete intake queue records
  - linked student/parent account handling
- `app/Http/Controllers/Registrar/RemedialEntryController.php`
  - context filters + existing records loading
  - draft/submitted remedial save flow
  - recomputed grade/status logic

### Routes

- `routes/roles/registrar.php`
  - registrar endpoints wired for SF1 upload, enrollment CRUD, remedial save

### Models/Migration

- `database/migrations/2026_02_20_140842_add_downpayment_to_enrollments_table.php`
- Enrollment/Student/Remedial model updates for fields and relationships

### Frontend

- `resources/js/pages/registrar/student-directory/index.tsx`
- `resources/js/pages/registrar/enrollment/index.tsx`
- `resources/js/pages/registrar/remedial-entry/index.tsx`

## 3.2 Finance (Core Scope Wired and Functional)

### Implemented Pages and Flows

1. Cashier Panel
   - `resources/js/pages/finance/cashier-panel/index.tsx`
   - one-page processing flow and transaction dialog
2. Student Ledgers
   - `resources/js/pages/finance/student-ledgers/index.tsx`
   - dues-by-plan, ledger filtering, profile + dues side-by-side
3. Transaction History
   - `resources/js/pages/finance/transaction-history/index.tsx`
4. Fee Structure
   - `resources/js/pages/finance/fee-structure/index.tsx`
   - tabs by grade with detailed breakdown
5. Product Inventory (Pricing only)
   - `resources/js/pages/finance/product-inventory/index.tsx`
6. Discount Manager
   - `resources/js/pages/finance/discount-manager/index.tsx`
   - actions-based management + tagging
7. Daily Reports
   - `resources/js/pages/finance/daily-reports/index.tsx`
8. Finance Dashboard (now data-driven)
   - `app/Http/Controllers/Finance/DashboardController.php`
   - `resources/js/pages/finance/dashboard.tsx`
   - `/dashboard` role switch wired in `routes/web.php`

### Finance Backend Surface

- Controllers under `app/Http/Controllers/Finance/`
- Form Requests under `app/Http/Requests/Finance/`
- Models under `app/Models/`:
  - `Fee`, `Discount`, `InventoryItem`, `StudentDiscount`
  - `BillingSchedule`, `Transaction`, `TransactionItem`, `LedgerEntry`

### Finance Current Deferred Backlog

1. Void/refund workflow + real void adjustments in reports
2. Large dataset pagination/performance hardening
3. Edge-case test expansion (concurrency, OR duplication, etc.)
4. Print/export implementation (explicitly deferred)

## 3.3 Teacher (Now Partially Backend-Wired)

### Completed in Current Progress

1. Teacher Dashboard backend wiring
   - `app/Http/Controllers/Teacher/DashboardController.php`
   - `/dashboard` teacher role now uses controller in `routes/web.php`
   - page now uses live schedule + pending submission metrics:
     - `resources/js/pages/teacher/dashboard.tsx`
2. Teacher Schedule backend wiring
   - `app/Http/Controllers/Teacher/ScheduleController.php`
   - route wiring in `routes/roles/teacher.php`
   - page now uses live schedule/break data:
     - `resources/js/pages/teacher/schedule/index.tsx`
3. Teacher Grading Sheet full backend wiring
   - controller:
     - `app/Http/Controllers/Teacher/GradingSheetController.php`
   - requests:
     - `app/Http/Requests/Teacher/IndexGradingSheetRequest.php`
     - `app/Http/Requests/Teacher/UpdateGradingRubricRequest.php`
     - `app/Http/Requests/Teacher/StoreGradedActivityRequest.php`
     - `app/Http/Requests/Teacher/StoreGradingScoresRequest.php`
   - models:
     - `app/Models/GradingRubric.php`
     - `app/Models/GradedActivity.php`
     - `app/Models/StudentScore.php`
   - relationships added to existing models:
     - `Subject`, `SubjectAssignment`, `Student`
   - routes:
     - `GET /teacher/grading-sheet`
     - `POST /teacher/grading-sheet/rubric`
     - `POST /teacher/grading-sheet/assessments`
     - `POST /teacher/grading-sheet/scores`
   - frontend bound to live data + actions:
     - `resources/js/pages/teacher/grading-sheet/index.tsx`

### Teacher Still Pending

1. Advisory Board backend wiring (conduct encoding + lock workflow)
   - currently still a closure-rendered page route

## 3.4 Student + Parent (Frontend Visualization Done Earlier)

- Student:
  - `resources/js/pages/student/schedule/index.tsx`
  - `resources/js/pages/student/grades/index.tsx`
- Parent:
  - `resources/js/pages/parent/schedule/index.tsx`
  - `resources/js/pages/parent/grades/index.tsx`
  - `resources/js/pages/parent/billing-information/index.tsx`

## 4. Cross-Cutting Technical Changes

## 4.1 Wayfinder Route Generation and Compatibility Fix

After route/controller expansion, `php artisan wayfinder:generate --no-interaction` updated generated route helpers.

Because of generated helper changes, non-GET `.form()` usage became incompatible in several auth/settings pages. These were updated to explicit `action={route().url}` and `method={route().method}` patterns.

Updated files include:

- `resources/js/components/delete-user.tsx`
- `resources/js/components/two-factor-recovery-codes.tsx`
- `resources/js/components/two-factor-setup-modal.tsx`
- `resources/js/pages/auth/confirm-password.tsx`
- `resources/js/pages/auth/forgot-password.tsx`
- `resources/js/pages/auth/login.tsx`
- `resources/js/pages/auth/reset-password.tsx`
- `resources/js/pages/auth/two-factor-challenge.tsx`
- `resources/js/pages/auth/verify-email.tsx`
- `resources/js/pages/settings/password.tsx`
- `resources/js/pages/settings/profile.tsx`
- `resources/js/pages/settings/two-factor.tsx`

## 4.2 Shared UI Utility

- `resources/js/components/ui/date-picker.tsx`
  - date range formatting standardized to `MM/DD/YYYY`

## 5. Test Coverage Added/Run

## 5.1 Finance

- `tests/Feature/Finance/`
  - cashier, student ledgers, transaction history, fee structure, product inventory, discount manager, daily reports, finance dashboard

## 5.2 Teacher

- `tests/Feature/Teacher/TeacherFeaturesTest.php`
  - schedule render from real assignments
  - dashboard live schedule + pending submission summary
  - grading sheet render with rubric/assessments/computed grades
  - grading actions: rubric update, assessment create, score submit + final grade lock

## 5.3 Additional Validation Commands Repeatedly Used

1. `php artisan test --compact <target-file-or-dir>`
2. `npm run types`
3. `vendor/bin/pint --dirty --format agent`
4. `php artisan route:list --path=<module>` for route verification

## 6. Current Project State (Functional Summary)

1. Registrar core enrollment + SF1 sync + remedial flows are wired.
2. Finance core workflows are wired and tested for current scope.
3. Teacher dashboard, schedule, and grading sheet are now backend-driven.
4. Teacher advisory board remains the key unfinished teacher workflow.
5. Several deferred modules remain intentionally untouched per directive.

## 7. Recommended Next Steps (Priority Order)

1. Teacher Advisory Board backend wiring
   - Implement controller + requests + routes for conduct/values save and lock flow.
   - Persist and load advisory class read-only grades from real data.
2. Role-level workflow completion pass
   - Verify all teacher flows (dashboard, schedule, grading sheet, advisory board) end-to-end.
3. Deferred registrar/admin modules (only when resumed)
   - batch promotion
   - permanent records
   - student departure
   - DepEd reports
   - SF9 generator
4. Finance hardening backlog (when resumed)
   - void/refund processing
   - print/export
   - performance and edge-case hardening
5. Final consistency QA pass
   - cross-role UI consistency
   - route generation consistency
   - regression tests and smoke checks

## 8. Startup Instruction for New Device/Session

Use this exact working pattern when continuing development on another device:

1. First scan and study codebase structure before changing code.
2. Map role routes and corresponding Inertia pages.
3. Review existing shared shadcn components/layout patterns.
4. Summarize findings first, then implement.
5. Follow shadcn-first + Tailwind layout-only rule.
6. Keep production-style UI consistency from existing modules.
7. Continue from highest-priority pending feature list above.

## 9. Notes for Continuity

1. Print/export remains intentionally deferred.
2. Finance scope is considered complete for current agreed scope.
3. Teacher implementation is in progress; advisory board is next major target.
4. Keep this file updated after every substantial module wiring session.
