import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    CircleHelp,
    ClipboardList,
    MousePointerClick,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Help',
        href: '/system-help',
    },
];

type PageGuide = {
    title: string;
    href: string;
    does: string;
    use: string[];
    notes?: string[];
};

type RoleGuide = {
    label: string;
    summary: string;
    pages: PageGuide[];
};

const commonStaffPages: PageGuide[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        does: 'Shows a quick overview of the most important records, alerts, trends, and shortcuts for your role.',
        use: [
            'Open Dashboard after logging in to check the current status of your work area.',
            'Review alert cards first because they usually point to records that need action.',
            'Use shortcut links to jump directly to the module related to the alert or summary.',
        ],
    },
    {
        title: 'Announcements',
        href: '/announcements',
        does: 'Lets authorized staff create school notices and events for selected roles.',
        use: [
            'Create a new announcement when the school needs to inform users inside the system.',
            'Select the target roles so only relevant users receive the announcement.',
            'Use acknowledgement or RSVP options only when the school needs a response.',
        ],
    },
];

const roleGuides: Record<string, RoleGuide> = {
    super_admin: {
        label: 'Super Admin',
        summary:
            'This guide explains the pages used to manage accounts, permissions, announcements, audit logs, and system-wide settings.',
        pages: [
            ...commonStaffPages,
            {
                title: 'User Manager',
                href: '/super-admin/user-manager',
                does: 'Manages system accounts for staff, students, and parents.',
                use: [
                    'Use the search and role filters to find an account.',
                    'Create accounts for staff users who need system access.',
                    'Edit account details, role, or status when a user changes responsibility.',
                    'Reset passwords only after confirming the user identity.',
                ],
                notes: [
                    'Keep at least one active Super Admin account.',
                    'Do not downgrade your own account unless another Super Admin is active.',
                ],
            },
            {
                title: 'Audit Logs',
                href: '/super-admin/audit-logs',
                does: 'Shows sensitive system activity such as creates, updates, deletes, and account changes.',
                use: [
                    'Use this page when investigating unexpected changes.',
                    'Filter by user, action, or date when tracing a specific event.',
                    'Compare log details with the affected record before reversing any change.',
                ],
            },
            {
                title: 'Permissions',
                href: '/super-admin/permissions',
                does: 'Controls which system features each role can access.',
                use: [
                    'Find the feature row that matches the module you want to control.',
                    'Set the access level for each role.',
                    'Save changes and ask affected users to refresh their browser.',
                ],
                notes: [
                    'Changing permissions can hide sidebar items from users.',
                ],
            },
            {
                title: 'System Settings',
                href: '/super-admin/system-settings',
                does: 'Stores school identity details used across printable forms and the interface.',
                use: [
                    'Update school name, contact details, logo, and printable header assets.',
                    'Save changes, then open a printable form to confirm the output.',
                ],
            },
        ],
    },
    admin: {
        label: 'Academic Admin',
        summary:
            'This guide explains the pages used to set up school years, curriculum, sections, schedules, class lists, and grade verification.',
        pages: [
            ...commonStaffPages,
            {
                title: 'School Year Manager',
                href: '/admin/academic-controls',
                does: 'Creates and controls academic years, active school year status, quarter progression, and year completion.',
                use: [
                    'Create or review the school year before enrollment and class setup starts.',
                    'Set the school year to ongoing when teachers should begin encoding.',
                    'Advance the current quarter only when the school is ready for the next grading period.',
                    'Complete the school year only after registrar, finance, and grade records are finalized.',
                ],
            },
            {
                title: 'Curriculum Manager',
                href: '/admin/curriculum-manager',
                does: 'Maintains subjects and teacher subject certifications.',
                use: [
                    'Create or update subjects used by schedules and grading sheets.',
                    'Assign teachers to subjects they are allowed to teach.',
                    'Review subject setup before building class schedules.',
                ],
            },
            {
                title: 'Section Manager',
                href: '/admin/section-manager',
                does: 'Manages grade-level sections and adviser assignments.',
                use: [
                    'Create sections for each grade level in the active school year.',
                    'Assign advisers to sections that need advisory board access.',
                    'Check section names before registrar starts assigning students.',
                ],
            },
            {
                title: 'Schedule Builder',
                href: '/admin/schedule-builder',
                does: 'Builds weekly class schedules by section, subject, teacher, day, and time.',
                use: [
                    'Select the grade level and section you want to schedule.',
                    'Choose the subject, assigned teacher, period type, day, and time.',
                    'Use the schedule grid to check overlaps before saving.',
                    'Edit or remove schedule blocks when the class schedule changes.',
                ],
                notes: [
                    'Teachers can only encode attendance and grades for assigned scheduled classes.',
                ],
            },
            {
                title: 'Grade Verification',
                href: '/admin/grade-verification',
                does: 'Reviews quarter grade submissions from teachers before grades can be released.',
                use: [
                    'Select the school year and quarter to review submissions.',
                    'Open submitted grade sheets and check for correctness.',
                    'Verify submissions that are correct.',
                    'Return submissions with notes when the teacher needs to correct entries.',
                ],
            },
            {
                title: 'Class Lists',
                href: '/admin/class-lists',
                does: 'Shows students assigned to each class or section.',
                use: [
                    'Filter by school year, grade level, section, or subject.',
                    'Use the list to confirm that schedules and enrollments match.',
                ],
            },
        ],
    },
    registrar: {
        label: 'Registrar',
        summary:
            'This guide explains the pages used to manage enrollment, student records, permanent records, promotion monitoring, remedial enrollment, and departures.',
        pages: [
            ...commonStaffPages,
            {
                title: 'Enrollment',
                href: '/registrar/enrollment',
                does: 'Handles enrollment intakes for new, returning, transferee, and remedial students.',
                use: [
                    'Create an intake and encode student identity, guardian, contact, and academic details.',
                    'Mark submitted requirements such as report card and birth certificate.',
                    'Select payment term and target grade level or section when applicable.',
                    'Save the intake for cashier payment when payment is required.',
                    'Print the registration and assessment form when needed.',
                ],
                notes: [
                    'Students waiting for cashier payment should not appear as enrolled in the directory.',
                    'Missing requirements should be tracked but should not block enrollment.',
                ],
            },
            {
                title: 'Student Directory',
                href: '/registrar/student-directory',
                does: 'Shows officially enrolled students and their profile, requirements, enrollment history, and account claim status.',
                use: [
                    'Search by student name, LRN, email, grade level, or section.',
                    'Open student details to review profile and guardian information.',
                    'Update missing requirements when the student submits to-follow documents.',
                    'Review enrollment history to confirm previous school years, sections, and status.',
                    'Update contact email and send or resend account claiming emails when needed.',
                ],
            },
            {
                title: 'Permanent Records',
                href: '/registrar/permanent-records',
                does: 'Stores and displays academic history, previous schools, grades, remedial remarks, and transfer details.',
                use: [
                    'Search for a student using name or LRN.',
                    'Open the record to review historical school years and grade levels.',
                    'Use records generated from released grades or imported historical data.',
                    'Check transfer, dropout, retained, and remedial remarks when validating student history.',
                ],
            },
            {
                title: 'Data Import',
                href: '/registrar/data-import',
                does: 'Imports student and enrollment-related spreadsheet templates.',
                use: [
                    'Download or prepare the supported import workbook.',
                    'Review required columns before uploading.',
                    'Preview rows and fix validation errors before applying the import.',
                    'Apply only after confirming the school year and record type.',
                ],
            },
            {
                title: 'Batch Promotion',
                href: '/registrar/batch-promotion',
                does: 'Monitors promotion outcomes such as passed, conditional, and retained students.',
                use: [
                    'Select the school year to review promotion records.',
                    'Check the breakdown of passed, conditional, and retained students.',
                    'Use the conditional list to identify students that may need remedial action.',
                ],
                notes: [
                    'This page is for monitoring. It should not be used to manually force promotion outcomes.',
                ],
            },
            {
                title: 'Remedial Entry',
                href: '/registrar/remedial-entry',
                does: 'Creates remedial enrollment records for conditional students and assigns teachers to remedial subjects.',
                use: [
                    'Click Select Student to choose from conditional students.',
                    'Review the failed subjects and original grade context.',
                    'Enroll the specific failed subject that needs remedial processing.',
                    'Assign the teacher responsible for remedial grade encoding.',
                    'Send the remedial enrollment to cashier payment when payment is required.',
                ],
            },
            {
                title: 'Student Departure',
                href: '/registrar/student-departure',
                does: 'Records students who transferred out, dropped out, or stopped enrollment.',
                use: [
                    'Search and select the enrolled student.',
                    'Choose the departure type and school year context.',
                    'Save remarks and effective date for registrar tracking.',
                ],
            },
        ],
    },
    finance: {
        label: 'Finance',
        summary:
            'This guide explains the pages used to process payments, ledgers, transaction records, imports, inventory, discounts, fees, and daily reports.',
        pages: [
            ...commonStaffPages,
            {
                title: 'Student Ledgers',
                href: '/finance/student-ledgers',
                does: 'Shows each student’s charges, dues, payments, discounts, adjustments, and remaining balances.',
                use: [
                    'Search for a student by name or LRN.',
                    'Open the ledger to review billing schedule and transactions.',
                    'Compare dues, payments, and discounts when checking balances.',
                ],
            },
            {
                title: 'Cashier Panel',
                href: '/finance/cashier-panel',
                does: 'Processes enrollment payments, remedial payments, product purchases, and other cashier transactions.',
                use: [
                    'Select the correct payment context such as enrollment intake, remedial intake, or student account.',
                    'Confirm student name, LRN, school year, and amount before posting.',
                    'Select payment method and encode the received amount.',
                    'Post the transaction and print or review the receipt if needed.',
                ],
                notes: [
                    'Enrollment status updates should come from the correct intake payment.',
                ],
            },
            {
                title: 'Transaction History',
                href: '/finance/transaction-history',
                does: 'Searches and reviews posted financial transactions.',
                use: [
                    'Filter by date, student, transaction type, or payment reference.',
                    'Open transaction details to review line items and allocations.',
                    'Use this page when checking receipts or payment disputes.',
                ],
            },
            {
                title: 'Data Import',
                href: '/finance/data-import',
                does: 'Imports finance templates such as historical transactions or dues records.',
                use: [
                    'Prepare the finance import workbook using the supported template.',
                    'Upload and preview rows before applying.',
                    'Fix row errors before committing imported records.',
                ],
            },
            {
                title: 'Product Inventory',
                href: '/finance/product-inventory',
                does: 'Manages sellable school items such as books, uniforms, and supplies.',
                use: [
                    'Add products with name, price, stock quantity, and category.',
                    'Update stock after inventory changes.',
                    'Deactivate items that should no longer be sold.',
                ],
            },
            {
                title: 'Discount Manager',
                href: '/finance/discount-manager',
                does: 'Creates and applies discounts or scholarships for student billing.',
                use: [
                    'Create discount definitions with amount or percentage rules.',
                    'Assign discounts to the correct student and school year.',
                    'Review the ledger to confirm the discount affected the correct charges.',
                ],
            },
            {
                title: 'Fee Structure',
                href: '/finance/fee-structure',
                does: 'Configures tuition, miscellaneous fees, other fees, and assessment charges.',
                use: [
                    'Select the school year and grade level.',
                    'Update tuition and required fee breakdowns.',
                    'Save changes before registrar prints new assessment forms.',
                ],
            },
            {
                title: 'Daily Reports',
                href: '/finance/daily-reports',
                does: 'Summarizes daily collections and cashier activity.',
                use: [
                    'Select the report date or date range.',
                    'Review totals by payment type or transaction category.',
                    'Use the report for end-of-day checking and reconciliation.',
                ],
            },
        ],
    },
    teacher: {
        label: 'Teacher',
        summary:
            'This guide explains the pages used to view schedules, encode attendance, encode grades, review historical records, handle remedial grades, and release advisory grades.',
        pages: [
            ...commonStaffPages,
            {
                title: 'Schedule',
                href: '/teacher/schedule',
                does: 'Shows your assigned classes, subjects, sections, days, and times.',
                use: [
                    'Select the school year if available.',
                    'Review daily and weekly class assignments.',
                    'Use this page to confirm which classes should appear in attendance and grading.',
                ],
            },
            {
                title: 'Attendance',
                href: '/teacher/attendance',
                does: 'Encodes student attendance for assigned classes.',
                use: [
                    'Select the class, subject, month, and school year context.',
                    'Mark each student’s attendance for the visible dates.',
                    'Save changes after encoding the attendance sheet.',
                    'Export SF2 when the school needs a printable attendance report.',
                ],
            },
            {
                title: 'Grading Sheet',
                href: '/teacher/grading-sheet',
                does: 'Creates assessments, encodes scores, computes grades, and submits quarter grades for verification.',
                use: [
                    'Select school year, section, subject, and quarter.',
                    'Add assessments under Written Works, Performance Tasks, or Quarterly Exam.',
                    'Encode student scores and save as draft while still editing.',
                    'Submit quarter grades when all scores are complete.',
                    'Hover on assessment headers to edit or remove assessments before final submission.',
                ],
                notes: [
                    'Submitted or verified grades are locked unless returned for revision.',
                ],
            },
            {
                title: 'Historical Records',
                href: '/teacher/historical-records',
                does: 'Shows past school year grades and attendance for classes connected to the teacher.',
                use: [
                    'Select school year, grade level, section, and subject.',
                    'Choose a student and click View Records.',
                    'Use the modal tabs to review historical grades and attendance.',
                ],
            },
            {
                title: 'Remedial Encoding',
                href: '/teacher/remedial-encoding',
                does: 'Lets assigned teachers encode remedial grades for students enrolled in remedial subjects.',
                use: [
                    'Open assigned remedial subjects.',
                    'Review the failed subject and original grade context.',
                    'Encode the remedial grade result.',
                    'Submit when the remedial grade is final.',
                ],
            },
            {
                title: 'Advisory Board',
                href: '/teacher/advisory-board',
                does: 'Shows advisory class grade readiness, conduct encoding, and quarter grade release controls.',
                use: [
                    'Select the advisory section and quarter.',
                    'Review subject grade verification status.',
                    'Encode conduct ratings if required.',
                    'Release quarter grades only when records are ready for students and parents.',
                ],
            },
        ],
    },
    student: {
        label: 'Student',
        summary:
            'This guide explains the pages students use to view their class schedule and released grades.',
        pages: [
            {
                title: 'Dashboard',
                href: '/dashboard',
                does: 'Shows a quick overview of student updates and available portal shortcuts.',
                use: [
                    'Open Dashboard after logging in to check recent school information.',
                    'Use the sidebar to open Schedule or Grades.',
                ],
            },
            {
                title: 'Schedule',
                href: '/student/schedule',
                does: 'Shows your enrolled section schedule and subject teachers.',
                use: [
                    'Review class days, times, subjects, and teachers.',
                    'Report incorrect schedule details to the registrar or adviser.',
                ],
            },
            {
                title: 'Grades',
                href: '/student/grades',
                does: 'Shows quarter grades after they are verified and released.',
                use: [
                    'Open Grades after the adviser announces grade release.',
                    'Select the available school year or quarter if filters are shown.',
                    'Review released grades and remarks.',
                ],
                notes: [
                    'Grades are not visible until the adviser releases them.',
                ],
            },
        ],
    },
    parent: {
        label: 'Parent',
        summary:
            'This guide explains the pages parents use to monitor schedule, released grades, and billing information.',
        pages: [
            {
                title: 'Dashboard',
                href: '/dashboard',
                does: 'Shows a quick overview of parent portal information and shortcuts.',
                use: [
                    'Open Dashboard after logging in to check available updates.',
                    'Use the sidebar to open Schedule, Grades, or Billing Information.',
                ],
            },
            {
                title: 'Schedule',
                href: '/parent/schedule',
                does: 'Shows the student’s enrolled section schedule.',
                use: [
                    'Review class days, times, subjects, and teachers.',
                    'Contact the registrar if the linked student or schedule is incorrect.',
                ],
            },
            {
                title: 'Grades',
                href: '/parent/grades',
                does: 'Shows the student’s released quarter grades.',
                use: [
                    'Open Grades after the adviser releases quarter grades.',
                    'Review grades and remarks for the selected school year or quarter.',
                ],
                notes: [
                    'Grades are hidden until verified and released by the school.',
                ],
            },
            {
                title: 'Billing Information',
                href: '/parent/billing-information',
                does: 'Shows dues, payment schedule, balances, and transaction records for the linked student.',
                use: [
                    'Review current balance and upcoming dues.',
                    'Check transaction records against official receipts.',
                    'Contact finance if a payment is missing or amount does not match.',
                ],
            },
        ],
    },
};

const fallbackGuide: RoleGuide = {
    label: 'User',
    summary:
        'This guide explains the pages available to your account and how to use them.',
    pages: [
        {
            title: 'Dashboard',
            href: '/dashboard',
            does: 'Shows the main landing page for your account.',
            use: [
                'Open Dashboard after logging in.',
                'Use the sidebar to open the pages available to your role.',
            ],
        },
    ],
};

const getRole = (role: unknown): string => {
    return typeof role === 'string' ? role : '';
};

const screenshotByRoleHref: Record<string, Record<string, string>> = {
    super_admin: {
        '/dashboard': 'super-admin-dashboard.png',
        '/announcements': 'super-admin-announcements.png',
        '/super-admin/user-manager': 'super-admin-user-manager.png',
        '/super-admin/audit-logs': 'super-admin-audit-logs.png',
        '/super-admin/permissions': 'super-admin-permissions.png',
        '/super-admin/system-settings': 'super-admin-system-settings.png',
    },
    admin: {
        '/dashboard': 'admin-dashboard.png',
        '/announcements': 'admin-announcements.png',
        '/admin/academic-controls': 'admin-school-year-manager.png',
        '/admin/curriculum-manager': 'admin-curriculum-manager.png',
        '/admin/section-manager': 'admin-section-manager.png',
        '/admin/schedule-builder': 'admin-schedule-builder.png',
        '/admin/grade-verification': 'admin-grade-verification.png',
        '/admin/class-lists': 'admin-class-lists.png',
    },
    registrar: {
        '/dashboard': 'registrar-dashboard.png',
        '/announcements': 'registrar-announcements.png',
        '/registrar/student-directory': 'registrar-student-directory.png',
        '/registrar/enrollment': 'registrar-enrollment.png',
        '/registrar/permanent-records': 'registrar-permanent-records.png',
        '/registrar/data-import': 'registrar-data-import.png',
        '/registrar/batch-promotion': 'registrar-batch-promotion.png',
        '/registrar/remedial-entry': 'registrar-remedial-entry.png',
        '/registrar/student-departure': 'registrar-student-departure.png',
    },
    finance: {
        '/dashboard': 'finance-dashboard.png',
        '/announcements': 'finance-announcements.png',
        '/finance/student-ledgers': 'finance-student-ledgers.png',
        '/finance/cashier-panel': 'finance-cashier-panel.png',
        '/finance/transaction-history': 'finance-transaction-history.png',
        '/finance/data-import': 'finance-data-import.png',
        '/finance/product-inventory': 'finance-product-inventory.png',
        '/finance/discount-manager': 'finance-discount-manager.png',
        '/finance/fee-structure': 'finance-fee-structure.png',
        '/finance/daily-reports': 'finance-daily-reports.png',
    },
    teacher: {
        '/dashboard': 'teacher-dashboard.png',
        '/announcements': 'teacher-announcements.png',
        '/teacher/schedule': 'teacher-schedule.png',
        '/teacher/attendance': 'teacher-attendance.png',
        '/teacher/grading-sheet': 'teacher-grading-sheet.png',
        '/teacher/historical-records': 'teacher-historical-records.png',
        '/teacher/remedial-encoding': 'teacher-remedial-encoding.png',
        '/teacher/advisory-board': 'teacher-advisory-board.png',
    },
    student: {
        '/dashboard': 'student-dashboard.png',
        '/student/schedule': 'student-schedule.png',
        '/student/grades': 'student-grades.png',
    },
    parent: {
        '/dashboard': 'parent-dashboard.png',
        '/parent/schedule': 'parent-schedule.png',
        '/parent/grades': 'parent-grades.png',
        '/parent/billing-information': 'parent-billing-information.png',
    },
};

const resolveScreenshot = (role: string, href: string): string | null => {
    const filename = screenshotByRoleHref[role]?.[href];

    return filename ? `/docs/screenshots/${filename}` : null;
};

const sectionId = (title: string): string => {
    return title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
};

export default function SystemHelp() {
    const page = usePage<SharedData>();
    const role = getRole(page.props.auth.user.role);
    const guide = roleGuides[role] ?? fallbackGuide;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Help" />

            <div className="space-y-6">
                <section className="rounded-3xl border bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-sm dark:from-slate-100 dark:via-white dark:to-slate-200 dark:text-slate-950">
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div className="space-y-3">
                            <Badge className="w-fit bg-white/15 text-white hover:bg-white/15 dark:bg-slate-950/10 dark:text-slate-950">
                                {guide.label} Page Guide
                            </Badge>
                            <div>
                                <h1 className="text-3xl font-semibold tracking-tight">
                                    System Help
                                </h1>
                                <p className="mt-2 max-w-3xl text-sm leading-6 text-white/75 dark:text-slate-700">
                                    {guide.summary}
                                </p>
                            </div>
                        </div>
                        <div className="rounded-2xl border border-white/15 bg-white/10 p-4 text-sm text-white/80 dark:border-slate-950/10 dark:bg-slate-950/5 dark:text-slate-700">
                            <div className="flex items-center gap-2 font-medium text-white dark:text-slate-950">
                                <CircleHelp className="size-4" />
                                How to read this page
                            </div>
                            <p className="mt-2 max-w-sm">
                                Each section explains what a page is for, how to
                                use it, and important notes before saving or
                                changing records.
                            </p>
                        </div>
                    </div>
                </section>

                <div className="grid gap-6 lg:grid-cols-[16rem_1fr]">
                    <aside className="hidden lg:block">
                        <div className="sticky top-4 rounded-2xl border bg-card p-4">
                            <p className="mb-3 text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                                Contents
                            </p>
                            <nav className="space-y-1">
                                {guide.pages.map((module) => (
                                    <a
                                        key={module.title}
                                        href={`#${sectionId(module.title)}`}
                                        className="block rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                    >
                                        {module.title}
                                    </a>
                                ))}
                            </nav>
                        </div>
                    </aside>

                    <div className="grid gap-5">
                        {guide.pages.map((module, index) => {
                            const screenshot = resolveScreenshot(
                                role,
                                module.href,
                            );

                            return (
                                <Card
                                    key={`${module.title}-${module.href}`}
                                    id={sectionId(module.title)}
                                    className="scroll-mt-4 overflow-hidden"
                                >
                                    <CardHeader className="border-b">
                                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div className="space-y-2">
                                                <div className="flex items-center gap-2">
                                                    <span className="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                                        {index + 1}
                                                    </span>
                                                    <CardTitle>
                                                        {module.title}
                                                    </CardTitle>
                                                </div>
                                                <p className="text-sm leading-6 text-muted-foreground">
                                                    {module.does}
                                                </p>
                                            </div>
                                            <Link
                                                href={module.href}
                                                className="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition hover:border-primary/40 hover:bg-muted"
                                            >
                                                Open Page
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-5 p-5">
                                        {screenshot ? (
                                            <div className="rounded-2xl border bg-slate-100 p-2 dark:bg-slate-900">
                                                <img
                                                    src={screenshot}
                                                    alt={`${module.title} screenshot`}
                                                    loading="lazy"
                                                    className="w-full rounded-xl border bg-background object-cover shadow-sm"
                                                />
                                            </div>
                                        ) : null}

                                        <div className="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
                                            <div>
                                                <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                                    <MousePointerClick className="size-4 text-primary" />
                                                    How to use this page
                                                </div>
                                                <ol className="space-y-2">
                                                    {module.use.map(
                                                        (step, stepIndex) => (
                                                            <li
                                                                key={step}
                                                                className="flex gap-3 text-sm leading-6"
                                                            >
                                                                <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                                                    {stepIndex +
                                                                        1}
                                                                </span>
                                                                <span>
                                                                    {step}
                                                                </span>
                                                            </li>
                                                        ),
                                                    )}
                                                </ol>
                                            </div>

                                            <div className="rounded-2xl border bg-muted/20 p-4">
                                                <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                                    <ClipboardList className="size-4 text-amber-600" />
                                                    Notes
                                                </div>
                                                {module.notes &&
                                                module.notes.length > 0 ? (
                                                    <ul className="space-y-2">
                                                        {module.notes.map(
                                                            (note) => (
                                                                <li
                                                                    key={note}
                                                                    className="flex gap-2 text-sm leading-6 text-muted-foreground"
                                                                >
                                                                    <span className="mt-2 size-1.5 shrink-0 rounded-full bg-amber-600" />
                                                                    <span>
                                                                        {note}
                                                                    </span>
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                ) : (
                                                    <p className="text-sm leading-6 text-muted-foreground">
                                                        Check filters such as
                                                        school year, quarter,
                                                        grade level, section, or
                                                        student before assuming
                                                        records are missing.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BookOpen className="size-5 text-primary" />
                            General Usage Reminders
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 md:grid-cols-3">
                            <div className="rounded-2xl border bg-muted/20 p-4 text-sm leading-6">
                                Wait for the success message after saving before
                                leaving the page.
                            </div>
                            <div className="rounded-2xl border bg-muted/20 p-4 text-sm leading-6">
                                Use exact names, LRN, school year, section, and
                                quarter when searching records.
                            </div>
                            <div className="rounded-2xl border bg-muted/20 p-4 text-sm leading-6">
                                If a button is disabled, the record may be
                                locked, finalized, outside the active school
                                year, or unavailable to your role.
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
