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

## 16. Interview Evidence Digest (Appendices-Derived)

This section converts appendices interviews into writing-ready evidence units that can be reused in Introduction, Project Context, Statement of the Problem, Objectives, Scope, and RRL relevance paragraphs.

### 16.1 Source Basis

Primary interview extraction source:

1. `capstone-paper/tmp_docx_paragraphs.txt`

Interview groups present in appendices:

1. Academic Head (`B.1`, Mr. Alexander F. Avellanosa)
2. Registrar (`B.2`, Mrs. Jocelyn M. Cleofe)
3. Finance (`B.3`, Mrs. Corrine P. Avellanosa)
4. Teacher (`B.4`, Mrs. Fe Mercedes M. Cavitt)
5. Security Guard (`B.5`, Mr. Davey)

### 16.2 Stakeholder Evidence Matrix

| Stakeholder | Evidence highlights from appendices | Direct writing implications | Mapped module scope |
| --- | --- | --- | --- |
| Academic Head | Shared Excel in cloud still behaves like manual ops; cross-department edits are hard to trace; conflicts across enrollment, payment, and attendance; high verification burden before release (`0527`, `0535`, `0559`, `0663`, `0667`). | Position the problem as process architecture fragmentation, not only lack of tools. Emphasize governance visibility and decision-support delay. | Admin dashboard, governance controls, cross-office data continuity, analytics layer |
| Registrar | Records are updated one-by-one and double-checked; inconsistencies across files; delays can span days or weeks; schedule checks are manual and conflict-prone (`0682`, `0685`, `0694`, `0700`, `0710`, `0713`). | Justify centralized intake, reusable records, and registrar-to-finance-to-academic continuity. Highlight manual schedule friction. | Student Directory, Enrollment Queue, SF1 upload, schedule support context |
| Finance | Excel/Drive is central but still manual; delayed posting under concurrent payments; no POS; parent balance visibility is periodic, not immediate (`0760`, `0769`, `0775`, `0784`, `0787`, `0801`, `0818`). | Frame finance issue as delayed reconciliation and non-real-time ledger transparency. Justify cashier panel, ledger automation, and reporting dashboards. | Cashier Panel, Student Ledgers, Transaction History, Daily Reports, Billing visibility |
| Teacher | Attendance and grading involve paper + Excel relay; encoding, checking, and computation cycles delay finalization; locating historical records is time-consuming (`0829`, `0834`, `0843`, `0846`, `0852`, `0862`). | Support claims about instructional workflow friction and need for structured grading and attendance-related data flow. | Teacher Schedule, Grading Sheet, Advisory context, dashboard monitoring |
| Security Guard | Gate monitoring and dismissal validation are mostly observational/manual; parent updates depend on ad hoc checks and logbook lookup (`0872`, `0876`, `0888`, `0892`, `0900`). | Supports stakeholder transparency argument and role-scoped visibility need, especially parent communication timeliness. | Parent portal visibility, status communication, operational coordination context |

### 16.3 Reusable Evidence Statements (for chapter drafting)

Use these as chapter-safe evidence anchors:

1. The school already uses digital tools, but transaction traceability remains manual because each department keeps separate records.
2. Delays are repeatedly caused by verification loops, not by absence of effort from staff.
3. Registrar and finance delays are linked; unresolved balance updates affect downstream release workflows.
4. Teacher-side data preparation is multi-step and often duplicated across paper and spreadsheet channels.
5. Parents and guardians experience update lag because status visibility is not continuously synchronized.

### 16.4 Evidence Priority Ranking

Priority for direct quotation in final manuscript:

1. High: Academic Head, Registrar, Finance
2. Medium: Teacher
3. Contextual: Security Guard

Reason:

1. High-priority interviews directly map to the core capstone modules and measurable process bottlenecks.
2. Medium/contextual interviews strengthen stakeholder impact discussion and transparency rationale.

## 17. Claim-to-Source Mapping Table

Use this matrix to prevent unsupported claims and to keep chapter arguments traceable.

| Claim ID | Core claim to write/defend | Primary interview evidence | Supporting RRL | System implementation anchor |
| --- | --- | --- | --- | --- |
| C01 | The school has fragmented, office-specific records that reduce operational consistency. | Academic Head (`0527`, `0535`, `0663`) | Grepon et al. (2021), Zulueta et al. (2021) | `routes/roles/registrar.php`, `routes/roles/finance.php`, shared models |
| C02 | Duplicate encoding and repeated validation are major causes of delay. | Registrar (`0682`, `0691`, `0694`) | Kanona (2022), Grepon et al. (2021) | Enrollment + Student Directory flows |
| C03 | Finance reconciliation is delayed during concurrent payment events. | Finance (`0769`, `0775`, `0801`) | Wulandari and Pinandito (2021), Chai and Mostafa (2021) | `Finance/CashierPanelController.php`, ledgers and transactions models |
| C04 | Non-real-time status visibility contributes to parent confusion and follow-up load. | Finance (`0787`, `0798`), Guard (`0892`) | Navarra and Antonio (2025), Chai and Mostafa (2021) | Parent billing page, parent dashboard, billing schedules |
| C05 | Manual schedule coordination creates conflict and revision overhead. | Registrar (`0710`, `0713`, `0716`) | Muller et al. (2025) | Admin schedule builder and class list modules |
| C06 | Teacher grading is delayed by multi-step manual encoding and review. | Teacher (`0843`, `0846`, `0852`) | Zulueta et al. (2021), Kanona (2022) | `Teacher/GradingSheetController.php`, score/rubric entities |
| C07 | Leadership cannot rapidly produce planning-grade insights from dispersed records. | Academic Head (`0667`, `0671`) | Esquivel and Esquivel (2021), Hussain et al. (2023) | Role dashboards + trend payload contract |
| C08 | LIS alignment is a hard requirement for enrollment identity discipline. | Registrar + Academic operations context (`0682`, `0700`) | Porlas et al. (2023) | SF1 upload and LRN enrichment workflow |
| C09 | Governance and access controls are required, not optional, in a shared system. | Academic Head (`0535`, `0563`) | Kanona (2022), Zulueta et al. (2021) | `CheckRole`, settings toggles, audit logs |
| C10 | The system must be role-scoped while keeping one authoritative dataset. | Academic Head + Registrar (`0663`, `0703`) | Grepon et al. (2021), Chai and Mostafa (2021) | Role routes + shared relational schema |
| C11 | Delinquency and payment-trend analytics improve finance decision speed. | Finance (`0818`, `0821`) | Wulandari and Pinandito (2021) | Finance dashboard + transaction history |
| C12 | Enrollment trend analytics support sectioning, staffing, and resource planning. | Academic Head (`0667`) | Hussain et al. (2023), Esquivel and Esquivel (2021) | Admin/super admin dashboard trend cards |

### 17.1 Chapter-level traceability usage

Recommended mapping:

1. Introduction and Project Context: C01, C02, C07, C08, C10
2. Statement of the Problem: C01 to C07
3. Objectives: C02, C03, C05, C07, C08, C11, C12
4. Scope and Limitations: C08, C09, C10
5. RRL Relevance paragraphs: C03, C05, C07, C11, C12
6. Requirements Analysis: C02, C03, C05, C09

## 18. Chapter Writing Constraints (Capstone Drafting Contract)

Use these constraints for consistency and defendability.

### 18.1 Universal constraints

1. Keep claims operational and evidence-backed.
2. Do not write abstract advantages unless mapped to observed school workflow pain.
3. Every major subsection must include at least one evidence anchor from interviews or RRL.
4. Avoid absolute claims (for example, "fully automated") when scope is staged or deferred.
5. Maintain present-edition boundaries and deferred-module clarity.

### 18.2 Per-chapter constraints

| Chapter section | Target depth | Required evidence minimum | Style constraints |
| --- | --- | --- | --- |
| Introduction | 4 to 7 dense paragraphs | At least 2 interview anchors + 2 RRL anchors | Problem framing first, solution framing second |
| Project Context | 8 to 14 paragraphs | At least 4 interview anchors across 3 offices | Explicit cross-office workflow narrative |
| Statement of the Problem | 1 general + 5 specific problem blocks | Each specific problem linked to at least 1 interview + 1 RRL | Problem question followed by operational rationale |
| Objectives | 1 general + 5 specific objective blocks | Each specific objective linked to matching problem | Use implementation-oriented verbs |
| Scope | Role-by-role module list + scope narrative | Must match actual route/module surface | Header lines are plain text, modules are bullets |
| Limitations | 1 framing paragraph + 4 to 7 limitation bullets + 1 closure paragraph | At least 1 boundary for integration, analytics, reporting, external validity | Clear "in scope vs out of scope" language |
| RRL | 10 studies (5 local + 5 international) | 2021+ only; each study has summary + relevance paragraph | Focus on strong direct relevance, avoid padding |
| Methodology | Model explanation + staged steps | Link model steps to actual validation flow | Keep implementation-feasible tone |
| Requirements Analysis | Business, system, functional, user requirements | Each requirement cluster maps to interview pain | Prefer explicit, testable statements |

### 18.3 Citation and source discipline

1. Keep RRL publication year at 2021 onward for primary set.
2. If adding a new source, log it first in the RRL quality table (Section 19).
3. Ensure every cited source has direct module relevance or governance relevance.

## 19. RRL Quality Log and Selection Rules

### 19.1 Selection criteria used

A source is considered strongly relevant only if it satisfies all primary checks:

1. Published 2021 onward.
2. Directly supports at least one MarriottConnect module or cross-office workflow.
3. Contains practical implementation or measurable operational insight, not only conceptual commentary.
4. Can be connected to interview-documented school pain points.

### 19.2 Current selected roster (approved set)

| Source | Type | Year | Why kept | Primary module mapping |
| --- | --- | --- | --- | --- |
| Grepon et al. | Local | 2021 | Validates centralized school workflow architecture in PH context | Enrollment, records continuity |
| Zulueta et al. | Local | 2021 | Describes MIS barriers in PH institutions, supports rollout realism | Adoption, governance, process standardization |
| Esquivel and Esquivel | Local | 2021 | Supports enrollment forecasting direction | Analytics, planning dashboards |
| Porlas et al. | Local | 2023 | Direct LIS vs school MIS synchronization evidence | LIS alignment, SF1/LRN continuity |
| Navarra and Antonio | Local | 2025 | Supports web-based monitoring and stakeholder visibility | Parent/student portal relevance |
| Wulandari and Pinandito | International | 2021 | Strong delinquency decision-support logic | Finance DSS, delinquency analytics |
| Chai and Mostafa | International | 2021 | Integrated web MIS for shared records | Cross-role access, data centralization |
| Kanona | International | 2022 | Manual-to-database transition evidence | Data integrity, security, retrieval speed |
| Hussain et al. | International | 2023 | Forecasting models for enrollment planning | Predictive planning analytics |
| Muller et al. | International | 2025 | Constraint-based scheduling evidence | Timetabling and conflict-aware scheduling |

### 19.3 Source set constraints to preserve

1. Keep 5 local and 5 international balance.
2. Keep year floor at 2021 unless user explicitly allows exceptions.
3. If a source is removed, replace with equal-or-stronger direct relevance.
4. Avoid sources that are only tangentially educational but not workflow-comparable.

### 19.4 Exclusion reasons log (policy-level)

Sources should be excluded when one or more of these applies:

1. Older than the active year window for this chapter.
2. High-level commentary without module-level operational linkage.
3. Weak traceability to interviews and actual Marriott workflows.
4. Redundant contribution already covered by a stronger source.

## 20. Formatting Contract for DOCX Editing

This section prevents repeat layout regressions during text replacement.

### 20.1 Heading hierarchy contract

1. Chapter titles use `Heading 1`.
2. Major subsection titles use `Heading 2`.
3. Nested requirement labels use `Heading 3` only when structurally needed.
4. Do not style narrative body paragraphs as heading styles.

### 20.2 Scope and list contract

1. Role label lines (for example, "The Finance Officer can access the following:") are plain body text.
2. Module entries under each role are bulleted list items.
3. Sub-items should keep one consistent indent level across the whole section.
4. No accidental bullets on headers or bridge sentences.

### 20.3 Paragraph spacing contract

1. Keep one consistent body spacing rule throughout core chapters.
2. Avoid excessive blank paragraph insertion between related points.
3. Merge fragmented sentence blocks when they are one logical paragraph.
4. Keep page-break behavior natural; do not insert manual gaps to force layout.

### 20.4 Known style-risk zones

Based on current revision history, these zones must be checked every cycle:

1. Introduction -> Project Context transition pages.
2. Scope pages with long role/module lists.
3. RRL pages where title-summary-relevance triads repeat.
4. Requirements Documentation section where heading misuse can occur.

### 20.5 Pre-release QA checklist

Before finalizing a revised DOCX:

1. Verify section presence: Introduction, Project Context, Scope, Limitations, RRL.
2. Verify bullet semantics in Scope.
3. Verify heading styles for major sections.
4. Verify paragraph spacing on rendered page images.
5. Verify references count and 5 local + 5 international distribution.
6. Verify all RRL entries are 2021+ in the active set.

### 20.6 Recommended verification workflow

1. Update text with style-preserving DOCX operations.
2. Export to PDF via `soffice`.
3. Render critical pages with `pdftoppm`.
4. Compare against prior accepted visual outputs in `capstone-paper/tmp/docs/*`.
5. Keep a checkpoint DOCX before each major section rewrite.
