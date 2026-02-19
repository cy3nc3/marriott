import { Head } from '@inertiajs/react';
import { CopyPlus, Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fee Structure',
        href: '/finance/fee-structure',
    },
];

type FeeItem = {
    label: string;
    category: 'Tuition' | 'Miscellaneous' | 'Books and Modules';
    amount: string;
};

type GradeLevelFee = {
    gradeLevel: string;
    feeItems: FeeItem[];
};

export default function FeeStructure() {
    const [isAddFeeDialogOpen, setIsAddFeeDialogOpen] = useState(false);

    const gradeLevelFees: GradeLevelFee[] = [
        {
            gradeLevel: 'Grade 7',
            feeItems: [
                {
                    label: 'Tuition Fee',
                    category: 'Tuition',
                    amount: '20,000.00',
                },
                {
                    label: 'Laboratory Fee',
                    category: 'Miscellaneous',
                    amount: '2,000.00',
                },
                {
                    label: 'ID and School Paper',
                    category: 'Miscellaneous',
                    amount: '3,000.00',
                },
                {
                    label: 'Books and Modules',
                    category: 'Books and Modules',
                    amount: '2,000.00',
                },
            ],
        },
        {
            gradeLevel: 'Grade 8',
            feeItems: [
                {
                    label: 'Tuition Fee',
                    category: 'Tuition',
                    amount: '20,000.00',
                },
                {
                    label: 'Laboratory Fee',
                    category: 'Miscellaneous',
                    amount: '2,200.00',
                },
                {
                    label: 'ID and School Paper',
                    category: 'Miscellaneous',
                    amount: '3,000.00',
                },
                {
                    label: 'Books and Modules',
                    category: 'Books and Modules',
                    amount: '2,000.00',
                },
            ],
        },
        {
            gradeLevel: 'Grade 9',
            feeItems: [
                {
                    label: 'Tuition Fee',
                    category: 'Tuition',
                    amount: '21,000.00',
                },
                {
                    label: 'Laboratory Fee',
                    category: 'Miscellaneous',
                    amount: '2,300.00',
                },
                {
                    label: 'ID and School Paper',
                    category: 'Miscellaneous',
                    amount: '3,000.00',
                },
                {
                    label: 'Books and Modules',
                    category: 'Books and Modules',
                    amount: '2,300.00',
                },
            ],
        },
        {
            gradeLevel: 'Grade 10',
            feeItems: [
                {
                    label: 'Tuition Fee',
                    category: 'Tuition',
                    amount: '21,500.00',
                },
                {
                    label: 'Laboratory Fee',
                    category: 'Miscellaneous',
                    amount: '2,300.00',
                },
                {
                    label: 'ID and School Paper',
                    category: 'Miscellaneous',
                    amount: '3,000.00',
                },
                {
                    label: 'Books and Modules',
                    category: 'Books and Modules',
                    amount: '2,500.00',
                },
            ],
        },
    ];

    const toNumber = (value: string) =>
        Number.parseFloat(value.replace(',', ''));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fee Structure" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Fee Breakdown by Grade</CardTitle>
                            <div className="flex items-center gap-2">
                                <Select defaultValue="sy-2025-2026">
                                    <SelectTrigger className="w-44">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sy-2025-2026">
                                            SY 2025-2026
                                        </SelectItem>
                                        <SelectItem value="sy-2024-2025">
                                            SY 2024-2025
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline">
                                    <CopyPlus className="size-4" />
                                    Use Previous Year
                                </Button>
                                <Button
                                    onClick={() => setIsAddFeeDialogOpen(true)}
                                >
                                    <Plus className="size-4" />
                                    Add Fee Item
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Tabs defaultValue="Grade 7" className="w-full">
                            <TabsList className="mb-4">
                                {gradeLevelFees.map((feeRow) => (
                                    <TabsTrigger
                                        key={feeRow.gradeLevel}
                                        value={feeRow.gradeLevel}
                                    >
                                        {feeRow.gradeLevel}
                                    </TabsTrigger>
                                ))}
                            </TabsList>

                            {gradeLevelFees.map((feeRow) => {
                                const tuitionTotal = feeRow.feeItems
                                    .filter(
                                        (item) => item.category === 'Tuition',
                                    )
                                    .reduce(
                                        (sum, item) =>
                                            sum + toNumber(item.amount),
                                        0,
                                    );
                                const miscellaneousTotal = feeRow.feeItems
                                    .filter(
                                        (item) =>
                                            item.category === 'Miscellaneous',
                                    )
                                    .reduce(
                                        (sum, item) =>
                                            sum + toNumber(item.amount),
                                        0,
                                    );
                                const booksAndModulesTotal = feeRow.feeItems
                                    .filter(
                                        (item) =>
                                            item.category ===
                                            'Books and Modules',
                                    )
                                    .reduce(
                                        (sum, item) =>
                                            sum + toNumber(item.amount),
                                        0,
                                    );
                                const annualTotal =
                                    tuitionTotal +
                                    miscellaneousTotal +
                                    booksAndModulesTotal;

                                return (
                                    <TabsContent
                                        key={feeRow.gradeLevel}
                                        value={feeRow.gradeLevel}
                                    >
                                        <div className="overflow-hidden rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead className="pl-6">
                                                            Fee Label
                                                        </TableHead>
                                                        <TableHead className="border-l">
                                                            Category
                                                        </TableHead>
                                                        <TableHead className="border-l pr-6 text-right">
                                                            Amount
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {feeRow.feeItems.map(
                                                        (item) => (
                                                            <TableRow
                                                                key={`${feeRow.gradeLevel}-${item.label}`}
                                                            >
                                                                <TableCell className="pl-6 font-medium">
                                                                    {item.label}
                                                                </TableCell>
                                                                <TableCell className="border-l">
                                                                    {
                                                                        item.category
                                                                    }
                                                                </TableCell>
                                                                <TableCell className="border-l pr-6 text-right">
                                                                    PHP{' '}
                                                                    {
                                                                        item.amount
                                                                    }
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>

                                        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                            <div className="rounded-md border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    Tuition Total
                                                </p>
                                                <p className="text-sm font-semibold">
                                                    PHP{' '}
                                                    {tuitionTotal.toLocaleString(
                                                        'en-US',
                                                        {
                                                            minimumFractionDigits: 2,
                                                        },
                                                    )}
                                                </p>
                                            </div>
                                            <div className="rounded-md border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    Miscellaneous Total
                                                </p>
                                                <p className="text-sm font-semibold">
                                                    PHP{' '}
                                                    {miscellaneousTotal.toLocaleString(
                                                        'en-US',
                                                        {
                                                            minimumFractionDigits: 2,
                                                        },
                                                    )}
                                                </p>
                                            </div>
                                            <div className="rounded-md border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    Books and Modules Total
                                                </p>
                                                <p className="text-sm font-semibold">
                                                    PHP{' '}
                                                    {booksAndModulesTotal.toLocaleString(
                                                        'en-US',
                                                        {
                                                            minimumFractionDigits: 2,
                                                        },
                                                    )}
                                                </p>
                                            </div>
                                            <div className="rounded-md border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    Annual Total
                                                </p>
                                                <p className="text-sm font-semibold">
                                                    PHP{' '}
                                                    {annualTotal.toLocaleString(
                                                        'en-US',
                                                        {
                                                            minimumFractionDigits: 2,
                                                        },
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </TabsContent>
                                );
                            })}
                        </Tabs>
                    </CardContent>
                </Card>

                <Dialog
                    open={isAddFeeDialogOpen}
                    onOpenChange={setIsAddFeeDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add Fee Item</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Grade Level</Label>
                                <Select defaultValue="grade-7">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="grade-7">
                                            Grade 7
                                        </SelectItem>
                                        <SelectItem value="grade-8">
                                            Grade 8
                                        </SelectItem>
                                        <SelectItem value="grade-9">
                                            Grade 9
                                        </SelectItem>
                                        <SelectItem value="grade-10">
                                            Grade 10
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Fee Label</Label>
                                <Input placeholder="e.g. Laboratory Fee" />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Category</Label>
                                    <Select defaultValue="miscellaneous">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="tuition">
                                                Tuition
                                            </SelectItem>
                                            <SelectItem value="miscellaneous">
                                                Miscellaneous
                                            </SelectItem>
                                            <SelectItem value="books-modules">
                                                Books and Modules
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Amount</Label>
                                    <Input type="number" placeholder="0.00" />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsAddFeeDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={() => setIsAddFeeDialogOpen(false)}
                            >
                                Add Item
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
