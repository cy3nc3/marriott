import { Head } from '@inertiajs/react';
import { CheckCircle2, Plus, Settings2 } from 'lucide-react';
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
        title: 'Grading Sheet',
        href: '/teacher/grading-sheet',
    },
];

export default function GradingSheet() {
    const [isAssessmentModalOpen, setIsAssessmentModalOpen] = useState(false);
    const [isRubricModalOpen, setIsRubricModalOpen] = useState(false);
    const rubricWeights = [
        { label: 'Written Works', value: 40 },
        { label: 'Performance Tasks', value: 40 },
        { label: 'Quarterly Exam', value: 20 },
    ];
    const groupedAssessments = [
        {
            component: 'Written Works',
            weight: 40,
            assessments: [
                { id: 'ww1', title: 'Quiz 1', maxPoints: 20 },
                { id: 'ww2', title: 'Seatwork 1', maxPoints: 15 },
                { id: 'ww3', title: 'Assignment 1', maxPoints: 25 },
            ],
        },
        {
            component: 'Performance Tasks',
            weight: 40,
            assessments: [
                { id: 'pt1', title: 'Project 1', maxPoints: 50 },
                { id: 'pt2', title: 'Lab Activity 1', maxPoints: 40 },
            ],
        },
    ];
    const quarterlyExamAssessment = {
        id: 'exam',
        title: 'Quarterly Exam',
        maxPoints: 100,
    };
    const students = [
        {
            name: 'Dela Cruz, Juan',
            scores: { ww1: 18, ww2: 13, ww3: 22, pt1: 45, pt2: 36, exam: 85 },
            computedGrade: '86.60',
        },
        {
            name: 'Santos, Maria',
            scores: { ww1: 19, ww2: 14, ww3: 24, pt1: 48, pt2: 38, exam: 91 },
            computedGrade: '91.10',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grading Sheet" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Class and Subject</CardTitle>
                            <Badge variant="outline">Status: Draft</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Select defaultValue="rizal">
                                    <SelectTrigger className="w-full sm:w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="rizal">
                                            Grade 7 - Rizal
                                        </SelectItem>
                                        <SelectItem value="bonifacio">
                                            Grade 7 - Bonifacio
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select defaultValue="math">
                                    <SelectTrigger className="w-full sm:w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="math">
                                            Mathematics 7
                                        </SelectItem>
                                        <SelectItem value="science">
                                            Science 7
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select defaultValue="first">
                                    <SelectTrigger className="w-full sm:w-36">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="first">
                                            1st Quarter
                                        </SelectItem>
                                        <SelectItem value="second">
                                            2nd Quarter
                                        </SelectItem>
                                        <SelectItem value="third">
                                            3rd Quarter
                                        </SelectItem>
                                        <SelectItem value="fourth">
                                            4th Quarter
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button
                                    variant="outline"
                                    onClick={() => setIsRubricModalOpen(true)}
                                >
                                    <Settings2 className="size-4" />
                                    Configure Rubric
                                </Button>
                                <Button>
                                    <CheckCircle2 className="size-4" />
                                    Submit Final Grades
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Score Matrix</CardTitle>
                            <Button
                                size="sm"
                                onClick={() => setIsAssessmentModalOpen(true)}
                            >
                                <Plus className="size-4" />
                                Add Assessment
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6"></TableHead>
                                    {groupedAssessments.map((group) => (
                                        <TableHead
                                            key={group.component}
                                            colSpan={group.assessments.length}
                                            className="border-l text-center"
                                        >
                                            {group.component} ({group.weight}%)
                                        </TableHead>
                                    ))}
                                    <TableHead className="border-l text-center"></TableHead>
                                    <TableHead className="border-l pr-6"></TableHead>
                                </TableRow>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    {groupedAssessments.flatMap((group) =>
                                        group.assessments.map((assessment) => (
                                            <TableHead
                                                key={assessment.id}
                                                className="border-l text-center"
                                            >
                                                {assessment.title} (
                                                {assessment.maxPoints})
                                            </TableHead>
                                        )),
                                    )}
                                    <TableHead className="border-l text-center">
                                        {quarterlyExamAssessment.title} (
                                        {quarterlyExamAssessment.maxPoints})
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Computed Grade
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.map((student) => (
                                    <TableRow key={student.name}>
                                        <TableCell className="pl-6">
                                            {student.name}
                                        </TableCell>
                                        {groupedAssessments.flatMap((group) =>
                                            group.assessments.map(
                                                (assessment) => (
                                                    <TableCell
                                                        key={`${student.name}-${assessment.id}`}
                                                        className="border-l text-center"
                                                    >
                                                        <Input
                                                            type="number"
                                                            defaultValue={
                                                                student.scores[
                                                                    assessment.id as keyof typeof student.scores
                                                                ]
                                                            }
                                                            className="mx-auto w-20"
                                                        />
                                                    </TableCell>
                                                ),
                                            ),
                                        )}
                                        <TableCell className="border-l text-center">
                                            <Input
                                                type="number"
                                                defaultValue={
                                                    student.scores.exam
                                                }
                                                className="mx-auto w-20"
                                            />
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right font-medium">
                                            {student.computedGrade}
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
                        <div className="flex gap-2">
                            <Button variant="outline">Save Draft</Button>
                            <Button>Submit Quarter Grades</Button>
                        </div>
                    </div>
                </Card>

                <Dialog
                    open={isAssessmentModalOpen}
                    onOpenChange={setIsAssessmentModalOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add Assessment</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label htmlFor="assessment-title">
                                    Assessment Title
                                </Label>
                                <Input
                                    id="assessment-title"
                                    placeholder="e.g. Unit Quiz 2"
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Component</Label>
                                    <Select defaultValue="written">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="written">
                                                Written Works
                                            </SelectItem>
                                            <SelectItem value="performance">
                                                Performance Tasks
                                            </SelectItem>
                                            <SelectItem value="exam">
                                                Quarterly Exam
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="assessment-max">
                                        Max Points
                                    </Label>
                                    <Input
                                        id="assessment-max"
                                        type="number"
                                        defaultValue="10"
                                    />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsAssessmentModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={() => setIsAssessmentModalOpen(false)}
                            >
                                Add
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
                            <DialogTitle>Configure Rubric</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="rubric-written">
                                        Written
                                    </Label>
                                    <Input
                                        id="rubric-written"
                                        type="number"
                                        defaultValue={rubricWeights[0].value}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="rubric-performance">
                                        Performance
                                    </Label>
                                    <Input
                                        id="rubric-performance"
                                        type="number"
                                        defaultValue={rubricWeights[1].value}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="rubric-exam">Exam</Label>
                                    <Input
                                        id="rubric-exam"
                                        type="number"
                                        defaultValue={rubricWeights[2].value}
                                    />
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
                                Apply
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
