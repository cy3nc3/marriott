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
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Printer } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Daily Reports',
        href: '/finance/daily-reports',
    },
];

export default function DailyReports() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Reports" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Header & Action */}
                <div className="flex justify-between items-center">
                    <h2 className="text-2xl font-bold tracking-tight">August 01, 2024</h2>
                    <Button variant="outline" size="sm" className="gap-2 print:hidden" onClick={() => window.print()}>
                        <Printer className="size-4" />
                        Print Z-Reading
                    </Button>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardContent className="p-6">
                            <p className="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-1">Total Collected</p>
                            <p className="text-3xl font-extrabold text-primary">₱ 6,400.00</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-6">
                            <p className="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-1">Cash on Hand</p>
                            <p className="text-2xl font-bold">₱ 5,000.00</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-6">
                            <p className="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-1">Digital / Bank</p>
                            <p className="text-2xl font-bold">₱ 1,400.00</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Breakdown Table */}
                <Card>
                    <CardHeader className="bg-muted/30 border-b">
                        <CardTitle className="text-lg">Collection Breakdown</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Category</TableHead>
                                    <TableHead className="text-center">Transaction Count</TableHead>
                                    <TableHead className="text-right">Total Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">Tuition Fees</TableCell>
                                    <TableCell className="text-center">1</TableCell>
                                    <TableCell className="text-right font-bold">5,000.00</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">Uniforms</TableCell>
                                    <TableCell className="text-center">1</TableCell>
                                    <TableCell className="text-right font-bold">1,350.00</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">Merchandise</TableCell>
                                    <TableCell className="text-center">1</TableCell>
                                    <TableCell className="text-right font-bold">50.00</TableCell>
                                </TableRow>
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="font-bold">TOTAL</TableCell>
                                    <TableCell className="text-center font-bold">3</TableCell>
                                    <TableCell className="text-right font-bold text-lg text-primary">₱ 6,400.00</TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>

                {/* Print-only Signature Area */}
                <div className="hidden print:grid grid-cols-2 gap-12 pt-16">
                    <div className="text-center space-y-2">
                        <div className="border-b border-foreground w-3/4 mx-auto" />
                        <p className="text-sm">Prepared by: <strong>Finance Officer</strong></p>
                    </div>
                    <div className="text-center space-y-2">
                        <div className="border-b border-foreground w-3/4 mx-auto" />
                        <p className="text-sm">Verified by: <strong>School Treasurer</strong></p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
