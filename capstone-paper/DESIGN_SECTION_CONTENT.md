# Design of Software, System, Product, and/or Processes

This file contains the revised content for the paper section titled **Design of Software, System, Product, and/or Processes**. It follows the structure used in `reference-paper.pdf`: a context flow diagram, data flow diagram, entity relationship diagram, and HIPO charts for the system accounts.

Module screenshots, Excel templates, and page captures should not be treated as separate system design diagrams. They should be placed in the requirements documentation or figure/screenshot sections instead.

## Scope Notes

Do not describe the following as completed system features unless they are implemented later:

| Area | Current Status | How to Handle in the Paper |
|---|---|---|
| DepEd Reports page | Placeholder page only | Do not include as a completed module. Mention only as future enhancement if needed. |
| SF9 Generator | Placeholder page only | Do not include as a completed module. Mention only as future enhancement if needed. |
| Complete DepEd report generation suite | Not fully implemented | Avoid claims such as "the system generates all DepEd reports." |
| Automatic DepEd/LIS submission | Not implemented | Do not claim online submission to DepEd systems. |
| DepEd grade transmutation table | Not confirmed in the implementation | Describe only the implemented weighted grade computation. |
| Full SF5 user-facing workflow | Template adapter/sample export support exists, but no confirmed production page | Mention only as template support or future report workflow. |

## Recommended Design Section Flow

Use this order in the paper:

1. MarriottConnect Context Flow Diagram
2. MarriottConnect Data Flow Diagram
3. MarriottConnect Entity Relationship Diagram - Academic and Learner Records
4. MarriottConnect Entity Relationship Diagram - Finance Records
5. MarriottConnect Entity Relationship Diagram - Access and Communication Records
6. HIPO Chart of Super Admin Account
7. HIPO Chart of Admin Account
8. HIPO Chart of Registrar Account
9. HIPO Chart of Finance Account
10. HIPO Chart of Teacher Account
11. HIPO Chart of Student Account
12. HIPO Chart of Parent Account
13. Grade and Promotion Computation Formulas
14. Notes for School Form Template Figures

---

## 1. MarriottConnect Context Flow Diagram

**Figure X. Context Flow Diagram of MarriottConnect**

Figure X illustrates the flow of information between MarriottConnect and its users. The system is accessed by seven main account types: Super Admin, Admin, Registrar, Finance Staff, Teacher, Student, and Parent. Each user group interacts with the system based on its assigned responsibility.

The Super Admin manages system-wide functions such as user accounts, permissions, announcements, audit logs, and system settings. The Admin manages academic setup, curriculum, sections, schedules, class lists, and grade verification. The Registrar handles student records, SF1-based learner record checking, enrollment, permanent records, promotion, remedial entries, and student departure records. The Finance Staff manages fees, discounts, cashier transactions, ledgers, daily reports, and due reminder settings. Teachers manage attendance, grading sheets, schedules, and advisory records. Students and parents access limited portal information such as schedules, grades, and billing information where applicable.

The context flow diagram should show MarriottConnect as the central system and the user roles as external entities. The data exchanged should focus on the major responsibilities of each user rather than every individual screen.

---

## 2. MarriottConnect Data Flow Diagram

**Figure X. Data Flow Diagram of MarriottConnect**

Figure X illustrates how data moves through the major processes of MarriottConnect. The flow begins with user access and role-based permissions, which determine the modules available to each account. Academic setup data, including school years, grade levels, subjects, sections, schedules, and teacher-subject assignments, becomes the basis for enrollment, class lists, attendance, grading, and student schedules.

Enrollment connects students to a school year, grade level, section, and payment term. Once a learner is enrolled, the record is used by the academic modules, finance modules, student portal, and parent portal. Teachers encode attendance and grades based on their assigned classes. Grade records move from teacher encoding to admin verification, then become part of permanent records and batch promotion. Finance records are connected to enrollment through fees, discounts, billing schedules, transactions, student ledgers, daily reports, and due reminders. Announcements and notifications distribute school updates to selected users.

The DFD should show the major processes and data movement, not every database table. Its purpose is to explain how records are created, processed, verified, and used by different modules.

---

## 3. MarriottConnect Entity Relationship Diagram

**Figure X. Entity Relationship Diagram of MarriottConnect - Academic and Learner Records**

Figure X presents the Entity Relationship Diagram of MarriottConnect. The ERD shows how the system stores and connects data for user access, academic setup, learner records, grading, attendance, finance, announcements, settings, and audit monitoring.

For the paper, use ERD continuation figures instead of forcing the complete database into one image. A complete technical ERD with every foreign key becomes too wide and unreadable when inserted into a DOCX page. The ERD should still show the implemented domain entities, but it should be split into readable parts.

The first ERD figure should cover academic and learner records. It should include academic years, grade levels, subjects, sections, teacher subjects, subject assignments, class schedules, students, enrollments, attendance, conduct ratings, permanent records, student departures, grading rubrics, graded activities, student scores, final grades, grade submissions, remedial cases, remedial records, and remedial subject fees.

**Figure X. Entity Relationship Diagram of MarriottConnect - Finance Records**

The second ERD figure should cover finance records. It should include fees, discounts, student discounts, billing schedules, transactions, transaction items, ledger entries, transaction due allocations, inventory items, OR number sequences, OR number reservations, finance due reminder rules, and finance due reminder dispatches. It should also show the connection of these records to students, grade levels, and users.

**Figure X. Entity Relationship Diagram of MarriottConnect - Access and Communication Records**

The third ERD figure should cover access and communication records. It should include users, permissions, parent-student links, saved account logins, audit logs, settings, announcements, announcement attachments, announcement reads, announcement recipients, announcement event responses, announcement reminder dispatches, and the connection to finance due reminder dispatches.

The ERD should exclude framework-only support tables such as cache, jobs, failed jobs, and password reset tokens unless the adviser asks for a fully technical database diagram. These tables support the framework but are not part of the school-management domain.

---

## 4. HIPO Chart of Super Admin Account

**Figure X. HIPO Chart of Super Admin Account**

Figure X presents the Super Admin account structure. This account controls system governance and administrative setup. The Super Admin can manage user accounts, assign roles and permissions, publish announcements, view announcement reports, review audit logs, and configure system settings such as school identity, maintenance mode, and backup-related controls.

The HIPO chart should show Super Admin as the parent function. The child functions should include User Manager, Permissions, Announcements, Announcement Report, Audit Logs, and System Settings.

---

## 5. HIPO Chart of Admin Account

**Figure X. HIPO Chart of Admin Account**

Figure X presents the Admin account structure. The Admin account focuses on academic management. This includes managing the school year, grade levels, curriculum, sections, class schedules, class lists, and grade verification. These modules provide the academic structure used by teachers, students, parents, and the registrar.

The HIPO chart should show Admin as the parent function. The child functions should include Academic Controls, Curriculum Manager, Section Manager, Schedule Builder, Class Lists, and Grade Verification.

Do not include DepEd Reports or SF9 Generator in this chart as completed modules because both pages are placeholders in the current system.

---

## 6. HIPO Chart of Registrar Account

**Figure X. HIPO Chart of Registrar Account**

Figure X presents the Registrar account structure. The Registrar account focuses on student record management and academic record continuity. It supports the Student Directory, SF1 Upload, Enrollment Queue, Permanent Records, Registrar Data Import, Batch Promotion, Remedial Entry, and Student Departure modules.

The Registrar modules help the school manage learner records from enrollment to promotion, remedial review, transfer, departure, or completion. The SF1 upload is used for learner record checking through LRN matching, while the enrollment export is used to generate a structured enrollment workbook.

---

## 7. HIPO Chart of Finance Account

**Figure X. HIPO Chart of Finance Account**

Figure X presents the Finance account structure. The Finance account focuses on fees, payments, discounts, ledgers, and reports. It supports the Cashier Panel, Student Ledgers, Transaction History, Finance Data Import, Product Inventory, Discount Manager, Fee Structure, Daily Reports, and Due Reminder Settings.

The Finance modules are connected to enrollment because billing schedules, balances, discounts, and payment records depend on the learner's academic year, grade level, payment term, and recorded transactions.

---

## 8. HIPO Chart of Teacher Account

**Figure X. HIPO Chart of Teacher Account**

Figure X presents the Teacher account structure. The Teacher account supports class-level academic work through the Teacher Schedule, Attendance, SF2 Export, Grading Sheet, and Advisory Board modules. Teachers use these modules to view assigned classes, encode attendance, export SF2 attendance records, create graded activities, encode scores, submit grades, and monitor advisory class records.

The Teacher HIPO chart should show how teacher functions depend on subject assignments, class schedules, enrolled students, attendance records, graded activities, and grade submissions.

---

## 9. HIPO Chart of Student Account

**Figure X. HIPO Chart of Student Account**

Figure X presents the Student account structure. The Student account has limited portal access and allows learners to view their dashboard, class schedule, and available grade records. The student portal does not provide administrative functions.

The HIPO chart should show Student as the parent function with Dashboard, Schedule, and Grades as the child functions.

---

## 10. HIPO Chart of Parent Account

**Figure X. HIPO Chart of Parent Account**

Figure X presents the Parent account structure. The Parent account provides controlled access to learner-related information. Parents can view the parent dashboard, learner schedule, grades, and billing information when the parent portal is available. If access is disabled, the system displays the parent portal disabled page.

The HIPO chart should show Parent as the parent function with Dashboard, Schedule, Grades, Billing Information, and Portal Disabled Page as the child functions.

---

## 11. Grade and Promotion Computation Formulas

The reference paper includes computation formulas after the account HIPO charts because payroll computation is a core part of that system. For MarriottConnect, the equivalent computation content is the grade computation and learner promotion computation. These should be written as formulas or tables, not separate workflow diagrams, unless the adviser specifically requests additional process diagrams.

### Grade Computation

```text
Component Percentage = Learner's Total Score / Total Possible Score x 100

Component Weighted Score = Component Percentage x Component Weight

Quarter Grade = Written Works Weighted Score + Performance Tasks Weighted Score + Quarterly Assessment Weighted Score
```

The teacher grading sheet uses written works, performance tasks, and quarterly assessment components. Each graded activity has a maximum score. The system computes the learner's component percentage, applies the configured component weight, and adds the weighted scores to produce the quarter grade.

Do not claim that the system uses a DepEd transmutation table unless that feature is implemented later.

### Promotion Computation

```text
Annual Subject Grade = (Q1 Grade + Q2 Grade + Q3 Grade + Q4 Grade) / 4

General Average = Sum of Annual Subject Grades / Number of Subjects
```

Promotion classification:

```text
0 failed subjects = Promoted
1 to 2 failed subjects = Conditional
More than 2 failed subjects = Retained
Terminal grade level with completed requirements = Completed
```

The promotion computation should be described as dependent on complete and locked grades. If grades are missing or not locked, the learner is held for review before promotion processing continues.

---

## 12. Notes for School Form Template Figures

School form templates should be handled as screenshots or template figures, not as design workflow diagrams. This follows the reference paper's pattern, where module pages are presented as screenshots while the design section uses system diagrams.

Recommended template figures:

| Figure Placeholder | Source |
|---|---|
| Figure X. SF1 Excel Template | `templates/SF1_2025.xls` |
| Figure X. SF2 Excel Template | `templates/SF2_2025.xls` |
| Figure X. Enrollment Excel Template | `templates/_SY 26-27 Enrolment.xlsx` |
| Figure X. SF5 Excel Template | `templates/SF5.xlsx`, only if discussing template support |

Recommended system screenshots:

| Figure Placeholder | Page |
|---|---|
| Figure X. SF1 Upload Function | `/registrar/student-directory` |
| Figure X. SF2 Export Function | `/teacher/attendance` |
| Figure X. Enrollment Workbook Export Function | `/registrar/enrollment` |
| Figure X. Grading Sheet Module | `/teacher/grading-sheet` |
| Figure X. Grade Verification Module | `/admin/grade-verification` |
| Figure X. Batch Promotion Module | `/registrar/batch-promotion` |

These screenshots may be placed in the requirements documentation, implementation section, appendix, or wherever the paper presents module-level figures.

---

## Final Diagram Checklist

Use only these generated diagrams for the main design section:

| Diagram | Keep in Design Section |
|---|---|
| Context Flow Diagram | Yes |
| Data Flow Diagram | Yes |
| Entity Relationship Diagram - Academic and Learner Records | Yes |
| Entity Relationship Diagram - Finance Records | Yes |
| Entity Relationship Diagram - Access and Communication Records | Yes |
| HIPO Chart of Super Admin Account | Yes |
| HIPO Chart of Admin Account | Yes |
| HIPO Chart of Registrar Account | Yes |
| HIPO Chart of Finance Account | Yes |
| HIPO Chart of Teacher Account | Yes |
| HIPO Chart of Student Account | Yes |
| HIPO Chart of Parent Account | Yes |

Do not include the extra workflow diagrams in the main design section unless requested by the adviser. They create unnecessary length and overlap with the DFD, ERD, HIPO charts, screenshots, and written explanations.
