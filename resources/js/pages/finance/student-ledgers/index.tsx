import { Head, router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, Printer, Filter, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
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
import type { BreadcrumbItem, SharedData } from '@/types';

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
    assessment_fee_total: number;
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
    assessment_fee_total: number;
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
    overdue_only: boolean;
};

type OverdueAccountRow = {
    student_id: number;
    student_name: string;
    lrn: string;
    overdue_balance: number;
    oldest_due_date: string | null;
    days_overdue: number;
    overdue_items: number;
};

interface Props {
    students: StudentOption[];
    selected_student: SelectedStudent | null;
    dues_schedule: DueScheduleRow[];
    ledger_entries: LedgerEntryRow[];
    summary: Summary;
    overdue_accounts: OverdueAccountRow[];
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
    overdue_accounts,
    filters,
}: Props) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
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
    const [overdueOnly, setOverdueOnly] = useState(filters.overdue_only);
    const [isOverdueModalOpen, setIsOverdueModalOpen] = useState(false);
    const [overdueSearchQuery, setOverdueSearchQuery] = useState('');

    const activeFilterCount = useMemo(() => {
        let count = 0;
        if (entryTypeFilter !== 'all') count++;
        if (entryDateRange?.from || entryDateRange?.to) count++;
        if (showPaidDues) count++;
        if (overdueOnly) count++;
        return count;
    }, [entryTypeFilter, entryDateRange, showPaidDues, overdueOnly]);

    const handleResetFilters = () => {
        setEntryTypeFilter('all');
        setEntryDateRange(undefined);
        setShowPaidDues(false);
        setOverdueOnly(false);

        router.get(
            student_ledgers.url({
                query: {
                    search: searchQuery || undefined,
                    student_id: selectedStudentId || undefined,
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

    const searchSuggestions = students.map((student) => ({
        id: student.id,
        label: student.name,
        value: student.name,
        description: `LRN: ${student.lrn}`,
        keywords: student.lrn,
    }));

    const filteredOverdueAccounts = useMemo(() => {
        const query = overdueSearchQuery.trim().toLowerCase();
        if (query === '') {
            return overdue_accounts;
        }

        return overdue_accounts.filter((row) =>
            row.student_name.toLowerCase().includes(query)
            || row.lrn.toLowerCase().includes(query),
        );
    }, [overdue_accounts, overdueSearchQuery]);

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
                    overdue_only: overdueOnly ? 1 : undefined,
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
                    overdue_only: overdueOnly ? 1 : undefined,
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
                    overdue_only: overdueOnly ? 1 : undefined,
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
            return 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        }

        if (status === 'partially_paid') {
            return 'bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        }

        if (status === 'overdue') {
            return 'bg-red-500/15 text-red-700 hover:bg-red-500/25 dark:text-red-400 border-red-200 dark:border-red-800';
        }

        return '';
    };

    const ledgerBadgeVariant = (entryType: string) => {
        if (entryType === 'payment') {
            return 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        }

        if (entryType === 'discount') {
            return 'bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        }

        return '';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Ledgers" />

            <div className="flex flex-col gap-4">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <CardTitle>Ledger Lookup</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-3 lg:grid-cols-[1fr_20rem_auto]">
                            <SearchAutocompleteInput
                                placeholder="Search by student name or LRN"
                                value={searchQuery}
                                onValueChange={setSearchQuery}
                                suggestions={searchSuggestions}
                                onEnterPress={() => applyFilters()}
                                onSelectSuggestion={(option) => {
                                    const selectedId = String(option.id);
                                    const selectedSearch =
                                        option.value ?? option.label;

                                    setSearchQuery(selectedSearch);
                                    setSelectedStudentId(selectedId);

                                    router.get(
                                        student_ledgers.url({
                                            query: {
                                                search: selectedSearch,
                                                student_id: selectedId,
                                                show_paid_dues: showPaidDues
                                                    ? 1
                                                    : undefined,
                                                entry_type:
                                                    entryTypeFilter === 'all'
                                                        ? undefined
                                                        : entryTypeFilter,
                                                date_from: entryDateRange?.from
                                                    ? format(
                                                          entryDateRange.from,
                                                          'yyyy-MM-dd',
                                                      )
                                                    : undefined,
                                                date_to: entryDateRange?.to
                                                    ? format(
                                                          entryDateRange.to,
                                                          'yyyy-MM-dd',
                                                      )
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
                                }}
                            />
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
                        <div className="mt-3 flex justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsOverdueModalOpen(true)}
                            >
                                Overdue Accounts
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
                                                Assessment Fee Total
                                            </p>
                                            <p className="text-sm font-medium">
                                                {formatCurrency(
                                                    selected_student.assessment_fee_total,
                                                )}
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
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="outline" className="gap-2">
                                                    <Download className="size-4" />
                                                    Export / Print
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => window.print()}>
                                                    <Printer className="mr-2 size-4" />
                                                    Print SOA (PDF)
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => { /* Logic for CSV/XLSX export if available */ }}>
                                                    <Download className="mr-2 size-4" />
                                                    Export as Excel
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
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
                            {isHandheld ? (
                                <div className="space-y-2.5 p-3">
                                    {dues_schedule.length === 0 ? (
                                        <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                            No dues found for this student.
                                        </div>
                                    ) : (
                                        dues_schedule.map((due) => (
                                            <div
                                                key={due.id}
                                                className="space-y-1 rounded-md border p-3"
                                            >
                                                <p className="text-sm font-medium">
                                                    {due.description}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Due:{' '}
                                                    {due.due_date_label || '-'}
                                                </p>
                                                <p className="text-sm font-semibold">
                                                    {formatCurrency(
                                                        due.amount_due,
                                                    )}
                                                </p>
                                                <Badge
                                                    variant="outline"
                                                    className={dueBadgeVariant(
                                                        due.status,
                                                    )}
                                                >
                                                    {due.status_label}
                                                </Badge>
                                            </div>
                                        ))
                                    )}
                                </div>
                            ) : (
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
                                        {dues_schedule.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={4}
                                                    className="h-24 text-center text-sm text-muted-foreground"
                                                >
                                                    No dues found for this
                                                    student.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            dues_schedule.map((due) => (
                                                <TableRow key={due.id}>
                                                    <TableCell className="pl-6">
                                                        {due.due_date_label ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="border-l">
                                                        {due.description}
                                                    </TableCell>
                                                    <TableCell className="border-l text-right">
                                                        {formatCurrency(
                                                            due.amount_due,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="border-l pr-6 text-right">
                                                        <Badge
                                                            variant="outline"
                                                            className={dueBadgeVariant(
                                                                due.status,
                                                            )}
                                                        >
                                                            {due.status_label}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Ledger Entries</CardTitle>
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button variant="outline" className="gap-2">
                                                <Filter className="size-4" />
                                                Filters
                                                {activeFilterCount > 0 && (
                                                    <Badge variant="secondary" className="ml-1 px-1 py-0 text-[10px]">
                                                        {activeFilterCount}
                                                    </Badge>
                                                )}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-80" align="end">
                                            <div className="grid gap-4">
                                                <div className="flex items-center justify-between">
                                                    <h4 className="font-medium leading-none">Entry Filters</h4>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-auto p-0 text-xs text-muted-foreground"
                                                        onClick={handleResetFilters}
                                                    >
                                                        Reset
                                                    </Button>
                                                </div>
                                                <div className="grid gap-4">
                                                    <div className="grid gap-2">
                                                        <Label>Date Range</Label>
                                                        <DateRangePicker
                                                            dateRange={entryDateRange}
                                                            setDateRange={setEntryDateRange}
                                                            className="w-full"
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>Entry Type</Label>
                                                        <Select
                                                            value={entryTypeFilter}
                                                            onValueChange={handleEntryTypeChange}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">All Entry Types</SelectItem>
                                                                <SelectItem value="charge">Charges</SelectItem>
                                                                <SelectItem value="payment">Payments</SelectItem>
                                                                <SelectItem value="discount">Discounts</SelectItem>
                                                                <SelectItem value="adjustment">Adjustments</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="flex items-center justify-between gap-2 py-1">
                                                        <Label htmlFor="popover-overdue-only" className="cursor-pointer">Overdue Accounts Only</Label>
                                                        <Switch
                                                            id="popover-overdue-only"
                                                            checked={overdueOnly}
                                                            onCheckedChange={(checked) => {
                                                                setOverdueOnly(checked);
                                                            }}
                                                        />
                                                    </div>
                                                    <Button size="sm" onClick={() => applyFilters()}>
                                                        Apply Filters
                                                    </Button>
                                                </div>
                                            </div>
                                        </PopoverContent>
                                    </Popover>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => router.get('/finance/due-reminder-settings')}
                                    >
                                        Reminder Scheduling
                                    </Button>
                                </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {isHandheld ? (
                            <div className="space-y-2.5 p-3">
                                {ledger_entries.length === 0 ? (
                                    <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                        No ledger entries found.
                                    </div>
                                ) : (
                                    ledger_entries.map((entry) => (
                                        <div
                                            key={entry.id}
                                            className="space-y-1 rounded-md border p-3"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-medium">
                                                    {entry.reference}
                                                </p>
                                                <Badge
                                                    variant="outline"
                                                    className={ledgerBadgeVariant(
                                                        entry.entry_type,
                                                    )}
                                                >
                                                    {entry.entry_type_label}
                                                </Badge>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                Date: {entry.date_label || '-'}
                                            </p>
                                            <div className="grid grid-cols-2 gap-2 text-xs">
                                                <p>
                                                    Charge:{' '}
                                                    {entry.charge > 0
                                                        ? formatCurrency(
                                                              entry.charge,
                                                          )
                                                        : '-'}
                                                </p>
                                                <p>
                                                    Payment:{' '}
                                                    {entry.payment > 0
                                                        ? formatCurrency(
                                                              entry.payment,
                                                          )
                                                        : '-'}
                                                </p>
                                            </div>
                                            <p className="text-sm font-semibold">
                                                Balance:{' '}
                                                {formatCurrency(
                                                    entry.running_balance,
                                                )}
                                            </p>
                                        </div>
                                    ))
                                )}
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Date
                                        </TableHead>
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
                                    {ledger_entries.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="h-24 text-center text-sm text-muted-foreground"
                                            >
                                                No ledger entries found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        ledger_entries.map((entry) => (
                                            <TableRow key={entry.id}>
                                                <TableCell className="pl-6">
                                                    {entry.date_label || '-'}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {entry.reference}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    <Badge
                                                        variant="outline"
                                                        className={ledgerBadgeVariant(
                                                            entry.entry_type,
                                                        )}
                                                    >
                                                        {entry.entry_type_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="border-l text-right">
                                                    {entry.charge > 0
                                                        ? formatCurrency(
                                                              entry.charge,
                                                          )
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="border-l text-right">
                                                    {entry.payment > 0
                                                        ? formatCurrency(
                                                              entry.payment,
                                                          )
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="border-l pr-6 text-right">
                                                    {formatCurrency(
                                                        entry.running_balance,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    <div className="grid gap-2 border-t p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Assessment Fee Total
                            </p>
                            <p className="font-medium">
                                {formatCurrency(summary.assessment_fee_total)}
                            </p>
                        </div>
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
            <Dialog open={isOverdueModalOpen} onOpenChange={setIsOverdueModalOpen}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Overdue Accounts</DialogTitle>
                    </DialogHeader>
                    <SearchAutocompleteInput
                        placeholder="Search overdue account by student name or LRN..."
                        value={overdueSearchQuery}
                        onValueChange={setOverdueSearchQuery}
                        suggestions={overdue_accounts.map((row) => ({
                            id: row.student_id,
                            label: row.student_name,
                            value: row.student_name,
                            description: `LRN: ${row.lrn}`,
                            keywords: row.lrn,
                        }))}
                    />
                    <div className="max-h-[60vh] overflow-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Student</TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead className="text-right">Overdue Balance</TableHead>
                                    <TableHead className="text-right">Days Overdue</TableHead>
                                    <TableHead className="text-right">Items</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredOverdueAccounts.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center text-sm text-muted-foreground">
                                            No overdue accounts found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    filteredOverdueAccounts.map((row) => (
                                        <TableRow key={row.student_id}>
                                            <TableCell>{row.student_name}</TableCell>
                                            <TableCell>{row.lrn}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(row.overdue_balance)}</TableCell>
                                            <TableCell className="text-right">{row.days_overdue}</TableCell>
                                            <TableCell className="text-right">{row.overdue_items}</TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setIsOverdueModalOpen(false);
                                                        setSelectedStudentId(String(row.student_id));
                                                        router.get(
                                                            student_ledgers.url({
                                                                query: {
                                                                    search: row.student_name,
                                                                    student_id: row.student_id,
                                                                    overdue_only: 1,
                                                                },
                                                            }),
                                                            {},
                                                            {
                                                                preserveState: true,
                                                                preserveScroll: true,
                                                                replace: true,
                                                            },
                                                        );
                                                    }}
                                                >
                                                    Open Ledger
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
