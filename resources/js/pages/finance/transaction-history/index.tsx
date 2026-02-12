import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
                    <CardHeader className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b bg-muted/30">
                        <CardTitle className="text-lg">Financial Transactions</CardTitle>
                        
                        <div className="flex flex-wrap items-center gap-2">
                            <Input type="date" className="w-[140px] h-8 text-xs" />
                            <span className="text-xs text-muted-foreground">to</span>
                            <Input type="date" className="w-[140px] h-8 text-xs" />
                            <Select defaultValue="All">
                                <SelectTrigger className="w-[120px] h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">All Modes</SelectItem>
                                    <SelectItem value="Cash">Cash</SelectItem>
                                    <SelectItem value="GCash">GCash</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button size="xs" variant="outline">
                                <Search className="size-3 mr-1" />
                                Filter
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6">OR Number</TableHead>
                                    <TableHead>Student Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                    <TableHead className="text-center">Mode</TableHead>
                                    <TableHead>Timestamp</TableHead>
                                    <TableHead className="text-center pr-6">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="hover:bg-muted/30 transition-colors">
                                    <TableCell className="pl-6 font-bold text-primary">OR-00921</TableCell>
                                    <TableCell className="font-medium">Juan Dela Cruz</TableCell>
                                    <TableCell className="text-muted-foreground">Downpayment</TableCell>
                                    <TableCell className="text-right font-mono font-bold tracking-tight text-primary">5,000.00</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Cash</Badge>
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">Aug 01, 2024 08:45 AM</TableCell>
                                    <TableCell className="text-right pr-6">
                                        <div className="flex justify-center gap-1">
                                            <Button variant="ghost" size="icon" className="size-8" title="View Receipt">
                                                <Receipt className="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="size-8 text-destructive" title="Void">
                                                <Ban className="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="hover:bg-muted/30 transition-colors">
                                    <TableCell className="pl-6 font-bold text-primary">OR-00922</TableCell>
                                    <TableCell className="font-medium">Maria Santos</TableCell>
                                    <TableCell className="text-muted-foreground">Uniform Purchase</TableCell>
                                    <TableCell className="text-right font-mono font-bold tracking-tight text-primary">1,350.00</TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">GCash</Badge>
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">Aug 01, 2024 09:15 AM</TableCell>
                                    <TableCell className="text-right pr-6">
                                        <div className="flex justify-center gap-1">
                                            <Button variant="ghost" size="icon" className="size-8" title="View Receipt">
                                                <Receipt className="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="size-8 text-destructive" title="Void">
                                                <Ban className="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                    <div className="p-4 border-t bg-muted/10 text-center">
                        <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Showing 2 transactions</p>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
