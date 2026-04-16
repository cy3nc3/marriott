# MarriottConnect DOCX Mismatch Fixes

Source paper: `capstone-paper/MarriottConnect - Second Edition.docx`

Purpose: Use this as an edit checklist. Each item shows where the mismatch appears, the current text or claim to find, and replacement text that better matches the current system.

## 1. Scope: Student Directory Overstates Profile Maintenance

### Location

Section: `Scope`  
Subsection/list: `The Registrar can access the following:`

### Current

```text
Student Directory Module - Maintain searchable learner profiles, identity continuity, and authorized record retrieval.
```

### Fix

```text
Student Directory Module - View searchable learner records, review LIS alignment status, and support authorized record retrieval. Full intake and profile updates are handled through the enrollment and permanent-record workflows.
```

### Reason

The current Student Directory page supports listing, searching, LIS status review, and SF1 upload matching. It does not directly provide full learner profile maintenance.

## 2. Scope: Registrar Analytics Described as a Separate Module

### Location

Section: `Scope`  
Subsection/list: `The Registrar can access the following:`

### Current

```text
Registrar Analytics - Review intake movement, enrichment status, record-completion indicators, and registrar risk alerts.
```

### Fix

```text
Registrar Dashboard Indicators - Review intake queue pressure, cashier handoff status, LIS synchronization, sync-error backlog, and registrar alerts through the role dashboard.
```

### Reason

The current system has registrar dashboard KPI, alert, and trend indicators, not a separate Registrar Analytics module.

## 3. Scope: Billing Schedule and Due Allocation Listed as a Standalone Module

### Location

Section: `Scope`  
Subsection/list: `The Finance Officer can access the following:`

### Current

```text
Billing Schedule and Due Allocation Module - Manage due schedules and map posted payments to outstanding obligations.
```

### Fix

```text
Billing Schedule and Due Allocation Logic - Generate and update due schedules through enrollment, fee, discount, cashiering, import, ledger, and transaction workflows, with posted payments allocated to outstanding obligations by the system.
```

### Reason

Billing schedules and due allocations exist in the data model and services, but there is no dedicated finance page where users directly manage due schedules as a standalone module.

## 4. Scope: Financial Analytics Described as a Separate Module

### Location

Section: `Scope`  
Subsection/list: `The Finance Officer can access the following:`

### Current

```text
Financial Analytics - Monitor collection trends, billing risk indicators, reminder outputs, and daily finance performance.
```

### Fix

```text
Finance Dashboard Indicators - Monitor collection efficiency, outstanding receivables, overdue concentration, next-month due forecast, daily collection trends, payment-mode mix, due reminder status, and daily finance performance through finance dashboard and report pages.
```

### Reason

Finance analytics are implemented through the Finance Dashboard, Daily Reports, Student Ledgers, Transaction History, and Due Reminder Settings, not as a separate Financial Analytics module.

## 5. Scope: Gradebook Validation Listed as a Teacher Module

### Location

Section: `Scope`  
Subsection/list: `The Teachers can access the following:`

### Current

```text
Gradebook Validation Module - Review grading completeness, submission status, and correction checkpoints.
```

### Fix

```text
Grade Completion Indicators - Review grading completeness and pending grade rows through the teacher dashboard, while formal verification, return, and correction checkpoints are handled through the Admin Grade Verification workflow.
```

### Reason

Teachers have grading workflows and dashboard completion indicators. Formal grade validation is an admin-side Grade Verification module, not a separate teacher Gradebook Validation module.

## 6. Scope: Instructional Analytics Described as a Separate Module

### Location

Section: `Scope`  
Subsection/list: `The Teachers can access the following:`

### Current

```text
Instructional Analytics  - Analyze class performance trends, completion alerts, and quarter progress indicators.
```

### Fix

```text
Teacher Dashboard Indicators - Review class schedules, quarter grade completion, pending grade rows, at-risk learners, completion alerts, and quarter progress indicators through the teacher dashboard.
```

### Reason

Teacher analytics are dashboard indicators, not a separate Instructional Analytics module.

## 7. Scope: Parent Analytics Described as a Separate Module

### Location

Section: `Scope`  
Subsection/list: `The Parents can access the following:`

### Current

```text
Parent Analytics - Review child-focused academic and billing indicators for timely family decisions.
```

### Fix

```text
Parent Dashboard Indicators - Review child-focused academic, schedule, billing, payment, and due-risk indicators through the parent dashboard.
```

### Reason

The parent-facing indicators are part of the Parent Dashboard, not a separate Parent Analytics module.

## 8. Scope: Student Learning Analytics Described as a Separate Module

### Location

Section: `Scope`  
Subsection/list: `The Students can access the following:`

### Current

```text
Student Learning Analytics - Review personal learning indicators, progress trends, and academic alerts.
```

### Fix

```text
Student Dashboard Indicators - Review personal class context, schedule status, recent score information, quarter averages, progress trends, and academic alerts through the student dashboard.
```

### Reason

Student learning indicators are dashboard content, not a standalone Student Learning Analytics module.

## 9. Scope: Institutional Analytics Described as a Separate Admin Module

### Location

Section: `Scope`  
Subsection/list: `The School Administrators (Academic Head/Principal) can access the following:`

### Current

```text
Institutional Analytics - Monitor enrollment, financial risk, academic status, compliance readiness, and KPI trends.
```

### Fix

```text
Admin Dashboard Indicators - Monitor enrollment trends, schedule readiness, conflict exposure, grade verification progress, academic alerts, and KPI trends through the admin dashboard.
```

### Reason

Admin analytics are dashboard indicators. Also, “compliance readiness” can imply completed DepEd/SF9 reporting, but those admin pages are currently placeholders.

## 10. Scope: Governance Analytics Described as a Separate Super Admin Module

### Location

Section: `Scope`  
Subsection/list: `The Super Admin (IT Personnel) can access the following:`

### Current

```text
System Configuration and Governance Analytics - Control maintenance and portal settings, institutional preferences, and governance performance indicators.
```

### Fix

```text
System Configuration and Governance Dashboard Indicators - Control maintenance mode, parent portal settings, school identity, branding, backup and restore controls, and review governance indicators such as system health, account status, audit activity, and backup freshness through the Super Admin dashboard and System Settings page.
```

### Reason

Configuration controls and governance indicators are split between System Settings and the Super Admin Dashboard. They are not a separate governance analytics module.

## 11. Requirements Analysis: Student Directory Maintenance Overclaim

### Location

Section: `Requirements Analysis`  
Subsection: `C. System Requirements`  
Subheading: `Major System Capabilities`

### Current

```text
The system supports registrar intake, student directory maintenance, and SF1 upload processing.
```

### Fix

```text
The system supports registrar intake, searchable student directory viewing, LIS alignment review, and SF1 upload processing with LRN-based matching.
```

### Reason

This avoids implying that the Student Directory page is the primary profile maintenance interface.

## 12. Requirements Analysis: Learner Profile Maintenance Needs Clarification

### Location

Section: `Requirements Analysis`  
Subsection: `E. Functional Requirements`  
Subheading: `Manage Student Records`

### Current

```text
The Registrar shall create and maintain learner profiles and enrollment queue records.
```

### Fix

```text
The Registrar shall create enrollment intake records, update enrollment-related learner details, review searchable student directory records, and maintain long-term academic records through the permanent-record workflow.
```

### Reason

This better matches the current split between Enrollment, Student Directory, and Permanent Records.

## 13. Requirements Analysis: Decision Support Analytics Should Not Imply Advanced Analytics Modules

### Location

Section: `Requirements Analysis`  
Subsection: `E. Functional Requirements`  
Subheading: `6. Provide Decision Support Analytics`

### Current

```text
6.1 The system shall process operational metrics for enrollment movement, payment behavior, and risk monitoring.
6.2 The system shall visualize trends using standardized chart-ready payloads for management decisions.
```

### Fix

```text
6.1 The system shall process dashboard-level operational metrics for enrollment movement, payment behavior, academic status, and risk monitoring.
6.2 The system shall visualize KPI cards, alerts, and trends using standardized chart-ready dashboard payloads for role-scoped decision support.
```

### Reason

The system has role dashboards with KPI, alert, and trend payloads. This wording avoids implying separate analytics modules or advanced predictive analytics.

## 14. Requirements Analysis: Operational Reports Claim Too Broad

### Location

Section: `Requirements Analysis`  
Subsection: `E. Functional Requirements`  
Subheading: `7. Generate Operational Reports`

### Current

```text
7.1 The system shall provide structured outputs for enrollment, finance, and academic summaries.
7.2 Reports shall remain consistent with authorized data sources and institutional formatting policies.
```

### Fix

```text
7.1 The system shall provide implemented structured outputs such as enrollment exports, finance daily reports, student ledgers, transaction history, SF2 attendance export, and dashboard summaries.
7.2 Implemented outputs shall remain consistent with authorized data sources and available institutional formatting templates.
```

### Reason

The app has several working reports/exports, but Admin DepEd Reports and SF9 Generator pages are placeholders. This avoids claiming all academic/DepEd/report-card reports are complete.

## 15. Requirements Documentation: Registry and Enrollment Overstates Learner Profile Maintenance

### Location

Section: `Requirements Documentation`

### Current

```text
The Registry and Enrollment module captures intake entries, maintains learner profiles, and supports SF1 upload with LRN-based reconciliation for LIS-aligned continuity.
```

### Fix

```text
The Registry and Enrollment workflow captures intake entries, updates enrollment-related learner details, displays searchable student records, maintains permanent-record continuity, and supports SF1 upload with LRN-based reconciliation for LIS-aligned continuity.
```

### Reason

This reflects the actual split across Enrollment, Student Directory, Permanent Records, and SF1 upload.

## 16. Requirements Documentation: Teacher Operations Overstates Gradebook Validation

### Location

Section: `Requirements Documentation`

### Current

```text
The Teacher Operations module provides rubric configuration, graded activity encoding, attendance tracking, score submission, and gradebook validation workflows.
```

### Fix

```text
The Teacher Operations module provides schedule viewing, rubric configuration, graded activity encoding, score entry, attendance tracking, advisory conduct encoding, and quarter grade submission workflows. Grade verification and return actions are handled by the Admin Grade Verification module.
```

### Reason

Teacher pages do not include a separate gradebook validation module. Admin handles verification and return actions.

## 17. Requirements Documentation: Analytics Layer Wording Should Stay Dashboard-Specific

### Location

Section: `Requirements Documentation`

### Current

```text
The analytics layer standardizes KPI cards, alerts, trend cards, and action links so each role receives decision-ready summaries from validated transactions.
```

### Fix

```text
The dashboard layer standardizes KPI cards, alerts, trend cards, and action links so each role receives role-scoped operational summaries from validated transactions.
```

### Reason

This is mostly correct already, but “dashboard layer” is more precise than “analytics layer” because the implementation is dashboard-based.

## 18. Objectives: Forecasting Language Should Be Framed as Justification, Not Full ML Implementation

### Location

Section: `Objectives`  
Subsection: Specific objective about dashboards and governance controls

### Current

```text
Hussain et al. (2023) further demonstrates how trend forecasting can aid in planning for resources and staffing, both of which contributes to a justification for developing KPI dashboards, governance controls, and analytics as primary functions within institutions.
```

### Fix

```text
Hussain et al. (2023) further demonstrates how trend forecasting can aid resource and staffing planning, which supports MarriottConnect's use of dashboard trends and simple forecast indicators as decision-support references.
```

### Reason

The current system includes trend and forecast-style dashboard indicators, but not machine-learning or advanced statistical forecasting implementation.

## 19. Scope and Limitations: Forecasting Overstatement

### Location

Section: `Scope and Limitations`  
Subsection: `Limitations`

### Current

```text
Analytics outputs are based on available institutional historical records; forecasting and trend interpretation quality remains dependent on the completeness and consistency of encoded transactions.
```

### Fix

```text
Dashboard outputs are based on available institutional records; trend summaries and simple forecast indicators remain dependent on the completeness and consistency of encoded transactions.
```

### Reason

This keeps the limitation accurate without implying advanced forecasting models.

## 20. Scope and Limitations: Report-Generation Standards Too Broad

### Location

Section: `Scope and Limitations`  
Subsection: `Limitations`

### Current

```text
Policy, curriculum, and regulatory updates may require periodic configuration adjustments to preserve alignment between institutional operations and report-generation standards.
```

### Fix

```text
Policy, curriculum, and regulatory updates may require periodic configuration adjustments to preserve alignment between institutional operations, available exports, dashboards, and configured academic or finance workflows.
```

### Reason

This avoids implying that all regulatory or DepEd report-generation standards are fully implemented.

## 21. RRL: Machine-Learning Forecasting Should Be Treated as Directional Support

### Location

Section: `Review of Related Literature/Studies/Systems`  
Paragraphs discussing Esquivel and Esquivel (2021) and Hussain et al. (2023)

### Current

```text
This is directly applicable to MarriottConnect's analytics direction, where enrollment trend interpretation is expected to support section planning, staffing preparation, and capacity decisions.
```

```text
Hussain et al. (2023) supports the capstone objective of converting validated operational history into structured planning insight through dashboard-ready analytics.
```

### Fix

```text
This is applicable to MarriottConnect's dashboard direction because enrollment trend summaries and simple forecast indicators can support section planning, staffing preparation, and capacity decisions without requiring the present implementation to include full machine-learning forecasting.
```

```text
Hussain et al. (2023) supports the capstone objective of converting validated operational history into structured planning references through dashboard-ready trend indicators.
```

### Reason

The RRL can justify future or directional decision-support design, but the current system should not be represented as implementing machine-learning forecasting.

## 22. Any Mention of DepEd Reports and SF9 Generator as Completed Modules

### Location

Search the paper for:

```text
DepEd Reports
SF9 Generator
SF9
report-card generation
DepEd report generation
```

### Current

Any statement implying that DepEd Reports or SF9 Generator are fully working modules.

### Fix

Use one of these depending on the sentence:

```text
Available school-form exports and academic summaries are limited to implemented workflows such as enrollment export, SF2 attendance export, permanent-record views, finance daily reports, and dashboard summaries.
```

or:

```text
DepEd Reports and SF9 generation remain outside the confirmed implemented module scope and should not be described as completed system capabilities.
```

### Reason

The current repo has routes and sidebar links for DepEd Reports and SF9 Generator, but their React pages are placeholder-only.

## Summary of Safe Wording

Use these phrases consistently:

1. `dashboard indicators` instead of separate role `analytics modules`.
2. `trend summaries and simple forecast indicators` instead of `machine-learning forecasting`.
3. `searchable student directory viewing` instead of `student directory maintenance`.
4. `billing schedule and due allocation logic` instead of standalone `Billing Schedule and Due Allocation Module`.
5. `teacher dashboard grade completion indicators` instead of `Gradebook Validation Module`.
6. `implemented exports and summaries` instead of broad `operational report generation`.

