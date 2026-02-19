import { Head } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product Inventory',
        href: '/finance/product-inventory',
    },
];

export default function ProductInventory() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    const inventoryRows = [
        {
            productName: 'School Uniform (Male - Small)',
            category: 'Uniform',
            unitPrice: '450.00',
            updatedBy: 'Cashier A',
        },
        {
            productName: 'Mathematics 7 Textbook',
            category: 'Book',
            unitPrice: '1,200.00',
            updatedBy: 'Cashier B',
        },
        {
            productName: 'School ID Lace',
            category: 'Other',
            unitPrice: '50.00',
            updatedBy: 'Cashier A',
        },
        {
            productName: 'PE Shirt (Medium)',
            category: 'Uniform',
            unitPrice: '280.00',
            updatedBy: 'Cashier C',
        },
    ];

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
                                    />
                                </div>
                                <Select defaultValue="all-categories">
                                    <SelectTrigger className="w-full sm:w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all-categories">
                                            All Categories
                                        </SelectItem>
                                        <SelectItem value="uniform">
                                            Uniform
                                        </SelectItem>
                                        <SelectItem value="book">
                                            Book
                                        </SelectItem>
                                        <SelectItem value="other">
                                            Other
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={() => setIsAddModalOpen(true)}>
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
                                    <TableHead className="border-l pr-6 text-right">
                                        Updated By
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {inventoryRows.map((row) => (
                                    <TableRow key={row.productName}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.productName}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.category}
                                        </TableCell>
                                        <TableCell className="border-l text-right">
                                            PHP {row.unitPrice}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            {row.updatedBy}
                                        </TableCell>
                                    </TableRow>
                                ))}
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
                                <Input placeholder="e.g. PE Shirt (Small)" />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Category</Label>
                                    <Select defaultValue="uniform">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="uniform">
                                                Uniform
                                            </SelectItem>
                                            <SelectItem value="book">
                                                Book
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Other
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Unit Price</Label>
                                    <Input type="number" placeholder="0.00" />
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
                            <Button onClick={() => setIsAddModalOpen(false)}>
                                Save Item
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
