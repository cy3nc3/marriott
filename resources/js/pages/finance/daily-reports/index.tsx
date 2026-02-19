import { Head } from '@inertiajs/react';
import { Download, Printer } from 'lucide-react';
import { useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
        title: 'Daily Reports',
        href: '/finance/daily-reports',
    },
];

export default function DailyReports() {
    const [reportDateRange, setReportDateRange] = useState<DateRange>();

    const breakdownRows = [
        {
            category: 'Tuition Fees',
            transactionCount: 12,
            totalAmount: '45,000.00',
        },
        {
            category: 'Enrollment Downpayment',
            transactionCount: 9,
            totalAmount: '32,500.00',
        },
        {
            category: 'Products (Uniform/Books)',
            transactionCount: 15,
            totalAmount: '18,350.00',
        },
    ];

    const transactionRows = [
        {
            orNumber: 'OR-01021',
            student: 'Juan Dela Cruz',
            paymentType: 'Downpayment',
            paymentMode: 'Cash',
            amount: '5,000.00',
            cashier: 'Cashier A',
            dateTime: '02/20/2026 08:25 AM',
        },
        {
            orNumber: 'OR-01022',
            student: 'Maria Santos',
            paymentType: 'Tuition',
            paymentMode: 'GCash',
            amount: '7,500.00',
            cashier: 'Cashier B',
            dateTime: '02/20/2026 08:44 AM',
        },
        {
            orNumber: 'OR-01023',
            student: 'Carlo Reyes',
            paymentType: 'Uniform Purchase',
            paymentMode: 'Cash',
            amount: '1,350.00',
            cashier: 'Cashier A',
            dateTime: '02/20/2026 09:02 AM',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Reports" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Daily Collection Report</CardTitle>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => window.print()}
                                >
                                    <Printer className="size-4" />
                                    Print Z-Reading
                                </Button>
                                <Button variant="outline">
                                    <Download className="size-4" />
                                    Export CSV
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="border-b p-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <DateRangePicker
                                dateRange={reportDateRange}
                                setDateRange={setReportDateRange}
                                className="w-fit max-w-full"
                            />
                            <Select defaultValue="cashier-all">
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cashier-all">
                                        All Cashiers
                                    </SelectItem>
                                    <SelectItem value="cashier-a">
                                        Cashier A
                                    </SelectItem>
                                    <SelectItem value="cashier-b">
                                        Cashier B
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select defaultValue="mode-all">
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mode-all">
                                        All Payment Modes
                                    </SelectItem>
                                    <SelectItem value="cash">Cash</SelectItem>
                                    <SelectItem value="gcash">GCash</SelectItem>
                                    <SelectItem value="bank">
                                        Bank Transfer
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Badge variant="outline">36 transactions</Badge>
                        </div>
                    </CardContent>

                    <div className="grid gap-3 border-b p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Gross Collection
                            </p>
                            <p className="text-sm font-semibold">
                                PHP 95,850.00
                            </p>
                        </div>
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Cash on Hand
                            </p>
                            <p className="text-sm font-semibold">
                                PHP 61,350.00
                            </p>
                        </div>
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Digital Collection
                            </p>
                            <p className="text-sm font-semibold">
                                PHP 34,500.00
                            </p>
                        </div>
                        <div className="rounded-md border px-3 py-2">
                            <p className="text-xs text-muted-foreground">
                                Void/Adjustments
                            </p>
                            <p className="text-sm font-semibold">PHP 0.00</p>
                        </div>
                    </div>

                    <CardContent className="border-b p-0">
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
                                {breakdownRows.map((row) => (
                                    <TableRow key={row.category}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.category}
                                        </TableCell>
                                        <TableCell className="border-l text-center">
                                            {row.transactionCount}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            PHP {row.totalAmount}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>

                    <CardContent className="p-0">
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
                                {transactionRows.map((row) => (
                                    <TableRow key={row.orNumber}>
                                        <TableCell className="pl-6">
                                            {row.orNumber}
                                        </TableCell>
                                        <TableCell>{row.student}</TableCell>
                                        <TableCell className="border-l">
                                            {row.paymentType}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.paymentMode}
                                        </TableCell>
                                        <TableCell className="border-l text-right">
                                            PHP {row.amount}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.cashier}
                                        </TableCell>
                                        <TableCell className="border-l pr-6">
                                            {row.dateTime}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
