import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, Clock3, TriangleAlert, UploadCloud } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { sf1_upload } from '@/routes/registrar/student_directory';

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
}

interface Props {
    students: StudentRow[];
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
    summary,
    last_upload,
}: Props) {
    const uploadForm = useForm<{
        sf1_file: File | null;
    }>({
        sf1_file: null,
    });

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        uploadForm.setData('sf1_file', file);
        uploadForm.post(sf1_upload().url, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
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

    const statusBadge = (status: StudentRow['lis_status']) => {
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
                <Badge variant="destructive">
                    <TriangleAlert className="size-3" />
                    Discrepancy
                </Badge>
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

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="flex flex-col gap-1 border-b sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                        <div className="space-y-1">
                            <CardTitle>Student Directory</CardTitle>
                            <div className="flex flex-wrap items-center gap-2 text-sm">
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
                                />
                                <Button asChild>
                                    <label
                                        htmlFor="sf1-upload-file"
                                        className="cursor-pointer"
                                    >
                                        <UploadCloud className="size-4" />
                                        {uploadForm.processing
                                            ? 'Uploading...'
                                            : 'Upload SF1'}
                                    </label>
                                </Button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {lastUploadLabel()}
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">LRN</TableHead>
                                    <TableHead>Student</TableHead>
                                    <TableHead>Grade and Section</TableHead>
                                    <TableHead className="pr-6">
                                        LIS Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.map((student) => (
                                    <TableRow key={student.id}>
                                        <TableCell className="pl-6">
                                            {student.lrn}
                                        </TableCell>
                                        <TableCell>
                                            {student.student_name}
                                        </TableCell>
                                        <TableCell>
                                            {student.grade_section}
                                        </TableCell>
                                        <TableCell className="pr-6">
                                            {statusBadge(student.lis_status)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {students.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="h-24 text-center text-sm text-muted-foreground"
                                        >
                                            No students found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
