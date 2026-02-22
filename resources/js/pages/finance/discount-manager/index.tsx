import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserPlus } from 'lucide-react';
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
import {
    destroy,
    store,
    tag_student,
    untag_student,
    update,
} from '@/routes/finance/discount_manager';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Discount Manager',
        href: '/finance/discount-manager',
    },
];

type DiscountType = 'percentage' | 'fixed';

type DiscountProgramRow = {
    id: number;
    name: string;
    type: DiscountType;
    calculation: string;
    value: number;
    value_label: string;
};

type StudentDiscountRow = {
    id: number;
    student_name: string;
    lrn: string;
    program: string;
    school_year: string;
    tagged_on: string;
};

type StudentOption = {
    id: number;
    lrn: string;
    name: string;
};

interface Props {
    discount_programs: DiscountProgramRow[];
    tagged_students: StudentDiscountRow[];
    students: StudentOption[];
    active_academic_year: {
        id: number;
        name: string;
    } | null;
}

const discountTypeOptions: { value: DiscountType; label: string }[] = [
    { value: 'percentage', label: 'Percentage' },
    { value: 'fixed', label: 'Fixed Amount' },
];

const formatDate = (value: string) => {
    return new Date(value).toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
    });
};

export default function DiscountManager({
    discount_programs,
    tagged_students,
    students,
    active_academic_year,
}: Props) {
    const [isCreateProgramOpen, setIsCreateProgramOpen] = useState(false);
    const [isTagStudentOpen, setIsTagStudentOpen] = useState(false);
    const [editingProgram, setEditingProgram] =
        useState<DiscountProgramRow | null>(null);

    const createProgramForm = useForm({
        name: '',
        type: 'percentage' as DiscountType,
        value: '',
    });

    const editProgramForm = useForm({
        name: '',
        type: 'percentage' as DiscountType,
        value: '',
    });

    const tagStudentForm = useForm({
        student_id: '',
        discount_id: '',
    });

    const openCreateProgramDialog = () => {
        createProgramForm.setData({
            name: '',
            type: 'percentage',
            value: '',
        });
        createProgramForm.clearErrors();
        setIsCreateProgramOpen(true);
    };

    const submitCreateProgram = () => {
        createProgramForm.submit(store(), {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateProgramOpen(false);
                createProgramForm.reset();
                createProgramForm.setData('type', 'percentage');
            },
        });
    };

    const openEditProgramDialog = (program: DiscountProgramRow) => {
        setEditingProgram(program);
        editProgramForm.setData({
            name: program.name,
            type: program.type,
            value: String(program.value),
        });
        editProgramForm.clearErrors();
    };

    const submitEditProgram = () => {
        if (!editingProgram) {
            return;
        }

        editProgramForm.submit(update({ discount: editingProgram.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setEditingProgram(null);
                editProgramForm.reset();
            },
        });
    };

    const removeProgram = (program: DiscountProgramRow) => {
        if (!confirm(`Remove "${program.name}" discount program?`)) {
            return;
        }

        router.delete(destroy({ discount: program.id }).url, {
            preserveScroll: true,
        });
    };

    const openTagStudentDialog = () => {
        tagStudentForm.setData({
            student_id: '',
            discount_id: '',
        });
        tagStudentForm.clearErrors();
        setIsTagStudentOpen(true);
    };

    const submitTagStudent = () => {
        tagStudentForm.submit(tag_student(), {
            preserveScroll: true,
            onSuccess: () => {
                setIsTagStudentOpen(false);
                tagStudentForm.reset();
            },
        });
    };

    const removeTaggedStudent = (row: StudentDiscountRow) => {
        if (
            !confirm(
                `Remove ${row.student_name} from ${row.program} discount registry?`,
            )
        ) {
            return;
        }

        router.delete(untag_student({ studentDiscount: row.id }).url, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Discount Manager" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Discount Programs</CardTitle>
                            <Button onClick={openCreateProgramDialog}>
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
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {discount_programs.map((program) => (
                                    <TableRow key={program.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {program.name}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {program.calculation}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {program.value_label}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        openEditProgramDialog(
                                                            program,
                                                        )
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        removeProgram(program)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {discount_programs.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No discount programs yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="gap-1 border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <CardTitle>Student Discount Registry</CardTitle>
                                {active_academic_year && (
                                    <p className="text-sm text-muted-foreground">
                                        Active School Year:{' '}
                                        {active_academic_year.name}
                                    </p>
                                )}
                            </div>
                            <Button
                                variant="outline"
                                onClick={openTagStudentDialog}
                                disabled={
                                    students.length === 0 ||
                                    discount_programs.length === 0
                                }
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
                                        School Year
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Tagged On
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tagged_students.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.student_name}
                                        </TableCell>
                                        <TableCell>{row.lrn}</TableCell>
                                        <TableCell className="border-l">
                                            {row.program}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {row.school_year}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            {formatDate(row.tagged_on)}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                onClick={() =>
                                                    removeTaggedStudent(row)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {tagged_students.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No students tagged yet.
                                        </TableCell>
                                    </TableRow>
                                )}
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
                                <Input
                                    placeholder="e.g. Academic Scholarship"
                                    value={createProgramForm.data.name}
                                    onChange={(event) =>
                                        createProgramForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                                {createProgramForm.errors.name && (
                                    <p className="text-sm text-destructive">
                                        {createProgramForm.errors.name}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Calculation</Label>
                                    <Select
                                        value={createProgramForm.data.type}
                                        onValueChange={(value: DiscountType) =>
                                            createProgramForm.setData(
                                                'type',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {discountTypeOptions.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {createProgramForm.errors.type && (
                                        <p className="text-sm text-destructive">
                                            {createProgramForm.errors.type}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>
                                        {createProgramForm.data.type ===
                                        'percentage'
                                            ? 'Value (%)'
                                            : 'Value (PHP)'}
                                    </Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={createProgramForm.data.value}
                                        onChange={(event) =>
                                            createProgramForm.setData(
                                                'value',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {createProgramForm.errors.value && (
                                        <p className="text-sm text-destructive">
                                            {createProgramForm.errors.value}
                                        </p>
                                    )}
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
                                onClick={submitCreateProgram}
                                disabled={createProgramForm.processing}
                            >
                                Save Program
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={editingProgram !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setEditingProgram(null);
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Discount Program</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label>Program Name</Label>
                                <Input
                                    value={editProgramForm.data.name}
                                    onChange={(event) =>
                                        editProgramForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                                {editProgramForm.errors.name && (
                                    <p className="text-sm text-destructive">
                                        {editProgramForm.errors.name}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Calculation</Label>
                                    <Select
                                        value={editProgramForm.data.type}
                                        onValueChange={(value: DiscountType) =>
                                            editProgramForm.setData(
                                                'type',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {discountTypeOptions.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {editProgramForm.errors.type && (
                                        <p className="text-sm text-destructive">
                                            {editProgramForm.errors.type}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>
                                        {editProgramForm.data.type ===
                                        'percentage'
                                            ? 'Value (%)'
                                            : 'Value (PHP)'}
                                    </Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={editProgramForm.data.value}
                                        onChange={(event) =>
                                            editProgramForm.setData(
                                                'value',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {editProgramForm.errors.value && (
                                        <p className="text-sm text-destructive">
                                            {editProgramForm.errors.value}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setEditingProgram(null)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitEditProgram}
                                disabled={editProgramForm.processing}
                            >
                                Save Changes
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
                            {active_academic_year && (
                                <p className="text-sm text-muted-foreground">
                                    Active School Year:{' '}
                                    {active_academic_year.name}
                                </p>
                            )}
                            <div className="space-y-2">
                                <Label>Student</Label>
                                <Select
                                    value={tagStudentForm.data.student_id}
                                    onValueChange={(value) =>
                                        tagStudentForm.setData(
                                            'student_id',
                                            value,
                                        )
                                    }
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
                                {tagStudentForm.errors.student_id && (
                                    <p className="text-sm text-destructive">
                                        {tagStudentForm.errors.student_id}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label>Select Program</Label>
                                <Select
                                    value={tagStudentForm.data.discount_id}
                                    onValueChange={(value) =>
                                        tagStudentForm.setData(
                                            'discount_id',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select program" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {discount_programs.map((program) => (
                                            <SelectItem
                                                key={program.id}
                                                value={String(program.id)}
                                            >
                                                {program.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {tagStudentForm.errors.discount_id && (
                                    <p className="text-sm text-destructive">
                                        {tagStudentForm.errors.discount_id}
                                    </p>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsTagStudentOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitTagStudent}
                                disabled={
                                    tagStudentForm.processing ||
                                    !active_academic_year
                                }
                            >
                                Apply Discount
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
