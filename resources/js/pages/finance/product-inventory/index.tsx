import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
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
import {
    destroy,
    store,
    update,
} from '@/routes/finance/product_inventory';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product Inventory',
        href: '/finance/product-inventory',
    },
];

type ItemType = 'uniform' | 'book' | 'other';

type ProductItemRow = {
    id: number;
    name: string;
    type: ItemType;
    type_label: string;
    price: number;
    updated_at: string | null;
};

interface Props {
    product_items: ProductItemRow[];
}

const itemTypeOptions: { value: ItemType; label: string }[] = [
    { value: 'uniform', label: 'Uniform' },
    { value: 'book', label: 'Book' },
    { value: 'other', label: 'Other' },
];

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount || 0);

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
    });
};

export default function ProductInventory({ product_items }: Props) {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<ProductItemRow | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [categoryFilter, setCategoryFilter] = useState<
        'all-categories' | ItemType
    >('all-categories');

    const createForm = useForm({
        name: '',
        type: 'uniform' as ItemType,
        price: '',
    });

    const editForm = useForm({
        name: '',
        type: 'uniform' as ItemType,
        price: '',
    });

    const filteredRows = useMemo(() => {
        return product_items.filter((row) => {
            const matchesSearch = row.name
                .toLowerCase()
                .includes(searchQuery.toLowerCase());
            const matchesCategory =
                categoryFilter === 'all-categories' ||
                row.type === categoryFilter;

            return matchesSearch && matchesCategory;
        });
    }, [product_items, searchQuery, categoryFilter]);

    const openAddDialog = () => {
        createForm.setData({
            name: '',
            type: 'uniform',
            price: '',
        });
        createForm.clearErrors();
        setIsAddModalOpen(true);
    };

    const submitCreate = () => {
        createForm.submit(store(), {
            preserveScroll: true,
            onSuccess: () => {
                setIsAddModalOpen(false);
                createForm.reset();
                createForm.setData('type', 'uniform');
            },
        });
    };

    const openEditDialog = (item: ProductItemRow) => {
        setEditingItem(item);
        editForm.setData({
            name: item.name,
            type: item.type,
            price: String(item.price),
        });
        editForm.clearErrors();
    };

    const submitEdit = () => {
        if (!editingItem) {
            return;
        }

        editForm.submit(update({ inventoryItem: editingItem.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setEditingItem(null);
                editForm.reset();
            },
        });
    };

    const removeItem = (item: ProductItemRow) => {
        if (!confirm(`Remove "${item.name}" from price catalog?`)) {
            return;
        }

        router.delete(destroy({ inventoryItem: item.id }).url, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Inventory" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Product Price Catalog</CardTitle>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <div className="relative">
                                    <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search product"
                                        className="w-full pl-9 sm:w-56"
                                        value={searchQuery}
                                        onChange={(event) =>
                                            setSearchQuery(event.target.value)
                                        }
                                    />
                                </div>
                                <Select
                                    value={categoryFilter}
                                    onValueChange={(
                                        value: 'all-categories' | ItemType,
                                    ) => setCategoryFilter(value)}
                                >
                                    <SelectTrigger className="w-full sm:w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all-categories">
                                            All Categories
                                        </SelectItem>
                                        {itemTypeOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button onClick={openAddDialog}>
                                    <Plus className="size-4" />
                                    Add Item
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Product
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Category
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Unit Price
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Last Updated
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredRows.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.name}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.type_label}
                                        </TableCell>
                                        <TableCell className="border-l text-right">
                                            {formatCurrency(row.price)}
                                        </TableCell>
                                        <TableCell className="border-l text-right">
                                            {formatDate(row.updated_at)}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        openEditDialog(row)
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        removeItem(row)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {filteredRows.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No products found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add Product Price</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Product Name</Label>
                                <Input
                                    placeholder="e.g. PE Shirt (Small)"
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
                                        onValueChange={(value: ItemType) =>
                                            createForm.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {itemTypeOptions.map((option) => (
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
                                    <Label>Unit Price</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={createForm.data.price}
                                        onChange={(event) =>
                                            createForm.setData(
                                                'price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {createForm.errors.price && (
                                        <p className="text-sm text-destructive">
                                            {createForm.errors.price}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsAddModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitCreate}
                                disabled={createForm.processing}
                            >
                                Save Item
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
                            <DialogTitle>Edit Product Price</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Product Name</Label>
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
                                        onValueChange={(value: ItemType) =>
                                            editForm.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {itemTypeOptions.map((option) => (
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
                                    <Label>Unit Price</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={editForm.data.price}
                                        onChange={(event) =>
                                            editForm.setData(
                                                'price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {editForm.errors.price && (
                                        <p className="text-sm text-destructive">
                                            {editForm.errors.price}
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
