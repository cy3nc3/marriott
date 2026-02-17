import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Reports" />
            <div className="flex flex-col gap-6">
                {/* Header & Action */}
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight">
                        Date Here
                    </h2>
                    <Button
                        variant="outline"
                        size="sm"
                        className="gap-2 print:hidden"
                        onClick={() => window.print()}
                    >
                        <Printer className="size-4" />
                        Print Z-Reading
                    </Button>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Card>
                        <CardContent className="p-6">
                            <p className="mb-1 text-sm font-medium tracking-wider text-muted-foreground uppercase">
                                Total Collected
                            </p>
                            <p className="text-3xl font-extrabold text-primary">
                                ₱ 6,400.00
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-6">
                            <p className="mb-1 text-sm font-medium tracking-wider text-muted-foreground uppercase">
                                Cash on Hand
                            </p>
                            <p className="text-2xl font-bold">₱ 5,000.00</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-6">
                            <p className="mb-1 text-sm font-medium tracking-wider text-muted-foreground uppercase">
                                Digital / Bank
                            </p>
                            <p className="text-2xl font-bold">₱ 1,400.00</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Breakdown Table */}
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle className="text-lg">
                            Collection Breakdown
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Category</TableHead>
                                    <TableHead className="text-center">
                                        Transaction Count
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total Amount
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Tuition Fees
                                    </TableCell>
                                    <TableCell className="text-center">
                                        1
                                    </TableCell>
                                    <TableCell className="text-right font-bold">
                                        5,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Uniforms
                                    </TableCell>
                                    <TableCell className="text-center">
                                        1
                                    </TableCell>
                                    <TableCell className="text-right font-bold">
                                        1,350.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Merchandise
                                    </TableCell>
                                    <TableCell className="text-center">
                                        1
                                    </TableCell>
                                    <TableCell className="text-right font-bold">
                                        50.00
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="font-bold">
                                        TOTAL
                                    </TableCell>
                                    <TableCell className="text-center font-bold">
                                        3
                                    </TableCell>
                                    <TableCell className="text-right text-lg font-bold text-primary">
                                        ₱ 6,400.00
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>

                {/* Print-only Signature Area */}
                <div className="hidden grid-cols-2 gap-12 pt-16 print:grid">
                    <div className="space-y-2 text-center">
                        <div className="mx-auto w-3/4 border-b border-foreground" />
                        <p className="text-sm">
                            Prepared by: <strong>Finance Officer</strong>
                        </p>
                    </div>
                    <div className="space-y-2 text-center">
                        <div className="mx-auto w-3/4 border-b border-foreground" />
                        <p className="text-sm">
                            Verified by: <strong>School Treasurer</strong>
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
