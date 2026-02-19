import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Download, Printer, Search } from 'lucide-react';
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Ledgers',
        href: '/finance/student-ledgers',
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
        { dueDate: 'Jun 15, 2026', amount: 'PHP 2,500.00', status: 'Paid' },
        { dueDate: 'Jul 15, 2026', amount: 'PHP 2,500.00', status: 'Paid' },
        { dueDate: 'Aug 15, 2026', amount: 'PHP 2,500.00', status: 'Unpaid' },
        { dueDate: 'Sep 15, 2026', amount: 'PHP 2,500.00', status: 'Unpaid' },
    ],
    quarterly: [
        { dueDate: 'Jun 15, 2026', amount: 'PHP 7,500.00', status: 'Paid' },
        { dueDate: 'Sep 15, 2026', amount: 'PHP 7,500.00', status: 'Unpaid' },
        { dueDate: 'Dec 15, 2026', amount: 'PHP 7,500.00', status: 'Unpaid' },
    ],
    'semi-annual': [
        { dueDate: 'Jun 15, 2026', amount: 'PHP 12,500.00', status: 'Paid' },
        { dueDate: 'Dec 15, 2026', amount: 'PHP 12,500.00', status: 'Unpaid' },
    ],
    cash: [
        { dueDate: 'Jun 15, 2026', amount: 'PHP 25,000.00', status: 'Paid' },
    ],
};

export default function StudentLedgers() {
    const [selectedPlan, setSelectedPlan] = useState<PaymentPlan>('monthly');
    const [showPaidDues, setShowPaidDues] = useState(false);
    const [entryDateRange, setEntryDateRange] = useState<DateRange>();
    const visibleDues = duesByPlan[selectedPlan].filter((due) => {
        if (showPaidDues) {
            return true;
        }

        return due.status !== 'Paid';
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Ledgers" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Ledger Lookup</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 lg:flex-row">
                            <Input
                                placeholder="Search by student name or LRN"
                                className="lg:flex-1"
                            />
                            <Button className="lg:w-auto">
                                <Search className="size-4" />
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-1">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div className="space-y-2">
                                    <CardTitle>
                                        Student Ledger Profile
                                    </CardTitle>
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <Badge variant="outline">
                                            Plan: {selectedPlan}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2">
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
                                        Grade and Section
                                    </p>
                                    <p className="text-sm font-medium">
                                        Grade 7 - Rizal
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Guardian
                                    </p>
                                    <p className="text-sm font-medium">
                                        Maria Dela Cruz
                                    </p>
                                </div>
                            </div>
                            <div className="mt-4 flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => window.print()}
                                >
                                    <Printer className="size-4" />
                                    Print SOA
                                </Button>
                                <Button variant="outline">
                                    <Download className="size-4" />
                                    Export
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <CardTitle>Dues Schedule</CardTitle>
                                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            id="show-paid-dues"
                                            checked={showPaidDues}
                                            onCheckedChange={setShowPaidDues}
                                        />
                                        <Label htmlFor="show-paid-dues">
                                            Show Paid
                                        </Label>
                                    </div>
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
                                            <SelectItem value="cash">
                                                Cash
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
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
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {visibleDues.map((due) => (
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
                                    {visibleDues.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                No unpaid dues for this plan.
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
                                <Select defaultValue="all">
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
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Date</TableHead>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Entry Type</TableHead>
                                    <TableHead className="text-right">
                                        Charge
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Payment
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Running Balance
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        Jun 10, 2026
                                    </TableCell>
                                    <TableCell>
                                        Tuition Fee Assessment
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">Charge</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        PHP 20,000.00
                                    </TableCell>
                                    <TableCell className="text-right">
                                        -
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        PHP 20,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        Jun 10, 2026
                                    </TableCell>
                                    <TableCell>
                                        Miscellaneous Fee Assessment
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">Charge</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        PHP 5,000.00
                                    </TableCell>
                                    <TableCell className="text-right">
                                        -
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        PHP 25,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        Jun 12, 2026
                                    </TableCell>
                                    <TableCell>
                                        Downpayment (OR-10221)
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            Payment
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        -
                                    </TableCell>
                                    <TableCell className="text-right">
                                        PHP 3,000.00
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        PHP 22,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        Jul 05, 2026
                                    </TableCell>
                                    <TableCell>
                                        Monthly Payment (OR-10408)
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            Payment
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        -
                                    </TableCell>
                                    <TableCell className="text-right">
                                        PHP 5,000.00
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        PHP 17,000.00
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                    <div className="grid gap-2 border-t p-4 text-sm sm:grid-cols-3">
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Total Charges
                            </p>
                            <p className="font-medium">PHP 25,000.00</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Total Payments
                            </p>
                            <p className="font-medium">PHP 8,000.00</p>
                        </div>
                        <div className="space-y-1 text-left sm:text-right">
                            <p className="text-muted-foreground">
                                Outstanding Balance
                            </p>
                            <p className="font-semibold">PHP 17,000.00</p>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
