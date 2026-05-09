import { Head, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
        title: 'Historical Records',
        href: '/teacher/historical-records',
    },
];

type Option = {
    id: number;
    name: string;
};

type SubjectOption = {
    subject_assignment_id: number;
    subject_name: string;
};

type StudentRow = {
    enrollment_id: number;
    student_name: string;
    lrn: string;
};

type GradeRow = {
    quarter: string;
    grade: number;
    is_locked: boolean;
};

type AssessmentRow = {
    quarter: string;
    type: string;
    title: string;
    max_score: number;
    score: number;
};

type AttendanceRow = {
    date: string;
    status: string;
};

type RecordsByEnrollment = Record<
    string,
    {
        grade_summaries: GradeRow[];
        assessments: AssessmentRow[];
        attendance: AttendanceRow[];
    }
>;

interface Props {
    context: {
        academic_year_options: Array<{ id: number; name: string; status: string }>;
        selected_academic_year_id: number | null;
        grade_level_options: Option[];
        selected_grade_level_id: number | null;
        section_options: Option[];
        selected_section_id: number | null;
        subject_options: SubjectOption[];
        selected_subject_assignment_id: number | null;
    };
    students: StudentRow[];
    records_by_enrollment: RecordsByEnrollment;
}

const quarterLabel = (value: string): string => {
    if (value === 'final') {
        return 'Final';
    }

    return `${value}nd Quarter`
        .replace('1nd', '1st')
        .replace('2nd', '2nd')
        .replace('3nd', '3rd')
        .replace('4nd', '4th');
};

const statusLabel = (value: string): string => {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const dateParts = (date: string): { year: number; month: number; day: number } => {
    const [year, month, day] = date.split('-').map(Number);

    return { year, month, day };
};

const monthLabel = (date: string): string => {
    const { year, month } = dateParts(date);

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, month - 1, 1));
};

const dayLabel = (date: string): string => {
    return String(dateParts(date).day);
};

const weekdayLabel = (date: string): string => {
    const { year, month, day } = dateParts(date);

    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
    }).format(new Date(year, month - 1, day));
};

const assessmentTypeLabels: Record<string, string> = {
    WW: 'Written Works',
    PT: 'Performance Tasks',
    QA: 'Quarterly Assessment',
};

const assessmentTypeOrder: Record<string, number> = {
    WW: 1,
    PT: 2,
    QA: 3,
};

const quarters = ['1', '2', '3', '4'];

function ReadOnlySf2MarkCell({ status }: { status: string }) {
    const normalizedStatus = status as
        | 'present'
        | 'absent'
        | 'tardy_late_comer'
        | 'tardy_cutting_classes';
    const shadeColor = 'rgba(0, 0, 0, 1)';
    const borderColor = 'currentColor';

    return (
        <div className="mx-auto flex size-8 items-center justify-center border bg-background text-foreground">
            <svg
                viewBox="0 0 100 100"
                className="size-full"
                shapeRendering="crispEdges"
                aria-hidden="true"
            >
                {normalizedStatus === 'tardy_late_comer' ? (
                    <polygon points="0,0 100,0 0,100" fill={shadeColor} />
                ) : null}
                {normalizedStatus === 'tardy_cutting_classes' ? (
                    <polygon points="100,0 0,100 100,100" fill={shadeColor} />
                ) : null}
                <line
                    x1="2"
                    y1="98"
                    x2="98"
                    y2="2"
                    stroke={borderColor}
                    strokeWidth="4"
                />
                {normalizedStatus === 'absent' ? (
                    <line
                        x1="2"
                        y1="2"
                        x2="98"
                        y2="98"
                        stroke={borderColor}
                        strokeWidth="4"
                    />
                ) : null}
            </svg>
            <span className="sr-only">{statusLabel(status)}</span>
        </div>
    );
}

export default function TeacherHistoricalRecords({
    context,
    students,
    records_by_enrollment,
}: Props) {
    const [selectedEnrollmentId, setSelectedEnrollmentId] = useState<number | null>(
        null,
    );

    const selectedStudent = useMemo(
        () =>
            students.find((student) => student.enrollment_id === selectedEnrollmentId) ??
            null,
        [selectedEnrollmentId, students],
    );

    const selectedRecords = useMemo(() => {
        if (!selectedEnrollmentId) {
            return null;
        }

        return records_by_enrollment[String(selectedEnrollmentId)] ?? null;
    }, [records_by_enrollment, selectedEnrollmentId]);

    const selectedAcademicYearLabel = useMemo(
        () =>
            context.academic_year_options.find(
                (option) => option.id === context.selected_academic_year_id,
            )?.name ?? 'School year not selected',
        [context.academic_year_options, context.selected_academic_year_id],
    );

    const selectedSectionLabel = useMemo(() => {
        const gradeLevelName =
            context.grade_level_options.find(
                (option) => option.id === context.selected_grade_level_id,
            )?.name ?? null;
        const sectionName =
            context.section_options.find(
                (option) => option.id === context.selected_section_id,
            )?.name ?? null;

        if (gradeLevelName && sectionName) {
            return `${gradeLevelName} - ${sectionName}`;
        }

        return sectionName ?? gradeLevelName ?? 'Section not selected';
    }, [
        context.grade_level_options,
        context.section_options,
        context.selected_grade_level_id,
        context.selected_section_id,
    ]);

    const selectedSubjectLabel = useMemo(
        () =>
            context.subject_options.find(
                (option) =>
                    option.subject_assignment_id ===
                    context.selected_subject_assignment_id,
            )?.subject_name ?? 'Subject not selected',
        [context.subject_options, context.selected_subject_assignment_id],
    );

    const assessmentGroups = useMemo(() => {
        const assessments = selectedRecords?.assessments ?? [];

        return Object.entries(assessmentTypeLabels)
            .map(([type, label]) => {
                const columns = assessments
                    .filter((assessment) => assessment.type === type)
                    .reduce<AssessmentRow[]>((uniqueAssessments, assessment) => {
                        const exists = uniqueAssessments.some(
                            (item) => item.title === assessment.title,
                        );

                        return exists
                            ? uniqueAssessments
                            : [...uniqueAssessments, assessment];
                    }, [])
                    .sort((left, right) => left.title.localeCompare(right.title));

                return {
                    type,
                    label,
                    columns,
                };
            })
            .filter((group) => group.columns.length > 0)
            .sort(
                (left, right) =>
                    assessmentTypeOrder[left.type] - assessmentTypeOrder[right.type],
            );
    }, [selectedRecords]);

    const assessmentScoreMap = useMemo(() => {
        const map = new Map<string, AssessmentRow>();

        (selectedRecords?.assessments ?? []).forEach((assessment) => {
            map.set(
                `${assessment.quarter}|${assessment.type}|${assessment.title}`,
                assessment,
            );
        });

        return map;
    }, [selectedRecords]);

    const gradeSummaryMap = useMemo(() => {
        const map = new Map<string, GradeRow>();

        (selectedRecords?.grade_summaries ?? []).forEach((grade) => {
            map.set(grade.quarter, grade);
        });

        return map;
    }, [selectedRecords]);

    const attendanceMonthGroups = useMemo(() => {
        const attendanceRows = selectedRecords?.attendance ?? [];

        return attendanceRows.reduce<
            Array<{ key: string; label: string; rows: AttendanceRow[] }>
        >((groups, attendance) => {
            const { year, month } = dateParts(attendance.date);
            const key = `${year}-${String(month).padStart(2, '0')}`;
            const currentGroup = groups.at(-1);

            if (currentGroup?.key === key) {
                currentGroup.rows.push(attendance);

                return groups;
            }

            groups.push({
                key,
                label: monthLabel(attendance.date),
                rows: [attendance],
            });

            return groups;
        }, []);
    }, [selectedRecords]);

    const updateFilters = (next: {
        academic_year_id?: number;
        grade_level_id?: number;
        section_id?: number;
        subject_assignment_id?: number;
    }) => {
        const has = <K extends keyof typeof next>(key: K): boolean =>
            Object.prototype.hasOwnProperty.call(next, key);

        router.get(
            '/teacher/historical-records',
            {
                academic_year_id:
                    has('academic_year_id')
                        ? next.academic_year_id
                        : (context.selected_academic_year_id ?? undefined),
                grade_level_id:
                    has('grade_level_id')
                        ? next.grade_level_id
                        : (context.selected_grade_level_id ?? undefined),
                section_id: has('section_id')
                    ? next.section_id
                    : (context.selected_section_id ?? undefined),
                subject_assignment_id:
                    has('subject_assignment_id')
                        ? next.subject_assignment_id
                        : (context.selected_subject_assignment_id ?? undefined),
            },
            { preserveScroll: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Historical Records" />

            <div className="space-y-6 p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Historical Records</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div className="space-y-2">
                            <div className="text-sm font-medium">School Year</div>
                            <Select
                                value={
                                    context.selected_academic_year_id
                                        ? String(context.selected_academic_year_id)
                                        : undefined
                                }
                                onValueChange={(value) =>
                                    updateFilters({
                                        academic_year_id: Number(value),
                                        grade_level_id: undefined,
                                        section_id: undefined,
                                        subject_assignment_id: undefined,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select school year" />
                                </SelectTrigger>
                                <SelectContent>
                                    {context.academic_year_options.map((option) => (
                                        <SelectItem key={option.id} value={String(option.id)}>
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <div className="text-sm font-medium">Grade Level</div>
                            <Select
                                value={
                                    context.selected_grade_level_id
                                        ? String(context.selected_grade_level_id)
                                        : undefined
                                }
                                onValueChange={(value) =>
                                    updateFilters({
                                        grade_level_id: Number(value),
                                        section_id: undefined,
                                        subject_assignment_id: undefined,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select grade level" />
                                </SelectTrigger>
                                <SelectContent>
                                    {context.grade_level_options.map((option) => (
                                        <SelectItem key={option.id} value={String(option.id)}>
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <div className="text-sm font-medium">Section</div>
                            <Select
                                value={
                                    context.selected_section_id
                                        ? String(context.selected_section_id)
                                        : undefined
                                }
                                onValueChange={(value) =>
                                    updateFilters({
                                        section_id: Number(value),
                                        subject_assignment_id: undefined,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select section" />
                                </SelectTrigger>
                                <SelectContent>
                                    {context.section_options.map((option) => (
                                        <SelectItem key={option.id} value={String(option.id)}>
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <div className="text-sm font-medium">Subject</div>
                            <Select
                                value={
                                    context.selected_subject_assignment_id
                                        ? String(context.selected_subject_assignment_id)
                                        : undefined
                                }
                                onValueChange={(value) =>
                                    updateFilters({
                                        subject_assignment_id: Number(value),
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select subject" />
                                </SelectTrigger>
                                <SelectContent>
                                    {context.subject_options.map((option) => (
                                        <SelectItem
                                            key={option.subject_assignment_id}
                                            value={String(option.subject_assignment_id)}
                                        >
                                            {option.subject_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Students</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>Student</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="text-center text-muted-foreground">
                                            No students found for the selected filters.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    students.map((student) => (
                                        <TableRow key={student.enrollment_id}>
                                            <TableCell>{student.lrn}</TableCell>
                                            <TableCell>{student.student_name}</TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-2"
                                                    onClick={() => setSelectedEnrollmentId(student.enrollment_id)}
                                                >
                                                    <Eye className="size-4" />
                                                    View Records
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={selectedEnrollmentId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedEnrollmentId(null);
                    }
                }}
            >
                <DialogContent className="flex max-h-[84vh] w-[90vw] max-w-[90vw] flex-col overflow-hidden sm:max-w-[90vw]">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedStudent ? `${selectedStudent.student_name} Records` : 'Records'}
                        </DialogTitle>
                        <div className="grid gap-2 pt-2 text-sm sm:grid-cols-3">
                            <div className="rounded-md border bg-muted/30 px-3 py-2">
                                <div className="text-xs font-medium text-muted-foreground">
                                    School Year
                                </div>
                                <div className="font-medium">{selectedAcademicYearLabel}</div>
                            </div>
                            <div className="rounded-md border bg-muted/30 px-3 py-2">
                                <div className="text-xs font-medium text-muted-foreground">
                                    Section
                                </div>
                                <div className="font-medium">{selectedSectionLabel}</div>
                            </div>
                            <div className="rounded-md border bg-muted/30 px-3 py-2">
                                <div className="text-xs font-medium text-muted-foreground">
                                    Subject
                                </div>
                                <div className="font-medium">{selectedSubjectLabel}</div>
                            </div>
                        </div>
                    </DialogHeader>

                    <Tabs defaultValue="grades" className="flex min-h-0 flex-1 flex-col">
                        <TabsList className="grid w-full shrink-0 grid-cols-2">
                            <TabsTrigger value="grades" className="w-full">
                                Grades
                            </TabsTrigger>
                            <TabsTrigger value="attendance" className="w-full">
                                Attendance
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent
                            value="grades"
                            className="mt-4"
                        >
                            {(selectedRecords?.assessments ?? []).length === 0 ? (
                                <div className="rounded-md border px-4 py-10 text-center text-sm text-muted-foreground">
                                    No assessment records found.
                                </div>
                            ) : (
                                <div className="max-h-[58vh] overflow-auto rounded-md border">
                                    <table className="w-full caption-bottom border-collapse text-sm">
                                        <thead className="[&_tr]:border-b">
                                            <tr className="border-b transition-colors duration-200">
                                                <th className="sticky top-0 left-0 z-40 h-10 min-w-36 border-r bg-background px-2 pl-6 text-left align-middle font-medium whitespace-nowrap shadow-sm">
                                                    &nbsp;
                                                </th>
                                                {assessmentGroups.map((group) => (
                                                    <th
                                                        key={group.type}
                                                        colSpan={group.columns.length}
                                                        className="sticky top-0 z-30 h-10 border-l bg-background px-2 text-center align-middle font-medium whitespace-nowrap shadow-sm"
                                                    >
                                                        {group.label}
                                                    </th>
                                                ))}
                                                <th className="sticky top-0 z-30 h-10 border-l bg-background px-2 pr-6 text-center align-middle font-medium whitespace-nowrap shadow-sm">
                                                    Final Grade
                                                </th>
                                            </tr>
                                            <tr className="border-b transition-colors duration-200">
                                                <th className="sticky top-10 left-0 z-40 h-10 border-r bg-background px-2 pl-6 text-left align-middle text-xs font-medium whitespace-nowrap text-muted-foreground shadow-sm">
                                                    Quarter
                                                </th>
                                                {assessmentGroups.flatMap((group) =>
                                                    group.columns.map((assessment) => (
                                                        <th
                                                            key={`${group.type}-${assessment.title}`}
                                                            className="sticky top-10 z-30 h-10 min-w-32 border-l bg-background px-2 text-center align-middle text-xs font-medium whitespace-nowrap shadow-sm"
                                                        >
                                                            {assessment.title} ({assessment.max_score})
                                                        </th>
                                                    )),
                                                )}
                                                <th className="sticky top-10 z-30 h-10 border-l bg-background px-2 pr-6 text-center align-middle text-xs font-medium whitespace-nowrap text-muted-foreground shadow-sm">
                                                    Verified
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="[&_tr:last-child]:border-0">
                                            {quarters.map((quarter) => {
                                                const grade = gradeSummaryMap.get(quarter);

                                                return (
                                                    <tr
                                                        key={quarter}
                                                        className="border-b transition-colors duration-200 hover:bg-primary/5 dark:hover:bg-primary/10"
                                                    >
                                                        <td className="sticky left-0 z-20 border-r bg-background p-2 pl-6 align-middle font-medium whitespace-nowrap shadow-sm">
                                                            {quarterLabel(quarter)}
                                                        </td>
                                                        {assessmentGroups.flatMap((group) =>
                                                            group.columns.map((assessment) => {
                                                                const score = assessmentScoreMap.get(
                                                                    `${quarter}|${group.type}|${assessment.title}`,
                                                                );

                                                                return (
                                                                    <td
                                                                        key={`${quarter}-${group.type}-${assessment.title}`}
                                                                        className="border-l p-2 text-center align-middle whitespace-nowrap"
                                                                    >
                                                                        {score
                                                                            ? score.score.toFixed(2)
                                                                            : '-'}
                                                                    </td>
                                                                );
                                                            }),
                                                        )}
                                                        <td className="border-l p-2 pr-6 text-center align-middle font-medium whitespace-nowrap">
                                                            {grade ? grade.grade.toFixed(2) : '-'}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                        </TabsContent>

                        <TabsContent value="attendance" className="mt-4">
                            <div className="max-h-[58vh] overflow-auto rounded-md border">
                                <table className="w-full caption-bottom border-collapse text-sm">
                                    <thead className="[&_tr]:border-b">
                                        <tr className="border-b transition-colors duration-200">
                                            <th
                                                rowSpan={3}
                                                className="sticky top-0 left-0 z-40 h-10 min-w-52 border-r bg-background px-2 pl-6 text-left align-middle font-medium whitespace-nowrap shadow-sm"
                                            >
                                                Student
                                            </th>
                                            {attendanceMonthGroups.map((group) => (
                                                <th
                                                    key={group.key}
                                                    colSpan={group.rows.length}
                                                    className="sticky top-0 z-30 h-10 border-l bg-background px-2 text-center align-middle font-medium whitespace-nowrap shadow-sm"
                                                >
                                                    {group.label}
                                                </th>
                                            ))}
                                        </tr>
                                        <tr className="border-b transition-colors duration-200">
                                            {attendanceMonthGroups.flatMap((group) =>
                                                group.rows.map((attendance) => (
                                                    <th
                                                        key={attendance.date}
                                                        className="sticky top-10 z-30 h-10 min-w-10 border-l bg-background px-2 text-center align-middle text-xs font-medium whitespace-nowrap shadow-sm"
                                                    >
                                                        {dayLabel(attendance.date)}
                                                    </th>
                                                )),
                                            )}
                                        </tr>
                                        <tr className="border-b transition-colors duration-200">
                                            {attendanceMonthGroups.flatMap((group) =>
                                                group.rows.map((attendance) => (
                                                    <th
                                                        key={`${attendance.date}-weekday`}
                                                        className="sticky top-20 z-30 h-10 min-w-10 border-l bg-background px-2 text-center align-middle text-[10px] font-medium whitespace-nowrap text-muted-foreground shadow-sm"
                                                    >
                                                        {weekdayLabel(attendance.date)}
                                                    </th>
                                                )),
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="[&_tr:last-child]:border-0">
                                        {(selectedRecords?.attendance ?? []).length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={2}
                                                    className="p-2 py-10 text-center align-middle text-sm whitespace-nowrap text-muted-foreground"
                                                >
                                                    No attendance records found.
                                                </td>
                                            </tr>
                                        ) : (
                                            <tr className="border-b transition-colors duration-200 hover:bg-primary/5 dark:hover:bg-primary/10">
                                                <td className="sticky left-0 z-20 border-r bg-background p-2 pl-6 align-middle font-medium whitespace-nowrap shadow-sm">
                                                    {selectedStudent?.student_name ?? 'Student'}
                                                </td>
                                                {(selectedRecords?.attendance ?? []).map((attendance, index) => (
                                                    <td
                                                        key={`${attendance.date}-${index}`}
                                                        className="border-l p-0 text-center align-middle whitespace-nowrap"
                                                    >
                                                        <ReadOnlySf2MarkCell status={attendance.status} />
                                                    </td>
                                                ))}
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </TabsContent>
                    </Tabs>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
