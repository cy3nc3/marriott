import { Head, router } from '@inertiajs/react';
import { Download, Info, Lock, Send, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import teacher from '@/routes/teacher';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Advisory Board',
        href: '/teacher/advisory-board',
    },
];

type RatingOption = 'AO' | 'SO' | 'RO' | 'NO';
type RatingValue = RatingOption | null;

type SectionOption = {
    id: number;
    label: string;
};

type Context = {
    academic_year_options: { id: number; name: string; status: string }[];
    selected_academic_year_id: number | null;
    is_read_only_historical: boolean;
    section_options: SectionOption[];
    selected_section_id: number | null;
    selected_quarter: '1' | '2' | '3' | '4';
};

type GradeColumn = {
    id: number;
    name: string;
};

type GradeRow = {
    enrollment_id: number;
    student_name: string;
    subject_grades: Record<string, string | null>;
    general_average: string | null;
};

type ConductRow = {
    enrollment_id: number;
    student_name: string;
    ratings: {
        maka_diyos: RatingValue;
        makatao: RatingValue;
        makakalikasan: RatingValue;
        makabansa: RatingValue;
    };
    remarks: string;
};

interface Props {
    context: Context;
    grade_columns: GradeColumn[];
    grade_rows: GradeRow[];
    conduct_rows: ConductRow[];
    status: 'draft' | 'locked';
    grade_release: {
        is_released: boolean;
        released_at: string | null;
        released_by_name: string | null;
        can_release: boolean;
        blocking_message: string | null;
    };
}

const quarterLabels: Record<string, string> = {
    '1': '1st Quarter',
    '2': '2nd Quarter',
    '3': '3rd Quarter',
    '4': '4th Quarter',
};

export default function AdvisoryBoard({
    context,
    grade_columns,
    grade_rows,
    conduct_rows,
    status,
    grade_release,
}: Props) {
    const advisoryBoardRoute = teacher.advisory_board;
    const [isFinalizeDialogOpen, setIsFinalizeDialogOpen] = useState(false);
    const [isReleaseDialogOpen, setIsReleaseDialogOpen] = useState(false);
    const [conductRows, setConductRows] = useState<ConductRow[]>(conduct_rows);

    useEffect(() => {
        setConductRows(conduct_rows);
    }, [conduct_rows]);

    const selectedSectionValue = context.selected_section_id
        ? String(context.selected_section_id)
        : 'section-none';
    const selectedQuarterLabel =
        quarterLabels[context.selected_quarter] ?? 'Selected Quarter';

    const handleSectionChange = (value: string) => {
        if (value === 'section-none') {
            return;
        }

        router.get(
            advisoryBoardRoute.url({
                query: {
                    section_id: Number(value),
                    quarter: context.selected_quarter,
                    academic_year_id:
                        context.selected_academic_year_id || undefined,
                },
            }),
            {},
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleQuarterChange = (value: string) => {
        router.get(
            advisoryBoardRoute.url({
                query: {
                    section_id: context.selected_section_id || undefined,
                    quarter: value,
                    academic_year_id:
                        context.selected_academic_year_id || undefined,
                },
            }),
            {},
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const updateRating = (
        enrollmentId: number,
        ratingKey: keyof ConductRow['ratings'],
        value: RatingValue,
    ) => {
        setConductRows((currentRows) =>
            currentRows.map((row) =>
                row.enrollment_id === enrollmentId
                    ? {
                          ...row,
                          ratings: {
                              ...row.ratings,
                              [ratingKey]: value,
                          },
                      }
                    : row,
            ),
        );
    };

    const updateRemarks = (enrollmentId: number, value: string) => {
        setConductRows((currentRows) =>
            currentRows.map((row) =>
                row.enrollment_id === enrollmentId
                    ? {
                          ...row,
                          remarks: value,
                      }
                    : row,
            ),
        );
    };

    const submitConduct = (saveMode: 'draft' | 'locked') => {
        if (!context.selected_section_id || conductRows.length === 0) {
            return;
        }

        if (status === 'locked') {
            return;
        }

        router.post(
            advisoryBoardRoute.store_conduct.url(),
            {
                section_id: context.selected_section_id,
                quarter: context.selected_quarter,
                save_mode: saveMode,
                rows: conductRows.map((row) => ({
                    enrollment_id: row.enrollment_id,
                    maka_diyos: row.ratings.maka_diyos,
                    makatao: row.ratings.makatao,
                    makakalikasan: row.ratings.makakalikasan,
                    makabansa: row.ratings.makabansa,
                    remarks: row.remarks.trim(),
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (saveMode === 'locked') {
                        setIsFinalizeDialogOpen(false);
                    }
                },
            },
        );
    };

    const handleAcademicYearChange = (value: string) => {
        router.get(
            advisoryBoardRoute.url({
                query: {
                    academic_year_id: Number(value),
                },
            }),
            {},
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const releaseGrades = () => {
        if (!context.selected_section_id || !grade_release.can_release) {
            return;
        }

        router.post(
            advisoryBoardRoute.release_grades.url(),
            {
                section_id: context.selected_section_id,
                quarter: context.selected_quarter,
            },
            {
                preserveScroll: true,
                onSuccess: () => setIsReleaseDialogOpen(false),
            },
        );
    };

    const actionDisabled =
        context.is_read_only_historical ||
        status === 'locked' ||
        !context.selected_section_id ||
        conductRows.length === 0;
    const releaseDisabled =
        context.is_read_only_historical ||
        grade_release.is_released ||
        !grade_release.can_release ||
        !context.selected_section_id;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Advisory Board" />
            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Advisory Context</CardTitle>
                            <Badge
                                variant="outline"
                                className={
                                    status === 'locked'
                                        ? 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
                                        : ''
                                }
                            >
                                Status:{' '}
                                {status === 'locked' ? 'Locked' : 'Draft'}
                            </Badge>
                            <Badge
                                variant="outline"
                                className={
                                    grade_release.is_released
                                        ? 'bg-sky-500/15 text-sky-700 hover:bg-sky-500/25 dark:text-sky-400 border-sky-200 dark:border-sky-800'
                                        : ''
                                }
                            >
                                Grades:{' '}
                                {grade_release.is_released
                                    ? 'Released'
                                    : 'Not Released'}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                {context.academic_year_options.length > 0 && (
                                    <Select
                                        value={
                                            context.selected_academic_year_id
                                                ? String(
                                                      context.selected_academic_year_id,
                                                  )
                                                : undefined
                                        }
                                        onValueChange={handleAcademicYearChange}
                                    >
                                        <SelectTrigger className="w-full sm:w-44">
                                            <SelectValue placeholder="School Year" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {context.academic_year_options.map(
                                                (schoolYear) => (
                                                    <SelectItem
                                                        key={schoolYear.id}
                                                        value={String(
                                                            schoolYear.id,
                                                        )}
                                                    >
                                                        {schoolYear.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                )}
                                <Select
                                    value={selectedSectionValue}
                                    onValueChange={handleSectionChange}
                                    disabled={
                                        context.section_options.length === 0
                                    }
                                >
                                    <SelectTrigger className="w-full sm:w-56">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {context.section_options.length ===
                                        0 ? (
                                            <SelectItem
                                                value="section-none"
                                                disabled
                                            >
                                                No advisory section
                                            </SelectItem>
                                        ) : (
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
                                        )}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={context.selected_quarter}
                                    onValueChange={handleQuarterChange}
                                >
                                    <SelectTrigger className="w-full sm:w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(quarterLabels).map(
                                            ([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button
                                    variant={
                                        grade_release.is_released
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                    onClick={() =>
                                        setIsReleaseDialogOpen(true)
                                    }
                                    disabled={releaseDisabled}
                                    className={
                                        grade_release.is_released
                                            ? 'cursor-not-allowed opacity-60 saturate-50'
                                            : undefined
                                    }
                                    title={
                                        grade_release.is_released
                                            ? `${selectedQuarterLabel} grades already released.`
                                            : (grade_release.blocking_message ?? '')
                                    }
                                >
                                    <Send className="size-4" />
                                    {grade_release.is_released
                                        ? `${selectedQuarterLabel} Grades Released`
                                        : `Release ${selectedQuarterLabel} Grades`}
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => submitConduct('draft')}
                                    disabled={actionDisabled}
                                >
                                    <Save className="size-4" />
                                    Save Draft
                                </Button>
                                <Button
                                    variant={
                                        status === 'locked'
                                            ? 'secondary'
                                            : 'destructive'
                                    }
                                    onClick={() =>
                                        setIsFinalizeDialogOpen(true)
                                    }
                                    disabled={actionDisabled}
                                    className={
                                        status === 'locked'
                                            ? 'cursor-not-allowed opacity-60 saturate-50'
                                            : undefined
                                    }
                                >
                                    <Lock className="size-4" />
                                    {status === 'locked'
                                        ? 'Finalized and Locked'
                                        : 'Finalize and Lock'}
                                </Button>
                            </div>
                        </div>
                        {grade_release.is_released && (
                            <p className="mt-4 text-sm text-muted-foreground">
                                Released by{' '}
                                {grade_release.released_by_name ?? '-'}.
                            </p>
                        )}
                        {!grade_release.is_released &&
                            grade_release.blocking_message && (
                                <p className="mt-4 text-sm text-muted-foreground">
                                    {grade_release.blocking_message}
                                </p>
                            )}
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
                            <CardContent className="overflow-x-auto p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Student
                                            </TableHead>
                                            {grade_columns.map(
                                                (subjectColumn) => (
                                                    <TableHead
                                                        key={subjectColumn.id}
                                                        className="border-l text-center whitespace-nowrap"
                                                    >
                                                        {subjectColumn.name}
                                                    </TableHead>
                                                ),
                                            )}
                                            <TableHead className="border-l pr-6 text-right whitespace-nowrap">
                                                General Average
                                            </TableHead>
                                            <TableHead className="border-l pr-6 text-center whitespace-nowrap">
                                                Action
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {grade_rows.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    className="py-8 text-center text-sm text-muted-foreground"
                                                    colSpan={
                                                        grade_columns.length + 2
                                                    }
                                                >
                                                    No grades available for the
                                                    selected quarter.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            grade_rows.map((gradeRow) => (
                                                <TableRow
                                                    key={gradeRow.enrollment_id}
                                                >
                                                    <TableCell className="pl-6 font-medium whitespace-nowrap">
                                                        {gradeRow.student_name}
                                                    </TableCell>
                                                    {grade_columns.map(
                                                        (subjectColumn) => (
                                                            <TableCell
                                                                key={
                                                                    subjectColumn.id
                                                                }
                                                                className="border-l text-center"
                                                            >
                                                                {gradeRow
                                                                    .subject_grades[
                                                                    String(
                                                                        subjectColumn.id,
                                                                    )
                                                                ] ?? '-'}
                                                            </TableCell>
                                                        ),
                                                    )}
                                                    <TableCell className="border-l pr-6 text-right font-medium">
                                                        {gradeRow.general_average ??
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="border-l pr-6 text-center">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-8 gap-1.5"
                                                            onClick={() => window.location.assign(`/teacher/advisory-board/export-sf9?enrollment_id=${gradeRow.enrollment_id}`)}
                                                        >
                                                            <Download className="size-3.5" />
                                                            SF9
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
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
                            <CardContent className="overflow-x-auto p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Student
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Maka-Diyos
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Makatao
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Makakalikasan
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Makabansa
                                            </TableHead>
                                            <TableHead className="border-l pr-6">
                                                Adviser Remarks
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {conductRows.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    className="py-8 text-center text-sm text-muted-foreground"
                                                    colSpan={6}
                                                >
                                                    No enrolled students found
                                                    for the selected advisory
                                                    class.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            conductRows.map((studentRow) => (
                                                <TableRow
                                                    key={
                                                        studentRow.enrollment_id
                                                    }
                                                >
                                                    <TableCell className="pl-6 font-medium whitespace-nowrap">
                                                        {
                                                            studentRow.student_name
                                                        }
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        <BehaviorSelect
                                                            value={
                                                                studentRow
                                                                    .ratings
                                                                    .maka_diyos
                                                            }
                                                            onChange={(value) =>
                                                                updateRating(
                                                                    studentRow.enrollment_id,
                                                                    'maka_diyos',
                                                                    value,
                                                                )
                                                            }
                                                            disabled={
                                                                status ===
                                                                'locked'
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        <BehaviorSelect
                                                            value={
                                                                studentRow
                                                                    .ratings
                                                                    .makatao
                                                            }
                                                            onChange={(value) =>
                                                                updateRating(
                                                                    studentRow.enrollment_id,
                                                                    'makatao',
                                                                    value,
                                                                )
                                                            }
                                                            disabled={
                                                                status ===
                                                                'locked'
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        <BehaviorSelect
                                                            value={
                                                                studentRow
                                                                    .ratings
                                                                    .makakalikasan
                                                            }
                                                            onChange={(value) =>
                                                                updateRating(
                                                                    studentRow.enrollment_id,
                                                                    'makakalikasan',
                                                                    value,
                                                                )
                                                            }
                                                            disabled={
                                                                status ===
                                                                'locked'
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="border-l text-center">
                                                        <BehaviorSelect
                                                            value={
                                                                studentRow
                                                                    .ratings
                                                                    .makabansa
                                                            }
                                                            onChange={(value) =>
                                                                updateRating(
                                                                    studentRow.enrollment_id,
                                                                    'makabansa',
                                                                    value,
                                                                )
                                                            }
                                                            disabled={
                                                                status ===
                                                                'locked'
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="border-l pr-6">
                                                        <Input
                                                            value={
                                                                studentRow.remarks
                                                            }
                                                            onChange={(event) =>
                                                                updateRemarks(
                                                                    studentRow.enrollment_id,
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="min-w-64"
                                                            disabled={
                                                                status ===
                                                                'locked'
                                                            }
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
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
                        This action will lock all conduct ratings and adviser
                        remarks for the selected quarter.
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
                            onClick={() => submitConduct('locked')}
                            disabled={actionDisabled}
                        >
                            Confirm Lock
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={isReleaseDialogOpen}
                onOpenChange={setIsReleaseDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Release {selectedQuarterLabel} Grades
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        This will make verified {selectedQuarterLabel.toLowerCase()}{' '}
                        grades for the selected advisory class visible in
                        student and parent portals.
                    </p>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsReleaseDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={releaseGrades}
                            disabled={releaseDisabled}
                        >
                            Confirm {selectedQuarterLabel} Release
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function BehaviorSelect({
    value,
    onChange,
    disabled,
}: {
    value: RatingValue;
    onChange: (value: RatingValue) => void;
    disabled: boolean;
}) {
    return (
        <Select
            value={value ?? 'not-set'}
            onValueChange={(newValue) =>
                onChange(newValue === 'not-set' ? null : (newValue as RatingOption))
            }
            disabled={disabled}
        >
            <SelectTrigger className="mx-auto h-8 w-20">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="not-set">-</SelectItem>
                <SelectItem value="AO">AO</SelectItem>
                <SelectItem value="SO">SO</SelectItem>
                <SelectItem value="RO">RO</SelectItem>
                <SelectItem value="NO">NO</SelectItem>
            </SelectContent>
        </Select>
    );
}
