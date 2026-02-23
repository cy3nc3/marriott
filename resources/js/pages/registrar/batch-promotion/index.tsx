import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, CircleDot, RotateCcw, ShieldAlert, UserCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Batch Promotion',
        href: '/registrar/batch-promotion',
    },
];

type QueueRow = {
    permanent_record_id: number;
    student_id: number;
    student_name: string;
    lrn: string;
    failed_subject_count: number;
    school_year?: string;
    grade_level?: string;
    source_year?: string;
    target_year?: string;
    recorded_at?: string;
};

type GradeIssueRow = {
    student_id: number;
    student_name: string;
    lrn: string;
    issue: string;
};

interface Props {
    run_summary: {
        run_at: string | null;
        processed_learners: number;
        promoted: number;
        conditional: number;
        retained: number;
        completed: number;
        conflicts: number;
        grade_completeness_issue_count: number;
    };
    conditional_queue: QueueRow[];
    held_for_review_queue: QueueRow[];
    grade_completeness_issues: GradeIssueRow[];
    source_year: {
        id: number;
        name: string;
    } | null;
    target_year: {
        id: number;
        name: string;
    } | null;
}

export default function BatchPromotion({
    run_summary,
    conditional_queue,
    held_for_review_queue,
    grade_completeness_issues,
    source_year,
    target_year,
}: Props) {
    const [reviewDialogOpen, setReviewDialogOpen] = useState(false);
    const [selectedCase, setSelectedCase] = useState<QueueRow | null>(null);

    const reviewForm = useForm({
        permanent_record_id: 0,
        decision: 'promoted',
        note: '',
    });

    const openReviewDialog = (row: QueueRow) => {
        setSelectedCase(row);
        reviewForm.setData({
            permanent_record_id: row.permanent_record_id,
            decision: 'promoted',
            note: '',
        });
        setReviewDialogOpen(true);
    };

    const submitReview = () => {
        reviewForm.post('/registrar/batch-promotion/review', {
            preserveScroll: true,
            onSuccess: () => {
                setReviewDialogOpen(false);
                setSelectedCase(null);
                reviewForm.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Batch Promotion" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <CardTitle>Batch Promotion Monitor</CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Source: {source_year?.name ?? 'N/A'}
                                    {' -> '}
                                    Target: {target_year?.name ?? 'N/A'}
                                </p>
                            </div>
                            <div className="text-sm text-muted-foreground">
                                Last run:{' '}
                                {run_summary.run_at
                                    ? new Date(run_summary.run_at).toLocaleString()
                                    : 'No run yet'}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Processed Learners
                                </p>
                                <p className="text-2xl font-semibold">
                                    {run_summary.processed_learners}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Promoted / Completed
                                </p>
                                <p className="text-2xl font-semibold">
                                    {run_summary.promoted + run_summary.completed}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Conditional / Retained
                                </p>
                                <p className="text-2xl font-semibold">
                                    {run_summary.conditional + run_summary.retained}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Blockers / Conflicts
                                </p>
                                <p className="text-2xl font-semibold">
                                    {run_summary.grade_completeness_issue_count +
                                        run_summary.conflicts}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Conditional Queue</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Learner</TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>Failed Subjects</TableHead>
                                    <TableHead>School Year</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {conditional_queue.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-8 text-center text-sm text-muted-foreground"
                                            colSpan={5}
                                        >
                                            No unresolved conditional learners.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    conditional_queue.map((row) => (
                                        <TableRow key={row.permanent_record_id}>
                                            <TableCell className="pl-6 font-medium">
                                                {row.student_name}
                                            </TableCell>
                                            <TableCell>{row.lrn}</TableCell>
                                            <TableCell>
                                                {row.failed_subject_count}
                                            </TableCell>
                                            <TableCell>
                                                {row.school_year ?? row.source_year}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Badge variant="outline">
                                                    <CircleDot className="size-3" />
                                                    Conditional
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Held for Registrar Review</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Learner</TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>School Year</TableHead>
                                    <TableHead>Failed Subjects</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {held_for_review_queue.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-8 text-center text-sm text-muted-foreground"
                                            colSpan={5}
                                        >
                                            No held review cases.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    held_for_review_queue.map((row) => (
                                        <TableRow key={row.permanent_record_id}>
                                            <TableCell className="pl-6 font-medium">
                                                {row.student_name}
                                            </TableCell>
                                            <TableCell>{row.lrn}</TableCell>
                                            <TableCell>
                                                {row.school_year}
                                            </TableCell>
                                            <TableCell>
                                                {row.failed_subject_count}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openReviewDialog(row)
                                                    }
                                                >
                                                    <RotateCcw className="size-4" />
                                                    Resolve
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Grade Completeness Issues</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Learner</TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead className="pr-6">Issue</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {grade_completeness_issues.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-8 text-center text-sm text-muted-foreground"
                                            colSpan={3}
                                        >
                                            No grade completeness issues from the
                                            latest run.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    grade_completeness_issues.map((issue) => (
                                        <TableRow
                                            key={`${issue.student_id}-${issue.issue}`}
                                        >
                                            <TableCell className="pl-6 font-medium">
                                                {issue.student_name}
                                            </TableCell>
                                            <TableCell>{issue.lrn}</TableCell>
                                            <TableCell className="pr-6 text-sm text-muted-foreground">
                                                {issue.issue}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={reviewDialogOpen} onOpenChange={setReviewDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Resolve Review Case</DialogTitle>
                        <DialogDescription>
                            {selectedCase?.student_name} ({selectedCase?.lrn})
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label>Decision</Label>
                            <Select
                                value={reviewForm.data.decision}
                                onValueChange={(value) =>
                                    reviewForm.setData(
                                        'decision',
                                        value as 'promoted' | 'retained',
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="promoted">
                                        <div className="flex items-center gap-2">
                                            <UserCheck className="size-4" />
                                            Promote
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="retained">
                                        <div className="flex items-center gap-2">
                                            <ShieldAlert className="size-4" />
                                            Retain
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Resolution Note</Label>
                            <Input
                                value={reviewForm.data.note}
                                onChange={(event) =>
                                    reviewForm.setData('note', event.target.value)
                                }
                                placeholder="State the registrar decision context"
                            />
                            {reviewForm.errors.note && (
                                <p className="text-sm text-destructive">
                                    {reviewForm.errors.note}
                                </p>
                            )}
                        </div>

                        <div className="rounded-md border p-3 text-sm text-muted-foreground">
                            <p className="flex items-center gap-2">
                                <AlertTriangle className="size-4" />
                                This action closes the hold case and updates the
                                learner progression record.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setReviewDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={submitReview}
                            disabled={reviewForm.processing}
                        >
                            <CheckCircle2 className="size-4" />
                            Confirm Resolution
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
