# Super Admin, Admin, Enrollment, Teacher, Student, Parent, and Year-End Flowchart

This file shows the super admin governance flow, the admin academic-controls setup, the enrollment workflow, the teacher workflow, the student and parent portal workflows, and the year-end progression flow in one Mermaid chart.

Use a Markdown preview that supports Mermaid, or open it in Mermaid Live Editor.

```mermaid
---
config:
  layout: elk
---
flowchart TB
 subgraph SuperAdmin["Super Admin Governance"]
    direction TB
        SA1["Super admin opens governance controls"]
        SA2["Create and manage admin, registrar, finance, and teacher accounts"]
        SA3["Review permissions and role access"]
        SA4["Monitor audit logs and system health"]
        SA5["Review backups and recovery readiness"]
  end
 subgraph Admin["Admin Academic Controls"]
    direction TB
        A2["Set school year start and end dates"]
        A3["Curriculum Manager"]
        A4["Create subjects"]
        A5["Assign qualified teachers to subjects"]
        A6["Section Manager"]
        A7["Create sections"]
        A8["Assign advisers"]
        A9["Schedule Builder"]
        A10["Select grade level and section"]
        A11["Plot weekly schedule"]
        A15["Publish academic setup for the school year"]
  end
 subgraph Registrar["Registrar Enrollment Intake"]
    direction TB
        C1["Encode LRN, first name, last name, sex, birthdate, emergency contact"]
        D["Registrar opens enrollment intake"]
        C2["Select payment plan"]
        C3["Enter downpayment if applicable"]
        C4["Create student account"]
        C5["Create parent account and link"]
        C6["Create enrollment record"]
        C7["Set status to pending_intake"]
        C8["Optional section assignment"]
        C9["Optional discount or scholarship tagging"]
        C10["Compute total assessment fee"]
        C11["Apply discount or scholarship if tagged"]
        C12["Subtract stated downpayment"]
        C13["Compute dues from remaining balance"]
        C14["Maintain permanent and historical records"]
        C15["Process student departure"]
        C16{"Departure type"}
        C17["Mark transferred_out and keep read-only history"]
        C18["Mark dropped_out and keep read-only history"]
  end
 subgraph Finance["Cashier Processing"]
    direction TB
        F1["Finance opens billing setup"]
        F2["Set fee structure by grade level"]
        F3["Set product prices"]
        F4["Configure due reminder settings"]
        F5["Publish finance setup for cashiering"]
        F7["Send automated due reminders"]
        D1["Open learner finance record"]
        F0["Cashier searches learner by LRN"]
        D2["Create transaction"]
        D3["Post enrollment payment"]
        D4["Create ledger entries"]
        D5["Settle stated downpayment first"]
        D8["Update student ledger balances"]
        D9["Recompute due balances and allocations"]
        D6["Update enrollment finance state"]
        D7["Enrollment waits for official roster confirmation"]
        D10["Include transaction in daily reports"]
        F6["Cashier searches active student by LRN"]
        D11["Open active student ledger and due list"]
        D12["Create midyear transaction"]
        D13{"Transaction type"}
        D14["Post assessment fee payment"]
        D15["Allocate payment to oldest unpaid due"]
        D16["Post custom item without due allocation"]
        D17["Post product sale"]
        D18["Update ledger balances and daily reports"]
        D19["Record transaction in history"]
        D20["Void or reverse transaction if needed"]
        D21["Recompute ledger and due balances after correction"]
        D22["Review and close daily reports"]
  end
 subgraph LIS["SF1 Enrichment"]
    direction TB
        E1["System reads school year from SF1"]
        E2["Match learner by LRN"]
        E3{"Mismatch found?"}
        E31["Registrar reviews and corrects intake mismatches"]
        E4["Finalize student directory record"]
        E5["Enrollment becomes fully regularized for the school year"]
  end
 subgraph Teacher["Teacher Academic Workflow"]
    direction TB
        T2["View teaching schedule"]
        T3["Open grading sheet"]
        T4["Configure rubrics"]
        T5["Add assessments"]
        T6["Record student scores"]
        T7["System computes grades"]
        T8["Review class standing"]
        T9["Finalize grade rows by class"]
        T10["Open advisory board"]
        T11["View advisory grades read-only"]
        T12["Encode conduct and values"]
        T13["Adviser uploads SF1"]
        T14["Distribute advisory grades"]
  end
 subgraph Verification["Admin Grade Verification"]
    direction TB
        V0["Admin opens Grade Verification"]
        V01["Set or edit submission deadline"]
        V1["Admin receives grades for verification"]
        V2["Review submitted grades for current quarter"]
        V3{"Decision"}
        V4["Release verified grades to portals"]
        V5["Return grades to teacher for revision"]
  end
 subgraph YearEnd["End of School Year"]
    direction TB
        Y1["Admin closes school year"]
        Y2["System checks verified final grades"]
        Y3{"Student classification"}
        Y4["Promoted students"]
        Y5["Conditional students"]
        Y6["Failed or retained students"]
        Y9["Completed terminal students"]
        Y10["Registrar reviews unresolved conditional cases"]
        Y7["Create next school year enrollment"]
        Y8["Create same-grade next school year enrollment"]
        Y11["Assign next school year sections"]
        Y12["Archive completed school year records"]
        Y13["Reuse archived accounts when learners re-enroll"]
  end
 subgraph Remedial["Remedial Process"]
    direction TB
        R1["Registrar opens remedial enrollment"]
        R2["Load failed subjects"]
        R3["Enroll student in remedial classes"]
        R4["Record remedial class mark and final rating"]
        R5{"Passed remedial?"}
        R6["Update record and clear conditional status"]
        R7["Hold for review or retain student"]
  end
 subgraph Student["Student Portal"]
    direction TB
        S1["Student opens portal"]
        S2["View dashboard"]
        S3["View schedule"]
        S4["View verified grades"]
        S5["View notifications and inbox"]
  end
 subgraph Parent["Parent Portal"]
    direction TB
        P1["Parent opens portal"]
        P2["View dashboard"]
        P3["View child schedule"]
        P4["View child verified grades"]
        P5["View billing and unpaid dues"]
        P6["View due timeline and payment history"]
        P7["View notifications and inbox"]
  end
    A(["Upcoming school year preparation starts"]) --> SA1
    SA1 --> SA2 & SA3 & SA4 & SA5
    SA2 --> A1["Admin initializes school year"]
    A1 --> A2 & A3 & A6 & A9 & F1
    A3 --> A4
    A4 --> A5
    A6 --> A7
    A7 --> A8
    A9 --> A10
    A10 --> A11
    F1 --> F2 & F3 & F4
    F2 --> F5
    F3 --> F5
    F4 --> F5
    F4 --> F7
    A5 --> A15
    A8 --> A15
    A11 --> A15
    A15 --> B["Enrollment season starts"] & T1["Teacher receives assigned class and schedule"]
    F5 --> B
    B --> C["Parent or enrollee submits requirements"]
    C --> D
    D --> C1
    C1 --> C2
    C2 --> C3
    C3 --> C4
    C4 --> C5
    C5 --> C6
    C6 --> C7 & C8 & C9 & C10
    C10 --> C11
    C11 --> C12
    C12 --> C13
    E5 --> C14
    C14 --> C15
    C15 --> C16
    C16 -- Transfer out --> C17
    C16 -- Dropped out --> C18
    C7 --> F0
    C13 --> F0
    F0 --> D1
    D1 --> D2
    D2 --> D3
    D3 --> D4
    D4 --> D5
    D5 --> D8
    D8 --> D9
    D9 --> D6
    D6 --> D10
    D10 --> D7
    D7 --> E0["Queued for SF1 enrichment"]
    E1 --> E2
    E2 --> E3
    E3 -- Yes --> E31
    E3 -- No --> E4
    E31 --> E4
    E4 --> E5
    E5 --> F6
    F6 --> D11
    D11 --> D12
    D12 --> D13
    D13 -- Assessment fee --> D14
    D13 -- Custom item --> D16
    D13 -- Product sale --> D17
    D14 --> D15
    D15 --> D18
    D16 --> D18
    D17 --> D18
    D18 --> D19
    D19 --> D22
    D19 --> D20
    D20 --> D21
    C4 --> S1
    C5 --> P1
    S1 --> S2 & S3 & S5
    P1 --> P2 & P3 & P7
    A15 --> S3 & P3
    D6 --> P5
    D18 --> P5
    D18 --> P6
    F7 --> P7
    T1 --> T2 & T3 & T10
    T3 --> T4 & T5
    T5 --> T6
    T6 --> T7
    T7 --> T8
    T8 --> T9
    T10 --> T11 & T12 & T13
    T13 --> E1
    V4 --> T14
    T9 --> V1
    V0 --> V01
    V01 --> V1
    V1 --> V2
    V2 --> V3
    V3 -- Verified --> V4
    V3 -- Returned --> V5
    V5 --> T3
    V4 --> S2 & S4 & P2 & P4 & S5 & P7
    V4 --> Y1
    A2 --> Y1
    Y1 --> Y2
    Y2 --> Y3
    Y3 -- Promoted --> Y4
    Y3 -- Conditional --> Y5
    Y3 -- Failed / Retained --> Y6
    Y3 -- Completed / Terminal --> Y9
    Y4 --> Y7
    Y6 --> Y8
    Y5 --> R1
    R1 --> R2
    R2 --> R3
    R3 --> R4
    R4 --> R5
    R5 -- Yes --> R6
    R5 -- No --> R7
    R6 --> Y7
    R7 --> Y10
    Y10 --> Y8
    Y7 --> Y11
    Y8 --> Y11
    Y9 --> Y12
    Y11 --> Y12
    Y12 --> Y13
    Y13 --> B
    E0 --> T13
    A2 --> A15
```
