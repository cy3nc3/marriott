# HANDOFF SUMMARY

Last updated: 2026-02-23
Project path: `/home/lomonol/projects/marriott` (active), `C:\Users\jadeg\Documents\Capstone\marriott` (previous Windows path)
Primary branch target: `main`
Latest pushed commit (before this update): `b6de4ca`

---

## 1. Current System State (Executive Snapshot)

This repository is now a role-based Laravel + Inertia school operations system with:

1. Full role dashboards wired to chart-based trend payloads.
2. Core backend wiring completed for Super Admin, Admin, Finance, Registrar, Teacher, Student, and Parent active modules.
3. Strong UI consistency pass across role pages using shadcn components and common layout patterns.
4. Operationally tested core flows for super admin governance, registrar intake, finance cashiering, and teacher grading.

High-confidence areas:

1. Super admin governance modules are functionally wired.
2. Registrar core workflow (directory + SF1 + intake + remedial + departure + batch promotion monitor) is wired.
3. Finance operational modules are wired.
4. Teacher grading/advisory/schedule modules are wired.
5. Student and Parent feature pages are consistent and wired.

Deferred areas remain intentionally out of active scope (see Section 15).

---

## 2. Product Constraints and Decisions to Preserve

### 2.1 Core Product Constraint

Avoid duplicate encoding because DepEd LIS is the primary external source.

### 2.2 Enrollment/LIS Workflow Model

1. Registrar performs minimal intake first.
2. Cashier handles payment after intake.
3. SF1 upload enriches student data via `LRN` matching.
4. SF1 carries school-year context; uploader should not prompt for separate school-year selection.
5. Reconciliation should be system-driven, not manual one-by-one matching.

### 2.3 UX/Engineering Directives

1. Use shadcn components as the default UI building blocks.
2. Avoid custom style overrides of shadcn internals unless required.
3. Keep layouts operational, simple, and low-scroll for staff usage.
4. Keep role modules visually consistent.
5. Keep implementation syntax readable and beginner-friendly when possible.

### 2.4 Confirmed Behavioral Decisions

1. Enrollment queue entries clear out after successful payment progression.
2. SF1 upload uses direct file selection workflow.
3. Cashier panel uses single-page flow with explicit action confirmation.
4. Product inventory is pricing-focused (no stock management in current scope).
5. Discount registry uses edit/delete actions rather than complex state machine UI.
6. Date range displays use `MM/DD/YYYY` presentation in relevant pages.

---

## 3. Stack and Architecture Snapshot

### 3.1 Backend

1. Laravel 12 (`laravel/framework` 12.x)
2. PHP 8.x
3. Fortify auth (`laravel/fortify`)
4. Wayfinder route generation (`laravel/wayfinder`)
5. PostgreSQL as current configured DB engine

### 3.2 Frontend

1. Inertia v2 (`inertiajs/inertia-laravel`, `@inertiajs/react`)
2. React 19 + TypeScript
3. Tailwind CSS v4
4. shadcn + Radix UI component composition
5. Recharts-backed chart rendering via shared chart wrapper components

### 3.3 Testing/Quality

1. Pest/PHPUnit feature tests
2. TypeScript check via `npm run types`
3. Formatting via Prettier and Pint

### 3.4 Routing Pattern

1. `/dashboard` dispatches by role in `routes/web.php`.
2. Role modules are split in `routes/roles/*.php`.
3. Middleware aliases/guards are configured in `bootstrap/app.php`.

---

## 4. Role Coverage Matrix

### 4.1 Super Admin

Implemented modules:

1. `user-manager`
2. `audit-logs`
3. `announcements`
4. `permissions`
5. `system-settings`
6. dashboard

### 4.2 Admin

Implemented modules:

1. `academic-controls`
2. `curriculum-manager`
3. `section-manager`
4. `schedule-builder`
5. `class-lists`
6. dashboard

Deferred admin modules:

1. `deped-reports`
2. `sf9-generator`

### 4.3 Registrar

Implemented modules:

1. `student-directory` (+ SF1 upload)
2. `enrollment`
3. `remedial-entry`
4. `student-departure`
5. `batch-promotion`
6. `permanent-records` (frontend production-style visualization and edit interactions)
7. dashboard

### 4.4 Finance

Implemented modules:

1. `cashier-panel`
2. `student-ledgers`
3. `transaction-history`
4. `fee-structure`
5. `product-inventory`
6. `discount-manager`
7. `daily-reports`
8. dashboard

### 4.5 Teacher

Implemented modules:

1. `schedule`
2. `grading-sheet`
3. `advisory-board`
4. dashboard

### 4.6 Student

Implemented modules:

1. `schedule`
2. `grades`
3. dashboard

### 4.7 Parent

Implemented modules:

1. `schedule`
2. `grades`
3. `billing-information`
4. dashboard

---

## 5. Super Admin Work Completed

### 5.1 User Manager

1. Backend wiring for create/edit/toggle/reset actions.
2. Pagination adjusted to 15 rows per page.
3. Footer text pattern standardized to range format (`X-X out of XX`).
4. Role filter header simplified (removed extra label text).
5. Edit dialog role selector fixed to show current selected role.
6. Create/Edit date picker updated to shadcn DOB-style date picker variant.

### 5.2 Announcements

1. List page refactored to common layout pattern.
2. Search + priority + roles filter aligned in one row.
3. Filters and create action moved outside heavy card header usage where applicable.
4. Added target-role badges per announcement row/card.
5. Implemented fallback behavior: no selected roles implies announcement targets all roles.
6. Backend wiring for create/edit/delete and filtering validated.

### 5.3 Audit Logs

1. Refactored listing layout for consistency.
2. `target` column output made more understandable and concise.
3. Added details action behavior.
4. Enhanced details visibility for change context (including change-oriented action descriptions).
5. Search/filter controls aligned on same row.
6. Date filtering improved to date-range picker style behavior.

### 5.4 Permissions

1. Refactored page to system-wide common layout patterns.
2. Maintained functional behavior while improving consistency.

### 5.5 System Settings

1. Backend wired for settings save + toggles + backup actions.
2. Maintenance mode and parent portal switches are functional.
3. Backup and restore actions confirmed working.
4. Actions are logged in audit layer.
5. UI polished to match role standards:
    - card header border usage standardized
    - subtitle clutter removed from main card headers
    - backup action presentation improved (`Configure` action button)

### 5.6 Dashboard

1. Super admin dashboard trends fully chart-backed.
2. `role-distribution` set to bar chart.
3. `audit-activity` set to line chart.

---

## 6. Admin Work Completed

### 6.1 Academic Controls

UI/UX iterations completed:

1. Start date/end date split clearly.
2. Hover-only edit actions removed in favor of explicit edit button pattern.
3. Edit dates button repositioned multiple times for clarity and consistency.
4. Current quarter display moved beside active school year based on user direction.
5. Action placement revised to avoid collision with simulation controls.
6. Quarter timeline card removed when deemed unnecessary.
7. Status badge placement updated to sit next to active school year label.

### 6.2 Curriculum Manager

1. Refactored toward common role layout.
2. Header bar removed per direction.
3. Tabs/actions placement aligned with requested positions.
4. Avoided wrapping primary table in extra unnecessary card as directed.

### 6.3 Section Manager and Class Lists

1. Refactored to follow curriculum manager/common layout patterns.
2. Admin class list page standardized.

### 6.4 Schedule Builder

1. Preserved Google Calendar-like timeline interaction and duration-based block heights.
2. Aesthetic refinement aligned to system visual language.
3. Protected core scheduling behavior while refactoring UI.
4. Runtime break (`CardHeader is not defined`) encountered during iterations and resolved.

### 6.5 Dashboard

1. Admin trend charts migrated to shadcn/recharts-driven unified renderer.
2. Enrollment forecast changed to area chart with clearer forecast visualization.
3. Grade-level enrollment chart upgraded to male/female grouped bars with total context.

---

## 7. Registrar Work Completed

### 7.1 Student Directory

1. Backend wiring for directory data retrieval.
2. SF1 upload handling integrated.
3. Auto-enrichment/reconciliation via LRN flow implemented.
4. Page layout aligned with common role pattern.

### 7.2 Enrollment

1. Queue/intake create/edit/delete wired.
2. Flow aligned with registrar -> cashier handoff model.
3. Layout and controls standardized.

### 7.3 Remedial Entry

1. Remedial encoding workflow wired end-to-end.
2. Input, context selection, and table interactions standardized.
3. UI spacing and card consistency updates applied.

### 7.4 Dashboard

1. Removed queue aging bucket trend as requested.
2. LIS Sync Distribution retained and charted as pie.
3. Replaced secondary trend with payment method usage mix.
4. LIS legend color semantics refined:
    - synced = main blue
    - pending = lighter tone
    - errors = red

### 7.5 Batch Promotion + Year Close Integration

1. Added batch promotion backend service: `app/Services/Registrar/BatchPromotionService.php`.
2. Added registrar monitoring page/controller wiring:
    - `app/Http/Controllers/Registrar/BatchPromotionController.php`
    - `resources/js/pages/registrar/batch-promotion/index.tsx`
3. Added review-resolution request validation:
    - `app/Http/Requests/Registrar/ResolveBatchPromotionReviewRequest.php`
4. Admin year-close integration implemented in:
    - `app/Http/Controllers/Admin/SchoolYearController.php`
5. Year close now attempts promotion to target school year before setting source year as completed.
6. Grade completeness blockers are returned and prevent year close when required annual grades are incomplete/unlocked.

### 7.6 Student Departure Workflow

1. Added registrar student departure controller and request validation:
    - `app/Http/Controllers/Registrar/StudentDepartureController.php`
    - `app/Http/Requests/Registrar/StoreStudentDepartureRequest.php`
2. Added registrar page:
    - `resources/js/pages/registrar/student-departure/index.tsx`
3. Added `student_departures` persistence model:
    - `app/Models/StudentDeparture.php`
4. Added login-expiry model via `users.access_expires_at`.
5. Departure flow now supports `transfer_out` and `dropped_out` with enrollment status updates.
6. Historical student/parent pages include read-only support for departed statuses.

### 7.7 Permanent Records Progress

1. Permanent records page was rebuilt into production-oriented UI flow:
    - search + student info + academic history + add historical record
2. Academic history (SF10-style) now shows subject rows by quarter (`Q1-Q4`) + final + general average.
3. Added edit action per academic-history card with prefilled dialog edit form.
4. Historical subject encoding supports add/remove subject rows and computed final grade previews.
5. Grade level/status inputs were standardized to select controls in add/edit flows.
6. Page routing is now controller-driven (`routes/roles/registrar.php` -> `PermanentRecordsController@index`) with backend-provided student/record payloads.

---

## 8. Finance Work Completed

### 8.1 Cashier Panel

1. Backend transaction posting wired.
2. Confirmation action behavior retained.
3. Ledger and transaction integration validated.

### 8.2 Student Ledgers / Transaction History / Daily Reports

1. Core listing and filtering workflows wired.
2. Data displayed from backend rather than mock UI.
3. Layout consistency refactor applied.

### 8.3 Fee Structure / Product Inventory / Discount Manager

1. CRUD and page wiring integrated.
2. Discount manager registry actions standardized.
3. Product inventory remains pricing-only by product decision.

### 8.4 Dashboard

1. `daily-collection` trend now line chart with chart payload.
2. `payment-mode-mix` trend now pie chart with chart payload.
3. Trend rendering now consistent with shared chart system.

---

## 9. Teacher Work Completed

### 9.1 Schedule

1. Teacher schedule page aligned with admin schedule-view styling pattern.
2. Preserved schedule behavior and data model.

### 9.2 Grading Sheet

1. Rubric update, assessment creation, and score submission workflows wired.
2. Submission/finalization actions operational.
3. Runtime error fixed:
    - resolved maximum update depth loop in page state synchronization.
4. Select controls hardened to avoid invalid controlled-value edge conditions.

### 9.3 Advisory Board

1. Data-driven advisory board improvements and consistency refactor.
2. Conduct-related flow and dashboard relationships retained.

### 9.4 Dashboard

1. All teacher trends chart-backed.
2. `today-classes` trend converted from list to bar chart (duration minutes).
3. `pending-grade-rows-by-class` remains bar chart.

---

## 10. Student and Parent Work Completed

### 10.1 Student

1. Schedule and grades views refactored for consistency with role patterns.
2. Dashboard is learning-focused and excludes finance-centric metrics.
3. `recent-score-trend` line chart maintained.
4. `upcoming-academic-items` converted to bar chart (count by day).

### 10.2 Parent

1. Schedule, grades, and billing pages refactored and wired.
2. Dashboard child-context summary retained.
3. Payment and dues trends remain chart-backed.

---

## 11. Dashboard Analytics and Charting Standardization

Major dashboard analytics work completed across roles:

1. Unified trend rendering through `resources/js/components/dashboard/analytics-panel.tsx`.
2. Added/standardized support for `line`, `bar`, `area`, and `pie` displays.
3. Added shadcn chart wrapper component usage via `resources/js/components/ui/chart.tsx`.
4. Replaced list-only trends with chart payloads in controllers where needed.
5. Forecast visualization improvements:
    - area chart with gradient styling
    - forecast differentiation using dashed/styled series
6. Tooltip improvements:
    - dot indicator defaults
    - total context for grade-level enrollment
    - duplicate forecast tooltip entry issue fixed
7. Chart spacing issue hardening:
    - addressed left-gap/layout behavior by container/axis tuning in shared chart rendering

Additional chart color directives implemented:

1. LIS pie colors mapped to semantic status colors.
2. Payment method mix colors differentiated across methods.

---

## 12. Cross-Role UI Consistency Work

### 12.1 Sidebar and Navigation

1. Fixed collapsed sidebar behavior so academic controls dropdown links remain accessible.
2. Navigation structure aligned with current role modules.

### 12.2 Card Layout Standardization

Implemented on non-dashboard role pages:

1. Non-table cards adjusted for tighter consistent gaps (`gap-2` policy).
2. Description-style headers adjusted to tighter header gap (`gap-1` policy).
3. Visual separation under bordered headers reinforced with `CardContent` top padding.
4. Padding was tuned from `pt-2` to `pt-6` based on user feedback for visible spacing.

### 12.3 System Settings Card Pattern Alignment

1. Card headers use `border-b` consistently.
2. Unnecessary subtitle paragraphs removed from major settings cards.
3. Backup card action layout refined to match other operational cards.

---

## 13. Backend Wiring Status (Functional)

### 13.1 Confirmed Working Through Development + Validation

1. Super admin user manager CRUD-style operations + state toggles.
2. Announcement CRUD and audience targeting behavior.
3. Audit logs listing and details interaction.
4. System settings save + maintenance/portal toggles.
5. Backup/restore system actions.
6. Registrar enrollment queue operations.
7. SF1 upload + student directory integration.
8. Teacher grading-sheet rubric/assessment/score flows.
9. Finance cashier posting and ledger-linked operations.

### 13.2 Manual Validation Notes Confirmed by User

1. Backup and restore actions were run and validated.
2. Executed actions were logged in audit trail.

---

## 14. Database and Schema Notes

Known additions/usage in this implementation stream include:

1. Conduct ratings data support (`conduct_ratings` migration and model integration).
2. Super admin feature wiring leveraged settings/audit/announcement persistence.
3. Registrar/finance/teacher workflows use enrollment, scores, transactions, and related records with active-year scoping patterns.
4. Registrar progression/departure schema updates were added:
    - `database/migrations/2026_02_22_202828_update_permanent_records_for_conditional_tracking.php`
    - `database/migrations/2026_02_22_202829_add_access_expires_at_to_users_table.php`
    - `database/migrations/2026_02_22_202829_create_student_departures_table.php`
5. `permanent_records` migration includes duplicate cleanup before unique index creation on `(student_id, academic_year_id)`.
6. P0 hardening/index migration added:
    - `database/migrations/2026_02_23_210000_harden_integrity_and_add_performance_indexes.php`
    - adds unique constraints for:
        - `enrollments (student_id, academic_year_id)`
        - `student_scores (student_id, graded_activity_id)`
    - adds performance indexes across `enrollments`, `transactions`, `ledger_entries`, `billing_schedules`, `final_grades`, `audit_logs`, and `class_schedules`
    - performs pre-constraint deduplication for `enrollments` and `student_scores`

Operational note:

1. Migrations are required before running newly wired modules.
2. User has previously run `php artisan migrate` successfully in this stream.
3. If migrating an older DB snapshot, ensure both the registrar lifecycle migrations and the 2026-02-23 hardening/index migration are applied.
4. Fortify auth now enforces account `is_active` and `access_expires_at` checks.
5. For older datasets, run a DB backup before `php artisan migrate` because the hardening migration deduplicates existing enrollment/score rows before adding unique constraints.

---

## 15. Deferred Modules (Intentionally Not Prioritized)

These remain deferred unless explicitly re-opened:

1. Admin `deped-reports`
2. Admin `sf9-generator`
3. Finance print/export-heavy extensions (deferred by product decision)

---

## 16. Testing and Validation Summary

### 16.1 Commands frequently used and required after edits

1. `npm run types`
2. `npx prettier --write <touched files>`
3. `vendor/bin/pint --dirty --format agent`
4. `php artisan test --compact <targeted tests>`

### 16.2 Test Suites actively touched in this implementation stream

1. `tests/Feature/DashboardTest.php`
2. `tests/Feature/Admin/AdminFeaturesTest.php`
3. `tests/Feature/Registrar/RegistrarFeaturesTest.php`
4. `tests/Feature/Finance/FinanceDashboardTest.php`
5. `tests/Feature/Teacher/TeacherFeaturesTest.php`
6. `tests/Feature/Student/StudentFeaturesTest.php`
7. `tests/Feature/Parent/ParentFeaturesTest.php`
8. `tests/Feature/SuperAdmin/*` modules where applicable

### 16.3 Latest verified runs in recent iterations

1. Type checks passed.
2. Pint formatting passed.
3. Targeted dashboard feature tests passed.
4. Targeted teacher dashboard/grading tests passed.
5. Post-table-refactor targeted suites passed:
    - `tests/Feature/Registrar/RegistrarFeaturesTest.php`
    - `tests/Feature/Finance/TransactionHistoryTest.php`
    - `tests/Feature/Finance/StudentLedgersTest.php`
    - `tests/Feature/SuperAdmin/AuditLogTest.php`
    - `tests/Feature/SuperAdmin/UserManagerTest.php`
    - `tests/Feature/RoleAccessTest.php`
6. Combined targeted run result: `22 passed (301 assertions)`.

---

## 17. Tooling and Environment Notes

1. Composer scripts support first-time setup and dev loop (`setup`, `setup:finish`, `dev`).
2. Wayfinder generation is part of route-helper consistency workflow.
3. MCP-based tooling integration has been used for debugging and documentation lookup.

If UI changes do not appear:

1. ensure `composer run dev` or `npm run dev` is active,
2. hard-refresh browser,
3. rebuild via `npm run build` when needed.

---

## 18. Recommended First Steps for Next Session

1. Scan current route/controller/page structure before implementing new changes.
2. Preserve shadcn-first composition and existing layout conventions.
3. Keep dashboard trends chart-backed when adding new analytics.
4. Run required checks after each edit batch.
5. Continue deferred module work only when explicitly requested.

---

## 19. Suggested Next Priorities

1. Perform final hardening pass of schedule-related views (admin/teacher/student/parent) with regression checks.
2. Expand feature tests for super admin announcements/audit-log detail scenarios.
3. Add deeper edge-case tests for dashboard chart payloads by role (empty, partial, and high-volume data).
4. Re-open deferred registrar/admin modules only upon explicit direction.

---

## 20. File Areas Most Frequently Updated

Backend:

1. `app/Http/Controllers/SuperAdmin/*`
2. `app/Http/Controllers/Admin/*`
3. `app/Http/Controllers/Registrar/*`
4. `app/Http/Controllers/Finance/*`
5. `app/Http/Controllers/Teacher/*`
6. `app/Http/Controllers/Student/*`
7. `app/Http/Controllers/ParentPortal/*`

Frontend:

1. `resources/js/pages/{role}/...`
2. `resources/js/components/dashboard/*`
3. `resources/js/components/ui/*`
4. `resources/js/components/nav-main.tsx`

Tests:

1. `tests/Feature/*Dashboard*`
2. role-specific feature suites under `tests/Feature/{Role}`

This summary is now the authoritative operational handoff for ongoing system development.

---

## 21. Latest UI/Frontend Progress Addendum (2026-02-23)

### 21.1 Table Strategy Change

1. DataTable usage was rolled back to standard shadcn `Table` markup for visual preference consistency.
2. Updated pages:
    - `resources/js/pages/registrar/student-directory/index.tsx`
    - `resources/js/pages/finance/student-ledgers/index.tsx`
    - `resources/js/pages/finance/transaction-history/index.tsx`
    - `resources/js/pages/super_admin/audit-logs/index.tsx`
    - `resources/js/pages/super_admin/user-manager/index.tsx`
3. Legacy DataTable wrapper components were removed after rollback:
    - `resources/js/components/ui/data-table.tsx`
    - `resources/js/components/ui/data-table-column-header.tsx`

### 21.2 Permanent Records Iteration Details

1. Search bar width and header/body card spacing were tuned to match system style.
2. Student selection display was converted into dedicated student info card (removed badge-style selection indicator).
3. Add Historical Record:
    - removed manual failed-subject and general-average entry fields
    - quarter-first entry flow with computed final values
    - compacted subject row UI to control page height growth
4. Edit Historical Record dialog:
    - prefilled from selected academic history record
    - reduced viewport footprint (`max-h` + internal scroll)
    - action placement and sizing aligned with form layout patterns
5. Add/edit grade level is constrained to `Grade 7` to `Grade 10` select options; status aligned beside grade level.

### 21.3 Auth/Login UI

1. Login page now uses block-style composition and split layout via:
    - `resources/js/pages/auth/login.tsx`
    - `resources/js/components/login-form.tsx`
2. Visual presentation follows prior direction (production-style layout with cleaner primary auth flow).

---

## 22. Setup and Run Checklist (Critical for Other Device Handoff)

Use this exact sequence on a fresh machine or after pulling major backend changes.

### 22.1 Fresh Clone Setup

1. `composer install`
2. `composer run setup`
3. Edit `.env` database credentials.
4. `composer run setup:finish`
    - runs `php artisan migrate --seed --force`
    - runs `npm run build`

### 22.2 Existing Project Pull / Incremental Setup

1. `git pull origin main`
2. `composer install`
3. `npm install`
4. `php artisan migrate`
5. `npm run build` (or `npm run dev` for local hot reload)

### 22.3 Dev Runtime

1. `composer run dev`
    - serves Laravel on port 8001
    - starts queue listener
    - starts Vite dev server
2. Alternative split runs:
    - `php artisan serve --port=8001`
    - `php artisan queue:listen --tries=1 --timeout=0`
    - `npm run dev`

### 22.4 If You Hit Common Issues

1. Vite manifest error:
    - run `npm run build` or keep `npm run dev` active.
2. Stale config/cache behavior:
    - run `php artisan optimize:clear`.
3. New registrar lifecycle features missing:
    - confirm migrations are applied (`php artisan migrate`).

### 22.5 Required Quality Checks After Edits

1. `vendor/bin/pint --dirty --format agent`
2. `npm run -s types`
3. `php artisan test --compact <targeted test files>`

---

## 23. Notes for the Next AI Session

1. Review this handoff and scan current route/controller/page structure before coding.
2. Do not assume deferred registrar lifecycle modules are pending; batch promotion and student departure are already wired.
3. Permanent records page now loads from backend payloads; add/edit interactions still use client-side state unless write endpoints are explicitly requested.
4. Preserve shadcn-first component usage and Tailwind utility-only layout positioning conventions.
5. Continue to avoid introducing DataTable wrappers unless explicitly requested again.

---

## 24. Latest Backend Hardening + Performance Wave (2026-02-23, `f2ac7d1`)

### 24.1 P0 Hardening Implemented

1. Academic controls now use strict FormRequests:
    - `app/Http/Requests/Admin/InitializeAcademicYearRequest.php`
    - `app/Http/Requests/Admin/UpdateAcademicYearDatesRequest.php`
2. School year safety guards added in `app/Http/Controllers/Admin/SchoolYearController.php`:
    - blocks editing completed years
    - blocks simulation open/reset in production
    - blocks simulation open if another year is already ongoing
    - keeps year-close blocker when grade completeness checks fail
3. High-risk academic-year actions now emit explicit audit logs:
    - initialize, date update, quarter advance, close blocked/closed, simulation open/reset
4. Last-super-admin survivability guard implemented in `app/Http/Controllers/SuperAdmin/UserManagerController.php`:
    - prevents demotion/deactivation of the final active `super_admin`
5. Enrollment duplicate protection hardened in `app/Http/Controllers/Registrar/EnrollmentController.php`:
    - rejects already enrolled student for active year
    - handles unique-constraint DB conflict path cleanly

### 24.2 Performance Improvements Implemented

1. Added shared dashboard cache utility:
    - `app/Services/DashboardCacheService.php`
2. Applied dashboard caching across all role dashboard controllers:
    - Admin, Super Admin, Registrar, Finance, Teacher, Student, Parent
3. Added cache busting in write paths (settings/user/admin/registrar/finance/teacher flows where metrics can change).
4. Cached settings reads via `Setting::allCached()` in `app/Models/Setting.php`.
5. Optimized cashier transaction item writes in `app/Http/Controllers/Finance/CashierPanelController.php` using batched relationship insert.
6. Optimized SF1 upload student lookup path in `app/Http/Controllers/Registrar/StudentDirectoryController.php` via preloaded LRN map.
7. Added pagination for heavy finance pages (15/page):
    - `app/Http/Controllers/Finance/TransactionHistoryController.php`
    - `app/Http/Controllers/Finance/DailyReportsController.php`
    - frontend pagination consumption updated in matching TSX pages

### 24.3 Permanent Records Backend Wiring Completed

1. Registrar permanent records route now points to controller:
    - `routes/roles/registrar.php`
2. Backend page payload now built from persisted data:
    - `app/Http/Controllers/Registrar/PermanentRecordsController.php`
3. Records include mapped quarter/final subject grades resolved from enrollment + final grades linkage.
4. Frontend page consumes backend props and no longer relies on local sample array:
    - `resources/js/pages/registrar/permanent-records/index.tsx`

### 24.4 CI and Quality Gate Updates

1. Lint workflow now uses non-mutating checks and includes type checking:
    - `.github/workflows/lint.yml`
2. Test workflow includes:
    - `composer audit`
    - frontend type check
    - non-blocking prod-only npm audit
    - full Pest suite execution
    - `.github/workflows/tests.yml`

### 24.5 Tests Added/Updated in This Wave

1. `tests/Feature/Admin/AdminFeaturesTest.php`
2. `tests/Feature/Registrar/RegistrarFeaturesTest.php`
3. `tests/Feature/SuperAdmin/UserManagerTest.php`
4. `tests/Feature/Finance/TransactionHistoryTest.php`
5. `tests/Feature/Finance/DailyReportsTest.php`

### 24.6 Required Setup for This Wave

1. Pull latest code:
    - `git pull origin main`
2. Ensure dependencies are current:
    - `composer install`
    - `npm install`
3. Apply new schema changes:
    - `php artisan migrate`
4. Recommended before migration on old/dirty data:
    - take a DB backup first (migration deduplicates rows before adding unique constraints)
5. If behavior looks stale after deploy/pull:
    - `php artisan optimize:clear`
6. Rebuild assets if frontend updates are not visible:
    - `npm run build` (or run `npm run dev` during development)

### 24.7 Last Full Verification Run (This Wave)

1. `npm run types` passed.
2. `vendor/bin/pint --dirty --format agent` passed.
3. `php artisan test --compact` full suite passed (`120 passed`).

---

## 25. Latest Schedule + Dashboard Hardening Wave (2026-02-23, pending push)

### 25.1 Schedule Hardening Implemented

1. Admin schedule builder now resolves school-year context safely in this order:
    - `ongoing`
    - `upcoming`
    - earliest non-completed fallback
2. Admin schedule builder teacher list now has stable ordering and safe display-name fallback.
3. Teacher schedule is now scoped to the resolved display academic year:
    - class schedules filtered by active year section
    - advisory schedules filtered by active year section
    - break extraction section IDs filtered by active year
4. Student and parent schedule payload ordering is now deterministic:
    - sorted by day-of-week then start time for class/advisory rows
    - sorted by day-of-week then start time for break rows
5. Admin schedule validation is stricter:
    - `day` must be one of Monday-Sunday
    - `start_time` and `end_time` must match `H:i`
    - `end_time` must be after `start_time`
    - `subject_id` and `teacher_id` are required when `type=academic`

### 25.2 Super Admin Test Expansion Implemented

1. Announcements coverage expanded:
    - role + priority filter behavior (including global target-role announcements)
    - search by poster name (`user.name`)
    - duplicate target-role normalization on create
2. Audit logs coverage expanded:
    - detail payload assertions (`old_values`, `new_values`, `ip_address`, `user_agent`)
    - date-from-only filtering path with user-search scenario

### 25.3 Dashboard Edge-Case Coverage Added (All Roles)

1. Admin:
    - forecast behavior with partial history and zero-baseline previous year
2. Registrar:
    - empty state chart-safe payload with zero queue/transaction counts
3. Finance:
    - empty state chart-safe payload with zero amounts and stable chart contract
4. Teacher:
    - high-volume pending-grade-row aggregation across multiple classes
5. Student:
    - recent score trend capped to latest 5 assessments
6. Parent:
    - upcoming dues timeline capped to next 4 entries, with next-due KPI consistency
7. Super Admin:
    - new dashboard suite covering empty-log chart safety and high-volume audit-risk scenario

### 25.4 Files Updated in This Wave

Backend:

1. `app/Http/Controllers/Admin/ScheduleController.php`
2. `app/Http/Controllers/Teacher/ScheduleController.php`
3. `app/Http/Controllers/Student/ScheduleController.php`
4. `app/Http/Controllers/ParentPortal/ScheduleController.php`
5. `app/Http/Requests/Admin/StoreScheduleRequest.php`
6. `app/Http/Requests/Admin/UpdateScheduleRequest.php`

Tests:

1. `tests/Feature/Admin/AdminFeaturesTest.php`
2. `tests/Feature/Registrar/RegistrarFeaturesTest.php`
3. `tests/Feature/Finance/FinanceDashboardTest.php`
4. `tests/Feature/Teacher/TeacherFeaturesTest.php`
5. `tests/Feature/Student/StudentFeaturesTest.php`
6. `tests/Feature/Parent/ParentFeaturesTest.php`
7. `tests/Feature/SuperAdmin/AnnouncementTest.php`
8. `tests/Feature/SuperAdmin/AuditLogTest.php`
9. `tests/Feature/SuperAdmin/SuperAdminDashboardTest.php` (new)

Other:

1. `package-lock.json` updated from dependency sync (`npm install`)

### 25.5 Verification Run (This Wave)

1. `vendor/bin/pint --dirty --format agent` passed.
2. `php artisan test --compact tests/Feature/SuperAdmin/AnnouncementTest.php tests/Feature/SuperAdmin/AuditLogTest.php tests/Feature/SuperAdmin/UserManagerTest.php tests/Feature/SuperAdmin/SystemSettingsTest.php` passed (`14 passed`).
3. `php artisan test --compact tests/Feature/Admin/AdminFeaturesTest.php tests/Feature/Registrar/RegistrarFeaturesTest.php tests/Feature/Finance/FinanceDashboardTest.php tests/Feature/Teacher/TeacherFeaturesTest.php tests/Feature/Student/StudentFeaturesTest.php tests/Feature/Parent/ParentFeaturesTest.php tests/Feature/SuperAdmin/SuperAdminDashboardTest.php` passed (`53 passed`, `988 assertions`).

### 25.6 Setup Impact for Other Device

1. Pull latest code:
    - `git pull origin main`
2. Sync dependencies:
    - `composer install`
    - `npm install`
3. No new migrations were introduced in this wave.
4. If UI updates are not visible:
    - `npm run build` (or run `npm run dev`)
5. If stale runtime behavior appears:
    - `php artisan optimize:clear`
