import { Head } from '@inertiajs/react';
import { Plus, Settings2, CheckCircle2, Info } from 'lucide-react';
import { useState } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
        title: 'Grading Sheet',
        href: '/teacher/grading-sheet',
    },
];

export default function GradingSheet() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [isRubricModalOpen, setIsRubricModalOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grading Sheet" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="space-y-1">
                            <Label className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                                Section & Subject
                            </Label>
                            <div className="flex items-center gap-2">
                                <Select defaultValue="rizal">
                                    <SelectTrigger className="h-9 w-40 border-primary/20 font-bold">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="rizal">
                                            7 - Rizal
                                        </SelectItem>
                                        <SelectItem value="bonifacio">
                                            7 - Bonifacio
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select defaultValue="math">
                                    <SelectTrigger className="h-9 w-40 border-primary/20 font-bold">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="math">
                                            Mathematics 7
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="space-y-1">
                            <Label className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                                Quarter
                            </Label>
                            <Select defaultValue="1st">
                                <SelectTrigger className="h-9 w-24 border-primary/20 font-bold">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1st">1st Qtr</SelectItem>
                                    <SelectItem value="2nd">2nd Qtr</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="gap-2 border-primary/20"
                            onClick={() => setIsRubricModalOpen(true)}
                        >
                            <Settings2 className="size-4 text-primary" />
                            Configure Rubric
                        </Button>
                        <Button
                            size="sm"
                            className="gap-2 bg-green-600 hover:bg-green-700"
                        >
                            <CheckCircle2 className="size-4" />
                            Finalize Grades
                        </Button>
                    </div>
                </div>

                <Card className="overflow-hidden shadow-md">
                    <div className="flex items-center justify-between border-b bg-muted/30 p-4">
                        <div className="flex gap-4">
                            <div className="flex items-center gap-2">
                                <div className="size-2 rounded-full bg-blue-500" />
                                <span className="text-[10px] font-black tracking-tighter text-muted-foreground uppercase">
                                    Written: 40%
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="size-2 rounded-full bg-purple-500" />
                                <span className="text-[10px] font-black tracking-tighter text-muted-foreground uppercase">
                                    Performance: 40%
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="size-2 rounded-full bg-orange-500" />
                                <span className="text-[10px] font-black tracking-tighter text-muted-foreground uppercase">
                                    Exam: 20%
                                </span>
                            </div>
                        </div>
                        <Button
                            size="xs"
                            variant="secondary"
                            className="h-7 gap-1"
                            onClick={() => setIsAddModalOpen(true)}
                        >
                            <Plus className="size-3" />
                            Add Activity
                        </Button>
                    </div>

                    <CardContent className="overflow-auto p-0">
                        <Table className="border-collapse">
                            <TableHeader className="bg-muted/10">
                                <TableRow>
                                    <TableHead
                                        rowSpan={2}
                                        className="sticky left-0 z-30 min-w-64 border-r bg-muted/5 pl-6 text-[10px] font-black uppercase"
                                    >
                                        Student Name
                                    </TableHead>
                                    <TableHead
                                        colSpan={2}
                                        className="border-r bg-blue-500/5 text-center text-[10px] font-black text-blue-700 uppercase"
                                    >
                                        Written Works
                                    </TableHead>
                                    <TableHead
                                        colSpan={2}
                                        className="border-r bg-purple-500/5 text-center text-[10px] font-black text-purple-700 uppercase"
                                    >
                                        Performance Tasks
                                    </TableHead>
                                    <TableHead className="border-r bg-orange-500/5 text-center text-[10px] font-black text-orange-700 uppercase">
                                        Assessment
                                    </TableHead>
                                    <TableHead
                                        rowSpan={2}
                                        className="sticky right-0 z-30 min-w-28 border-l bg-primary/5 text-center text-[10px] font-black text-primary uppercase"
                                    >
                                        Final Grade
                                    </TableHead>
                                </TableRow>
                                <TableRow className="bg-muted/5">
                                    <TableHead className="min-w-24 border-r py-2 text-center">
                                        <div className="flex flex-col leading-tight">
                                            <span className="text-[11px] font-bold">
                                                Quiz 1
                                            </span>
                                            <span className="text-[9px] text-muted-foreground">
                                                Max: 20
                                            </span>
                                        </div>
                                    </TableHead>
                                    <TableHead className="min-w-24 border-r py-2 text-center">
                                        <div className="flex flex-col leading-tight">
                                            <span className="text-[11px] font-bold">
                                                Quiz 2
                                            </span>
                                            <span className="text-[9px] text-muted-foreground">
                                                Max: 30
                                            </span>
                                        </div>
                                    </TableHead>
                                    <TableHead className="min-w-24 border-r py-2 text-center">
                                        <div className="flex flex-col leading-tight">
                                            <span className="text-[11px] font-bold">
                                                Project 1
                                            </span>
                                            <span className="text-[9px] text-muted-foreground">
                                                Max: 50
                                            </span>
                                        </div>
                                    </TableHead>
                                    <TableHead className="min-w-24 border-r py-2 text-center">
                                        <div className="flex flex-col leading-tight">
                                            <span className="text-[11px] font-bold">
                                                Recitation
                                            </span>
                                            <span className="text-[9px] text-muted-foreground">
                                                Max: 20
                                            </span>
                                        </div>
                                    </TableHead>
                                    <TableHead className="min-w-24 border-r py-2 text-center">
                                        <div className="flex flex-col leading-tight">
                                            <span className="text-[11px] font-bold">
                                                Exam
                                            </span>
                                            <span className="text-[9px] text-muted-foreground">
                                                Max: 100
                                            </span>
                                        </div>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="transition-colors hover:bg-muted/30">
                                    <TableCell className="sticky left-0 z-20 border-r bg-background pl-6 font-bold">
                                        Dela Cruz, Juan
                                    </TableCell>
                                    <TableCell className="border-r text-center">
                                        <Input
                                            type="number"
                                            className="mx-auto h-8 w-14 text-center font-bold"
                                            defaultValue="18"
                                        />
                                    </TableCell>
                                    <TableCell className="border-r text-center">
                                        <Input
                                            type="number"
                                            className="mx-auto h-8 w-14 text-center font-bold"
                                            defaultValue="25"
                                        />
                                    </TableCell>
                                    <TableCell className="border-r text-center">
                                        <Input
                                            type="number"
                                            className="mx-auto h-8 w-14 text-center font-bold"
                                            defaultValue="45"
                                        />
                                    </TableCell>
                                    <TableCell className="border-r text-center">
                                        <Input
                                            type="number"
                                            className="mx-auto h-8 w-14 text-center font-bold"
                                            defaultValue="18"
                                        />
                                    </TableCell>
                                    <TableCell className="border-r text-center">
                                        <Input
                                            type="number"
                                            className="mx-auto h-8 w-14 text-center font-bold"
                                            defaultValue="85"
                                        />
                                    </TableCell>
                                    <TableCell className="sticky right-0 z-20 border-l bg-primary/[0.02] text-center text-lg font-black text-primary">
                                        86.6
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Modals */}
                <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-2xl font-black">
                                Add New Activity
                            </DialogTitle>
                            <DialogDescription>
                                Create a new graded entry for this class.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Activity Title</Label>
                                <Input
                                    id="title"
                                    placeholder="e.g. Unit Quiz 1"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Component</Label>
                                    <Select defaultValue="ww">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="ww">
                                                Written Work
                                            </SelectItem>
                                            <SelectItem value="pt">
                                                Performance Task
                                            </SelectItem>
                                            <SelectItem value="qa">
                                                Quarterly Exam
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Max Points</Label>
                                    <Input type="number" defaultValue="20" />
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
                                Create Entry
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={isRubricModalOpen}
                    onOpenChange={setIsRubricModalOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-2xl font-black">
                                Configure Rubric
                            </DialogTitle>
                            <DialogDescription>
                                Adjust component weights for Mathematics 7.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-6 py-4">
                            <Alert className="border-primary/10 bg-primary/5">
                                <Info className="size-4 text-primary" />
                                <AlertDescription className="text-xs">
                                    Total must equal 100%.
                                </AlertDescription>
                            </Alert>
                            <div className="grid grid-cols-3 gap-4 text-center">
                                <div className="space-y-2">
                                    <Label className="text-[10px] font-black text-blue-700 uppercase">
                                        Written
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            type="number"
                                            defaultValue="40"
                                            className="pr-6 text-center font-bold"
                                        />
                                        <span className="absolute top-2 right-2 text-xs text-muted-foreground">
                                            %
                                        </span>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label className="text-[10px] font-black text-purple-700 uppercase">
                                        Performance
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            type="number"
                                            defaultValue="40"
                                            className="pr-6 text-center font-bold"
                                        />
                                        <span className="absolute top-2 right-2 text-xs text-muted-foreground">
                                            %
                                        </span>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label className="text-[10px] font-black text-orange-700 uppercase">
                                        Exam
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            type="number"
                                            defaultValue="20"
                                            className="pr-6 text-center font-bold"
                                        />
                                        <span className="absolute top-2 right-2 text-xs text-muted-foreground">
                                            %
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsRubricModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button onClick={() => setIsRubricModalOpen(false)}>
                                Apply Weights
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
