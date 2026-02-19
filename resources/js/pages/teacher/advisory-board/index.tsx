import { Head } from '@inertiajs/react';
import { Info, Lock, Save } from 'lucide-react';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Advisory Board',
        href: '/teacher/advisory-board',
    },
];

export default function AdvisoryBoard() {
    const [isFinalizeDialogOpen, setIsFinalizeDialogOpen] = useState(false);
    const valueCriteria = [
        'Maka-Diyos',
        'Makatao',
        'Makakalikasan',
        'Makabansa',
    ];
    const students = [
        {
            name: 'Dela Cruz, Juan',
            ratings: {
                maka_diyos: 'AO',
                makatao: 'AO',
                makakalikasan: 'SO',
                makabansa: 'AO',
            },
            remarks: 'Consistent participation in class activities.',
        },
        {
            name: 'Santos, Maria',
            ratings: {
                maka_diyos: 'AO',
                makatao: 'AO',
                makakalikasan: 'AO',
                makabansa: 'AO',
            },
            remarks: 'Positive leadership and peer support.',
        },
        {
            name: 'Reyes, Carlo',
            ratings: {
                maka_diyos: 'SO',
                makatao: 'SO',
                makakalikasan: 'AO',
                makabansa: 'SO',
            },
            remarks: 'Needs closer monitoring on classroom behavior.',
        },
    ];
    const advisoryGrades = [
        {
            studentName: 'Dela Cruz, Juan',
            math: 85,
            science: 82,
            english: 88,
            filipino: 86,
            average: '85.25',
        },
        {
            studentName: 'Santos, Maria',
            math: 92,
            science: 90,
            english: 91,
            filipino: 93,
            average: '91.50',
        },
        {
            studentName: 'Reyes, Carlo',
            math: 78,
            science: 80,
            english: 79,
            filipino: 81,
            average: '79.50',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Advisory Board" />
            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Advisory Context</CardTitle>
                            <Badge variant="outline">Status: Draft</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Select defaultValue="section-rizal">
                                    <SelectTrigger className="w-full sm:w-52">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="section-rizal">
                                            Grade 7 - Rizal
                                        </SelectItem>
                                        <SelectItem value="section-bonifacio">
                                            Grade 7 - Bonifacio
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select defaultValue="first-quarter">
                                    <SelectTrigger className="w-full sm:w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="first-quarter">
                                            1st Quarter
                                        </SelectItem>
                                        <SelectItem value="second-quarter">
                                            2nd Quarter
                                        </SelectItem>
                                        <SelectItem value="third-quarter">
                                            3rd Quarter
                                        </SelectItem>
                                        <SelectItem value="fourth-quarter">
                                            4th Quarter
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button variant="outline">
                                    <Save className="size-4" />
                                    Save Draft
                                </Button>
                                <Button
                                    variant="destructive"
                                    onClick={() =>
                                        setIsFinalizeDialogOpen(true)
                                    }
                                >
                                    <Lock className="size-4" />
                                    Finalize and Lock
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="grades" className="w-full">
                    <TabsList>
                        <TabsTrigger value="grades">Grades</TabsTrigger>
                        <TabsTrigger value="conduct">Conduct</TabsTrigger>
                    </TabsList>

                    <TabsContent value="grades">
                        <Card>
                            <CardHeader className="border-b">
                                <div className="flex items-center justify-between gap-3">
                                    <CardTitle>Advisory Class Grades</CardTitle>
                                    <Badge variant="secondary">Read-only</Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Student
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Math
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Science
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                English
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Filipino
                                            </TableHead>
                                            <TableHead className="border-l pr-6 text-right">
                                                General Average
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {advisoryGrades.map((gradeRow) => (
                                            <TableRow
                                                key={gradeRow.studentName}
                                            >
                                                <TableCell className="pl-6 font-medium">
                                                    {gradeRow.studentName}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {gradeRow.math}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {gradeRow.science}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {gradeRow.english}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {gradeRow.filipino}
                                                </TableCell>
                                                <TableCell className="border-l pr-6 text-right font-medium">
                                                    {gradeRow.average}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card>
                            <CardHeader className="border-b">
                                <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                    <CardTitle>Conduct and Values</CardTitle>
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Info className="size-4" />
                                        <p>
                                            Legend: AO (Always), SO (Sometimes),
                                            RO (Rarely), NO (Not Observed)
                                        </p>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Student
                                            </TableHead>
                                            {valueCriteria.map((criterion) => (
                                                <TableHead
                                                    key={criterion}
                                                    className="border-l text-center"
                                                >
                                                    {criterion}
                                                </TableHead>
                                            ))}
                                            <TableHead className="border-l pr-6">
                                                Adviser Remarks
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {students.map((student) => (
                                            <TableRow key={student.name}>
                                                <TableCell className="pl-6 font-medium">
                                                    {student.name}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    <BehaviorSelect
                                                        defaultValue={
                                                            student.ratings
                                                                .maka_diyos
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    <BehaviorSelect
                                                        defaultValue={
                                                            student.ratings
                                                                .makatao
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    <BehaviorSelect
                                                        defaultValue={
                                                            student.ratings
                                                                .makakalikasan
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    <BehaviorSelect
                                                        defaultValue={
                                                            student.ratings
                                                                .makabansa
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="border-l pr-6">
                                                    <Input
                                                        defaultValue={
                                                            student.remarks
                                                        }
                                                        className="min-w-64"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                            <div className="flex items-center justify-between border-t px-4 py-3">
                                <p className="text-sm text-muted-foreground">
                                    {students.length} students
                                </p>
                                <Button
                                    variant="destructive"
                                    onClick={() =>
                                        setIsFinalizeDialogOpen(true)
                                    }
                                >
                                    <Lock className="size-4" />
                                    Finalize and Lock Quarter
                                </Button>
                            </div>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            <Dialog
                open={isFinalizeDialogOpen}
                onOpenChange={setIsFinalizeDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Finalize Conduct and Values</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        This will lock all conduct ratings and adviser remarks
                        for the selected quarter.
                    </p>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsFinalizeDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setIsFinalizeDialogOpen(false)}
                        >
                            Confirm Lock
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function BehaviorSelect({ defaultValue }: { defaultValue: string }) {
    return (
        <Select defaultValue={defaultValue}>
            <SelectTrigger className="mx-auto h-8 w-20">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="AO">AO</SelectItem>
                <SelectItem value="SO">SO</SelectItem>
                <SelectItem value="RO">RO</SelectItem>
                <SelectItem value="NO">NO</SelectItem>
            </SelectContent>
        </Select>
    );
}
