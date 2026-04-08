import { Head } from '@inertiajs/react';
import { FilePlus2, Pencil, Plus, Printer, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
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
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
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
        title: 'Permanent Records',
        href: '/registrar/permanent-records',
    },
];

type PermanentRecordStatus =
    | 'promoted'
    | 'conditional'
    | 'retained'
    | 'completed';

interface SubjectQuarterGrade {
    subject: string;
    q1: string;
    q2: string;
    q3: string;
    q4: string;
    final: string;
}

interface HistoricalSubjectInput {
    id: number;
    subject: string;
    q1: string;
    q2: string;
    q3: string;
    q4: string;
}

interface PermanentRecord {
    id: number;
    school_year: string;
    grade_level: string;
    school_name: string;
    status: PermanentRecordStatus;
    failed_subject_count: number;
    subjects: SubjectQuarterGrade[];
}

interface EditHistoricalRecordForm {
    id: number;
    school_name: string;
    school_year_start: string;
    school_year_end: string;
    grade_level: string;
    status: PermanentRecordStatus;
    subjects: HistoricalSubjectInput[];
}

interface StudentOption {
    id: number;
    name: string;
    lrn: string;
}

interface SelectedStudent {
    id: number;
    name: string;
    lrn: string;
    current_assignment: string;
}

interface Props {
    students: StudentOption[];
    selected_student: SelectedStudent | null;
    records: PermanentRecord[];
    filters: {
        student_id: number | null;
    };
}

function statusBadge(status: PermanentRecordStatus) {
    if (status === 'promoted') {
        return <Badge variant="outline" className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">Promoted</Badge>;
    }

    if (status === 'conditional') {
        return <Badge variant="outline" className="bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400 border-amber-200 dark:border-amber-800">Conditional</Badge>;
    }

    if (status === 'retained') {
        return <Badge variant="outline" className="bg-red-500/15 text-red-700 hover:bg-red-500/25 dark:text-red-400 border-red-200 dark:border-red-800">Retained</Badge>;
    }

    return <Badge variant="outline">Completed</Badge>;
}

function computeGeneralAverage(subjects: SubjectQuarterGrade[]) {
    const finalValues = subjects
        .map((subject) => Number(subject.final))
        .filter((value) => !Number.isNaN(value));

    if (finalValues.length === 0) {
        return '-';
    }

    const average =
        finalValues.reduce((carry, value) => carry + value, 0) /
        finalValues.length;

    return average.toFixed(2);
}

function computeHistoricalFinal(subject: HistoricalSubjectInput): string {
    const quarterValues = [subject.q1, subject.q2, subject.q3, subject.q4]
        .map((value) => Number(value))
        .filter((value) => !Number.isNaN(value));

    if (quarterValues.length === 0) {
        return '-';
    }

    const average =
        quarterValues.reduce((carry, value) => carry + value, 0) /
        quarterValues.length;

    return average.toFixed(2);
}

function toHistoricalInputSubjects(
    subjects: SubjectQuarterGrade[],
): HistoricalSubjectInput[] {
    return subjects.map((subject, index) => ({
        id: index + 1,
        subject: subject.subject,
        q1: subject.q1,
        q2: subject.q2,
        q3: subject.q3,
        q4: subject.q4,
    }));
}

function toRecordSubjects(
    subjects: HistoricalSubjectInput[],
): SubjectQuarterGrade[] {
    return subjects.map((subject) => ({
        subject: subject.subject,
        q1: subject.q1,
        q2: subject.q2,
        q3: subject.q3,
        q4: subject.q4,
        final: computeHistoricalFinal(subject),
    }));
}

function computeFailedSubjectCount(subjects: SubjectQuarterGrade[]): number {
    return subjects.filter((subject) => {
        const finalValue = Number(subject.final);

        if (Number.isNaN(finalValue)) {
            return false;
        }

        return finalValue < 75;
    }).length;
}

export default function PermanentRecords({
    records: initialRecords,
    selected_student: selectedStudent,
}: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [records, setRecords] = useState<PermanentRecord[]>(initialRecords);
    const [historicalSubjects, setHistoricalSubjects] = useState<
        HistoricalSubjectInput[]
    >([
        {
            id: 1,
            subject: '',
            q1: '',
            q2: '',
            q3: '',
            q4: '',
        },
    ]);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editForm, setEditForm] = useState<EditHistoricalRecordForm | null>(
        null,
    );

    const addHistoricalSubject = () => {
        setHistoricalSubjects((previousRows) => [
            ...previousRows,
            {
                id: Date.now(),
                subject: '',
                q1: '',
                q2: '',
                q3: '',
                q4: '',
            },
        ]);
    };

    const updateHistoricalSubject = (
        id: number,
        field: keyof Omit<HistoricalSubjectInput, 'id'>,
        value: string,
    ) => {
        setHistoricalSubjects((previousRows) =>
            previousRows.map((row) => {
                if (row.id !== id) {
                    return row;
                }

                return {
                    ...row,
                    [field]: value,
                };
            }),
        );
    };

    const removeHistoricalSubject = (id: number) => {
        setHistoricalSubjects((previousRows) => {
            if (previousRows.length <= 1) {
                return previousRows;
            }

            return previousRows.filter((row) => row.id !== id);
        });
    };

    const openEditDialog = (record: PermanentRecord) => {
        const [schoolYearStart = '', schoolYearEnd = ''] =
            record.school_year.split('-');

        setEditForm({
            id: record.id,
            school_name: record.school_name,
            school_year_start: schoolYearStart,
            school_year_end: schoolYearEnd,
            grade_level: record.grade_level,
            status: record.status,
            subjects: toHistoricalInputSubjects(record.subjects),
        });
        setIsEditDialogOpen(true);
    };

    const closeEditDialog = () => {
        setIsEditDialogOpen(false);
        setEditForm(null);
    };

    const updateEditField = (
        field:
            | 'school_name'
            | 'school_year_start'
            | 'school_year_end'
            | 'grade_level',
        value: string,
    ) => {
        setEditForm((previousForm) => {
            if (previousForm === null) {
                return null;
            }

            return {
                ...previousForm,
                [field]: value,
            };
        });
    };

    const updateEditStatus = (status: PermanentRecordStatus) => {
        setEditForm((previousForm) => {
            if (previousForm === null) {
                return null;
            }

            return {
                ...previousForm,
                status,
            };
        });
    };

    const addEditSubject = () => {
        setEditForm((previousForm) => {
            if (previousForm === null) {
                return null;
            }

            return {
                ...previousForm,
                subjects: [
                    ...previousForm.subjects,
                    {
                        id: Date.now(),
                        subject: '',
                        q1: '',
                        q2: '',
                        q3: '',
                        q4: '',
                    },
                ],
            };
        });
    };

    const updateEditSubject = (
        id: number,
        field: keyof Omit<HistoricalSubjectInput, 'id'>,
        value: string,
    ) => {
        setEditForm((previousForm) => {
            if (previousForm === null) {
                return null;
            }

            return {
                ...previousForm,
                subjects: previousForm.subjects.map((subject) => {
                    if (subject.id !== id) {
                        return subject;
                    }

                    return {
                        ...subject,
                        [field]: value,
                    };
                }),
            };
        });
    };

    const removeEditSubject = (id: number) => {
        setEditForm((previousForm) => {
            if (previousForm === null || previousForm.subjects.length <= 1) {
                return previousForm;
            }

            return {
                ...previousForm,
                subjects: previousForm.subjects.filter(
                    (subject) => subject.id !== id,
                ),
            };
        });
    };

    const saveEditedRecord = () => {
        if (editForm === null) {
            return;
        }

        const schoolYear = `${editForm.school_year_start}-${editForm.school_year_end}`;
        const computedSubjects = toRecordSubjects(editForm.subjects);
        const failedSubjectCount = computeFailedSubjectCount(computedSubjects);

        setRecords((previousRecords) =>
            previousRecords.map((record) => {
                if (record.id !== editForm.id) {
                    return record;
                }

                return {
                    ...record,
                    school_name: editForm.school_name,
                    school_year: schoolYear,
                    grade_level: editForm.grade_level,
                    status: editForm.status,
                    failed_subject_count: failedSubjectCount,
                    subjects: computedSubjects,
                };
            }),
        );

        closeEditDialog();
    };

    const filteredRecords = useMemo(() => {
        const query = searchQuery.trim().toLowerCase();

        if (query === '') {
            return records;
        }

        return records.filter((record) => {
            const matchesRecordInfo =
                record.school_year.toLowerCase().includes(query) ||
                record.grade_level.toLowerCase().includes(query) ||
                record.school_name.toLowerCase().includes(query) ||
                record.status.toLowerCase().includes(query);

            const matchesSubject = record.subjects.some((subject) =>
                subject.subject.toLowerCase().includes(query),
            );

            return matchesRecordInfo || matchesSubject;
        });
    }, [records, searchQuery]);

    const searchSuggestions = useMemo(
        () =>
            records.map((record) => ({
                id: record.id,
                label: `${record.grade_level} • ${record.school_year}`,
                value: `${record.grade_level} ${record.school_year}`,
                description: record.school_name,
                keywords: `${record.status} ${record.subjects
                    .map((subject) => subject.subject)
                    .join(' ')}`,
            })),
        [records],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permanent Records" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div className="space-y-1">
                                <CardTitle>Permanent Records</CardTitle>
                            </div>

                            <Button variant="outline">
                                <Printer className="size-4" />
                                Print SF10
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div>
                            <SearchAutocompleteInput
                                value={searchQuery}
                                onValueChange={setSearchQuery}
                                suggestions={searchSuggestions}
                                showSuggestions={false}
                                placeholder="Search by school year, grade level, school, status, or subject"
                            />
                        </div>
                    </CardContent>
                </Card>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <div className="min-w-0 space-y-6">
                        <Card className="gap-2">
                            <CardHeader className="border-b">
                                <CardTitle>Student Information</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="space-y-1">
                                        <p className="text-xs text-muted-foreground uppercase">
                                            Student
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selectedStudent?.name ?? '-'}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-xs text-muted-foreground uppercase">
                                            LRN
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selectedStudent?.lrn ?? '-'}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-xs text-muted-foreground uppercase">
                                            Current Assignment
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selectedStudent?.current_assignment ??
                                                'Unassigned'}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <CardTitle>
                                        Academic History (SF10)
                                    </CardTitle>
                                    <Badge variant="secondary">
                                        Records: {filteredRecords.length}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4 p-4">
                                {filteredRecords.length === 0 ? (
                                    <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                        No permanent records found.
                                    </div>
                                ) : (
                                    filteredRecords.map((record) => (
                                        <Card key={record.id} className="gap-2">
                                            <CardHeader className="border-b">
                                                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div className="space-y-1">
                                                        <CardTitle className="text-base">
                                                            {record.grade_level}{' '}
                                                            ·{' '}
                                                            {record.school_year}
                                                        </CardTitle>
                                                        <p className="text-sm text-muted-foreground">
                                                            {record.school_name}
                                                        </p>
                                                    </div>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        {statusBadge(
                                                            record.status,
                                                        )}
                                                        <Badge variant="outline">
                                                            Failed Subjects:{' '}
                                                            {
                                                                record.failed_subject_count
                                                            }
                                                        </Badge>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openEditDialog(
                                                                    record,
                                                                )
                                                            }
                                                        >
                                                            <Pencil className="size-4" />
                                                            Edit
                                                        </Button>
                                                    </div>
                                                </div>
                                            </CardHeader>
                                            <CardContent className="p-0">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead className="pl-6">
                                                                Subject
                                                            </TableHead>
                                                            <TableHead className="border-l text-center">
                                                                Q1
                                                            </TableHead>
                                                            <TableHead className="border-l text-center">
                                                                Q2
                                                            </TableHead>
                                                            <TableHead className="border-l text-center">
                                                                Q3
                                                            </TableHead>
                                                            <TableHead className="border-l text-center">
                                                                Q4
                                                            </TableHead>
                                                            <TableHead className="border-l pr-6 text-right">
                                                                Final
                                                            </TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {record.subjects.map(
                                                            (subject) => (
                                                                <TableRow
                                                                    key={`${record.id}-${subject.subject}`}
                                                                >
                                                                    <TableCell className="pl-6 font-medium">
                                                                        {
                                                                            subject.subject
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l text-center">
                                                                        {
                                                                            subject.q1
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l text-center">
                                                                        {
                                                                            subject.q2
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l text-center">
                                                                        {
                                                                            subject.q3
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l text-center">
                                                                        {
                                                                            subject.q4
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell className="border-l pr-6 text-right font-medium">
                                                                        {
                                                                            subject.final
                                                                        }
                                                                    </TableCell>
                                                                </TableRow>
                                                            ),
                                                        )}
                                                        <TableRow>
                                                            <TableCell className="pl-6 font-semibold">
                                                                General Average
                                                            </TableCell>
                                                            <TableCell className="border-l text-center">
                                                                -
                                                            </TableCell>
                                                            <TableCell className="border-l text-center">
                                                                -
                                                            </TableCell>
                                                            <TableCell className="border-l text-center">
                                                                -
                                                            </TableCell>
                                                            <TableCell className="border-l text-center">
                                                                -
                                                            </TableCell>
                                                            <TableCell className="border-l pr-6 text-right font-semibold">
                                                                {computeGeneralAverage(
                                                                    record.subjects,
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableBody>
                                                </Table>
                                            </CardContent>
                                        </Card>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card className="min-w-0 gap-2 self-start">
                        <CardHeader className="border-b">
                            <CardTitle className="flex items-center gap-2">
                                <FilePlus2 className="size-4" />
                                Add Historical Record
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-6">
                            <div className="space-y-2">
                                <Label>School Name</Label>
                                <Input placeholder="Previous school name" />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label>SY Start</Label>
                                    <Input placeholder="2024" />
                                </div>
                                <div className="space-y-2">
                                    <Label>SY End</Label>
                                    <Input placeholder="2025" />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label>Grade Level</Label>
                                    <Select defaultValue="grade_7">
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="grade_7">
                                                Grade 7
                                            </SelectItem>
                                            <SelectItem value="grade_8">
                                                Grade 8
                                            </SelectItem>
                                            <SelectItem value="grade_9">
                                                Grade 9
                                            </SelectItem>
                                            <SelectItem value="grade_10">
                                                Grade 10
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Status</Label>
                                    <Select defaultValue="promoted">
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="promoted">
                                                Promoted
                                            </SelectItem>
                                            <SelectItem value="conditional">
                                                Conditional
                                            </SelectItem>
                                            <SelectItem value="retained">
                                                Retained
                                            </SelectItem>
                                            <SelectItem value="completed">
                                                Completed
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between gap-2">
                                    <Label>Subject Quarterly Grades</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addHistoricalSubject}
                                    >
                                        <Plus className="size-4" />
                                        Add Subject
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {historicalSubjects.map(
                                        (historicalSubject, index) => (
                                            <div
                                                key={historicalSubject.id}
                                                className="space-y-2 rounded-md border p-2.5"
                                            >
                                                <div className="flex items-center gap-2">
                                                    <Input
                                                        placeholder={`Subject ${index + 1}`}
                                                        value={
                                                            historicalSubject.subject
                                                        }
                                                        onChange={(event) =>
                                                            updateHistoricalSubject(
                                                                historicalSubject.id,
                                                                'subject',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon-sm"
                                                        onClick={() =>
                                                            removeHistoricalSubject(
                                                                historicalSubject.id,
                                                            )
                                                        }
                                                        disabled={
                                                            historicalSubjects.length <=
                                                            1
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>

                                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                                    <Input
                                                        aria-label="Quarter 1 grade"
                                                        placeholder="Q1"
                                                        value={
                                                            historicalSubject.q1
                                                        }
                                                        onChange={(event) =>
                                                            updateHistoricalSubject(
                                                                historicalSubject.id,
                                                                'q1',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="text-center"
                                                    />
                                                    <Input
                                                        aria-label="Quarter 2 grade"
                                                        placeholder="Q2"
                                                        value={
                                                            historicalSubject.q2
                                                        }
                                                        onChange={(event) =>
                                                            updateHistoricalSubject(
                                                                historicalSubject.id,
                                                                'q2',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="text-center"
                                                    />
                                                    <Input
                                                        aria-label="Quarter 3 grade"
                                                        placeholder="Q3"
                                                        value={
                                                            historicalSubject.q3
                                                        }
                                                        onChange={(event) =>
                                                            updateHistoricalSubject(
                                                                historicalSubject.id,
                                                                'q3',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="text-center"
                                                    />
                                                    <Input
                                                        aria-label="Quarter 4 grade"
                                                        placeholder="Q4"
                                                        value={
                                                            historicalSubject.q4
                                                        }
                                                        onChange={(event) =>
                                                            updateHistoricalSubject(
                                                                historicalSubject.id,
                                                                'q4',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="text-center"
                                                    />
                                                    <div className="flex h-9 items-center justify-center rounded-md border bg-muted/40 text-sm font-medium">
                                                        {computeHistoricalFinal(
                                                            historicalSubject,
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>

                                <p className="text-xs text-muted-foreground">
                                    Final subject grades, failed-subject count,
                                    and general average are auto-computed from
                                    quarterly grades.
                                </p>
                            </div>

                            <div className="flex justify-end border-t pt-4">
                                <Button type="button">
                                    Save Historical Record
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog
                open={isEditDialogOpen}
                onOpenChange={(open) => {
                    if (open) {
                        setIsEditDialogOpen(true);
                    } else {
                        closeEditDialog();
                    }
                }}
            >
                <DialogContent className="max-h-[90vh] sm:max-w-2xl">
                    <DialogHeader className="border-b">
                        <DialogTitle>Edit Historical Record</DialogTitle>
                    </DialogHeader>

                    {editForm !== null ? (
                        <div className="max-h-[62vh] space-y-4 overflow-y-auto pt-4 pr-1">
                            <div className="space-y-2">
                                <Label>School Name</Label>
                                <Input
                                    value={editForm.school_name}
                                    onChange={(event) =>
                                        updateEditField(
                                            'school_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label>SY Start</Label>
                                    <Input
                                        value={editForm.school_year_start}
                                        onChange={(event) =>
                                            updateEditField(
                                                'school_year_start',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>SY End</Label>
                                    <Input
                                        value={editForm.school_year_end}
                                        onChange={(event) =>
                                            updateEditField(
                                                'school_year_end',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label>Grade Level</Label>
                                    <Select
                                        value={editForm.grade_level}
                                        onValueChange={(value) =>
                                            updateEditField(
                                                'grade_level',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Grade 7">
                                                Grade 7
                                            </SelectItem>
                                            <SelectItem value="Grade 8">
                                                Grade 8
                                            </SelectItem>
                                            <SelectItem value="Grade 9">
                                                Grade 9
                                            </SelectItem>
                                            <SelectItem value="Grade 10">
                                                Grade 10
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Status</Label>
                                    <Select
                                        value={editForm.status}
                                        onValueChange={(value) =>
                                            updateEditStatus(
                                                value as PermanentRecordStatus,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="promoted">
                                                Promoted
                                            </SelectItem>
                                            <SelectItem value="conditional">
                                                Conditional
                                            </SelectItem>
                                            <SelectItem value="retained">
                                                Retained
                                            </SelectItem>
                                            <SelectItem value="completed">
                                                Completed
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between gap-2">
                                    <Label>Subject Quarterly Grades</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addEditSubject}
                                    >
                                        <Plus className="size-4" />
                                        Add Subject
                                    </Button>
                                </div>

                                <div className="max-h-[360px] space-y-2 overflow-y-auto">
                                    {editForm.subjects.map((subject, index) => (
                                        <div
                                            key={subject.id}
                                            className="space-y-2 rounded-md border p-2.5"
                                        >
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    placeholder={`Subject ${index + 1}`}
                                                    value={subject.subject}
                                                    onChange={(event) =>
                                                        updateEditSubject(
                                                            subject.id,
                                                            'subject',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    onClick={() =>
                                                        removeEditSubject(
                                                            subject.id,
                                                        )
                                                    }
                                                    disabled={
                                                        editForm.subjects
                                                            .length <= 1
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>

                                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                                <Input
                                                    aria-label="Quarter 1 grade"
                                                    placeholder="Q1"
                                                    value={subject.q1}
                                                    onChange={(event) =>
                                                        updateEditSubject(
                                                            subject.id,
                                                            'q1',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="text-center"
                                                />
                                                <Input
                                                    aria-label="Quarter 2 grade"
                                                    placeholder="Q2"
                                                    value={subject.q2}
                                                    onChange={(event) =>
                                                        updateEditSubject(
                                                            subject.id,
                                                            'q2',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="text-center"
                                                />
                                                <Input
                                                    aria-label="Quarter 3 grade"
                                                    placeholder="Q3"
                                                    value={subject.q3}
                                                    onChange={(event) =>
                                                        updateEditSubject(
                                                            subject.id,
                                                            'q3',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="text-center"
                                                />
                                                <Input
                                                    aria-label="Quarter 4 grade"
                                                    placeholder="Q4"
                                                    value={subject.q4}
                                                    onChange={(event) =>
                                                        updateEditSubject(
                                                            subject.id,
                                                            'q4',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="text-center"
                                                />
                                                <div className="flex h-9 items-center justify-center rounded-md border bg-muted/40 text-sm font-medium">
                                                    {computeHistoricalFinal(
                                                        subject,
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : null}

                    <DialogFooter className="border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={closeEditDialog}
                        >
                            Cancel
                        </Button>
                        <Button type="button" onClick={saveEditedRecord}>
                            Save Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
