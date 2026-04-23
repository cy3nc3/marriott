import { Head, router, useForm, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, Eye, Pencil, Printer, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateOfBirthPicker } from '@/components/ui/date-picker';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import registrar from '@/routes/registrar';
import {
    assessment,
    regenerate_activation_codes,
} from '@/routes/registrar/enrollment';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

interface StudentRow {
    id: number;
    enrollment_id: number | null;
    lrn: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    gender: string | null;
    birthdate: string | null;
    guardian_name: string | null;
    guardian_contact_number: string | null;
    email: string | null;
    student_name: string;
    grade_section: string;
    enrollment_status: string | null;
    status: 'enrolled' | 'transferred_out' | 'dropped' | 'not_currently_enrolled';
}

interface SectionOption {
    id: number;
    label: string;
}

interface Props {
    students: {
        data: StudentRow[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    section_options: SectionOption[];
    ongoing_academic_year_id: number | null;
    filters: {
        search?: string;
    };
}

const normalizeMobileSubscriberDigits = (value: string): string => {
    const digits = value.replace(/\D/g, '');

    if (digits.startsWith('9')) {
        return digits.slice(0, 10);
    }

    if (digits.startsWith('09')) {
        return digits.slice(1, 11);
    }

    if (digits.startsWith('63')) {
        return digits.slice(2, 12);
    }

    return digits.slice(0, 10);
};

const formatMobileForDisplay = (subscriberDigits: string): string => {
    if (subscriberDigits.length === 10 && subscriberDigits.startsWith('9')) {
        return `+63${subscriberDigits}`;
    }

    return '-';
};

export default function StudentDirectory({
    students,
    section_options,
    ongoing_academic_year_id,
    filters,
}: Props) {
    const { ui, flash } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const openedAssessmentUrlRef = useRef<string | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [isExportDialogOpen, setIsExportDialogOpen] = useState(false);
    const [selectedStudent, setSelectedStudent] = useState<StudentRow | null>(null);
    const [isDetailEditMode, setIsDetailEditMode] = useState(false);
    const [selectedSectionIds, setSelectedSectionIds] = useState<number[]>(
        section_options.map((section) => section.id),
    );
    const [exportSelectionError, setExportSelectionError] = useState('');
    const detailForm = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        birthdate: '',
        guardian_name: '',
        guardian_contact_number: '',
        email: '',
    });

    const searchSuggestions = students.data.map((student) => ({
        id: student.id,
        label: student.student_name,
        value: student.student_name,
        description: `LRN: ${student.lrn}`,
        keywords: student.lrn,
    }));

    const toggleSection = (sectionId: number) => {
        setSelectedSectionIds((current) =>
            current.includes(sectionId)
                ? current.filter((id) => id !== sectionId)
                : [...current, sectionId],
        );
        setExportSelectionError('');
    };

    const toggleAllSections = () => {
        if (selectedSectionIds.length === section_options.length) {
            setSelectedSectionIds([]);
        } else {
            setSelectedSectionIds(section_options.map((section) => section.id));
        }
        setExportSelectionError('');
    };

    const exportSf1 = () => {
        if (!ongoing_academic_year_id) {
            return;
        }

        if (selectedSectionIds.length === 0) {
            setExportSelectionError('Select at least one section to export.');

            return;
        }

        const params = new URLSearchParams();
        params.set('academic_year_id', String(ongoing_academic_year_id));
        selectedSectionIds.forEach((sectionId) => {
            params.append('section_ids[]', String(sectionId));
        });

        setIsExportDialogOpen(false);
        window.location.assign(`/registrar/student-directory/export-sf1-reference?${params.toString()}`);
    };

    useEffect(() => {
        const assessmentPrintUrl =
            typeof flash.assessment_print_url === 'string' &&
            flash.assessment_print_url.length > 0
                ? flash.assessment_print_url
                : null;

        if (!assessmentPrintUrl) {
            return;
        }

        if (openedAssessmentUrlRef.current === assessmentPrintUrl) {
            return;
        }

        openedAssessmentUrlRef.current = assessmentPrintUrl;
        window.open(assessmentPrintUrl, '_blank', 'noopener,noreferrer');
    }, [flash.assessment_print_url]);

    const applyFilters = (search?: string) => {
        router.get(
            registrar.student_directory.url({
                query: {
                    search: search || undefined,
                    page: undefined,
                },
            }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const openAssessmentForm = (enrollmentId: number | null) => {
        if (!enrollmentId) {
            return;
        }

        window.open(
            assessment(enrollmentId).url,
            '_blank',
            'noopener,noreferrer',
        );
    };

    const regenerateActivationCodes = (enrollmentId: number | null) => {
        if (!enrollmentId) {
            return;
        }

        router.post(
            regenerate_activation_codes(enrollmentId).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const openStudentDetails = (student: StudentRow) => {
        setSelectedStudent(student);
        setIsDetailEditMode(false);
        detailForm.clearErrors();
        detailForm.setData({
            first_name: student.first_name || '',
            middle_name: student.middle_name || '',
            last_name: student.last_name || '',
            gender: student.gender || '',
            birthdate: student.birthdate || '',
            guardian_name: student.guardian_name || '',
            guardian_contact_number: normalizeMobileSubscriberDigits(
                student.guardian_contact_number || '',
            ),
            email: student.email || '',
        });
    };

    const closeStudentDetails = () => {
        setSelectedStudent(null);
        setIsDetailEditMode(false);
        detailForm.clearErrors();
    };

    const submitStudentDetailsUpdate = () => {
        if (!selectedStudent) {
            return;
        }

        detailForm.patch(`/registrar/student-directory/${selectedStudent.id}`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setSelectedStudent({
                    ...selectedStudent,
                    first_name: detailForm.data.first_name,
                    middle_name: detailForm.data.middle_name || null,
                    last_name: detailForm.data.last_name,
                    gender: detailForm.data.gender || null,
                    birthdate: detailForm.data.birthdate || null,
                    guardian_name: detailForm.data.guardian_name || null,
                    guardian_contact_number: formatMobileForDisplay(
                        detailForm.data.guardian_contact_number,
                    ),
                    email: detailForm.data.email || null,
                    student_name: [
                        detailForm.data.first_name,
                        detailForm.data.last_name,
                    ]
                        .map((value) => value.trim())
                        .filter((value) => value.length > 0)
                        .join(' '),
                });
                setIsDetailEditMode(false);
            },
        });
    };

    const statusLabel = (status: StudentRow['status']): string => {
        return ({
            enrolled: 'Enrolled',
            transferred_out: 'Transferred Out',
            dropped: 'Dropped Out',
            not_currently_enrolled: 'Not Currently Enrolled',
        })[status];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Directory" />

            <div className="flex flex-col gap-4">
                <Card>
                    <CardHeader className="flex flex-col gap-1 border-b sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div className="space-y-1">
                            <CardTitle>Student Directory</CardTitle>
                            <div className="flex flex-wrap items-center gap-2">
                                <SearchAutocompleteInput
                                    wrapperClassName="w-full sm:w-[18rem]"
                                    placeholder="Search by LRN or name..."
                                    value={searchQuery}
                                    onValueChange={(value) => {
                                        setSearchQuery(value);
                                        applyFilters(value);
                                    }}
                                    suggestions={searchSuggestions}
                                    showSuggestions={false}
                                />
                            </div>
                        </div>

                        <div className="flex items-start">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsExportDialogOpen(true)}
                                disabled={!ongoing_academic_year_id || section_options.length === 0}
                            >
                                <Download className="size-4" />
                                Export SF1
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        {isHandheld ? (
                            <div className="space-y-2.5 p-3">
                                {students.data.length === 0 ? (
                                    <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                        No students found.
                                    </div>
                                ) : (
                                    students.data.map((student) => (
                                        <div
                                            key={student.id}
                                            className="space-y-1 rounded-md border p-2.5"
                                        >
                                            <p className="text-sm font-semibold">
                                                {student.student_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                LRN: {student.lrn}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {student.grade_section}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {statusLabel(student.status)}
                                            </p>
                                            <div className="flex items-center gap-2 pt-1">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openStudentDetails(
                                                            student,
                                                        )
                                                    }
                                                >
                                                    <Eye className="size-4" />
                                                    View Details
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={
                                                        !student.enrollment_id
                                                    }
                                                    onClick={() =>
                                                        openAssessmentForm(
                                                            student.enrollment_id,
                                                        )
                                                    }
                                                >
                                                    <Printer className="size-4" />
                                                    Print
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={
                                                        !student.enrollment_id
                                                    }
                                                    onClick={() =>
                                                        regenerateActivationCodes(
                                                            student.enrollment_id,
                                                        )
                                                    }
                                                >
                                                    <RefreshCw className="size-4" />
                                                    Regenerate
                                                </Button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            LRN
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Student
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Grade and Section
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Status
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="h-24 text-center text-sm text-muted-foreground"
                                            >
                                                No students found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        students.data.map((student) => (
                                            <TableRow key={student.id}>
                                                <TableCell className="pl-6 font-medium">
                                                    {student.lrn}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {student.student_name}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {student.grade_section}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {statusLabel(student.status)}
                                                </TableCell>
                                                <TableCell className="border-l pr-6">
                                                    <div className="flex justify-end gap-2">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8"
                                                                    onClick={() =>
                                                                        openStudentDetails(
                                                                            student,
                                                                        )
                                                                    }
                                                                >
                                                                    <Eye className="size-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                View student details
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8"
                                                                    disabled={
                                                                        !student.enrollment_id
                                                                    }
                                                                    onClick={() =>
                                                                        openAssessmentForm(
                                                                            student.enrollment_id,
                                                                        )
                                                                    }
                                                                >
                                                                    <Printer className="size-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Print assessment form
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8"
                                                                    disabled={
                                                                        !student.enrollment_id
                                                                    }
                                                                    onClick={() =>
                                                                        regenerateActivationCodes(
                                                                            student.enrollment_id,
                                                                        )
                                                                    }
                                                                >
                                                                    <RefreshCw className="size-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Regenerate activation codes
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    <div className="flex items-center justify-between border-t p-4">
                        <p className="text-sm text-muted-foreground">
                            Showing {students.from ?? 0}-{students.to ?? 0} of{' '}
                            {students.total} entries
                        </p>
                        {students.links.length > 3 && (
                            <div className="flex items-center gap-2">
                                {students.links.map((link, index) => {
                                    let label = link.label;
                                    if (label.includes('Previous')) {
                                        label = 'Previous';
                                    } else if (label.includes('Next')) {
                                        label = 'Next';
                                    } else {
                                        label = label
                                            .replace(/&[^;]+;/g, '')
                                            .trim();
                                    }

                                    return (
                                        <Button
                                            key={`${link.label}-${index}`}
                                            variant="outline"
                                            size="sm"
                                            disabled={!link.url || link.active}
                                            onClick={() => {
                                                if (link.url) {
                                                    router.get(
                                                        link.url,
                                                        {},
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            {label}
                                        </Button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </Card>
            </div>

            <Dialog open={!!selectedStudent} onOpenChange={(open) => !open && closeStudentDetails()}>
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>Student Details</DialogTitle>
                    </DialogHeader>
                    {selectedStudent && (
                        <div className="space-y-4 py-2">
                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label>First Name</Label>
                                    {isDetailEditMode ? (
                                        <Input
                                            value={detailForm.data.first_name}
                                            onChange={(event) => detailForm.setData('first_name', event.target.value)}
                                        />
                                    ) : (
                                        <p className="text-sm">{selectedStudent.first_name || '-'}</p>
                                    )}
                                    {detailForm.errors.first_name && (
                                        <p className="text-sm text-destructive">{detailForm.errors.first_name}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Middle Name</Label>
                                    {isDetailEditMode ? (
                                        <Input
                                            value={detailForm.data.middle_name}
                                            onChange={(event) => detailForm.setData('middle_name', event.target.value)}
                                        />
                                    ) : (
                                        <p className="text-sm">{selectedStudent.middle_name || '-'}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Last Name</Label>
                                    {isDetailEditMode ? (
                                        <Input
                                            value={detailForm.data.last_name}
                                            onChange={(event) => detailForm.setData('last_name', event.target.value)}
                                        />
                                    ) : (
                                        <p className="text-sm">{selectedStudent.last_name || '-'}</p>
                                    )}
                                    {detailForm.errors.last_name && (
                                        <p className="text-sm text-destructive">{detailForm.errors.last_name}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Gender</Label>
                                    {isDetailEditMode ? (
                                        <Select
                                            value={detailForm.data.gender || 'none'}
                                            onValueChange={(value) => detailForm.setData('gender', value === 'none' ? '' : value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select gender" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Select gender</SelectItem>
                                                <SelectItem value="Male">Male</SelectItem>
                                                <SelectItem value="Female">Female</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <p className="text-sm">{selectedStudent.gender || '-'}</p>
                                    )}
                                    {detailForm.errors.gender && (
                                        <p className="text-sm text-destructive">{detailForm.errors.gender}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Birthdate</Label>
                                    {isDetailEditMode ? (
                                        <DateOfBirthPicker
                                            date={detailForm.data.birthdate ? new Date(detailForm.data.birthdate) : undefined}
                                            setDate={(date) => detailForm.setData('birthdate', date ? format(date, 'yyyy-MM-dd') : '')}
                                            className="w-full"
                                            placeholder="Select date"
                                        />
                                    ) : (
                                        <p className="text-sm">{selectedStudent.birthdate || '-'}</p>
                                    )}
                                    {detailForm.errors.birthdate && (
                                        <p className="text-sm text-destructive">{detailForm.errors.birthdate}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Guardian Name</Label>
                                {isDetailEditMode ? (
                                    <Input
                                        value={detailForm.data.guardian_name}
                                        onChange={(event) => detailForm.setData('guardian_name', event.target.value)}
                                    />
                                ) : (
                                    <p className="text-sm">{selectedStudent.guardian_name || '-'}</p>
                                )}
                                {detailForm.errors.guardian_name && (
                                    <p className="text-sm text-destructive">{detailForm.errors.guardian_name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Guardian Contact Number</Label>
                                {isDetailEditMode ? (
                                    <div className="flex w-full min-w-0">
                                        <span className="inline-flex items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                            +63
                                        </span>
                                        <Input
                                            className="rounded-l-none"
                                            inputMode="numeric"
                                            pattern="[0-9]*"
                                            maxLength={10}
                                            placeholder="9XXXXXXXXX"
                                            value={detailForm.data.guardian_contact_number}
                                            onChange={(event) =>
                                                detailForm.setData(
                                                    'guardian_contact_number',
                                                    normalizeMobileSubscriberDigits(event.target.value),
                                                )
                                            }
                                        />
                                    </div>
                                ) : (
                                    <p className="text-sm">
                                        {formatMobileForDisplay(
                                            normalizeMobileSubscriberDigits(selectedStudent.guardian_contact_number || ''),
                                        )}
                                    </p>
                                )}
                                {detailForm.errors.guardian_contact_number && (
                                    <p className="text-sm text-destructive">
                                        {detailForm.errors.guardian_contact_number}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Contact Email</Label>
                                {isDetailEditMode ? (
                                    <Input
                                        type="email"
                                        value={detailForm.data.email}
                                        onChange={(event) => detailForm.setData('email', event.target.value)}
                                    />
                                ) : (
                                    <p className="text-sm">{selectedStudent.email || '-'}</p>
                                )}
                                {detailForm.errors.email && (
                                    <p className="text-sm text-destructive">{detailForm.errors.email}</p>
                                )}
                            </div>
                        </div>
                    )}
                    <DialogFooter>
                        {isDetailEditMode ? (
                            <>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsDetailEditMode(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="button"
                                    onClick={submitStudentDetailsUpdate}
                                    disabled={detailForm.processing}
                                >
                                    Save Changes
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button type="button" variant="outline" onClick={closeStudentDetails}>
                                    Close
                                </Button>
                                <Button type="button" onClick={() => setIsDetailEditMode(true)}>
                                    <Pencil className="size-4" />
                                    Edit
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={isExportDialogOpen}
                onOpenChange={(open) => {
                    setIsExportDialogOpen(open);
                    if (!open) {
                        setExportSelectionError('');
                    }
                }}
            >
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>Select Sections for SF1 Export</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 py-2">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Choose one or more sections to include.
                            </p>
                            <Button type="button" variant="ghost" size="sm" onClick={toggleAllSections}>
                                {selectedSectionIds.length === section_options.length ? 'Clear All' : 'Select All'}
                            </Button>
                        </div>
                        <div className="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3">
                            {section_options.map((section) => (
                                <label
                                    key={section.id}
                                    className="flex cursor-pointer items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        className="size-4"
                                        checked={selectedSectionIds.includes(section.id)}
                                        onChange={() => toggleSection(section.id)}
                                    />
                                    <span>{section.label}</span>
                                </label>
                            ))}
                        </div>
                        {exportSelectionError !== '' ? (
                            <p className="text-sm text-destructive">{exportSelectionError}</p>
                        ) : null}
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setIsExportDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="button" onClick={exportSf1}>
                            Export
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
