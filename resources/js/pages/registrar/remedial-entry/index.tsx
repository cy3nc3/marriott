import { Head, router, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { store } from '@/routes/registrar/remedial_entry';
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

interface RemedialRow {
    record_id: number | null;
    subject_id: number;
    subject_name: string;
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

interface RecentEncoding {
    student_name: string;
    lrn: string;
    school_year: string;
    updated_at: string;
    status: string;
}

interface Props {
    academic_years: Option[];
    grade_levels: Option[];
    students: StudentOption[];
    selected_student: SelectedStudent | null;
    remedial_rows: RemedialRow[];
    recent_encodings: RecentEncoding[];
    filters: {
        academic_year_id: number | null;
        grade_level_id: number | null;
        search: string | null;
        student_id: number | null;
    };
}

interface RemedialFormRow {
    subject_id: number;
    final_rating: string;
    remedial_class_mark: string;
}

export default function RemedialEntry({
    academic_years,
    grade_levels,
    students,
    selected_student,
    remedial_rows,
    recent_encodings,
    filters,
}: Props) {
    const [academicYearId, setAcademicYearId] = useState<string>(
        filters.academic_year_id
            ? String(filters.academic_year_id)
            : academic_years[0]
              ? String(academic_years[0].id)
              : '',
    );
    const [gradeLevelId, setGradeLevelId] = useState<string>(
        filters.grade_level_id ? String(filters.grade_level_id) : 'all',
    );
    const [searchQuery, setSearchQuery] = useState<string>(
        filters.search || '',
    );
    const [studentId, setStudentId] = useState<string>(
        filters.student_id
            ? String(filters.student_id)
            : students[0]
              ? String(students[0].id)
              : '',
    );

    const remedialForm = useForm<{
        academic_year_id: number;
        student_id: number;
        save_mode: 'draft' | 'submitted';
        records: RemedialFormRow[];
    }>({
        academic_year_id: Number(academicYearId || 0),
        student_id: Number(studentId || 0),
        save_mode: 'draft',
        records: remedial_rows.map((row) => ({
            subject_id: row.subject_id,
            final_rating:
                row.final_rating !== null ? String(row.final_rating) : '',
            remedial_class_mark:
                row.remedial_class_mark !== null
                    ? String(row.remedial_class_mark)
                    : '',
        })),
    });

    useEffect(() => {
        setAcademicYearId(
            filters.academic_year_id
                ? String(filters.academic_year_id)
                : academic_years[0]
                  ? String(academic_years[0].id)
                  : '',
        );
        setGradeLevelId(
            filters.grade_level_id ? String(filters.grade_level_id) : 'all',
        );
        setSearchQuery(filters.search || '');
        setStudentId(
            filters.student_id
                ? String(filters.student_id)
                : students[0]
                  ? String(students[0].id)
                  : '',
        );

        remedialForm.setData({
            academic_year_id: Number(filters.academic_year_id || 0),
            student_id: Number(filters.student_id || 0),
            save_mode: 'draft',
            records: remedial_rows.map((row) => ({
                subject_id: row.subject_id,
                final_rating:
                    row.final_rating !== null ? String(row.final_rating) : '',
                remedial_class_mark:
                    row.remedial_class_mark !== null
                        ? String(row.remedial_class_mark)
                        : '',
            })),
        });
    }, [
        filters.academic_year_id,
        filters.grade_level_id,
        filters.search,
        filters.student_id,
        academic_years,
        students,
        remedial_rows,
    ]);

    const applyFilters = (next?: {
        academicYearId?: string;
        gradeLevelId?: string;
        search?: string;
        studentId?: string;
    }) => {
        const resolvedAcademicYear = next?.academicYearId ?? academicYearId;
        const resolvedGradeLevel = next?.gradeLevelId ?? gradeLevelId;
        const resolvedSearch = next?.search ?? searchQuery;
        const resolvedStudent = next?.studentId ?? studentId;

        router.get(
            registrar.remedial_entry.url({
                query: {
                    academic_year_id: resolvedAcademicYear || undefined,
                    grade_level_id:
                        resolvedGradeLevel === 'all'
                            ? undefined
                            : resolvedGradeLevel,
                    search: resolvedSearch || undefined,
                    student_id: resolvedStudent || undefined,
                },
            }),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const updateRecord = (
        index: number,
        field: keyof RemedialFormRow,
        value: string,
    ) => {
        const updated = [...remedialForm.data.records];
        updated[index] = {
            ...updated[index],
            [field]: value,
        };
        remedialForm.setData('records', updated);
    };

    const computedRow = (index: number) => {
        const finalRaw = remedialForm.data.records[index]?.final_rating ?? '';
        const remedialRaw =
            remedialForm.data.records[index]?.remedial_class_mark ?? '';

        if (finalRaw.trim() === '' || remedialRaw.trim() === '') {
            return {
                recomputed: '--',
                status: 'For Encoding',
            };
        }

        const finalValue = Number(finalRaw);
        const remedialValue = Number(remedialRaw);
        const hasNumbers =
            !Number.isNaN(finalValue) && !Number.isNaN(remedialValue);

        if (!hasNumbers) {
            return {
                recomputed: '--',
                status: 'For Encoding',
            };
        }

        const recomputed = ((finalValue + remedialValue) / 2).toFixed(2);
        const status = Number(recomputed) >= 75 ? 'Passed' : 'Failed';

        return {
            recomputed,
            status,
        };
    };

    const save = (mode: 'draft' | 'submitted') => {
        if (!studentId || !academicYearId) return;

        remedialForm.transform((data) => ({
            ...data,
            academic_year_id: Number(academicYearId),
            student_id: Number(studentId),
            save_mode: mode,
            records: data.records.map((record) => ({
                subject_id: record.subject_id,
                final_rating:
                    record.final_rating.trim() === ''
                        ? null
                        : Number(record.final_rating),
                remedial_class_mark:
                    record.remedial_class_mark.trim() === ''
                        ? null
                        : Number(record.remedial_class_mark),
            })),
        }));

        remedialForm.post(store().url, {
            preserveScroll: true,
            onFinish: () => {
                remedialForm.transform((data) => data);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Remedial Entry" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Remedial Context</CardTitle>
                            <Badge variant="outline">
                                Status:{' '}
                                {selected_student?.overall_result ||
                                    'No Student'}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-2">
                                <Label>School Year</Label>
                                <Select
                                    value={academicYearId}
                                    onValueChange={(value) => {
                                        setAcademicYearId(value);
                                        applyFilters({
                                            academicYearId: value,
                                        });
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select school year" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {academic_years.map((year) => (
                                            <SelectItem
                                                key={year.id}
                                                value={String(year.id)}
                                            >
                                                {year.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Grade Level</Label>
                                <Select
                                    value={gradeLevelId}
                                    onValueChange={(value) => {
                                        setGradeLevelId(value);
                                        applyFilters({
                                            gradeLevelId: value,
                                        });
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Grade Levels
                                        </SelectItem>
                                        {grade_levels.map((level) => (
                                            <SelectItem
                                                key={level.id}
                                                value={String(level.id)}
                                            >
                                                {level.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Search Student</Label>
                                <div className="flex gap-2">
                                    <Input
                                        value={searchQuery}
                                        onChange={(event) =>
                                            setSearchQuery(event.target.value)
                                        }
                                        placeholder="LRN or student name"
                                    />
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            applyFilters({
                                                search: searchQuery,
                                            })
                                        }
                                    >
                                        Find
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Selected Student</Label>
                                <Select
                                    value={studentId}
                                    onValueChange={(value) => {
                                        setStudentId(value);
                                        applyFilters({
                                            studentId: value,
                                        });
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose student" />
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
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="gap-2">
                        <CardHeader className="border-b">
                            <CardTitle>Selected Student</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 pt-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Name
                                </p>
                                <p className="text-sm font-medium">
                                    {selected_student?.name || '--'}
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    LRN
                                </p>
                                <p className="text-sm font-medium">
                                    {selected_student?.lrn || '--'}
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Section
                                </p>
                                <p className="text-sm font-medium">
                                    {selected_student?.grade_and_section ||
                                        '--'}
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Overall Result
                                </p>
                                <Badge variant="outline">
                                    {selected_student?.overall_result ||
                                        'No Student'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
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
                                        <TableHead className="border-l text-center">
                                            Original
                                        </TableHead>
                                        <TableHead className="border-l text-center">
                                            Remedial
                                        </TableHead>
                                        <TableHead className="border-l text-center">
                                            Final Rating
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {remedial_rows.map((row, index) => {
                                        const liveRow = computedRow(index);

                                        return (
                                            <TableRow key={row.subject_id}>
                                                <TableCell className="pl-6 font-medium">
                                                    {row.subject_name}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    <Input
                                                        value={
                                                            remedialForm.data
                                                                .records[index]
                                                                ?.final_rating ||
                                                            ''
                                                        }
                                                        onChange={(event) =>
                                                            updateRecord(
                                                                index,
                                                                'final_rating',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="mx-auto w-20"
                                                    />
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    <Input
                                                        value={
                                                            remedialForm.data
                                                                .records[index]
                                                                ?.remedial_class_mark ||
                                                            ''
                                                        }
                                                        onChange={(event) =>
                                                            updateRecord(
                                                                index,
                                                                'remedial_class_mark',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="mx-auto w-20"
                                                    />
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {liveRow.recomputed}
                                                </TableCell>
                                                <TableCell className="border-l pr-6 text-right">
                                                    <Badge
                                                        variant={
                                                            liveRow.status ===
                                                            'Passed'
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {liveRow.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                    {remedial_rows.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="h-24 text-center text-sm text-muted-foreground"
                                            >
                                                No remedial subjects found for
                                                this context.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                        <div className="flex items-center justify-end gap-2 border-t px-4 py-3">
                            <Button
                                variant="outline"
                                onClick={() => save('draft')}
                                disabled={
                                    remedialForm.processing ||
                                    !studentId ||
                                    remedial_rows.length === 0
                                }
                            >
                                Save Draft
                            </Button>
                            <Button
                                onClick={() => save('submitted')}
                                disabled={
                                    remedialForm.processing ||
                                    !studentId ||
                                    remedial_rows.length === 0
                                }
                            >
                                <Save className="size-4" />
                                Submit Remedial Results
                            </Button>
                        </div>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Recent Remedial Encodings</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>School Year</TableHead>
                                    <TableHead>Last Updated</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recent_encodings.map((row) => (
                                    <TableRow
                                        key={`${row.lrn}-${row.school_year}`}
                                    >
                                        <TableCell className="pl-6 font-medium">
                                            {row.student_name}
                                        </TableCell>
                                        <TableCell>{row.lrn}</TableCell>
                                        <TableCell>{row.school_year}</TableCell>
                                        <TableCell>{row.updated_at}</TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <Badge
                                                variant={
                                                    row.status === 'Submitted'
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {row.status}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {recent_encodings.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-sm text-muted-foreground"
                                        >
                                            No remedial encodings yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
