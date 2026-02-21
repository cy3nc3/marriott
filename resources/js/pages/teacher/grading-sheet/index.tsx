import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Plus, Settings2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
import teacher from '@/routes/teacher';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grading Sheet',
        href: '/teacher/grading-sheet',
    },
];

type SectionOption = {
    id: number;
    label: string;
};

type SubjectOption = {
    id: number;
    name: string;
};

type Context = {
    section_options: SectionOption[];
    subject_options: SubjectOption[];
    selected_section_id: number | null;
    selected_subject_id: number | null;
    selected_assignment_id: number | null;
    selected_quarter: '1' | '2' | '3' | '4';
    has_assignment: boolean;
};

type RubricWeights = {
    ww_weight: number;
    pt_weight: number;
    qa_weight: number;
};

type Assessment = {
    id: number;
    title: string;
    max_points: number;
};

type AssessmentGroup = {
    component: string;
    weight: number;
    assessments: Assessment[];
};

type StudentRow = {
    id: number;
    enrollment_id: number;
    name: string;
    scores: Record<string, number | null>;
    computed_grade: string;
};

interface Props {
    context: Context;
    rubric_weights: RubricWeights;
    grouped_assessments: AssessmentGroup[];
    quarterly_exam_assessment: Assessment | null;
    students: StudentRow[];
    status: 'draft' | 'submitted';
}

const quarterLabels: Record<string, string> = {
    '1': '1st Quarter',
    '2': '2nd Quarter',
    '3': '3rd Quarter',
    '4': '4th Quarter',
};

const asNumber = (value: string): number => {
    const parsedValue = Number(value);

    if (Number.isNaN(parsedValue)) {
        return 0;
    }

    return parsedValue;
};

export default function GradingSheet({
    context,
    rubric_weights,
    grouped_assessments,
    quarterly_exam_assessment,
    students,
    status,
}: Props) {
    const gradingSheetRoute = teacher.grading_sheet;

    const [isAssessmentModalOpen, setIsAssessmentModalOpen] = useState(false);
    const [isRubricModalOpen, setIsRubricModalOpen] = useState(false);

    const [assessmentTitle, setAssessmentTitle] = useState('');
    const [assessmentComponent, setAssessmentComponent] = useState<
        'WW' | 'PT' | 'QA'
    >('WW');
    const [assessmentMaxPoints, setAssessmentMaxPoints] = useState('10');

    const [rubricForm, setRubricForm] = useState({
        ww_weight: String(rubric_weights.ww_weight),
        pt_weight: String(rubric_weights.pt_weight),
        qa_weight: String(rubric_weights.qa_weight),
    });

    useEffect(() => {
        setRubricForm({
            ww_weight: String(rubric_weights.ww_weight),
            pt_weight: String(rubric_weights.pt_weight),
            qa_weight: String(rubric_weights.qa_weight),
        });
    }, [rubric_weights]);

    const visibleAssessmentGroups = grouped_assessments.filter(
        (group) => group.assessments.length > 0,
    );

    const allAssessments = useMemo(() => {
        const grouped = visibleAssessmentGroups.flatMap(
            (group) => group.assessments,
        );

        if (quarterly_exam_assessment) {
            return [...grouped, quarterly_exam_assessment];
        }

        return grouped;
    }, [visibleAssessmentGroups, quarterly_exam_assessment]);

    const initialScoreValues = useMemo(() => {
        const values: Record<string, string> = {};

        students.forEach((student) => {
            allAssessments.forEach((assessment) => {
                const scoreValue = student.scores[String(assessment.id)];
                values[`${student.id}-${assessment.id}`] =
                    scoreValue === null || scoreValue === undefined
                        ? ''
                        : String(scoreValue);
            });
        });

        return values;
    }, [students, allAssessments]);

    const [scoreValues, setScoreValues] = useState<Record<string, string>>(
        initialScoreValues,
    );

    useEffect(() => {
        setScoreValues(initialScoreValues);
    }, [initialScoreValues]);

    const selectedSectionValue = context.selected_section_id
        ? String(context.selected_section_id)
        : 'section-none';

    const selectedSubjectValue = context.selected_subject_id
        ? String(context.selected_subject_id)
        : 'subject-none';

    const goToFilters = (
        sectionId: number | null,
        subjectId: number | null,
        quarter: string,
    ) => {
        router.get(
            gradingSheetRoute.url({
                query: {
                    section_id: sectionId || undefined,
                    subject_id: subjectId || undefined,
                    quarter,
                },
            }),
            {},
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleSectionChange = (value: string) => {
        if (value === 'section-none') {
            return;
        }

        goToFilters(Number(value), null, context.selected_quarter);
    };

    const handleSubjectChange = (value: string) => {
        if (value === 'subject-none') {
            return;
        }

        goToFilters(
            context.selected_section_id,
            Number(value),
            context.selected_quarter,
        );
    };

    const handleQuarterChange = (value: string) => {
        goToFilters(
            context.selected_section_id,
            context.selected_subject_id,
            value,
        );
    };

    const handleScoreChange = (
        studentId: number,
        assessmentId: number,
        value: string,
    ) => {
        if (value !== '' && !/^\d*\.?\d{0,2}$/.test(value)) {
            return;
        }

        setScoreValues((previousValues) => ({
            ...previousValues,
            [`${studentId}-${assessmentId}`]: value,
        }));
    };

    const submitRubric = () => {
        if (!context.selected_subject_id) {
            return;
        }

        router.post(
            gradingSheetRoute.update_rubric.url(),
            {
                subject_id: context.selected_subject_id,
                ww_weight: asNumber(rubricForm.ww_weight),
                pt_weight: asNumber(rubricForm.pt_weight),
                qa_weight: asNumber(rubricForm.qa_weight),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsRubricModalOpen(false);
                },
            },
        );
    };

    const submitAssessment = () => {
        if (!context.selected_assignment_id) {
            return;
        }

        const normalizedTitle = assessmentTitle.trim();
        if (normalizedTitle === '') {
            return;
        }

        router.post(
            gradingSheetRoute.store_assessment.url(),
            {
                subject_assignment_id: context.selected_assignment_id,
                quarter: context.selected_quarter,
                type: assessmentComponent,
                title: normalizedTitle,
                max_score: asNumber(assessmentMaxPoints),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsAssessmentModalOpen(false);
                    setAssessmentTitle('');
                    setAssessmentComponent('WW');
                    setAssessmentMaxPoints('10');
                },
            },
        );
    };

    const submitScores = (saveMode: 'draft' | 'submitted') => {
        if (!context.selected_assignment_id) {
            return;
        }

        if (students.length === 0 || allAssessments.length === 0) {
            return;
        }

        const scoresPayload = students.flatMap((student) => {
            return allAssessments.map((assessment) => {
                const rowKey = `${student.id}-${assessment.id}`;
                const scoreValue = scoreValues[rowKey];

                return {
                    student_id: student.id,
                    graded_activity_id: assessment.id,
                    score: scoreValue === '' ? null : asNumber(scoreValue),
                };
            });
        });

        router.post(
            gradingSheetRoute.store_scores.url(),
            {
                subject_assignment_id: context.selected_assignment_id,
                quarter: context.selected_quarter,
                save_mode: saveMode,
                scores: scoresPayload,
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grading Sheet" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Class and Subject</CardTitle>
                            <Badge
                                variant={
                                    status === 'submitted' ? 'secondary' : 'outline'
                                }
                            >
                                Status:{' '}
                                {status === 'submitted' ? 'Submitted' : 'Draft'}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Select
                                    value={selectedSectionValue}
                                    onValueChange={handleSectionChange}
                                >
                                    <SelectTrigger className="w-full sm:w-56">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {context.section_options.length > 0 ? (
                                            context.section_options.map(
                                                (sectionOption) => (
                                                    <SelectItem
                                                        key={sectionOption.id}
                                                        value={String(
                                                            sectionOption.id,
                                                        )}
                                                    >
                                                        {sectionOption.label}
                                                    </SelectItem>
                                                ),
                                            )
                                        ) : (
                                            <SelectItem value="section-none">
                                                No sections
                                            </SelectItem>
                                        )}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={selectedSubjectValue}
                                    onValueChange={handleSubjectChange}
                                >
                                    <SelectTrigger className="w-full sm:w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {context.subject_options.length > 0 ? (
                                            context.subject_options.map(
                                                (subjectOption) => (
                                                    <SelectItem
                                                        key={subjectOption.id}
                                                        value={String(
                                                            subjectOption.id,
                                                        )}
                                                    >
                                                        {subjectOption.name}
                                                    </SelectItem>
                                                ),
                                            )
                                        ) : (
                                            <SelectItem value="subject-none">
                                                No subjects
                                            </SelectItem>
                                        )}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={context.selected_quarter}
                                    onValueChange={handleQuarterChange}
                                >
                                    <SelectTrigger className="w-full sm:w-36">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">
                                            1st Quarter
                                        </SelectItem>
                                        <SelectItem value="2">
                                            2nd Quarter
                                        </SelectItem>
                                        <SelectItem value="3">
                                            3rd Quarter
                                        </SelectItem>
                                        <SelectItem value="4">
                                            4th Quarter
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button
                                    variant="outline"
                                    onClick={() => setIsRubricModalOpen(true)}
                                    disabled={!context.selected_subject_id}
                                >
                                    <Settings2 className="size-4" />
                                    Configure Rubric
                                </Button>
                                <Button
                                    onClick={() => submitScores('submitted')}
                                    disabled={
                                        !context.has_assignment ||
                                        students.length === 0 ||
                                        allAssessments.length === 0
                                    }
                                >
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
                                disabled={!context.has_assignment}
                            >
                                <Plus className="size-4" />
                                Add Assessment
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {!context.has_assignment ? (
                            <p className="px-4 py-5 text-sm text-muted-foreground">
                                Select section and subject to load grading data.
                            </p>
                        ) : allAssessments.length === 0 ? (
                            <p className="px-4 py-5 text-sm text-muted-foreground">
                                No assessments for{' '}
                                {quarterLabels[context.selected_quarter]} yet.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6"></TableHead>
                                        {visibleAssessmentGroups.map((group) => (
                                            <TableHead
                                                key={group.component}
                                                colSpan={
                                                    group.assessments.length
                                                }
                                                className="border-l text-center"
                                            >
                                                {group.component} ({group.weight}
                                                %)
                                            </TableHead>
                                        ))}
                                        {quarterly_exam_assessment ? (
                                            <TableHead className="border-l text-center"></TableHead>
                                        ) : null}
                                        <TableHead className="border-l pr-6"></TableHead>
                                    </TableRow>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Student
                                        </TableHead>
                                        {visibleAssessmentGroups.flatMap(
                                            (group) =>
                                                group.assessments.map(
                                                    (assessment) => (
                                                        <TableHead
                                                            key={assessment.id}
                                                            className="border-l text-center"
                                                        >
                                                            {assessment.title} ({' '}
                                                            {
                                                                assessment.max_points
                                                            }
                                                            )
                                                        </TableHead>
                                                    ),
                                                ),
                                        )}
                                        {quarterly_exam_assessment ? (
                                            <TableHead className="border-l text-center">
                                                {quarterly_exam_assessment.title}{' '}
                                                (
                                                {
                                                    quarterly_exam_assessment.max_points
                                                }
                                                )
                                            </TableHead>
                                        ) : null}
                                        <TableHead className="border-l pr-6 text-right">
                                            Computed Grade
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.map((student) => (
                                        <TableRow key={student.id}>
                                            <TableCell className="pl-6">
                                                {student.name}
                                            </TableCell>
                                            {visibleAssessmentGroups.flatMap(
                                                (group) =>
                                                    group.assessments.map(
                                                        (assessment) => {
                                                            const scoreKey = `${student.id}-${assessment.id}`;

                                                            return (
                                                                <TableCell
                                                                    key={
                                                                        scoreKey
                                                                    }
                                                                    className="border-l text-center"
                                                                >
                                                                    <Input
                                                                        type="text"
                                                                        value={
                                                                            scoreValues[
                                                                                scoreKey
                                                                            ] ??
                                                                            ''
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            handleScoreChange(
                                                                                student.id,
                                                                                assessment.id,
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                        className="mx-auto w-20"
                                                                    />
                                                                </TableCell>
                                                            );
                                                        },
                                                    ),
                                            )}
                                            {quarterly_exam_assessment ? (
                                                <TableCell className="border-l text-center">
                                                    <Input
                                                        type="text"
                                                        value={
                                                            scoreValues[
                                                                `${student.id}-${quarterly_exam_assessment.id}`
                                                            ] ?? ''
                                                        }
                                                        onChange={(event) =>
                                                            handleScoreChange(
                                                                student.id,
                                                                quarterly_exam_assessment.id,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="mx-auto w-20"
                                                    />
                                                </TableCell>
                                            ) : null}
                                            <TableCell className="border-l pr-6 text-right font-medium">
                                                {student.computed_grade}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    <div className="flex items-center justify-between border-t px-4 py-3">
                        <p className="text-sm text-muted-foreground">
                            {students.length} students
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                onClick={() => submitScores('draft')}
                                disabled={
                                    !context.has_assignment ||
                                    students.length === 0 ||
                                    allAssessments.length === 0
                                }
                            >
                                Save Draft
                            </Button>
                            <Button
                                onClick={() => submitScores('submitted')}
                                disabled={
                                    !context.has_assignment ||
                                    students.length === 0 ||
                                    allAssessments.length === 0
                                }
                            >
                                Submit Quarter Grades
                            </Button>
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
                                    value={assessmentTitle}
                                    onChange={(event) =>
                                        setAssessmentTitle(event.target.value)
                                    }
                                    placeholder="e.g. Unit Quiz 2"
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Component</Label>
                                    <Select
                                        value={assessmentComponent}
                                        onValueChange={(value) =>
                                            setAssessmentComponent(
                                                value as 'WW' | 'PT' | 'QA',
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="WW">
                                                Written Works
                                            </SelectItem>
                                            <SelectItem value="PT">
                                                Performance Tasks
                                            </SelectItem>
                                            <SelectItem value="QA">
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
                                        type="text"
                                        value={assessmentMaxPoints}
                                        onChange={(event) =>
                                            setAssessmentMaxPoints(
                                                event.target.value,
                                            )
                                        }
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
                            <Button onClick={submitAssessment}>Add</Button>
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
                                        type="text"
                                        value={rubricForm.ww_weight}
                                        onChange={(event) =>
                                            setRubricForm((previousForm) => ({
                                                ...previousForm,
                                                ww_weight: event.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="rubric-performance">
                                        Performance
                                    </Label>
                                    <Input
                                        id="rubric-performance"
                                        type="text"
                                        value={rubricForm.pt_weight}
                                        onChange={(event) =>
                                            setRubricForm((previousForm) => ({
                                                ...previousForm,
                                                pt_weight: event.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="rubric-exam">Exam</Label>
                                    <Input
                                        id="rubric-exam"
                                        type="text"
                                        value={rubricForm.qa_weight}
                                        onChange={(event) =>
                                            setRubricForm((previousForm) => ({
                                                ...previousForm,
                                                qa_weight: event.target.value,
                                            }))
                                        }
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
                            <Button onClick={submitRubric}>Apply</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
