# MarriottConnect User Guide

Welcome to the official user guide for **MarriottConnect**, a comprehensive, role-based school operations system designed for academic, financial, and administrative excellence.

---

## 1. Super Admin (System Governance)
The Super Admin is responsible for the overall health, security, and configuration of the system.

### Key Workflows:
*   **User Management:** Create and manage high-level accounts for Admins, Registrars, and Finance Officers. Toggle account status and reset passwords.
*   **Role & Permissions:** Manage the fine-grained permission matrix to control access to specific modules and features for every role in the institution.
*   **System Settings:** Configure institutional metadata such as School Name, Division, District, and Principal name used in official DepEd exports.
*   **Announcement Management:** Create and publish system-wide announcements to all users or specific roles (e.g., "Teachers only").
*   **Audit Logging:** Monitor system activity through detailed audit logs to track changes made by staff members.
*   **Backups & Recovery:** Review system backup status and perform database restorations if necessary.

---

## 2. Admin (Academic Controls)
The Admin manages the academic structure and ensures compliance with institutional standards.

### Key Workflows:
*   **School Year Management:** Initialize upcoming academic years and set official start and end dates.
*   **Curriculum Manager:** Define the subjects offered for each grade level, assign unique subject codes, and certify qualified teachers from the faculty pool.
*   **Section Manager:** Create class sections (e.g., "St. Paul") and assign Class Advisers. View historical data to see past advisers for each section.
*   **Schedule Builder:** Build weekly class schedules by plotting subject assignments into time slots for each section.
*   **Grade Verification:** Set submission deadlines for teachers. Review, verify, and release quarterly grades to student and parent portals. Return grades for revision if discrepancies are found.
*   **Teacher Profiling:** Manage detailed faculty profiles, including LET passer status, PRC license numbers, and professional qualifications.

---

## 3. Registrar (Student Records & Intake)
The Registrar oversees the entire student lifecycle, from initial enrollment to graduation or transfer.

### Key Workflows:
*   **Enrollment Intake:** 
    *   Perform **LRN Lookups** to identify returning students and pre-fill their historical data.
    *   Enroll new learners by encoding personal details and selecting payment terms.
    *   Assign students to sections and tag them with scholarships or discounts.
*   **Student Directory:** Maintain the "Master List" of all students. Update personal information and contact details.
*   **SF1 (School Register) Enrichment:** Upload and process SF1 data to regularize enrollments and ensure LRN consistency with DepEd records.
*   **Permanent Records (SF10):** Export official transcript records (SF10) containing deep historical grades and general averages.
*   **Batch Promotion:** At the end of the school year, process the entire student body into "Promoted," "Conditional," or "Retained" status based on verified grades.
*   **Remedial Management:** Enroll conditional students into remedial classes, record their final ratings, and resolve their status for the next academic year.
*   **Student Departures:** Officially process students who are transferring out or dropping out while maintaining their historical data.

---

## 4. Finance & Cashiering (Financial Operations)
The Finance department manages the institution's revenue cycle, fee structures, and collections.

### Key Workflows:
*   **Fee Structure:** Define tuition, miscellaneous, and other fees for every grade level and academic year.
*   **Cashier Panel:** 
    *   Search students by LRN to open their financial profile.
    *   **Reserve OR Numbers** to prevent sequence collisions during high-volume periods.
    *   Post payments (Downpayments or Installments) via Cash, GCash, or Bank Transfer.
*   **Student Ledgers:** View granular debit/credit histories for every student. Generate real-time balances and aging reports for outstanding dues.
*   **Billing Schedules:** Automatically generate monthly payment timelines for students based on their total assessment.
*   **Daily Reports:** Review and close daily collection reports categorized by payment mode and fee type.
*   **Due Reminders:** Configure and send automated SMS or Email reminders to parents with upcoming or overdue balances.
*   **Inventory Management:** Track and sell institutional items (uniforms, books) through the cashiering system.

---

## 5. Teacher (Instruction & Assessment)
Teachers manage their classroom environment and provide the core academic data for the system.

### Key Workflows:
*   **Class Management:** View assigned teaching schedules and class lists for each subject.
*   **Attendance Tracking:** Record daily student attendance and export **SF2 (Daily Attendance)** reports.
*   **Grading Sheets:** 
    *   Configure grading rubrics (Written Works, Performance Tasks, Quarterly Exams).
    *   Input raw scores for assessments; the system automatically computes quarterly and final grades.
    *   Submit finalized grades to the Admin for verification.
*   **Advisory Board:** Class Advisers manage their specific sections, encoding Conduct Ratings (Values) and preparing **SF9 (Report Cards)** for distribution.
*   **Remedial Encoding:** Encode grades for students undergoing remedial instructions for failed subjects.

---

## 6. Parent (Portal Access)
Parents use the portal to stay informed about their child's academic and financial standing.

### Key Workflows:
*   **Academic Monitoring:** View their child’s verified quarterly grades and general averages in real-time.
*   **Attendance Alerts:** Check their child's daily attendance status (Present, Absent, or Tardy).
*   **Schedule View:** Access their child's weekly class schedule.
*   **Billing & Payments:** View the total outstanding balance, upcoming due dates, and a complete history of payments made to the cashier.
*   **Announcements:** Receive important school updates and due reminders through the portal inbox.

---

## 7. Student (Portal Access)
Students use the portal to manage their own academic progress and stay updated with school requirements.

### Key Workflows:
*   **Grades & Standing:** View released grades for the current and past quarters.
*   **Class Schedule:** Access their personalized weekly schedule and teacher assignments.
*   **Attendance History:** Review their own attendance records.
*   **Notifications:** Stay updated with school announcements and institutional events.
*   **Account Security:** Manage their own profile and update their password for security.
