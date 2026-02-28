# HANDOFF SUMMARY

Last updated: 2026-02-28
Project path: `/home/lomonol/projects/marriott`
Primary branch: `main`
Current HEAD before this documentation update: `8fab87b`

---

## 1) Current Product State

This is a role-based Laravel + Inertia school operations system for a DepEd-aligned private school workflow.

Core system goals currently implemented:

1. Reduce duplicate work vs. DepEd LIS by using SF1 import/enrichment.
2. Keep registrar-finance-teacher operations connected in one system.
3. Improve school-to-staff/parent/student communication through announcements + notifications + event acknowledgement/RSVP.
4. Provide role-based analytics dashboards with actionable summaries.
5. Support mobile handheld access for communication/light operations while hard-blocking sensitive desktop-only workflows.

---

## 2) Locked Product Rules to Preserve

1. Shadcn-first UI architecture. Use existing shadcn components; avoid rewriting base component internals.
2. Tailwind utility classes for layout/spacing updates.
3. SF1 upload drives LIS enrichment; no manual one-by-one reconciliation workflow.
4. Enrollment + cashier flows remain operationally separate, but linked.
5. Student dashboard is learning-only (no billing details).
6. Teacher dashboard completion tracks grade finalization/class rows, not deadline-based submission tracking.
7. Attendance feature remains deferred/out of scope for now.

---

## 3) Role Coverage (Implemented)

### Super Admin

1. Dashboard
2. User Manager
3. Announcements
4. Announcement report/analytics
5. Audit logs
6. Permissions
7. System settings

### Admin

1. Dashboard
2. Academic controls
3. Curriculum manager
4. Section manager
5. Schedule builder
6. Grade verification
7. Class lists

### Registrar

1. Dashboard
2. Student directory + SF1 upload
3. Enrollment intake queue
4. Permanent records (improved production UI)
5. Batch promotion monitor/review
6. Remedial entry
7. Student departure

### Finance

1. Dashboard
2. Cashier panel
3. Student ledgers
4. Transaction history (void/refund/reissue support present)
5. Fee structure (school-year versioned)
6. Product pricing manager
7. Discount manager
8. Daily reports
9. Due reminder settings

### Teacher

1. Dashboard
2. Schedule
3. Grading sheet
4. Advisory board

### Student

1. Dashboard
2. Schedule
3. Grades
4. Notifications inbox/detail

### Parent

1. Dashboard
2. Schedule
3. Grades
4. Billing information
5. Notifications inbox/detail

---

## 4) Communication System (Current)

Announcements are now a full communication backbone with two modes:

1. `notice` (view-only announcements)
2. `event` (actionable announcements with acknowledge + RSVP)

Implemented behavior:

1. Role-scoped publishing and strict audience targeting rules.
2. Event fields on announcements (start/end/deadline/cancel metadata).
3. Recipient snapshot table for per-announcement audience tracking.
4. Event response table (ack / yes / no / maybe).
5. Reminder dispatch idempotency table for one-day-before and day-of reminders.
6. Attachment support on announcements with protected preview/download routes.
7. Notification inbox and full announcement detail page.
8. Read tracking and unread counters wired globally.

Key files:

1. `app/Http/Controllers/SuperAdmin/AnnouncementController.php`
2. `app/Http/Controllers/AnnouncementNotificationController.php`
3. `app/Services/AnnouncementEventService.php`
4. `app/Services/AnnouncementResponseService.php`
5. `app/Services/AnnouncementEventReminderService.php`
6. `app/Services/AnnouncementAudienceResolver.php`

---

## 5) Mobile Access Policy + UX (Implemented)

### 5.1 Server-side handheld enforcement

Implemented:

1. Handheld detector service: `app/Services/HandheldDeviceDetector.php`
2. Desktop-only middleware: `app/Http/Middleware/EnsureDesktopOnlyRoute.php`
3. Middleware alias in `bootstrap/app.php`: `desktop_only`
4. Shared Inertia prop in `HandleInertiaRequests`: `ui.is_handheld`
5. Restricted routes are blocked server-side for handheld, with:
    - GET -> `mobile/desktop-required` page (403)
    - non-GET -> `403 Desktop device required for this action.`

### 5.2 Mobile navigation

Implemented:

1. Handheld sidebar filtering by role allowed routes.
2. Role-based bottom quick nav tabs.
3. Retained sidebar sheet as secondary navigation.

Key files:

1. `resources/js/components/mobile-quick-nav.tsx`
2. `resources/js/components/app-sidebar.tsx`
3. `resources/js/layouts/app/app-sidebar-layout.tsx`
4. `resources/js/layouts/app/app-header-layout.tsx`
5. `resources/js/pages/mobile/desktop-required.tsx`

### 5.3 Mobile UX compact pass

Implemented latest pass:

1. Compact dashboards for all roles.
2. Handheld dashboard uses KPI strip + tabs (`Alerts`, `Trends`, `Actions`).
3. Desktop dashboards kept functionally same with lighter compacting.
4. Trend list supports mobile show-more/show-less.
5. Communication and table-heavy mobile-allowed pages were compacted to reduce vertical scroll.

Main file:

1. `resources/js/components/dashboard/analytics-panel.tsx`

Role-specific dashboard compact strips:

1. `resources/js/pages/teacher/dashboard.tsx`
2. `resources/js/pages/student/dashboard.tsx`
3. `resources/js/pages/parent/dashboard.tsx`

---

## 6) PWA State (Implemented)

Implemented:

1. Manifest + service worker + offline fallback page.
2. Install prompt available for authenticated roles.
3. Service worker update-detection flow with update action and reload.
4. Cache versioning and old-cache cleanup.

Key files:

1. `public/manifest.webmanifest`
2. `public/sw.js`
3. `public/offline.html`
4. `resources/js/app.tsx`
5. `resources/js/components/pwa-install-banner.tsx`
6. `resources/views/app.blade.php`

---

## 7) Finance Billing/Collections Notes (Current Behavior)

Implemented behaviors to preserve:

1. Dues schedule generation by payment plan with school-year-aware windowing.
2. Partial-payment carry-over allocation across dues (oldest due prioritized/FIFO style).
3. Transaction due allocation tracking table exists for exact rollback paths.
4. Due reminders are now hybrid-configurable:
    - Rule-level lead days (`N days before due`) using `finance_due_reminder_rules`.
    - Automation-level controls in `settings`:
        - `finance_due_reminder_auto_send_enabled`
        - `finance_due_reminder_send_time`
        - `finance_due_reminder_max_announcements_per_run` (optional limiter)

---

## 8) Migrations Added in This Stream

Recent migrations that must be present in target DB:

1. `2026_02_25_075316_add_event_fields_to_announcements_table.php`
2. `2026_02_25_075320_create_announcement_recipients_table.php`
3. `2026_02_25_075326_create_announcement_event_responses_table.php`
4. `2026_02_25_075328_create_announcement_reminder_dispatches_table.php`

Related previously-added required migrations (already part of current main):

1. `2026_02_24_160337_create_finance_due_reminder_rules_table.php`
2. `2026_02_24_160338_create_finance_due_reminder_dispatches_table.php`
3. `2026_02_24_183220_create_transaction_due_allocations_table.php`
4. `2026_02_24_183224_add_void_columns_to_transactions_table.php`
5. `2026_02_24_201627_add_academic_year_id_to_fees_table.php`
6. `2026_02_24_201632_add_refund_and_reissue_columns_to_transactions_table.php`

---

## 9) Scheduled Commands (Important)

Configured in `routes/console.php`:

1. `grading:send-deadline-reminders` runs every minute, but execution is gated by settings:
    - `grade_deadline_reminder_auto_send_enabled` (default: true)
    - `grade_deadline_reminder_send_time` (default: `07:00`)
    - supports manual override with `--force`
2. `finance:send-due-reminders` runs every minute, but execution is gated by settings:
    - `finance_due_reminder_auto_send_enabled` (default: true)
    - `finance_due_reminder_send_time` (default: `07:30`)
    - optional limiter `finance_due_reminder_max_announcements_per_run`
    - supports manual override with `--force`
3. `announcements:send-event-reminders` at `08:00`

For reminders to actually send, scheduler must run in environment:

1. dev: run `php artisan schedule:work`
2. prod: set cron for `php artisan schedule:run` every minute

---

## 10) Setup / Pull / Run Checklist

This next AI is explicitly allowed to run required installs and setup commands.

### Fresh machine setup

1. `composer install`
2. `npm install`
3. copy `.env` and set DB credentials
4. `php artisan key:generate`
5. `php artisan migrate --seed`
6. `npm run build` (or `npm run dev`)

### Existing machine after pull

1. `git pull origin main`
2. `composer install`
3. `npm install`
4. `php artisan migrate`
5. `npm run build` (or keep `npm run dev` running)
6. if scheduled reminders are being tested, run `php artisan schedule:work`

### Dev run options

1. `composer run dev` (preferred all-in-one if configured)
2. or separate terminals:
    - `php artisan serve --host=0.0.0.0 --port=8000`
    - `php artisan queue:listen --tries=1 --timeout=0`
    - `npm run dev`

### If frontend looks stale

1. ensure Vite is running OR rebuild: `npm run build`
2. hard refresh browser
3. clear app cache if needed: `php artisan optimize:clear`

---

## 11) Required Quality Checks After Edits

1. `vendor/bin/pint --dirty --format agent`
2. `npm run -s types`
3. `php artisan test --compact <targeted tests>`

Recent targeted verification (latest wave):

1. `tests/Feature/DashboardTest.php`
2. `tests/Feature/MobileAccessPolicyTest.php`
3. `tests/Feature/RoleAccessTest.php`
4. `tests/Feature/Auth/AuthenticationTest.php`
5. `tests/Feature/Finance/DueReminderSettingsTest.php`
6. `tests/Feature/Finance/DueReminderNotificationsTest.php`
7. `tests/Feature/Admin/GradeVerificationTest.php`

All passed in latest run.

---

## 12) Out of Scope / Deferred (Current)

1. Attendance module (still deferred).
2. DepEd reports module (`admin/deped-reports`) deferred.
3. SF9 generator (`admin/sf9-generator`) deferred.
4. Print/export expansions beyond current implemented pages remain optional future work.

---

## 13) Suggested Next Priorities

1. Use `SYSTEM_FLOWCHART.md` as the implementation map for any next module work so new flows stay consistent with the documented lifecycle.
2. Add the still-deferred communication flowchart slice if announcements / event acknowledgement workflows need to be brought into the same system diagram later.
3. Add end-to-end tests for announcement event lifecycle (create/edit/cancel/reminder dispatch assertions).
4. Add focused UI tests for handheld compact dashboard tabs and trend expansion behavior.
5. Add backup/restore coverage audit for newly added announcement event/recipient/reminder tables and attachments.
6. Continue mobile polish for dense pages based on real-device QA findings.

---

## 14) Notes for Next AI Session

1. Read this file first, then scan routes/controllers/pages before editing.
2. Preserve shadcn-first and Tailwind utility-only layout approach.
3. Do not re-introduce DataTable wrappers unless explicitly requested.
4. Keep handheld access restrictions server-side; do not rely on frontend hiding only.
5. Keep dashboard payload contract stable (`kpis`, `alerts`, `trends`, `action_links`).

---

## 15) Latest Session Progress (2026-02-26)

### 15.1 Finance due reminder automation (implemented)

1. Added backend automation settings for due reminders:
    - `finance_due_reminder_auto_send_enabled`
    - `finance_due_reminder_send_time`
    - `finance_due_reminder_max_announcements_per_run` (nullable)
2. Added validation request:
    - `app/Http/Requests/Finance/UpdateDueReminderAutomationSettingsRequest.php`
3. Added controller handler + Inertia payload support:
    - `app/Http/Controllers/Finance/DueReminderSettingsController.php`
    - now returns `automation` payload to the Finance settings page
4. Added route:
    - `PATCH /finance/due-reminder-settings/automation`
    - file: `routes/roles/finance.php`
5. Updated Finance UI:
    - `resources/js/pages/finance/due-reminder-settings/index.tsx`
    - new Reminder Automation card with toggle, send time, optional max per run
6. Updated reminder command:
    - `app/Console/Commands/SendFinanceDueRemindersCommand.php`
    - added schedule gating using settings
    - added `--force` and retained `--date` support for manual runs
7. Updated reminder service:
    - `app/Services/Finance/DueReminderNotificationService.php`
    - added optional max-per-run limiter
    - summary now includes `skipped_due_to_run_limit`

### 15.2 Admin grade submission reminder automation (implemented)

1. Added validation request:
    - `app/Http/Requests/Admin/UpdateGradeReminderAutomationRequest.php`
2. Extended grade verification controller:
    - `app/Http/Controllers/Admin/GradeVerificationController.php`
    - now exposes `context.reminder_automation`
    - new handler `updateReminderAutomation(...)`
3. Added route:
    - `PATCH /admin/grade-verification/reminder-automation`
    - file: `routes/roles/admin.php`
4. Updated admin grade verification page:
    - `resources/js/pages/admin/grade-verification/index.tsx`
    - added Auto Reminder controls in header (toggle + send time + save action)
5. Updated grade reminder command:
    - `app/Console/Commands/SendGradeDeadlineRemindersCommand.php`
    - now checks automation settings before sending
    - added `--force` support

### 15.3 Scheduler behavior changes

1. `routes/console.php` updates:
    - `grading:send-deadline-reminders` -> `everyMinute()`
    - `finance:send-due-reminders` -> `everyMinute()`
2. Both commands now self-gate by configured time and enabled state from `settings`.

### 15.4 Compatibility and stability fix

1. Updated reminder services to accept immutable Carbon values from `now()` during tests:
    - `app/Services/Finance/DueReminderNotificationService.php` uses `CarbonInterface`
    - `app/Services/GradeDeadlineAnnouncementService.php` uses `CarbonInterface` for reminder date evaluation

### 15.5 Generated routes / tooling

1. Regenerated Wayfinder mappings after adding new routes:
    - `php artisan wayfinder:generate --with-form --no-interaction`
2. Note: generated outputs remain in ignored directories (`resources/js/routes`, `resources/js/actions`) per existing repo setup.

### 15.6 Validation commands run in this session

1. `npx prettier --write resources/js/pages/finance/due-reminder-settings/index.tsx`
2. `npx prettier --write resources/js/pages/admin/grade-verification/index.tsx`
3. `vendor/bin/pint --dirty --format agent`
4. `npm run types`
5. `php artisan test --compact tests/Feature/Finance/DueReminderSettingsTest.php tests/Feature/Finance/DueReminderNotificationsTest.php`
6. `php artisan test --compact tests/Feature/Admin/GradeVerificationTest.php`

All above commands passed after final fixes.

### 15.7 Migration impact

1. No new migrations were added in this session.
2. All new behavior is wired through existing tables (`settings`, `finance_due_reminder_rules`, `finance_due_reminder_dispatches`, existing announcement tables).

---

## 16) Latest Session Progress (2026-02-27)

### 16.1 System flowchart documentation added

1. Added a new documentation artifact:
    - `SYSTEM_FLOWCHART.md`
2. Added an exported flowchart image artifact:
    - `flowchart.svg`
3. The flowchart is now a single integrated Mermaid system map covering:
    - super admin governance
    - admin academic controls
    - registrar enrollment intake
    - finance billing setup, enrollment cashiering, and midyear transactions
    - LIS / SF1 enrichment
    - teacher grading and advisory workflow
    - admin grade verification
    - student and parent portal flows
    - year-end promotion / retention / conditional routing
    - remedial processing

### 16.2 Key flowchart decisions captured

1. Student and parent accounts originate from enrollment intake account creation:
    - student portal source: `Create student account`
    - parent portal source: `Create parent account and link`
2. Student and parent schedule visibility originates from:
    - `Publish academic setup for the school year`
3. SF1 is documented as:
    - roster confirmation
    - mismatch detection
    - final regularization of the school-year student record
4. Adviser is the uploader of SF1 in the documented workflow.
5. Finance flow now distinguishes:
    - enrollment-time payment handling
    - midyear assessment/custom/product transactions
    - transaction correction / void recomputation
6. Year-end flow now distinguishes:
    - promoted
    - conditional
    - failed / retained
    - completed / terminal
    - remedial routing
    - next-school-year enrollment creation
    - archive / account reuse loop

### 16.3 Notes for the next AI

1. Treat `SYSTEM_FLOWCHART.md` as a planning and consistency reference, not as executable specification.
2. If future product decisions change the workflow, update the flowchart first or alongside implementation so the docs remain trustworthy.
3. `flowchart.svg` is a generated visual export of the Mermaid flowchart and is intended to be versioned with the project.

### 16.4 Setup / migration impact for this session

1. No migrations were added.
2. No backend/frontend runtime behavior was changed in this session.
3. No additional setup commands are required beyond the existing checklist.

### 16.5 Validation for this session

1. No tests were run because this session only added and refined documentation artifacts.

---

## 17) Latest Session Progress (2026-02-28)

### 17.1 Registrar enrollment intake flow overhaul (implemented)

1. Enrollment intake UI was refactored into a guided multi-step flow in `resources/js/pages/registrar/enrollment/index.tsx`.
2. Intake now captures expanded student identity and guardian details:
    - `lrn` (strict numeric)
    - `first_name`
    - `middle_name`
    - `last_name`
    - `gender`
    - `birthdate`
    - `guardian_name`
    - `guardian_contact_number`
3. Grade level and section assignment are linked in the form:
    - grade level is explicit
    - section choices are filtered by selected grade level
4. Payment setup remains in intake:
    - `payment_term`
    - `downpayment`
5. Intake queue statuses were simplified to:
    - `for_cashier_payment`
    - `partial_payment`
6. Legacy pending states were removed from active registrar workflow:
    - `pending`
    - `pending_intake`
7. Enrollment create/update now store richer student profile data in `app/Http/Controllers/Registrar/EnrollmentController.php`.
8. Enrollment validation now enforces:
    - LRN exactly 12 digits
    - guardian contact exactly 11 digits
    - required birthdate (`before_or_equal:today`)
9. Student account provisioning behavior changed:
    - student email format: `<normalized-surname>.<lrn>@marriott.edu`
    - surname normalization removes spaces/dashes/special characters (example: `Dela Cruz`, `Dela-Cruz` -> `delacruz`)
    - student default password format: `<first-firstname-token>@<mmddyyyy>` from birthdate
10. Parent account linkage remains LRN-based and still auto-created on intake.

### 17.2 Enrollment-related backend and lifecycle normalization

1. `app/Models/Student.php` now includes `middle_name` as fillable.
2. `app/Services/Registrar/BatchPromotionService.php` now creates next-year rows with `for_cashier_payment` status (no `pending_intake`).
3. `app/Http/Controllers/Registrar/DashboardController.php` queue metrics now reflect the normalized status model.
4. `app/Services/AnnouncementAudienceResolver.php` finance audience scope no longer uses legacy pending statuses.

### 17.3 Cashier panel rework for real-time registrar handoff

1. `app/Http/Controllers/Finance/CashierPanelController.php` now exposes:
    - improved student option resolver limited to active-year students in `for_cashier_payment`
    - case-insensitive matching using lowered fields
    - new JSON suggestions endpoint: `GET /finance/cashier-panel/student-suggestions`
    - pending intakes payload + count for cashier quick-pick dialog
2. `resources/js/pages/finance/cashier-panel/index.tsx` was updated to:
    - remove the old student select dropdown
    - keep search bar + search button as explicit query trigger
    - avoid unintended auto-selection while typing
    - fetch lightweight async suggestions (max 5) for targeted cashier lookups
    - add inline `Enrollment Intakes (N)` button
    - show intake table in dialog with quick-select action to load transaction target student
3. Cashier search/filter behavior now prioritizes concurrent registrar-finance operation by surfacing active intake candidates immediately.

### 17.4 New data import modules (Registrar + Finance)

1. Added Registrar data import backend:
    - `app/Http/Controllers/Registrar/DataImportController.php`
    - `app/Http/Requests/Registrar/ImportPermanentRecordsRequest.php`
2. Added Registrar data import page:
    - `resources/js/pages/registrar/data-import/index.tsx`
3. Added Registrar routes:
    - `GET /registrar/data-import`
    - `POST /registrar/data-import/permanent-records`
4. Registrar import behavior:
    - accepts CSV uploads
    - maps historical student records (school year, LRN, names, gender, birthday, grade level, section, grades/status)
    - creates/updates student, academic year, grade level, section, enrollment, and permanent record rows
    - writes import snapshot + import history to audit logs and settings
5. Added Finance data import backend:
    - `app/Http/Controllers/Finance/DataImportController.php`
    - `app/Http/Requests/Finance/ImportFinanceTransactionsRequest.php`
6. Added Finance data import page:
    - `resources/js/pages/finance/data-import/index.tsx`
7. Added Finance routes:
    - `GET /finance/data-import`
    - `POST /finance/data-import/transactions`
8. Finance import behavior:
    - imports historical transaction CSV rows
    - creates/updates student + enrollment context as needed
    - creates/updates transactions by OR number
    - recreates transaction item row and corresponding imported ledger entry
    - records import snapshots in audit logs/settings
9. Sidebar navigation was updated for both roles (`resources/js/components/app-sidebar.tsx`) to expose `Data Import` entries under Registrar and Finance.

### 17.5 Search UX consistency update

1. `resources/js/components/ui/search-autocomplete-input.tsx` now supports optional `showSuggestions`.
2. Pages using table-filter style search now disable typeahead suggestions (`showSuggestions={false}`) to avoid noisy dropdown behavior on generic list filtering.
3. Applied in:
    - `resources/js/pages/finance/product-inventory/index.tsx`
    - `resources/js/pages/finance/student-ledgers/index.tsx`
    - `resources/js/pages/finance/transaction-history/index.tsx`
    - `resources/js/pages/notifications/inbox/index.tsx`
    - `resources/js/pages/registrar/permanent-records/index.tsx`
    - `resources/js/pages/super_admin/announcements/index.tsx`
    - `resources/js/pages/super_admin/announcements/report.tsx`
    - `resources/js/pages/super_admin/audit-logs/index.tsx`
    - `resources/js/pages/super_admin/user-manager/index.tsx`

### 17.6 Super admin search behavior hardening

1. `app/Http/Controllers/SuperAdmin/UserManagerController.php` search is now case-insensitive over `name`, `first_name`, `last_name`, and `email`.

### 17.7 Login/auth screen redesign pass

1. Login page was redesigned in `resources/js/pages/auth/login.tsx` to use a dedicated illustration-led split layout.
2. Login hero now uses SIS-oriented messaging and custom branding marker (`M`) in the left panel.
3. Added auth illustrations:
    - `public/images/auth/login-online-learning.svg` (active)
    - `public/images/auth/login-education.svg`
    - `public/images/auth/login-students-parents.svg`
4. Right panel was simplified to focus on the form only (removed extra header branding text/icons).
5. Login form controls were scaled for better readability in `resources/js/components/login-form.tsx`:
    - larger heading/subtext
    - taller inputs/buttons (`h-12`)
    - larger control typography

### 17.8 Migrations added in this session

1. `database/migrations/2026_02_28_115916_add_middle_name_to_students_table.php`
    - adds nullable `students.middle_name`
2. `database/migrations/2026_02_28_135937_normalize_pending_intake_statuses_in_enrollments_table.php`
    - converts enrollment statuses `pending` and `pending_intake` to `for_cashier_payment`

### 17.9 Test coverage updates in this session

1. Expanded registrar feature tests in `tests/Feature/Registrar/RegistrarFeaturesTest.php`:
    - new enrollment validation assertions (LRN, birthdate, guardian contact)
    - student account email/password generation assertions
    - status normalization assertions
    - registrar data import coverage
2. Expanded finance cashier tests in `tests/Feature/Finance/CashierPanelTest.php`:
    - pending intake population/count checks
    - case-insensitive search checks
    - LRN search behavior checks
    - async suggestions endpoint checks
3. Added finance import feature tests:
    - `tests/Feature/Finance/DataImportTest.php`
4. Added case-insensitive user manager search test:
    - `tests/Feature/SuperAdmin/UserManagerTest.php`
5. Updated admin feature expectations for normalized statuses:
    - `tests/Feature/Admin/AdminFeaturesTest.php`

### 17.10 Required setup after pulling this session

1. Run migrations:
    - `php artisan migrate`
2. Regenerate frontend build/dev bundle as usual:
    - `npm run dev` or `npm run build`
3. If validating this wave, prioritize these tests:
    - `php artisan test --compact tests/Feature/Registrar/RegistrarFeaturesTest.php`
    - `php artisan test --compact tests/Feature/Finance/CashierPanelTest.php`
    - `php artisan test --compact tests/Feature/Finance/DataImportTest.php`
    - `php artisan test --compact tests/Feature/SuperAdmin/UserManagerTest.php`
