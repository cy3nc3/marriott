# MarriottConnect Institutional "How-To" Guide

This guide provides comprehensive, step-by-step instructions for every feature and module within the **MarriottConnect** system.

---

## 1. Super Admin: System Governance & Security

### How to Manage Staff Accounts
1.  Navigate to **User Manager**.
2.  Click **"New User"** to create accounts for Admin, Registrar, or Finance roles.
3.  **Account Claiming:** Upon creation, the system automatically sends an **Account Claim Invitation** to the staff member's personal email.
4.  Use the **"Toggle Status"** button to deactivate accounts or **"Reset Password"** to force a security reset.

### How to Broadcast Institutional Announcements
1.  Navigate to **Announcements**.
2.  Click **"Create Announcement"**.
3.  Choose the **Target Roles** (e.g., "All Students" or "Finance only").
4.  Set a **Publish Date** for scheduled broadcasts.
5.  Click **"Publish"**. Users will receive these in their portal inbox instantly.

### How to Configure School Metadata (Official Reports)
1.  Navigate to **System Settings**.
2.  Fill in the **Institutional Details** (School Name, Division, District, Region).
3.  Set the **School Principal** name (appears on all exported SF9 and SF10 forms).
4.  Upload the **School Logo** for custom branding on portal dashboards.
5.  Click **"Save Settings"**.

### How to Manage the Permission Matrix
1.  Navigate to **Permission Manager**.
2.  Select a role (e.g., "Registrar").
3.  Toggle specific features (e.g., "Delete Student") to grant or revoke access.
4.  Click **"Update Permissions"** to apply changes instantly.

### How to Monitor Audit Logs (Security Tracking)
1.  Navigate to **Audit Logs**.
2.  Use filters to search by **User**, **Module**, or **Date**.
3.  Click **"View Details"** to see the exact data changed (Old Value vs. New Value).

### How to Use "View As Role" (Admin Testing)
1.  As a Super Admin, click the role switcher in the top navigation.
2.  Select a role (e.g., "Parent") to temporarily see the system from their perspective.
3.  Click **"Exit View As"** to return to your dashboard.

### How to Perform a Database Restore
1.  Navigate to **System Settings > Backup Management**.
2.  Click the **"Preview"** (eye) icon to see record counts before restoring.
3.  Click **"Restore"** and confirm to overwrite the current database state.

---

## 2. Admin: Academic Controls & Quality Assurance

### How to Initialize a School Year
1.  Navigate to **Academic Controls > School Year**.
2.  Click **"Initialize Academic Year"**.
3.  Set Start/End dates and status (Ongoing/Upcoming).

### How to Manage the Curriculum & Faculty Eligibility
1.  Navigate to **Curriculum Manager**.
2.  Click **"New Subject"** to add a course.
3.  Click the **"Manage Qualified Teachers"** icon to certify faculty members for that specific subject.

### How to Build a Class Schedule
1.  Navigate to **Schedule Builder**.
2.  Select a Grade and Section.
3.  Click **"Add Schedule Entry"** and plot the day/time. The system will block double-booking.

### How to Verify and Release Quarterly Grades
1.  Navigate to **Grade Verification**.
2.  Set the **Submission Deadline** for teachers.
3.  Review submitted grade sheets.
4.  Click **"Verify & Release"** to send grades to portals, or **"Return for Revision"** with comments if errors are found.

### How to Manage Teacher Profiles
1.  Navigate to **Teacher Profiles**.
2.  Select a teacher to encode their **PRC License**, **Professional Units**, and **Major/Minor**.

---

## 3. Registrar: Student Records & Lifecycle

### How to Enroll a New or Returning Student
1.  Navigate to **Enrollment Intake**.
2.  Search by **12-digit LRN**. History pre-fills for returning students.
3.  Verify personal details and select **Payment Term**.
4.  Assign a **Section** and apply any applicable **Discounts/Scholarships**.
5.  Click **"Enroll"**. The student is now queued for Finance. **Note:** Account claim emails are sent once the Finance department confirms the enrollment via a transaction.

### How to Manually Send Account Claim Links
1.  Navigate to **Student Directory**.
2.  Locate the student and click **"Edit"**.
3.  If the account is unclaimed, you will see a **"Send Claim Invitation"** button.
4.  Confirm the email address and send. Both the **Student** and the **Linked Parent** will receive invitations to their respective personal emails.

### How to Enrich Records via SF1 (LIS)
1.  Navigate to **Student Directory**.
2.  Click **"Upload SF1"** and select the official DepEd Excel file.
3.  The system will match LRNs and update addresses, guardian names, and birthdates automatically.

### How to Manage Permanent Records (SF10)
1.  Navigate to **Permanent Records**.
2.  Select a student to view their 4-year academic history (Grades 7-10).
3.  Click **"Export to Excel (SF10)"** for an official transcript.

### How to Process Batch Promotions
1.  Navigate to **Batch Promotion** at the end of the year.
2.  Review Computed General Averages for the student body.
3.  Finalize the status: **Promoted**, **Conditional**, or **Retained**.

### How to Manage Remedial Cases
1.  Navigate to **Remedial Entry**.
2.  Enroll students who failed subjects into the remedial module.
3.  Record the **Remedial Mark** to resolve their "Conditional" status.

### How to Process a Student Departure (Transfer/Drop)
1.  Navigate to **Student Departures**.
2.  Click **"Record Departure"**.
3.  Select Type (Transferred Out / Dropped Out). This archives the record while keeping history.

---

## 4. Finance: Revenue & Inventory

### How to Manage Institutional Fees
1.  Navigate to **Fee Structure**.
2.  Set **Tuition** and **Miscellaneous** amounts for each Grade Level.
3.  These amounts are automatically applied to students during intake.

### How to Process a Multi-Item Transaction
1.  Navigate to **Cashier Panel**.
2.  Search by LRN.
3.  Add items: **Assessment Installments**, **Uniforms**, or **Books**.
4.  **Reserve OR Number** to secure the next receipt sequence.
5.  Post via **Cash/GCash/Bank**.
6.  **Account Provisioning:** Upon confirmation of an enrollment payment, the system automatically triggers the **Account Claim Emails** for the Student and Parent.

### How to Manage Product Inventory
1.  Navigate to **Inventory Items**.
2.  Create items (e.g., "PE Uniform") and set stock levels and prices.

### How to Configure Auto-Due Reminders
1.  Navigate to **Due Reminder Settings**.
2.  Set the **"Days Before Due"** trigger.
3.  Enable **"Auto-Send"** to notify parents via the portal and SMS.

### How to Close Daily Reports
1.  Navigate to **Daily Reports**.
2.  Review the mode mix (Cash vs Bank).
3.  Click **"Close Day"** to finalize the collection period.

---

## 5. Teacher: Instruction & Advisory

### How to Use the Grading Sheet
1.  Navigate to **Grading Sheet**.
2.  Set **Rubric Weights** (e.g., 30% Written, 50% Performance, 20% Exam).
3.  Add **Assessments** and input scores. The system computes grades in real-time.
4.  Click **"Submit to Admin"** for quarterly finalization.

### How to Manage Advisory Tasks (SF9)
1.  Navigate to **Advisory Board**.
2.  Encode **Conduct Ratings (Core Values)** for your section.
3.  Click **"Export SF9"** to generate individual student report cards.

### How to Take Daily Attendance (SF2)
1.  Navigate to **Attendance Tracking**.
2.  Select the date and log student status (Present/Absent/Tardy).
3.  The system aggregates this into the monthly **SF2 Export**.

---

## 6. Account Claiming & Portal Access

### For Staff (Teachers, Admins, Registrar, Finance)
1.  Check your personal email for the **"Institutional Account Invitation."**
2.  Click the secure claim link.
3.  Enter the **Firebase OTP** sent to your email/phone.
4.  Set a password to activate your account.

### For Students & Parents
1.  Once the first enrollment payment is made, check your personal email for the invitation.
2.  Click the claim link and verify your identity via **Firebase OTP**.
3.  Set your portal password.
4.  **Linked Access:** The Parent and Student share a personal email for verification, but have separate login credentials for their respective portals.

### How Students Monitor Progress
1.  **Dashboard:** Check upcoming class schedules.
2.  **Grades:** View verified quarterly ratings.
3.  **Inbox:** Read school announcements.

### How Parents Monitor Billing & Academics
1.  **Academic Monitoring:** View child's attendance and verified grades.
2.  **Billing:** View unpaid dues and payment timeline.
3.  **Downloads:** Click **"Download Receipt"** to save official PDF copies of previous payments.
