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
import { Plus, Edit2, Trash2 } from 'lucide-react';
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
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fee Structure',
        href: '/finance/fee-structure',
    },
];

export default function FeeStructure() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    const GradeCard = ({ grade, total }: { grade: string, total: string }) => (
        <Card className="flex flex-col h-full">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/30">
                <CardTitle className="text-lg">Grade {grade} Fees</CardTitle>
                <Button size="xs" className="gap-1" onClick={() => setIsAddModalOpen(true)}>
                    <Plus className="size-3" />
                    Add Fee
                </Button>
            </CardHeader>
            <CardContent className="p-0 flex-1 overflow-auto">
                <Table>
                    <TableHeader className="bg-muted/20">
                        <TableRow>
                            <TableHead className="pl-6">Description</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead className="text-right">Amount</TableHead>
                            <TableHead className="text-right pr-6">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow>
                            <TableCell className="pl-6 font-medium">Tuition Fee</TableCell>
                            <TableCell>
                                <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">Tuition</Badge>
                            </TableCell>
                            <TableCell className="text-right font-mono font-bold">20,000.00</TableCell>
                            <TableCell className="text-right pr-6">
                                <div className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" className="size-8">
                                        <Edit2 className="size-3.5" />
                                    </Button>
                                    <Button variant="ghost" size="icon" className="size-8 text-destructive">
                                        <Trash2 className="size-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell className="pl-6 font-medium">Miscellaneous Fee</TableCell>
                            <TableCell>
                                <Badge variant="secondary">Misc</Badge>
                            </TableCell>
                            <TableCell className="text-right font-mono font-bold">5,000.00</TableCell>
                            <TableCell className="text-right pr-6">
                                <div className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" className="size-8">
                                        <Edit2 className="size-3.5" />
                                    </Button>
                                    <Button variant="ghost" size="icon" className="size-8 text-destructive">
                                        <Trash2 className="size-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
            <div className="p-4 bg-muted/30 border-t flex justify-between items-center px-6">
                <span className="text-xs font-bold uppercase text-muted-foreground">Total Assessment:</span>
                <span className="text-lg font-black tracking-tight">₱ {total}</span>
            </div>
        </Card>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fee Structure" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                Enter the fee details for the selected grade level.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="description">Fee Description</Label>
                                <Input id="description" placeholder="e.g. Energy Fee" />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount (₱)</Label>
                                <Input id="amount" type="number" step="0.01" placeholder="0.00" />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select defaultValue="Miscellaneous">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Type..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Miscellaneous">Miscellaneous</SelectItem>
                                        <SelectItem value="Tuition">Tuition</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsAddModalOpen(false)}>Cancel</Button>
                            <Button onClick={() => setIsAddModalOpen(false)}>Add Fee</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
