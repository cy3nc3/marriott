import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing Information',
        href: '/parent/billing-information',
    },
];

export default function BillingInformation() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing Information" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                {/* Top Card: Total Outstanding */}
                <Card>
                    <CardContent className="p-6">
                        <h3 className="text-lg font-medium text-muted-foreground uppercase tracking-wider">Total Outstanding Balance</h3>
                        <div className="mt-4 flex items-baseline gap-2">
                            <span className="text-4xl font-black text-destructive tracking-tight">
                                ₱ 15,000.00
                            </span>
                            <span className="text-sm text-muted-foreground font-medium italic">as of Aug 01, 2024</span>
                        </div>
                    </CardContent>
                </Card>

                {/* Section A: Bill Breakdown */}
                <Card>
                    <CardHeader className="bg-muted/30 border-b">
                        <CardTitle className="text-lg">Bill Breakdown</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6">Description</TableHead>
                                    <TableHead>Due Date</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                    <TableHead className="text-center pr-6">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="bg-destructive/5 hover:bg-destructive/10 transition-colors">
                                    <TableCell className="pl-6 font-medium">Tuition Fee (Q1)</TableCell>
                                    <TableCell className="text-muted-foreground">Aug 15, 2024</TableCell>
                                    <TableCell className="text-right font-mono font-bold">10,000.00</TableCell>
                                    <TableCell className="text-center pr-6">
                                        <Badge variant="destructive" className="uppercase text-[10px]">Unpaid</Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="bg-destructive/5 hover:bg-destructive/10 transition-colors">
                                    <TableCell className="pl-6 font-medium">Miscellaneous Fee</TableCell>
                                    <TableCell className="text-muted-foreground">Aug 15, 2024</TableCell>
                                    <TableCell className="text-right font-mono font-bold">5,000.00</TableCell>
                                    <TableCell className="text-center pr-6">
                                        <Badge variant="destructive" className="uppercase text-[10px]">Unpaid</Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="hover:bg-muted/30 transition-colors opacity-60">
                                    <TableCell className="pl-6 font-medium">Downpayment</TableCell>
                                    <TableCell className="text-muted-foreground">Jul 15, 2024</TableCell>
                                    <TableCell className="text-right font-mono font-bold">5,000.00</TableCell>
                                    <TableCell className="text-center pr-6">
                                        <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200 uppercase text-[10px]">Paid</Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Section B: Transaction History */}
                <Card>
                    <CardHeader className="bg-muted/30 border-b">
                        <CardTitle className="text-lg">Transaction History</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6">Date</TableHead>
                                    <TableHead>Reference No.</TableHead>
                                    <TableHead>Payment Mode</TableHead>
                                    <TableHead className="text-right pr-6">Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="hover:bg-muted/30 transition-colors">
                                    <TableCell className="pl-6 text-muted-foreground">Jul 15, 2024</TableCell>
                                    <TableCell className="font-bold text-primary">OR-00921</TableCell>
                                    <TableCell>Cash</TableCell>
                                    <TableCell className="text-right pr-6 font-mono font-bold text-green-600">5,000.00</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
