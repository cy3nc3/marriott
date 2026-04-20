import { Head, router, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock3,
    Printer,
    RefreshCw,
    TriangleAlert,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import registrar from '@/routes/registrar';
import {
    assessment,
    regenerate_activation_codes,
} from '@/routes/registrar/enrollment';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

interface StudentRow {
    id: number;
    enrollment_id: number | null;
    lrn: string;
    student_name: string;
    grade_section: string;
    lis_status: 'matched' | 'pending' | 'discrepancy';
    lis_status_reason: string | null;
}

interface Props {
    students: {
        data: StudentRow[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    school_year_options: {
        id: number;
        name: string;
        status: string;
    }[];
    selected_school_year_id: number | null;
    filters: {
        search?: string;
        academic_year_id?: number;
    };
    summary: {
        matched: number;
        pending: number;
        discrepancy: number;
    };
    last_upload: {
        at: string | null;
        file_name: string | null;
    };
}

export default function StudentDirectory({
    students,
    school_year_options,
    selected_school_year_id,
    filters,
    summary,
}: Props) {
    const { ui, flash } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const openedAssessmentUrlRef = useRef<string | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const searchSuggestions = students.data.map((student) => ({
        id: student.id,
        label: student.student_name,
        value: student.student_name,
        description: `LRN: ${student.lrn}`,
        keywords: student.lrn,
    }));

    useEffect(() => {
        const assessmentPrintUrl =
            typeof flash.assessment_print_url === 'string' &&
            flash.assessment_print_url.length > 0
                ? flash.assessment_print_url
                : null;

        if (!assessmentPrintUrl) {
            return;
        }

        if (openedAssessmentUrlRef.current === assessmentPrintUrl) {
            return;
        }

        openedAssessmentUrlRef.current = assessmentPrintUrl;
        window.open(assessmentPrintUrl, '_blank', 'noopener,noreferrer');
    }, [flash.assessment_print_url]);

    const applyFilters = (academicYearId?: string, search?: string) => {
        router.get(
            registrar.student_directory.url({
                query: {
                    academic_year_id: academicYearId || undefined,
                    search: search || undefined,
                    page: undefined,
                },
            }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const openAssessmentForm = (enrollmentId: number | null) => {
        if (!enrollmentId) {
            return;
        }

        window.open(
            assessment(enrollmentId).url,
            '_blank',
            'noopener,noreferrer',
        );
    };

    const regenerateActivationCodes = (enrollmentId: number | null) => {
        if (!enrollmentId) {
            return;
        }

        router.post(
            regenerate_activation_codes(enrollmentId).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const statusBadge = (student: StudentRow) => {
        const { lis_status: status, lis_status_reason: reason } = student;

        if (status === 'matched') {
            return (
                <Badge variant="outline" className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">
                    <CheckCircle2 className="size-3" />
                    Matched
                </Badge>
            );
        }

        if (status === 'discrepancy') {
            return (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <span className="inline-flex">
                            <Badge variant="outline" className="bg-red-500/15 text-red-700 hover:bg-red-500/25 dark:text-red-400 border-red-200 dark:border-red-800">
                                <TriangleAlert className="size-3" />
                                Discrepancy
                            </Badge>
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{reason ?? 'Needs review'}</TooltipContent>
                </Tooltip>
            );
        }

        return (
            <Badge variant="outline" className="bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400 border-amber-200 dark:border-amber-800">
                <Clock3 className="size-3" />
                Pending
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Directory" />

            <div className="flex flex-col gap-4">
                <Card>
                    <CardHeader className="flex flex-col gap-1 border-b sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div className="space-y-1">
                            <CardTitle>Student Directory</CardTitle>
                            <div className="flex flex-wrap items-center gap-2">
                                <Select
                                    value={
                                        selected_school_year_id
                                            ? String(selected_school_year_id)
                                            : ''
                                    }
                                    onValueChange={(value) => {
                                        applyFilters(value, searchQuery);
                                    }}
                                >
                                    <SelectTrigger className="w-[13rem]">
                                        <SelectValue placeholder="School Year" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {school_year_options.map(
                                            (schoolYear) => (
                                                <SelectItem
                                                    key={schoolYear.id}
                                                    value={String(
                                                        schoolYear.id,
                                                    )}
                                                >
                                                    {schoolYear.name}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <SearchAutocompleteInput
                                    wrapperClassName="w-full sm:w-[18rem]"
                                    placeholder="Search by LRN or name..."
                                    value={searchQuery}
                                    onValueChange={(value) => {
                                        setSearchQuery(value);
                                        applyFilters(
                                            selected_school_year_id
                                                ? String(
                                                      selected_school_year_id,
                                                  )
                                                : '',
                                            value,
                                        );
                                    }}
                                    suggestions={searchSuggestions}
                                    showSuggestions={false}
                                />
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                <Badge variant="secondary">
                                    Matched: {summary.matched}
                                </Badge>
                                <Badge variant="outline">
                                    Pending Match: {summary.pending}
                                </Badge>
                                <Badge variant="destructive">
                                    Discrepancy: {summary.discrepancy}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex flex-col items-end gap-2 text-right">
                            <p className="max-w-xs text-xs text-muted-foreground">
                                Inbound SF1 sync is disabled. Export SF1
                                reference from Enrollment for LIS posting.
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        {isHandheld ? (
                            <div className="space-y-2.5 p-3">
                                {students.data.length === 0 ? (
                                    <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                        No students found.
                                    </div>
                                ) : (
                                    students.data.map((student) => (
                                        <div
                                            key={student.id}
                                            className="space-y-1 rounded-md border p-2.5"
                                        >
                                            <p className="text-sm font-semibold">
                                                {student.student_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                LRN: {student.lrn}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {student.grade_section}
                                            </p>
                                            <div>{statusBadge(student)}</div>
                                            <div className="flex items-center gap-2 pt-1">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={
                                                        !student.enrollment_id
                                                    }
                                                    onClick={() =>
                                                        openAssessmentForm(
                                                            student.enrollment_id,
                                                        )
                                                    }
                                                >
                                                    <Printer className="size-4" />
                                                    Print
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={
                                                        !student.enrollment_id
                                                    }
                                                    onClick={() =>
                                                        regenerateActivationCodes(
                                                            student.enrollment_id,
                                                        )
                                                    }
                                                >
                                                    <RefreshCw className="size-4" />
                                                    Regenerate
                                                </Button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            LRN
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Student
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Grade and Section
                                        </TableHead>
                                        <TableHead className="border-l pr-6">
                                            LIS Status
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="h-24 text-center text-sm text-muted-foreground"
                                            >
                                                No students found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        students.data.map((student) => (
                                            <TableRow key={student.id}>
                                                <TableCell className="pl-6 font-medium">
                                                    {student.lrn}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {student.student_name}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {student.grade_section}
                                                </TableCell>
                                                <TableCell className="border-l pr-6">
                                                    {statusBadge(student)}
                                                </TableCell>
                                                <TableCell className="border-l pr-6">
                                                    <div className="flex justify-end gap-2">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8"
                                                                    disabled={
                                                                        !student.enrollment_id
                                                                    }
                                                                    onClick={() =>
                                                                        openAssessmentForm(
                                                                            student.enrollment_id,
                                                                        )
                                                                    }
                                                                >
                                                                    <Printer className="size-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Print assessment form
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8"
                                                                    disabled={
                                                                        !student.enrollment_id
                                                                    }
                                                                    onClick={() =>
                                                                        regenerateActivationCodes(
                                                                            student.enrollment_id,
                                                                        )
                                                                    }
                                                                >
                                                                    <RefreshCw className="size-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Regenerate activation codes
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    <div className="flex items-center justify-between border-t p-4">
                        <p className="text-sm text-muted-foreground">
                            Showing {students.from ?? 0}-{students.to ?? 0} of{' '}
                            {students.total} entries
                        </p>
                        {students.links.length > 3 && (
                            <div className="flex items-center gap-2">
                                {students.links.map((link, index) => {
                                    let label = link.label;
                                    if (label.includes('Previous')) {
                                        label = 'Previous';
                                    } else if (label.includes('Next')) {
                                        label = 'Next';
                                    } else {
                                        label = label
                                            .replace(/&[^;]+;/g, '')
                                            .trim();
                                    }

                                    return (
                                        <Button
                                            key={`${link.label}-${index}`}
                                            variant="outline"
                                            size="sm"
                                            disabled={!link.url || link.active}
                                            onClick={() => {
                                                if (link.url) {
                                                    router.get(
                                                        link.url,
                                                        {},
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            {label}
                                        </Button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
