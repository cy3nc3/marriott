import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, Trash2 } from 'lucide-react';
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
import AppLayout from '@/layouts/app-layout';
import { cashier_panel, student_ledgers } from '@/routes/finance';
import { store_transaction } from '@/routes/finance/cashier_panel';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cashier Panel',
        href: cashier_panel().url,
    },
];

type StudentOption = {
    id: number;
    lrn: string;
    name: string;
};

type SelectedStudent = {
    id: number;
    lrn: string;
    name: string;
    grade_and_section: string;
    payment_plan: string | null;
    stated_downpayment: number;
    remaining_balance: number;
};

type FeeOption = {
    id: number;
    name: string;
    type: string;
    amount: number;
};

type InventoryOption = {
    id: number;
    name: string;
    type: string;
    price: number;
};

type CurrentTransactionItem = {
    id: string;
    type: 'fee' | 'inventory' | 'custom';
    description: string;
    amount: number;
    fee_id: number | null;
    inventory_item_id: number | null;
};

interface Props {
    students: StudentOption[];
    selected_student: SelectedStudent | null;
    fee_options: FeeOption[];
    inventory_options: InventoryOption[];
    filters: {
        search?: string;
        student_id?: string | number;
    };
}

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount || 0);

const formatPaymentPlan = (value: string | null) => {
    if (!value) {
        return '-';
    }

    if (value === 'semi-annual') {
        return 'Semi-Annual';
    }

    if (value === 'cash') {
        return 'Cash';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
};

export default function CashierPanel({
    students,
    selected_student,
    fee_options,
    inventory_options,
    filters,
}: Props) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedStudentId, setSelectedStudentId] = useState(
        selected_student?.id
            ? String(selected_student.id)
            : filters.student_id
              ? String(filters.student_id)
              : '',
    );
    const [transactionItems, setTransactionItems] = useState<
        CurrentTransactionItem[]
    >([]);
    const [isAddItemOpen, setIsAddItemOpen] = useState(false);
    const [isProcessDialogOpen, setIsProcessDialogOpen] = useState(false);

    const [itemType, setItemType] = useState<'fee' | 'inventory' | 'custom'>(
        fee_options.length > 0
            ? 'fee'
            : inventory_options.length > 0
              ? 'inventory'
              : 'custom',
    );
    const [selectedFeeId, setSelectedFeeId] = useState<string>(
        fee_options[0] ? String(fee_options[0].id) : '',
    );
    const [selectedInventoryId, setSelectedInventoryId] = useState<string>(
        inventory_options[0] ? String(inventory_options[0].id) : '',
    );
    const [itemDescription, setItemDescription] = useState('');
    const [itemAmount, setItemAmount] = useState('');

    const transactionForm = useForm({
        student_id: '',
        or_number: '',
        payment_mode: 'cash',
        reference_no: '',
        remarks: '',
        tendered_amount: '',
        items: [] as Array<{
            type: 'fee' | 'inventory' | 'custom';
            description: string;
            amount: number;
            fee_id?: number;
            inventory_item_id?: number;
        }>,
    });

    const totalAmount = useMemo(() => {
        return transactionItems.reduce((sum, item) => sum + item.amount, 0);
    }, [transactionItems]);

    const selectedFee = useMemo(() => {
        const parsedFeeId = Number(selectedFeeId);

        return fee_options.find((fee) => fee.id === parsedFeeId) ?? null;
    }, [fee_options, selectedFeeId]);

    const selectedInventory = useMemo(() => {
        const parsedInventoryId = Number(selectedInventoryId);

        return (
            inventory_options.find(
                (inventoryItem) => inventoryItem.id === parsedInventoryId,
            ) ?? null
        );
    }, [inventory_options, selectedInventoryId]);

    const runSearch = (studentId = selectedStudentId) => {
        router.get(
            cashier_panel.url({
                query: {
                    search: searchQuery || undefined,
                    student_id: studentId || undefined,
                },
            }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const selectStudent = (value: string) => {
        setSelectedStudentId(value);
        runSearch(value);
    };

    const openAddItemDialog = () => {
        if (fee_options.length > 0) {
            const defaultFee = fee_options[0];

            setItemType('fee');
            setSelectedFeeId(String(defaultFee.id));
            setItemDescription(defaultFee.name);
            setItemAmount(String(defaultFee.amount));
        } else if (inventory_options.length > 0) {
            const defaultInventory = inventory_options[0];

            setItemType('inventory');
            setSelectedInventoryId(String(defaultInventory.id));
            setItemDescription(defaultInventory.name);
            setItemAmount(String(defaultInventory.price));
        } else {
            setItemType('custom');
            setItemDescription('');
            setItemAmount('');
        }

        setIsAddItemOpen(true);
    };

    const applyFeeSelection = (value: string) => {
        setSelectedFeeId(value);
        const option = fee_options.find((fee) => fee.id === Number(value));
        if (option) {
            setItemDescription(option.name);
            setItemAmount(String(option.amount));
        }
    };

    const applyInventorySelection = (value: string) => {
        setSelectedInventoryId(value);
        const option = inventory_options.find(
            (inventoryItem) => inventoryItem.id === Number(value),
        );
        if (option) {
            setItemDescription(option.name);
            setItemAmount(String(option.price));
        }
    };

    const addItemToCurrentTransaction = () => {
        const parsedAmount = Number(itemAmount);
        if (!itemDescription.trim() || !Number.isFinite(parsedAmount)) {
            return;
        }

        if (parsedAmount <= 0) {
            return;
        }

        setTransactionItems((previousItems) => [
            ...previousItems,
            {
                id: `${Date.now()}-${Math.random()}`,
                type: itemType,
                description: itemDescription.trim(),
                amount: Number(parsedAmount.toFixed(2)),
                fee_id: itemType === 'fee' ? Number(selectedFeeId) : null,
                inventory_item_id:
                    itemType === 'inventory'
                        ? Number(selectedInventoryId)
                        : null,
            },
        ]);

        setIsAddItemOpen(false);
        setItemDescription('');
        setItemAmount('');
    };

    const removeTransactionItem = (itemId: string) => {
        setTransactionItems((previousItems) =>
            previousItems.filter((item) => item.id !== itemId),
        );
    };

    const openProcessDialog = () => {
        if (!selected_student || transactionItems.length === 0) {
            return;
        }

        transactionForm.clearErrors();
        transactionForm.setData({
            student_id: String(selected_student.id),
            or_number: '',
            payment_mode: 'cash',
            reference_no: '',
            remarks: '',
            tendered_amount: String(Number(totalAmount.toFixed(2))),
            items: [],
        });
        setIsProcessDialogOpen(true);
    };

    const postTransaction = () => {
        if (!selected_student || transactionItems.length === 0) {
            return;
        }

        const payloadItems = transactionItems.map((item) => ({
            type: item.type,
            description: item.description,
            amount: Number(item.amount.toFixed(2)),
            ...(item.fee_id ? { fee_id: item.fee_id } : {}),
            ...(item.inventory_item_id
                ? { inventory_item_id: item.inventory_item_id }
                : {}),
        }));

        transactionForm.transform((formData) => ({
            ...formData,
            student_id: Number(selected_student.id),
            tendered_amount: Number(formData.tendered_amount || 0),
            items: payloadItems,
        }));

        transactionForm.submit(store_transaction(), {
            preserveScroll: true,
            onSuccess: () => {
                setIsProcessDialogOpen(false);
                setTransactionItems([]);
                transactionForm.reset();
                transactionForm.setData('payment_mode', 'cash');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashier Panel" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <CardTitle>Student Lookup</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-3 lg:grid-cols-[1fr_20rem_auto]">
                            <div className="relative">
                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search by LRN or student name"
                                    className="pl-9"
                                    value={searchQuery}
                                    onChange={(event) =>
                                        setSearchQuery(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            event.preventDefault();
                                            runSearch();
                                        }
                                    }}
                                />
                            </div>

                            <Select
                                value={selectedStudentId}
                                onValueChange={selectStudent}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select student" />
                                </SelectTrigger>
                                <SelectContent>
                                    {students.map((student) => (
                                        <SelectItem
                                            key={student.id}
                                            value={String(student.id)}
                                        >
                                            {student.name} ({student.lrn})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Button type="button" onClick={() => runSearch()}>
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="gap-2 lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>Student Profile</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-6">
                            {selected_student ? (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Name
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.name}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                LRN
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.lrn}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-sm text-muted-foreground">
                                            Grade and Section
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selected_student.grade_and_section}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-sm text-muted-foreground">
                                            Payment Plan
                                        </p>
                                        <p className="text-sm font-medium">
                                            {formatPaymentPlan(
                                                selected_student.payment_plan,
                                            )}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-sm text-muted-foreground">
                                            Remaining Balance
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {formatCurrency(
                                                selected_student.remaining_balance,
                                            )}
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
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Search a student to begin a transaction.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex items-center justify-between gap-2">
                                <CardTitle>Current Transaction</CardTitle>
                                <Button
                                    size="sm"
                                    onClick={openAddItemDialog}
                                    disabled={!selected_student}
                                >
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
                                        <TableHead className="border-l">
                                            Type
                                        </TableHead>
                                        <TableHead className="border-l text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactionItems.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="pl-6">
                                                {item.description}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {item.type === 'fee'
                                                    ? 'Fee'
                                                    : item.type === 'inventory'
                                                      ? 'Inventory'
                                                      : 'Custom'}
                                            </TableCell>
                                            <TableCell className="border-l text-right">
                                                {formatCurrency(item.amount)}
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        removeTransactionItem(
                                                            item.id,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {transactionItems.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                Add items to start this
                                                transaction.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                        <div className="flex items-center justify-between border-t px-6 py-4">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Total Amount
                                </p>
                                <p className="text-lg font-semibold">
                                    {formatCurrency(totalAmount)}
                                </p>
                            </div>
                            <Button
                                onClick={openProcessDialog}
                                disabled={
                                    !selected_student ||
                                    transactionItems.length === 0
                                }
                            >
                                Process Transaction
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>

            <Dialog open={isAddItemOpen} onOpenChange={setIsAddItemOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add Item</DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="space-y-2">
                            <Label>Item Type</Label>
                            <Select
                                value={itemType}
                                onValueChange={(
                                    value: 'fee' | 'inventory' | 'custom',
                                ) => {
                                    setItemType(value);

                                    if (value === 'fee' && selectedFee) {
                                        setItemDescription(selectedFee.name);
                                        setItemAmount(
                                            String(selectedFee.amount),
                                        );
                                    }

                                    if (
                                        value === 'inventory' &&
                                        selectedInventory
                                    ) {
                                        setItemDescription(
                                            selectedInventory.name,
                                        );
                                        setItemAmount(
                                            String(selectedInventory.price),
                                        );
                                    }

                                    if (value === 'custom') {
                                        setItemDescription('');
                                        setItemAmount('');
                                    }
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="fee">Fee</SelectItem>
                                    <SelectItem value="inventory">
                                        Inventory
                                    </SelectItem>
                                    <SelectItem value="custom">
                                        Custom
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {itemType === 'fee' && (
                            <div className="space-y-2">
                                <Label>Fee Item</Label>
                                <Select
                                    value={selectedFeeId}
                                    onValueChange={applyFeeSelection}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select fee item" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {fee_options.map((fee) => (
                                            <SelectItem
                                                key={fee.id}
                                                value={String(fee.id)}
                                            >
                                                {fee.name} (
                                                {formatCurrency(fee.amount)})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {itemType === 'inventory' && (
                            <div className="space-y-2">
                                <Label>Inventory Item</Label>
                                <Select
                                    value={selectedInventoryId}
                                    onValueChange={applyInventorySelection}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select inventory item" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {inventory_options.map(
                                            (inventoryItem) => (
                                                <SelectItem
                                                    key={inventoryItem.id}
                                                    value={String(
                                                        inventoryItem.id,
                                                    )}
                                                >
                                                    {inventoryItem.name} (
                                                    {formatCurrency(
                                                        inventoryItem.price,
                                                    )}
                                                    )
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label>Description</Label>
                            <Input
                                value={itemDescription}
                                onChange={(event) =>
                                    setItemDescription(event.target.value)
                                }
                                placeholder="Description"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Amount</Label>
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={itemAmount}
                                onChange={(event) =>
                                    setItemAmount(event.target.value)
                                }
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsAddItemOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button onClick={addItemToCurrentTransaction}>
                            Add to Transaction
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={isProcessDialogOpen}
                onOpenChange={setIsProcessDialogOpen}
            >
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Process Transaction</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="rounded-md border p-4">
                            <p className="text-sm font-medium">
                                Transaction Summary
                            </p>
                            <div className="mt-3 space-y-2">
                                {transactionItems.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <span className="text-muted-foreground">
                                            {item.description}
                                        </span>
                                        <span>
                                            {formatCurrency(item.amount)}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex items-center justify-between border-t pt-2 text-sm font-medium">
                                    <span>Total</span>
                                    <span>{formatCurrency(totalAmount)}</span>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="tendered-amount">
                                    Tendered Amount
                                </Label>
                                <Input
                                    id="tendered-amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={transactionForm.data.tendered_amount}
                                    onChange={(event) =>
                                        transactionForm.setData(
                                            'tendered_amount',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="0.00"
                                />
                                {transactionForm.errors.tendered_amount && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.tendered_amount}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="payment-method">
                                    Payment Method
                                </Label>
                                <Select
                                    value={transactionForm.data.payment_mode}
                                    onValueChange={(value) =>
                                        transactionForm.setData(
                                            'payment_mode',
                                            value,
                                        )
                                    }
                                >
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
                                        <SelectItem value="bank_transfer">
                                            Bank Transfer
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {transactionForm.errors.payment_mode && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.payment_mode}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="or-number">OR Number</Label>
                                <Input
                                    id="or-number"
                                    value={transactionForm.data.or_number}
                                    onChange={(event) =>
                                        transactionForm.setData(
                                            'or_number',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="OR-2026-0001"
                                />
                                {transactionForm.errors.or_number && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.or_number}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reference-no">
                                    Reference Number
                                </Label>
                                <Input
                                    id="reference-no"
                                    value={transactionForm.data.reference_no}
                                    onChange={(event) =>
                                        transactionForm.setData(
                                            'reference_no',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Optional"
                                />
                                {transactionForm.errors.reference_no && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.reference_no}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="remarks">Remarks</Label>
                            <Input
                                id="remarks"
                                value={transactionForm.data.remarks}
                                onChange={(event) =>
                                    transactionForm.setData(
                                        'remarks',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional note"
                            />
                            {transactionForm.errors.remarks && (
                                <p className="text-sm text-destructive">
                                    {transactionForm.errors.remarks}
                                </p>
                            )}
                            {transactionForm.errors.items && (
                                <p className="text-sm text-destructive">
                                    {transactionForm.errors.items}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsProcessDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={postTransaction}
                            disabled={transactionForm.processing}
                        >
                            Confirm and Post
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
