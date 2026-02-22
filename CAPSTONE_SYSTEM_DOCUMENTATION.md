# Marriott School Information System

Last updated: 2026-02-22  
Project root: `C:\Users\jadeg\Documents\Capstone\marriott`

## 1. Document Purpose

This document describes the whole system for:

1. Capstone paper writing.
2. Technical onboarding of developers.
3. AI ingestion for analysis, drafting, and question answering.

The system is a role-based school information system for a DepEd-recognized school in the Philippines, built to reduce duplicate encoding and support operational workflows from enrollment to grading and billing.

## 2. Problem Context and Design Intent

### 2.1 Domain Context

The school uses DepEd LIS as the official source of truth for learner identity and enrollment reporting. Internal operations still require daily registrar, finance, and academic workflows.

### 2.2 Core Design Constraint

Avoid double encoding.  
The system follows a staged model:

1. Registrar records minimal enrollment intake.
2. Cashier handles payment.
3. SF1 upload later enriches student records by LRN.
4. LIS reconciliation is automated as much as possible.

### 2.3 Product Direction

The UI is designed for staff productivity:

1. Shadcn-based components and consistent layouts.
2. Low-friction one-page operational screens.
3. Dashboard views focused on decision support and trend visualization.

## 3. User Roles and Responsibilities

The system supports seven roles (`App\Enums\UserRole`):

1. `super_admin`: global governance, accounts, audit, settings.
2. `admin`: academic controls, curriculum, sections, schedules, class lists.
3. `registrar`: student directory, enrollment queue, remedial entries.
4. `finance`: cashier transactions, ledgers, fees, discounts, reports.
5. `teacher`: schedule, grading sheet, advisory board.
6. `student`: personal schedule, grades, learning dashboard.
7. `parent`: child schedule, grades, billing dashboard.

## 4. Functional Scope by Role

### 4.1 Super Admin

Main modules:

1. User Manager: create/edit users, role assignment, reset password, status toggle.
2. Audit Logs: searchable action history and detail visibility.
3. Announcements: create/edit/delete, role-targeted publishing.
4. Permissions: permission matrix view.
5. System Settings: school identity, branding, maintenance mode, parent portal toggle, backup/restore controls.

### 4.2 Admin

Main modules:

1. Academic Controls: active school year lifecycle and simulation controls.
2. Curriculum Manager: subject catalog and teacher certification mapping.
3. Section Manager: create/update/delete sections and adviser mapping.
4. Schedule Builder: calendar-style class schedule planning.
5. Class Lists: section-wise class roster view.
6. Deferred: DepEd Reports and SF9 Generator are present as pages but intentionally deferred.

### 4.3 Registrar

Main modules:

1. Student Directory: searchable learner records with SF1 upload endpoint.
2. Enrollment Queue: intake creation, updates, deletions.
3. Remedial Entry: remedial grade encoding workflow.
4. Deferred: permanent records, batch promotion, and student departure are currently deferred in active implementation scope.

### 4.4 Finance

Main modules:

1. Cashier Panel: one-page transaction workflow with confirmation.
2. Student Ledgers: learner-level debit/credit tracking.
3. Transaction History: posted payment records.
4. Fee Structure: fee definitions.
5. Product Inventory: pricing-oriented item catalog.
6. Discount Manager: discount definitions and student-tagging.
7. Daily Reports: daily finance reporting page.

### 4.5 Teacher

Main modules:

1. Dashboard: teaching operations metrics and trends.
2. Schedule: assigned class and advisory schedule.
3. Grading Sheet: rubric weights, assessments, score entry, quarter submission.
4. Advisory Board: advisory class conduct-related workflows.

### 4.6 Student

Main modules:

1. Dashboard: learning-focused analytics (non-billing).
2. Grades: quarter/final grade views and conduct context.
3. Schedule: personal class/advisory schedule.

### 4.7 Parent

Main modules:

1. Dashboard: child context, dues risk, payment trend.
2. Grades: child academic performance view.
3. Schedule: child schedule view.
4. Billing Information: dues schedule and recent payments.

## 5. End-to-End Core Workflows

### 5.1 Enrollment to LIS-Aligned Record

1. Registrar creates minimal enrollment intake.
2. Finance cashier processes payment.
3. Student entry leaves queue after successful payment.
4. Registrar uploads SF1.
5. System aligns/enriches records via LRN matching.

### 5.2 Teaching and Grading

1. Teacher selects section, subject, quarter in grading sheet.
2. Teacher sets rubric distribution.
3. Teacher creates graded activities.
4. Teacher enters student scores.
5. Teacher saves draft or submits grades.
6. Quarter completion status appears in teacher dashboard metrics.

### 5.3 Billing and Financial Posting

1. Cashier selects student and payment items.
2. Transaction is confirmed and posted.
3. Ledger and transaction history reflect updates.
4. Parent and finance dashboards consume payment/balance trends.

## 6. Technical Architecture

### 6.1 Stack

Backend:

1. Laravel 12
2. PHP 8.x
3. PostgreSQL
4. Laravel Fortify (auth)
5. Laravel Wayfinder

Frontend:

1. Inertia.js v2 with React 19
2. TypeScript
3. Tailwind CSS v4
4. Shadcn UI + Radix UI
5. Recharts (through shared chart wrappers)

Quality and Tooling:

1. Pest v4 + PHPUnit
2. Laravel Pint
3. Prettier + ESLint

### 6.2 Layering

1. HTTP controllers under `app/Http/Controllers/*`.
2. Route files split by role under `routes/roles/*.php`.
3. Shared dashboard renderer in `resources/js/components/dashboard/analytics-panel.tsx`.
4. Page modules under `resources/js/pages/{role}/...`.
5. Domain models under `app/Models`.

### 6.3 Role Routing Strategy

`routes/web.php` resolves `/dashboard` to role-specific dashboard controller responses.  
Each role also has prefix-based route groups with `auth`, `verified`, and role middleware.

## 7. Security and Access Control

### 7.1 Authentication and Role Guarding

1. Fortify-based authentication flow.
2. `CheckRole` middleware blocks unauthorized role access.
3. Unauthenticated users are redirected to login.

### 7.2 Operational Guards

1. `EnsureMaintenanceMode`: blocks non-admin/super-admin users when maintenance is enabled.
2. `EnsureParentPortalEnabled`: blocks parent routes when parent portal is disabled.
3. System-level toggles are managed via `settings` records in System Settings.

### 7.3 Traceability

Audit logging is part of governance scope and exposed through super admin features.

## 8. Dashboard and Analytics Design

Dashboards across roles use a common contract (`resources/js/types/dashboard.ts`):

1. KPI cards
2. Alert list
3. Trend cards
4. Action links

Trend rendering supports:

1. `line`
2. `bar`
3. `area`
4. `pie`

Current role dashboards are wired to chart-based trend payloads via `display` + `chart` data.

## 9. Core Data Model (High-Level)

Primary entities include:

1. `User`, `Student`, `AcademicYear`, `GradeLevel`, `Section`
2. `Enrollment`, `ClassSchedule`, `Subject`, `TeacherSubject`, `SubjectAssignment`
3. `GradingRubric`, `GradedActivity`, `StudentScore`, `FinalGrade`, `ConductRating`
4. `LedgerEntry`, `Transaction`, `TransactionItem`, `BillingSchedule`
5. `Discount`, `StudentDiscount`, `InventoryItem`, `Fee`
6. `Announcement`, `AuditLog`, `Setting`

Functional relationship summary:

1. Academic structure: academic year -> grade level -> section -> schedules/assignments.
2. Teaching structure: teacher -> teacher subjects -> section assignments -> graded activities.
3. Student progress: enrollment -> scores/final grades/conduct.
4. Finance structure: billing schedules + ledger entries + cashier transactions.
5. Governance: users, settings, announcements, audit logs.

## 10. Route Surface (Condensed)

Role route files:

1. `routes/roles/admin.php`
2. `routes/roles/registrar.php`
3. `routes/roles/finance.php`
4. `routes/roles/teacher.php`
5. `routes/roles/student.php`
6. `routes/roles/parent.php`
7. `routes/roles/super_admin.php`

The dashboard entrypoint route is `GET /dashboard` in `routes/web.php`, dynamically resolved by user role.

## 11. Testing and Quality Status

Test strategy includes role-specific feature tests under `tests/Feature/*`, including:

1. dashboard routing and payload shape checks,
2. role flow behavior checks (teacher, student, parent, finance, admin),
3. governance tests for super admin modules.

Typical quality commands:

1. `npm run types`
2. `vendor/bin/pint --dirty --format agent`
3. `php artisan test --compact <targeted-test-file>`

## 12. Deployment and Local Run

### 12.1 Initial setup

1. `composer run setup`
2. Configure database credentials in `.env`
3. `composer run setup:finish`

### 12.2 Development run

1. `composer run dev`

This starts Laravel server, queue worker, and Vite.

## 13. Current Scope vs Deferred Scope

### 13.1 Implemented and actively maintained

1. Core role dashboards.
2. Super admin governance modules.
3. Admin academic planning modules.
4. Registrar student directory/enrollment/remedial flows.
5. Finance cashier/ledger/reporting flows.
6. Teacher grading and advisory workflows.
7. Student and parent experience modules.

### 13.2 Deferred / intentionally not prioritized right now

1. Registrar: batch promotion.
2. Registrar: permanent records.
3. Registrar: student departure.
4. Admin: DepEd reports.
5. Admin: SF9 generator.
6. Advanced print/export refinements in finance.

## 14. AI Ingestion Summary (Structured Block)

```yaml
system_name: 'Marriott School Information System'
domain: 'K-12 school operations (Philippines, DepEd context)'
architecture:
    backend: 'Laravel 12'
    frontend: 'Inertia React + TypeScript'
    ui: 'Shadcn UI + Tailwind CSS v4'
    charts: 'Recharts via shared analytics-panel'
    database: 'PostgreSQL'
roles:
    - super_admin
    - admin
    - registrar
    - finance
    - teacher
    - student
    - parent
core_workflows:
    - enrollment_intake_to_cashier_to_sf1_enrichment
    - grading_rubric_assessment_score_submission
    - billing_and_transaction_posting
non_negotiables:
    - 'Avoid duplicate encoding against DepEd LIS'
    - 'Use shadcn-first component approach'
    - 'Keep layouts consistent and staff-efficient'
deferred_modules:
    - registrar_batch_promotion
    - registrar_permanent_records
    - registrar_student_departure
    - admin_deped_reports
    - admin_sf9_generator
entry_routes:
    dashboard: '/dashboard'
    role_route_files:
        - 'routes/roles/admin.php'
        - 'routes/roles/registrar.php'
        - 'routes/roles/finance.php'
        - 'routes/roles/teacher.php'
        - 'routes/roles/student.php'
        - 'routes/roles/parent.php'
        - 'routes/roles/super_admin.php'
```

## 15. Suggested Capstone Sections (Optional Outline)

If this is used directly for a capstone manuscript, this structure is recommended:

1. Introduction and Problem Statement.
2. Related Systems and Gap Analysis.
3. System Architecture and Design.
4. Module-Level Functional Specifications.
5. Data Model and Workflow Diagrams.
6. Implementation Stack and Tooling.
7. Testing and Validation.
8. Deployment and Operational Considerations.
9. Limitations and Future Enhancements.
10. Conclusion.
