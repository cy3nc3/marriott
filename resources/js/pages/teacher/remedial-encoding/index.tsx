import { Head, router } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
        title: 'Remedial Encoding',
        href: '/teacher/remedial-encoding',
    },
];

type AcademicYearOption = {
    id: number;
    name: string;
    status: string;
};

type Row = {
    case_subject_id: number;
    student_name: string;
    lrn: string;
    subject_name: string;
    school_year: string;
    final_rating: number | null;
    remedial_class_mark: number | null;
    recomputed_final_grade: number | null;
    case_status: string;
    can_encode: boolean;
    status: string;
};

interface Props {
    academic_years: AcademicYearOption[];
    selected_academic_year_id: number | null;
    rows: Row[];
}

export default function TeacherRemedialEncoding({
    rows,
}: Props) {
    const [scoreDrafts, setScoreDrafts] = useState<Record<number, string>>(() => {
        const initial: Record<number, string> = {};
        rows.forEach((row) => {
            initial[row.case_subject_id] =
                row.remedial_class_mark !== null
                    ? String(row.remedial_class_mark)
                    : '';
        });

        return initial;
    });
    const [savingRowId, setSavingRowId] = useState<number | null>(null);

    const groupedRows = useMemo(() => {
        const grouped = new Map<string, Row[]>();

        rows.forEach((row) => {
            const key = `${row.student_name}::${row.lrn}`;
            if (!grouped.has(key)) {
                grouped.set(key, []);
            }
            grouped.get(key)?.push(row);
        });

        return Array.from(grouped.entries()).map(([key, groupRows]) => {
            const [studentName, lrn] = key.split('::');

            return {
                student_name: studentName,
                lrn,
                rows: groupRows,
            };
        });
    }, [rows]);

    const submitRow = (row: Row) => {
        const rawValue = scoreDrafts[row.case_subject_id] ?? '';
        if (!row.can_encode || rawValue.trim() === '') {
            return;
        }

        setSavingRowId(row.case_subject_id);
        router.post(
            teacher.remedial_encoding.store.url(),
            {
                case_subject_id: row.case_subject_id,
                remedial_class_mark: Number(rawValue),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSavingRowId(null);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Remedial Encoding" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle>Assigned Remedial Subjects</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-5 p-4">
                        {groupedRows.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No assigned remedial subjects for this school year.
                            </p>
                        ) : (
                            groupedRows.map((group) => (
                                <div key={`${group.student_name}-${group.lrn}`} className="rounded-lg border">
                                    <div className="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/40 px-4 py-3">
                                        <div>
                                            <p className="text-sm font-semibold">{group.student_name}</p>
                                            <p className="text-xs text-muted-foreground">{`LRN: ${group.lrn}`}</p>
                                        </div>
                                        <Badge variant="outline">{group.rows[0]?.school_year ?? 'N/A'}</Badge>
                                    </div>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="pl-4">Subject</TableHead>
                                                <TableHead className="text-center">Final Rating</TableHead>
                                                <TableHead className="text-center">Remedial Grade</TableHead>
                                                <TableHead className="text-center">Recomputed</TableHead>
                                                <TableHead className="text-center">Payment</TableHead>
                                                <TableHead className="pr-4 text-right">Action</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {group.rows.map((row) => (
                                                <TableRow key={row.case_subject_id}>
                                                    <TableCell className="pl-4 font-medium">
                                                        {row.subject_name}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {row.final_rating !== null ? row.final_rating : '--'}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Input
                                                            value={scoreDrafts[row.case_subject_id] ?? ''}
                                                            onChange={(event) =>
                                                                setScoreDrafts((current) => ({
                                                                    ...current,
                                                                    [row.case_subject_id]: event.target.value,
                                                                }))
                                                            }
                                                            className="mx-auto w-24"
                                                            disabled={!row.can_encode}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {row.recomputed_final_grade ?? '--'}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge variant="outline">
                                                            {row.case_status === 'paid'
                                                                ? 'Paid'
                                                                : row.case_status.replaceAll('_', ' ')}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="pr-4 text-right">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            disabled={
                                                                !row.can_encode ||
                                                                savingRowId === row.case_subject_id ||
                                                                (scoreDrafts[row.case_subject_id] ?? '').trim() === ''
                                                            }
                                                            onClick={() => submitRow(row)}
                                                        >
                                                            <Save className="size-4" />
                                                            Save
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
