import { Head } from '@inertiajs/react';
import { type ChangeEvent, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Import Batches',
        href: '/registrar/data-import/batches',
    },
];

type BatchAction = 'create' | 'update' | 'skip' | 'blocked';
type RowClassification = 'payment' | 'due' | 'mixed' | 'unresolved';

interface MappingRow {
    id: string;
    sourceColumn: string;
    targetField: string;
    required: boolean;
}

interface CorrectionRow {
    id: number;
    rowIndex: number;
    lrn: string;
    learnerName: string;
    gradeLevel: string;
    sectionName: string;
    classification: RowClassification;
    action: BatchAction;
    hasError: boolean;
    notes: string;
}

const initialMappings: MappingRow[] = [
    {
        id: 'lrn',
        sourceColumn: 'LRN',
        targetField: 'lrn',
        required: true,
    },
    {
        id: 'last_name',
        sourceColumn: 'Last Name',
        targetField: 'last_name',
        required: true,
    },
    {
        id: 'first_name',
        sourceColumn: 'First Name',
        targetField: 'first_name',
        required: true,
    },
    {
        id: 'grade_level',
        sourceColumn: 'Grade Level',
        targetField: 'grade_level',
        required: true,
    },
    {
        id: 'section',
        sourceColumn: 'Section',
        targetField: 'section',
        required: false,
    },
];

const initialRows: CorrectionRow[] = [
    {
        id: 1,
        rowIndex: 2,
        lrn: '123456789012',
        learnerName: 'Mia Santos',
        gradeLevel: 'Grade 8',
        sectionName: 'Rizal',
        classification: 'mixed',
        action: 'update',
        hasError: false,
        notes: '',
    },
    {
        id: 2,
        rowIndex: 3,
        lrn: '000000000000',
        learnerName: 'Unknown Learner',
        gradeLevel: 'Grade 7',
        sectionName: 'Mabini',
        classification: 'unresolved',
        action: 'blocked',
        hasError: true,
        notes: 'Duplicate LRN conflict with existing student.',
    },
    {
        id: 3,
        rowIndex: 4,
        lrn: '987654321000',
        learnerName: 'Jules Reyes',
        gradeLevel: 'Grade 9',
        sectionName: 'Bonifacio',
        classification: 'payment',
        action: 'create',
        hasError: false,
        notes: '',
    },
];

export function RegistrarImportBatchesPanel() {
    const [mappings, setMappings] = useState<MappingRow[]>(initialMappings);
    const [rows, setRows] = useState<CorrectionRow[]>(initialRows);

    const missingRequiredMappings = useMemo(() => {
        return mappings.filter(
            (mapping) => mapping.required && mapping.targetField.trim() === '',
        ).length;
    }, [mappings]);

    const blockingRows = useMemo(() => {
        return rows.filter(
            (row) =>
                row.hasError ||
                row.action === 'blocked' ||
                row.classification === 'unresolved',
        ).length;
    }, [rows]);

    const validRows = useMemo(() => {
        return rows.filter(
            (row) =>
                !row.hasError &&
                row.action !== 'blocked' &&
                row.classification !== 'unresolved',
        ).length;
    }, [rows]);

    const beforeStudentTotal = rows.length;
    const afterStudentTotal = rows.filter(
        (row) => row.action !== 'skip',
    ).length;
    const beforeSectionTotal = new Set(rows.map((row) => row.sectionName)).size;
    const afterSectionTotal = new Set(
        rows
            .filter((row) => row.action !== 'skip' && row.action !== 'blocked')
            .map((row) => row.sectionName),
    ).size;

    const hasBlockers = missingRequiredMappings > 0 || blockingRows > 0;

    const handleMappingChange = (
        mappingId: string,
        event: ChangeEvent<HTMLInputElement>,
    ) => {
        const nextTargetField = event.target.value;

        setMappings((currentMappings) =>
            currentMappings.map((mapping) => {
                if (mapping.id !== mappingId) {
                    return mapping;
                }

                return {
                    ...mapping,
                    targetField: nextTargetField,
                };
            }),
        );
    };

    const handleRowChange = <K extends keyof CorrectionRow>(
        rowId: number,
        key: K,
        value: CorrectionRow[K],
    ) => {
        setRows((currentRows) =>
            currentRows.map((row) => {
                if (row.id !== rowId) {
                    return row;
                }

                return {
                    ...row,
                    [key]: value,
                };
            }),
        );
    };

    return (
        <div className="flex flex-col gap-6">
            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Column Mapping</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6">
                                    Source CSV Column
                                </TableHead>
                                <TableHead className="border-l">
                                    Target Field
                                </TableHead>
                                <TableHead className="border-l pr-6">
                                    Required
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {mappings.map((mapping) => (
                                <TableRow key={mapping.id}>
                                    <TableCell className="pl-6 font-medium">
                                        {mapping.sourceColumn}
                                    </TableCell>
                                    <TableCell className="border-l">
                                        <Input
                                            value={mapping.targetField}
                                            placeholder="Set target field"
                                            onChange={(event) =>
                                                handleMappingChange(
                                                    mapping.id,
                                                    event,
                                                )
                                            }
                                        />
                                    </TableCell>
                                    <TableCell className="border-l pr-6">
                                        <Badge
                                            variant={
                                                mapping.required
                                                    ? 'destructive'
                                                    : 'outline'
                                            }
                                        >
                                            {mapping.required
                                                ? 'Required'
                                                : 'Optional'}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Validation Summary</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 pt-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-md border p-4">
                        <p className="text-xs text-muted-foreground">
                            Rows in Batch
                        </p>
                        <p className="text-2xl font-semibold">{rows.length}</p>
                    </div>
                    <div className="rounded-md border p-4">
                        <p className="text-xs text-muted-foreground">
                            Valid Rows
                        </p>
                        <p className="text-2xl font-semibold">{validRows}</p>
                    </div>
                    <div className="rounded-md border border-destructive/40 p-4">
                        <p className="text-xs text-muted-foreground">
                            Blocking Rows
                        </p>
                        <p className="text-2xl font-semibold text-destructive">
                            {blockingRows}
                        </p>
                    </div>
                    <div className="rounded-md border border-destructive/40 p-4">
                        <p className="text-xs text-muted-foreground">
                            Missing Required Mappings
                        </p>
                        <p className="text-2xl font-semibold text-destructive">
                            {missingRequiredMappings}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Before vs After Totals</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6">Metric</TableHead>
                                <TableHead className="border-l text-right">
                                    Before
                                </TableHead>
                                <TableHead className="border-l text-right">
                                    After
                                </TableHead>
                                <TableHead className="border-l pr-6 text-right">
                                    Delta
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell className="pl-6 font-medium">
                                    Students
                                </TableCell>
                                <TableCell className="border-l text-right">
                                    {beforeStudentTotal}
                                </TableCell>
                                <TableCell className="border-l text-right">
                                    {afterStudentTotal}
                                </TableCell>
                                <TableCell className="border-l pr-6 text-right">
                                    {afterStudentTotal - beforeStudentTotal}
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell className="pl-6 font-medium">
                                    Sections
                                </TableCell>
                                <TableCell className="border-l text-right">
                                    {beforeSectionTotal}
                                </TableCell>
                                <TableCell className="border-l text-right">
                                    {afterSectionTotal}
                                </TableCell>
                                <TableCell className="border-l pr-6 text-right">
                                    {afterSectionTotal - beforeSectionTotal}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Editable Row Correction Grid</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Row</TableHead>
                                    <TableHead className="border-l">
                                        LRN
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Learner
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Grade
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Section
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Classification
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Action
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Status
                                    </TableHead>
                                    <TableHead className="border-l pr-6">
                                        Notes
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="pl-6">
                                            {row.rowIndex}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Input
                                                value={row.lrn}
                                                onChange={(event) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'lrn',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Input
                                                value={row.learnerName}
                                                onChange={(event) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'learnerName',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Input
                                                value={row.gradeLevel}
                                                onChange={(event) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'gradeLevel',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Input
                                                value={row.sectionName}
                                                onChange={(event) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'sectionName',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Select
                                                value={row.classification}
                                                onValueChange={(value) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'classification',
                                                        value as RowClassification,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="payment">
                                                        Payment
                                                    </SelectItem>
                                                    <SelectItem value="due">
                                                        Due
                                                    </SelectItem>
                                                    <SelectItem value="mixed">
                                                        Mixed
                                                    </SelectItem>
                                                    <SelectItem value="unresolved">
                                                        Unresolved
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Select
                                                value={row.action}
                                                onValueChange={(value) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'action',
                                                        value as BatchAction,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="create">
                                                        Create
                                                    </SelectItem>
                                                    <SelectItem value="update">
                                                        Update
                                                    </SelectItem>
                                                    <SelectItem value="skip">
                                                        Skip
                                                    </SelectItem>
                                                    <SelectItem value="blocked">
                                                        Blocked
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Badge
                                                variant={
                                                    row.hasError ||
                                                    row.action === 'blocked' ||
                                                    row.classification ===
                                                        'unresolved'
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                            >
                                                {row.hasError
                                                    ? 'Has error'
                                                    : 'Ready'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="border-l pr-6">
                                            <Input
                                                value={row.notes}
                                                placeholder="Correction notes"
                                                onChange={(event) =>
                                                    handleRowChange(
                                                        row.id,
                                                        'notes',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Apply Batch</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-muted-foreground">
                        Resolve blockers in mapping and rows before applying
                        this batch.
                    </p>
                    <Button disabled={hasBlockers}>
                        Confirm & Apply Batch
                    </Button>
                </CardContent>
            </Card>
        </div>
    );
}

export default function RegistrarDataImportBatchesPage() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Import Batches" />

            <RegistrarImportBatchesPanel />
        </AppLayout>
    );
}
