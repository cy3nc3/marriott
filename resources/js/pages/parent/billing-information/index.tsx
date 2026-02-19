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
    dueDate: string;
    amount: string;
    status: 'Paid' | 'Unpaid';
};

const duesByPlan: Record<PaymentPlan, DueItem[]> = {
    monthly: [
        { dueDate: '06/15/2026', amount: 'PHP 2,500.00', status: 'Paid' },
        { dueDate: '07/15/2026', amount: 'PHP 2,500.00', status: 'Paid' },
        { dueDate: '08/15/2026', amount: 'PHP 2,500.00', status: 'Unpaid' },
        { dueDate: '09/15/2026', amount: 'PHP 2,500.00', status: 'Unpaid' },
    ],
    quarterly: [
        { dueDate: '06/15/2026', amount: 'PHP 7,500.00', status: 'Paid' },
        { dueDate: '09/15/2026', amount: 'PHP 7,500.00', status: 'Unpaid' },
        { dueDate: '12/15/2026', amount: 'PHP 7,500.00', status: 'Unpaid' },
    ],
    'semi-annual': [
        { dueDate: '06/15/2026', amount: 'PHP 12,500.00', status: 'Paid' },
        { dueDate: '12/15/2026', amount: 'PHP 12,500.00', status: 'Unpaid' },
    ],
    cash: [{ dueDate: '06/15/2026', amount: 'PHP 25,000.00', status: 'Paid' }],
};

export default function BillingInformation() {
    const [selectedPlan, setSelectedPlan] = useState<PaymentPlan>('monthly');
    const [paymentDateRange, setPaymentDateRange] = useState<DateRange>();

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
                                    Juan Dela Cruz
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    LRN
                                </p>
                                <p className="text-sm font-medium">
                                    123456789012
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Payment Plan
                                </p>
                                <p className="text-sm font-medium">
                                    {selectedPlan}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Outstanding Balance
                                </p>
                                <p className="text-sm font-semibold">
                                    PHP 17,000.00
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
                                {duesByPlan[selectedPlan].map((due) => (
                                    <TableRow
                                        key={`${selectedPlan}-${due.dueDate}`}
                                    >
                                        <TableCell className="pl-6">
                                            {due.dueDate}
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
                                ))}
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
                                <TableRow>
                                    <TableCell className="pl-6">
                                        07/05/2026
                                    </TableCell>
                                    <TableCell>OR-10408</TableCell>
                                    <TableCell>Cash</TableCell>
                                    <TableCell className="text-right">
                                        PHP 5,000.00
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <Badge variant="secondary">
                                            Posted
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        06/12/2026
                                    </TableCell>
                                    <TableCell>OR-10221</TableCell>
                                    <TableCell>GCash</TableCell>
                                    <TableCell className="text-right">
                                        PHP 3,000.00
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <Badge variant="secondary">
                                            Posted
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
