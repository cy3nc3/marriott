import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock3, TriangleAlert, UploadCloud } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useEffect } from 'react';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import registrar from '@/routes/registrar';
import { sf1_upload } from '@/routes/registrar/student_directory';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

interface StudentRow {
    id: number;
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
    summary,
    last_upload,
}: Props) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const uploadForm = useForm<{
        sf1_file: File | null;
        academic_year_id: string;
    }>({
        sf1_file: null,
        academic_year_id: selected_school_year_id
            ? String(selected_school_year_id)
            : '',
    });
    const canUploadSf1 = uploadForm.data.academic_year_id !== '' && !isHandheld;

    useEffect(() => {
        uploadForm.setData(
            'academic_year_id',
            selected_school_year_id ? String(selected_school_year_id) : '',
        );
    }, [selected_school_year_id]);

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        uploadForm.transform((data) => ({
            ...data,
            sf1_file: file,
        }));
        uploadForm.post(sf1_upload().url, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                uploadForm.transform((data) => data);
                uploadForm.reset('sf1_file');
                event.target.value = '';
            },
        });
    };

    const lastUploadLabel = () => {
        if (!last_upload.at) {
            return 'No SF1 upload yet.';
        }

        const formattedDate = new Date(last_upload.at).toLocaleString();
        if (last_upload.file_name) {
            return `Last SF1 upload: ${formattedDate} (${last_upload.file_name})`;
        }

        return `Last SF1 upload: ${formattedDate}`;
    };

    const statusBadge = (student: StudentRow) => {
        const { lis_status: status, lis_status_reason: reason } = student;

        if (status === 'matched') {
            return (
                <Badge variant="secondary">
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
                            <Badge variant="destructive">
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
            <Badge variant="outline">
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
                                        router.get(
                                            registrar.student_directory.url({
                                                query: {
                                                    academic_year_id:
                                                        value || undefined,
                                                },
                                            }),
                                            {},
                                            {
                                                preserveState: false,
                                                preserveScroll: true,
                                                replace: true,
                                            },
                                        );
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
                            <div className="flex items-center gap-2">
                                <input
                                    id="sf1-upload-file"
                                    type="file"
                                    accept=".csv,.txt"
                                    className="hidden"
                                    onChange={handleFileChange}
                                    disabled={!canUploadSf1}
                                />
                                <Button asChild>
                                    <label
                                        htmlFor="sf1-upload-file"
                                        className={
                                            canUploadSf1
                                                ? 'cursor-pointer'
                                                : 'pointer-events-none cursor-not-allowed opacity-50'
                                        }
                                    >
                                        <UploadCloud className="size-4" />
                                        {uploadForm.processing
                                            ? 'Uploading...'
                                            : 'Upload SF1'}
                                    </label>
                                </Button>
                            </div>
                            {isHandheld ? (
                                <p className="text-xs text-muted-foreground">
                                    SF1 upload is available on desktop only.
                                </p>
                            ) : null}
                            {uploadForm.errors.academic_year_id ? (
                                <p className="text-xs text-destructive">
                                    {uploadForm.errors.academic_year_id}
                                </p>
                            ) : null}
                            {uploadForm.errors.sf1_file ? (
                                <p className="text-xs text-destructive">
                                    {uploadForm.errors.sf1_file}
                                </p>
                            ) : null}
                            <p className="text-xs text-muted-foreground">
                                {lastUploadLabel()}
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
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
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
