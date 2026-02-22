import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, Printer, Search } from 'lucide-react';
import { useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { student_ledgers } from '@/routes/finance';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Ledgers',
        href: '/finance/student-ledgers',
    },
];

type StudentOption = {
    id: number;
    lrn: string;
    name: string;
};

type SelectedStudent = {
    id: number;
    name: string;
    lrn: string;
    grade_and_section: string;
    guardian_name: string | null;
    payment_plan: string | null;
    payment_plan_label: string;
    outstanding_balance: number;
};

type DueScheduleRow = {
    id: number;
    description: string;
    due_date: string | null;
    due_date_label: string | null;
    amount_due: number;
    amount_paid: number;
    status: string;
    status_label: string;
};

type LedgerEntryRow = {
    id: number;
    date: string | null;
    date_label: string | null;
    reference: string;
    entry_type: 'charge' | 'payment' | 'discount' | 'adjustment';
    entry_type_label: string;
    charge: number;
    payment: number;
    running_balance: number;
};

type Summary = {
    total_charges: number;
    total_payments: number;
    outstanding_balance: number;
};

type Filters = {
    search: string | null;
    student_id: number | null;
    entry_type: 'all' | 'charge' | 'payment' | 'discount' | 'adjustment';
    date_from: string | null;
    date_to: string | null;
    show_paid_dues: boolean;
};

interface Props {
    students: StudentOption[];
    selected_student: SelectedStudent | null;
    dues_schedule: DueScheduleRow[];
    ledger_entries: LedgerEntryRow[];
    summary: Summary;
    filters: Filters;
}

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount || 0);

const parseDateInput = (value: string | null) => {
    if (!value) {
        return undefined;
    }

    const parsedDate = new Date(`${value}T00:00:00`);
    if (Number.isNaN(parsedDate.getTime())) {
        return undefined;
    }

    return parsedDate;
};

export default function StudentLedgers({
    students,
    selected_student,
    dues_schedule,
    ledger_entries,
    summary,
    filters,
}: Props) {
    const initialFromDate = parseDateInput(filters.date_from);
    const initialToDate = parseDateInput(filters.date_to);
    const initialDateRange =
        initialFromDate || initialToDate
            ? {
                  from: initialFromDate,
                  to: initialToDate,
              }
            : undefined;

    const [searchQuery, setSearchQuery] = useState(filters.search ?? '');
    const [selectedStudentId, setSelectedStudentId] = useState(
        selected_student?.id
            ? String(selected_student.id)
            : filters.student_id
              ? String(filters.student_id)
              : '',
    );
    const [showPaidDues, setShowPaidDues] = useState(filters.show_paid_dues);
    const [entryTypeFilter, setEntryTypeFilter] = useState(filters.entry_type);
    const [entryDateRange, setEntryDateRange] = useState<DateRange | undefined>(
        initialDateRange,
    );

    const applyFilters = (
        studentId = selectedStudentId,
        paidFlag = showPaidDues,
    ) => {
        router.get(
            student_ledgers.url({
                query: {
                    search: searchQuery || undefined,
                    student_id: studentId || undefined,
                    show_paid_dues: paidFlag ? 1 : undefined,
                    entry_type:
                        entryTypeFilter === 'all' ? undefined : entryTypeFilter,
                    date_from: entryDateRange?.from
                        ? format(entryDateRange.from, 'yyyy-MM-dd')
                        : undefined,
                    date_to: entryDateRange?.to
                        ? format(entryDateRange.to, 'yyyy-MM-dd')
                        : undefined,
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

    const handleSelectStudent = (value: string) => {
        setSelectedStudentId(value);
        applyFilters(value);
    };

    const handleToggleShowPaid = (checked: boolean) => {
        setShowPaidDues(checked);
        applyFilters(selectedStudentId, checked);
    };

    const handleEntryTypeChange = (
        value: 'all' | 'charge' | 'payment' | 'discount' | 'adjustment',
    ) => {
        setEntryTypeFilter(value);
        router.get(
            student_ledgers.url({
                query: {
                    search: searchQuery || undefined,
                    student_id: selectedStudentId || undefined,
                    show_paid_dues: showPaidDues ? 1 : undefined,
                    entry_type: value === 'all' ? undefined : value,
                    date_from: entryDateRange?.from
                        ? format(entryDateRange.from, 'yyyy-MM-dd')
                        : undefined,
                    date_to: entryDateRange?.to
                        ? format(entryDateRange.to, 'yyyy-MM-dd')
                        : undefined,
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

    const resetEntryFilters = () => {
        setEntryTypeFilter('all');
        setEntryDateRange(undefined);

        router.get(
            student_ledgers.url({
                query: {
                    search: searchQuery || undefined,
                    student_id: selectedStudentId || undefined,
                    show_paid_dues: showPaidDues ? 1 : undefined,
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

    const dueBadgeVariant = (status: string) => {
        if (status === 'paid') {
            return 'secondary';
        }

        if (status === 'partially_paid') {
            return 'default';
        }

        return 'outline';
    };

    const ledgerBadgeVariant = (entryType: string) => {
        if (entryType === 'payment') {
            return 'secondary';
        }

        if (entryType === 'discount') {
            return 'default';
        }

        return 'outline';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Ledgers" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <CardTitle>Ledger Lookup</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-3 lg:grid-cols-[1fr_20rem_auto]">
                            <div className="relative">
                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search by student name or LRN"
                                    className="pl-9"
                                    value={searchQuery}
                                    onChange={(event) =>
                                        setSearchQuery(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            event.preventDefault();
                                            applyFilters();
                                        }
                                    }}
                                />
                            </div>
                            <Select
                                value={selectedStudentId}
                                onValueChange={handleSelectStudent}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select student" />
                                </SelectTrigger>
                                <SelectContent>
                                    {students.map((student) => (
                                        <SelectItem
                                            key={student.id}
                                            value={String(student.id)}
                                        >
                                            {student.name} ({student.lrn})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                onClick={() => applyFilters()}
                            >
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="gap-2 lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>Student Ledger Profile</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            {selected_student ? (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Student
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.name}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                LRN
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.lrn}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Grade and Section
                                            </p>
                                            <p className="text-sm font-medium">
                                                {
                                                    selected_student.grade_and_section
                                                }
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Guardian
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.guardian_name ||
                                                    '-'}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Payment Plan
                                            </p>
                                            <p className="text-sm font-medium">
                                                {
                                                    selected_student.payment_plan_label
                                                }
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Outstanding Balance
                                            </p>
                                            <p className="text-sm font-semibold">
                                                {formatCurrency(
                                                    selected_student.outstanding_balance,
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-4 flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            type="button"
                                            onClick={() => window.print()}
                                        >
                                            <Printer className="size-4" />
                                            Print SOA
                                        </Button>
                                        <Button variant="outline" type="button">
                                            <Download className="size-4" />
                                            Export
                                        </Button>
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Search and select a student to view ledger
                                    details.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-2">
                                    <CardTitle>Dues Schedule</CardTitle>
                                    {selected_student && (
                                        <Badge variant="outline">
                                            Plan:{' '}
                                            {
                                                selected_student.payment_plan_label
                                            }
                                        </Badge>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Switch
                                        id="show-paid-dues"
                                        checked={showPaidDues}
                                        onCheckedChange={handleToggleShowPaid}
                                    />
                                    <Label htmlFor="show-paid-dues">
                                        Show Paid
                                    </Label>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Due Date
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Description
                                        </TableHead>
                                        <TableHead className="border-l text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {dues_schedule.map((due) => (
                                        <TableRow key={due.id}>
                                            <TableCell className="pl-6">
                                                {due.due_date_label || '-'}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {due.description}
                                            </TableCell>
                                            <TableCell className="border-l text-right">
                                                {formatCurrency(due.amount_due)}
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                <Badge
                                                    variant={dueBadgeVariant(
                                                        due.status,
                                                    )}
                                                >
                                                    {due.status_label}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {dues_schedule.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                No dues found for this student.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Ledger Entries</CardTitle>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Select
                                    value={entryTypeFilter}
                                    onValueChange={handleEntryTypeChange}
                                >
                                    <SelectTrigger className="w-full sm:w-44">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Entry Types
                                        </SelectItem>
                                        <SelectItem value="charge">
                                            Charges
                                        </SelectItem>
                                        <SelectItem value="payment">
                                            Payments
                                        </SelectItem>
                                        <SelectItem value="discount">
                                            Discounts
                                        </SelectItem>
                                        <SelectItem value="adjustment">
                                            Adjustments
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <DateRangePicker
                                    dateRange={entryDateRange}
                                    setDateRange={setEntryDateRange}
                                    className="w-fit max-w-full"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={resetEntryFilters}
                                >
                                    Reset
                                </Button>
                                <Button
                                    type="button"
                                    onClick={() => applyFilters()}
                                >
                                    Apply
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Date</TableHead>
                                    <TableHead className="border-l">
                                        Reference
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Entry Type
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Charge
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Payment
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Running Balance
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {ledger_entries.map((entry) => (
                                    <TableRow key={entry.id}>
                                        <TableCell className="pl-6">
                                            {entry.date_label || '-'}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {entry.reference}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Badge
                                                variant={ledgerBadgeVariant(
                                                    entry.entry_type,
                                                )}
                                            >
                                                {entry.entry_type_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="border-l text-right">
                                            {entry.charge > 0
                                                ? formatCurrency(entry.charge)
                                                : '-'}
                                        </TableCell>
                                        <TableCell className="border-l text-right">
                                            {entry.payment > 0
                                                ? formatCurrency(entry.payment)
                                                : '-'}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            {formatCurrency(
                                                entry.running_balance,
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {ledger_entries.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No ledger entries found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                    <div className="grid gap-2 border-t p-4 text-sm sm:grid-cols-3">
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Total Charges
                            </p>
                            <p className="font-medium">
                                {formatCurrency(summary.total_charges)}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Total Payments
                            </p>
                            <p className="font-medium">
                                {formatCurrency(summary.total_payments)}
                            </p>
                        </div>
                        <div className="space-y-1 text-left sm:text-right">
                            <p className="text-muted-foreground">
                                Outstanding Balance
                            </p>
                            <p className="font-semibold">
                                {formatCurrency(summary.outstanding_balance)}
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
