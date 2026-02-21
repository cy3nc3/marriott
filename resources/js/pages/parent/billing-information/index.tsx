import { Head } from '@inertiajs/react';
import { useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-picker';
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
        title: 'Billing Information',
        href: '/parent/billing-information',
    },
];

type PaymentPlan = 'monthly' | 'quarterly' | 'semi-annual' | 'cash';

type DueItem = {
    due_date: string | null;
    amount: string;
    status: 'Paid' | 'Unpaid';
};

type PaymentRow = {
    date: string | null;
    or_number: string | null;
    payment_mode: string;
    amount: string;
    status: string;
};

interface Props {
    account_summary: {
        student_name: string;
        lrn: string;
        payment_plan: PaymentPlan;
        payment_plan_label: string;
        outstanding_balance: string;
    };
    dues_by_plan: Record<PaymentPlan, DueItem[]>;
    default_plan: PaymentPlan;
    recent_payments: PaymentRow[];
}

export default function BillingInformation({
    account_summary,
    dues_by_plan,
    default_plan,
    recent_payments,
}: Props) {
    const [selectedPlan, setSelectedPlan] = useState<PaymentPlan>(default_plan);
    const [paymentDateRange, setPaymentDateRange] = useState<DateRange>();

    const visiblePayments = recent_payments.filter((paymentRow) => {
        if (!paymentDateRange?.from || !paymentDateRange?.to || !paymentRow.date) {
            return true;
        }

        const paymentDate = new Date(paymentRow.date);

        return paymentDate >= paymentDateRange.from && paymentDate <= paymentDateRange.to;
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing Information" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Account Summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Student
                                </p>
                                <p className="text-sm font-medium">
                                    {account_summary.student_name}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    LRN
                                </p>
                                <p className="text-sm font-medium">
                                    {account_summary.lrn}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Payment Plan
                                </p>
                                <p className="text-sm font-medium">
                                    {account_summary.payment_plan_label}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Outstanding Balance
                                </p>
                                <p className="text-sm font-semibold">
                                    {account_summary.outstanding_balance}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle>Dues Schedule</CardTitle>
                            <Select
                                value={selectedPlan}
                                onValueChange={(value: PaymentPlan) =>
                                    setSelectedPlan(value)
                                }
                            >
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="monthly">
                                        Monthly
                                    </SelectItem>
                                    <SelectItem value="quarterly">
                                        Quarterly
                                    </SelectItem>
                                    <SelectItem value="semi-annual">
                                        Semi-Annual
                                    </SelectItem>
                                    <SelectItem value="cash">Cash</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Due Date
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {dues_by_plan[selectedPlan]?.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-8 text-center text-sm text-muted-foreground"
                                            colSpan={3}
                                        >
                                            No dues for the selected plan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    dues_by_plan[selectedPlan]?.map((due) => (
                                        <TableRow
                                            key={`${selectedPlan}-${due.due_date}-${due.amount}`}
                                        >
                                            <TableCell className="pl-6">
                                                {due.due_date ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {due.amount}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Badge
                                                    variant={
                                                        due.status === 'Paid'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {due.status}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle>Recent Payments</CardTitle>
                            <DateRangePicker
                                dateRange={paymentDateRange}
                                setDateRange={setPaymentDateRange}
                                className="w-fit max-w-full"
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Date</TableHead>
                                    <TableHead>OR Number</TableHead>
                                    <TableHead>Mode</TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {visiblePayments.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-8 text-center text-sm text-muted-foreground"
                                            colSpan={5}
                                        >
                                            No payments for the selected date range.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    visiblePayments.map((paymentRow) => (
                                        <TableRow
                                            key={`${paymentRow.date}-${paymentRow.or_number}-${paymentRow.amount}`}
                                        >
                                            <TableCell className="pl-6">
                                                {paymentRow.date ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {paymentRow.or_number ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {paymentRow.payment_mode}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {paymentRow.amount}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Badge variant="secondary">
                                                    {paymentRow.status}
                                                </Badge>
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
