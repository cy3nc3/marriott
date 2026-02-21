import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
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
import { destroy, store, update } from '@/routes/finance/fee_structure';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fee Structure',
        href: '/finance/fee-structure',
    },
];

type FeeType = 'tuition' | 'miscellaneous' | 'books_modules';

type FeeItem = {
    id: number;
    grade_level_id: number;
    label: string;
    type: FeeType;
    category: string;
    amount: number;
};

type GradeLevelFee = {
    id: number;
    name: string;
    fee_items: FeeItem[];
};

interface Props {
    grade_level_fees: GradeLevelFee[];
}

const feeTypeOptions: { value: FeeType; label: string }[] = [
    { value: 'tuition', label: 'Tuition' },
    { value: 'miscellaneous', label: 'Miscellaneous' },
    { value: 'books_modules', label: 'Books and Modules' },
];

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount || 0);

export default function FeeStructure({ grade_level_fees }: Props) {
    const [isAddFeeDialogOpen, setIsAddFeeDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<FeeItem | null>(null);
    const [activeTab, setActiveTab] = useState<string>(
        String(grade_level_fees[0]?.id ?? ''),
    );

    const createForm = useForm({
        grade_level_id: String(grade_level_fees[0]?.id ?? ''),
        type: 'miscellaneous' as FeeType,
        name: '',
        amount: '',
    });

    const editForm = useForm({
        grade_level_id: '',
        type: 'miscellaneous' as FeeType,
        name: '',
        amount: '',
    });

    const selectedGradeLevel = useMemo(
        () =>
            grade_level_fees.find(
                (gradeLevel) => String(gradeLevel.id) === activeTab,
            ),
        [grade_level_fees, activeTab],
    );

    const openAddDialog = () => {
        const defaultGradeLevelId = selectedGradeLevel
            ? selectedGradeLevel.id
            : grade_level_fees[0]?.id;

        createForm.setData({
            grade_level_id: String(defaultGradeLevelId ?? ''),
            type: 'miscellaneous',
            name: '',
            amount: '',
        });
        createForm.clearErrors();
        setIsAddFeeDialogOpen(true);
    };

    const submitCreate = () => {
        createForm.submit(store(), {
            preserveScroll: true,
            onSuccess: () => {
                setIsAddFeeDialogOpen(false);
                createForm.reset();
                createForm.setData('type', 'miscellaneous');
                if (selectedGradeLevel) {
                    createForm.setData(
                        'grade_level_id',
                        String(selectedGradeLevel.id),
                    );
                }
            },
        });
    };

    const openEditDialog = (feeItem: FeeItem) => {
        setEditingItem(feeItem);
        editForm.setData({
            grade_level_id: String(feeItem.grade_level_id),
            type: feeItem.type,
            name: feeItem.label,
            amount: String(feeItem.amount),
        });
        editForm.clearErrors();
    };

    const submitEdit = () => {
        if (!editingItem) {
            return;
        }

        editForm.submit(update({ fee: editingItem.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setEditingItem(null);
                editForm.reset();
            },
        });
    };

    const removeItem = (feeItem: FeeItem) => {
        if (!confirm(`Remove "${feeItem.label}"?`)) {
            return;
        }

        router.delete(destroy({ fee: feeItem.id }).url, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fee Structure" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Fee Breakdown by Grade</CardTitle>
                            <Button
                                onClick={openAddDialog}
                                disabled={grade_level_fees.length === 0}
                            >
                                <Plus className="size-4" />
                                Add Fee Item
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {grade_level_fees.length > 0 ? (
                            <Tabs
                                value={activeTab}
                                onValueChange={setActiveTab}
                                className="w-full"
                            >
                                <TabsList className="mb-4">
                                    {grade_level_fees.map((feeRow) => (
                                        <TabsTrigger
                                            key={feeRow.id}
                                            value={String(feeRow.id)}
                                        >
                                            {feeRow.name}
                                        </TabsTrigger>
                                    ))}
                                </TabsList>

                                {grade_level_fees.map((feeRow) => {
                                    const tuitionTotal = feeRow.fee_items
                                        .filter((item) => item.type === 'tuition')
                                        .reduce(
                                            (sum, item) => sum + item.amount,
                                            0,
                                        );
                                    const miscellaneousTotal = feeRow.fee_items
                                        .filter(
                                            (item) =>
                                                item.type === 'miscellaneous',
                                        )
                                        .reduce(
                                            (sum, item) => sum + item.amount,
                                            0,
                                        );
                                    const booksAndModulesTotal =
                                        feeRow.fee_items
                                            .filter(
                                                (item) =>
                                                    item.type ===
                                                    'books_modules',
                                            )
                                            .reduce(
                                                (sum, item) =>
                                                    sum + item.amount,
                                                0,
                                            );
                                    const annualTotal =
                                        tuitionTotal +
                                        miscellaneousTotal +
                                        booksAndModulesTotal;

                                    return (
                                        <TabsContent
                                            key={feeRow.id}
                                            value={String(feeRow.id)}
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
                                                            <TableHead className="border-l text-right">
                                                                Amount
                                                            </TableHead>
                                                            <TableHead className="border-l pr-6 text-right">
                                                                Actions
                                                            </TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {feeRow.fee_items.map(
                                                            (item) => (
                                                                <TableRow
                                                                    key={
                                                                        item.id
                                                                    }
                                                                >
                                                                    <TableCell className="pl-6 font-medium">
                                                                        {
                                                                            item.label
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l">
                                                                        {
                                                                            item.category
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l text-right">
                                                                        {formatCurrency(
                                                                            item.amount,
                                                                        )}
                                                                    </TableCell>
                                                                    <TableCell className="border-l pr-6 text-right">
                                                                        <div className="flex justify-end gap-2">
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="icon"
                                                                                className="size-8"
                                                                                onClick={() =>
                                                                                    openEditDialog(
                                                                                        item,
                                                                                    )
                                                                                }
                                                                            >
                                                                                <Pencil className="size-4" />
                                                                            </Button>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="icon"
                                                                                className="size-8"
                                                                                onClick={() =>
                                                                                    removeItem(
                                                                                        item,
                                                                                    )
                                                                                }
                                                                            >
                                                                                <Trash2 className="size-4" />
                                                                            </Button>
                                                                        </div>
                                                                    </TableCell>
                                                                </TableRow>
                                                            ),
                                                        )}
                                                        {feeRow.fee_items
                                                            .length === 0 && (
                                                            <TableRow>
                                                                <TableCell
                                                                    colSpan={4}
                                                                    className="py-8 text-center text-sm text-muted-foreground"
                                                                >
                                                                    No fee items
                                                                    yet for this
                                                                    grade level.
                                                                </TableCell>
                                                            </TableRow>
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
                                                        {formatCurrency(
                                                            tuitionTotal,
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="rounded-md border px-3 py-2">
                                                    <p className="text-xs text-muted-foreground">
                                                        Miscellaneous Total
                                                    </p>
                                                    <p className="text-sm font-semibold">
                                                        {formatCurrency(
                                                            miscellaneousTotal,
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="rounded-md border px-3 py-2">
                                                    <p className="text-xs text-muted-foreground">
                                                        Books and Modules Total
                                                    </p>
                                                    <p className="text-sm font-semibold">
                                                        {formatCurrency(
                                                            booksAndModulesTotal,
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="rounded-md border px-3 py-2">
                                                    <p className="text-xs text-muted-foreground">
                                                        Annual Total
                                                    </p>
                                                    <p className="text-sm font-semibold">
                                                        {formatCurrency(
                                                            annualTotal,
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        </TabsContent>
                                    );
                                })}
                            </Tabs>
                        ) : (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No grade levels found. Please set up grade
                                levels first.
                            </div>
                        )}
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
                                <Select
                                    value={createForm.data.grade_level_id}
                                    onValueChange={(value) =>
                                        createForm.setData(
                                            'grade_level_id',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {grade_level_fees.map((gradeLevel) => (
                                            <SelectItem
                                                key={gradeLevel.id}
                                                value={String(gradeLevel.id)}
                                            >
                                                {gradeLevel.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {createForm.errors.grade_level_id && (
                                    <p className="text-sm text-destructive">
                                        {createForm.errors.grade_level_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Fee Label</Label>
                                <Input
                                    placeholder="e.g. Laboratory Fee"
                                    value={createForm.data.name}
                                    onChange={(event) =>
                                        createForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                                {createForm.errors.name && (
                                    <p className="text-sm text-destructive">
                                        {createForm.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Category</Label>
                                    <Select
                                        value={createForm.data.type}
                                        onValueChange={(value: FeeType) =>
                                            createForm.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {feeTypeOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {createForm.errors.type && (
                                        <p className="text-sm text-destructive">
                                            {createForm.errors.type}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Amount</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={createForm.data.amount}
                                        onChange={(event) =>
                                            createForm.setData(
                                                'amount',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {createForm.errors.amount && (
                                        <p className="text-sm text-destructive">
                                            {createForm.errors.amount}
                                        </p>
                                    )}
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
                                onClick={submitCreate}
                                disabled={createForm.processing}
                            >
                                Add Item
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={editingItem !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setEditingItem(null);
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Fee Item</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Grade Level</Label>
                                <Select
                                    value={editForm.data.grade_level_id}
                                    onValueChange={(value) =>
                                        editForm.setData('grade_level_id', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {grade_level_fees.map((gradeLevel) => (
                                            <SelectItem
                                                key={gradeLevel.id}
                                                value={String(gradeLevel.id)}
                                            >
                                                {gradeLevel.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {editForm.errors.grade_level_id && (
                                    <p className="text-sm text-destructive">
                                        {editForm.errors.grade_level_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Fee Label</Label>
                                <Input
                                    value={editForm.data.name}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                                {editForm.errors.name && (
                                    <p className="text-sm text-destructive">
                                        {editForm.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Category</Label>
                                    <Select
                                        value={editForm.data.type}
                                        onValueChange={(value: FeeType) =>
                                            editForm.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {feeTypeOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {editForm.errors.type && (
                                        <p className="text-sm text-destructive">
                                            {editForm.errors.type}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Amount</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={editForm.data.amount}
                                        onChange={(event) =>
                                            editForm.setData(
                                                'amount',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {editForm.errors.amount && (
                                        <p className="text-sm text-destructive">
                                            {editForm.errors.amount}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setEditingItem(null)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitEdit}
                                disabled={editForm.processing}
                            >
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
