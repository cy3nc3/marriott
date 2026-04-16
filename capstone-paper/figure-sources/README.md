# Figure Sources and Screenshot Checklist

This folder is for organizing source material for the paper figures.

Recommended use:

| Folder | Purpose |
|---|---|
| `screenshots/` | Put screenshots you capture from the running MarriottConnect system. |
| `templates/` | Put screenshots or cropped images of Excel templates if you want to show them in the paper. The actual template files already exist in the repo-level `templates/` folder. |
| `external-references/` | Put downloaded or cited external figure sources, such as DepEd school form guidance or systems analysis diagram references. |
| `exported-diagrams/` | Put exported PNG/SVG versions of the Graphviz DOT diagrams from `capstone-paper/MERMAID_SYSTEM_DIAGRAMS.md`. |

## Rendered Diagram Outputs

The core design diagrams have been rendered from Graphviz DOT sources and saved in two formats:

| Folder | Use |
|---|---|
| `exported-diagrams/svg/` | Recommended for DOCX when supported, because SVG stays sharp when resized. |
| `exported-diagrams/png/` | Use when DOCX/image handling requires raster images. These are rendered with a white background. |
| `exported-diagrams/sources/` | Individual `.dot` files extracted from `capstone-paper/MERMAID_SYSTEM_DIAGRAMS.md`. |

The diagrams use sharp orthogonal connectors. Insert SVG files when possible because they stay sharp after resizing. Use PNG files only when the document editor cannot handle SVG reliably.

## Existing Excel Templates

The actual template files are already stored in the repo-level `templates/` folder:

| Template File | Related Figure |
|---|---|
| `templates/SF1_2025.xls` | Figure X. SF1 Excel Template |
| `templates/SF2_2025.xls` | Figure X. SF2 Excel Template |
| `templates/SF5.xlsx` | Figure X. SF5 Excel Template |
| `templates/_SY 26-27 Enrolment.xlsx` | Figure X. Enrollment Excel Template |

Do not move these workbook files unless the application paths are updated. For the paper, capture screenshots or cropped images and place those images under `capstone-paper/figure-sources/templates/`.

## Screenshot Checklist

Use this checklist when taking screenshots from the system. The `Suggested Caption` column can be used directly as figure placeholder names.

| Suggested Caption | Page or Source to Capture | Notes |
|---|---|---|
| Figure X. Login Page | `/login` | Use as an authentication/interface screenshot if your paper still needs UI figures. |
| Figure X. Super Admin Dashboard | `/dashboard` as Super Admin | Capture role dashboard. |
| Figure X. User Manager Module | `/super-admin/user-manager` | Show account management table or form. |
| Figure X. Permissions Module | `/super-admin/permissions` | Show role permission controls. |
| Figure X. Audit Logs Module | `/super-admin/audit-logs` | Show activity log list. |
| Figure X. Announcements Module | `/announcements` as authorized user | Show announcement list or creation modal. |
| Figure X. Announcement Report Page | Announcement report page | Show read/response report if sample announcement exists. |
| Figure X. System Settings Module | `/super-admin/system-settings` | Show identity, controls, or backup section. |
| Figure X. Admin Dashboard | `/dashboard` as Admin | Capture role dashboard. |
| Figure X. Academic Controls Module | `/admin/academic-controls` | Show school year manager and quarter controls. |
| Figure X. Curriculum Manager Module | `/admin/curriculum-manager` | Show subjects and certified teachers. |
| Figure X. Section Manager Module | `/admin/section-manager` | Show sections and advisers. |
| Figure X. Schedule Builder Module | `/admin/schedule-builder` | Show schedule grid or schedule form. |
| Figure X. Class Lists Module | `/admin/class-lists` | Show class list by section. |
| Figure X. Grade Verification Module | `/admin/grade-verification` | Show submitted, returned, or verified grade rows. |
| Figure X. Registrar Dashboard | `/dashboard` as Registrar | Capture role dashboard. |
| Figure X. Student Directory Module | `/registrar/student-directory` | Show directory table and SF1 status area. |
| Figure X. SF1 Upload Function | `/registrar/student-directory` | Capture upload control or result summary after upload. |
| Figure X. Enrollment Queue Module | `/registrar/enrollment` | Show enrollment queue and export workbook button. |
| Figure X. Permanent Records Module | `/registrar/permanent-records` | Show academic history and Print SF10 button. |
| Figure X. Registrar Data Import Module | `/registrar/data-import` | Show import panel and history. |
| Figure X. Batch Promotion Module | `/registrar/batch-promotion` | Show promotion monitor, conditional queue, or grade completeness issues. |
| Figure X. Remedial Entry Module | `/registrar/remedial-entry` | Show remedial subject ratings. |
| Figure X. Student Departure Module | `/registrar/student-departure` | Show departure details form. |
| Figure X. Finance Dashboard | `/dashboard` as Finance Staff | Capture role dashboard. |
| Figure X. Cashier Panel Module | `/finance/cashier-panel` | Show student lookup or current transaction. |
| Figure X. Student Ledgers Module | `/finance/student-ledgers` | Show ledger profile, dues schedule, or ledger entries. |
| Figure X. Transaction History Module | `/finance/transaction-history` | Show transaction search/list. |
| Figure X. Finance Data Import Module | `/finance/data-import` | Show import panel and history. |
| Figure X. Fee Structure Module | `/finance/fee-structure` | Show fee breakdown by grade. |
| Figure X. Discount Manager Module | `/finance/discount-manager` | Show discount programs and export column settings. |
| Figure X. Product Inventory Module | `/finance/product-inventory` | Show product price catalog. |
| Figure X. Daily Reports Module | `/finance/daily-reports` | Show daily collection report. |
| Figure X. Due Reminder Settings Module | `/finance/due-reminder-settings` | Show reminder automation rules. |
| Figure X. Teacher Dashboard | `/dashboard` as Teacher | Capture role dashboard. |
| Figure X. Teacher Schedule Module | `/teacher/schedule` | Show teacher weekly schedule. |
| Figure X. Attendance Module | `/teacher/attendance` | Show attendance context and attendance log. |
| Figure X. SF2 Export Function | `/teacher/attendance` | Capture export control if visible after selecting class/month. |
| Figure X. Grading Sheet Module | `/teacher/grading-sheet` | Show class/subject selector and score matrix. |
| Figure X. Advisory Board Module | `/teacher/advisory-board` | Show advisory class grades or conduct and values. |
| Figure X. Student Dashboard | `/dashboard` as Student | Capture role dashboard. |
| Figure X. Student Schedule Module | `/student/schedule` | Show schedule grid. |
| Figure X. Student Grades Module | `/student/grades` | Show general average and subjects. |
| Figure X. Parent Dashboard | `/dashboard` as Parent | Capture role dashboard. |
| Figure X. Parent Schedule Module | `/parent/schedule` | Show learner schedule. |
| Figure X. Parent Grades Module | `/parent/grades` | Show report card context and subjects. |
| Figure X. Parent Billing Information Module | `/parent/billing-information` | Show account summary and dues schedule. |
| Figure X. Notification Inbox Module | `/notifications` | Show notification inbox. |
| Figure X. Announcement Details Page | Announcement details page | Show announcement content and attachments if present. |
| Figure X. Profile Settings Page | `/settings/profile` | Show profile form. |
| Figure X. Security Settings Page | `/settings/password` | Show password/security settings. |
| Figure X. Account Settings Page | `/settings/account` | Show account controls. |
| Figure X. Notification Settings Page | `/settings/notifications` | Show notification preferences. |
| Figure X. Appearance Settings Page | `/settings/appearance` | Show appearance settings. |
| Figure X. Desktop Required Page | Trigger a desktop-only page from mobile viewport | Useful if the paper discusses desktop-required workflows. |
| Figure X. SF1 Excel Template | `templates/SF1_2025.xls` | Capture workbook header and learner row layout. |
| Figure X. SF2 Excel Template | `templates/SF2_2025.xls` | Capture workbook attendance layout. |
| Figure X. Enrollment Excel Template | `templates/_SY 26-27 Enrolment.xlsx` | Capture workbook columns and summary rows. |
| Figure X. SF5 Excel Template | `templates/SF5.xlsx` | Optional. Use only if discussing template support, not completed report module. |

## Excluded or Future Screenshots

Avoid using these as completed-feature screenshots unless they are implemented later:

| Page | Reason |
|---|---|
| `/admin/deped-reports` | Placeholder page only. |
| `/admin/sf9-generator` | Placeholder page only. |

## External Reference Checklist

These are not screenshots from the system. Save source notes, downloaded PDFs, or source screenshots in `external-references/` if your adviser requires references for conceptual figures.

| Topic | Suggested Source Type |
|---|---|
| Context diagram, DFD, ERD, HIPO chart definitions | Systems analysis and design textbook or credible academic source. |
| Role-based access control | Information security or access control source. |
| SF1, SF2, and SF5 template meaning | Official DepEd school form guide, memorandum, or official template source. |
| Learner records and permanent record continuity | DepEd learner record guidance or official school form documentation. |
| Data privacy for student records | Philippine Data Privacy Act or National Privacy Commission guidance. |
| Grade computation policy | DepEd grading policy only if the wording matches the implemented weighted-grade computation. |
