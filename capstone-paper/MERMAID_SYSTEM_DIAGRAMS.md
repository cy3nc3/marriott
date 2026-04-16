# MarriottConnect Core Design Diagrams

This file contains the source diagrams for the **Design of Software, System, Product, and/or Processes** section. The diagrams are written in Graphviz DOT. This replaced the earlier Mermaid/D2 versions because DOT gives cleaner document-style layouts and sharp orthogonal connectors.

The rendered files are stored in `capstone-paper/figure-sources/exported-diagrams/svg/` and `capstone-paper/figure-sources/exported-diagrams/png/`.

## Figure X. Context Flow Diagram of MarriottConnect

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.35, ranksep=0.55, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=11, margin="0.12,0.08"];
  edge [color="#334155", arrowsize=0.7, fontname="Arial", fontsize=9];

  personnel [label="School Personnel", fillcolor="#dbeafe"];
  super_admin [label="Super Admin"];
  admin [label="Admin"];
  registrar [label="Registrar"];
  finance [label="Finance Staff"];
  teacher [label="Teacher"];

  system [label="MarriottConnect\nSchool Management System", fillcolor="#dcfce7", fontsize=13];

  portal [label="Portal Users", fillcolor="#fef3c7"];
  student [label="Student"];
  parent [label="Parent"];

  personnel -> super_admin;
  personnel -> admin;
  personnel -> registrar;
  personnel -> finance;
  personnel -> teacher;

  super_admin -> system [label="accounts, permissions, settings"];
  admin -> system [label="academic setup, grade verification"];
  registrar -> system [label="student records, enrollment"];
  finance -> system [label="fees, payments, ledgers"];
  teacher -> system [label="attendance, grades"];

  system -> portal [label="dashboards and record access"];
  portal -> student;
  portal -> parent;
}
```

## Figure X. Data Flow Diagram of MarriottConnect

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.25, ranksep=0.35, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.7, fontname="Arial", fontsize=9];

  access [label="1. User Access Control", fillcolor="#dbeafe"];
  academic [label="2. Academic Setup", fillcolor="#dbeafe"];
  records [label="3. Student and Class Records", fillcolor="#dcfce7"];
  grading [label="4. Grade and Permanent Records", fillcolor="#dcfce7"];
  finance [label="5. Finance and Communication", fillcolor="#fef3c7"];
  output [label="6. Portal and Governance Output", fillcolor="#ede9fe"];

  access -> academic [label="authorized setup"];
  academic -> records [label="school year, subjects, sections, schedules"];
  records -> grading [label="enrollment, attendance, class lists"];
  grading -> finance [label="verified academic status"];
  finance -> output [label="billing, notices, reminders"];
  grading -> output [label="grades and permanent records"];
}
```

## Figure X. Entity Relationship Diagram of MarriottConnect - Academic and Learner Records

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.25, ranksep=0.45, compound=true, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=9, margin="0.08,0.05"];
  edge [color="#334155", arrowsize=0.55, penwidth=1.0];

  subgraph cluster_academic {
    label="Academic Structure";
    color="#93c5fd";
    style="rounded";
    academic_years [label="academic_years", fillcolor="#dbeafe"];
    grade_levels [label="grade_levels"];
    subjects [label="subjects"];
    sections [label="sections"];
    teacher_subjects [label="teacher_subjects"];
    subject_assignments [label="subject_assignments"];
    class_schedules [label="class_schedules"];
  }

  subgraph cluster_learner {
    label="Learner Records";
    color="#86efac";
    style="rounded";
    students [label="students", fillcolor="#dcfce7"];
    enrollments [label="enrollments"];
    attendances [label="attendances"];
    conduct_ratings [label="conduct_ratings"];
    permanent_records [label="permanent_records"];
    student_departures [label="student_departures"];
  }

  subgraph cluster_grading {
    label="Grading Records";
    color="#86efac";
    style="rounded";
    grading_rubrics [label="grading_rubrics"];
    graded_activities [label="graded_activities"];
    student_scores [label="student_scores"];
    final_grades [label="final_grades"];
    grade_submissions [label="grade_submissions"];
  }

  subgraph cluster_remedial {
    label="Remedial Records";
    color="#86efac";
    style="rounded";
    remedial_cases [label="remedial_cases"];
    remedial_records [label="remedial_records"];
    remedial_subject_fees [label="remedial_subject_fees"];
  }

  academic_years -> sections;
  grade_levels -> sections;
  grade_levels -> subjects;
  subjects -> teacher_subjects;
  sections -> subject_assignments;
  teacher_subjects -> subject_assignments;
  subject_assignments -> class_schedules;

  students -> enrollments;
  academic_years -> enrollments;
  grade_levels -> enrollments;
  sections -> enrollments;
  enrollments -> attendances;
  subject_assignments -> attendances;
  enrollments -> conduct_ratings;
  students -> permanent_records;
  students -> student_departures;

  subject_assignments -> graded_activities;
  graded_activities -> student_scores;
  students -> student_scores;
  enrollments -> final_grades;
  subject_assignments -> final_grades;
  subject_assignments -> grade_submissions;

  students -> remedial_cases;
  students -> remedial_records;
  subjects -> remedial_records;
  subjects -> remedial_subject_fees;
}
```

## Figure X. Entity Relationship Diagram of MarriottConnect - Finance Records

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.25, ranksep=0.45, compound=true, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=9, margin="0.08,0.05"];
  edge [color="#334155", arrowsize=0.55, penwidth=1.0];

  students [label="students", fillcolor="#dcfce7"];
  grade_levels [label="grade_levels", fillcolor="#dbeafe"];
  users [label="users", fillcolor="#dbeafe"];

  subgraph cluster_finance {
    label="Finance Records";
    color="#facc15";
    style="rounded";
    fees [label="fees", fillcolor="#fef3c7"];
    discounts [label="discounts"];
    student_discounts [label="student_discounts"];
    billing_schedules [label="billing_schedules"];
    transactions [label="transactions"];
    transaction_items [label="transaction_items"];
    ledger_entries [label="ledger_entries"];
    inventory_items [label="inventory_items"];
    transaction_due_allocations [label="transaction_due_allocations"];
    or_number_sequences [label="or_number_sequences"];
    or_number_reservations [label="or_number_reservations"];
    finance_due_reminder_rules [label="finance_due_reminder_rules"];
    finance_due_reminder_dispatches [label="finance_due_reminder_dispatches"];
  }

  grade_levels -> fees;
  students -> student_discounts;
  discounts -> student_discounts;
  students -> billing_schedules;
  students -> transactions;
  users -> transactions;
  transactions -> transaction_items;
  fees -> transaction_items;
  inventory_items -> transaction_items;
  students -> ledger_entries;
  transactions -> transaction_due_allocations;
  billing_schedules -> transaction_due_allocations;
  transactions -> or_number_reservations;
  users -> or_number_reservations;
  finance_due_reminder_rules -> finance_due_reminder_dispatches;
  billing_schedules -> finance_due_reminder_dispatches;
}
```

## Figure X. Entity Relationship Diagram of MarriottConnect - Access and Communication Records

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.25, ranksep=0.45, compound=true, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=9, margin="0.08,0.05"];
  edge [color="#334155", arrowsize=0.55, penwidth=1.0];

  subgraph cluster_access {
    label="User Access and Governance";
    color="#93c5fd";
    style="rounded";
    users [label="users", fillcolor="#dbeafe"];
    permissions [label="permissions"];
    parent_student [label="parent_student"];
    saved_account_logins [label="saved_account_logins"];
    audit_logs [label="audit_logs"];
    settings [label="settings"];
  }

  subgraph cluster_communication {
    label="Announcements and Notifications";
    color="#c4b5fd";
    style="rounded";
    announcements [label="announcements", fillcolor="#ede9fe"];
    announcement_attachments [label="announcement_attachments"];
    announcement_reads [label="announcement_reads"];
    announcement_recipients [label="announcement_recipients"];
    announcement_event_responses [label="announcement_event_responses"];
    announcement_reminder_dispatches [label="announcement_reminder_dispatches"];
  }

  finance_due_reminder_dispatches [label="finance_due_reminder_dispatches", fillcolor="#fef3c7"];

  users -> permissions;
  users -> parent_student;
  users -> saved_account_logins;
  users -> audit_logs;
  users -> announcements;
  announcements -> announcement_attachments;
  announcements -> announcement_reads;
  announcements -> announcement_recipients;
  announcements -> announcement_event_responses;
  announcements -> announcement_reminder_dispatches;
  announcements -> finance_due_reminder_dispatches;
}
```

## Figure X. HIPO Chart of Super Admin Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Super Admin Account", fillcolor="#dbeafe"];
  governance [label="System Governance", fillcolor="#dcfce7"];
  communication [label="Communication", fillcolor="#dcfce7"];
  users [label="User Manager"];
  permissions [label="Permissions"];
  audit [label="Audit Logs"];
  settings [label="System Settings"];
  announcements [label="Announcements"];
  reports [label="Announcement Report"];

  root -> governance -> users -> permissions -> audit -> settings -> communication -> announcements -> reports;
}
```

## Figure X. HIPO Chart of Admin Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Admin Account", fillcolor="#dbeafe"];
  setup [label="Academic Setup", fillcolor="#dcfce7"];
  operations [label="Academic Operations", fillcolor="#dcfce7"];
  controls [label="Academic Controls"];
  curriculum [label="Curriculum Manager"];
  sections [label="Section Manager"];
  schedules [label="Schedule Builder"];
  class_lists [label="Class Lists"];
  verification [label="Grade Verification"];

  root -> setup -> controls -> curriculum -> sections -> operations -> schedules -> class_lists -> verification;
}
```

## Figure X. HIPO Chart of Registrar Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Registrar Account", fillcolor="#dbeafe"];
  records [label="Learner Records", fillcolor="#dcfce7"];
  enrollment [label="Enrollment and Status", fillcolor="#dcfce7"];
  history [label="Academic History", fillcolor="#dcfce7"];
  directory [label="Student Directory"];
  sf1 [label="SF1 Upload"];
  import [label="Data Import"];
  queue [label="Enrollment Queue"];
  promotion [label="Batch Promotion"];
  departure [label="Student Departure"];
  permanent [label="Permanent Records"];
  remedial [label="Remedial Entry"];

  root -> records -> directory -> sf1 -> import -> enrollment -> queue -> promotion -> departure -> history -> permanent -> remedial;
}
```

## Figure X. HIPO Chart of Finance Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Finance Account", fillcolor="#dbeafe"];
  collection [label="Collection Management", fillcolor="#dcfce7"];
  setup [label="Billing Setup", fillcolor="#dcfce7"];
  reminder [label="Reminder Management", fillcolor="#dcfce7"];
  cashier [label="Cashier Panel"];
  ledgers [label="Student Ledgers"];
  transactions [label="Transaction History"];
  reports [label="Daily Reports"];
  fees [label="Fee Structure"];
  discounts [label="Discount Manager"];
  inventory [label="Product Inventory"];
  import [label="Data Import"];
  due_settings [label="Due Reminder Settings"];

  root -> collection -> cashier -> ledgers -> transactions -> reports -> setup -> fees -> discounts -> inventory -> import -> reminder -> due_settings;
}
```

## Figure X. HIPO Chart of Teacher Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Teacher Account", fillcolor="#dbeafe"];
  class_work [label="Class Work", fillcolor="#dcfce7"];
  grading [label="Grading and Advisory", fillcolor="#dcfce7"];
  schedule [label="Schedule"];
  attendance [label="Attendance"];
  sf2 [label="SF2 Export"];
  sheet [label="Grading Sheet"];
  advisory [label="Advisory Board"];

  root -> class_work -> schedule -> attendance -> sf2 -> grading -> sheet -> advisory;
}
```

## Figure X. HIPO Chart of Student Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Student Account", fillcolor="#dbeafe"];
  portal [label="Student Portal", fillcolor="#dcfce7"];
  dashboard [label="Dashboard"];
  schedule [label="Schedule"];
  grades [label="Grades"];

  root -> portal -> dashboard -> schedule -> grades;
}
```

## Figure X. HIPO Chart of Parent Account

```dot
digraph G {
  graph [rankdir=TB, splines=ortho, nodesep=0.3, ranksep=0.5, bgcolor="white", pad=0.25];
  node [shape=box, style="filled", fillcolor="#f8fafc", color="#334155", fontname="Arial", fontsize=10, margin="0.10,0.07"];
  edge [color="#334155", arrowsize=0.65];

  root [label="Parent Account", fillcolor="#dbeafe"];
  portal [label="Parent Portal", fillcolor="#dcfce7"];
  access [label="Access Control", fillcolor="#dcfce7"];
  dashboard [label="Dashboard"];
  schedule [label="Schedule"];
  grades [label="Grades"];
  billing [label="Billing Information"];
  disabled [label="Portal Disabled Page"];

  root -> portal -> dashboard -> schedule -> grades -> billing -> access -> disabled;
}
```
