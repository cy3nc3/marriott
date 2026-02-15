import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Wallet, CreditCard, History, Info, ArrowUpRight, CheckCircle2 } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing Information',
        href: '/parent/billing-information',
    },
];

export default function BillingInformation() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing & Payments" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <Wallet className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">Financial Account</h1>
                    </div>
                    <Button className="gap-2 bg-indigo-600 hover:bg-indigo-700 shadow-md">
                        <CreditCard className="size-4" />
                        Pay Online (GCash/Maya)
                    </Button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Summary Card */}
                    <Card className="lg:col-span-1 border-primary/10 bg-primary/[0.02]">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Current Total Balance</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div>
                                <p className="text-5xl font-black tracking-tighter text-destructive">₱ 15,000.00</p>
                                <p className="text-xs text-muted-foreground mt-1 font-medium italic">Statement as of Aug 15, 2024</p>
                            </div>
                            <Alert className="bg-background border-primary/10">
                                <Info className="size-4 text-primary" />
                                <AlertDescription className="text-[10px] leading-relaxed font-medium">
                                    Next installment due on <span className="font-bold">Aug 30, 2024</span>. Please settle before the deadline to avoid late fees.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>

                    {/* Breakdown Card */}
                    <Card className="lg:col-span-2 shadow-sm border-primary/10 overflow-hidden">
                        <CardHeader className="bg-muted/30 border-b py-4 px-6 flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-sm font-bold flex items-center gap-2">
                                <History className="size-4 text-primary" />
                                Outstanding Dues
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader className="bg-muted/20">
                                    <TableRow>
                                        <TableHead className="pl-6 font-black text-[10px] uppercase">Description</TableHead>
                                        <TableHead className="text-center font-black text-[10px] uppercase">Due Date</TableHead>
                                        <TableHead className="text-right font-black text-[10px] uppercase">Amount</TableHead>
                                        <TableHead className="text-right pr-6 font-black text-[10px] uppercase">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow className="hover:bg-muted/10 transition-colors">
                                        <TableCell className="pl-6 font-bold">Tuition Fee (Q1)</TableCell>
                                        <TableCell className="text-center text-xs font-medium">Aug 30, 2024</TableCell>
                                        <TableCell className="text-right font-mono font-black text-destructive">10,000.00</TableCell>
                                        <TableCell className="text-right pr-6">
                                            <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 text-[10px] font-black uppercase">Unpaid</Badge>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow className="hover:bg-muted/10 transition-colors">
                                        <TableCell className="pl-6 font-bold">Miscellaneous Fee</TableCell>
                                        <TableCell className="text-center text-xs font-medium">Aug 30, 2024</TableCell>
                                        <TableCell className="text-right font-mono font-black text-destructive">5,000.00</TableCell>
                                        <TableCell className="text-right pr-6">
                                            <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 text-[10px] font-black uppercase">Unpaid</Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* Transaction History */}
                <Card className="shadow-sm border-primary/10 overflow-hidden">
                    <CardHeader className="bg-muted/30 border-b py-4 px-6 flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-sm font-bold flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-green-600" />
                            Recent Payments
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6 font-black text-[10px] uppercase">Date</TableHead>
                                    <TableHead className="font-black text-[10px] uppercase">Official Receipt</TableHead>
                                    <TableHead className="font-black text-[10px] uppercase">Mode</TableHead>
                                    <TableHead className="text-right pr-6 font-black text-[10px] uppercase">Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="hover:bg-muted/5 transition-colors opacity-80">
                                    <TableCell className="pl-6 text-xs font-medium">Jul 15, 2024</TableCell>
                                    <TableCell className="font-black text-primary text-xs uppercase tracking-tighter">OR-00921</TableCell>
                                    <TableCell className="text-xs font-bold text-muted-foreground">Cash</TableCell>
                                    <TableCell className="text-right pr-6 font-mono font-black text-green-600">5,000.00</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
