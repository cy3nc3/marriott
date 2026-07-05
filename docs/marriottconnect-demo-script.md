# MarriottConnect System Demo Script

This script is designed for five speakers. The demo follows the normal school operation cycle: system setup, enrollment, cashiering, academic setup, teacher encoding, student and parent access, and system safeguards. Backend explanations are already included inside the main script so the delivery sounds continuous.

Recommended total demo time: 25 to 35 minutes.

Lines labeled `Demo action` are stage directions. The speaker should perform the click, page opening, or screen focus first, then continue with the spoken explanation below it.

Lines labeled `Live demo action` are controlled actions that can safely be performed during the defense using prepared demo data. Avoid destructive actions such as deleting records, restoring backups, or running bulk imports during the live presentation.

## Speaker 1: Introduction, Login, Governance, And System Setup

### Opening

Demo action: Keep the MarriottConnect login page or landing page open on the projector before the first speaker starts.

Good day. We are presenting MarriottConnect, an integrated student information system with decision support analytics for Marriott School.

MarriottConnect centralizes the school operations that are usually handled separately, such as enrollment, student records, cashiering, class scheduling, grading, attendance, reports, announcements, and user account management. In this demo, we will follow the same flow used in actual school operations: first system access and governance, then enrollment, payment, academic setup, teacher encoding, portal access, and system safeguards.

### Login And Role-Based Access

Demo action: Open the login page, enter the Super Admin account, then click `Log in`.

I will start by logging in as a system-level account. When the user enters an email and password, the system authenticates the account, starts a secure session, checks the user's role, and redirects the user to the correct dashboard. This is why each user sees a different set of pages. The registrar cannot access cashier-only pages, the teacher cannot access super admin settings, and students or parents can only access their own records.

On the technical side, this is handled through authentication guards, session handling, and role-based middleware. Each protected route checks the authenticated user's role before the controller is allowed to load the page. This means access control is enforced in the backend, not only hidden from the sidebar in the frontend.

### Super Admin Dashboard

Demo action: Stay on the Super Admin dashboard and point to the KPI cards, alerts, analytics, and notification area.

This is the Super Admin dashboard. It shows system-level information such as important actions, account readiness, backup status, announcements, and notifications. These indicators help the institution decide if the system is ready for daily use. For example, backup status helps the school know if data protection is current, while account claiming and activity indicators help identify users who may still need onboarding.

The dashboard is not manually encoded. The system gathers information from user records, audit logs, backup records, announcements, and settings, then prepares the summary values before displaying them on the page.

In the backend, this uses aggregated database queries and dashboard services instead of loading raw records one by one. The controller prepares KPIs and analytics data, then sends them to the frontend as structured props. This keeps the dashboard focused on decision support rather than just listing database rows.

### User Manager And Account Controls

Demo action: In the sidebar, click `User Manager`. Open the create user dialog or point to the account list, role filters, claim status, and account controls.

Live demo action: Create a sample staff account using a prepared demo personal email, assign a non-admin role such as Teacher or Finance, save it, then show that the account is pending claim. If asked about admin limits, open the role selector and explain that Super Admin and Admin accounts are limited by backend validation.

Next, I will open User Manager. This page allows the super admin to manage staff accounts. For governance, the system limits high-level accounts such as Super Admin and Admin. Staff accounts use account claiming instead of birthday-based default passwords.

When a staff account is created, the system stores the staff role, account email, personal email, and account status. It can send a claim email to the personal email, and the staff member sets their own password using a secure expiring token. This prevents predictable passwords and avoids manually sharing credentials.

The claim flow uses tokenized account setup. Instead of saving a temporary plain password, the system stores a claim token, expiry timestamp, and claim status. When the staff opens the link, the backend validates the token before allowing password creation.

### Audit Logs And Security

Demo action: In the sidebar, click `Audit Logs`. Show the table of recent actions and point to the actor, action, module, and timestamp columns.

Now I will open Audit Logs. Audit logs show important actions performed in the system, such as account changes, settings updates, and other administrative actions. This gives accountability because the system records who performed the action, what module was affected, and when it happened. Sensitive information such as IP address is not shown in the audit log display.

This is implemented using audit logging at the application layer. When selected create, update, or delete actions happen, the system writes an audit record with the actor, action type, affected model, and timestamp. This is useful during review because the school can trace changes without exposing unnecessary technical details to normal users.

### Announcements

Demo action: In the sidebar, click `Announcements`. Click `Create Announcement`, show role selection, then point to the select-all option and delivery settings.

Live demo action: Create a short demo announcement targeted to one safe role, such as Teachers only, then publish it. After saving, open the notification area to show that the announcement appears inside the system.

Now I will open Announcements. Announcements allow the school to send information to selected roles such as students, parents, teachers, or staff. The system supports selecting target roles, including a select-all option.

When an announcement is created, the system resolves the target audience, creates notification records, and sends email delivery when enabled. Announcement emails can be sent to both official account emails and personal emails, so users can receive important updates even outside the portal.

The announcement module uses audience resolution, notification records, and queued email delivery. This means the frontend only submits the announcement content and target roles, while the backend expands those roles into actual users and handles delivery consistently.

### Backup And Restore Safeguards

Demo action: While still using the Super Admin account, open the system settings or backup area if available. Point to backup status and explain the restore safeguard at a high level.

Live demo action: Show the latest backup record or backup status only. Do not click restore during the live demo because restore is a controlled recovery operation, not a normal daily workflow.

For system safeguards, MarriottConnect supports backup and restore workflows. If the system needs to be restored, the restore process is designed to avoid duplicate or partial data by using structured restore operations and idempotent records where applicable.

Backup scripts export database data and related files. Restore scripts use controlled database restore procedures. For imports and seeders, the system uses unique keys and update-or-create logic where possible to avoid duplicating data after repeated operations.

This is important for disaster recovery. If a restore or import is interrupted, idempotent operations and unique constraints help prevent duplicate records when the process is repeated.

After the system and user access are prepared, the next step in school operations is enrollment. I will now pass the demo to Speaker 2.

## Speaker 2: Registrar Workflow, Enrollment, Student Directory, And Records

### Registrar Dashboard

Demo action: Switch to a Registrar account or use the role switcher if available. Open the Registrar dashboard and point to enrollment, requirements, and section-related KPI cards.

I will now demonstrate the Registrar side of the system. The Registrar dashboard focuses on enrollment operations, missing requirements, students waiting for section assignment, and student records that may need review.

These analytics help the registrar decide where to focus daily work. Students with missing requirements tell the office who needs document follow-up. Students with grade level but no section help the registrar decide if section assignment should be completed or if another section may be needed. Enrollment movement also helps the school estimate if current facilities and class capacity are enough.

The dashboard counts and analyzes enrollment records, student records, requirements, grade levels, sections, and the current school year context directly from the database.

The dashboard values are produced using filtered queries and school-year scoping. For example, the system does not count old enrollment records as current enrollment unless they belong to the active academic year. This prevents the dashboard from mixing historical data with current operational data.

### Enrollment Page

Demo action: In the Registrar sidebar, click `Enrollment`. Open a sample enrollment record or start a new enrollment form. Point to LRN, student personal email, guardian contact email, requirements, discount tagging, grade level, section, and payment term.

Live demo action: Encode one prepared sample enrollment using a unique 12-digit LRN, fill in student and guardian details, add student personal email and guardian contact email, mark one requirement as submitted and one as to-follow, select grade level and section, choose a payment term, apply a sample discount if available, then save.

Now I will open the Enrollment page. This page is used to encode new students, returning students, and enrollment details. The registrar can enter the learner's LRN, name, birthdate, gender, guardian details, contact number, contact email, student personal email, grade level, section, payment term, submitted requirements, and discount tagging.

The system requires grade level and section before enrollment can be completed. It also tracks the submitted report card and birth certificate. These requirements do not block enrollment, but they affect the student's status in the directory so the registrar can follow up later.

When the form is saved, the system validates the required fields, checks the LRN format, prevents duplicate learner records, links the student to the school year, creates system-generated student and parent accounts, and prepares account claim tokens. If the student personal email is provided, the student claim email is sent there. If not, the system can use the guardian contact email. Parent claim emails use the enrollment contact email.

If a discount or scholarship is selected during enrollment, the system stores it with the enrollment record. This is important because finance will use the same saved discount when calculating the student's assessment and billing schedule.

This save process is handled as a database transaction. That means the student record, enrollment record, generated user accounts, requirement status, discount tag, and billing preparation are treated as one connected operation. If a critical part fails, the system can roll back instead of leaving incomplete enrollment data.

The LRN is used as a unique learner identifier. The backend validation rules check required fields, acceptable formats, and duplicate records before inserting or updating data in the relational tables.

After enrollment, the system can generate the Registration and Assessment Form. This form shows the student's information, academic details, class schedule, assessment breakdown, other charges and adjustments, net assessment, payment schedule, and account claiming instructions. The form is built from the actual enrollment, student, section, fee, discount, billing schedule, and class schedule records, so the registrar and finance data stay consistent.

Demo action: Click the print or view action for the `Registration Assessment Form`, then briefly show the student information, class schedule, assessment breakdown, payment schedule, and account claiming instructions.

Live demo action: Generate the Registration Assessment Form for the newly encoded or prepared student, then point to the class schedule, net assessment, payment schedule, and account claiming instructions.

From the same enrollment workflow, I will briefly show the Enrollment Workbook export. This supports the main enrollment process by helping the registrar and school head review enrollment distribution by grade level, section, payment term, discount tagging, and student status without manually re-encoding the list in a spreadsheet.

Demo action: Click the `Export` or `Enrollment Workbook` action if available, then briefly show that the workbook contains enrollment data instead of discussing every column.

The form and workbook are generated by export builders. These builders transform database records into printable or spreadsheet-ready output. Instead of manually copying data, the system reads the same saved enrollment data and formats it for school use.

### Student Directory

Demo action: In the Registrar sidebar, click `Student Directory`. Use the search bar to search a seeded student, then point to status badges and requirement badges.

Live demo action: Search for the student used in the enrollment demo. If the student has not paid yet, explain why the student is still not treated as fully enrolled in the directory. If using a prepared paid student, open the record and show the requirement badge.

Now I will open the Student Directory. The directory lists enrolled students and allows the registrar to view student details. It shows badges such as complete requirements, missing requirements, or not enrolled. Students who are still for cashier payment do not appear as fully enrolled in the directory.

The directory uses enrollment status, school year, requirements, student records, and generated account emails from the database. It also has case-insensitive search suggestions and sorting so the registrar can quickly locate student records.

The search uses query filtering on names, LRN, and generated emails. The result list is scoped so students still waiting for cashier payment are not treated as fully enrolled directory records. This makes the list more accurate for registrar operations.

### Student Details And Enrollment History

Demo action: Click `View` or open a student from the directory. Scroll through learner profile, personal information, guardian contact, account emails, and enrollment history.

Inside the student details page, the registrar can view personal information, guardian contact information, generated student and parent account emails, and enrollment history. The enrollment history shows the school years where the student enrolled, the grade level, section, and whether the student transferred out or dropped out.

This page reads the student's enrollment records across school years, not just the current year. From the same student records context, the system can briefly show learner-list exports such as SF1 reference data and class lists, using the Student Directory, enrollment records, grade level, section, and LRN-aware student data.

Demo action: If time allows, click the SF1 or class list export action and show only the generated file preview or downloaded workbook briefly.

The enrollment history is possible because the system stores enrollments as separate records per academic year. The student identity stays the same, while the grade level, section, status, and school year can change over time.

### Permanent Records

Demo action: In the Registrar sidebar, click `Permanent Records`. Search for a student and show that records are tied to released grades and school years.

The Registrar can also access Permanent Records. Permanent records are populated when grades are released through the proper academic workflow. This prevents incomplete or unreleased grades from becoming official permanent records. Once grades are released, the system compiles the final grades and updates the student's permanent record.

This uses a release-based trigger in the academic workflow. The permanent record is not populated from draft grades. It waits for adviser release, then uses final grade records, enrollment records, and subject assignments to create the official learner record.

### Registrar Data Import

Demo action: While still using the Registrar account, open the Registrar `Data Import` page. Show the template-based upload area and explain that registrar imports use formal templates.

Live demo action: Upload only a prepared valid small registrar template if needed. Otherwise, show the upload controls and explain that random Excel layouts are intentionally rejected to protect learner-record quality.

The registrar import workflow supports structured student and enrollment-related data. Instead of accepting random layouts, the import process follows the system's own formal templates. This reduces mapping errors and prevents unpredictable records from entering the database.

When a registrar file is imported, the system validates expected sheets and columns, maps rows to student, enrollment, section, and requirement records, and prevents duplicates through identifiers such as LRN, school year, grade level, and section.

The import process uses strict template validation instead of adaptive guessing. This is safer because the backend can reject files with missing columns, wrong sheets, or invalid identifiers before any database write happens.

After enrollment details are encoded, the next operational step is payment and cashiering. I will now pass the demo to Speaker 3.

## Speaker 3: Finance Workflow, Cashier Payment, Ledgers, And Financial Reports

### Finance Dashboard

Demo action: Switch to a Finance account. Open the Finance dashboard and point to collection, overdue, and cashier-related KPI cards.

I will now demonstrate the Finance module. The Finance dashboard focuses on collection and payment-related decision support, such as this month's collection, overdue accounts, and payment follow-up needs.

These KPIs help the school decide if collections are enough for short-term obligations such as operating expenses and salary preparation. This month's collection shows actual incoming funds. Overdue account indicators help finance decide which accounts need follow-up. Cashier-related queues can also help the school decide if more cashier support is needed during busy enrollment periods.

The dashboard summarizes billing schedules, payments, transaction records, and outstanding balances from the database. The collection values come from actual posted transactions and due records.

Technically, the dashboard uses ledger and billing schedule queries to calculate totals. It separates amount due, amount paid, and remaining balance, so finance decisions are based on computed financial records rather than static labels.

### Cashier Panel

Demo action: In the Finance sidebar, click `Cashier Panel`. Open a student waiting for payment, review the assessment, then show the payment posting area without necessarily completing a real payment unless prepared.

Live demo action: Select the prepared student waiting for cashier payment, enter the upon-enrollment payment amount, choose the payment method, add a receipt or reference number, then post the payment. After saving, show that the transaction was recorded and that the enrollment can move forward.

Now I will open the Cashier Panel. This is where finance staff process payments. Students who completed enrollment but still need payment appear here for cashier processing. The cashier can select a student payment entry, review the assessment, post payment, and confirm the transaction.

For cash payment terms, the system can apply the full required amount. For installment plans, the system follows the generated billing schedule. When payment is posted, the system creates transaction records, updates billing schedule amounts paid, recalculates balances, and changes enrollment status when the required enrollment payment is completed.

The payment posting process uses database transactions to keep the ledger consistent. A transaction header, transaction line items, billing schedule updates, and enrollment status update must match. This prevents a payment from being recorded without updating the student's balance.

### Student Ledgers

Demo action: Click `Student Ledgers`. Use the search bar to select a student, then show dues, payments, balances, transaction history, and the overdue accounts button or modal.

Live demo action: Search for the same student after payment posting and show that the ledger now contains the posted payment. Then open a prepared student with overdue dues to show how finance can identify accounts needing follow-up.

Next, I will open Student Ledgers. This page allows finance staff to search for a student and view the account ledger. The ledger shows assessment, payments, dues, balances, and transaction history. It also includes an overdue accounts view with search so finance staff can quickly find students who need follow-up.

The ledger combines billing schedules and transaction records, then computes balances using amount due minus amount paid. Finance can also export a student ledger from this page when a printed or spreadsheet copy is needed. This export is only a support action, but it helps finance explain balances clearly to parents or school management.

Demo action: Click the ledger export action only briefly, then return to the ledger page so the demo remains focused on the finance workflow.

The ledger is built from normalized finance tables. Billing schedules define what should be paid, while transactions define what was actually paid. The frontend shows a unified view, but the backend keeps these records separate for auditability and reconciliation.

### Due Reminders

Demo action: Open the due reminder settings or show the reminder-related controls from Student Ledgers if available.

The system supports scheduled reminders for dues. Finance staff can configure reminders so parents or guardians are notified before or after due dates. The system stores reminder rules, creates scheduled notification jobs, and a scheduled command checks which reminders are due.

This uses scheduled jobs and deduplication keys. The scheduler can prepare reminders ahead of time and avoid sending the same reminder repeatedly for the same due item and reminder date.

### Transaction History

Demo action: Click `Transaction History`. Filter or search a student, then show transaction rows, receipt/reference details, and line items.

Live demo action: Search for the receipt or student used in the cashier demo, open the transaction details, and point to the cashier, date, reference number, and line items.

Now I will open Transaction History. This page shows payment records and other school-related purchases such as books, uniforms, and other items. It supports filtering and a transaction history export for reconciliation.

Each transaction has line items, official receipt references, cashier information, student reference, payment date, and amount. This gives finance staff a traceable financial trail and helps them review collection activity, audit payment records, and identify common transaction types.

From a backend perspective, this is a parent-child data structure. The main transaction stores the receipt-level details, while line items store the specific payments or purchases included in that transaction.

### Fee Structure, Discounts, And Daily Reports

Demo action: Open `Fee Structure`, then `Discount Manager`, then briefly open `Daily Reports`. Do not stay too long on each page; show how finance setup connects to billing.

The Finance module also includes Fee Structure and Discount Manager. Fee Structure defines tuition, miscellaneous fees, and other assessment charges by grade level and school year. Discount Manager defines discounts or scholarships that can be applied during enrollment.

When an enrollment assessment is generated, the system calculates the gross assessment, applies discounts, computes the net assessment, and generates the payment schedule.

Finance can also generate daily collection reports. This belongs to the cashiering close-of-day process. The report helps the school confirm how much was collected on a specific day, which cashier handled payments, and what payment categories contributed to the total. The export is generated from saved transaction and billing records.

The reports are generated through export controllers and report builders. They apply filters such as date range, cashier, and transaction category before formatting the result for review.

### Finance Data Import

Demo action: While still using the Finance account, open the Finance `Data Import` page if available. Show the template-based upload area for finance records.

Live demo action: Upload only a prepared valid small finance template if needed. Otherwise, show the upload controls and explain that bulk finance import should not be run live unless the file and rollback plan are prepared.

Finance import is used for structured financial records such as dues, ledgers, or transaction history when historical finance data needs to be migrated. The system should not guess from arbitrary spreadsheets because financial records affect balances, reports, and parent billing views.

When a finance file is imported, the backend validates the expected workbook structure, checks identifiers such as LRN, school year, due reference, and transaction reference, then maps rows into billing schedules or transaction records.

This keeps finance migration controlled and auditable. If the same valid file is processed again, unique references and update-or-create behavior help avoid duplicate dues or duplicate transaction records.

After enrollment and payment, the next step is academic setup: curriculum, sections, schedules, teacher assignment, attendance, grading, and promotion. I will now pass the demo to Speaker 4.

## Speaker 4: Admin Academic Setup, Teacher Workflow, Attendance, Grades, And Promotion

### Admin Dashboard

Demo action: Switch to an Admin account. Open the Admin dashboard and point to section capacity, subject staffing, schedule readiness, and grade verification analytics.

I will now demonstrate the Admin and Teacher academic workflow. The Admin dashboard monitors section capacity, subject staffing, grade verification, and scheduling readiness.

These analytics help the school make academic planning decisions. Section capacity indicators help determine if the school should open additional sections. Subject staffing pressure helps identify subjects that may need more qualified teachers. Schedule readiness helps confirm if classes can operate without conflict. Grade verification indicators help the school know if teachers or departments need follow-up before report card release.

The dashboard analyzes sections, enrollment counts, teacher profiles, subject assignments, schedules, and grade submissions from the database.

The analytics are built from relationships between academic years, grade levels, sections, subjects, teacher profiles, schedules, and grade submissions. This allows the dashboard to answer operational questions, such as whether a subject has enough qualified teacher coverage or whether grade submissions are delayed.

### Curriculum Manager And Teacher Profiles

Demo action: Click `Curriculum Manager`. Open a subject or assignment control, then show that teacher selection is based on qualified teachers. If available, open `Teacher Profiles` and show subject competency tags and grade band eligibility.

Live demo action: Open a subject assignment field and type a teacher name. Show that the selectable teachers are filtered by qualification. Do not change the demo assignment unless a prepared test subject is available.

Now I will open Curriculum Manager. This page manages subjects by grade level. When assigning teachers to subjects, the system checks teacher qualifications, so only qualified teachers should appear or be accepted for subject assignment.

Teacher profiling stores each teacher's degree, major, LET status, grade band eligibility, and subject competency tags. These profiles are stored separately from user accounts, so a teacher account does not automatically become eligible for every subject. The curriculum and schedule modules use these profiles to filter and validate teacher assignments.

The teacher assignment logic uses eligibility validation. It checks the teacher profile, subject competency tags, grade band, and qualification status before creating a teacher-subject link. This prevents a teacher from being assigned to a subject simply because they have a teacher account.

### Section Manager And Class Lists

Demo action: Click `Section Manager`. Open a section, show adviser assignment and adviser history, then point to class list or class-list export from the section/class list context.

Now I will open Section Manager. Sections are created per academic year and grade level. The system prevents duplicate section names for the same grade level and school year. It also shows adviser history so the school can see who previously handled a section.

Sections are connected to academic years, grade levels, advisers, enrollments, and subject schedules. From the class list or section context, the admin can export class lists. This export is only briefly shown because it supports the main section workflow. It helps advisers and teachers confirm the official learners assigned to their classes and helps the admin check whether sections are balanced.

The section manager uses uniqueness validation to prevent duplicate section names within the same grade level and school year. Adviser changes are also tracked so the system can show the current adviser without losing past adviser history.

### Schedule Builder

Demo action: Click `Schedule Builder`. Select a grade level or section, show the Monday-to-Friday schedule grid, and point to one subject repeating at a fixed one-hour time slot. If editing is safe, open the schedule modal and show teacher/subject selection.

Live demo action: Open the add or edit schedule modal for a prepared section, select a subject and qualified teacher, then show the conflict validation by explaining that overlapping teacher schedules are rejected. Save only if using a prepared empty slot.

Now I will open Schedule Builder. This page allows the admin to manage class schedules. The seeded schedules follow the TSS-style pattern: each subject has a fixed one-hour time slot and repeats Monday to Friday. Teachers are assigned based on their qualification profiles, and the system prevents teacher schedule conflicts, including overlapping times.

Schedules are stored as class schedule records linked to sections and subject assignments. When saving schedules, the system validates time ranges and checks conflicts so a teacher cannot be assigned to overlapping classes.

The conflict validation compares time ranges, not just exact start and end times. For example, a schedule from 7:20 to 8:20 conflicts with 7:30 to 8:20 because the time intervals overlap. The backend checks this before accepting the schedule.

### Grade Verification

Demo action: Click `Grade Verification`. Open the submission status modal and use the search bar to find a class or teacher.

The Admin can also monitor Grade Verification. Teachers submit grades, and the admin can verify them. The submission status modal includes search so the admin can quickly find classes or teachers.

Grade submissions are tracked by subject assignment, quarter, teacher, and status. Verification updates the submission state and makes the grading process auditable.

This module uses status transitions. A submission can move from encoded to submitted, then verified, depending on the workflow. This allows the admin to monitor which classes are ready for release and which still need follow-up.

### Teacher Dashboard

Demo action: Switch to a Teacher account. Open the Teacher dashboard and show today's class schedule, advisory card, KPI cards, and analytics.

Now I will switch to the Teacher portal. The teacher dashboard shows today's actual assigned classes and advisory section separately. If the teacher is an adviser, the dashboard does not show all subjects in the advisory section. It only shows the teacher's assigned subject classes plus a separate advisory card.

These KPIs help the teacher decide what needs immediate classroom action. Pending grade rows show which classes still need encoding. Students with low grades help identify learners who may need intervention. Attendance risk helps the teacher decide who may need follow-up before absences affect performance.

The teacher dashboard uses scoped queries based on the authenticated teacher's subject assignments. If the teacher is also an adviser, advisory information is loaded separately so the dashboard does not incorrectly show all subjects in the advisory section as if the adviser teaches them.

### Attendance

Demo action: Click `Attendance`. Select a class if needed, then show the attendance grid and the student attendance statuses.

Live demo action: Select a prepared class and date, mark one student as present, one as late, and one as absent, then save. After saving, explain that the same attendance record can be viewed later by the student and parent portals.

Now I will open Attendance. Teachers can select their assigned class and mark student attendance. Attendance is stored per enrollment, subject assignment, date, and status, which allows the same record to appear in teacher, student, and parent portals.

From attendance records, the system can support SF2 attendance export. This is mentioned briefly here because SF2 depends on the attendance data being encoded first. It helps the school review attendance patterns and prepare attendance-related reporting.

Attendance records are stored per date, enrollment, subject assignment, and status. This structure allows the same attendance data to be reused in teacher pages, student and parent portals, analytics, and SF2-style reports.

### Grading Sheet

Demo action: Click `Grading Sheet`. Select the section, subject, and quarter if prompted. Show assessment columns, score encoding, and the finalize/lock button.

Live demo action: Open a prepared grading sheet, edit one assessment score, save the score, then show the recalculated grade. If using a prepared finalization account, click finalize and show that the finalize button becomes disabled after locking.

Now I will open the Grading Sheet. Teachers can encode assessments such as quizzes, seatworks, assignments, performance tasks, and quarterly assessments. The grading sheet calculates grades and supports finalizing and locking. When grades are finalized, the button becomes unclickable to prevent accidental changes.

The system stores assessment definitions, scores, final grades, and lock states per student, subject, quarter, and class assignment.

The grading sheet is backed by assessment and final grade tables. Assessment columns define the activity, maximum score, and category, while score records store each student's result. Locking prevents further unintended changes after finalization.

### Advisory Board, Grade Release, And Grade Forms

Demo action: Click `Advisory Board`. Select the current quarter and show the `Release nth Quarter Grades` action. Briefly mention SF5, SF9, and SF10 only after showing grade release.

Live demo action: Open a prepared advisory section with finalized grades, click `Release nth Quarter Grades`, confirm the release, then explain that student and parent portals can now display those grades.

The adviser uses the Advisory Board to review class grades and release quarterly grades. Grades are not visible to students and parents until the adviser releases them per quarter.

This timing matters because the school should not generate official grade reports from unfinished or unreleased grades. After grades are released, the system can support grade-related forms such as SF5, SF9, and SF10. These exports use final grades, learner records, adviser information, school year, section data, and mapped templates.

The release step acts as a visibility gate. Student and parent portals only read released grade records. Permanent records and grade-related exports also depend on verified and released grade data, so draft grades do not become official outputs.

### Batch Promotion And Remedial

Demo action: Open `Batch Promotion` and show passed, conditional, and retained lists. Then open `Remedial Entry`, click `Select Student`, and show the failed subjects and remedial enrollment flow without spending too much time.

Live demo action: Select one prepared conditional student in Remedial Entry, open the failed subject list, and show the enroll button for one failed subject. Stop before payment unless a prepared remedial payment demo account is available.

The Batch Promotion page shows student promotion status such as passed, conditional, and retained. It is a monitoring page, not a manual override page.

Conditional students can proceed with remedial processing. The Remedial Entry workflow allows selecting conditional students, enrolling failed subjects for remedial payment, assigning remedial teachers, and recording remedial results after payment and teacher encoding. Promotion status comes from final grades and permanent records, while remedial enrollment, payment, and grade encoding are kept as separate records so registrar, cashier, and teacher responsibilities remain separated.

This separation is important in the backend design. Registrar actions create remedial enrollment records, finance actions handle payment records, and teacher actions encode remedial grades. Keeping these as separate modules prevents one role from controlling the entire process without the required checks.

After academic records are encoded and released, the final part of the operation is student and parent access, notifications, help, and closing. I will now pass the demo to Speaker 5.

## Speaker 5: Student And Parent Portals, Notifications, Help, And Closing

### Account Claiming

Demo action: Open an account claiming link or the claim account page. Show the password setup fields and expiration information.

Live demo action: Open a prepared unclaimed student or staff claim link, enter a valid password and confirmation, submit the form, then show that the account can now log in. If the demo account is already claimed, use the page only to explain the token and expiry behavior.

I will now demonstrate the account claiming concept. After enrollment, students and parents receive account claiming instructions. They open the claim link, verify the account, and set their own password.

The system generates claim tokens for the student and parent users. The token is checked before allowing password setup, and expiration is shown using Manila, Philippines time. If the student personal email was provided, the claim email is sent there. If not, the system uses the guardian contact email. Parent claim emails use the enrollment contact email.

The claim process uses token validation, password hashing, and account state updates. Once the account is claimed, the system records that a password has been set and future resend logic can treat the account differently from an unclaimed account.

### Student Portal

Demo action: Log in as a student. Open the dashboard, then briefly click schedule, attendance, grades, billing, and notifications.

Live demo action: Use the student account connected to the earlier attendance or grade release demo. Open attendance to show the saved attendance result, then open grades to show only released grades.

Now I will open the Student portal. Students can view their dashboard, schedule, attendance, released grades, billing information, announcements, and notifications. Grades only appear after the adviser releases them.

All student portal data is scoped to the logged-in student's account and linked student record, so one student cannot access another student's records.

This uses user-to-student relationship checks in the backend. Even if a student changes a URL parameter, the query scope still limits the data to the authenticated user's linked student record.

### Parent Portal

Demo action: Log in as a parent. Select or view the linked student, then briefly show schedule, attendance, billing, released grades, and notifications.

Live demo action: Use the parent account linked to the same student. Open attendance, billing, and released grades to show that the parent sees the student's records without accessing other students.

Now I will open the Parent portal. Parents can view linked student information such as schedule, attendance, billing, released grades, announcements, and notifications.

Parents are connected to students through parent-student relationships. The system checks this relationship before showing records, so a parent only sees their linked child or children.

This is also enforced server-side through relationship-based authorization. The portal does not trust the frontend alone to decide which student data a parent can view.

### Notifications

Demo action: Click the notification bell or open the notifications page. Show recent announcements or billing reminders.

Both students and parents receive notifications for announcements, billing reminders, and important school updates. Notifications are stored in the database and may also be sent through email. The system supports real-time updates through broadcasting so users do not always need to refresh the page to see new notifications.

Technically, notifications can be database notifications, email notifications, or broadcast events. This allows the system to show notifications inside the portal while also reaching users through their email.

### System Help

Demo action: Click `System Help`. Show that the page explains what each module does and how users should use it.

Now I will open System Help. The help page explains what each page does and how users should use the system. This reduces training burden because users can access guidance inside the system.

### Closing Summary

Demo action: Return to the main dashboard or final presentation slide before delivering the closing summary.

To summarize, MarriottConnect covers the full school workflow:

- Super Admin manages users, roles, settings, announcements, audit logs, and safeguards.
- Registrar handles enrollment, student directory, requirements, enrollment history, and permanent records.
- Finance handles assessment, cashier payments, ledgers, dues, discounts, transactions, and reports.
- Admin manages curriculum, teacher profiles, sections, schedules, and grade verification.
- Teachers handle attendance, grades, advisory release, remedial encoding, and historical records.
- Students and parents access schedules, attendance, released grades, billing, announcements, and notifications.

The system connects frontend workflows with backend validation, role-based access, database records, reporting, notifications, and auditability.

This completes our MarriottConnect system demonstration.

## Suggested Speaker Time Split

| Speaker | Area | Suggested Time |
| --- | --- | --- |
| Speaker 1 | Introduction, login, super admin, governance, safeguards | 5-7 minutes |
| Speaker 2 | Registrar, enrollment, student records, registrar import | 6-8 minutes |
| Speaker 3 | Finance, cashiering, ledgers, reports, finance import | 5-7 minutes |
| Speaker 4 | Admin academics, teacher workflow, grading | 7-9 minutes |
| Speaker 5 | Student/parent portals, notifications, help, closing | 5-7 minutes |

## Demo Preparation Checklist

- Use seeded demo accounts for each role.
- Prepare one enrolled student with complete requirements.
- Prepare one enrolled student with missing requirements.
- Prepare one student waiting for cashier payment.
- Prepare one student with billing dues and transaction history.
- Prepare one unique sample LRN, student personal email, and guardian contact email for live enrollment.
- Prepare one safe payment receipt or reference number for the cashier demo.
- Prepare one teacher account with assigned current-year schedule.
- Prepare one adviser account with grades ready for release.
- Prepare one teacher class where attendance and one assessment score can be safely edited.
- Prepare one conditional student for remedial workflow viewing.
- Prepare one parent and one student account with claimed portal access.
- Prepare one unclaimed account claim link if account claiming will be performed live.
- Prepare one small valid registrar or finance import template if file import will be demonstrated.
- Prepare sample exports before the defense if internet or file generation may be slow.
- Keep one browser tab per role if switching accounts takes too long.
