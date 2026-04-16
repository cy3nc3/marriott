# MarriottConnect DOCX Justification Fixes

Source paper: `capstone-paper/MarriottConnect - Second Edition.docx`  
Reference checked: `capstone-paper/reference-paper.pdf`

Purpose: Use this file as a paste-ready guide for sections that already state system choices but need clearer justification. The reference paper explains each technology, resource, and methodology item in short practical paragraphs. This guide follows that same style, but keeps the wording specific to MarriottConnect and avoids unnecessary dependency version numbers unless they are important to the capstone discussion.

## 1. Technical Background: Add a Short Technology-Choice Bridge

### Location

Section: `TECHNICAL BACKGROUND`  
Subsection: `Overview of Current Technologies Used in the System`  
Place after the first overview sentence or before the backend/frontend stack paragraphs.

### Current

```text
MarriottConnect is implemented as a modern web-based school information system that centralizes institutional records, enforces role-based operations, and delivers analytics-ready outputs for decision support.
```

### Fix

```text
MarriottConnect is implemented as a modern web-based school information system that centralizes institutional records, enforces role-based operations, and delivers dashboard-ready outputs for decision support. The technologies used in the system were selected because the school requires secure authentication, structured relational records, reusable role-based interfaces, and maintainable workflows for registrar, finance, academic, student, parent, and governance users.
```

### Reason

The reference paper introduces the technical stack by explaining what the tools support in the system. This bridge explains why the stack exists before listing individual technologies.

## 2. Technical Background: Replace Version-Heavy Backend Paragraph

### Location

Section: `TECHNICAL BACKGROUND`  
Subsection: `Overview of Current Technologies Used in the System`

### Current

```text
The backend uses Laravel 12 on PHP 8.5 with PostgreSQL as the primary relational database. Authentication and access security are handled through Laravel Fortify, while role middleware and route-group policies control authorized module access.
```

### Fix

```text
The backend uses Laravel and PHP to handle routing, validation, authentication integration, database transactions, scheduled tasks, and role-based access control. Laravel is appropriate for MarriottConnect because the system contains many office-specific workflows that must remain organized by role while still sharing one institutional dataset. PostgreSQL is used as the relational database because learner records, enrollments, sections, schedules, grades, attendance, transactions, ledgers, billing schedules, announcements, and users are connected records that require consistency and referential integrity.
```

### Reason

For a capstone paper, exact framework versions are usually less important than explaining why the technology fits the project. This replacement justifies Laravel/PHP/PostgreSQL in relation to the school workflows.

## 3. Technical Background: Add Authentication and Access-Control Justification

### Location

Section: `TECHNICAL BACKGROUND`  
Subsection: `Overview of Current Technologies Used in the System`  
Place after the backend paragraph.

### Current

The paper mentions Fortify and role middleware but does not fully explain why this matters.

### Fix

```text
Authentication and access security are handled through Laravel Fortify and role-based middleware. This is necessary because the system stores sensitive learner, grade, billing, and account information. Super admin, admin, registrar, finance, teacher, student, and parent users must only access the records and actions assigned to their responsibilities. This access structure protects confidential information while allowing each office to complete its own workflow.
```

### Reason

The reference paper links tools to security and operational needs. This paragraph connects authentication to actual data sensitivity and role boundaries.

## 4. Technical Background: Replace Version-Heavy Frontend Paragraph

### Location

Section: `TECHNICAL BACKGROUND`  
Subsection: `Overview of Current Technologies Used in the System`

### Current

```text
The frontend uses Inertia.js v2 with React 19 and TypeScript to support structured, responsive workflows per role. Tailwind CSS v4 and Shadcn component patterns are used to maintain consistent interfaces across registrar, finance, academic, and governance pages.
```

### Fix

```text
The frontend uses Inertia.js, React, and TypeScript to provide interactive web pages while keeping the application connected to Laravel's route and controller structure. This approach is suitable for MarriottConnect because each role needs task-focused screens such as enrollment intake, cashier posting, schedule building, grading, attendance, and dashboard review. Tailwind CSS and Shadcn-style component patterns are used to keep tables, forms, buttons, dialogs, filters, and layouts consistent across modules.
```

### Reason

This keeps the stack explanation capstone-friendly and connects the frontend tools to actual role workflows. It removes unnecessary version emphasis.

## 5. Technical Background: Strengthen Dashboard and Chart Justification

### Location

Section: `TECHNICAL BACKGROUND`  
Subsection: `Overview of Current Technologies Used in the System`

### Current

```text
For analytics and reporting, shared chart wrappers support line, bar, area, and pie visualizations, enabling trend interpretation through standardized dashboard payloads.
```

### Fix

```text
For dashboard reporting, shared chart components support line, bar, area, and pie visualizations. These visualizations are included because users need summarized information, not only raw records. KPI cards, alerts, and trend charts allow administrators, registrar staff, finance staff, teachers, students, and parents to understand operational status more quickly than reviewing tables alone.
```

### Reason

The reference paper explains the practical role of each tool. This revision explains why charts are needed in school operations.

## 6. Prototyping Model: Clarify Why the Model Fits MarriottConnect

### Location

Section: `Prototyping Model`  
Place after the opening paragraph.

### Current

```text
The study follows an iterative prototyping model to ensure that system behavior continuously reflects validated operational requirements from school stakeholders.
```

### Fix

```text
The study follows an iterative prototyping model to ensure that system behavior continuously reflects validated operational requirements from school stakeholders. This model is appropriate for MarriottConnect because registrar, finance, academic, teacher, student, and parent workflows are interconnected. A prototype-based approach allows the developers to verify whether each module matches actual school tasks before refining the final system behavior.
```

### Reason

The reference paper explains why prototyping is used and how it connects to user feedback. This addition makes the model selection more explicit.

## 7. Prototyping Model: Requirements Gathering and Analysis

### Location

Section: `Prototyping Model`  
Subsection: `Requirements Gathering and Analysis`

### Current

```text
Interviews, process mapping, and role-task validation are used to identify bottlenecks in enrollment, cashiering, scheduling, grading, and reporting workflows.
```

### Fix

```text
Interviews, process mapping, and role-task validation are used to identify bottlenecks in enrollment, cashiering, scheduling, grading, attendance, communication, and reporting workflows. This phase establishes the basis for the system requirements by identifying where duplicate encoding, delayed reconciliation, manual validation, and limited visibility occur in the current school process.
```

### Reason

The reference paper lists the stakeholder groups and pain points that justify requirements gathering. This revision does the same for MarriottConnect.

## 8. Prototyping Model: Quick Design

### Location

Section: `Prototyping Model`  
Subsection: `Quick Design`

### Current

```text
Initial page flows, data mappings, and module interaction logic are prepared based on verified responsibilities and system boundaries.
```

### Fix

```text
Initial page flows, data mappings, and module interaction logic are prepared based on verified responsibilities and system boundaries. For MarriottConnect, this includes draft workflows for enrollment intake, cashier posting, student directory review, schedule preparation, grading, attendance, parent visibility, and role dashboards. This stage allows the proponents to organize how each role will interact with the shared data model before final implementation.
```

### Reason

The reference paper gives examples of rough designs for actual modules. This version adds MarriottConnect-specific examples.

## 9. Prototyping Model: Build Prototype

### Location

Section: `Prototyping Model`  
Subsection: `Build Prototype`

### Current

```text
A working prototype is produced for core operational modules, including registrar intake, finance posting, instructional grading, and role dashboards.
```

### Fix

```text
A working prototype is produced for core operational modules, including registrar intake, finance posting, instructional grading, attendance recording, notification handling, and role dashboards. The prototype allows users and evaluators to inspect real task flows such as adding an enrollment record, posting a payment, encoding grades, viewing schedules, and checking dashboard indicators.
```

### Reason

The reference paper describes the prototype as a working sample that users can test. This adds concrete MarriottConnect workflows.

## 10. Prototyping Model: Initial User Evaluation

### Location

Section: `Prototyping Model`  
Subsection: `Initial User Evaluation`

### Current

```text
Key users from registrar, finance, academic, and administration evaluate usability, output validity, and workflow fit against actual office practice.
```

### Fix

```text
Key users from registrar, finance, academic, and administration evaluate usability, output validity, and workflow fit against actual office practice. Evaluation focuses on whether the prototype reduces repeated encoding, keeps records consistent across offices, presents clear navigation, and produces useful outputs for daily school operations.
```

### Reason

The current text says evaluation happens, but not what criteria are used. The reference paper states what the adviser/user checks, so this adds evaluation criteria.

## 11. Prototyping Model: Refine Prototype

### Location

Section: `Prototyping Model`  
Subsection: `Refine Prototype`

### Current

```text
Evaluation feedback is applied iteratively to improve data accuracy, process speed, and interface clarity until acceptance criteria are satisfied.
```

### Fix

```text
Evaluation feedback is applied iteratively to improve data accuracy, process speed, interface clarity, and role alignment until acceptance criteria are satisfied. For example, if a workflow still requires unnecessary repeated entry, unclear record status, or confusing navigation, the design is revised before the module is considered ready for controlled use.
```

### Reason

The reference paper gives examples of what may be corrected during refinement. This adds examples without inventing unsupported features.

## 12. Prototyping Model: Implement and Maintain

### Location

Section: `Prototyping Model`  
Subsection: `Implement and Maintain`

### Current

```text
Validated modules are rolled out for controlled use, followed by user orientation, policy alignment, and maintenance planning for sustained operations.
```

### Fix

```text
Validated modules are rolled out for controlled use, followed by user orientation, policy alignment, and maintenance planning for sustained operations. Maintenance includes correcting workflow issues, preserving data integrity, updating school-year and finance settings, monitoring access controls, and adjusting the system when institutional policies change.
```

### Reason

The reference paper describes maintenance as long-term reliability, data integrity, uptime, and adaptation. This adapts that idea to MarriottConnect.

## 13. Calendar of Activities: Add a Purpose Sentence

### Location

Section: `Calendar of Activities`  
Place before the month list.

### Current

```text
Calendar of Activities
August - Initial data gathering, stakeholder interviews, and baseline workflow documentation were completed to establish the project problem context.
...
```

### Fix

```text
Calendar of Activities
The activity schedule follows the prototyping model by moving from data gathering, scope definition, and requirements consolidation to module design, implementation refinement, and final document alignment. Each activity period contributes to converting stakeholder evidence into a working role-based information system.

August - Initial data gathering, stakeholder interviews, and baseline workflow documentation were completed to establish the project problem context.
...
```

### Reason

The reference paper lists month-by-month work. This added sentence explains how the timeline connects to the methodology.

## 14. Resources: Replace Generic Hardware Requirements Intro

### Location

Section: `Resources`  
Subsection: `Hardware Requirements`

### Current

```text
The following resources are required to host, access, and operate MarriottConnect across school offices and stakeholder portals.
```

### Fix

```text
The following resources are required to host, access, and operate MarriottConnect across school offices and stakeholder portals. The hardware requirements are based on the different work patterns of the users: staff modules require stable desktop or laptop access for data-heavy workflows, while parent and student access can be supported through mobile-capable browser views for viewing schedules, grades, billing information, and notifications.
```

### Reason

The reference paper explains why each hardware category is needed. This gives the same practical justification.

## 15. Resources: Application/Database Server

### Location

Section: `Resources`  
Subsection: `Hardware Requirements`

### Current

```text
Application/Database Server: Dedicated or virtual infrastructure with sufficient CPU, memory, and storage for concurrent transactions and analytics workloads.
```

### Fix

```text
Application/Database Server: Dedicated or virtual infrastructure with sufficient CPU, memory, storage, and network reliability for concurrent transactions, scheduled tasks, file uploads, backups, and dashboard queries. This server is necessary because registrar, finance, academic, notification, and portal workflows all depend on one centralized database and application environment.
```

### Reason

This explains why server capacity matters for a centralized school system.

## 16. Resources: Client Devices for Staff

### Location

Section: `Resources`  
Subsection: `Hardware Requirements`

### Current

```text
Client Devices (Web): Desktop or laptop devices with updated browsers for registrar, finance, admin, and teacher operations.
```

### Fix

```text
Client Devices (Web): Desktop or laptop devices with updated browsers for registrar, finance, admin, and teacher operations. These devices are required because staff modules involve dense tables, multi-step forms, file uploads, exports, filters, confirmation dialogs, and review actions that are more reliable on larger screens.
```

### Reason

This matches the reference style by explaining why the device requirement exists.

## 17. Resources: Client Devices for Parents and Students

### Location

Section: `Resources`  
Subsection: `Hardware Requirements`

### Current

```text
Client Devices (Mobile): Smartphones or tablets for parent and student access to authorized schedule, grade, billing, and notification views.
```

### Fix

```text
Client Devices (Mobile): Smartphones or tablets with modern browsers for parent and student access to authorized schedule, grade, billing, and notification views. Mobile access is suitable for these users because their primary activities are viewing information, reading announcements, and checking status updates rather than performing high-density administrative encoding.
```

### Reason

This clarifies why mobile is enough for parent/student workflows.

## 18. Resources: Software Requirements Intro

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

The paper lists software requirements but does not introduce why the software stack matters.

### Fix

```text
The software requirements support secure development, reliable data handling, interactive user interfaces, and verifiable code quality. Each software component contributes to a specific need of the system, such as authentication, database consistency, interface development, dashboard visualization, testing, or deployment preparation.
```

### Reason

The reference paper gives short descriptions for software tools. This intro prepares the section for practical explanations.

## 19. Resources: Laravel and PHP

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Server Runtime: PHP 8.5 and Laravel 12 with secure environment configuration for web requests, queues, and scheduled tasks.
```

### Fix

```text
Server Runtime: PHP and Laravel provide the backend foundation for handling web requests, validation, database transactions, queues, scheduled tasks, and role-based workflows. Laravel is suitable for MarriottConnect because it supports organized route groups and controller structures for different school roles while keeping shared records under one application.
```

### Reason

Version numbers are not necessary unless your adviser specifically requires exact stack versions. This version explains what the runtime does.

## 20. Resources: PostgreSQL

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Database Management: PostgreSQL as the relational data engine for centralized and consistent institutional records.
```

### Fix

```text
Database Management: PostgreSQL serves as the relational data engine for centralized and consistent institutional records. It is appropriate because MarriottConnect connects users, students, enrollments, sections, schedules, grades, attendance, transactions, ledgers, billing schedules, announcements, and settings through related tables that must remain accurate across modules.
```

### Reason

This justifies the database choice through the project’s data relationships.

## 21. Resources: Authentication and Backend Services

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Backend Services: Laravel Fortify for authentication, role middleware for access control, and audit-safe processing for governance actions.
```

### Fix

```text
Backend Services: Laravel Fortify supports login, password handling, email verification, and two-factor authentication flows, while role middleware controls access to protected modules. These services are necessary because the system handles confidential student, billing, grade, and user-account data that must be protected from unauthorized access and changes.
```

### Reason

This explains why authentication and role controls are important for the school system.

## 22. Resources: Frontend Stack

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Frontend Stack: Inertia.js v2, React 19, and TypeScript for structured SPA-like role workflows and maintainable component architecture.
```

### Fix

```text
Frontend Stack: Inertia.js, React, and TypeScript are used for structured role workflows and maintainable component-based pages. This stack allows the system to provide interactive screens for enrollment, cashiering, scheduling, grading, attendance, notifications, dashboards, and settings while staying connected to Laravel routes and server-side data.
```

### Reason

This removes unnecessary version references and explains the frontend’s role in the system.

## 23. Resources: Interface and Visualization Framework

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Interface and Visualization Framework: Tailwind CSS v4, Shadcn component patterns, and chart wrappers for consistent UI and analytics presentation.
```

### Fix

```text
Interface and Visualization Framework: Tailwind CSS, Shadcn-style component patterns, and shared chart components are used for consistent forms, tables, buttons, dialogs, filters, layout structures, KPI cards, and trend visualizations. This consistency helps users move between modules without relearning different interface patterns for each office workflow.
```

### Reason

This connects UI tooling to usability, similar to the reference paper’s software descriptions.

## 24. Resources: Development Environment

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Development Environment: Composer, Node.js tooling, Vite, and Visual Studio Code for implementation, integration, and deployment preparation.
```

### Fix

```text
Development Environment: Composer, Node.js tooling, Vite, and Visual Studio Code support implementation, dependency management, local development, frontend bundling, and deployment preparation. These tools allow the developers to build backend and frontend changes efficiently while keeping the application organized during development.
```

### Reason

This explains the role of each development tool category without going into unnecessary detail.

## 25. Resources: Quality and Collaboration Tools

### Location

Section: `Resources`  
Subsection: `Software Requirements`

### Current

```text
Quality and Collaboration Tools: Figma for interface planning, with Pest, PHPUnit, Pint, ESLint, and Prettier for quality assurance and code consistency.
```

### Fix

```text
Quality and Collaboration Tools: Figma supports interface planning, while Pest, PHPUnit, Pint, ESLint, and Prettier support testing, formatting, and code consistency. These tools help verify that role workflows behave correctly and that future changes do not easily break existing registrar, finance, academic, and portal features.
```

### Reason

The current text lists tools. This revision explains why they matter for capstone quality and maintainability.

## 26. Requirements Analysis: Add a Justification Bridge Before Requirements

### Location

Section: `Requirements Analysis`  
Place after the opening paragraph.

### Current

```text
This section defines the requirements needed to address Marriott School's validated operational constraints through MarriottConnect, with emphasis on centralized data handling, role-based process control, and analytics-backed decision visibility.
```

### Fix

```text
This section defines the requirements needed to address Marriott School's validated operational constraints through MarriottConnect, with emphasis on centralized data handling, role-based process control, and dashboard-backed decision visibility. The requirements are based on interview findings showing repeated encoding, delayed payment reconciliation, manual schedule checking, paper-based attendance and grading workflows, and limited consolidated visibility for school leadership.
```

### Reason

This makes the requirements feel grounded in evidence before the list starts, matching the reference paper’s approach.

## 27. Requirements Analysis: Strengthen Non-Functional Requirements Justification

### Location

Section: `Requirements Analysis`  
Subsection: `F. Non-Functional Requirements`  
Place before the non-functional requirement list.

### Current

```text
F. Non-Functional Requirements
Operational Requirements
...
```

### Fix

```text
F. Non-Functional Requirements

The non-functional requirements describe the operating conditions needed for the system to remain useful, secure, and maintainable in daily school operations. These requirements are important because the system handles sensitive records and supports multiple departments that depend on timely access to the same validated information.

Operational Requirements
...
```

### Reason

The reference paper groups non-functional requirements under operational, performance, and security concerns. This added paragraph explains why the categories matter.

## 28. Requirements Analysis: Add Short Explanations Under Non-Functional Categories

### Location

Section: `Requirements Analysis`  
Subsection: `F. Non-Functional Requirements`

### Current

```text
Operational Requirements
1. The system must remain accessible through supported web clients for office and stakeholder use.
2. The system requires stable connectivity for synchronized transactions and dashboard updates.
Performance Requirements
1. Posting and grade-related updates must reflect promptly after validated submissions.
2. The platform should maintain reliable responsiveness during normal school-hour usage.
Security Requirements
1. Credentials must be protected through secure authentication handling and password hashing.
2. Authorization rules must prevent cross-role access to restricted modules and records.
3. Governance-relevant actions must be traceable through audit-ready logging mechanisms.
Usability Requirements
1. Interfaces must present clear task flows aligned with each role's routine operational responsibilities.
2. Pages must maintain readability and consistency across desktop and supported mobile contexts.
Maintainability Requirements
1. The system must support controlled updates to school-year settings, fee structures, and role configurations.
2. Core modules must remain maintainable so policy updates and institutional enhancements can be integrated without destabilizing existing workflows.
```

### Fix

```text
Operational Requirements
1. The system must remain accessible through supported web clients for office and stakeholder use.
2. The system requires stable connectivity for synchronized transactions and dashboard updates.
These requirements are necessary because registrar, finance, academic, student, and parent users rely on timely access to shared records.

Performance Requirements
1. Posting and grade-related updates must reflect promptly after validated submissions.
2. The platform should maintain reliable responsiveness during normal school-hour usage.
These requirements are necessary because delayed posting, grading, or dashboard updates would recreate the same verification delays that the system is intended to reduce.

Security Requirements
1. Credentials must be protected through secure authentication handling and password hashing.
2. Authorization rules must prevent cross-role access to restricted modules and records.
3. Governance-relevant actions must be traceable through audit-ready logging mechanisms.
These requirements are necessary because the system handles sensitive learner, grade, billing, parent, and user-account data.

Usability Requirements
1. Interfaces must present clear task flows aligned with each role's routine operational responsibilities.
2. Pages must maintain readability and consistency across desktop and supported mobile contexts.
These requirements are necessary because the intended users include school personnel and stakeholders who need clear workflows for daily tasks, not complex technical interfaces.

Maintainability Requirements
1. The system must support controlled updates to school-year settings, fee structures, and role configurations.
2. Core modules must remain maintainable so policy updates and institutional enhancements can be integrated without destabilizing existing workflows.
These requirements are necessary because school policies, academic periods, fee structures, and operational procedures may change over time.
```

### Reason

This turns the list into a justified requirements discussion while keeping the reference-paper style.

## 29. Requirements Documentation: Add Evidence-Based Module Bridge

### Location

Section: `Requirements Documentation`  
Place before the module descriptions.

### Current

```text
MarriottConnect is structured as an integrated module ecosystem where registrar, finance, academic, governance, and stakeholder services operate on one validated institutional dataset.
```

### Fix

```text
MarriottConnect is structured as an integrated module ecosystem where registrar, finance, academic, governance, and stakeholder services operate on one validated institutional dataset. Each module corresponds to an operational bottleneck identified in the interviews: registrar duplication, finance reconciliation delay, manual academic coordination, delayed stakeholder visibility, and limited governance-level monitoring.
```

### Reason

The reference requirements documentation explains the proposed system before listing modules. This bridge connects modules to validated problems.

## 30. Design Section: Add Architecture Justification

### Location

Section: `Design of Software, System, Product, and/or Processes`  
Place after the first paragraph.

### Current

```text
The software design of MarriottConnect follows a role-centered, modular architecture that separates registrar, finance, academic, stakeholder, and governance concerns while maintaining one shared institutional data model.
```

### Fix

```text
The software design of MarriottConnect follows a role-centered, modular architecture that separates registrar, finance, academic, stakeholder, and governance concerns while maintaining one shared institutional data model. This design was chosen because the school’s main problem is not the absence of digital tools, but the separation of departmental records. A role-centered modular architecture allows each office to keep its own workflow while still using shared records, reducing repeated encoding and improving traceability across the student lifecycle.
```

### Reason

This directly justifies the design choice and ties it to the main problem.

## 31. Design Section: Strengthen Data Design Justification

### Location

Section: `Design of Software, System, Product, and/or Processes`

### Current

```text
The data design is relational and continuity-oriented: learner identity, enrollment state, financial transactions, schedules, attendance, and grades are linked through validated references so updates are reusable across modules without repeated encoding.
```

### Fix

```text
The data design is relational and continuity-oriented: learner identity, enrollment state, financial transactions, billing schedules, sections, subjects, class schedules, attendance, and grades are linked through validated references so updates are reusable across modules without repeated encoding. This structure is necessary because registrar, finance, teacher, student, and parent workflows all depend on the same learner lifecycle data.
```

### Reason

This makes the data design justification explicit and includes billing schedules.

## 32. Design Section: Dashboard Design Wording

### Location

Section: `Design of Software, System, Product, and/or Processes`

### Current

```text
Analytics design uses standardized KPI, alert, and trend payload contracts so dashboards across roles can render consistent indicators while still reflecting role-specific context. This allows school leadership and operational staff to interpret metrics from one coherent data pipeline.
```

### Fix

```text
Dashboard design uses standardized KPI, alert, and trend payload contracts so dashboards across roles can render consistent indicators while still reflecting role-specific context. This allows school leadership and operational staff to interpret enrollment, finance, academic, governance, and portal-related status from one coherent data pipeline.
```

### Reason

This avoids overstating analytics as advanced or standalone while keeping the decision-support value.

## 33. Optional: Technical Background Headings for Individual Tools

### Location

Section: `TECHNICAL BACKGROUND` or `Resources > Software Requirements`

### Current

The current paper lists the stack in compact form.

### Fix

If your adviser wants the reference-paper style with individual software descriptions, you can add short entries like this:

```text
Laravel

Laravel is a PHP web framework used to build the system’s backend routes, controllers, validation, authentication integration, database transactions, and scheduled tasks. It is suitable for MarriottConnect because the application contains multiple role-based workflows that need secure access control and organized server-side processing.

PostgreSQL

PostgreSQL is a relational database management system used to store the system’s institutional records. It supports the project because student, enrollment, section, schedule, grade, attendance, billing, transaction, announcement, and user records must remain connected and consistent across modules.

Inertia.js, React, and TypeScript

Inertia.js, React, and TypeScript are used to build interactive web pages for the system without separating the frontend from Laravel’s route-based architecture. These tools support maintainable role pages for registrar, finance, academic, teacher, student, parent, and governance workflows.

Tailwind CSS and Shadcn-style Components

Tailwind CSS and Shadcn-style components are used to create consistent layouts, forms, tables, buttons, dialogs, and dashboard elements. This consistency helps users perform tasks across different modules without adapting to unrelated interface patterns.

Dashboard Chart Components

Dashboard chart components are used to display KPI cards, alerts, and trend visualizations. They help convert validated records into readable summaries for planning, monitoring, and daily decision support.

Testing and Formatting Tools

Pest, PHPUnit, Pint, ESLint, and Prettier are used to support testing, formatting, and code consistency. These tools help verify system behavior and maintain code quality as modules are updated.
```

### Reason

The reference paper uses individual software descriptions. This optional format follows that style while only including technologies actually relevant to MarriottConnect.

## Summary of What to Avoid

1. Do not overemphasize exact version numbers unless the panel asks for them.
2. Do not list technologies that are not actually used in the project.
3. Do not describe dashboard indicators as full machine-learning analytics.
4. Do not describe placeholder pages as completed report modules.
5. Do not leave resource or technology lists as inventory only; add why each item supports the system.

