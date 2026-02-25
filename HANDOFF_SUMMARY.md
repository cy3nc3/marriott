# HANDOFF SUMMARY

Last updated: 2026-02-25
Project path: `/home/lomonol/projects/marriott`
Primary branch: `main`
Current HEAD at update time: `81b4269`

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
4. Due reminders with configurable “N days before due” logic exists.

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

1. `grading:send-deadline-reminders` at `07:00`
2. `finance:send-due-reminders` at `07:30`
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

All passed in latest run.

---

## 12) Out of Scope / Deferred (Current)

1. Attendance module (still deferred).
2. DepEd reports module (`admin/deped-reports`) deferred.
3. SF9 generator (`admin/sf9-generator`) deferred.
4. Print/export expansions beyond current implemented pages remain optional future work.

---

## 13) Suggested Next Priorities

1. Add end-to-end tests for announcement event lifecycle (create/edit/cancel/reminder dispatch assertions).
2. Add focused UI tests for handheld compact dashboard tabs and trend expansion behavior.
3. Add backup/restore coverage audit for newly added announcement event/recipient/reminder tables and attachments.
4. Continue mobile polish for dense pages based on real-device QA findings.

---

## 14) Notes for Next AI Session

1. Read this file first, then scan routes/controllers/pages before editing.
2. Preserve shadcn-first and Tailwind utility-only layout approach.
3. Do not re-introduce DataTable wrappers unless explicitly requested.
4. Keep handheld access restrictions server-side; do not rely on frontend hiding only.
5. Keep dashboard payload contract stable (`kpis`, `alerts`, `trends`, `action_links`).

