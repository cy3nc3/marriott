import { useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Search, Plus, Trash2, ShoppingCart, CreditCard, Package } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

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
                <div className="grid h-full grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Left Column: Student Context */}
                    <div className="flex flex-col gap-4 lg:col-span-1">
                        {/* Search Bar */}
                        <Card className="border-primary/10 shadow-sm">
                            <CardContent className="pt-6">
                                <div className="relative">
                                    <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="Search LRN or Student Name..."
                                        className="pl-10 h-10 border-primary/20 focus-visible:ring-primary"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Student Card */}
                        <Card className="flex flex-1 flex-col border-primary/10 shadow-sm overflow-hidden">
                            <CardContent className="flex flex-1 flex-col p-0">
                                <div className="flex flex-col items-center space-y-4 bg-primary/5 p-8 border-b border-primary/10">
                                    <Avatar
                                        className="size-24 border-4 border-background shadow-md"
                                    >
                                        <AvatarImage src="" />
                                        <AvatarFallback className="text-2xl font-black bg-primary text-primary-foreground">JD</AvatarFallback>
                                    </Avatar>
                                    <div className="text-center space-y-1">
                                        <h2 className="text-2xl font-black tracking-tight text-primary">
                                            Juan Dela Cruz
                                        </h2>
                                        <p className="text-sm font-mono font-bold text-muted-foreground">
                                            1234567890123
                                        </p>
                                    </div>
                                    <div className="rounded-full bg-background border border-primary/20 px-4 py-1 text-[10px] font-black uppercase tracking-widest text-primary">
                                        Grade 7 - Rizal
                                    </div>
                                </div>

                                <div className="flex flex-1 flex-col items-center justify-center space-y-4 p-8 text-center bg-white">
                                    <p className="text-[10px] font-black tracking-[0.2em] text-muted-foreground uppercase">
                                        Outstanding Balance
                                    </p>
                                    <div className="w-full rounded-2xl border-2 border-destructive/10 bg-destructive/[0.02] p-6">
                                        <span className="text-4xl font-black tracking-tighter text-destructive">
                                            ₱ 15,000.00
                                        </span>
                                    </div>
                                    <p className="text-[10px] italic text-muted-foreground">Calculated from all active ledger entries</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Transaction Cart */}
                    <div className="flex flex-col lg:col-span-2">
                        <Card className="flex flex-1 flex-col shadow-md border-primary/10">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b py-4 px-6 bg-muted/30">
                                <CardTitle className="flex items-center gap-2 text-lg font-bold">
                                    <ShoppingCart className="size-5 text-primary" />
                                    Transaction Items
                                </CardTitle>

                                <Dialog
                                    open={isAddModalOpen}
                                    onOpenChange={setIsAddModalOpen}
                                >
                                    <DialogTrigger asChild>
                                        <Button size="sm" className="gap-2 font-bold shadow-sm">
                                            <Plus className="size-4" />
                                            Add Item
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent className="sm:max-w-[500px]">
                                        <DialogHeader>
                                            <DialogTitle className="text-2xl font-black">Add Transaction Item</DialogTitle>
                                            <DialogDescription>
                                                Select a fee or inventory item to process for this student.
                                            </DialogDescription>
                                        </DialogHeader>
                                        
                                        <Tabs defaultValue="fees" className="py-4">
                                            <TabsList className="grid w-full grid-cols-2">
                                                <TabsTrigger value="fees" className="gap-2">
                                                    <CreditCard className="size-4" />
                                                    School Fees
                                                </TabsTrigger>
                                                <TabsTrigger value="inventory" className="gap-2">
                                                    <Package className="size-4" />
                                                    Inventory
                                                </TabsTrigger>
                                            </TabsList>
                                            <TabsContent value="fees" className="pt-4 space-y-4">
                                                <div className="grid gap-2">
                                                    <Label>Select Outstanding Fee</Label>
                                                    <Select>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Choose fee..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="tuition">Tuition Fee (Bal: ₱10,000)</SelectItem>
                                                            <SelectItem value="misc">Misc Fee (Bal: ₱5,000)</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Payment Amount</Label>
                                                    <div className="relative">
                                                        <span className="absolute left-3 top-2.5 font-bold text-muted-foreground">₱</span>
                                                        <Input placeholder="0.00" className="pl-7 font-mono font-bold" type="number" />
                                                    </div>
                                                </div>
                                            </TabsContent>
                                            <TabsContent value="inventory" className="pt-4 space-y-4">
                                                <div className="grid gap-2">
                                                    <Label>Product Name</Label>
                                                    <Select>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Search inventory..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="uniform">School Uniform - Small (₱450)</SelectItem>
                                                            <SelectItem value="id">ID Lace (₱50)</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Quantity</Label>
                                                    <Input type="number" defaultValue="1" />
                                                </div>
                                            </TabsContent>
                                        </Tabs>

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
                                                Add to Cart
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </CardHeader>

                            <CardContent className="flex-1 overflow-auto p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6">Item Details</TableHead>
                                            <TableHead className="text-center">Category</TableHead>
                                            <TableHead className="text-right">Amount</TableHead>
                                            <TableHead className="pr-6 text-right">Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="hover:bg-muted/10 transition-colors">
                                            <TableCell className="pl-6">
                                                <div className="font-bold text-primary">Tuition Fee</div>
                                                <div className="text-[10px] text-muted-foreground font-medium uppercase">Partial Payment</div>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <div className="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-black bg-indigo-50 text-indigo-700 border-indigo-200">FEES</div>
                                            </TableCell>
                                            <TableCell className="text-right font-mono font-bold">
                                                ₱ 5,000.00
                                            </TableCell>
                                            <TableCell className="pr-6 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-destructive hover:bg-destructive/10"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>

                            <div className="space-y-4 border-t p-8 bg-muted/5">
                                <div className="flex items-end justify-between">
                                    <div>
                                        <p className="text-[10px] font-black tracking-[0.2em] text-muted-foreground uppercase">Total Payable</p>
                                        <p className="text-4xl font-black tracking-tighter text-primary">
                                            ₱ 5,000.00
                                        </p>
                                    </div>
                                    <div className="text-right space-y-2">
                                        <div className="flex items-center gap-2 justify-end mb-2">
                                            <Label className="text-[10px] font-bold text-muted-foreground">PAYMENT MODE:</Label>
                                            <Select defaultValue="cash">
                                                <SelectTrigger className="w-[120px] h-8 text-xs font-bold border-primary/20">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="cash">Cash</SelectItem>
                                                    <SelectItem value="gcash">GCash</SelectItem>
                                                    <SelectItem value="bank">Bank Transfer</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <Button className="h-14 w-64 bg-green-600 text-lg font-black shadow-lg hover:bg-green-700 transition-all active:scale-[0.98]">
                                            COMPLETE PAYMENT
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
