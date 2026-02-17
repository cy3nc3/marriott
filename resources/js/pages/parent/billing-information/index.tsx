import { Head } from '@inertiajs/react';
import { CreditCard, Info } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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

export default function BillingInformation() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing & Payments" />
            <div className="flex flex-col gap-6">
                
                <div className="flex justify-end">
                    <Button>
                        <CreditCard className="mr-2 h-4 w-4" />
                        Pay Online
                    </Button>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    {/* Summary Card */}
                    <Card className="md:col-span-1">
                        <CardHeader>
                            <CardTitle>Current Total Balance</CardTitle>
                            <CardDescription>Statement as of Aug 15, 2024</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="text-3xl font-bold">₱ 15,000.00</div>
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertTitle>Notice</AlertTitle>
                                <AlertDescription>
                                    Next installment due on Aug 30, 2024. Please settle before the deadline.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>

                    {/* Breakdown Card */}
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Outstanding Dues</CardTitle>
                            <CardDescription>Breakdown of fees to be paid</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="text-center">Due Date</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                        <TableHead className="text-right">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="font-medium">Tuition Fee (Q1)</TableCell>
                                        <TableCell className="text-center">Aug 30, 2024</TableCell>
                                        <TableCell className="text-right">10,000.00</TableCell>
                                        <TableCell className="text-right">
                                            <Badge variant="destructive">Unpaid</Badge>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-medium">Miscellaneous Fee</TableCell>
                                        <TableCell className="text-center">Aug 30, 2024</TableCell>
                                        <TableCell className="text-right">5,000.00</TableCell>
                                        <TableCell className="text-right">
                                            <Badge variant="destructive">Unpaid</Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* Transaction History */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Payments</CardTitle>
                        <CardDescription>History of your recent transactions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Official Receipt</TableHead>
                                    <TableHead>Mode</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell>Jul 15, 2024</TableCell>
                                    <TableCell className="font-medium">OR-00921</TableCell>
                                    <TableCell>Cash</TableCell>
                                    <TableCell className="text-right">5,000.00</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
