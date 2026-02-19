import { Head } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
        title: 'Discount Manager',
        href: '/finance/discount-manager',
    },
];

export default function DiscountManager() {
    const [isCreateProgramOpen, setIsCreateProgramOpen] = useState(false);
    const [isTagStudentOpen, setIsTagStudentOpen] = useState(false);

    const discountPrograms = [
        {
            name: 'Academic Scholarship',
            calculation: 'Percentage',
            value: '100%',
            appliesTo: 'Tuition',
            stackable: 'No',
        },
        {
            name: 'Sibling Discount',
            calculation: 'Percentage',
            value: '10%',
            appliesTo: 'Total Fees',
            stackable: 'Yes',
        },
        {
            name: 'Early Payment Grant',
            calculation: 'Fixed Amount',
            value: 'PHP 1,000.00',
            appliesTo: 'Total Fees',
            stackable: 'No',
        },
    ];

    const taggedStudents = [
        {
            student: 'Juan Dela Cruz',
            lrn: '123456789012',
            program: 'Academic Scholarship',
            creditApplied: 'PHP 20,000.00',
            effectiveDate: '02/20/2026',
            status: 'Active',
        },
        {
            student: 'Maria Santos',
            lrn: '987654321098',
            program: 'Sibling Discount',
            creditApplied: 'PHP 2,500.00',
            effectiveDate: '02/18/2026',
            status: 'Active',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Discount Manager" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Discount Programs</CardTitle>
                            <Button
                                onClick={() => setIsCreateProgramOpen(true)}
                            >
                                <Plus className="size-4" />
                                Add Program
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Program
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Calculation
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Value
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Applies To
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Stackable
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {discountPrograms.map((program) => (
                                    <TableRow key={program.name}>
                                        <TableCell className="pl-6 font-medium">
                                            {program.name}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {program.calculation}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {program.value}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {program.appliesTo}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {program.stackable}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Student Discount Registry</CardTitle>
                            <Button
                                variant="outline"
                                onClick={() => setIsTagStudentOpen(true)}
                            >
                                <UserPlus className="size-4" />
                                Tag Student
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead className="border-l">
                                        Program
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Credit Applied
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Effective Date
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {taggedStudents.map((row) => (
                                    <TableRow key={row.lrn}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.student}
                                        </TableCell>
                                        <TableCell>{row.lrn}</TableCell>
                                        <TableCell className="border-l">
                                            {row.program}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.creditApplied}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.effectiveDate}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            <Badge variant="secondary">
                                                {row.status}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Dialog
                    open={isCreateProgramOpen}
                    onOpenChange={setIsCreateProgramOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create Discount Program</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Program Name</Label>
                                <Input placeholder="e.g. Faculty Dependent" />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Calculation</Label>
                                    <Select defaultValue="percentage">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="percentage">
                                                Percentage
                                            </SelectItem>
                                            <SelectItem value="fixed">
                                                Fixed Amount
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Value</Label>
                                    <Input type="number" placeholder="0" />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Applies To</Label>
                                    <Select defaultValue="tuition">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="tuition">
                                                Tuition
                                            </SelectItem>
                                            <SelectItem value="total-fees">
                                                Total Fees
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Stackable</Label>
                                    <Select defaultValue="no">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="yes">
                                                Yes
                                            </SelectItem>
                                            <SelectItem value="no">
                                                No
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsCreateProgramOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={() => setIsCreateProgramOpen(false)}
                            >
                                Save Program
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={isTagStudentOpen}
                    onOpenChange={setIsTagStudentOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tag Student Discount</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Student (LRN or Name)</Label>
                                <Input placeholder="Search student" />
                            </div>
                            <div className="space-y-2">
                                <Label>Select Program</Label>
                                <Select defaultValue="academic-scholarship">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="academic-scholarship">
                                            Academic Scholarship
                                        </SelectItem>
                                        <SelectItem value="sibling-discount">
                                            Sibling Discount
                                        </SelectItem>
                                        <SelectItem value="early-payment-grant">
                                            Early Payment Grant
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Effective Date</Label>
                                <Input type="date" defaultValue="2026-02-20" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsTagStudentOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button onClick={() => setIsTagStudentOpen(false)}>
                                Apply Discount
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
