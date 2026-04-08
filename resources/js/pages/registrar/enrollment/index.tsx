import { Head, router, useForm } from '@inertiajs/react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import { format } from 'date-fns';
import { Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { destroy, store, update } from '@/routes/registrar/enrollment';
import registrar from '@/routes/registrar';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Enrollment',
        href: '/registrar/enrollment',
    },
];

interface EnrollmentRow {
    id: number;
    lrn: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    gender: string | null;
    birthdate: string | null;
    guardian_name: string;
    guardian_contact_number: string;
    payment_term: string;
    downpayment: number;
    status: string;
    grade_level_id: number | null;
    section_id: number | null;
    section_label: string | null;
}

interface GradeLevelOption {
    id: number;
    name: string;
}

interface SectionOption {
    id: number;
    grade_level_id: number;
    label: string;
}

interface Props {
    enrollments: EnrollmentRow[];
    grade_level_options: GradeLevelOption[];
    section_options: SectionOption[];
    school_year_options: {
        id: number;
        name: string;
        status: string;
    }[];
    selected_school_year_id: number | null;
    selected_school_year_status: string | null;
    summary: {
        for_cashier_payment: number;
    };
    filters: {
        search?: string;
        academic_year_id?: number;
    };
}

export default function Enrollment({
    enrollments,
    grade_level_options,
    section_options,
    school_year_options,
    selected_school_year_id,
    selected_school_year_status,
    summary,
    filters,
}: Props) {
    const [editingItem, setEditingItem] = useState<EnrollmentRow | null>(null);
    const [createStep, setCreateStep] = useState<1 | 2 | 3 | 4>(1);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedSchoolYearId, setSelectedSchoolYearId] = useState(
        selected_school_year_id ? String(selected_school_year_id) : '',
    );
    const [isSaveConfirmOpen, setIsSaveConfirmOpen] = useState(false);
    const [itemToRemove, setItemToRemove] = useState<EnrollmentRow | null>(null);

    const createForm = useForm({
        academic_year_id: selected_school_year_id
            ? String(selected_school_year_id)
            : '',
        lrn: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        birthdate: '',
        guardian_name: '',
        guardian_contact_number: '',
        grade_level_id: '',
        section_id: '',
        payment_term: 'monthly',
        downpayment: '',
    });

    const editForm = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        birthdate: '',
        guardian_name: '',
        guardian_contact_number: '',
        grade_level_id: '',
        section_id: '',
        payment_term: 'monthly',
        downpayment: '',
    });

    const createSectionOptions = useMemo(() => {
        const selectedGradeLevelId = Number(
            createForm.data.grade_level_id || 0,
        );

        if (selectedGradeLevelId <= 0) {
            return [];
        }

        return section_options.filter(
            (sectionOption) =>
                sectionOption.grade_level_id === selectedGradeLevelId,
        );
    }, [createForm.data.grade_level_id, section_options]);

    const editSectionOptions = useMemo(() => {
        const selectedGradeLevelId = Number(editForm.data.grade_level_id || 0);

        if (selectedGradeLevelId <= 0) {
            return [];
        }

        return section_options.filter(
            (sectionOption) =>
                sectionOption.grade_level_id === selectedGradeLevelId,
        );
    }, [editForm.data.grade_level_id, section_options]);

    const applySearch = (value: string) => {
        setSearchQuery(value);
        router.get(
            registrar.enrollment.url({
                query: {
                    academic_year_id: selectedSchoolYearId || undefined,
                    search: value || undefined,
                },
            }),
            {},
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    const submitCreate = () => {
        createForm.post(store().url, {
            preserveScroll: true,
            onSuccess: () => {
                const yearId = createForm.data.academic_year_id;
                createForm.reset();
                createForm.setData('academic_year_id', yearId);
                createForm.setData('payment_term', 'monthly');
                createForm.setData('grade_level_id', '');
                createForm.setData('section_id', '');
                setCreateStep(1);
                setIsSaveConfirmOpen(false);
            },
        });
    };

    const openEdit = (item: EnrollmentRow) => {
        const sectionGradeLevelId = item.section_id
            ? section_options.find(
                  (sectionOption) => sectionOption.id === item.section_id,
              )?.grade_level_id
            : null;

        setEditingItem(item);
        editForm.setData({
            first_name: item.first_name,
            middle_name: item.middle_name || '',
            last_name: item.last_name,
            gender: item.gender || '',
            birthdate: item.birthdate || '',
            guardian_name: item.guardian_name || '',
            guardian_contact_number: item.guardian_contact_number || '',
            grade_level_id: item.grade_level_id
                ? String(item.grade_level_id)
                : sectionGradeLevelId
                  ? String(sectionGradeLevelId)
                  : '',
            section_id: item.section_id ? String(item.section_id) : '',
            payment_term: item.payment_term,
            downpayment: String(item.downpayment ?? 0),
        });
    };

    const submitEdit = () => {
        if (!editingItem) return;

        editForm.patch(update(editingItem.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                setEditingItem(null);
                editForm.reset();
            },
        });
    };

    const submitRemove = () => {
        if (!itemToRemove) return;

        router.delete(destroy(itemToRemove.id).url, {
            preserveScroll: true,
            onSuccess: () => setItemToRemove(null),
        });
    };

    const formatPaymentTerm = (term: string) => {
        if (term === 'semi-annual') return 'Semi-Annual';
        if (term === 'monthly') return 'Monthly';
        if (term === 'quarterly') return 'Quarterly';
        if (term === 'cash' || term === 'full') return 'Cash';

        return term;
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount || 0);
    };

    const formatStudentName = (
        firstName: string,
        middleName: string | null,
        lastName: string,
    ) => {
        return [firstName, middleName || '', lastName]
            .map((value) => value.trim())
            .filter((value) => value.length > 0)
            .join(' ');
    };

    const normalizeStatus = (status: string) => {
        if (status === 'pending' || status === 'pending_intake') {
            return 'for_cashier_payment';
        }

        return status;
    };

    const statusBadge = (status: string) => {
        const normalized = normalizeStatus(status);
        const labelMap: Record<string, string> = {
            for_cashier_payment: 'For Cashier Payment',
            enrolled: 'Enrolled',
            rejected: 'Rejected',
            pending: 'Pending',
            pending_intake: 'Pending',
        };

        const label = labelMap[normalized] || normalized
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (c) => c.toUpperCase());

        if (normalized === 'enrolled') {
            return (
                <Badge variant="outline" className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">
                    {label}
                </Badge>
            );
        }

        if (normalized === 'rejected') {
            return (
                <Badge variant="outline" className="bg-red-500/15 text-red-700 hover:bg-red-500/25 dark:text-red-400 border-red-200 dark:border-red-800">
                    {label}
                </Badge>
            );
        }

        if (normalized === 'for_cashier_payment') {
            return (
                <Badge variant="outline" className="bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400 border-amber-200 dark:border-amber-800">
                    {label}
                </Badge>
            );
        }

        return (
            <Badge variant="outline">{label}</Badge>
        );
    };

    const updateCreateGradeLevel = (value: string) => {
        const gradeLevelId = value === 'unselected' ? '' : value;

        createForm.setData('grade_level_id', gradeLevelId);

        if (!gradeLevelId) {
            if (createForm.data.section_id) {
                createForm.setData('section_id', '');
            }

            return;
        }

        if (!createForm.data.section_id) {
            return;
        }

        const selectedSection = section_options.find(
            (sectionOption) =>
                String(sectionOption.id) === createForm.data.section_id,
        );

        if (
            selectedSection &&
            String(selectedSection.grade_level_id) !== gradeLevelId
        ) {
            createForm.setData('section_id', '');
        }
    };

    const updateEditGradeLevel = (value: string) => {
        const gradeLevelId = value === 'unselected' ? '' : value;

        editForm.setData('grade_level_id', gradeLevelId);

        if (!gradeLevelId) {
            if (editForm.data.section_id) {
                editForm.setData('section_id', '');
            }

            return;
        }

        if (!editForm.data.section_id) {
            return;
        }

        const selectedSection = section_options.find(
            (sectionOption) =>
                String(sectionOption.id) === editForm.data.section_id,
        );

        if (
            selectedSection &&
            String(selectedSection.grade_level_id) !== gradeLevelId
        ) {
            editForm.setData('section_id', '');
        }
    };

    const switchSchoolYear = (value: string) => {
        setSelectedSchoolYearId(value);
        setCreateStep(1);
        createForm.setData('academic_year_id', value);
        router.get(
            registrar.enrollment.url({
                query: {
                    academic_year_id: value || undefined,
                    search: searchQuery || undefined,
                },
            }),
            {},
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    const searchSuggestions = useMemo(
        () =>
            enrollments.map((enrollment) => ({
                id: enrollment.id,
                label: formatStudentName(
                    enrollment.first_name,
                    enrollment.middle_name,
                    enrollment.last_name,
                ),
                value: formatStudentName(
                    enrollment.first_name,
                    enrollment.middle_name,
                    enrollment.last_name,
                ),
                description: `LRN: ${enrollment.lrn}`,
                keywords: enrollment.lrn,
            })),
        [enrollments],
    );

    const selectedGradeLevelLabel =
        grade_level_options.find(
            (gradeLevel) =>
                String(gradeLevel.id) === createForm.data.grade_level_id,
        )?.name ?? '';

    const selectedSectionLabel =
        section_options.find(
            (sectionOption) =>
                String(sectionOption.id) === createForm.data.section_id,
        )?.label ?? 'Unassigned';

    const hasStepOneRequiredFields =
        createForm.data.lrn.trim() !== '' &&
        createForm.data.first_name.trim() !== '' &&
        createForm.data.last_name.trim() !== '' &&
        createForm.data.birthdate.trim() !== '';

    const hasStepTwoRequiredFields =
        createForm.data.guardian_name.trim() !== '' &&
        createForm.data.guardian_contact_number.trim() !== '';

    const hasStepThreeRequiredFields =
        createForm.data.grade_level_id !== '' &&
        createForm.data.payment_term !== '' &&
        (createForm.data.payment_term === 'cash' ||
            createForm.data.downpayment.trim() !== '');

    const createStepProgress = (createStep / 4) * 100;
    const intakeCreationDisabled = selected_school_year_status === 'completed';
    const createStepLabelMap: Record<1 | 2 | 3 | 4, string> = {
        1: 'Student Details',
        2: 'Guardian Details',
        3: 'Enrollment Setup',
        4: 'Review & Finalize',
    };
    const parsedDownpayment = Number.parseFloat(
        createForm.data.downpayment || '0',
    );
    const normalizedDownpayment = Number.isFinite(parsedDownpayment)
        ? parsedDownpayment
        : 0;

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Enrollment" />

            <div className="flex flex-col gap-6">
                <div className="grid items-start gap-6 lg:grid-cols-3">
                    <Card className="h-[calc(100svh-7rem)] gap-2 overflow-hidden lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>New Enrollment Intake</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 overflow-y-auto pt-6">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Step {createStep} of 4</span>
                                    <span>
                                        {createStepLabelMap[createStep]}
                                    </span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full bg-primary transition-all duration-300"
                                        style={{
                                            width: `${createStepProgress}%`,
                                        }}
                                    />
                                </div>
                            </div>

                            {createStep === 1 && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="lrn">LRN</Label>
                                        <Input
                                            id="lrn"
                                            placeholder="eg. 123456789012"
                                            inputMode="numeric"
                                            pattern="[0-9]*"
                                            maxLength={12}
                                            value={createForm.data.lrn}
                                            onChange={(event) =>
                                                createForm.setData(
                                                    'lrn',
                                                    event.target.value
                                                        .replace(/\D/g, '')
                                                        .slice(0, 12),
                                                )
                                            }
                                        />
                                        {createForm.errors.lrn && (
                                            <p className="text-sm text-destructive">
                                                {createForm.errors.lrn}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="first-name">
                                                First Name
                                            </Label>
                                            <Input
                                                id="first-name"
                                                placeholder="eg. Juan"
                                                value={
                                                    createForm.data.first_name
                                                }
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'first_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="middle-name">
                                                Middle Name
                                            </Label>
                                            <Input
                                                id="middle-name"
                                                placeholder="eg. Santos"
                                                value={
                                                    createForm.data.middle_name
                                                }
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'middle_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="last-name">
                                                Surname
                                            </Label>
                                            <Input
                                                id="last-name"
                                                placeholder="eg. Dela Cruz"
                                                value={
                                                    createForm.data.last_name
                                                }
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'last_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Gender</Label>
                                            <Select
                                                value={
                                                    createForm.data.gender ||
                                                    'none'
                                                }
                                                onValueChange={(value) =>
                                                    createForm.setData(
                                                        'gender',
                                                        value === 'none'
                                                            ? ''
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select gender" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">
                                                        Select gender
                                                    </SelectItem>
                                                    <SelectItem value="Male">
                                                        Male
                                                    </SelectItem>
                                                    <SelectItem value="Female">
                                                        Female
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Birthday</Label>
                                            <DateOfBirthPicker
                                                date={
                                                    createForm.data.birthdate
                                                        ? new Date(
                                                              createForm.data
                                                                  .birthdate,
                                                          )
                                                        : undefined
                                                }
                                                setDate={(date) =>
                                                    createForm.setData(
                                                        'birthdate',
                                                        date
                                                            ? format(
                                                                  date,
                                                                  'yyyy-MM-dd',
                                                              )
                                                            : '',
                                                    )
                                                }
                                                className="w-full"
                                                placeholder="Select date"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                            {createStep === 2 && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="guardian-name">
                                            Guardian Name
                                        </Label>
                                        <Input
                                            id="guardian-name"
                                            placeholder="eg. Maria Dela Cruz"
                                            value={
                                                createForm.data.guardian_name
                                            }
                                            onChange={(event) =>
                                                createForm.setData(
                                                    'guardian_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="guardian-contact">
                                            Guardian Contact
                                        </Label>
                                        <Input
                                            id="guardian-contact"
                                            placeholder="eg. 09171234567"
                                            inputMode="numeric"
                                            pattern="[0-9]*"
                                            maxLength={11}
                                            value={
                                                createForm.data
                                                    .guardian_contact_number
                                            }
                                            onChange={(event) =>
                                                createForm.setData(
                                                    'guardian_contact_number',
                                                    event.target.value
                                                        .replace(/\D/g, '')
                                                        .slice(0, 11),
                                                )
                                            }
                                        />
                                        {createForm.errors
                                            .guardian_contact_number && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    createForm.errors
                                                        .guardian_contact_number
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {createStep === 3 && (
                                <div className="space-y-4">
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>Grade Level</Label>
                                            <Select
                                                value={
                                                    createForm.data
                                                        .grade_level_id ||
                                                    'unselected'
                                                }
                                                onValueChange={
                                                    updateCreateGradeLevel
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select grade level" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="unselected">
                                                        Select grade level
                                                    </SelectItem>
                                                    {grade_level_options.map(
                                                        (gradeLevel) => (
                                                            <SelectItem
                                                                key={
                                                                    gradeLevel.id
                                                                }
                                                                value={String(
                                                                    gradeLevel.id,
                                                                )}
                                                            >
                                                                {
                                                                    gradeLevel.name
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Section Assignment</Label>
                                            <Select
                                                value={
                                                    createForm.data
                                                        .section_id ||
                                                    'unassigned'
                                                }
                                                onValueChange={(value) => {
                                                    if (
                                                        value === 'unassigned'
                                                    ) {
                                                        createForm.setData(
                                                            'section_id',
                                                            '',
                                                        );

                                                        return;
                                                    }

                                                    createForm.setData(
                                                        'section_id',
                                                        value,
                                                    );

                                                    const selectedSection =
                                                        section_options.find(
                                                            (sectionOption) =>
                                                                String(
                                                                    sectionOption.id,
                                                                ) === value,
                                                        );

                                                    if (selectedSection) {
                                                        createForm.setData(
                                                            'grade_level_id',
                                                            String(
                                                                selectedSection.grade_level_id,
                                                            ),
                                                        );
                                                    }
                                                }}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder={
                                                            createForm.data
                                                                .grade_level_id
                                                                ? 'Select section'
                                                                : 'Select grade level first'
                                                        }
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="unassigned">
                                                        Unassigned
                                                    </SelectItem>
                                                    {createSectionOptions.map(
                                                        (sectionOption) => (
                                                            <SelectItem
                                                                key={
                                                                    sectionOption.id
                                                                }
                                                                value={String(
                                                                    sectionOption.id,
                                                                )}
                                                            >
                                                                {
                                                                    sectionOption.label
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>Payment Plan</Label>
                                            <Select
                                                value={
                                                    createForm.data.payment_term
                                                }
                                                onValueChange={(value) => {
                                                    createForm.setData(
                                                        'payment_term',
                                                        value,
                                                    );
                                                    if (value === 'cash') {
                                                        createForm.setData(
                                                            'downpayment',
                                                            '0',
                                                        );
                                                    }
                                                }}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="cash">
                                                        Cash
                                                    </SelectItem>
                                                    <SelectItem value="monthly">
                                                        Monthly
                                                    </SelectItem>
                                                    <SelectItem value="quarterly">
                                                        Quarterly
                                                    </SelectItem>
                                                    <SelectItem value="semi-annual">
                                                        Semi-Annual
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="downpayment">
                                                Downpayment
                                            </Label>
                                            <Input
                                                id="downpayment"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                value={
                                                    createForm.data.downpayment
                                                }
                                                disabled={
                                                    createForm.data
                                                        .payment_term === 'cash'
                                                }
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'downpayment',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {createStep === 4 && (
                                <div className="rounded-md border p-4">
                                    <h4 className="text-sm font-medium">
                                        Enrollment Intake Summary
                                    </h4>
                                    <div className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                        <p className="text-muted-foreground">
                                            LRN
                                        </p>
                                        <p>{createForm.data.lrn || '-'}</p>

                                        <p className="text-muted-foreground">
                                            Student Name
                                        </p>
                                        <p>
                                            {formatStudentName(
                                                createForm.data.first_name,
                                                createForm.data.middle_name ||
                                                    null,
                                                createForm.data.last_name,
                                            ) || '-'}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Gender
                                        </p>
                                        <p>{createForm.data.gender || '-'}</p>

                                        <p className="text-muted-foreground">
                                            Birthday
                                        </p>
                                        <p>
                                            {createForm.data.birthdate || '-'}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Guardian Name
                                        </p>
                                        <p>
                                            {createForm.data.guardian_name ||
                                                '-'}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Guardian Contact
                                        </p>
                                        <p>
                                            {createForm.data
                                                .guardian_contact_number || '-'}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Grade Level
                                        </p>
                                        <p>{selectedGradeLevelLabel || '-'}</p>

                                        <p className="text-muted-foreground">
                                            Section
                                        </p>
                                        <p>{selectedSectionLabel}</p>

                                        <p className="text-muted-foreground">
                                            Payment Plan
                                        </p>
                                        <p>
                                            {formatPaymentTerm(
                                                createForm.data.payment_term,
                                            )}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Downpayment
                                        </p>
                                        <p>
                                            {createForm.data.payment_term ===
                                            'cash'
                                                ? formatCurrency(0)
                                                : formatCurrency(
                                                      normalizedDownpayment,
                                                  )}
                                        </p>
                                    </div>
                                </div>
                            )}

                            <div className="sticky bottom-0 z-10 flex flex-col gap-2 border-t bg-card pt-4">
                                {createStep > 1 && (
                                    <Button
                                        variant="outline"
                                        className="w-full whitespace-normal"
                                        onClick={() =>
                                            setCreateStep(
                                                (createStep - 1) as
                                                    | 1
                                                    | 2
                                                    | 3
                                                    | 4,
                                            )
                                        }
                                    >
                                        Back
                                    </Button>
                                )}

                                {createStep === 1 && (
                                    <Button
                                        className="w-full whitespace-normal"
                                        onClick={() => setCreateStep(2)}
                                        disabled={
                                            intakeCreationDisabled ||
                                            !hasStepOneRequiredFields
                                        }
                                    >
                                        Continue to Guardian Details
                                    </Button>
                                )}

                                {createStep === 2 && (
                                    <Button
                                        className="w-full whitespace-normal"
                                        onClick={() => setCreateStep(3)}
                                        disabled={
                                            intakeCreationDisabled ||
                                            !hasStepTwoRequiredFields
                                        }
                                    >
                                        Continue to Enrollment Setup
                                    </Button>
                                )}

                                {createStep === 3 && (
                                    <Button
                                        className="w-full whitespace-normal"
                                        onClick={() => setCreateStep(4)}
                                        disabled={
                                            intakeCreationDisabled ||
                                            !hasStepThreeRequiredFields
                                        }
                                    >
                                        Continue to Summary
                                    </Button>
                                )}

                                {createStep === 4 && (
                                    <Button
                                        className="w-full whitespace-normal"
                                        onClick={() =>
                                            setIsSaveConfirmOpen(true)
                                        }
                                        disabled={
                                            createForm.processing ||
                                            intakeCreationDisabled
                                        }
                                    >
                                        Save Enrollment Intake
                                    </Button>
                                )}
                            </div>

                            {intakeCreationDisabled && (
                                <p className="text-sm text-muted-foreground">
                                    Intake creation is disabled for completed
                                    school years.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3">
                                <CardTitle>Enrollment Queue</CardTitle>
                                <div className="flex flex-wrap items-center gap-2 text-sm">
                                    <Badge variant="outline">
                                        For Cashier Payment:{' '}
                                        {summary.for_cashier_payment}
                                    </Badge>
                                </div>
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <Select
                                        value={selectedSchoolYearId}
                                        onValueChange={switchSchoolYear}
                                    >
                                        <SelectTrigger className="w-full sm:w-44">
                                            <SelectValue placeholder="School Year" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {school_year_options.map(
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
                                    <SearchAutocompleteInput
                                        wrapperClassName="w-full sm:max-w-sm"
                                        placeholder="Search by LRN or name..."
                                        value={searchQuery}
                                        onValueChange={applySearch}
                                        suggestions={searchSuggestions}
                                        showSuggestions={false}
                                    />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Student
                                        </TableHead>
                                        <TableHead>Enrollment</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {enrollments.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="pl-6">
                                                <div className="space-y-1">
                                                    <p className="font-medium">
                                                        {formatStudentName(
                                                            item.first_name,
                                                            item.middle_name,
                                                            item.last_name,
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        LRN: {item.lrn}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1 text-sm">
                                                    <p>
                                                        {item.section_label ??
                                                            '-'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatPaymentTerm(
                                                            item.payment_term,
                                                        )}{' '}
                                                        •{' '}
                                                        {formatCurrency(
                                                            item.downpayment,
                                                        )}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {statusBadge(item.status)}
                                            </TableCell>
                                            <TableCell className="pr-6">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        onClick={() =>
                                                            openEdit(item)
                                                        }
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        onClick={() =>
                                                            setItemToRemove(item)
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {enrollments.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="h-24 text-center text-sm text-muted-foreground"
                                            >
                                                No enrollment intakes in queue.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog
                open={!!editingItem}
                onOpenChange={() => setEditingItem(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Edit Enrollment Intake</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-2">
                        <div className="grid grid-cols-3 gap-4">
                            <div className="space-y-2">
                                <Label>First Name</Label>
                                <Input
                                    value={editForm.data.first_name}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'first_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Middle Name</Label>
                                <Input
                                    value={editForm.data.middle_name}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'middle_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Last Name</Label>
                                <Input
                                    value={editForm.data.last_name}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'last_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Gender</Label>
                                <Select
                                    value={editForm.data.gender || 'none'}
                                    onValueChange={(value) =>
                                        editForm.setData(
                                            'gender',
                                            value === 'none' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Select gender
                                        </SelectItem>
                                        <SelectItem value="Male">
                                            Male
                                        </SelectItem>
                                        <SelectItem value="Female">
                                            Female
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Birthday</Label>
                                <DateOfBirthPicker
                                    date={
                                        editForm.data.birthdate
                                            ? new Date(editForm.data.birthdate)
                                            : undefined
                                    }
                                    setDate={(date) =>
                                        editForm.setData(
                                            'birthdate',
                                            date
                                                ? format(date, 'yyyy-MM-dd')
                                                : '',
                                        )
                                    }
                                    className="w-full"
                                    placeholder="Select date"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Guardian Name</Label>
                                <Input
                                    value={editForm.data.guardian_name}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'guardian_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Guardian Contact Number</Label>
                                <Input
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={11}
                                    value={
                                        editForm.data.guardian_contact_number
                                    }
                                    onChange={(event) =>
                                        editForm.setData(
                                            'guardian_contact_number',
                                            event.target.value
                                                .replace(/\D/g, '')
                                                .slice(0, 11),
                                        )
                                    }
                                />
                                {editForm.errors.guardian_contact_number && (
                                    <p className="text-sm text-destructive">
                                        {
                                            editForm.errors
                                                .guardian_contact_number
                                        }
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Grade Level</Label>
                                <Select
                                    value={
                                        editForm.data.grade_level_id ||
                                        'unselected'
                                    }
                                    onValueChange={updateEditGradeLevel}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select grade level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unselected">
                                            Select grade level
                                        </SelectItem>
                                        {grade_level_options.map(
                                            (gradeLevel) => (
                                                <SelectItem
                                                    key={gradeLevel.id}
                                                    value={String(
                                                        gradeLevel.id,
                                                    )}
                                                >
                                                    {gradeLevel.name}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Section Assignment</Label>
                                <Select
                                    value={
                                        editForm.data.section_id || 'unassigned'
                                    }
                                    onValueChange={(value) => {
                                        if (value === 'unassigned') {
                                            editForm.setData('section_id', '');

                                            return;
                                        }

                                        editForm.setData('section_id', value);

                                        const selectedSection =
                                            section_options.find(
                                                (sectionOption) =>
                                                    String(sectionOption.id) ===
                                                    value,
                                            );

                                        if (selectedSection) {
                                            editForm.setData(
                                                'grade_level_id',
                                                String(
                                                    selectedSection.grade_level_id,
                                                ),
                                            );
                                        }
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder={
                                                editForm.data.grade_level_id
                                                    ? 'Select section'
                                                    : 'Select grade level first'
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unassigned">
                                            Unassigned
                                        </SelectItem>
                                        {editSectionOptions.map(
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
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Payment Plan</Label>
                                <Select
                                    value={editForm.data.payment_term}
                                    onValueChange={(value) => {
                                        editForm.setData('payment_term', value);
                                        if (value === 'cash') {
                                            editForm.setData(
                                                'downpayment',
                                                '0',
                                            );
                                        }
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">
                                            Cash
                                        </SelectItem>
                                        <SelectItem value="monthly">
                                            Monthly
                                        </SelectItem>
                                        <SelectItem value="quarterly">
                                            Quarterly
                                        </SelectItem>
                                        <SelectItem value="semi-annual">
                                            Semi-Annual
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Downpayment</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={editForm.data.downpayment}
                                    disabled={
                                        editForm.data.payment_term === 'cash'
                                    }
                                    onChange={(event) =>
                                        editForm.setData(
                                            'downpayment',
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
                            onClick={() => setEditingItem(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={submitEdit}
                            disabled={editForm.processing}
                        >
                            Save Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>

            <ActionConfirmDialog
                open={isSaveConfirmOpen}
                onOpenChange={setIsSaveConfirmOpen}
                title="Save Enrollment Intake"
                description={`Are you sure you want to save the enrollment intake for ${formatStudentName(createForm.data.first_name, createForm.data.middle_name || null, createForm.data.last_name)}? This will add them to the queue for cashier payment.`}
                confirmLabel="Confirm Enrollment"
                loading={createForm.processing}
                onConfirm={submitCreate}
            />

            <ActionConfirmDialog
                open={!!itemToRemove}
                onOpenChange={(open) => !open && setItemToRemove(null)}
                title="Remove from Queue"
                description={`Are you sure you want to remove ${itemToRemove ? formatStudentName(itemToRemove.first_name, itemToRemove.middle_name, itemToRemove.last_name) : ''} from the enrollment queue? This action cannot be undone.`}
                variant="destructive"
                confirmLabel="Remove Entry"
                onConfirm={submitRemove}
            />
        </>
    );
}
