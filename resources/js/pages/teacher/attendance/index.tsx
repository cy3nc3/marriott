import { Head, router } from '@inertiajs/react';
import { Printer, Save } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MonthPicker } from '@/components/ui/month-picker';
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
    status_options: AttendanceStatus[];
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
    const shadeColor = 'rgba(148, 163, 184, 0.9)';
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
                    <polygon points="100,100 100,0 0,100" fill={shadeColor} />
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
    status_options,
}: Props) {
    const [workingStatuses, setWorkingStatuses] = useState<
        Record<string, AttendanceStatus>
    >(() => flattenStatuses(rows, days));
    const [initialStatuses, setInitialStatuses] = useState<
        Record<string, AttendanceStatus>
    >(() => flattenStatuses(rows, days));

    useEffect(() => {
        const flattened = flattenStatuses(rows, days);
        setWorkingStatuses(flattened);
        setInitialStatuses(flattened);
    }, [rows, days]);

    const selectedClassValue = context.selected_subject_assignment_id
        ? String(context.selected_subject_assignment_id)
        : 'class-none';

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Attendance Context</CardTitle>
                            <Badge variant="outline">
                                Active School Year:{' '}
                                {context.active_school_year ??
                                    'No active school year'}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Select
                                    value={selectedClassValue}
                                    onValueChange={handleClassChange}
                                    disabled={!hasClasses || isFeatureLocked}
                                >
                                    <SelectTrigger className="w-full sm:w-64">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {hasClasses ? (
                                            context.class_options.map(
                                                (classOption) => (
                                                    <SelectItem
                                                        key={classOption.id}
                                                        value={String(
                                                            classOption.id,
                                                        )}
                                                    >
                                                        {classOption.label}
                                                    </SelectItem>
                                                ),
                                            )
                                        ) : (
                                            <SelectItem
                                                value="class-none"
                                                disabled
                                            >
                                                No assigned classes
                                            </SelectItem>
                                        )}
                                    </SelectContent>
                                </Select>
                                <MonthPicker
                                    value={context.selected_month}
                                    onValueChange={handleMonthChange}
                                    disabled={isFeatureLocked}
                                    className="w-full sm:w-44"
                                />
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled
                                >
                                    <Printer className="size-4" />
                                    Print SF2
                                </Button>
                                <Button
                                    onClick={saveAttendance}
                                    disabled={
                                        !canEdit || pendingChangesCount < 1
                                    }
                                >
                                    <Save className="size-4" />
                                    Save Attendance
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                {isFeatureLocked && feature_lock.message ? (
                    <Alert>
                        <AlertTitle>Attendance Unavailable</AlertTitle>
                        <AlertDescription>
                            {feature_lock.message}
                        </AlertDescription>
                    </Alert>
                ) : null}
                {isMonthOutOfScope && month_scope.message ? (
                    <Alert>
                        <AlertTitle>Selected Month Is Read Only</AlertTitle>
                        <AlertDescription>
                            {month_scope.message}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Attendance Log</CardTitle>
                            <div className="flex flex-wrap items-center gap-3 text-xs lg:justify-end">
                                <span className="font-medium">Legend:</span>
                                <div className="flex items-center gap-2">
                                    <Sf2MarkCell
                                        status="present"
                                        onClick={() => {}}
                                        disabled
                                        sizeClassName="size-6"
                                    />
                                    <span>Present</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Sf2MarkCell
                                        status="absent"
                                        onClick={() => {}}
                                        disabled
                                        sizeClassName="size-6"
                                    />
                                    <span>Absent (X)</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Sf2MarkCell
                                        status="tardy_late_comer"
                                        onClick={() => {}}
                                        disabled
                                        sizeClassName="size-6"
                                    />
                                    <span>Tardy: Late Comer</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Sf2MarkCell
                                        status="tardy_cutting_classes"
                                        onClick={() => {}}
                                        disabled
                                        sizeClassName="size-6"
                                    />
                                    <span>Tardy: Cutting Classes</span>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
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
