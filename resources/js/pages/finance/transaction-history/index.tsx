import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Receipt, Ban, Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaction History',
        href: '/finance/transaction-history',
    },
];

export default function TransactionHistory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaction History" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <Card>
                    <CardHeader className="flex flex-col items-start justify-between border-b md:flex-row md:items-center">
                        <CardTitle className="text-lg">
                            Financial Transactions
                        </CardTitle>

                        <div className="flex flex-wrap items-center gap-2">
                            <Input type="date" className="h-8 w-35 text-xs" />
                            <span className="text-xs text-muted-foreground">
                                to
                            </span>
                            <Input type="date" className="h-8 w-35 text-xs" />
                            <Select defaultValue="All">
                                <SelectTrigger className="h-8 w-40 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">
                                        All Modes
                                    </SelectItem>
                                    <SelectItem value="Cash">Cash</SelectItem>
                                    <SelectItem value="GCash">GCash</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button size="xs" variant="outline">
                                <Search className="mr-1 size-3" />
                                Filter
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="">
                        <Table>
                            <TableHeader className="">
                                <TableRow>
                                    <TableHead className="">
                                        OR Number
                                    </TableHead>
                                    <TableHead>Student Name</TableHead>
                                    <TableHead className="text-center">
                                        Type
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Mode
                                    </TableHead>
                                    <TableHead className="">
                                        Timestamp
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="transition-colors hover:bg-muted/30">
                                    <TableCell className="font-bold">
                                        OR-00921
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        Juan Dela Cruz
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Downpayment
                                    </TableCell>
                                    <TableCell className="text-right font-mono font-bold">
                                        5,000.00
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge
                                            variant="outline"
                                            className="border-green-200 bg-green-50 text-green-700"
                                        >
                                            Cash
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">
                                        Aug 01, 2024 08:45 AM
                                    </TableCell>
                                    <TableCell className="pr-0 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                            title="View Receipt"
                                        >
                                            <Receipt className="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-destructive"
                                            title="Void"
                                        >
                                            <Ban className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="transition-colors hover:bg-muted/30">
                                    <TableCell className="font-bold text-primary">
                                        OR-00922
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        Maria Santos
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Uniform Purchase
                                    </TableCell>
                                    <TableCell className="text-right font-mono font-bold">
                                        1,350.00
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge
                                            variant="outline"
                                            className="border-blue-200 bg-blue-50 text-blue-700"
                                        >
                                            GCash
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">
                                        Aug 01, 2024 09:15 AM
                                    </TableCell>
                                    <TableCell className="pr-0 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                            title="View Receipt"
                                        >
                                            <Receipt className="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-destructive"
                                            title="Void"
                                        >
                                            <Ban className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                    <div className="border-t p-4 text-center">
                        <p className="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">
                            Showing 2 transactions
                        </p>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
