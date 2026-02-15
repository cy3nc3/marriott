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
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ButtonGroup } from '@/components/ui/button-group';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Printer, Search } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Ledgers',
        href: '/finance/student-ledgers',
    },
];

export default function StudentLedgers() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Ledger" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                {/* Print Header (Only visible on print) */}
                <div className="mb-8 hidden space-y-2 text-center print:block">
                    <h1 className="text-2xl font-bold uppercase">
                        Marriott Connect School
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        123 Education St, City, Country
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Tel: (02) 123-4567 | Email: finance@marriott.edu
                    </p>
                    <div className="pt-4">
                        <h2 className="inline-block border-b-2 border-foreground pb-1 text-xl font-bold uppercase">
                            Statement of Account
                        </h2>
                    </div>
                </div>

                {/* Search Section (Hidden on Print) */}
                <Card className="print:hidden">
                    <CardHeader>
                        <CardTitle className="text-lg">
                            Search Student
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <Input
                                placeholder="Enter Student Name or LRN..."
                                className="flex-1"
                            />
                            <Button className="gap-2">
                                <Search className="size-4" />
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Student Profile Card */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-6">
                                <Avatar size="2xl" className="hidden sm:flex">
                                    <AvatarImage src="" />
                                    <AvatarFallback>J</AvatarFallback>
                                </Avatar>
                                <div>
                                    <h3 className="text-2xl font-bold">
                                        Juan Dela Cruz
                                    </h3>
                                    <div className="mt-4 grid grid-cols-1 gap-x-8 gap-y-2 text-sm md:grid-cols-2">
                                        <p>
                                            <span className="font-medium text-muted-foreground">
                                                LRN:
                                            </span>{' '}
                                            1234567890123
                                        </p>
                                        <p>
                                            <span className="font-medium text-muted-foreground">
                                                Grade & Section:
                                            </span>{' '}
                                            Grade 7 - Rizal
                                        </p>
                                        <p>
                                            <span className="font-medium text-muted-foreground">
                                                Guardian:
                                            </span>{' '}
                                            Maria Dela Cruz
                                        </p>
                                        <p>
                                            <span className="font-medium text-muted-foreground">
                                                Contact No:
                                            </span>{' '}
                                            09123456789
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="space-y-2 text-right">
                                <Button
                                    variant="outline"
                                    className="gap-2 print:hidden"
                                    onClick={() => window.print()}
                                >
                                    <Printer className="size-4" />
                                    Print SOA
                                </Button>
                                <p className="text-[10px] font-bold text-muted-foreground uppercase">
                                    Date: Aug 01, 2024
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Financial Summary Cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Card className="border-l-4 border-l-blue-500">
                        <CardContent className="p-6">
                            <p className="text-sm font-medium tracking-wider text-muted-foreground uppercase">
                                Total Fees Assessed
                            </p>
                            <p className="mt-2 text-2xl font-bold">
                                ₱ 25,000.00
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-l-4 border-l-green-500">
                        <CardContent className="p-6">
                            <p className="text-sm font-medium tracking-wider text-muted-foreground uppercase">
                                Total Payments Made
                            </p>
                            <p className="mt-2 text-2xl font-bold">
                                ₱ 10,000.00
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-l-4 border-l-red-500">
                        <CardContent className="p-6">
                            <p className="text-sm font-medium tracking-wider text-muted-foreground uppercase">
                                Current Balance
                            </p>
                            <p className="mt-2 text-2xl font-bold text-destructive">
                                ₱ 15,000.00
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Ledger Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">
                            Transaction History
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="">
                        <Table>
                            <TableHeader>
                                <TableRow className="">
                                    <TableHead className="">Date</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-center">
                                        Debit (+)
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Credit (-)
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Running Balance
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell>Aug 01, 2024</TableCell>
                                    <TableCell className="font-medium">
                                        Tuition Fee Assessment
                                    </TableCell>
                                    <TableCell className="text-center">
                                        20,000.00
                                    </TableCell>
                                    <TableCell className="text-center">
                                        -
                                    </TableCell>
                                    <TableCell className="text-right font-bold text-destructive">
                                        20,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell>Aug 01, 2024</TableCell>
                                    <TableCell className="font-medium">
                                        Misc Fee Assessment
                                    </TableCell>
                                    <TableCell className="text-center">
                                        5,000.00
                                    </TableCell>
                                    <TableCell className="text-center">
                                        -
                                    </TableCell>
                                    <TableCell className="text-right font-bold text-destructive">
                                        25,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell>Aug 01, 2024</TableCell>
                                    <TableCell className="font-medium">
                                        Downpayment (OR-00921)
                                    </TableCell>
                                    <TableCell className="text-center">
                                        -
                                    </TableCell>
                                    <TableCell className="text-center font-bold text-green-600">
                                        5,000.00
                                    </TableCell>
                                    <TableCell className="text-right font-bold text-destructive">
                                        20,000.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell>Sep 05, 2024</TableCell>
                                    <TableCell className="font-medium">
                                        Tuition Payment (OR-01054)
                                    </TableCell>
                                    <TableCell className="text-center">
                                        -
                                    </TableCell>
                                    <TableCell className="text-center font-bold text-green-600">
                                        5,000.00
                                    </TableCell>
                                    <TableCell className="text-right font-bold text-destructive">
                                        15,000.00
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Print Footer (Only visible on print) */}
                <div className="mt-12 hidden text-center text-sm text-muted-foreground italic print:block">
                    <p>
                        This is a system generated report. No signature
                        required.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
