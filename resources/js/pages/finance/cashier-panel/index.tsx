import { Head, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { cashier_panel, student_ledgers } from '@/routes/finance';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cashier Panel',
        href: cashier_panel().url,
    },
];

export default function CashierPanel() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashier Panel" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Student Lookup</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Input placeholder="Search by LRN or student name" />
                            <Button className="sm:w-auto">Search</Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>Selected Student</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
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
                                    Payment Plan
                                </p>
                                <p className="text-sm font-medium">Monthly</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Stated Downpayment
                                </p>
                                <p className="text-sm font-medium">
                                    PHP 3,000.00
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Remaining Balance
                                </p>
                                <p className="text-lg font-semibold">
                                    PHP 15,000.00
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                className="w-full"
                                asChild
                            >
                                <Link href={student_ledgers()}>
                                    View Ledger
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-6 lg:col-span-2">
                        <Card>
                            <CardHeader className="border-b">
                                <div className="flex items-center justify-between gap-2">
                                    <CardTitle>Current Transaction</CardTitle>
                                    <Button size="sm">
                                        <Plus className="size-4" />
                                        Add Item
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Item
                                            </TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead className="pr-6 text-right">
                                                Action
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell className="pl-6">
                                                Tuition Downpayment
                                            </TableCell>
                                            <TableCell>Fee</TableCell>
                                            <TableCell>PHP 3,000.00</TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableCell className="pl-6">
                                                School Uniform
                                            </TableCell>
                                            <TableCell>Inventory</TableCell>
                                            <TableCell>PHP 650.00</TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                            <div className="flex items-center justify-between border-t px-6 py-4">
                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Total Amount
                                    </p>
                                    <p className="text-lg font-semibold">
                                        PHP 3,650.00
                                    </p>
                                </div>
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button>Process Transaction</Button>
                                    </DialogTrigger>
                                    <DialogContent className="sm:max-w-xl">
                                        <DialogHeader>
                                            <DialogTitle>
                                                Process Transaction
                                            </DialogTitle>
                                        </DialogHeader>

                                        <div className="space-y-4">
                                            <div className="rounded-md border">
                                                <div className="space-y-2 p-4">
                                                    <p className="text-sm font-medium">
                                                        Transaction Summary
                                                    </p>
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-muted-foreground">
                                                            Tuition Downpayment
                                                        </span>
                                                        <span>
                                                            PHP 3,000.00
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-muted-foreground">
                                                            School Uniform
                                                        </span>
                                                        <span>PHP 650.00</span>
                                                    </div>
                                                    <div className="flex items-center justify-between border-t pt-2 text-sm font-medium">
                                                        <span>Total</span>
                                                        <span>
                                                            PHP 3,650.00
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="grid gap-4 md:grid-cols-3">
                                                <div className="space-y-2">
                                                    <Label htmlFor="tendered-amount">
                                                        Tendered Amount
                                                    </Label>
                                                    <Input
                                                        id="tendered-amount"
                                                        type="number"
                                                        placeholder="0.00"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="payment-method">
                                                        Payment Method
                                                    </Label>
                                                    <Select defaultValue="cash">
                                                        <SelectTrigger id="payment-method">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
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
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="or-number">
                                                        OR Number
                                                    </Label>
                                                    <Input
                                                        id="or-number"
                                                        placeholder="OR-2026-0001"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <DialogFooter>
                                            <Button variant="outline">
                                                Cancel
                                            </Button>
                                            <Button>Confirm and Post</Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
