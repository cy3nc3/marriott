import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Ban, Receipt, Search } from 'lucide-react';
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaction History',
        href: '/finance/transaction-history',
    },
];

export default function TransactionHistory() {
    const [dateRange, setDateRange] = useState<DateRange>();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaction History" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Transaction History</CardTitle>
                            <Badge variant="outline">2 results</Badge>
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
                                    />
                                </div>
                                <Button>Apply</Button>
                            </div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <DateRangePicker
                                    dateRange={dateRange}
                                    setDateRange={setDateRange}
                                    className="w-fit max-w-full"
                                />
                                <Select defaultValue="all-modes">
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
                                        <SelectItem value="bank">
                                            Bank Transfer
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select defaultValue="all-status">
                                    <SelectTrigger className="w-full sm:w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all-status">
                                            All Status
                                        </SelectItem>
                                        <SelectItem value="posted">
                                            Posted
                                        </SelectItem>
                                        <SelectItem value="voided">
                                            Voided
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline">Reset</Button>
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
                                    <TableHead>Student</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Mode</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date and Time</TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        OR-00921
                                    </TableCell>
                                    <TableCell>Juan Dela Cruz</TableCell>
                                    <TableCell>Downpayment</TableCell>
                                    <TableCell>Cash</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            Posted
                                        </Badge>
                                    </TableCell>
                                    <TableCell>08/01/2026 08:45 AM</TableCell>
                                    <TableCell className="text-right">
                                        PHP 5,000.00
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                            >
                                                <Receipt className="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                            >
                                                <Ban className="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        OR-00922
                                    </TableCell>
                                    <TableCell>Maria Santos</TableCell>
                                    <TableCell>Uniform Purchase</TableCell>
                                    <TableCell>GCash</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">Voided</Badge>
                                    </TableCell>
                                    <TableCell>08/01/2026 09:15 AM</TableCell>
                                    <TableCell className="text-right">
                                        PHP 1,350.00
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                            >
                                                <Receipt className="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                disabled
                                            >
                                                <Ban className="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>

                    <div className="grid gap-2 border-t p-4 text-sm sm:grid-cols-4">
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Transactions
                            </p>
                            <p className="font-medium">2</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Posted Amount
                            </p>
                            <p className="font-medium">PHP 5,000.00</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground">
                                Voided Amount
                            </p>
                            <p className="font-medium">PHP 1,350.00</p>
                        </div>
                        <div className="space-y-1 text-left sm:text-right">
                            <p className="text-muted-foreground">Net Amount</p>
                            <p className="font-semibold">PHP 3,650.00</p>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
