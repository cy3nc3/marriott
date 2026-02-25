import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import type { BreadcrumbItem, SharedData } from '@/types';

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
    outstanding_amount: string;
    status: 'Paid' | 'Partially Paid' | 'Unpaid';
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
    school_year_options: { id: number; name: string; status: string }[];
    selected_school_year_id: number | null;
    is_departed_read_only: boolean;
}

export default function BillingInformation({
    account_summary,
    dues_by_plan,
    default_plan,
    recent_payments,
    school_year_options,
    selected_school_year_id,
    is_departed_read_only,
}: Props) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const [selectedPlan, setSelectedPlan] = useState<PaymentPlan>(default_plan);
    const [paymentDateRange, setPaymentDateRange] = useState<DateRange>();

    const visiblePayments = recent_payments.filter((paymentRow) => {
        if (
            !paymentDateRange?.from ||
            !paymentDateRange?.to ||
            !paymentRow.date
        ) {
            return true;
        }

        const paymentDate = new Date(paymentRow.date);

        return (
            paymentDate >= paymentDateRange.from &&
            paymentDate <= paymentDateRange.to
        );
    });

    const handleSchoolYearChange = (value: string) => {
        router.get(
            '/parent/billing-information',
            {
                academic_year_id: Number(value),
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing Information" />

            <div className="flex flex-col gap-4">
                {is_departed_read_only && (
                    <Alert>
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Read-only historical record</AlertTitle>
                        <AlertDescription>
                            This learner is marked as departed. Billing details
                            are shown for historical reference only.
                        </AlertDescription>
                    </Alert>
                )}

                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle>Account Summary</CardTitle>
                            {school_year_options.length > 0 && (
                                <Select
                                    value={
                                        selected_school_year_id
                                            ? String(selected_school_year_id)
                                            : undefined
                                    }
                                    onValueChange={handleSchoolYearChange}
                                >
                                    <SelectTrigger className="w-full sm:w-[220px]">
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
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
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
                        {isHandheld ? (
                            dues_by_plan[selectedPlan]?.length === 0 ? (
                                <div className="px-6 py-8 text-center text-sm text-muted-foreground">
                                    No dues for the selected plan.
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {dues_by_plan[selectedPlan]?.map((due) => (
                                        <div
                                            key={`${selectedPlan}-${due.due_date}-${due.amount}`}
                                            className="space-y-2 px-6 py-4"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-medium">
                                                    {due.due_date ?? '-'}
                                                </p>
                                                <Badge
                                                    variant={
                                                        due.status === 'Unpaid'
                                                            ? 'outline'
                                                            : due.status ===
                                                                'Partially Paid'
                                                              ? 'secondary'
                                                              : 'default'
                                                    }
                                                >
                                                    {due.status}
                                                </Badge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 text-xs">
                                                <p className="text-muted-foreground">
                                                    Amount Due
                                                </p>
                                                <p className="text-right font-medium">
                                                    {due.amount}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    Outstanding
                                                </p>
                                                <p className="text-right font-medium">
                                                    {due.outstanding_amount}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Due Date
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Amount Due
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Outstanding
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
                                                colSpan={4}
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
                                                <TableCell className="text-right">
                                                    {due.outstanding_amount}
                                                </TableCell>
                                                <TableCell className="pr-6 text-right">
                                                    <Badge
                                                        variant={
                                                            due.status === 'Unpaid'
                                                                ? 'outline'
                                                                : due.status ===
                                                                    'Partially Paid'
                                                                  ? 'secondary'
                                                                  : 'default'
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
                        )}
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
                        {isHandheld ? (
                            visiblePayments.length === 0 ? (
                                <div className="px-6 py-8 text-center text-sm text-muted-foreground">
                                    No payments for the selected date range.
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {visiblePayments.map((paymentRow) => (
                                        <div
                                            key={`${paymentRow.date}-${paymentRow.or_number}-${paymentRow.amount}`}
                                            className="space-y-2 px-6 py-4"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-medium">
                                                    {paymentRow.date ?? '-'}
                                                </p>
                                                <Badge variant="secondary">
                                                    {paymentRow.status}
                                                </Badge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 text-xs">
                                                <p className="text-muted-foreground">
                                                    OR Number
                                                </p>
                                                <p className="text-right font-medium">
                                                    {paymentRow.or_number ?? '-'}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    Payment Mode
                                                </p>
                                                <p className="text-right font-medium">
                                                    {paymentRow.payment_mode}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    Amount
                                                </p>
                                                <p className="text-right font-medium">
                                                    {paymentRow.amount}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )
                        ) : (
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
                                                No payments for the selected date
                                                range.
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
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
