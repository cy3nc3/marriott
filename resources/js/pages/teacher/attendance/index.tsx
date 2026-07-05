import { Head, router } from '@inertiajs/react';
import { Download, ListFilter, Save } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { MonthPicker } from '@/components/ui/month-picker';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
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
        href: '/teacher/attendance',
    },
];

type AttendanceStatus =
    | 'present'
    | 'absent'
    | 'tardy_late_comer'
    | 'tardy_cutting_classes';

type ClassOption = {
    id: number;
    label: string;
};

type DayColumn = {
    date: string;
    day: string;
    weekday: string;
};

type AttendanceRow = {
    enrollment_id: number;
    student_name: string;
    statuses: Record<string, AttendanceStatus>;
};

interface Props {
    context: {
        class_options: ClassOption[];
        selected_subject_assignment_id: number | null;
        selected_month: string;
        active_school_year: string | null;
        academic_year_options: { id: number; name: string; status: string }[];
        selected_academic_year_id: number | null;
        is_read_only_historical: boolean;
    };
    feature_lock: {
        is_locked: boolean;
        message: string | null;
    };
    month_scope: {
        is_out_of_scope: boolean;
        message: string | null;
    };
    days: DayColumn[];
    rows: AttendanceRow[];
}

const statusCycle: AttendanceStatus[] = [
    'present',
    'absent',
    'tardy_late_comer',
    'tardy_cutting_classes',
];

const statusLabel: Record<AttendanceStatus, string> = {
    present: 'Present',
    absent: 'Absent',
    tardy_late_comer: 'Tardy - Late Comer',
    tardy_cutting_classes: 'Tardy - Cutting Classes',
};

const keyFor = (enrollmentId: number, date: string): string =>
    `${enrollmentId}|${date}`;

const flattenStatuses = (rows: AttendanceRow[], days: DayColumn[]) => {
    const flattened: Record<string, AttendanceStatus> = {};

    rows.forEach((row) => {
        days.forEach((day) => {
            const date = day.date;
            flattened[keyFor(row.enrollment_id, date)] =
                row.statuses[date] ?? 'present';
        });
    });

    return flattened;
};

const nextStatus = (currentStatus: AttendanceStatus): AttendanceStatus => {
    const currentIndex = statusCycle.indexOf(currentStatus);
    const nextIndex = (currentIndex + 1) % statusCycle.length;

    return statusCycle[nextIndex];
};

function Sf2MarkCell({
    status,
    onClick,
    disabled,
    sizeClassName = 'size-8',
}: {
    status: AttendanceStatus;
    onClick: () => void;
    disabled: boolean;
    sizeClassName?: string;
}) {
    const shadeColor = 'rgba(0, 0, 0, 1)';
    const borderColor = 'currentColor';

    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            className={`relative rounded-none p-0 ${sizeClassName}`}
            onClick={onClick}
            disabled={disabled}
        >
            <svg
                viewBox="0 0 100 100"
                className="absolute inset-0 size-full text-foreground"
                shapeRendering="crispEdges"
                aria-hidden="true"
            >
                {status === 'tardy_late_comer' ? (
                    <polygon points="0,0 100,0 0,100" fill={shadeColor} />
                ) : null}
                {status === 'tardy_cutting_classes' ? (
                    <polygon points="100,0 0,100 100,100" fill={shadeColor} />
                ) : null}
                <line
                    x1="2"
                    y1="98"
                    x2="98"
                    y2="2"
                    stroke={borderColor}
                    strokeWidth="4"
                />
                {status === 'absent' ? (
                    <line
                        x1="2"
                        y1="2"
                        x2="98"
                        y2="98"
                        stroke={borderColor}
                        strokeWidth="4"
                    />
                ) : null}
            </svg>
            <span className="sr-only">{statusLabel[status]}</span>
        </Button>
    );
}

export default function TeacherAttendance({
    context,
    feature_lock,
    month_scope,
    days,
    rows,
}: Props) {
    const initialStatuses = useMemo(
        () => flattenStatuses(rows, days),
        [rows, days],
    );
    const [workingStatuses, setWorkingStatuses] = useState<
        Record<string, AttendanceStatus>
    >(() => initialStatuses);

    const selectedClassValue = context.selected_subject_assignment_id
        ? String(context.selected_subject_assignment_id)
        : 'class-none';
    const selectedAcademicYearValue = context.selected_academic_year_id
        ? String(context.selected_academic_year_id)
        : '';

    const pendingChangesCount = useMemo(() => {
        let count = 0;

        rows.forEach((row) => {
            days.forEach((day) => {
                const cellKey = keyFor(row.enrollment_id, day.date);
                const currentStatus = workingStatuses[cellKey] ?? 'present';
                const initialStatus = initialStatuses[cellKey] ?? 'present';

                if (currentStatus !== initialStatus) {
                    count += 1;
                }
            });
        });

        return count;
    }, [rows, days, workingStatuses, initialStatuses]);

    const isFeatureLocked = feature_lock.is_locked;
    const isMonthOutOfScope = month_scope.is_out_of_scope;
    const hasClasses = context.class_options.length > 0;
    const hasSelectedClass = context.selected_subject_assignment_id !== null;
    const canEdit =
        !isFeatureLocked &&
        !isMonthOutOfScope &&
        !context.is_read_only_historical &&
        hasSelectedClass &&
        rows.length > 0;

    const handleClassChange = (value: string) => {
        if (value === 'class-none') {
            return;
        }

        router.get(
            '/teacher/attendance',
            {
                subject_assignment_id: Number(value),
                month: context.selected_month,
                academic_year_id: context.selected_academic_year_id ?? undefined,
            },
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleMonthChange = (nextMonth: string) => {
        router.get(
            '/teacher/attendance',
            {
                subject_assignment_id:
                    context.selected_subject_assignment_id ?? undefined,
                month: nextMonth,
                academic_year_id: context.selected_academic_year_id ?? undefined,
            },
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleAcademicYearChange = (value: string) => {
        router.get(
            '/teacher/attendance',
            {
                academic_year_id: Number(value),
                month: context.selected_month,
            },
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const cycleCellStatus = (enrollmentId: number, date: string) => {
        const cellKey = keyFor(enrollmentId, date);
        const currentStatus = workingStatuses[cellKey] ?? 'present';

        setWorkingStatuses((currentMap) => ({
            ...currentMap,
            [cellKey]: nextStatus(currentStatus),
        }));
    };

    const saveAttendance = () => {
        if (!hasSelectedClass) {
            return;
        }

        const entries: Array<{
            enrollment_id: number;
            date: string;
            status: AttendanceStatus;
        }> = [];

        rows.forEach((row) => {
            days.forEach((day) => {
                const cellKey = keyFor(row.enrollment_id, day.date);
                const currentStatus = workingStatuses[cellKey] ?? 'present';
                const initialStatus = initialStatuses[cellKey] ?? 'present';

                if (currentStatus !== initialStatus) {
                    entries.push({
                        enrollment_id: row.enrollment_id,
                        date: day.date,
                        status: currentStatus,
                    });
                }
            });
        });

        router.post(
            '/teacher/attendance',
            {
                subject_assignment_id: context.selected_subject_assignment_id,
                month: context.selected_month,
                entries,
            },
            {
                preserveScroll: true,
            },
        );
    };

    const triggerExport = (format: 'xlsx' | 'csv' | 'xls') => {
        if (!hasSelectedClass) {
            return;
        }

        const params = new URLSearchParams({
            subject_assignment_id: String(context.selected_subject_assignment_id),
            month: context.selected_month,
            format,
        });

        window.location.assign(`/teacher/attendance/export-sf2?${params.toString()}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1">
                                <CardTitle>Attendance Log</CardTitle>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline">
                                        Active School Year:{' '}
                                        {context.active_school_year ?? 'No active school year'}
                                    </Badge>
                                    <Badge variant="secondary" className="bg-muted text-muted-foreground">
                                        {context.class_options.find(c => String(c.id) === selectedClassValue)?.label ?? 'No Class Selected'}
                                    </Badge>
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button variant="outline" className="gap-2">
                                            <ListFilter className="size-4" />
                                            Filters
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-80" align="end">
                                        <div className="grid gap-4">
                                            <h4 className="font-medium leading-none">Context Filters</h4>
                                            <div className="grid gap-4">
                                                <div className="grid gap-2">
                                                    <Label>School Year</Label>
                                                    <Select
                                                        value={selectedAcademicYearValue}
                                                        onValueChange={handleAcademicYearChange}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="School Year" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {context.academic_year_options.map((year) => (
                                                                <SelectItem key={year.id} value={String(year.id)}>
                                                                    {year.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Assigned Class</Label>
                                                    <Select
                                                        value={selectedClassValue}
                                                        onValueChange={handleClassChange}
                                                        disabled={!hasClasses || isFeatureLocked}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {hasClasses ? (
                                                                context.class_options.map((classOption) => (
                                                                    <SelectItem key={classOption.id} value={String(classOption.id)}>
                                                                        {classOption.label}
                                                                    </SelectItem>
                                                                ))
                                                            ) : (
                                                                <SelectItem value="class-none" disabled>
                                                                    No assigned classes
                                                                </SelectItem>
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Month</Label>
                                                    <MonthPicker
                                                        value={context.selected_month}
                                                        onValueChange={handleMonthChange}
                                                        disabled={isFeatureLocked}
                                                        className="w-full"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" className="gap-2" disabled={!hasSelectedClass || rows.length === 0}>
                                            <Download className="size-4" />
                                            Export SF2
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem onClick={() => triggerExport('xlsx')}>
                                            Export as Excel (.xlsx)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => triggerExport('csv')}>
                                            Export as CSV (.csv)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => triggerExport('xls')}>
                                            Export as Excel 97-2003 (.xls)
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                {pendingChangesCount > 0 && (
                                    <Button onClick={saveAttendance} className="gap-2">
                                        <Save className="size-4" />
                                        Save {pendingChangesCount} Change(s)
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {isFeatureLocked && feature_lock.message ? (
                            <Alert className="mb-6">
                                <AlertTitle>Attendance Unavailable</AlertTitle>
                                <AlertDescription>{feature_lock.message}</AlertDescription>
                            </Alert>
                        ) : null}
                        {isMonthOutOfScope && month_scope.message ? (
                            <Alert className="mb-6">
                                <AlertTitle>Selected Month Is Read Only</AlertTitle>
                                <AlertDescription>{month_scope.message}</AlertDescription>
                            </Alert>
                        ) : null}

                        <div className="mb-6 flex flex-wrap items-center gap-3 text-xs">
                            <span className="font-medium text-muted-foreground">Legend:</span>
                            <div className="flex items-center gap-2">
                                <Sf2MarkCell status="present" onClick={() => {}} disabled sizeClassName="size-5" />
                                <span>Present</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Sf2MarkCell status="absent" onClick={() => {}} disabled sizeClassName="size-5" />
                                <span>Absent (X)</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Sf2MarkCell status="tardy_late_comer" onClick={() => {}} disabled sizeClassName="size-5" />
                                <span>Tardy: Late Comer</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Sf2MarkCell status="tardy_cutting_classes" onClick={() => {}} disabled sizeClassName="size-5" />
                                <span>Tardy: Cutting Classes</span>
                            </div>
                        </div>

                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="sticky left-0 z-10 min-w-52 border-r bg-background pl-6">
                                            &nbsp;
                                        </TableHead>
                                        {days.map((day) => (
                                            <TableHead
                                                key={day.date}
                                                className="border-l text-center"
                                            >
                                                {day.day}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                    <TableRow>
                                        <TableHead className="sticky left-0 z-10 border-r bg-background pl-6 text-xs text-muted-foreground">
                                            Students
                                        </TableHead>
                                        {days.map((day) => (
                                            <TableHead
                                                key={`${day.date}-weekday`}
                                                className="border-l text-center text-[10px] text-muted-foreground"
                                            >
                                                {day.weekday}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={days.length + 1}
                                                className="py-10 text-center text-sm text-muted-foreground"
                                            >
                                                {isMonthOutOfScope
                                                    ? 'Selected month is outside the school year date range.'
                                                    : 'No enrolled students found for this class and month.'}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row) => (
                                            <TableRow key={row.enrollment_id}>
                                                <TableCell className="sticky left-0 z-10 border-r bg-background pl-6 font-medium whitespace-nowrap">
                                                    {row.student_name}
                                                </TableCell>
                                                {days.map((day) => {
                                                    const cellKey = keyFor(
                                                        row.enrollment_id,
                                                        day.date,
                                                    );
                                                    const status =
                                                        workingStatuses[
                                                            cellKey
                                                        ] ?? 'present';

                                                    return (
                                                        <TableCell
                                                            key={`${row.enrollment_id}-${day.date}`}
                                                            className="border-l p-0 text-center"
                                                        >
                                                            <Sf2MarkCell
                                                                status={status}
                                                                onClick={() =>
                                                                    cycleCellStatus(
                                                                        row.enrollment_id,
                                                                        day.date,
                                                                    )
                                                                }
                                                                disabled={
                                                                    !canEdit
                                                                }
                                                            />
                                                        </TableCell>
                                                    );
                                                })}
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        <p className="mt-3 text-xs text-muted-foreground">
                            Click each box to cycle marks: Present to Absent to
                            Tardy (Late Comer) to Tardy (Cutting Classes).
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
