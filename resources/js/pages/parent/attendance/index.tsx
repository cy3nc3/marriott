import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CalendarCheck2 } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
        title: 'Attendance',
        href: '/parent/attendance',
    },
];

type AttendanceRow = {
    date: string;
    subject: string;
    status: string;
    remarks: string | null;
};

interface Props {
    context: {
        student_name: string | null;
        school_year: string | null;
    };
    attendance_rows: AttendanceRow[];
    summary: {
        present: number;
        absent: number;
        tardy_late_comer: number;
        tardy_cutting_classes: number;
    };
    school_year_options: { id: number; name: string; status: string }[];
    selected_school_year_id: number | null;
    is_departed_read_only: boolean;
}

const statusLabel = (status: string): string => {
    if (status === 'present') return 'Present';
    if (status === 'absent') return 'Absent';
    if (status === 'tardy_late_comer') return 'Tardy - Late Comer';
    if (status === 'tardy_cutting_classes') return 'Tardy - Cutting Classes';

    return status;
};

const statusBadgeClass = (status: string): string => {
    if (status === 'present') {
        return 'bg-emerald-500/15 text-emerald-700 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800';
    }

    if (status === 'absent') {
        return 'bg-red-500/15 text-red-700 border-red-200 dark:text-red-400 dark:border-red-800';
    }

    if (status === 'tardy_late_comer' || status === 'tardy_cutting_classes') {
        return 'bg-amber-500/15 text-amber-700 border-amber-200 dark:text-amber-400 dark:border-amber-800';
    }

    return '';
};

export default function ParentAttendance({
    context,
    attendance_rows,
    summary,
    school_year_options,
    selected_school_year_id,
    is_departed_read_only,
}: Props) {
    const handleSchoolYearChange = (value: string) => {
        router.get(
            '/parent/attendance',
            { academic_year_id: Number(value) },
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-col gap-4">
                {is_departed_read_only && (
                    <Alert>
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Read-only historical record</AlertTitle>
                        <AlertDescription>
                            This learner is marked as departed. Attendance shown
                            here is for historical reference.
                        </AlertDescription>
                    </Alert>
                )}

                <Card className="gap-2">
                    <CardContent className="p-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-2">
                                <CalendarCheck2 className="size-4 text-muted-foreground" />
                                <p className="text-sm font-medium">Attendance Log</p>
                                <Badge variant="outline">Parent View</Badge>
                            </div>
                            <div className="flex items-center gap-2">
                                {school_year_options.length > 0 && (
                                    <Select
                                        value={
                                            selected_school_year_id
                                                ? String(selected_school_year_id)
                                                : undefined
                                        }
                                        onValueChange={handleSchoolYearChange}
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="School Year" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {school_year_options.map((schoolYear) => (
                                                <SelectItem
                                                    key={schoolYear.id}
                                                    value={String(schoolYear.id)}
                                                >
                                                    {schoolYear.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                                <Badge variant="secondary">{context.student_name ?? 'No Linked Student'}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Present</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">{summary.present}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Absent</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">{summary.absent}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Late Comer</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">{summary.tardy_late_comer}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Cutting Classes</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">{summary.tardy_cutting_classes}</CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Attendance Entries</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Date</TableHead>
                                    <TableHead className="border-l">Subject</TableHead>
                                    <TableHead className="border-l">Status</TableHead>
                                    <TableHead className="border-l pr-6">Remarks</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {attendance_rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-24 text-center text-muted-foreground">
                                            No attendance records available.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    attendance_rows.map((row, index) => (
                                        <TableRow key={`${row.date}-${row.subject}-${index}`}>
                                            <TableCell className="pl-6">{row.date}</TableCell>
                                            <TableCell className="border-l">{row.subject}</TableCell>
                                            <TableCell className="border-l">
                                                <Badge variant="outline" className={statusBadgeClass(row.status)}>
                                                    {statusLabel(row.status)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-muted-foreground">
                                                {row.remarks || '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

