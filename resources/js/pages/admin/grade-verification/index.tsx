import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock3, RotateCcw } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grade Verification',
        href: '/admin/grade-verification',
    },
];

type SubmissionStatus = 'submitted' | 'returned' | 'verified' | 'draft';

type SubmissionRow = {
    id: number;
    academic_year_id: number;
    class_label: string;
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
    can_verify: boolean;
    can_return: boolean;
};

interface Props {
    context: {
        academic_year: string | null;
        current_quarter: string;
        current_quarter_label: string;
        submission_deadline: string | null;
    };
    summary: {
        submitted_count: number;
        returned_count: number;
        verified_count: number;
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

const formatDeadline = (value: string | null): string => {
    const formattedValue = formatDateTime(value);

    if (formattedValue === '-') {
        return 'No deadline set';
    }

    return formattedValue;
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

const statusBadgeVariant = (
    status: SubmissionStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'verified') {
        return 'default';
    }

    if (status === 'returned') {
        return 'destructive';
    }

    if (status === 'submitted') {
        return 'secondary';
    }

    return 'outline';
};

export default function GradeVerification({ context, summary, submissions }: Props) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const [activeTab, setActiveTab] = useState<
        'submitted' | 'returned' | 'verified'
    >('submitted');
    const [returnDialogOpen, setReturnDialogOpen] = useState(false);
    const [deadlineDialogOpen, setDeadlineDialogOpen] = useState(false);
    const [selectedSubmission, setSelectedSubmission] = useState<SubmissionRow | null>(
        null,
    );

    const returnForm = useForm({
        return_notes: '',
    });

    const deadlineForm = useForm({
        submission_deadline: toDateTimeLocalValue(context.submission_deadline),
    });

    useEffect(() => {
        deadlineForm.setData(
            'submission_deadline',
            toDateTimeLocalValue(context.submission_deadline),
        );
    }, [context.submission_deadline]);

    const submittedRows = useMemo(() => {
        return submissions.filter((submission) => submission.status === 'submitted');
    }, [submissions]);

    const returnedRows = useMemo(() => {
        return submissions.filter((submission) => submission.status === 'returned');
    }, [submissions]);

    const verifiedRows = useMemo(() => {
        return submissions.filter((submission) => submission.status === 'verified');
    }, [submissions]);

    const deadlineButtonLabel = context.submission_deadline
        ? 'Edit Deadline'
        : 'Set Deadline';

    const handleVerify = (submission: SubmissionRow) => {
        router.post(`/admin/grade-verification/${submission.id}/verify`, undefined, {
            preserveScroll: true,
        });
    };

    const openReturnDialog = (submission: SubmissionRow) => {
        setSelectedSubmission(submission);
        returnForm.setData('return_notes', '');
        setReturnDialogOpen(true);
    };

    const submitReturn = () => {
        if (!selectedSubmission) {
            return;
        }

        returnForm.post(`/admin/grade-verification/${selectedSubmission.id}/return`, {
            preserveScroll: true,
            onSuccess: () => {
                setReturnDialogOpen(false);
                setSelectedSubmission(null);
                returnForm.reset();
            },
        });
    };

    const submitDeadline = () => {
        deadlineForm.post('/admin/grade-verification/deadline', {
            preserveScroll: true,
            onSuccess: () => {
                setDeadlineDialogOpen(false);
            },
        });
    };

    const renderRows = (rows: SubmissionRow[], showActions: boolean) => {
        if (rows.length === 0) {
            return (
                <TableRow>
                    <TableCell
                        colSpan={showActions ? 10 : 9}
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
                    <div className="space-y-1">
                        <p className="font-medium">{row.class_label}</p>
                        <p className="text-xs text-muted-foreground">
                            {row.quarter_label}
                        </p>
                    </div>
                </TableCell>
                <TableCell>{row.teacher_name}</TableCell>
                <TableCell className="text-center">
                    {row.posted_rows} / {row.expected_rows}
                </TableCell>
                <TableCell className="text-center">{row.locked_rows}</TableCell>
                <TableCell className="text-center">
                    {row.average_grade !== null
                        ? row.average_grade.toFixed(2)
                        : '-'}
                </TableCell>
                <TableCell className="text-center">{row.at_risk_count}</TableCell>
                <TableCell className="text-center">
                    <Badge variant={statusBadgeVariant(row.status)}>
                        {row.status_label}
                    </Badge>
                </TableCell>
                <TableCell className="text-center text-xs text-muted-foreground">
                    {formatDateTime(row.submitted_at)}
                </TableCell>
                <TableCell className="max-w-[280px] whitespace-normal text-xs text-muted-foreground">
                    {row.return_notes || '-'}
                </TableCell>
                {showActions ? (
                    <TableCell className="pr-6 text-right">
                        {row.status === 'submitted' ? (
                            <div className="flex justify-end gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => openReturnDialog(row)}
                                    disabled={!row.can_return}
                                >
                                    <RotateCcw className="size-4" />
                                    Return
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() => handleVerify(row)}
                                    disabled={!row.can_verify}
                                >
                                    <CheckCircle2 className="size-4" />
                                    Verify
                                </Button>
                            </div>
                        ) : (
                            <span className="text-xs text-muted-foreground">No actions</span>
                        )}
                    </TableCell>
                ) : null}
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
                                    {row.teacher_name}
                                </p>
                            </div>
                            <Badge variant={statusBadgeVariant(row.status)}>
                                {row.status_label}
                            </Badge>
                        </div>
                        <div className="grid grid-cols-2 gap-2 text-xs">
                            <p className="text-muted-foreground">Quarter</p>
                            <p className="text-right font-medium">{row.quarter_label}</p>
                            <p className="text-muted-foreground">Posted / Expected</p>
                            <p className="text-right font-medium">
                                {row.posted_rows} / {row.expected_rows}
                            </p>
                            <p className="text-muted-foreground">Locked</p>
                            <p className="text-right font-medium">{row.locked_rows}</p>
                            <p className="text-muted-foreground">Average</p>
                            <p className="text-right font-medium">
                                {row.average_grade !== null
                                    ? row.average_grade.toFixed(2)
                                    : '-'}
                            </p>
                            <p className="text-muted-foreground">Below 75</p>
                            <p className="text-right font-medium">{row.at_risk_count}</p>
                            <p className="text-muted-foreground">Submitted</p>
                            <p className="text-right font-medium">
                                {formatDateTime(row.submitted_at)}
                            </p>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Return notes: {row.return_notes || '-'}
                        </p>
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
                        <TableHead>Teacher</TableHead>
                        <TableHead className="text-center">
                            Posted / Expected
                        </TableHead>
                        <TableHead className="text-center">Locked</TableHead>
                        <TableHead className="text-center">Average</TableHead>
                        <TableHead className="text-center">&lt;75</TableHead>
                        <TableHead className="text-center">Status</TableHead>
                        <TableHead className="text-center">Submitted</TableHead>
                        <TableHead>Return Notes</TableHead>
                        <TableHead className="pr-6 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>{renderRows(rows, true)}</TableBody>
            </Table>
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
                                    <p className="text-xs text-muted-foreground">School Year</p>
                                    <p className="text-sm font-semibold">
                                        {context.academic_year || 'No active school year'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs text-muted-foreground">Current Quarter</p>
                                    <p className="text-sm font-semibold">
                                        {context.current_quarter_label}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-end gap-3">
                                <div className="space-y-1">
                                    <p className="text-xs text-muted-foreground">Submission Deadline</p>
                                    <p className="text-sm font-semibold">
                                        {formatDeadline(context.submission_deadline)}
                                    </p>
                                </div>
                                {isHandheld ? (
                                    <p className="text-xs text-muted-foreground">
                                        Desktop only
                                    </p>
                                ) : (
                                    <Button
                                        variant="outline"
                                        onClick={() => setDeadlineDialogOpen(true)}
                                    >
                                        <Clock3 className="size-4" />
                                        {deadlineButtonLabel}
                                    </Button>
                                )}
                            </div>
                        </div>

                        <Tabs
                            value={activeTab}
                            onValueChange={(value) =>
                                setActiveTab(value as 'submitted' | 'returned' | 'verified')
                            }
                            className="flex w-full flex-col gap-0"
                        >
                            <div className="border-b px-6 py-4">
                                <TabsList>
                                    <TabsTrigger value="submitted">
                                        For Verification ({summary.submitted_count})
                                    </TabsTrigger>
                                    <TabsTrigger value="returned">
                                        Returned ({summary.returned_count})
                                    </TabsTrigger>
                                    <TabsTrigger value="verified">
                                        Verified ({summary.verified_count})
                                    </TabsTrigger>
                                </TabsList>
                                {isHandheld ? (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Review is available on mobile. Verify/return actions require desktop.
                                    </p>
                                ) : null}
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

            <Dialog open={deadlineDialogOpen} onOpenChange={setDeadlineDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{deadlineButtonLabel}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <div className="space-y-1">
                            <Label className="text-xs text-muted-foreground">Quarter</Label>
                            <p className="text-sm font-medium">{context.current_quarter_label}</p>
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="submission-deadline">Deadline</Label>
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
                            Save Deadline
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={returnDialogOpen} onOpenChange={setReturnDialogOpen}>
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
                                returnForm.setData('return_notes', event.target.value)
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
                                setReturnDialogOpen(false);
                                setSelectedSubmission(null);
                                returnForm.reset();
                            }}
                        >
                            Cancel
                        </Button>
                        <Button onClick={submitReturn} disabled={returnForm.processing}>
                            Confirm Return
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
