import { Head, useForm, usePage } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { RegistrarImportBatchesPanel } from './batches';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Import',
        href: '/registrar/data-import',
    },
];

interface Props {
    imports: {
        id: number;
        imported_at: string | null;
        file_name: string;
        processed_rows: number;
        imported_rows: number;
        created_records: number;
        updated_records: number;
        created_students: number;
        created_academic_years: number;
        created_grade_levels: number;
        skipped_rows: number;
        performed_by: string;
    }[];
}

export default function DataImport({ imports }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [selectedImport, setSelectedImport] = useState<
        Props['imports'][number] | null
    >(null);
    const importForm = useForm<{
        import_file: File | null;
    }>({
        import_file: null,
    });
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const [showBatchWorkspace, setShowBatchWorkspace] = useState(false);

    const previewData = (flash as Record<string, unknown>).import_preview as {
        file_name: string;
        detected_format: string;
        mode?: 'workbook' | 'single_sheet';
        headers?: string[];
        missing_required_headers?: string[];
        processed_rows?: number;
        ready_rows?: number;
        invalid_rows?: number;
        missing_sheets?: string[];
        invalid_sheets?: string[];
        sheets?: {
            sheet: string;
            processed_rows: number;
            headers: string[];
            missing_required_headers: string[];
        }[];
    } | undefined;

    const submitPreview = () => {
        importForm.post('/registrar/data-import/permanent-records/preview', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const submitImport = (event?: FormEvent<HTMLFormElement>) => {
        if (event) event.preventDefault();

        importForm.post('/registrar/data-import/permanent-records', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                importForm.reset('import_file');
                setIsConfirmOpen(false);
            },
        });
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Data Import" />

                <div className="flex flex-col gap-6">
                    <Card className="gap-2">
                        <CardHeader className="border-b">
                            <CardTitle>Data Import</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="mb-4 rounded-md border bg-muted/30 p-3 text-xs text-muted-foreground">
                                <p className="font-medium text-foreground">
                                    Workbook sheets required
                                </p>
                                <p>`students`</p>
                                <p className="mt-1">
                                    Use the enrollment export workbook for enrollment history and discount tagging import.
                                </p>
                            </div>
                            <form
                                className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end"
                                onSubmit={submitImport}
                            >
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="permanent-record-import-file"
                                        className="sr-only"
                                    >
                                        Import Workbook File
                                    </Label>
                                    <Input
                                        id="permanent-record-import-file"
                                        type="file"
                                        accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                        onChange={(event) =>
                                            importForm.setData(
                                                'import_file',
                                                event.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                    {importForm.errors.import_file ? (
                                        <p className="text-xs text-destructive">
                                            {importForm.errors.import_file}
                                        </p>
                                    ) : null}
                                </div>
                                <div className="flex items-end justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            window.location.href =
                                                '/registrar/data-import/permanent-records/template';
                                        }}
                                    >
                                        Download Template
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            setShowBatchWorkspace(
                                                (current) => !current,
                                            )
                                        }
                                    >
                                        {showBatchWorkspace
                                            ? 'Hide Batch Workspace'
                                            : 'Open Batch Workspace'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={submitPreview}
                                        disabled={
                                            importForm.processing ||
                                            !importForm.data.import_file
                                        }
                                    >
                                        Preview File
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => setIsConfirmOpen(true)}
                                        disabled={
                                            importForm.processing ||
                                            !importForm.data.import_file
                                        }
                                    >
                                        <Upload className="size-4" />
                                        Import File
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {previewData ? (
                        <Card>
                            <CardHeader className="border-b">
                                <CardTitle>Import Preview</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 pt-4 text-sm">
                                <div className="grid gap-2 sm:grid-cols-2">
                                <p className="text-muted-foreground">File</p>
                                <p>{previewData.file_name}</p>
                                <p className="text-muted-foreground">Detected Format</p>
                                <p>{previewData.detected_format}</p>
                                </div>

                                {previewData.mode === 'workbook' ? (
                                    <div className="space-y-3">
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            <p className="text-muted-foreground">Missing Required Sheets</p>
                                            <p>
                                                {(previewData.missing_sheets?.length ?? 0) > 0
                                                    ? previewData.missing_sheets?.join(', ')
                                                    : 'None'}
                                            </p>
                                            <p className="text-muted-foreground">Sheets With Invalid Headers</p>
                                            <p>
                                                {(previewData.invalid_sheets?.length ?? 0) > 0
                                                    ? previewData.invalid_sheets?.join(', ')
                                                    : 'None'}
                                            </p>
                                        </div>

                                        <div className="overflow-x-auto rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Sheet</TableHead>
                                                        <TableHead>Rows</TableHead>
                                                        <TableHead>Missing Required Headers</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {(previewData.sheets ?? []).map((sheet) => (
                                                        <TableRow key={sheet.sheet}>
                                                            <TableCell className="font-medium">{sheet.sheet}</TableCell>
                                                            <TableCell>{sheet.processed_rows}</TableCell>
                                                            <TableCell>
                                                                {sheet.missing_required_headers.length > 0
                                                                    ? sheet.missing_required_headers.join(', ')
                                                                    : 'None'}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        <p className="text-muted-foreground">Processed Rows</p>
                                        <p>{previewData.processed_rows ?? 0}</p>
                                        <p className="text-muted-foreground">Ready Rows</p>
                                        <p>{previewData.ready_rows ?? 0}</p>
                                        <p className="text-muted-foreground">Invalid Rows</p>
                                        <p>{previewData.invalid_rows ?? 0}</p>
                                        <p className="text-muted-foreground">Missing Required Headers</p>
                                        <p>
                                            {(previewData.missing_required_headers?.length ?? 0) > 0
                                                ? previewData.missing_required_headers?.join(', ')
                                                : 'None'}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ) : null}

                    {showBatchWorkspace ? (
                        <RegistrarImportBatchesPanel />
                    ) : null}

                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Import History</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Imported At
                                            </TableHead>
                                            <TableHead className="border-l">
                                                File
                                            </TableHead>
                                            <TableHead className="border-l">
                                                Result
                                            </TableHead>
                                            <TableHead className="border-l">
                                                Records
                                            </TableHead>
                                            <TableHead className="border-l">
                                                Skipped
                                            </TableHead>
                                            <TableHead className="border-l">
                                                By
                                            </TableHead>
                                            <TableHead className="border-l pr-6 text-right">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {imports.length > 0 ? (
                                            imports.map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell className="pl-6">
                                                        {item.imported_at ??
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell
                                                        className="max-w-48 truncate border-l"
                                                        title={item.file_name}
                                                    >
                                                        {item.file_name}
                                                    </TableCell>
                                                    <TableCell className="border-l">
                                                        {item.imported_rows}/
                                                        {item.processed_rows}
                                                    </TableCell>
                                                    <TableCell className="border-l">
                                                        +{item.created_records}{' '}
                                                        / ~
                                                        {item.updated_records}
                                                    </TableCell>
                                                    <TableCell className="border-l">
                                                        {item.skipped_rows}
                                                    </TableCell>
                                                    <TableCell className="border-l">
                                                        {item.performed_by}
                                                    </TableCell>
                                                    <TableCell className="border-l pr-6 text-right">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setSelectedImport(
                                                                    item,
                                                                )
                                                            }
                                                        >
                                                            Details
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={7}
                                                    className="h-24 text-center text-muted-foreground"
                                                >
                                                    No import history yet.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Dialog
                    open={selectedImport !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setSelectedImport(null);
                        }
                    }}
                >
                    <DialogContent className="sm:max-w-xl">
                        <DialogHeader className="border-b">
                            <DialogTitle>Import Details</DialogTitle>
                        </DialogHeader>

                        {selectedImport ? (
                            <div className="grid gap-2 pt-4 text-sm sm:grid-cols-2">
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Imported At
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.imported_at ?? '-'}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        File
                                    </p>
                                    <p className="font-medium break-all">
                                        {selectedImport.file_name}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Processed Rows
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.processed_rows}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Imported Rows
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.imported_rows}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Created Records
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.created_records}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Updated Records
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.updated_records}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Created Students
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.created_students}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Created School Years
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.created_academic_years}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Created Grade Levels
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.created_grade_levels}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3">
                                    <p className="text-xs text-muted-foreground">
                                        Skipped Rows
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.skipped_rows}
                                    </p>
                                </div>
                                <div className="rounded-md border p-3 sm:col-span-2">
                                    <p className="text-xs text-muted-foreground">
                                        Imported By
                                    </p>
                                    <p className="font-medium">
                                        {selectedImport.performed_by}
                                    </p>
                                </div>
                            </div>
                        ) : null}

                        <DialogFooter className="border-t pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setSelectedImport(null)}
                            >
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </AppLayout>

            <ActionConfirmDialog
                open={isConfirmOpen}
                onOpenChange={setIsConfirmOpen}
                title="Import Permanent Records"
                description="Are you sure you want to import this file? This will create new student records and update existing ones in the system. Please ensure the data layout is correct before proceeding."
                confirmLabel="Confirm Import"
                loading={importForm.processing}
                onConfirm={() => submitImport()}
            />
        </>
    );
}
