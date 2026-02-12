import { useState } from 'react';
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
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    Search, 
    Plus, 
    Trash2, 
    ShoppingCart,
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Label } from "@/components/ui/label"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cashier Panel',
        href: '/finance/cashier-panel',
    },
];

export default function CashierPanel() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashier Panel" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
                    
                    {/* Left Column: Student Context */}
                    <div className="lg:col-span-1 flex flex-col gap-4">
                        {/* Search Bar */}
                        <Card>
                            <CardContent className="pt-6">
                                <Label htmlFor="search" className="mb-2 block">Find Student</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="Enter Student LRN or Name..."
                                        className="pl-10"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Student Card */}
                        <Card className="flex-1 flex flex-col">
                            <CardContent className="p-0 flex-1 flex flex-col">
                                <div className="p-6 flex flex-col items-center bg-muted/30 border-b space-y-4">
                                    <Avatar size="2xl" className="border-4 border-background shadow-sm">
                                        <AvatarImage src="" />
                                        <AvatarFallback>JD</AvatarFallback>
                                    </Avatar>
                                    <div className="text-center">
                                        <h2 className="text-xl font-bold">Juan Dela Cruz</h2>
                                        <p className="text-sm text-muted-foreground">1234567890123</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <div className="px-2 py-1 text-xs font-semibold rounded-full bg-primary/10 text-primary">Grade 7</div>
                                        <div className="px-2 py-1 text-xs font-semibold rounded-full bg-primary/10 text-primary">Rizal</div>
                                    </div>
                                </div>

                                <div className="p-6 flex-1 flex flex-col justify-center items-center text-center space-y-4">
                                    <p className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Outstanding Balance</p>
                                    <div className="p-4 rounded-lg bg-destructive/5 border border-destructive/20 w-full">
                                        <span className="text-3xl font-black text-destructive tracking-tight">₱ 15,000.00</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Transaction Cart */}
                    <div className="lg:col-span-2 flex flex-col">
                        <Card className="flex-1 flex flex-col">
                            <CardHeader className="flex flex-row items-center justify-between bg-muted/30 border-b space-y-0 py-4">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <ShoppingCart className="size-5 text-primary" />
                                    Current Transaction
                                </CardTitle>
                                
                                <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                                    <DialogTrigger asChild>
                                        <Button size="sm" className="gap-1">
                                            <Plus className="size-4" />
                                            Add Item
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Add Item to Transaction</DialogTitle>
                                            <DialogDescription>
                                                Select an item type and enter the amount to add to the cart.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="grid gap-4 py-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="item-type">Item Type</Label>
                                                <Select>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select Item..." />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="tuition">Tuition Fee</SelectItem>
                                                        <SelectItem value="misc">Miscellaneous</SelectItem>
                                                        <SelectItem value="uniform">Uniform</SelectItem>
                                                        <SelectItem value="id">ID Lace</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="amount">Amount</Label>
                                                <div className="relative">
                                                    <span className="absolute left-3 top-2.5 text-sm text-muted-foreground">₱</span>
                                                    <Input id="amount" type="number" className="pl-7" placeholder="0.00" />
                                                </div>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setIsAddModalOpen(false)}>Cancel</Button>
                                            <Button onClick={() => setIsAddModalOpen(false)}>Add to Cart</Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </CardHeader>
                            
                            <CardContent className="p-0 flex-1 overflow-auto">
                                <Table>
                                    <TableHeader className="bg-muted/20 sticky top-0">
                                        <TableRow>
                                            <TableHead className="pl-6">Item Details</TableHead>
                                            <TableHead className="text-right">Amount</TableHead>
                                            <TableHead className="text-right pr-6">Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell className="pl-6">
                                                <div className="font-bold">Tuition Fee</div>
                                                <div className="text-xs text-muted-foreground italic">Partial Payment</div>
                                            </TableCell>
                                            <TableCell className="text-right font-mono">₱ 5,000.00</TableCell>
                                            <TableCell className="text-right pr-6">
                                                <Button variant="ghost" size="icon" className="text-destructive">
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>

                            <div className="p-6 bg-muted/30 border-t space-y-4">
                                <div className="flex justify-between items-end">
                                    <p className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Total Amount Due</p>
                                    <p className="text-3xl font-black tracking-tighter">₱ 5,000.00</p>
                                </div>
                                <Button className="w-full h-12 text-lg bg-green-600 hover:bg-green-700">
                                    Process Payment
                                </Button>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
