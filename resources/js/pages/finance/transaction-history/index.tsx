import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
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
import { transaction_history } from '@/routes/finance';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaction History',
        href: '/finance/transaction-history',
    },
];

type TransactionRow = {
    id: number;
    or_number: string;
    student_name: string;
    student_lrn: string | null;
    entry_label: string;
    payment_mode: string;
    payment_mode_label: string;
    status: string;
    status_label: string;
    cashier_name: string;
    amount: number;
    posted_at: string | null;
};

type Summary = {
    count: number;
    posted_amount: number;
    voided_amount: number;
    net_amount: number;
};

type Filters = {
    search: string | null;
    payment_mode: 'cash' | 'gcash' | 'bank_transfer' | null;
    date_from: string | null;
    date_to: string | null;
};

interface Props {
    transactions: TransactionRow[];
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

export default function TransactionHistory({
    transactions,
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
    const [paymentModeFilter, setPaymentModeFilter] = useState(
        filters.payment_mode ?? 'all-modes',
    );
    const [dateRange, setDateRange] =
        useState<DateRange | undefined>(initialDateRange);

    const applyFilters = () => {
        router.get(
            transaction_history.url({
                query: {
                    search: searchQuery || undefined,
                    payment_mode:
                        paymentModeFilter === 'all-modes'
                            ? undefined
                            : paymentModeFilter,
                    date_from: dateRange?.from
                        ? format(dateRange.from, 'yyyy-MM-dd')
                        : undefined,
                    date_to: dateRange?.to
                        ? format(dateRange.to, 'yyyy-MM-dd')
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
        setSearchQuery('');
        setPaymentModeFilter('all-modes');
        setDateRange(undefined);

        router.get(
            transaction_history.url(),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaction History" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Transaction History</CardTitle>
                            <Badge variant="outline">
                                {summary.count} results
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent className="border-b p-4">
                        <div className="flex flex-col gap-3">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <div className="relative w-full sm:flex-1">
                                    <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                    <Input
                                        placeholder="OR number or student"
                                        className="pl-10"
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
                                <Button type="button" onClick={applyFilters}>
                                    Apply
                                </Button>
                            </div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <DateRangePicker
                                    dateRange={dateRange}
                                    setDateRange={setDateRange}
                                    className="w-fit max-w-full"
                                />
                                <Select
                                    value={paymentModeFilter}
                                    onValueChange={(value) =>
                                        setPaymentModeFilter(value)
                                    }
                                >
                                    <SelectTrigger className="w-full sm:w-44">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all-modes">
                                            All Modes
                                        </SelectItem>
                                        <SelectItem value="cash">
                                            Cash
                                        </SelectItem>
                                        <SelectItem value="gcash">
                                            GCash
                                        </SelectItem>
                                        <SelectItem value="bank_transfer">
                                            Bank Transfer
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    variant="outline"
                                    type="button"
                                    onClick={resetFilters}
                                >
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </CardContent>

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        OR Number
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Student
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Entry
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Mode
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Status
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Posted On
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Amount
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transactions.map((transaction) => (
                                    <TableRow key={transaction.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {transaction.or_number}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {transaction.student_name}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {transaction.entry_label}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {transaction.payment_mode_label}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Badge variant="secondary">
                                                {transaction.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {formatPostedAt(
                                                transaction.posted_at,
                                            )}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            {formatCurrency(
                                                transaction.amount,
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {transactions.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-10 text-center text-sm text-muted-foreground"
                                        >
                                            No transactions found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>

                    <div className="grid gap-2 border-t p-4 text-sm sm:grid-cols-4">
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Transactions
                            </p>
                            <p className="font-medium">{summary.count}</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Posted Amount
                            </p>
                            <p className="font-medium">
                                {formatCurrency(summary.posted_amount)}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Voided Amount
                            </p>
                            <p className="font-medium">
                                {formatCurrency(summary.voided_amount)}
                            </p>
                        </div>
                        <div className="space-y-1 text-left sm:text-right">
                            <p className="text-muted-foreground">Net Amount</p>
                            <p className="font-semibold">
                                {formatCurrency(summary.net_amount)}
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
