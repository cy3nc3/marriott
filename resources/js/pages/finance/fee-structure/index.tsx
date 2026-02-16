import { Head } from '@inertiajs/react';
import { Plus, CalendarDays, RefreshCcw, Info } from 'lucide-react';
import { useState } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
} from '@/components/ui/dialog';
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
import { Input } from '@/components/ui/input';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fee Structure',
        href: '/finance/fee-structure',
    },
];

export default function FeeStructure() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    const GradeCard = ({ grade, total }: { grade: string; total: string }) => (
        <Card className="flex h-full flex-col shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/10">
                <div className="space-y-1">
                    <CardTitle className="text-lg">Grade {grade}</CardTitle>
                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Base Assessment</p>
                </div>
                <Button
                    size="xs"
                    className="gap-1 h-7"
                    onClick={() => setIsAddModalOpen(true)}
                >
                    <Plus className="size-3" />
                    Add Item
                </Button>
            </CardHeader>
            <CardContent className="flex-1 overflow-auto p-0">
                <Table>
                    <TableHeader className="bg-muted/5">
                        <TableRow>
                            <TableHead className="pl-6">Description</TableHead>
                            <TableHead className="text-center">Type</TableHead>
                            <TableHead className="text-right pr-6">Amount</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow className="group">
                            <TableCell className="pl-6 font-medium">
                                Tuition Fee
                            </TableCell>
                            <TableCell className="text-center">
                                <Badge
                                    variant="outline"
                                    className="border-blue-200 bg-blue-50 text-blue-700 text-[10px]"
                                >
                                    Tuition
                                </Badge>
                            </TableCell>
                            <TableCell className="text-right pr-6 font-mono font-bold">
                                20,000.00
                            </TableCell>
                        </TableRow>
                        <TableRow className="group">
                            <TableCell className="pl-6 font-medium">
                                Miscellaneous Fee
                            </TableCell>
                            <TableCell className="text-center">
                                <Badge variant="secondary" className="text-[10px]">Misc</Badge>
                            </TableCell>
                            <TableCell className="text-right pr-6 font-mono font-bold">
                                5,000.00
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
            <div className="flex items-center justify-between border-t p-4 px-6 bg-muted/5">
                <span className="text-[10px] font-black text-muted-foreground uppercase tracking-tighter">
                    Annual Total:
                </span>
                <span className="font-mono text-xl font-black text-primary">₱ {total}</span>
            </div>
        </Card>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fee Structure" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <CalendarDays className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">Fee Configuration</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        <Select defaultValue="2025-2026">
                            <SelectTrigger className="w-[180px] h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="2025-2026">SY 2025-2026</SelectItem>
                                <SelectItem value="2024-2025">SY 2024-2025</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" size="sm" className="gap-2 border-primary/20">
                            <RefreshCcw className="size-3.5 text-primary" />
                            Sync from Previous
                        </Button>
                    </div>
                </div>

                <Alert className="bg-primary/5 border-primary/10">
                    <Info className="size-4 text-primary" />
                    <AlertDescription className="text-xs font-medium text-primary/80">
                        Fees are automatically carried over from the previous school year. Changes here only affect the selected academic cycle.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <GradeCard grade="7" total="25,000.00" />
                    <GradeCard grade="8" total="22,000.00" />
                    <GradeCard grade="9" total="22,000.00" />
                    <GradeCard grade="10" total="23,000.00" />
                </div>

                <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add New Fee</DialogTitle>
                            <DialogDescription>
                                Enter the fee details for the selected grade
                                level.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Fee Description
                                </Label>
                                <Input
                                    id="description"
                                    placeholder="e.g. Energy Fee"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount (₱)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select defaultValue="Miscellaneous">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Type..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Miscellaneous">
                                            Miscellaneous
                                        </SelectItem>
                                        <SelectItem value="Tuition">
                                            Tuition
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
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
                                Add Fee
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
