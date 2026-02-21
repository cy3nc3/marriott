# HANDOFF SUMMARY

Last updated: 2026-02-21
Project path: `/home/lomonol/projects/marriott`
Primary branch target: `main`

---

## 1. Product Context (Must Preserve)

This is a school system for a DepEd-recognized school in the Philippines.

Core constraint: avoid double encoding because DepEd LIS remains the primary enrollment source.

### Enrollment/LIS model in this project

1. Registrar encodes a minimal enrollment intake first (queue/floating transaction model).
2. Cashier processes payment after registrar intake.
3. SF1 upload later auto-enriches student records by `LRN`.
4. SF1 contains school year; uploader should not ask for school year manually.
5. LIS reconciliation should be automatic, not manual row-by-row matching.

---

## 2. Non-Negotiable Product/UX Instructions From User

These must be followed in future development unless user changes direction.

1. Use shadcn components across the project.
2. Avoid custom CSS hacks against shadcn internals.
3. Use Tailwind utilities mainly for layout/positioning and page-level styling.
4. Keep production-style pages (not step-by-step visualization UIs).
5. Keep UI simple, low-scroll, and staff-efficient.
6. Keep code syntax beginner-friendly/readable.
7. Remove unnecessary card header descriptions in production pages.
8. Keep visual consistency across all modules.

### Confirmed functional decisions

1. Enrollment queue removes entries once payment is completed.
2. LIS-match concern sits in Student Directory flow.
3. SF1 upload is a direct file-selection button pattern.
4. Cashier panel is one-page flow.
5. Transaction processing uses a confirmation dialog.
6. Product inventory is pricing-only (no stock management).
7. Discount programs table uses actions (`Edit`, `Delete`) instead of status.
8. Date-range display is `MM/DD/YYYY`.

### Explicitly deferred/ignored for now

1. Registrar batch promotion
2. Registrar permanent records
3. Registrar student departure
4. Admin DepEd reports
5. Admin SF9 generator
6. Finance print/export workflows (deferred backlog)

---

## 3. Tech Stack + Structure Snapshot

- Laravel 12 + Inertia React + TypeScript + Tailwind v4 + shadcn + Radix + Lucide
- Backend: role controllers under `app/Http/Controllers/...`
- Frontend pages: `resources/js/pages/...`
- Shared UI: `resources/js/components/ui/...`
- Layout wrappers (global UI behavior):
  - `resources/js/layouts/app/app-sidebar-layout.tsx`
  - `resources/js/layouts/app/app-header-layout.tsx`
  - `resources/js/layouts/auth/auth-simple-layout.tsx`
  - `resources/js/layouts/auth/auth-split-layout.tsx`
  - `resources/js/layouts/auth/auth-card-layout.tsx`
- Theme tokens and global CSS: `resources/css/app.css`
- shadcn project config: `components.json`

---

## 4. Major Implemented Work (Functional)

## 4.1 Registrar

Implemented core flows (backend + frontend wiring):

- Student Directory + SF1 upload + auto LRN reconciliation
- Enrollment queue intake (minimal fields), edit/delete actions
- Remedial entry workflows

Primary touched areas include:

- `app/Http/Controllers/Registrar/StudentDirectoryController.php`
- `app/Http/Controllers/Registrar/EnrollmentController.php`
- `app/Http/Controllers/Registrar/RemedialEntryController.php`
- `resources/js/pages/registrar/student-directory/index.tsx`
- `resources/js/pages/registrar/enrollment/index.tsx`
- `resources/js/pages/registrar/remedial-entry/index.tsx`
- `routes/roles/registrar.php`

## 4.2 Finance

Implemented and wired core finance modules:

1. Cashier Panel
2. Student Ledgers
3. Transaction History
4. Fee Structure
5. Product Inventory (pricing only)
6. Discount Manager
7. Daily Reports
8. Finance Dashboard

Primary touched areas include:

- `resources/js/pages/finance/cashier-panel/index.tsx`
- `resources/js/pages/finance/student-ledgers/index.tsx`
- `resources/js/pages/finance/transaction-history/index.tsx`
- `resources/js/pages/finance/fee-structure/index.tsx`
- `resources/js/pages/finance/product-inventory/index.tsx`
- `resources/js/pages/finance/discount-manager/index.tsx`
- `resources/js/pages/finance/daily-reports/index.tsx`
- `resources/js/pages/finance/dashboard.tsx`
- finance controllers + requests + models under `app/Http/Controllers/Finance`, `app/Http/Requests/Finance`, `app/Models`

## 4.3 Teacher

Implemented significant teacher backend wiring:

1. Teacher Dashboard (data-driven)
2. Teacher Schedule (data-driven)
3. Teacher Grading Sheet (rubrics/assessments/scores/finalization)
4. Advisory Board progression with conduct-related data support (in progress/refined over sessions)

Primary touched areas include:

- `app/Http/Controllers/Teacher/DashboardController.php`
- `app/Http/Controllers/Teacher/ScheduleController.php`
- `app/Http/Controllers/Teacher/GradingSheetController.php`
- `app/Http/Controllers/Teacher/AdvisoryBoardController.php`
- `app/Http/Requests/Teacher/*`
- `app/Models/ConductRating.php`
- `database/migrations/2026_02_21_163605_create_conduct_ratings_table.php`
- `resources/js/pages/teacher/dashboard.tsx`
- `resources/js/pages/teacher/schedule/index.tsx`
- `resources/js/pages/teacher/grading-sheet/index.tsx`
- `resources/js/pages/teacher/advisory-board/index.tsx`
- `routes/roles/teacher.php`

## 4.4 Student + Parent

Implemented visualization/wiring for:

- Student schedule + grades + dashboard
- Parent schedule + grades + billing information + dashboard

Primary touched areas include:

- `resources/js/pages/student/*`
- `resources/js/pages/parent/*`
- Role routes in:
  - `routes/roles/student.php`
  - `routes/roles/parent.php`

## 4.5 Dashboard Analytics (Decision-support direction)

Implemented/iterated role dashboards with decision-support analytics structures and chart support.

Key shared file:

- `resources/js/components/dashboard/analytics-panel.tsx`
- `resources/js/types/dashboard.ts`

Role dashboard files touched include:

- `resources/js/pages/super_admin/dashboard.tsx`
- `resources/js/pages/admin/dashboard.tsx`
- `resources/js/pages/registrar/dashboard.tsx`
- `resources/js/pages/finance/dashboard.tsx`
- `resources/js/pages/teacher/dashboard.tsx`
- `resources/js/pages/student/dashboard.tsx`
- `resources/js/pages/parent/dashboard.tsx`

Backend role controllers touched include:

- `app/Http/Controllers/SuperAdmin/DashboardController.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Registrar/DashboardController.php`
- `app/Http/Controllers/Finance/DashboardController.php`
- `app/Http/Controllers/Teacher/DashboardController.php`
- `app/Http/Controllers/Student/*`
- `app/Http/Controllers/ParentPortal/*`

---

## 5. UI System Refinements Already Implemented

## 5.1 Global card spacing behavior (without editing shadcn base card)

Implemented via layout-level selectors (not by editing `resources/js/components/ui/card.tsx`):

- card top padding removed globally
- card internal gap reduced
- card header border-bottom spacing refined
- conditional header gap behavior (depends on presence of description/`<p>`/icon)

Applied in app/auth layout wrappers listed above.

## 5.2 Theme migration to shadcn Create settings (global token-driven)

User-provided Create settings applied:

- Component Library: Radix UI
- Style: Nova
- Base Color: Gray
- Theme: Blue
- Icon Library: Lucide
- Font: Figtree
- Radius: Default
- Menu Color: Default
- Menu Accent: Subtle

Actual project updates:

1. `components.json`
   - `style: "radix-nova"`
   - `tailwind.baseColor: "gray"`
2. `resources/css/app.css`
   - Added Figtree import
   - Set `--font-sans` to Figtree
   - Replaced root + dark color tokens with Nova/Blue token set from shadcn create/init response

Important: this project should now prefer token-driven theming and avoid one-off hardcoded color overrides when possible.

---

## 6. Routes and Role Access Notes

Role dashboard routing and role-specific pages are wired in:

- `routes/web.php`
- `routes/roles/*.php`

Wayfinder route generation is part of the workflow; some auth/settings form usage was adjusted previously to stay compatible with generated methods/URLs.

---

## 7. Testing and Quality Status

Frequent validation commands used throughout implementation:

1. `npm run -s types`
2. `npm run build`
3. `php artisan test --compact <target tests>`
4. `vendor/bin/pint --dirty --format agent`

Role and module tests touched include:

- `tests/Feature/DashboardTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/RoleAccessTest.php`
- `tests/Feature/Admin/AdminFeaturesTest.php`
- `tests/Feature/Finance/FinanceDashboardTest.php`
- `tests/Feature/Registrar/RegistrarFeaturesTest.php`
- `tests/Feature/Teacher/TeacherFeaturesTest.php`
- `tests/Feature/Parent/*`
- `tests/Feature/Student/*`

Current smoke status during latest theming/config updates:

- Types: pass
- Build: pass
- Targeted feature tests: pass
- Pint: pass

---

## 8. Explicit Permission for Future AI on Another Device

This is an explicit operational authorization:

**The next AI is allowed to run required setup/install/build/test commands needed to make the project work on that device.**

Allowed and expected commands (as needed):

1. `composer install`
2. `npm install`
3. `composer run setup`
4. Update `.env` database credentials
5. `composer run setup:finish`
6. `php artisan migrate --seed --force` (if needed)
7. `php artisan key:generate`
8. `npm run dev` or `npm run build`
9. `php artisan wayfinder:generate --no-interaction` (if route helpers are stale)
10. `vendor/bin/pint --dirty --format agent`
11. `php artisan test --compact ...`

Also allowed:

- installing OS/runtime dependencies required for PHP extensions, Composer, Node/NPM, and database client connectivity
- clearing caches if needed (`php artisan optimize:clear`, etc.)

---

## 9. Setup Instructions for Another Device (Recommended Sequence)

## 9.1 Prerequisites

1. PHP 8.2+
2. Composer
3. Node + NPM
4. PostgreSQL (or configured DB engine matching `.env`)
5. Git

## 9.2 First-time bootstrap

1. Clone repo and checkout latest `main`.
2. Run:
   - `composer install`
   - `npm install`
3. Initialize env:
   - copy `.env.example` to `.env` (if missing)
   - configure DB credentials
4. Run setup scripts:
   - `composer run setup`
   - `composer run setup:finish`
5. Start development:
   - `composer run dev`

## 9.3 If frontend changes do not appear

1. Run `npm run dev` (or restart it).
2. If still stale, run `npm run build`.

## 9.4 If route helpers mismatch

Run:

- `php artisan wayfinder:generate --no-interaction`

Then rebuild or rerun type-check.

---

## 10. Required First Actions for Next AI Session

Before coding, next AI must:

1. Scan codebase structure first (routes/controllers/pages/components).
2. Identify current conventions from nearby files.
3. Reuse existing shadcn/UI patterns before creating new structures.
4. Summarize findings briefly before implementing.

This instruction is mandatory for continuity.

---

## 11. Recommended Next Work (Priority Order)

1. Complete/verify teacher advisory lifecycle end-to-end
   - conduct/values encode, lock/finalization behavior, tests
2. Hardening pass for dashboards
   - verify role payload isolation and alert threshold behavior in edge cases
3. Resume deferred finance backlog when requested
   - print/export + void/refund workflows
4. Resume deferred registrar/admin modules only when explicitly re-opened
5. Perform a cross-role UX polish pass under Nova theme tokens
   - remove remaining page-level hardcoded color overrides where token classes are sufficient

---

## 12. Important Continuity Notes

1. Theming should now be token-first (Nova/Blue/Gray/Figtree).
2. Avoid reintroducing hardcoded color overrides unless specifically requested.
3. Keep production layout consistency and low-scroll operator workflows.
4. Keep this handoff updated at every major module completion.

