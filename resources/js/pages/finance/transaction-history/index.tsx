import { Head, router, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { RefreshCcw, Search, Undo2 } from 'lucide-react';
import { useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Textarea } from '@/components/ui/textarea';
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
    voided_at: string | null;
    void_reason: string | null;
    refunded_at: string | null;
    refund_reason: string | null;
    reissued_at: string | null;
    reissue_reason: string | null;
    reissued_transaction_or_number: string | null;
    can_void: boolean;
    can_refund: boolean;
    can_reissue: boolean;
};

type SchoolYearOption = {
    id: number;
    name: string;
    status: string;
};

type Summary = {
    count: number;
    posted_amount: number;
    voided_amount: number;
    corrected_amount: number;
    net_amount: number;
};

type Filters = {
    academic_year_id: number | null;
    search: string | null;
    payment_mode: 'cash' | 'gcash' | 'bank_transfer' | null;
    date_from: string | null;
    date_to: string | null;
};

interface Props {
    school_year_options: SchoolYearOption[];
    selected_school_year_id: number | null;
    transactions: {
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

export default function TransactionHistory({
    school_year_options,
    selected_school_year_id,
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
    const [selectedSchoolYearId, setSelectedSchoolYearId] = useState(
        selected_school_year_id ? String(selected_school_year_id) : '',
    );
    const [paymentModeFilter, setPaymentModeFilter] = useState(
        filters.payment_mode ?? 'all-modes',
    );
    const [dateRange, setDateRange] = useState<DateRange | undefined>(
        initialDateRange,
    );
    const [voidingTransaction, setVoidingTransaction] =
        useState<TransactionRow | null>(null);
    const [refundingTransaction, setRefundingTransaction] =
        useState<TransactionRow | null>(null);
    const [reissuingTransaction, setReissuingTransaction] =
        useState<TransactionRow | null>(null);
    const voidForm = useForm({
        reason: '',
    });
    const refundForm = useForm({
        reason: '',
    });
    const reissueForm = useForm({
        reason: '',
        or_number: '',
        payment_mode: 'cash',
        reference_no: '',
        remarks: '',
    });

    const applyFilters = () => {
        router.get(
            transaction_history.url({
                query: {
                    search: searchQuery || undefined,
                    academic_year_id: selectedSchoolYearId || undefined,
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
            transaction_history.url({
                query: {
                    academic_year_id: selectedSchoolYearId || undefined,
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

    const openVoidDialog = (transaction: TransactionRow) => {
        voidForm.reset();
        voidForm.clearErrors();
        setVoidingTransaction(transaction);
    };

    const submitVoid = () => {
        if (!voidingTransaction) {
            return;
        }

        voidForm.post(
            `/finance/transaction-history/${voidingTransaction.id}/void`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setVoidingTransaction(null);
                    voidForm.reset();
                },
            },
        );
    };

    const openRefundDialog = (transaction: TransactionRow) => {
        refundForm.reset();
        refundForm.clearErrors();
        setRefundingTransaction(transaction);
    };

    const submitRefund = () => {
        if (!refundingTransaction) {
            return;
        }

        refundForm.post(
            `/finance/transaction-history/${refundingTransaction.id}/refund`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRefundingTransaction(null);
                    refundForm.reset();
                },
            },
        );
    };

    const openReissueDialog = (transaction: TransactionRow) => {
        reissueForm.setData({
            reason: '',
            or_number: '',
            payment_mode: transaction.payment_mode,
            reference_no: '',
            remarks: '',
        });
        reissueForm.clearErrors();
        setReissuingTransaction(transaction);
    };

    const submitReissue = () => {
        if (!reissuingTransaction) {
            return;
        }

        reissueForm.post(
            `/finance/transaction-history/${reissuingTransaction.id}/reissue`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReissuingTransaction(null);
                    reissueForm.reset();
                },
            },
        );
    };

    const correctionLabel = (transaction: TransactionRow) => {
        if (transaction.status === 'voided') {
            return transaction.voided_at
                ? `Voided ${formatPostedAt(transaction.voided_at)}`
                : 'Voided';
        }

        if (transaction.status === 'refunded') {
            return transaction.refunded_at
                ? `Refunded ${formatPostedAt(transaction.refunded_at)}`
                : 'Refunded';
        }

        if (transaction.status === 'reissued') {
            if (transaction.reissued_transaction_or_number) {
                return `Reissued to ${transaction.reissued_transaction_or_number}`;
            }

            return transaction.reissued_at
                ? `Reissued ${formatPostedAt(transaction.reissued_at)}`
                : 'Reissued';
        }

        return '';
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
                                    value={selectedSchoolYearId}
                                    onValueChange={(value) => {
                                        setSelectedSchoolYearId(value);
                                        router.get(
                                            transaction_history.url({
                                                query: {
                                                    academic_year_id:
                                                        value || undefined,
                                                    search:
                                                        searchQuery ||
                                                        undefined,
                                                    payment_mode:
                                                        paymentModeFilter ===
                                                        'all-modes'
                                                            ? undefined
                                                            : paymentModeFilter,
                                                    date_from: dateRange?.from
                                                        ? format(
                                                              dateRange.from,
                                                              'yyyy-MM-dd',
                                                          )
                                                        : undefined,
                                                    date_to: dateRange?.to
                                                        ? format(
                                                              dateRange.to,
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
                                >
                                    <SelectTrigger className="w-full sm:w-44">
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
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transactions.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="h-24 text-center text-sm text-muted-foreground"
                                        >
                                            No transactions found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    transactions.data.map((transaction) => (
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
                                                <Badge
                                                    variant={
                                                        transaction.status ===
                                                            'voided' ||
                                                        transaction.status ===
                                                            'refunded'
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    }
                                                >
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
                                            <TableCell className="border-l pr-6 text-right">
                                                {transaction.can_void ||
                                                transaction.can_refund ||
                                                transaction.can_reissue ? (
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openVoidDialog(
                                                                    transaction,
                                                                )
                                                            }
                                                        >
                                                            <Undo2 className="mr-2 size-4" />
                                                            Void
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openRefundDialog(
                                                                    transaction,
                                                                )
                                                            }
                                                        >
                                                            Refund
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openReissueDialog(
                                                                    transaction,
                                                                )
                                                            }
                                                        >
                                                            <RefreshCcw className="mr-2 size-4" />
                                                            Reissue
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        {correctionLabel(
                                                            transaction,
                                                        )}
                                                    </span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>

                    {transactions.links.length > 3 && (
                        <div className="flex items-center justify-between border-t p-4">
                            <p className="text-sm text-muted-foreground">
                                {transactions.from ?? 0}-{transactions.to ?? 0}{' '}
                                out of {transactions.total}
                            </p>
                            <div className="flex items-center gap-2">
                                {transactions.links.map((link, index) => {
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
                                Corrected Amount
                            </p>
                            <p className="font-medium">
                                {formatCurrency(summary.corrected_amount)}
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

            <Dialog
                open={!!voidingTransaction}
                onOpenChange={(open) => {
                    if (!open) {
                        setVoidingTransaction(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Void Transaction</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        <div className="rounded-md border p-3 text-sm">
                            <p className="font-medium">
                                OR {voidingTransaction?.or_number}
                            </p>
                            <p className="text-muted-foreground">
                                {voidingTransaction?.student_name} ·{' '}
                                {voidingTransaction
                                    ? formatCurrency(voidingTransaction.amount)
                                    : '-'}
                            </p>
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Void Reason
                            </label>
                            <Textarea
                                rows={4}
                                value={voidForm.data.reason}
                                onChange={(event) =>
                                    voidForm.setData(
                                        'reason',
                                        event.target.value,
                                    )
                                }
                                placeholder="State why this transaction is being voided."
                            />
                            {voidForm.errors.reason && (
                                <p className="text-sm text-destructive">
                                    {voidForm.errors.reason}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setVoidingTransaction(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={submitVoid}
                            disabled={voidForm.processing}
                        >
                            Confirm Void
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={!!refundingTransaction}
                onOpenChange={(open) => {
                    if (!open) {
                        setRefundingTransaction(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Refund Transaction</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        <div className="rounded-md border p-3 text-sm">
                            <p className="font-medium">
                                OR {refundingTransaction?.or_number}
                            </p>
                            <p className="text-muted-foreground">
                                {refundingTransaction?.student_name} ·{' '}
                                {refundingTransaction
                                    ? formatCurrency(refundingTransaction.amount)
                                    : '-'}
                            </p>
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Refund Reason
                            </label>
                            <Textarea
                                rows={4}
                                value={refundForm.data.reason}
                                onChange={(event) =>
                                    refundForm.setData(
                                        'reason',
                                        event.target.value,
                                    )
                                }
                                placeholder="State why this transaction is being refunded."
                            />
                            {refundForm.errors.reason && (
                                <p className="text-sm text-destructive">
                                    {refundForm.errors.reason}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setRefundingTransaction(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={submitRefund}
                            disabled={refundForm.processing}
                        >
                            Confirm Refund
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={!!reissuingTransaction}
                onOpenChange={(open) => {
                    if (!open) {
                        setReissuingTransaction(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-[560px]">
                    <DialogHeader>
                        <DialogTitle>Reissue Transaction</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        <div className="rounded-md border p-3 text-sm">
                            <p className="font-medium">
                                Current OR {reissuingTransaction?.or_number}
                            </p>
                            <p className="text-muted-foreground">
                                {reissuingTransaction?.student_name} ·{' '}
                                {reissuingTransaction
                                    ? formatCurrency(reissuingTransaction.amount)
                                    : '-'}
                            </p>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Reissue Reason
                            </label>
                            <Textarea
                                rows={3}
                                value={reissueForm.data.reason}
                                onChange={(event) =>
                                    reissueForm.setData(
                                        'reason',
                                        event.target.value,
                                    )
                                }
                                placeholder="State why this transaction is being reissued."
                            />
                            {reissueForm.errors.reason && (
                                <p className="text-sm text-destructive">
                                    {reissueForm.errors.reason}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    New OR Number
                                </label>
                                <Input
                                    value={reissueForm.data.or_number}
                                    onChange={(event) =>
                                        reissueForm.setData(
                                            'or_number',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="OR-2026-XXXX"
                                />
                                {reissueForm.errors.or_number && (
                                    <p className="text-sm text-destructive">
                                        {reissueForm.errors.or_number}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Payment Mode
                                </label>
                                <Select
                                    value={reissueForm.data.payment_mode}
                                    onValueChange={(value) =>
                                        reissueForm.setData(
                                            'payment_mode',
                                            value as
                                                | 'cash'
                                                | 'gcash'
                                                | 'bank_transfer',
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
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
                                {reissueForm.errors.payment_mode && (
                                    <p className="text-sm text-destructive">
                                        {reissueForm.errors.payment_mode}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Reference Number
                            </label>
                            <Input
                                value={reissueForm.data.reference_no}
                                onChange={(event) =>
                                    reissueForm.setData(
                                        'reference_no',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional"
                            />
                            {reissueForm.errors.reference_no && (
                                <p className="text-sm text-destructive">
                                    {reissueForm.errors.reference_no}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Remarks
                            </label>
                            <Textarea
                                rows={3}
                                value={reissueForm.data.remarks}
                                onChange={(event) =>
                                    reissueForm.setData(
                                        'remarks',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional"
                            />
                            {reissueForm.errors.remarks && (
                                <p className="text-sm text-destructive">
                                    {reissueForm.errors.remarks}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setReissuingTransaction(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={submitReissue}
                            disabled={reissueForm.processing}
                        >
                            Confirm Reissue
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
