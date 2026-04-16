# Requirements Documentation

The proposed Marriott School Information System, also referred to as MarriottConnect, is designed to centralize and improve the academic, registrar, finance, communication, and governance workflows of the school. The system replaces fragmented office-based records and repeated manual encoding with one role-based web application that supports secure access, real-time operational visibility, structured student records, payment posting, grading, attendance, announcements, and parent-facing information.

The system is accessible through a web browser and presents each user with modules appropriate to their role. It supports Super Admin, Admin, Registrar, Finance, Teacher, Student, and Parent users. Each role receives a dashboard and role-specific pages so that school personnel can work from the same authoritative dataset while still observing proper access boundaries.

## Web Application

### Login Page

The Login Page serves as the authentication interface where users securely enter their credentials to access their assigned account. The page supports standard sign-in, saved account login, password reset access, email verification, and two-factor authentication challenge flows when required.

**Figure 2. Login Page**

## Super Admin Module

### Super Admin Dashboard

The Super Admin Dashboard presents system-wide governance information, operational indicators, recent activity, and administrative shortcuts. It helps the highest-level administrator monitor users, system configuration, announcements, and institutional activity from one entry point.

**Figure 4. Super Admin Dashboard**

### User Manager

The User Manager allows the Super Admin to create, update, enable, disable, and manage user accounts. It supports role assignment, password reset actions, account status control, and basic user profile administration for school personnel and portal users.

**Figure 4.1. User Manager Module**

### Permissions

The Permissions module allows the Super Admin to review and configure role-based access controls. It helps ensure that each user role can only access the system pages and actions appropriate to that role.

**Figure 4.2. Permissions Module**

### Audit Logs

The Audit Logs module records and displays important user actions and system activities. It supports accountability by making administrative and operational changes traceable for review.

**Figure 4.3. Audit Logs Module**

### Announcements Management

The Announcements Management module allows authorized users to create, publish, update, cancel, and remove announcements. It supports audience targeting, scheduled publishing, attachments, acknowledgement tracking, event responses, and report views.

**Figure 4.4. Announcements Management Module**

### Announcement Report

The Announcement Report page summarizes announcement delivery, read status, acknowledgement status, and event response information. It helps administrators evaluate whether recipients have received and responded to important school communications.

**Figure 4.5. Announcement Report Page**

### System Settings

The System Settings module allows the Super Admin to manage school identity, branding, maintenance mode, parent portal availability, and backup or restore controls. It centralizes key configuration options that affect the whole system.

**Figure 4.6. System Settings Module**

## Admin Module

### Admin Dashboard

The Admin Dashboard provides academic management indicators such as enrollment trends, schedule readiness, section status, grade verification progress, and academic alerts. It helps administrators monitor academic operations and make planning decisions.

**Figure 5. Admin Dashboard**

### Academic Controls

The Academic Controls module manages the active school year, quarter progression, academic dates, and simulation controls used for academic-year lifecycle handling. It ensures that dependent academic records follow the correct school-year context.

**Figure 5.1. Academic Controls Module**

### Curriculum Manager

The Curriculum Manager allows administrators to create, update, delete, and organize subjects. It also supports teacher certification mapping so that teaching assignments can be matched with appropriate subject qualifications.

**Figure 5.2. Curriculum Manager Module**

### Section Manager

The Section Manager allows administrators to create, update, and manage sections by grade level and academic year. It supports adviser assignment and section organization for class scheduling and roster preparation.

**Figure 5.3. Section Manager Module**

### Schedule Builder

The Schedule Builder allows administrators to create, edit, and delete class schedules. It helps prevent schedule conflicts by organizing subject assignments, sections, teachers, days, and times in a structured academic schedule.

**Figure 5.4. Schedule Builder Module**

### Class Lists

The Class Lists module displays section-based student rosters. It helps administrators and academic staff review enrolled learners by class section and supports academic record checking.

**Figure 5.5. Class Lists Module**

### Grade Verification

The Grade Verification module allows administrators to monitor submitted grades, set deadlines, enable reminder automation, verify valid submissions, and return submissions that require correction. It provides academic quality control before grade records are finalized.

**Figure 5.6. Grade Verification Module**

## Registrar Module

### Registrar Dashboard

The Registrar Dashboard presents registrar-focused indicators such as enrollment queue status, student record readiness, SF1 alignment, promotion cases, remedial cases, and departure records. It helps registrar staff monitor record processing and learner movement.

**Figure 6. Registrar Dashboard**

### Student Directory

The Student Directory displays learner records for the selected school year. It supports searching, reviewing LIS alignment status, and enriching student information through LRN-based SF1 upload matching.

**Figure 6.1. Student Directory Module**

### SF1 Upload

The SF1 Upload function allows registrar staff to import official learner information and enrich existing student records using LRN matching. This reduces duplicate encoding and keeps internal records aligned with DepEd-related student data.

**Figure 6.2. SF1 Upload Function**

### Enrollment Queue

The Enrollment Queue supports intake creation, updating, deletion, and export of enrollment records. It captures initial student enrollment details and connects the registrar workflow with finance payment processing.

**Figure 6.3. Enrollment Queue Module**

### Permanent Records

The Permanent Records module stores long-term learner academic information. It supports record review and continuity for students across school years, grade levels, promotions, remedial actions, and departures.

**Figure 6.4. Permanent Records Module**

### Data Import for Permanent Records

The Data Import module allows registrar staff to upload permanent record data for batch processing. It improves record migration and reduces the need for manual one-by-one encoding.

**Figure 6.5. Registrar Data Import Module**

### Batch Promotion

The Batch Promotion module assists registrar staff in reviewing students for promotion to the next grade level. It includes review and resolution workflows for cases that require manual validation before promotion is finalized.

**Figure 6.6. Batch Promotion Module**

### Remedial Entry

The Remedial Entry module records remedial intake and remedial case details. It supports tracking of students who require subject remediation and connects those records with academic and finance-related requirements.

**Figure 6.7. Remedial Entry Module**

### Student Departure

The Student Departure module records student transfer, withdrawal, or departure information. It allows the registrar to maintain accurate learner status and historical movement records.

**Figure 6.8. Student Departure Module**

## Finance Module

### Finance Dashboard

The Finance Dashboard provides finance-focused summaries such as payment activity, outstanding balances, due reminders, transaction trends, and ledger-related alerts. It helps finance personnel monitor collection activity and billing risk.

**Figure 7. Finance Dashboard**

### Cashier Panel

The Cashier Panel supports one-page transaction posting. It includes student search suggestions, official receipt number reservation, payment item entry, transaction confirmation, and posting of payments to the appropriate student record.

**Figure 7.1. Cashier Panel Module**

### Student Ledgers

The Student Ledgers module displays learner-level financial records, including charges, payments, credits, and balances. It provides finance staff with a clear view of each student's account history.

**Figure 7.2. Student Ledgers Module**

### Transaction History

The Transaction History module lists posted payment records and supports authorized void, refund, and reissue actions. It helps maintain traceability for changes to financial transactions.

**Figure 7.3. Transaction History Module**

### Finance Data Import

The Finance Data Import module allows finance staff to upload transaction data for structured processing. It supports migration or batch entry of finance records using validated import templates.

**Figure 7.4. Finance Data Import Module**

### Fee Structure

The Fee Structure module allows finance staff to create, update, and delete fee definitions. It also includes remedial subject fee configuration so remedial-related charges can be managed consistently.

**Figure 7.5. Fee Structure Module**

### Product Inventory

The Product Inventory module manages school-related items that can be sold or billed, such as uniforms, supplies, or other inventory-linked products. It stores item descriptions and prices for transaction use.

**Figure 7.6. Product Inventory Module**

### Discount Manager

The Discount Manager allows finance staff to define discounts and tag eligible students. It supports discount categorization and controlled application of financial assistance or payment reductions.

**Figure 7.7. Discount Manager Module**

### Daily Reports

The Daily Reports module summarizes daily finance activity. It supports review of collections, transaction totals, and operational reporting for a selected date or reporting period.

**Figure 7.8. Daily Reports Module**

### Due Reminder Settings

The Due Reminder Settings module allows finance staff to configure billing reminder rules and automation status. It supports scheduled notifications for upcoming or overdue balances and keeps dispatch history for traceability.

**Figure 7.9. Due Reminder Settings Module**

## Teacher Module

### Teacher Dashboard

The Teacher Dashboard provides teaching-related indicators such as assigned classes, grading progress, attendance tasks, advisory updates, and academic alerts. It gives teachers a focused view of current responsibilities.

**Figure 8. Teacher Dashboard**

### Teacher Schedule

The Teacher Schedule module displays assigned class schedules, advisory schedules, subjects, sections, days, and time slots. It helps teachers organize daily class responsibilities.

**Figure 8.1. Teacher Schedule Module**

### Grading Sheet

The Grading Sheet module allows teachers to configure grading rubrics, create graded activities, enter student scores, save work, and submit grades for verification. It replaces manual grade computation with structured score encoding.

**Figure 8.2. Grading Sheet Module**

### Attendance

The Attendance module allows teachers to record student attendance using SF2-aligned attendance statuses. It supports subject-assignment-aware attendance records and export of SF2-related attendance information.

**Figure 8.3. Attendance Module**

### Advisory Board

The Advisory Board module supports adviser-specific class monitoring and conduct-related encoding. It allows advisers to record conduct ratings and manage advisory class context.

**Figure 8.4. Advisory Board Module**

## Student Module

### Student Dashboard

The Student Dashboard presents learner-focused information such as class schedule, academic alerts, grade visibility, and school announcements. It is designed for academic awareness rather than administrative control.

**Figure 9. Student Dashboard**

### Student Schedule

The Student Schedule module displays the student's assigned classes, subjects, teachers, days, and time slots. It helps students view their academic timetable in one place.

**Figure 9.1. Student Schedule Module**

### Student Grades

The Student Grades module allows students to view available grades, final grade information, and academic performance details. It provides transparent access to academic results once records are available.

**Figure 9.2. Student Grades Module**

## Parent Module

### Parent Dashboard

The Parent Dashboard presents child-related academic and finance summaries, including grades, schedule context, billing risk, due status, recent payments, and school announcements. It helps parents monitor their child's school standing from a role-limited portal.

**Figure 10. Parent Dashboard**

### Parent Schedule

The Parent Schedule module allows parents to view their child's class schedule. It supports household awareness of class times and academic responsibilities.

**Figure 10.1. Parent Schedule Module**

### Parent Grades

The Parent Grades module allows parents to view their child's academic performance. It provides grade visibility without giving parents access to staff-only academic management functions.

**Figure 10.2. Parent Grades Module**

### Billing Information

The Billing Information module allows parents to view dues, outstanding balances, recent payments, and billing-related status for their child. It improves payment transparency and reduces the need for repeated manual balance inquiries.

**Figure 10.3. Billing Information Module**

### Parent Portal Disabled Page

The Parent Portal Disabled Page appears when parent portal access is temporarily turned off through system settings. It informs parent users that portal access is unavailable while keeping administrative controls intact.

**Figure 10.4. Parent Portal Disabled Page**

## Notifications and Communication Module

### Notification Inbox

The Notification Inbox displays announcements and school communications for the logged-in user. It supports read tracking, acknowledgement, event response actions, and access to announcement attachments.

**Figure 11. Notification Inbox Module**

### Announcement Details

The Announcement Details page displays the full announcement content, attached files, recipient actions, and response options when applicable. It supports clear communication between school administrators, staff, students, and parents.

**Figure 11.1. Announcement Details Page**

### Attachment Viewing and Download

The Attachment Viewing and Download functions allow users to open or download files attached to announcements. This supports distribution of circulars, forms, schedules, and other school documents.

**Figure 11.2. Announcement Attachment Function**

## Settings Module

### Profile Settings

The Profile Settings page allows users to review and update personal profile details, including account identity information and avatar-related data when available.

**Figure 12. Profile Settings Page**

### Security Settings

The Security Settings page allows users to update their password, manage two-factor authentication setup, view recovery codes, and maintain account security.

**Figure 12.1. Security Settings Page**

### Account Settings

The Account Settings page provides account-level controls available to the user, including account management actions that are separate from profile editing.

**Figure 12.2. Account Settings Page**

### Notification Settings

The Notification Settings page allows users to configure notification preferences. It helps users manage how they receive system and school communication updates.

**Figure 12.3. Notification Settings Page**

### Appearance Settings

The Appearance Settings page allows users to configure the interface appearance preference. It supports a more comfortable user experience across different user environments.

**Figure 12.4. Appearance Settings Page**

### Session Management

The Session Management functions allow users to end active sessions or sign out from other sessions. This helps secure accounts when users access the system from multiple devices.

**Figure 12.5. Session Management Function**

## Access Control and Device Policy

### Role-Based Access

The system enforces role-based access so that each user can only open modules and perform actions assigned to their role. Super Admin, Admin, Registrar, Finance, Teacher, Student, and Parent users each receive separate route groups, dashboards, and permissions.

**Figure 13. Role-Based Access Control**

### Desktop-Required Page

The Desktop-Required Page appears when a user attempts to access selected operational modules from an unsupported mobile device. This policy protects high-density administrative workflows that require a desktop layout.

**Figure 13.1. Desktop-Required Page**

### Maintenance Mode

Maintenance Mode allows system administrators to temporarily restrict access for ordinary users while administrative work is being performed. Authorized administrators retain the ability to manage the system during maintenance periods.

**Figure 13.2. Maintenance Mode**

## System Data and Record Management

### Student Records

Student records store learner identity, grade level, section, enrollment, LRN, academic history, attendance, grades, conduct ratings, billing data, remedial status, and departure information. These records serve as the central data source for registrar, academic, finance, student, and parent modules.

**Figure 14. Student Records**

### Academic Records

Academic records include subjects, teacher certifications, subject assignments, class schedules, rubrics, graded activities, student scores, final grades, grade submissions, attendance, and conduct ratings. These records support class management, grade encoding, verification, and reporting.

**Figure 14.1. Academic Records**

### Finance Records

Finance records include fees, inventory items, discounts, student discounts, billing schedules, transactions, transaction items, ledger entries, transaction due allocations, official receipt reservations, refunds, reissues, and void actions. These records support payment posting, balance visibility, auditability, and reporting.

**Figure 14.2. Finance Records**

### Communication Records

Communication records include announcements, attachments, recipients, read receipts, acknowledgements, event responses, reminder dispatches, and notification settings. These records support targeted communication and response tracking.

**Figure 14.3. Communication Records**

### Governance Records

Governance records include users, roles, permissions, settings, saved account logins, audit logs, maintenance settings, parent portal settings, and system backup controls. These records support system administration, access control, accountability, and operational continuity.

**Figure 14.4. Governance Records**

## Core System Workflows

### Enrollment to Student Record Workflow

The registrar records enrollment intake, finance processes the required payment, and the registrar later enriches student records through SF1 upload and LRN matching. This workflow reduces duplicate encoding and keeps student records aligned with official learner information.

**Figure 15. Enrollment to Student Record Workflow**

### Grading and Verification Workflow

Teachers encode rubrics, activities, scores, attendance, and conduct-related information. Submitted grades are reviewed by administrators through Grade Verification, where submissions can be verified or returned for correction.

**Figure 15.1. Grading and Verification Workflow**

### Billing and Payment Workflow

Finance staff configure fees, post payments through the Cashier Panel, allocate payments to dues, update ledgers, and review transactions through reports and history pages. Parents can view billing information through the parent portal.

**Figure 15.2. Billing and Payment Workflow**

### Announcement and Notification Workflow

Authorized users create announcements with target audiences and optional attachments. Recipients receive them in the Notification Inbox, mark items as read, acknowledge messages, or submit event responses when required.

**Figure 15.3. Announcement and Notification Workflow**

### Due Reminder Workflow

Finance staff configure reminder rules for billing due dates. The system dispatches reminders based on the configured schedule and retains reminder history for monitoring and reporting.

**Figure 15.4. Due Reminder Workflow**
