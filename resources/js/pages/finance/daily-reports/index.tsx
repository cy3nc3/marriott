import { Head, router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, ListFilter, Printer } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-picker';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
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
import { daily_reports } from '@/routes/finance';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Daily Reports',
        href: '/finance/daily-reports',
    },
];

type CashierOption = {
    id: number;
    name: string;
};

type SchoolYearOption = {
    id: number;
    name: string;
    status: string;
};

type BreakdownRow = {
    category: string;
    transaction_count: number;
    total_amount: number;
};

type TransactionRow = {
    id: number;
    or_number: string;
    student_name: string;
    payment_type: string;
    payment_mode: string;
    payment_mode_label: string;
    status: string;
    amount: number;
    cashier_name: string;
    posted_at: string | null;
};

type Summary = {
    transaction_count: number;
    gross_collection: number;
    cash_on_hand: number;
    digital_collection: number;
    void_adjustments: number;
};

type Filters = {
    academic_year_id: number | null;
    cashier_id: number | null;
    payment_mode: 'cash' | 'gcash' | 'bank_transfer' | null;
    date_from: string | null;
    date_to: string | null;
};

interface Props {
    cashiers: CashierOption[];
    school_year_options: SchoolYearOption[];
    selected_school_year_id: number | null;
    breakdown_rows: BreakdownRow[];
    transaction_rows: {
        data: TransactionRow[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
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

const formatPostedAt = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

export default function DailyReports({
    cashiers,
    school_year_options,
    selected_school_year_id,
    breakdown_rows,
    transaction_rows,
    summary,
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

    const [reportDateRange, setReportDateRange] = useState<
        DateRange | undefined
    >(initialDateRange);
    const [selectedSchoolYearId, setSelectedSchoolYearId] = useState(
        selected_school_year_id ? String(selected_school_year_id) : '',
    );
    const [cashierFilter, setCashierFilter] = useState(
        filters.cashier_id ? String(filters.cashier_id) : 'cashier-all',
    );
    const [paymentModeFilter, setPaymentModeFilter] = useState(
        filters.payment_mode ?? 'mode-all',
    );

    const activeFilterCount = useMemo(() => {
        let count = 0;
        if (selectedSchoolYearId) count++;
        if (cashierFilter !== 'cashier-all') count++;
        if (paymentModeFilter !== 'mode-all') count++;
        if (reportDateRange?.from || reportDateRange?.to) count++;
        return count;
    }, [selectedSchoolYearId, cashierFilter, paymentModeFilter, reportDateRange]);

    const applyFilters = () => {
        router.get(
            daily_reports.url({
                query: {
                    academic_year_id: selectedSchoolYearId || undefined,
                    cashier_id:
                        cashierFilter === 'cashier-all'
                            ? undefined
                            : Number(cashierFilter),
                    payment_mode:
                        paymentModeFilter === 'mode-all'
                            ? undefined
                            : paymentModeFilter,
                    date_from: reportDateRange?.from
                        ? format(reportDateRange.from, 'yyyy-MM-dd')
                        : undefined,
                    date_to: reportDateRange?.to
                        ? format(reportDateRange.to, 'yyyy-MM-dd')
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

    const resetFilters = () => {
        setReportDateRange(undefined);
        setSelectedSchoolYearId(
            selected_school_year_id ? String(selected_school_year_id) : '',
        );
        setCashierFilter('cashier-all');
        setPaymentModeFilter('mode-all');

        router.get(
            daily_reports.url({
                query: {
                    academic_year_id: selected_school_year_id ?? undefined,
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

    const triggerExport = (formatType: 'xlsx' | 'csv' | 'pdf') => {
        const query = new URLSearchParams();
        query.set('format', formatType);

        if (selectedSchoolYearId) {
            query.set('academic_year_id', selectedSchoolYearId);
        }

        if (cashierFilter !== 'cashier-all') {
            query.set('cashier_id', cashierFilter);
        }

        if (paymentModeFilter !== 'mode-all') {
            query.set('payment_mode', paymentModeFilter);
        }

        if (reportDateRange?.from) {
            query.set('date_from', format(reportDateRange.from, 'yyyy-MM-dd'));
        }

        if (reportDateRange?.to) {
            query.set('date_to', format(reportDateRange.to, 'yyyy-MM-dd'));
        }

        window.location.assign(`/finance/daily-reports/export?${query.toString()}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Reports" />

            <div className="flex flex-col gap-4">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1">
                                <CardTitle>Daily Collection Report</CardTitle>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary" className="bg-muted text-muted-foreground">
                                        {summary.transaction_count} transactions
                                    </Badge>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    type="button"
                                    onClick={() => window.print()}
                                    className="hidden sm:flex"
                                >
                                    <Printer className="mr-2 size-4" />
                                    Print Z-Reading
                                </Button>

                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button variant="outline" className="gap-2">
                                            <ListFilter className="size-4" />
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
                                                <h4 className="font-medium leading-none">Report Filters</h4>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-auto p-0 text-xs text-muted-foreground"
                                                    onClick={resetFilters}
                                                >
                                                    Reset
                                                </Button>
                                            </div>
                                            <div className="grid gap-4">
                                                <div className="grid gap-2">
                                                    <Label>Date Range</Label>
                                                    <DateRangePicker
                                                        dateRange={reportDateRange}
                                                        setDateRange={setReportDateRange}
                                                        className="w-full"
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>School Year</Label>
                                                    <Select
                                                        value={selectedSchoolYearId}
                                                        onValueChange={setSelectedSchoolYearId}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
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
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Cashier</Label>
                                                    <Select
                                                        value={cashierFilter}
                                                        onValueChange={setCashierFilter}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="cashier-all">
                                                                All Cashiers
                                                            </SelectItem>
                                                            {cashiers.map((cashier) => (
                                                                <SelectItem
                                                                    key={cashier.id}
                                                                    value={String(cashier.id)}
                                                                >
                                                                    {cashier.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Payment Mode</Label>
                                                    <Select
                                                        value={paymentModeFilter}
                                                        onValueChange={setPaymentModeFilter}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="mode-all">
                                                                All Payment Modes
                                                            </SelectItem>
                                                            <SelectItem value="cash">Cash</SelectItem>
                                                            <SelectItem value="gcash">GCash</SelectItem>
                                                            <SelectItem value="bank_transfer">
                                                                Bank Transfer
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <Button type="button" size="sm" onClick={applyFilters}>
                                                    Apply Filters
                                                </Button>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" className="gap-2">
                                            <Download className="size-4" />
                                            Export
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem onClick={() => triggerExport('xlsx')}>
                                            Export as Excel (.xlsx)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => triggerExport('csv')}>
                                            Export as CSV (.csv)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => triggerExport('pdf')}>
                                            Export as PDF (.pdf)
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </CardHeader>

                    <div className="grid gap-3 border-b p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Gross Collection
                            </p>
                            <p className="text-sm font-semibold">
                                {formatCurrency(summary.gross_collection)}
                            </p>
                        </div>
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Cash on Hand
                            </p>
                            <p className="text-sm font-semibold">
                                {formatCurrency(summary.cash_on_hand)}
                            </p>
                        </div>
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Digital Collection
                            </p>
                            <p className="text-sm font-semibold">
                                {formatCurrency(summary.digital_collection)}
                            </p>
                        </div>
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Void/Adjustments
                            </p>
                            <p className="text-sm font-semibold">
                                {formatCurrency(summary.void_adjustments)}
                            </p>
                        </div>
                    </div>

                    <CardContent className="border-b p-0">
                        {isHandheld ? (
                            <div className="space-y-2.5 p-3">
                                {breakdown_rows.length === 0 ? (
                                    <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                        No category breakdown available.
                                    </div>
                                ) : (
                                    breakdown_rows.map((row) => (
                                        <div
                                            key={row.category}
                                            className="space-y-1 rounded-md border p-3"
                                        >
                                            <p className="text-sm font-medium">
                                                {row.category}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Transactions: {row.transaction_count}
                                            </p>
                                            <p className="text-sm font-semibold">
                                                {formatCurrency(row.total_amount)}
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
                                            Category
                                        </TableHead>
                                        <TableHead className="border-l text-center">
                                            Transactions
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Total Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {breakdown_rows.map((row) => (
                                        <TableRow key={row.category}>
                                            <TableCell className="pl-6 font-medium">
                                                {row.category}
                                            </TableCell>
                                            <TableCell className="border-l text-center">
                                                {row.transaction_count}
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                {formatCurrency(row.total_amount)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {breakdown_rows.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                No category breakdown available.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>

                    <CardContent className="p-0">
                        {isHandheld ? (
                            <div className="space-y-2.5 p-3">
                                {transaction_rows.data.length === 0 ? (
                                    <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                        No transactions found.
                                    </div>
                                ) : (
                                    transaction_rows.data.map((row) => (
                                        <div
                                            key={row.id}
                                            className="space-y-1 rounded-md border p-3"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-medium">
                                                    {row.or_number}
                                                </p>
                                                <p className="text-sm font-semibold">
                                                    {formatCurrency(row.amount)}
                                                </p>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {row.student_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {row.payment_type} •{' '}
                                                {row.payment_mode_label}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {row.cashier_name} •{' '}
                                                {formatPostedAt(row.posted_at)}
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
                                            OR Number
                                        </TableHead>
                                        <TableHead>Student</TableHead>
                                        <TableHead className="border-l">
                                            Type
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Mode
                                        </TableHead>
                                        <TableHead className="border-l text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Cashier
                                        </TableHead>
                                        <TableHead className="border-l pr-6">
                                            Date and Time
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transaction_rows.data.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="pl-6">
                                                {row.or_number}
                                            </TableCell>
                                            <TableCell>
                                                {row.student_name}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {row.payment_type}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {row.payment_mode_label}
                                            </TableCell>
                                            <TableCell className="border-l text-right">
                                                {formatCurrency(row.amount)}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {row.cashier_name}
                                            </TableCell>
                                            <TableCell className="border-l pr-6">
                                                {formatPostedAt(row.posted_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {transaction_rows.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                No transactions found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>

                    {transaction_rows.links.length > 3 && (
                        <div className="flex items-center justify-between border-t p-4">
                            <p className="text-sm text-muted-foreground">
                                {transaction_rows.from ?? 0}-
                                {transaction_rows.to ?? 0} out of{' '}
                                {transaction_rows.total}
                            </p>
                            <div className="flex items-center gap-2">
                                {transaction_rows.links.map((link, index) => {
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
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
