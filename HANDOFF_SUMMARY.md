# HANDOFF SUMMARY

Last updated: 2026-03-02
Project path: `c:\Users\jadeg\Documents\Capstone\marriott`
Primary branch: `main`
Current HEAD before this documentation update: `bfab86a`

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
7. Attendance feature is implemented for teacher subject-class logging; SF2 print/export generation is still placeholder-only.

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

1. Attendance SF2 print/export generation is still placeholder-only (logging is implemented).
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

---

## 18) Latest Session Progress (2026-03-01)

### 18.1 Authentication hardening: forced password change on default credentials

1. Added `users.must_change_password`:
    - `database/migrations/2026_03_01_132135_add_must_change_password_to_users_table.php`
2. Added global middleware guard:
    - `app/Http/Middleware/EnsurePasswordChanged.php`
    - registered in web stack via `bootstrap/app.php`
3. Password update/reset now clears forced-change flag:
    - `app/Http/Controllers/Settings/PasswordController.php`
    - `app/Actions/Fortify/ResetUserPassword.php`
4. Password settings routes were adjusted so forced users can access password change without verified middleware:
    - `routes/settings.php`
5. Added feature tests:
    - `tests/Feature/ForcePasswordChangeTest.php`
    - covers redirect enforcement, update success, and logout allowance.

### 18.2 Teacher attendance module implemented (subject-teacher level, SF2-style encoding)

1. Added new teacher attendance backend:
    - `app/Http/Controllers/Teacher/AttendanceController.php`
    - `app/Http/Requests/Teacher/IndexAttendanceRequest.php`
    - `app/Http/Requests/Teacher/StoreAttendanceRequest.php`
2. Added teacher attendance routes:
    - `GET /teacher/attendance`
    - `POST /teacher/attendance`
    - file: `routes/roles/teacher.php`
3. Attendance is now subject-assignment scoped (not adviser-only per section):
    - migration `database/migrations/2026_03_01_124650_add_subject_assignment_to_attendances_table.php`
    - adds `attendances.subject_assignment_id`
    - updates unique key to `(enrollment_id, date, subject_assignment_id)`
4. Attendance statuses now aligned to requested SF2 variants:
    - `present`
    - `absent`
    - `tardy_late_comer`
    - `tardy_cutting_classes`
    - model constants updated in `app/Models/Attendance.php`
5. Postgres-safe status migration was added:
    - `database/migrations/2026_03_01_120644_update_attendance_statuses_to_sf2_variants.php`
    - includes explicit check-constraint drop/recreate flow for pgsql.
6. Added attendance UI page:
    - `resources/js/pages/teacher/attendance/index.tsx`
    - SF2-like mark cells (diagonal/two-diagonal shading logic), save action, legend, and `Print SF2` placeholder button.
7. Added reusable month picker component:
    - `resources/js/components/ui/month-picker.tsx`
8. Added attendance feature tests:
    - `tests/Feature/Attendance/*` (new attendance test suite for access and save flows).

### 18.3 Registrar remedial intake now wired to finance charging flow

1. Added remedial case domain model and schema:
    - `database/migrations/2026_03_01_104836_create_remedial_cases_table.php`
    - `app/Models/RemedialCase.php`
2. Added subject-specific remedial fee table:
    - `database/migrations/2026_03_01_112954_create_remedial_subject_fees_table.php`
    - `app/Models/RemedialSubjectFee.php`
3. Registrar remedial entry controller now supports:
    - remedial intake creation from failed annual final grades
    - computed failed-subject count
    - fee computation using per-subject override with default fallback
    - remedial case lifecycle statuses (`for_cashier_payment`, `partial_payment`, `paid`)
    - payment-gated submission of remedial results
    - file: `app/Http/Controllers/Registrar/RemedialEntryController.php`
4. Added registrar request validation class:
    - `app/Http/Requests/Registrar/StoreRemedialIntakeRequest.php`
5. Added remedial intake route:
    - `POST /registrar/remedial-entry/intake`
    - file: `routes/roles/registrar.php`
6. Remedial intake creates/updates ledger debit entry for finance collection tracking.

### 18.4 Finance fee structure extended for remedial subject pricing

1. Finance fee structure now includes remedial subject fee mapping by grade/subject for selected school year:
    - `app/Http/Controllers/Finance/FeeStructureController.php`
    - `resources/js/pages/finance/fee-structure/index.tsx`
2. Added remedial fee update request classes:
    - `app/Http/Requests/Finance/UpdateRemedialSubjectFeeRequest.php`
    - `app/Http/Requests/Finance/UpdateRemedialFeeSettingRequest.php`
3. Added route for remedial subject fee update:
    - `PATCH /finance/fee-structure/remedial-subject-fee`
    - file: `routes/roles/finance.php`
4. Current default fallback setting used when no per-subject override exists:
    - `finance_remedial_fee_per_subject` (from `settings` table).

### 18.5 Finance cashier panel and data import hardening

1. Cashier panel search and selection flow updates:
    - case-insensitive matching
    - explicit selection behavior (no unintended auto-change while typing)
    - inline `Enrollment Intakes (N)` action and quick-select dialog
    - status filtering aligned to active queue states
    - files:
        - `app/Http/Controllers/Finance/CashierPanelController.php`
        - `resources/js/pages/finance/cashier-panel/index.tsx`
2. Finance data import was expanded to support billing context from CSV:
    - reads and applies `payment_term`, `downpayment`, `enrollment_status`, `due_date`, `due_amount`, `due_description`
    - creates/updates billing schedules
    - performs due allocation rollback for re-imported OR numbers
    - re-applies payment allocations and syncs enrollment status
    - file: `app/Http/Controllers/Finance/DataImportController.php`
3. Related tests updated:
    - `tests/Feature/Finance/CashierPanelTest.php`
    - `tests/Feature/Finance/DataImportTest.php`
    - `tests/Feature/Finance/FeeStructureTest.php`

### 18.6 Registrar SF1 reconciliation updates (automatic section targeting)

1. SF1 upload no longer requires registrar to manually choose a target section.
2. SF1 import now resolves section per row using SF1 columns (`section`, with grade-level disambiguation if needed) within selected school year.
3. Enrollment reassignment now auto-updates `section_id` and `grade_level_id` when SF1 indicates a changed assignment.
4. Rows are flagged discrepancy when section cannot be resolved for the selected school year.
5. Files:
    - `app/Http/Controllers/Registrar/StudentDirectoryController.php`
    - `resources/js/pages/registrar/student-directory/index.tsx`
    - `tests/Feature/Registrar/RegistrarFeaturesTest.php`

### 18.7 Section seeding aligned with SF1 samples

1. Updated section seeding to include SF1 sample section names across grades/years:
    - `Rizal`, `Bonifacio`, `Mabini`, `Del Pilar`, `Luna`, `Aguinaldo`
2. File:
    - `database/seeders/SectionSeeder.php`

### 18.8 New enrollment intake seeder based on SF1 sample with account provisioning

1. Added new seeder:
    - `database/seeders/EnrollmentIntakeSeeder.php`
2. Added to default seeding pipeline:
    - `database/seeders/DatabaseSeeder.php`
3. Seeder behavior:
    - uses `tests/Fixtures/imports/registrar_sf1_sample.csv` as intake source
    - differentiates old/new using overlap with `tests/Fixtures/imports/registrar_permanent_records_sample.csv`
    - auto-creates/updates student and parent accounts for seeded intake rows
    - sets varied payment terms (`cash`, `monthly`, `quarterly`, `semi-annual`) and varied downpayment amounts
    - seeds varied discrepancy scenarios for SF1 upload tests:
        - `student-not-found`: intentionally left-out old + new students
        - `no-active-enrollment`: profile-only and past-year-only seeded learners
4. Added seeder test:
    - `tests/Feature/EnrollmentIntakeSeederTest.php`

### 18.9 Manual enrollment test students intentionally left out by seeder

These are excluded by `EnrollmentIntakeSeeder` so they can be enrolled manually through registrar intake UI:

1. Old student not seeded:
    - LRN: `120000000001`
    - Name: `Sofia Castro`
    - Gender: `Female`
    - Birthdate: `2010-06-10`
    - Grade/Section from SF1: `Grade 7 / Rizal`
    - Guardian: `Rosario Aquino`
    - Contact: `09171000001`
2. New student not seeded:
    - LRN: `130000000001`
    - Name: `Xavier Espino`
    - Gender: `Male`
    - Birthdate: `2011-01-08`
    - Grade/Section from SF1: `Grade 7 / Luna`
    - Guardian: `Corazon Aquino`
    - Contact: `09181000001`

### 18.10 Migrations added in this session

1. `database/migrations/2026_03_01_104836_create_remedial_cases_table.php`
2. `database/migrations/2026_03_01_112954_create_remedial_subject_fees_table.php`
3. `database/migrations/2026_03_01_120644_update_attendance_statuses_to_sf2_variants.php`
4. `database/migrations/2026_03_01_124650_add_subject_assignment_to_attendances_table.php`
5. `database/migrations/2026_03_01_132135_add_must_change_password_to_users_table.php`

### 18.11 Required setup after pulling this session

1. Pull latest and install deps:
    - `git pull origin main`
    - `composer install`
    - `npm install`
2. Apply DB changes:
    - `php artisan migrate`
3. Seed data (if using sample QA dataset):
    - `php artisan db:seed --class=SectionSeeder --no-interaction`
    - `php artisan db:seed --class=EnrollmentIntakeSeeder --no-interaction`
    - or full seed: `php artisan db:seed --no-interaction`
4. Build/run frontend:
    - `npm run dev` or `npm run build`

### 18.12 Validation run for this update wave

1. `vendor/bin/pint --dirty --format agent`
2. `npm run types`
3. `php artisan test --compact tests/Feature/EnrollmentIntakeSeederTest.php`
4. `php artisan test --compact tests/Feature/Registrar/RegistrarFeaturesTest.php --filter="sf1 upload"`

---

## 19) Latest Session Progress (2026-03-02)

### 19.1 Academic controls initialization + no-active-year setup hardening

1. School year setup flow was hardened for first-run scenarios where no active school year exists.
2. Date handling was updated so school-year dates can remain unset during initial cycle setup and be filled later.
3. Admin academic controls behavior now supports explicit first-year initialization flow before normal quarter progression.
4. Files:
    - `app/Http/Controllers/Admin/SchoolYearController.php`
    - `app/Http/Requests/Admin/InitializeAcademicYearRequest.php`
    - `resources/js/pages/admin/academic-controls/index.tsx`

### 19.2 Enrollment status normalization (queue consistency)

1. Enrollment queue statuses were normalized to remove partial-payment status drift from old records.
2. Current registrar/finance intake queue behavior is aligned around `for_cashier_payment` and enrolled progression logic.
3. Migration added:
    - `database/migrations/2026_03_02_054148_normalize_enrollment_partial_payment_statuses.php`

### 19.3 Parent portal disabled experience wired

1. Parent-portal-disabled handling now routes to a dedicated Inertia page instead of blank/unstyled fallback paths.
2. Middleware handling for disabled parent portal now consistently renders user-facing context.
3. Files:
    - `app/Http/Middleware/EnsureParentPortalEnabled.php`
    - `resources/js/pages/parent/portal-disabled.tsx`
    - `tests/Feature/Parent/ParentFeaturesTest.php`

### 19.4 Registrar SF1 upload reliability + LIS discrepancy UX

1. Student Directory SF1 upload form now synchronizes selected school year reliably in request payload.
2. Upload validation feedback for `sf1_file` and `academic_year_id` is now shown inline to avoid silent failure.
3. Desktop-only upload behavior is now explicitly shown in UI when on handheld.
4. LIS discrepancy now shows as:
    - badge label remains `Discrepancy`
    - detailed reason shown via tooltip on hover
5. Backend payload now includes `lis_status_reason` for discrepancy rows.
6. Files:
    - `app/Http/Controllers/Registrar/StudentDirectoryController.php`
    - `resources/js/pages/registrar/student-directory/index.tsx`
    - `tests/Feature/Registrar/RegistrarFeaturesTest.php`

### 19.5 Finance and enrollment lifecycle recomputation updates

1. Finance and registrar flow updates were applied to keep intake, due computation, cashier selection, and ledger/reporting views synchronized.
2. Billing schedule recomputation logic was updated to preserve intended due sequencing under edited intake contexts.
3. Affected backend files:
    - `app/Http/Controllers/Finance/CashierPanelController.php`
    - `app/Http/Controllers/Finance/DailyReportsController.php`
    - `app/Http/Controllers/Finance/DataImportController.php`
    - `app/Http/Controllers/Finance/DiscountManagerController.php`
    - `app/Http/Controllers/Finance/StudentLedgersController.php`
    - `app/Http/Controllers/Finance/TransactionHistoryController.php`
    - `app/Http/Controllers/Registrar/EnrollmentController.php`
    - `app/Services/Finance/BillingScheduleService.php`
    - `app/Models/Transaction.php`

### 19.6 User/account defaults + seeded dataset updates

1. User manager and enrollment-linked account generation behavior were updated to match revised password format and role flow expectations.
2. Seeder flows were updated to reflect new academic-year initialization assumptions and enrollment-intake QA dataset behavior.
3. Files:
    - `app/Http/Controllers/SuperAdmin/UserManagerController.php`
    - `database/seeders/AcademicYearSeeder.php`
    - `database/seeders/EnrollmentIntakeSeeder.php`
    - `database/seeders/DatabaseSeeder.php`
    - `app/Services/AnnouncementAudienceResolver.php` (audience resolution compatibility updates)

### 19.7 Teacher feature gating alignment

1. Teacher attendance and grading editability logic was aligned with quarter-status gating behavior used in simulation/testing workflows.
2. Files:
    - `app/Http/Controllers/Teacher/AttendanceController.php`
    - `app/Http/Controllers/Teacher/GradingSheetController.php`
    - `resources/js/pages/teacher/attendance/index.tsx`
    - `resources/js/pages/teacher/grading-sheet/index.tsx`

### 19.8 Additional migration added in this session

1. `database/migrations/2026_03_02_043859_make_academic_year_dates_nullable.php`
    - allows nullable `academic_years.start_date` and `academic_years.end_date` to support setup-before-dates workflow.

### 19.9 Validation coverage touched in this session

1. Updated/expanded tests across:
    - `tests/Feature/Admin/AdminFeaturesTest.php`
    - `tests/Feature/EnrollmentIntakeSeederTest.php`
    - `tests/Feature/Finance/CashierPanelTest.php`
    - `tests/Feature/Finance/DailyReportsTest.php`
    - `tests/Feature/Finance/DataImportTest.php`
    - `tests/Feature/Finance/DiscountManagerTest.php`
    - `tests/Feature/Finance/StudentLedgersTest.php`
    - `tests/Feature/Finance/TransactionHistoryTest.php`
    - `tests/Feature/Parent/ParentFeaturesTest.php`
    - `tests/Feature/Registrar/RegistrarFeaturesTest.php`
    - `tests/Feature/SuperAdmin/UserManagerTest.php`
    - `tests/Feature/Teacher/TeacherFeaturesTest.php`

### 19.10 Required setup after pulling this session

1. Run migrations:
    - `php artisan migrate`
2. If reseeding test dataset:
    - `php artisan db:seed --no-interaction`
3. Rebuild assets if needed:
    - `npm run dev` or `npm run build`

### 19.11 Quick verification commands

1. `npm run types`
2. `php artisan test --compact tests/Feature/Registrar/RegistrarFeaturesTest.php --filter="sf1"`
3. `php artisan test --compact tests/Feature/Parent/ParentFeaturesTest.php`
4. `vendor/bin/pint --dirty --format agent`

---

## 20) Latest Session Progress (2026-03-02, Auth + Header + Sidebar UX)

### 20.1 Password peek/show-hide added system-wide

1. Implemented a reusable password input component with inline show/hide toggle.
2. Replaced all existing password fields that previously used raw `type="password"` in:
    - login form
    - confirm password page
    - reset password page
    - settings password page
    - delete account confirmation dialog
3. New/updated files:
    - `resources/js/components/ui/password-input.tsx` (new)
    - `resources/js/components/login-form.tsx`
    - `resources/js/pages/auth/confirm-password.tsx`
    - `resources/js/pages/auth/reset-password.tsx`
    - `resources/js/pages/settings/password.tsx`
    - `resources/js/components/delete-user.tsx`

### 20.2 Login welcome Sonner toast wired through Fortify

1. Added backend login-response flash payload for successful auth:
    - title: `Welcome, [First Name]!`
    - description: `Logged in as [Role]`
2. Supports both normal login and two-factor completion by binding custom Fortify response contracts.
3. Added shared Inertia flash key with dedupe key support.
4. New/updated backend files:
    - `app/Http/Responses/Concerns/InteractsWithLoginWelcomeToast.php` (new)
    - `app/Http/Responses/FortifyLoginResponse.php` (new)
    - `app/Http/Responses/FortifyTwoFactorLoginResponse.php` (new)
    - `app/Providers/FortifyServiceProvider.php`
    - `app/Http/Middleware/HandleInertiaRequests.php`
5. New/updated frontend files:
    - `resources/js/components/ui/sonner.tsx` (new)
    - `resources/js/components/login-welcome-toast.tsx` (new)
    - `resources/js/layouts/app/app-sidebar-layout.tsx`
    - `resources/js/types/index.ts`
6. Added/updated test coverage:
    - `tests/Feature/Auth/AuthenticationTest.php`
      - verifies login flash contains expected welcome title + role description

### 20.3 Sonner behavior refinement (per latest UI request)

1. Toast now uses success type (`toast.success`) for login welcome notice.
2. Toast position moved to top-center.
3. Enabled rich success styling via shadcn Sonner wrapper for stronger visual notice (green success style).
4. Files:
    - `resources/js/components/login-welcome-toast.tsx`
    - `resources/js/components/ui/sonner.tsx`

### 20.4 Active school year badge in top header near notifications

1. Added shared `active_academic_year` payload in global Inertia props:
    - prioritizes `ongoing`
    - falls back to `upcoming`
    - null when no configured year exists
2. Displayed active school year badge on the right side of the notification bell in the sidebar header bar.
3. Files:
    - `app/Http/Middleware/HandleInertiaRequests.php`
    - `resources/js/components/app-sidebar-header.tsx`
    - `resources/js/types/index.ts`
4. Added coverage:
    - `tests/Feature/DashboardTest.php`
      - verifies shared active school year payload is present and correct

### 20.5 Sidebar branding update

1. Sidebar logo mark now uses the same circular `M` style as the login/tab branding.
2. Sidebar title text changed from static `Marriott` to the authenticated user’s role label.
3. Files:
    - `resources/js/components/app-logo.tsx`
    - `resources/js/components/app-sidebar.tsx`

### 20.6 Dependency updates in this session

1. Added Sonner dependency:
    - `package.json`
    - `package-lock.json`

### 20.7 Migrations required for this session

1. None.

### 20.8 Required setup after pulling this session

1. Install frontend dependencies:
    - `npm install`
2. Run app:
    - `npm run dev` (or build with `npm run build`)

### 20.9 Verification run executed

1. `npm run types`
2. `vendor/bin/pint --dirty --format agent`
3. `php artisan test --compact tests/Feature/Auth/AuthenticationTest.php`
4. `php artisan test --compact tests/Feature/DashboardTest.php`
