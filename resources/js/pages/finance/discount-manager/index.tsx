import { Head } from '@inertiajs/react';
import { Plus, UserPlus, Info, Tag, Search, CreditCard } from 'lucide-react';
import { useState } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
        title: 'Discount Manager',
        href: '/finance/discount-manager',
    },
];

export default function DiscountManager() {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isTagModalOpen, setIsTagModalOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Discount Manager" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="mb-2 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex items-center gap-2">
                        <Tag className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">
                            Discounts & Scholarships
                        </h1>
                    </div>
                </div>

                <Alert className="border-blue-200 bg-blue-50">
                    <Info className="size-4 text-blue-700" />
                    <AlertDescription className="text-xs font-medium text-blue-800">
                        Tagging a student with a discount will automatically
                        create a <strong>Credit entry</strong> in their
                        financial ledger, reducing their outstanding balance.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Section 1: Discount Types */}
                    <Card className="h-fit shadow-sm lg:col-span-1">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/30">
                            <CardTitle className="text-lg font-bold">
                                Discount Types
                            </CardTitle>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                onClick={() => setIsCreateModalOpen(true)}
                            >
                                <Plus className="size-5" />
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4 p-4">
                            {[
                                {
                                    name: 'Academic Scholarship',
                                    type: 'Percentage',
                                    val: '100%',
                                },
                                {
                                    name: 'Sibling Discount',
                                    type: 'Percentage',
                                    val: '10%',
                                },
                                {
                                    name: 'Early Bird',
                                    type: 'Fixed',
                                    val: '₱ 1,000.00',
                                },
                            ].map((d, i) => (
                                <div
                                    key={i}
                                    className="group flex items-center justify-between rounded-xl border p-4 transition-all hover:border-primary/30 hover:bg-primary/[0.02]"
                                >
                                    <div>
                                        <h4 className="text-sm font-bold">
                                            {d.name}
                                        </h4>
                                        <span className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                                            {d.type}
                                        </span>
                                    </div>
                                    <div className="font-black tracking-tight text-green-600">
                                        {d.val}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Section 2: Scholar List */}
                    <Card className="shadow-md lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/30 px-6 py-4">
                            <CardTitle className="text-lg font-bold">
                                Scholar Registry
                            </CardTitle>
                            <Button
                                size="sm"
                                className="gap-2 font-bold"
                                onClick={() => setIsTagModalOpen(true)}
                            >
                                <UserPlus className="size-4" />
                                Tag Student
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader className="bg-muted/20">
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Student Name
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Grade
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Active Discount
                                        </TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Ledger Credit
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow className="transition-colors hover:bg-muted/10">
                                        <TableCell className="pl-6 font-bold text-primary">
                                            Juan Dela Cruz
                                        </TableCell>
                                        <TableCell className="text-center font-medium">
                                            Grade 7
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <div className="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[10px] font-black text-green-700 uppercase">
                                                Academic Scholarship
                                            </div>
                                        </TableCell>
                                        <TableCell className="pr-6 text-right font-mono font-black text-green-600">
                                            - 20,000.00
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* Create Discount Modal */}
                <Dialog
                    open={isCreateModalOpen}
                    onOpenChange={setIsCreateModalOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-2xl font-black">
                                New Discount Type
                            </DialogTitle>
                            <DialogDescription>
                                Define a new scholarship or discount program.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="discount-name">
                                    Program Name
                                </Label>
                                <Input
                                    id="discount-name"
                                    placeholder="eg. Faculty Dependent"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="discount-type">
                                        Calculation
                                    </Label>
                                    <Select defaultValue="Percentage">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Percentage">
                                                Percentage (%)
                                            </SelectItem>
                                            <SelectItem value="Fixed">
                                                Fixed Amount (₱)
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="discount-value">
                                        Value
                                    </Label>
                                    <Input
                                        id="discount-value"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button onClick={() => setIsCreateModalOpen(false)}>
                                Create Program
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Tag Student Modal */}
                <Dialog open={isTagModalOpen} onOpenChange={setIsTagModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-2xl font-black">
                                Tag Student
                            </DialogTitle>
                            <DialogDescription>
                                Search student by LRN to apply a scholarship.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="student-search">
                                    Search Student (LRN or Name)
                                </Label>
                                <div className="relative">
                                    <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                    <Input
                                        id="student-search"
                                        placeholder="123456789..."
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="select-discount">
                                    Select Active Discount
                                </Label>
                                <Select>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose program..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">
                                            Academic Scholarship (100%)
                                        </SelectItem>
                                        <SelectItem value="2">
                                            Sibling Discount (10%)
                                        </SelectItem>
                                        <SelectItem value="3">
                                            Early Bird (₱ 1,000.00)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="flex items-start gap-3 rounded-lg border border-green-100 bg-green-50 p-4">
                            <CreditCard className="mt-0.5 size-5 text-green-600" />
                            <p className="text-xs font-medium text-green-800">
                                This action will apply the credit immediately to
                                the student's ledger.
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsTagModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button onClick={() => setIsTagModalOpen(false)}>
                                Apply Discount
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
