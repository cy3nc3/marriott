import { Head, router } from '@inertiajs/react';
import { CheckCircle2, CircleDot, ShieldAlert } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

type PromotionRow = {
    permanent_record_id: number;
    student_id: number;
    student_name: string;
    lrn: string;
    grade_level: string;
    status: 'promoted' | 'completed' | 'conditional' | 'retained';
    failed_subject_count: number;
    progression: string;
};

type SchoolYearOption = {
    id: number;
    name: string;
    status: string;
};

interface Props {
    run_summary: {
        run_at: string | null;
        processed_learners: number;
        passed: number;
        conditional: number;
        retained: number;
    };
    school_years: SchoolYearOption[];
    selected_year: SchoolYearOption | null;
    status_breakdown: {
        passed: PromotionRow[];
        conditional: PromotionRow[];
        retained: PromotionRow[];
    };
}

function statusBadge(status: PromotionRow['status']) {
    if (status === 'completed') {
        return <Badge variant="outline">Completed</Badge>;
    }

    if (status === 'promoted') {
        return (
            <Badge variant="outline" className="border-emerald-200 bg-emerald-500/15 text-emerald-700 dark:border-emerald-800 dark:text-emerald-400">
                Promoted
            </Badge>
        );
    }

    if (status === 'conditional') {
        return (
            <Badge variant="outline" className="border-amber-200 bg-amber-500/15 text-amber-700 dark:border-amber-800 dark:text-amber-400">
                Conditional
            </Badge>
        );
    }

    return (
        <Badge variant="outline" className="border-red-200 bg-red-500/15 text-red-700 dark:border-red-800 dark:text-red-400">
            Retained
        </Badge>
    );
}

function BreakdownTable({
    rows,
    emptyMessage,
    pageSize = 10,
}: {
    rows: PromotionRow[];
    emptyMessage: string;
    pageSize?: number;
}) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));

    useEffect(() => {
        setPage(1);
    }, [rows]);

    const paginatedRows = useMemo(() => {
        const start = (page - 1) * pageSize;
        return rows.slice(start, start + pageSize);
    }, [page, pageSize, rows]);

    return (
        <div>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="pl-6">Learner</TableHead>
                        <TableHead>LRN</TableHead>
                        <TableHead>Grade Level</TableHead>
                        <TableHead>Failed Subjects</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="pr-6">Progression</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.length === 0 ? (
                        <TableRow>
                            <TableCell
                                className="py-8 text-center text-sm text-muted-foreground"
                                colSpan={6}
                            >
                                {emptyMessage}
                            </TableCell>
                        </TableRow>
                    ) : (
                        paginatedRows.map((row) => (
                            <TableRow key={row.permanent_record_id}>
                                <TableCell className="pl-6 font-medium">
                                    {row.student_name}
                                </TableCell>
                                <TableCell>{row.lrn}</TableCell>
                                <TableCell>{row.grade_level}</TableCell>
                                <TableCell>{row.failed_subject_count}</TableCell>
                                <TableCell>{statusBadge(row.status)}</TableCell>
                                <TableCell className="pr-6">
                                    {row.progression}
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
            {rows.length > 0 ? (
                <div className="flex items-center justify-end gap-2 border-t px-4 py-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setPage((prev) => Math.max(1, prev - 1))}
                        disabled={page <= 1}
                    >
                        Previous
                    </Button>
                    <p className="text-xs text-muted-foreground">
                        Page {page} of {totalPages}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setPage((prev) => Math.min(totalPages, prev + 1))
                        }
                        disabled={page >= totalPages}
                    >
                        Next
                    </Button>
                </div>
            ) : null}
        </div>
    );
}

export default function BatchPromotion({
    run_summary,
    school_years,
    selected_year,
    status_breakdown,
}: Props) {
    const safeSummary = run_summary ?? {
        run_at: null,
        processed_learners: 0,
        passed: 0,
        conditional: 0,
        retained: 0,
    };
    const safeSchoolYears = school_years ?? [];
    const safeBreakdown = status_breakdown ?? {
        passed: [],
        conditional: [],
        retained: [],
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
                                    Track promotion outcomes by school year.
                                </p>
                            </div>
                            <div className="w-full lg:w-72">
                                <Select
                                    value={
                                        selected_year
                                            ? String(selected_year.id)
                                            : undefined
                                    }
                                    onValueChange={(value) => {
                                        router.get(
                                            '/registrar/batch-promotion',
                                            {
                                                academic_year_id:
                                                    Number(value),
                                            },
                                            {
                                                preserveScroll: true,
                                                preserveState: true,
                                                replace: true,
                                            },
                                        );
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select school year" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {safeSchoolYears.map((schoolYear) => (
                                            <SelectItem
                                                key={schoolYear.id}
                                                value={String(schoolYear.id)}
                                            >
                                                {schoolYear.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="mb-4 text-sm text-muted-foreground">
                            School Year:{' '}
                            <span className="font-medium text-foreground">
                                {selected_year?.name ?? 'N/A'}
                            </span>
                            {' · '}
                            Last run:{' '}
                            {safeSummary.run_at
                                ? new Date(safeSummary.run_at).toLocaleString()
                                : 'No run yet'}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Processed Learners
                                </p>
                                <p className="text-2xl font-semibold">
                                    {safeSummary.processed_learners}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <CheckCircle2 className="size-3.5" />
                                    Passed
                                </p>
                                <p className="text-2xl font-semibold">
                                    {safeSummary.passed}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <CircleDot className="size-3.5" />
                                    Conditional
                                </p>
                                <p className="text-2xl font-semibold">
                                    {safeSummary.conditional}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <ShieldAlert className="size-3.5" />
                                    Retained
                                </p>
                                <p className="text-2xl font-semibold">
                                    {safeSummary.retained}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Passed Students</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <BreakdownTable
                            rows={safeBreakdown.passed}
                            emptyMessage="No passed students for this school year."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Conditional Students</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <BreakdownTable
                            rows={safeBreakdown.conditional}
                            emptyMessage="No conditional students for this school year."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Retained Students</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <BreakdownTable
                            rows={safeBreakdown.retained}
                            emptyMessage="No retained students for this school year."
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
