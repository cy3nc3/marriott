import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product Inventory',
        href: '/finance/product-inventory',
    },
];

export default function ProductInventory() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product Inventory" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b">
                        <CardTitle className="text-lg">
                            Inventory Management
                        </CardTitle>

                        <Dialog
                            open={isAddModalOpen}
                            onOpenChange={setIsAddModalOpen}
                        >
                            <DialogTrigger asChild>
                                <Button size="sm" className="gap-1">
                                    <Plus className="size-4" />
                                    Add Item
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Add New Item</DialogTitle>
                                    <DialogDescription>
                                        Enter the details for the new inventory
                                        item.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Item Name</Label>
                                        <Input
                                            id="name"
                                            placeholder="Enter item name..."
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Type</Label>
                                        <Select>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Type..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Book">
                                                    Book
                                                </SelectItem>
                                                <SelectItem value="Uniform">
                                                    Uniform
                                                </SelectItem>
                                                <SelectItem value="Stationery">
                                                    Stationery
                                                </SelectItem>
                                                <SelectItem value="Other">
                                                    Other
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="price">Price</Label>
                                        <div className="relative">
                                            <span className="absolute top-2.5 left-3 text-sm text-muted-foreground">
                                                ₱
                                            </span>
                                            <Input
                                                id="price"
                                                type="number"
                                                step="0.01"
                                                className="pl-7"
                                                placeholder="0.00"
                                            />
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
                                        onClick={() => setIsAddModalOpen(false)}
                                    >
                                        Save
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </CardHeader>
                    <CardContent className="">
                        <Table>
                            <TableHeader>
                                <TableRow className="">
                                    <TableHead className="">
                                        Item Name
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Type
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Price
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        School Uniform (Male, Small)
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="secondary">
                                            Uniform
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right font-mono font-bold">
                                        450.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Mathematics 7 Textbook
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="secondary">Book</Badge>
                                    </TableCell>
                                    <TableCell className="text-right font-mono font-bold">
                                        1,200.00
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        School ID Lace
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="secondary">Other</Badge>
                                    </TableCell>
                                    <TableCell className="text-right font-mono font-bold">
                                        50.00
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
