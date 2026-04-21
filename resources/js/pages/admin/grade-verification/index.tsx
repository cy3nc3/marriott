import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock3, ListChecks, RotateCcw } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grade Verification',
        href: '/admin/grade-verification',
    },
];

type SubmissionStatus = 'submitted' | 'returned' | 'verified' | 'draft';
type CoverageStatus =
    | SubmissionStatus
    | 'not_submitted';

type StudentGradeRow = {
    enrollment_id: number | null;
    student_name: string;
    grade: number | null;
    is_locked: boolean;
    is_missing: boolean;
    is_at_risk: boolean;
};

type SubmissionRow = {
    id: number;
    academic_year_id: number;
    class_label: string;
    subject_code: string;
    subject_name: string;
    teacher_name: string;
    quarter: string;
    quarter_label: string;
    status: SubmissionStatus;
    status_label: string;
    expected_rows: number;
    posted_rows: number;
    locked_rows: number;
    missing_rows: number;
    average_grade: number | null;
    min_grade: number | null;
    max_grade: number | null;
    at_risk_count: number;
    return_notes: string | null;
    submitted_at: string | null;
    verified_at: string | null;
    returned_at: string | null;
    verified_by_name: string;
    returned_by_name: string;
    student_grades: StudentGradeRow[];
    can_verify: boolean;
    can_return: boolean;
};

type CoverageRow = {
    subject_assignment_id: number;
    class_label: string;
    subject_code: string;
    subject_name: string;
    teacher_name: string;
    status: CoverageStatus;
    status_label: string;
    submitted_at: string | null;
    is_submitted: boolean;
};

interface Props {
    context: {
        academic_year: string | null;
        current_quarter: string;
        current_quarter_label: string;
        submission_deadline: string | null;
        reminder_automation: {
            auto_send_enabled: boolean;
            send_time: string;
            reminder_days: number[];
        };
    };
    summary: {
        submitted_count: number;
        returned_count: number;
        verified_count: number;
    };
    coverage: {
        submitted_count: number;
        not_submitted_count: number;
        submitted: CoverageRow[];
        not_submitted: CoverageRow[];
    };
    submissions: SubmissionRow[];
}

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    const parsedDate = new Date(value);

    if (Number.isNaN(parsedDate.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsedDate);
};

const toDateTimeLocalValue = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const parsedDate = new Date(value);

    if (Number.isNaN(parsedDate.getTime())) {
        return '';
    }

    const localDate = new Date(
        parsedDate.getTime() - parsedDate.getTimezoneOffset() * 60000,
    );

    return localDate.toISOString().slice(0, 16);
};

const formatGrade = (value: number | null): string => {
    return value !== null ? value.toFixed(2) : '-';
};

const statusBadgeVariant = (
    status: SubmissionStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'returned') {
        return 'destructive';
    }

    return 'outline';
};

const statusBadgeClass = (status: SubmissionStatus): string => {
    if (status === 'verified') {
        return 'border-emerald-300 bg-emerald-100 text-emerald-800';
    }

    if (status === 'submitted') {
        return 'border-amber-300 bg-amber-100 text-amber-800';
    }

    return '';
};

export default function GradeVerification({
    context,
    summary,
    coverage,
    submissions,
}: Props) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const [activeTab, setActiveTab] = useState<
        'submitted' | 'returned' | 'verified'
    >('submitted');
    const [detailsDialogOpen, setDetailsDialogOpen] = useState(false);
    const [returnPromptOpen, setReturnPromptOpen] = useState(false);
    const [deadlineDialogOpen, setDeadlineDialogOpen] = useState(false);
    const [coverageDialogOpen, setCoverageDialogOpen] = useState(false);
    const [selectedSubmission, setSelectedSubmission] =
        useState<SubmissionRow | null>(null);

    const returnForm = useForm({
        return_notes: '',
    });

    const deadlineForm = useForm({
        submission_deadline: toDateTimeLocalValue(context.submission_deadline),
        send_time: context.reminder_automation.send_time,
        reminder_days: context.reminder_automation.reminder_days,
    });

    useEffect(() => {
        deadlineForm.setData(
            'submission_deadline',
            toDateTimeLocalValue(context.submission_deadline),
        );
    }, [context.submission_deadline]);

    useEffect(() => {
        deadlineForm.setData('send_time', context.reminder_automation.send_time);
        deadlineForm.setData(
            'reminder_days',
            context.reminder_automation.reminder_days,
        );
    }, [
        context.reminder_automation.send_time,
        context.reminder_automation.reminder_days,
    ]);

    const submittedRows = useMemo(() => {
        return submissions.filter(
            (submission) => submission.status === 'submitted',
        );
    }, [submissions]);

    const returnedRows = useMemo(() => {
        return submissions.filter(
            (submission) => submission.status === 'returned',
        );
    }, [submissions]);

    const verifiedRows = useMemo(() => {
        return submissions.filter(
            (submission) => submission.status === 'verified',
        );
    }, [submissions]);

    const openDetailsDialog = (submission: SubmissionRow) => {
        setSelectedSubmission(submission);
        returnForm.reset();
        returnForm.clearErrors();
        setDetailsDialogOpen(true);
    };

    const openReturnPrompt = () => {
        if (!selectedSubmission) {
            return;
        }

        returnForm.reset();
        returnForm.clearErrors();
        setReturnPromptOpen(true);
    };

    const handleVerify = (submission: SubmissionRow) => {
        router.post(
            `/admin/grade-verification/${submission.id}/verify`,
            undefined,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDetailsDialogOpen(false);
                    setSelectedSubmission(null);
                },
            },
        );
    };

    const submitReturn = () => {
        if (!selectedSubmission) {
            return;
        }

        returnForm.post(
            `/admin/grade-verification/${selectedSubmission.id}/return`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReturnPromptOpen(false);
                    setDetailsDialogOpen(false);
                    setSelectedSubmission(null);
                    returnForm.reset();
                },
            },
        );
    };

    const submitDeadline = () => {
        deadlineForm.post('/admin/grade-verification/deadline', {
            preserveScroll: true,
            onSuccess: () => {
                setDeadlineDialogOpen(false);
            },
        });
    };

    const toggleReminderDay = (day: number) => {
        const currentDays = deadlineForm.data.reminder_days;
        if (currentDays.includes(day) && currentDays.length === 1) {
            return;
        }

        const updatedDays = currentDays.includes(day)
            ? currentDays.filter((value) => value !== day)
            : [...currentDays, day];

        deadlineForm.setData(
            'reminder_days',
            [...updatedDays].sort((a, b) => b - a),
        );
    };

    const renderRows = (rows: SubmissionRow[]) => {
        if (rows.length === 0) {
            return (
                <TableRow>
                    <TableCell
                        colSpan={7}
                        className="px-6 py-6 text-center text-sm text-muted-foreground"
                    >
                        No records in this queue.
                    </TableCell>
                </TableRow>
            );
        }

        return rows.map((row) => (
            <TableRow key={row.id}>
                <TableCell className="pl-6">
                    <p className="font-medium">{row.class_label}</p>
                </TableCell>
                <TableCell className="text-center font-medium">
                    {row.subject_code}
                </TableCell>
                <TableCell>{row.teacher_name}</TableCell>
                <TableCell className="text-center">{row.quarter_label}</TableCell>
                <TableCell className="text-center">
                    {row.posted_rows} / {row.expected_rows}
                </TableCell>
                <TableCell className="text-center text-xs text-muted-foreground">
                    {formatDateTime(row.submitted_at)}
                </TableCell>
                <TableCell className="pr-6 text-right">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => openDetailsDialog(row)}
                    >
                        View Details
                    </Button>
                </TableCell>
            </TableRow>
        ));
    };

    const renderCards = (rows: SubmissionRow[]) => {
        if (rows.length === 0) {
            return (
                <div className="px-6 py-6 text-center text-sm text-muted-foreground">
                    No records in this queue.
                </div>
            );
        }

        return (
            <div className="divide-y">
                {rows.map((row) => (
                    <div key={row.id} className="space-y-3 px-6 py-4">
                        <div className="flex items-center justify-between gap-2">
                            <div>
                                <p className="text-sm font-medium">{row.class_label}</p>
                                <p className="text-xs text-muted-foreground">
                                    {row.subject_code}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {row.teacher_name}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2 text-xs">
                            <p className="text-muted-foreground">Quarter</p>
                            <p className="text-right font-medium">{row.quarter_label}</p>
                            <p className="text-muted-foreground">Posted / Expected</p>
                            <p className="text-right font-medium">
                                {row.posted_rows} / {row.expected_rows}
                            </p>
                            <p className="text-muted-foreground">Submitted</p>
                            <p className="text-right font-medium">
                                {formatDateTime(row.submitted_at)}
                            </p>
                        </div>
                        <div className="pt-1">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => openDetailsDialog(row)}
                            >
                                View Details
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        );
    };

    const renderTabContent = (rows: SubmissionRow[]) => {
        if (isHandheld) {
            return renderCards(rows);
        }

        return (
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="pl-6">Class</TableHead>
                        <TableHead className="text-center">
                            Subject Code
                        </TableHead>
                        <TableHead>Teacher</TableHead>
                        <TableHead className="text-center">Quarter</TableHead>
                        <TableHead className="text-center">
                            Posted / Expected
                        </TableHead>
                        <TableHead className="text-center">Submitted</TableHead>
                        <TableHead className="pr-6 text-right">Details</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>{renderRows(rows)}</TableBody>
            </Table>
        );
    };

    const renderCoverageRows = (
        rows: CoverageRow[],
        emptyMessage: string,
        mode: 'submitted' | 'not_submitted',
    ) => {
        if (rows.length === 0) {
            return (
                <div className="rounded-md border px-4 py-8 text-center text-sm text-muted-foreground">
                    {emptyMessage}
                </div>
            );
        }

        return (
            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Class</TableHead>
                            <TableHead>Subject</TableHead>
                            <TableHead>Teacher</TableHead>
                            {mode === 'submitted' ? (
                                <TableHead className="text-right">
                                    Submitted
                                </TableHead>
                            ) : null}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.subject_assignment_id}>
                                <TableCell className="font-medium">
                                    {row.class_label}
                                </TableCell>
                                <TableCell>
                                    <p className="font-medium">{row.subject_code}</p>
                                </TableCell>
                                <TableCell>{row.teacher_name}</TableCell>
                                {mode === 'submitted' ? (
                                    <TableCell className="text-right text-xs text-muted-foreground">
                                        {formatDateTime(row.submitted_at)}
                                    </TableCell>
                                ) : null}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grade Verification" />

            <div className="flex flex-col gap-4">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex flex-col gap-4 border-b p-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <p className="text-xs text-muted-foreground">
                                        School Year
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {context.academic_year ||
                                            'No active school year'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs text-muted-foreground">
                                        Current Quarter
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {context.current_quarter_label}
                                    </p>
                                </div>
                            </div>

                            <div className="ml-auto flex items-end gap-3">
                                <Button
                                    variant="outline"
                                    onClick={() => setCoverageDialogOpen(true)}
                                >
                                    <ListChecks className="size-4" />
                                    Submission Status
                                </Button>
                                {isHandheld ? (
                                    <p className="text-xs text-muted-foreground">
                                        Desktop only
                                    </p>
                                ) : (
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDeadlineDialogOpen(true)
                                        }
                                    >
                                        <Clock3 className="size-4" />
                                        Set Deadline
                                    </Button>
                                )}
                            </div>
                        </div>

                        <Tabs
                            value={activeTab}
                            onValueChange={(value) =>
                                setActiveTab(
                                    value as
                                        | 'submitted'
                                        | 'returned'
                                        | 'verified',
                                )
                            }
                            className="flex w-full flex-col gap-0"
                        >
                            <div className="border-b px-6 py-4">
                                <TabsList>
                                    <TabsTrigger value="submitted">
                                        For Verification (
                                        {summary.submitted_count})
                                    </TabsTrigger>
                                    <TabsTrigger value="returned">
                                        Returned ({summary.returned_count})
                                    </TabsTrigger>
                                    <TabsTrigger value="verified">
                                        Verified ({summary.verified_count})
                                    </TabsTrigger>
                                </TabsList>
                            </div>

                            <TabsContent value="submitted" className="m-0">
                                {renderTabContent(submittedRows)}
                            </TabsContent>

                            <TabsContent value="returned" className="m-0">
                                {renderTabContent(returnedRows)}
                            </TabsContent>

                            <TabsContent value="verified" className="m-0">
                                {renderTabContent(verifiedRows)}
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={deadlineDialogOpen}
                onOpenChange={setDeadlineDialogOpen}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Set Deadline</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <div className="space-y-1">
                            <Label className="text-xs text-muted-foreground">
                                Quarter
                            </Label>
                            <p className="text-sm font-medium">
                                {context.current_quarter_label}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="submission-deadline">
                                Deadline
                            </Label>
                            <Input
                                id="submission-deadline"
                                type="datetime-local"
                                value={deadlineForm.data.submission_deadline}
                                onChange={(event) =>
                                    deadlineForm.setData(
                                        'submission_deadline',
                                        event.target.value,
                                    )
                                }
                            />
                            {deadlineForm.errors.submission_deadline ? (
                                <p className="text-xs text-destructive">
                                    {deadlineForm.errors.submission_deadline}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="reminder-time">
                                Reminder Time
                            </Label>
                            <Input
                                id="reminder-time"
                                type="time"
                                value={deadlineForm.data.send_time}
                                onChange={(event) =>
                                    deadlineForm.setData(
                                        'send_time',
                                        event.target.value,
                                    )
                                }
                            />
                            {deadlineForm.errors.send_time ? (
                                <p className="text-xs text-destructive">
                                    {deadlineForm.errors.send_time}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-2">
                            <Label>Reminder Frequency (days before deadline)</Label>
                            <div className="flex flex-wrap gap-2">
                                {[1, 2, 3, 5, 7].map((day) => (
                                    <Button
                                        key={day}
                                        type="button"
                                        size="sm"
                                        variant={
                                            deadlineForm.data.reminder_days.includes(
                                                day,
                                            )
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() => toggleReminderDay(day)}
                                    >
                                        {day} day{day > 1 ? 's' : ''}
                                    </Button>
                                ))}
                            </div>
                            {deadlineForm.errors.reminder_days ? (
                                <p className="text-xs text-destructive">
                                    {deadlineForm.errors.reminder_days}
                                </p>
                            ) : null}
                            {deadlineForm.errors['reminder_days.0'] ? (
                                <p className="text-xs text-destructive">
                                    {deadlineForm.errors['reminder_days.0']}
                                </p>
                            ) : null}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeadlineDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={submitDeadline}
                            disabled={deadlineForm.processing}
                        >
                            Save Settings
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={coverageDialogOpen}
                onOpenChange={setCoverageDialogOpen}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>Class Submission Status</DialogTitle>
                    </DialogHeader>
                    <Tabs defaultValue="submitted" className="space-y-4">
                        <TabsList>
                            <TabsTrigger value="submitted">
                                Submitted ({coverage.submitted_count})
                            </TabsTrigger>
                            <TabsTrigger value="not_submitted">
                                Not Yet Submitted ({coverage.not_submitted_count})
                            </TabsTrigger>
                        </TabsList>
                        <TabsContent value="submitted" className="m-0">
                            {renderCoverageRows(
                                coverage.submitted,
                                'No submitted classes yet.',
                                'submitted',
                            )}
                        </TabsContent>
                        <TabsContent value="not_submitted" className="m-0">
                            {renderCoverageRows(
                                coverage.not_submitted,
                                'All classes are submitted.',
                                'not_submitted',
                            )}
                        </TabsContent>
                    </Tabs>
                </DialogContent>
            </Dialog>

            <Dialog
                open={detailsDialogOpen}
                onOpenChange={setDetailsDialogOpen}
            >
                <DialogContent
                    showCloseButton={false}
                    className="flex max-h-[90vh] flex-col overflow-hidden p-0 sm:max-w-4xl"
                >
                    <DialogHeader className="sticky top-0 z-20 border-b bg-background px-6 py-4">
                        <DialogTitle>Grade Submission Details</DialogTitle>
                    </DialogHeader>
                    <div className="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                        {selectedSubmission ? (
                            <div className="space-y-4">
                            <div className="grid gap-3 text-sm md:grid-cols-2">
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Class
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.class_label}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Teacher
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.teacher_name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Subject Code
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.subject_code}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Subject Title
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.subject_name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Quarter
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.quarter_label}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Status
                                    </p>
                                    <Badge
                                        variant={statusBadgeVariant(
                                            selectedSubmission.status,
                                        )}
                                        className={statusBadgeClass(
                                            selectedSubmission.status,
                                        )}
                                    >
                                        {selectedSubmission.status_label}
                                    </Badge>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Submitted
                                    </p>
                                    <p className="font-medium">
                                        {formatDateTime(
                                            selectedSubmission.submitted_at,
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Verified
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.verified_at
                                            ? `${formatDateTime(selectedSubmission.verified_at)} by ${selectedSubmission.verified_by_name}`
                                            : '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Returned
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.returned_at
                                            ? `${formatDateTime(selectedSubmission.returned_at)} by ${selectedSubmission.returned_by_name}`
                                            : '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Submission Coverage
                                    </p>
                                    <p className="font-medium">
                                        {selectedSubmission.posted_rows} /{' '}
                                        {selectedSubmission.expected_rows}
                                    </p>
                                </div>
                            </div>

                            <div className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                <Card>
                                    <CardContent className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Locked Rows
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {selectedSubmission.locked_rows}
                                        </p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Missing Rows
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {selectedSubmission.missing_rows}
                                        </p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Average Grade
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {formatGrade(
                                                selectedSubmission.average_grade,
                                            )}
                                        </p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Min Grade
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {formatGrade(
                                                selectedSubmission.min_grade,
                                            )}
                                        </p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Max Grade
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {formatGrade(
                                                selectedSubmission.max_grade,
                                            )}
                                        </p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="space-y-1 p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Below 75
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {selectedSubmission.at_risk_count}
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>

                            {selectedSubmission.status === 'returned' ? (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium">
                                        Return Notes
                                    </p>
                                    <div className="rounded-md border bg-muted/30 p-3 text-sm">
                                        {selectedSubmission.return_notes ||
                                            'No return notes provided.'}
                                    </div>
                                </div>
                            ) : null}

                            <div className="space-y-2">
                                <p className="text-sm font-medium">Student Grades</p>
                                <div className="overflow-hidden rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="pl-4">
                                                    Student
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Grade
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Locked
                                                </TableHead>
                                                <TableHead className="pr-4 text-right">
                                                    Flags
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {selectedSubmission.student_grades
                                                .length === 0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={4}
                                                        className="px-4 py-6 text-center text-sm text-muted-foreground"
                                                    >
                                                        No student grade rows available.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                selectedSubmission.student_grades.map(
                                                    (row, index) => (
                                                        <TableRow
                                                            key={
                                                                row.enrollment_id ??
                                                                `${row.student_name}-${index}`
                                                            }
                                                        >
                                                            <TableCell className="pl-4">
                                                                {row.student_name}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {formatGrade(
                                                                    row.grade,
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {row.is_locked
                                                                    ? 'Yes'
                                                                    : 'No'}
                                                            </TableCell>
                                                            <TableCell className="pr-4 text-right">
                                                                <div className="flex justify-end gap-2">
                                                                    {row.is_missing ? (
                                                                        <Badge variant="outline">
                                                                            Missing
                                                                        </Badge>
                                                                    ) : null}
                                                                    {row.is_at_risk ? (
                                                                        <Badge variant="destructive">
                                                                            {'<75'}
                                                                        </Badge>
                                                                    ) : null}
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )
                                            )}
                                        </TableBody>
                                    </Table>
                                    {selectedSubmission.status === 'submitted' ? (
                                        <div className="grid grid-cols-2 gap-2 border-t p-3">
                                            <Button
                                                variant="outline"
                                                onClick={openReturnPrompt}
                                                disabled={
                                                    returnForm.processing ||
                                                    !selectedSubmission.can_return
                                                }
                                                className="w-full"
                                            >
                                                <RotateCcw className="size-4" />
                                                Return Submission
                                            </Button>
                                            <Button
                                                onClick={() =>
                                                    handleVerify(selectedSubmission)
                                                }
                                                disabled={
                                                    returnForm.processing ||
                                                    !selectedSubmission.can_verify
                                                }
                                                className="w-full"
                                            >
                                                <CheckCircle2 className="size-4" />
                                                Verify Submission
                                            </Button>
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                            </div>
                        ) : null}
                    </div>
                    <DialogFooter className="sticky bottom-0 z-20 border-t bg-background px-6 py-3">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setReturnPromptOpen(false);
                                setDetailsDialogOpen(false);
                                setSelectedSubmission(null);
                                returnForm.reset();
                            }}
                            className="ml-auto"
                        >
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={returnPromptOpen} onOpenChange={setReturnPromptOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Return Grade Submission</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            Provide revision notes for{' '}
                            {selectedSubmission?.class_label || 'this class'}.
                        </p>
                        <Textarea
                            rows={5}
                            value={returnForm.data.return_notes}
                            onChange={(event) =>
                                returnForm.setData(
                                    'return_notes',
                                    event.target.value,
                                )
                            }
                            placeholder="State what needs to be corrected before resubmission."
                        />
                        {returnForm.errors.return_notes ? (
                            <p className="text-xs text-destructive">
                                {returnForm.errors.return_notes}
                            </p>
                        ) : null}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setReturnPromptOpen(false);
                                returnForm.clearErrors();
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={submitReturn}
                            disabled={returnForm.processing}
                        >
                            Confirm Return
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
