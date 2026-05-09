import { Head, router } from '@inertiajs/react';
import { Eye, ListChecks, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
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
import AppLayout from '@/layouts/app-layout';
import registrar from '@/routes/registrar';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Remedial Entry',
        href: '/registrar/remedial-entry',
    },
];

interface Option {
    id: number;
    name: string;
}

interface StudentOption {
    id: number;
    lrn: string;
    name: string;
    grade_level_id: number | null;
    grade_and_section: string;
}

interface ConditionalStudent {
    id: number;
    lrn: string;
    name: string;
    grade_and_section: string;
    failed_subject_count: number;
    remedial_case_status: string;
}

interface RemedialRow {
    record_id: number | null;
    subject_id: number;
    subject_name: string;
    school_year: string;
    enrolled_for_remedial: boolean;
    assigned_teacher_id: number | null;
    assigned_teacher_name: string | null;
    final_rating: number | null;
    remedial_class_mark: number | null;
    recomputed_final_grade: number | null;
    status: string;
}

interface SelectedStudent {
    id: number;
    name: string;
    lrn: string;
    grade_and_section: string;
    overall_result: string;
}

interface RemedialCaseSummary {
    id: number;
    failed_subject_count: number;
    fee_per_subject: number;
    total_amount: number;
    amount_paid: number;
    balance: number;
    status: string;
    paid_at: string | null;
}

interface RecentEncodingDetail {
    subject_name: string;
    final_rating: number;
    remedial_class_mark: number;
    recomputed_final_grade: number;
    status: string;
}

interface RecentEncoding {
    key: string;
    student_name: string;
    lrn: string;
    school_year: string;
    updated_at: string;
    status: string;
    details: RecentEncodingDetail[];
}

interface Props {
    academic_years: Option[];
    students: StudentOption[];
    conditional_students: ConditionalStudent[];
    selected_student: SelectedStudent | null;
    remedial_case: RemedialCaseSummary | null;
    remedial_rows: RemedialRow[];
    teacher_options: Option[];
    recent_encodings: RecentEncoding[];
    filters: {
        academic_year_id: number | null;
        grade_level_id: number | null;
        search: string | null;
        student_id: number | null;
    };
}

function prettifyCaseStatus(status: string): string {
    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function RemedialEntry({
    academic_years,
    conditional_students,
    selected_student,
    remedial_case,
    remedial_rows,
    teacher_options,
    recent_encodings,
    filters,
}: Props) {
    const [academicYearId] = useState<string>(
        filters.academic_year_id
            ? String(filters.academic_year_id)
            : academic_years[0]
              ? String(academic_years[0].id)
              : '',
    );
    const [studentId, setStudentId] = useState<string>(
        filters.student_id ? String(filters.student_id) : '',
    );
    const [isStudentModalOpen, setIsStudentModalOpen] = useState(false);
    const [studentModalQuery, setStudentModalQuery] = useState('');
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
    const [selectedHistory, setSelectedHistory] = useState<RecentEncoding | null>(
        null,
    );
    const [enrollingSubjectId, setEnrollingSubjectId] = useState<number | null>(
        null,
    );
    const [teacherAssignments, setTeacherAssignments] = useState<
        Record<number, string>
    >(() => {
        const initial: Record<number, string> = {};
        remedial_rows.forEach((row) => {
            if (row.assigned_teacher_id) {
                initial[row.subject_id] = String(row.assigned_teacher_id);
            }
        });

        return initial;
    });

    useEffect(() => {
        const nextAssignments: Record<number, string> = {};
        remedial_rows.forEach((row) => {
            if (row.assigned_teacher_id) {
                nextAssignments[row.subject_id] = String(row.assigned_teacher_id);
            }
        });
        setTeacherAssignments(nextAssignments);
    }, [remedial_rows, studentId]);

    const conditionalStudentsFiltered = useMemo(() => {
        const query = studentModalQuery.trim().toLowerCase();
        if (query === '') {
            return conditional_students;
        }

        return conditional_students.filter((student) => {
            const haystack = `${student.name} ${student.lrn} ${student.grade_and_section}`.toLowerCase();
            return haystack.includes(query);
        });
    }, [conditional_students, studentModalQuery]);

    const applyFilters = (next?: {
        academicYearId?: string;
        studentId?: string;
    }) => {
        const resolvedAcademicYear = next?.academicYearId ?? academicYearId;
        const resolvedStudent = next?.studentId ?? studentId;

        router.get(
            registrar.remedial_entry.url({
                query: {
                    academic_year_id: resolvedAcademicYear || undefined,
                    student_id: resolvedStudent || undefined,
                },
            }),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const isRemedialPaid = remedial_case?.status === 'paid';

    const enrollFailedSubject = (subjectId: number) => {
        if (!studentId || !academicYearId) {
            return;
        }

        setEnrollingSubjectId(subjectId);
        router.post(
            '/registrar/remedial-entry/intake-subject',
            {
                academic_year_id: Number(academicYearId),
                student_id: Number(studentId),
                subject_id: subjectId,
                assigned_teacher_id: Number(
                    teacherAssignments[subjectId] || 0,
                ),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setEnrollingSubjectId(null);
                },
            },
        );
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Remedial Entry" />

                <div className="flex flex-col gap-6">
                    <Card className="gap-2">
                        <CardHeader className="border-b">
                            <div className="flex items-center justify-between gap-3">
                                <CardTitle>Selected Student</CardTitle>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    onClick={() => setIsStudentModalOpen(true)}
                                >
                                    <Users className="size-4" />
                                    {`Select Student (${conditional_students.length})`}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-5">
                            <div className="rounded-lg border bg-muted/30 p-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="space-y-1">
                                        <p className="text-xs uppercase tracking-wide text-muted-foreground">
                                            Student
                                        </p>
                                        <p className="text-base font-semibold">
                                            {selected_student?.name || '--'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {`LRN: ${selected_student?.lrn || '--'}`}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {selected_student?.overall_result ||
                                            'No Student'}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                            <CardHeader className="border-b">
                                <CardTitle>Remedial Subject Ratings</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Subject
                                            </TableHead>
                                            <TableHead>School Year</TableHead>
                                            <TableHead className="border-l text-center">
                                                Failing Grade
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Assigned Teacher
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Intake
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Encoding
                                            </TableHead>
                                            <TableHead className="border-l pr-6 text-right">
                                                Status
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {remedial_rows.map((row) => {
                                            return (
                                                <TableRow key={row.subject_id}>
                                                    <TableCell className="pl-6 font-medium">
                                                        {row.subject_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        {row.school_year}
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        {row.final_rating ?? '--'}
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        <Select
                                                            value={
                                                                teacherAssignments[
                                                                    row.subject_id
                                                                ] ??
                                                                (row.assigned_teacher_id
                                                                    ? String(
                                                                          row.assigned_teacher_id,
                                                                      )
                                                                    : 'none')
                                                            }
                                                            onValueChange={(value) =>
                                                                setTeacherAssignments(
                                                                    (current) => ({
                                                                        ...current,
                                                                        [row.subject_id]:
                                                                            value ===
                                                                            'none'
                                                                                ? ''
                                                                                : value,
                                                                    }),
                                                                )
                                                            }
                                                            disabled={row.enrolled_for_remedial}
                                                        >
                                                            <SelectTrigger className="mx-auto w-52">
                                                                <SelectValue placeholder="Assign teacher" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="none">
                                                                    Assign teacher
                                                                </SelectItem>
                                                                {teacher_options.map(
                                                                    (teacher) => (
                                                                        <SelectItem
                                                                            key={
                                                                                teacher.id
                                                                            }
                                                                            value={String(
                                                                                teacher.id,
                                                                            )}
                                                                        >
                                                                            {
                                                                                teacher.name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        {row.record_id !== null ? (
                                                            <Badge variant="outline">
                                                                Added
                                                            </Badge>
                                                        ) : row.enrolled_for_remedial ? (
                                                            <Badge variant="outline">
                                                                Queued
                                                            </Badge>
                                                        ) : (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={
                                                                    enrollingSubjectId ===
                                                                        row.subject_id ||
                                                                    !teacherAssignments[
                                                                        row.subject_id
                                                                    ]
                                                                }
                                                                onClick={() =>
                                                                    enrollFailedSubject(
                                                                        row.subject_id,
                                                                    )
                                                                }
                                                            >
                                                                Add to Intake
                                                            </Button>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        {row.record_id !== null ? (
                                                            <Badge variant="outline">
                                                                Encoded
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline">
                                                                Pending
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="border-l pr-6 text-right">
                                                        {row.record_id !== null ? (
                                                            <Badge variant="outline">
                                                                {row.status}
                                                            </Badge>
                                                        ) : row.enrolled_for_remedial ? (
                                                            <Badge variant="outline">
                                                                {isRemedialPaid
                                                                    ? 'For Teacher Encoding'
                                                                    : 'For Cashier Payment'}
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline">
                                                                Not Selected
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                        {remedial_rows.length === 0 && (
                                            <TableRow>
                                            <TableCell
                                                    colSpan={7}
                                                    className="h-24 text-center text-sm text-muted-foreground"
                                                >
                                                    No failed subjects found for
                                                    this student context.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                            <div className="sticky bottom-0 z-10 flex items-center justify-end gap-2 border-t bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsHistoryModalOpen(true)}
                                >
                                    <ListChecks className="size-4" />
                                    Remedial Encoding History
                                </Button>
                            </div>
                        </Card>
                </div>
            </AppLayout>

            <Dialog open={isStudentModalOpen} onOpenChange={setIsStudentModalOpen}>
                <DialogContent className="w-[95vw] max-w-[95vw] sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Select Conditional Student</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <Input
                            value={studentModalQuery}
                            onChange={(event) =>
                                setStudentModalQuery(event.target.value)
                            }
                            placeholder="Search by name, LRN, or grade"
                        />
                        <div className="max-h-[420px] overflow-auto rounded-md border">
                            <Table className="w-full table-fixed">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[30%] pl-4">Student</TableHead>
                                        <TableHead className="w-[17%]">LRN</TableHead>
                                        <TableHead className="w-[20%]">Grade</TableHead>
                                        <TableHead className="w-[10%]">Failed</TableHead>
                                        <TableHead className="w-[13%]">Intake</TableHead>
                                        <TableHead className="w-[10%] pr-4 text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {conditionalStudentsFiltered.map((student) => (
                                        <TableRow key={student.id}>
                                            <TableCell className="truncate pl-4 font-medium" title={student.name}>
                                                {student.name}
                                            </TableCell>
                                            <TableCell className="truncate whitespace-nowrap" title={student.lrn}>
                                                {student.lrn}
                                            </TableCell>
                                            <TableCell className="truncate" title={student.grade_and_section}>
                                                {student.grade_and_section}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {student.failed_subject_count}
                                            </TableCell>
                                            <TableCell className="truncate" title={prettifyCaseStatus(student.remedial_case_status)}>
                                                {prettifyCaseStatus(
                                                    student.remedial_case_status,
                                                )}
                                            </TableCell>
                                            <TableCell className="pr-4 text-right whitespace-nowrap">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const nextId = String(
                                                            student.id,
                                                        );
                                                        setStudentId(nextId);
                                                        setIsStudentModalOpen(
                                                            false,
                                                        );
                                                        applyFilters({
                                                            studentId: nextId,
                                                        });
                                                    }}
                                                >
                                                    Select
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {conditionalStudentsFiltered.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="h-20 text-center text-sm text-muted-foreground"
                                            >
                                                No conditional students found.
                                            </TableCell>
                                        </TableRow>
                                    ) : null}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={isHistoryModalOpen} onOpenChange={setIsHistoryModalOpen}>
                <DialogContent className="max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>Remedial Encoding History</DialogTitle>
                    </DialogHeader>
                    <div className="max-h-[460px] overflow-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">Student</TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>School Year</TableHead>
                                    <TableHead>Updated</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="pr-4 text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recent_encodings.map((row) => (
                                    <TableRow key={row.key}>
                                        <TableCell className="pl-4 font-medium">
                                            {row.student_name}
                                        </TableCell>
                                        <TableCell>{row.lrn}</TableCell>
                                        <TableCell>{row.school_year}</TableCell>
                                        <TableCell>{row.updated_at}</TableCell>
                                        <TableCell>{row.status}</TableCell>
                                        <TableCell className="pr-4 text-right">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setSelectedHistory(row)
                                                }
                                            >
                                                <Eye className="size-4" />
                                                View Details
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {recent_encodings.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-20 text-center text-sm text-muted-foreground"
                                        >
                                            No remedial encodings yet.
                                        </TableCell>
                                    </TableRow>
                                ) : null}
                            </TableBody>
                        </Table>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog
                open={selectedHistory !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedHistory(null);
                    }
                }}
            >
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>
                            Remedial Encoding Details
                            {selectedHistory
                                ? ` · ${selectedHistory.student_name} (${selectedHistory.school_year})`
                                : ''}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="max-h-[430px] overflow-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">Subject</TableHead>
                                    <TableHead>Failing Grade</TableHead>
                                    <TableHead>Remedial Grade</TableHead>
                                    <TableHead>Recomputed</TableHead>
                                    <TableHead className="pr-4">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(selectedHistory?.details ?? []).map((detail) => (
                                    <TableRow
                                        key={`${detail.subject_name}-${detail.final_rating}-${detail.remedial_class_mark}`}
                                    >
                                        <TableCell className="pl-4 font-medium">
                                            {detail.subject_name}
                                        </TableCell>
                                        <TableCell>{detail.final_rating}</TableCell>
                                        <TableCell>
                                            {detail.remedial_class_mark}
                                        </TableCell>
                                        <TableCell>
                                            {detail.recomputed_final_grade}
                                        </TableCell>
                                        <TableCell className="pr-4">
                                            {detail.status}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(selectedHistory?.details ?? []).length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-20 text-center text-sm text-muted-foreground"
                                        >
                                            No subject details found.
                                        </TableCell>
                                    </TableRow>
                                ) : null}
                            </TableBody>
                        </Table>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
