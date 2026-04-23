import { Head, router, useForm, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, Pencil, Printer, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import registrar from '@/routes/registrar';
import {
    assessment,
    destroy,
    lookup,
    store,
    update,
} from '@/routes/registrar/enrollment';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Enrollment',
        href: '/registrar/enrollment',
    },
];

interface EnrollmentRow {
    id: number;
    lrn: string;
    email: string | null;
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
    enrollments: {
        data: EnrollmentRow[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    grade_level_options: GradeLevelOption[];
    section_options: SectionOption[];
    active_school_year: {
        id: number;
        name: string;
        status: string;
    } | null;
    summary: {
        for_cashier_payment: number;
        enrolled: number;
    };
    filters: {
        search?: string;
        status?: 'for_cashier_payment' | 'enrolled';
    };
}

interface EnrollmentLookupResponse {
    matched: boolean;
    academic_year_id: number | null;
    student: {
        lrn: string;
        first_name: string;
        middle_name: string | null;
        last_name: string;
        gender: string | null;
        birthdate: string | null;
        guardian_name: string | null;
        guardian_contact_number: string | null;
        recommended_grade_level_id: number | null;
    } | null;
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

export default function Enrollment({
    enrollments,
    grade_level_options,
    section_options,
    active_school_year,
    summary,
    filters,
}: Props) {
    const { flash } = usePage<SharedData>().props;
    const [editingItem, setEditingItem] = useState<EnrollmentRow | null>(null);
    const [createStep, setCreateStep] = useState<1 | 2 | 3 | 4>(1);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [statusTab, setStatusTab] = useState<'for_cashier_payment' | 'enrolled'>(
        filters.status === 'enrolled' ? 'enrolled' : 'for_cashier_payment',
    );
    const [isStepOneExpanded, setIsStepOneExpanded] = useState(false);
    const [isLookupLoading, setIsLookupLoading] = useState(false);
    const [lookupStatus, setLookupStatus] = useState<
        'idle' | 'matched' | 'not_found' | 'error'
    >('idle');
    const [lookupMessage, setLookupMessage] = useState(
        'Type 12 digits to continue.',
    );
    const [isSaveConfirmOpen, setIsSaveConfirmOpen] = useState(false);
    const [itemToRemove, setItemToRemove] = useState<EnrollmentRow | null>(null);
    const openedAssessmentUrlRef = useRef<string | null>(null);
    const latestLookupLrnRef = useRef<string | null>(null);

    const createForm = useForm({
        academic_year_id: active_school_year
            ? String(active_school_year.id)
            : '',
        lrn: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        birthdate: '',
        guardian_name: '',
        guardian_contact_number: '',
        email: '',
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
        email: '',
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
                    status: statusTab,
                    search: value || undefined,
                    page: undefined,
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

    const applyLookupResult = (payload: EnrollmentLookupResponse) => {
        if (typeof payload.academic_year_id === 'number') {
            createForm.setData('academic_year_id', String(payload.academic_year_id));
        }

        if (!payload.student) {
            createForm.setData('first_name', '');
            createForm.setData('middle_name', '');
            createForm.setData('last_name', '');
            createForm.setData('gender', '');
            createForm.setData('birthdate', '');
            createForm.setData('guardian_name', '');
            createForm.setData('guardian_contact_number', '');
            createForm.setData('email', '');
            createForm.setData('grade_level_id', '');
            createForm.setData('section_id', '');

            return;
        }

        createForm.setData('first_name', payload.student.first_name ?? '');
        createForm.setData('middle_name', payload.student.middle_name ?? '');
        createForm.setData('last_name', payload.student.last_name ?? '');
        createForm.setData('gender', payload.student.gender ?? '');
        createForm.setData('birthdate', payload.student.birthdate ?? '');
        createForm.setData('guardian_name', payload.student.guardian_name ?? '');
        createForm.setData(
            'guardian_contact_number',
            normalizeMobileSubscriberDigits(
                payload.student.guardian_contact_number ?? '',
            ),
        );
        createForm.setData(
            'grade_level_id',
            payload.student.recommended_grade_level_id
                ? String(payload.student.recommended_grade_level_id)
                : '',
        );
        createForm.setData('section_id', '');
    };

    const runLrnLookup = async (lrnValue: string) => {
        const normalizedLrn = lrnValue.replace(/\D/g, '').slice(0, 12);

        if (normalizedLrn.length !== 12) {
            setLookupStatus('idle');
            setLookupMessage('Type 12 digits to continue.');

            return;
        }

        if (isLookupLoading || latestLookupLrnRef.current === normalizedLrn) {
            return;
        }

        setIsLookupLoading(true);
        setLookupStatus('idle');
        setLookupMessage('Checking learner records...');

        try {
            const response = await fetch(
                lookup.url({
                    query: {
                        lrn: normalizedLrn,
                    },
                }),
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );
            const payload =
                (await response.json()) as EnrollmentLookupResponse & {
                    errors?: {
                        lrn?: string[];
                    };
                };

            if (!response.ok) {
                if (response.status === 422 && payload.errors?.lrn?.length) {
                    createForm.setError('lrn', payload.errors.lrn[0]);
                    setLookupStatus('error');
                    setLookupMessage(payload.errors.lrn[0]);
                } else {
                    setLookupStatus('error');
                    setLookupMessage(
                        'Unable to check learner records. Please try again.',
                    );
                }

                return;
            }

            latestLookupLrnRef.current = normalizedLrn;
            createForm.clearErrors('lrn');
            applyLookupResult(payload);
            setIsStepOneExpanded(true);

            if (payload.matched) {
                setLookupStatus('matched');
                setLookupMessage('');

                return;
            }

            setLookupStatus('not_found');
            setLookupMessage('');
        } catch {
            setLookupStatus('error');
            setLookupMessage(
                'Unable to check learner records. Please try again.',
            );
        } finally {
            setIsLookupLoading(false);
        }
    };

    const handleCreateLrnChange = (rawValue: string) => {
        const normalizedLrn = rawValue.replace(/\D/g, '').slice(0, 12);

        createForm.setData('lrn', normalizedLrn);
        latestLookupLrnRef.current = null;

        if (normalizedLrn.length < 12) {
            setLookupStatus('idle');
            setLookupMessage('Type 12 digits to continue.');
        }

        if (normalizedLrn.length === 12) {
            void runLrnLookup(normalizedLrn);
        }
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
                latestLookupLrnRef.current = null;
                setIsStepOneExpanded(false);
                setLookupStatus('idle');
                setLookupMessage('Type 12 digits to continue.');
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
            guardian_contact_number: normalizeMobileSubscriberDigits(
                item.guardian_contact_number || '',
            ),
            email: item.email || '',
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

    useEffect(() => {
        const shouldExpandStepOne =
            createStep === 1 &&
            (
                createForm.data.first_name.trim() !== '' ||
                createForm.data.last_name.trim() !== '' ||
                !!createForm.errors.first_name ||
                !!createForm.errors.last_name ||
                !!createForm.errors.birthdate
            );

        if (shouldExpandStepOne) {
            setIsStepOneExpanded(true);
        }
    }, [
        createStep,
        createForm.data.first_name,
        createForm.data.last_name,
        createForm.errors.birthdate,
        createForm.errors.first_name,
        createForm.errors.last_name,
    ]);

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

    const switchStatusTab = (value: string) => {
        if (value !== 'for_cashier_payment' && value !== 'enrolled') {
            return;
        }

        setStatusTab(value);
        router.get(
            registrar.enrollment.url({
                query: {
                    status: value,
                    search: searchQuery || undefined,
                    page: undefined,
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

    const goToQueuePage = (page: number) => {
        router.get(
            registrar.enrollment.url({
                query: {
                    status: statusTab,
                    search: searchQuery || undefined,
                    page: page > 1 ? page : undefined,
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

    const exportEnrollmentWorkbook = () => {
        window.location.assign('/registrar/enrollment/export');
    };

    const searchSuggestions = useMemo(
        () =>
            enrollments.data.map((enrollment) => ({
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
        [enrollments.data],
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
        createForm.data.gender.trim() !== '' &&
        createForm.data.birthdate.trim() !== '';

    const hasStepTwoRequiredFields =
        createForm.data.guardian_name.trim() !== '' &&
        createForm.data.guardian_contact_number.trim().length === 10;

    const hasStepThreeRequiredFields =
        createForm.data.grade_level_id !== '' &&
        createForm.data.payment_term !== '' &&
        (createForm.data.payment_term === 'cash' ||
            createForm.data.downpayment.trim() !== '');

    const createStepProgress = (createStep / 4) * 100;
    const intakeCreationDisabled = active_school_year?.status === 'completed';
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
                <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
                    <Card className="h-[calc(100svh-7rem)] gap-2 overflow-hidden lg:sticky lg:top-6 lg:w-[25rem] lg:flex-none xl:w-[27rem]">
                        <CardHeader className="border-b">
                            <CardTitle>New Enrollment Intake</CardTitle>
                        </CardHeader>
                        <CardContent className="flex h-full flex-col gap-4 overflow-y-auto pt-6">
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
                                    <div className="relative min-h-[16rem] rounded-lg border bg-card p-4">
                                        <div
                                            className={`absolute left-4 right-4 text-center transition-opacity duration-200 ${
                                                isStepOneExpanded
                                                    ? 'pointer-events-none opacity-0'
                                                    : 'top-[34%] -translate-y-1/2 opacity-100'
                                            }`}
                                        >
                                            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                Student Lookup
                                            </p>
                                            <p className="mt-2 text-base font-medium text-foreground">
                                                Enter LRN to start enrollment
                                            </p>
                                        </div>

                                        <div
                                            className={`absolute z-20 transition-all duration-300 ease-out ${
                                                isStepOneExpanded
                                                    ? 'left-4 right-4 top-4 translate-x-0 translate-y-0'
                                                    : 'left-1/2 top-[60%] w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2'
                                            }`}
                                        >
                                            <div className="space-y-2">
                                                {isStepOneExpanded && (
                                                    <div className="flex items-center justify-between">
                                                        <Label htmlFor="lrn">
                                                            LRN
                                                        </Label>
                                                        {lookupStatus ===
                                                            'matched' && (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-emerald-200 bg-emerald-500/15 text-emerald-700 dark:border-emerald-800 dark:text-emerald-400"
                                                            >
                                                                Returning
                                                                Student
                                                            </Badge>
                                                        )}
                                                        {lookupStatus ===
                                                            'not_found' && (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-blue-200 bg-blue-500/15 text-blue-700 dark:border-blue-800 dark:text-blue-400"
                                                            >
                                                                New Student
                                                            </Badge>
                                                        )}
                                                    </div>
                                                )}
                                                <Input
                                                    id="lrn"
                                                    placeholder={
                                                        isStepOneExpanded
                                                            ? 'eg. 123456789012'
                                                            : '123456789012'
                                                    }
                                                    inputMode="numeric"
                                                    pattern="[0-9]*"
                                                    maxLength={12}
                                                    value={createForm.data.lrn}
                                                    className={
                                                        isStepOneExpanded
                                                            ? 'h-10 text-base'
                                                            : 'h-11 text-center text-lg tracking-wide'
                                                    }
                                                    onChange={(event) =>
                                                        handleCreateLrnChange(
                                                            event.target.value,
                                                        )
                                                    }
                                                    onBlur={() =>
                                                        void runLrnLookup(
                                                            createForm.data.lrn,
                                                        )
                                                    }
                                                    onKeyDown={(event) => {
                                                        if (
                                                            event.key === 'Enter'
                                                        ) {
                                                            event.preventDefault();
                                                            void runLrnLookup(
                                                                createForm.data
                                                                    .lrn,
                                                            );
                                                        }
                                                    }}
                                                />
                                                {(isLookupLoading ||
                                                    lookupMessage !== '') && (
                                                    <p
                                                        className={`text-xs text-muted-foreground ${
                                                            isStepOneExpanded
                                                                ? ''
                                                                : 'text-center'
                                                        }`}
                                                    >
                                                        {isLookupLoading
                                                            ? 'Checking learner records...'
                                                            : lookupMessage}
                                                    </p>
                                                )}
                                                {createForm.errors.lrn && (
                                                    <p className="text-sm text-destructive">
                                                        {
                                                            createForm.errors
                                                                .lrn
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div
                                            className={`space-y-4 transition-all duration-200 ${
                                                isStepOneExpanded
                                                    ? 'mt-24 translate-y-0 opacity-100'
                                                    : 'pointer-events-none absolute left-4 right-4 top-[4.5rem] translate-y-4 opacity-0'
                                            }`}
                                        >
                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="first-name">
                                                        First Name
                                                    </Label>
                                                    <Input
                                                        id="first-name"
                                                        placeholder="eg. Juan"
                                                        value={
                                                            createForm.data
                                                                .first_name
                                                        }
                                                        onChange={(event) =>
                                                            createForm.setData(
                                                                'first_name',
                                                                event.target
                                                                    .value,
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
                                                            createForm.data
                                                                .middle_name
                                                        }
                                                        onChange={(event) =>
                                                            createForm.setData(
                                                                'middle_name',
                                                                event.target
                                                                    .value,
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
                                                            createForm.data
                                                                .last_name
                                                        }
                                                        onChange={(event) =>
                                                            createForm.setData(
                                                                'last_name',
                                                                event.target
                                                                    .value,
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
                                                            createForm.data
                                                                .gender ||
                                                            'none'
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            createForm.setData(
                                                                'gender',
                                                                value ===
                                                                    'none'
                                                                    ? ''
                                                                    : value,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger className="w-full min-w-0">
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
                                                            createForm.data
                                                                .birthdate
                                                                ? new Date(
                                                                      createForm
                                                                          .data
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
                                                        className="w-full min-w-0"
                                                        placeholder="Select date"
                                                    />
                                                </div>
                                            </div>
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
                                        <div className="flex w-full min-w-0">
                                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                                +63
                                            </span>
                                            <Input
                                                id="guardian-contact"
                                                className="rounded-l-none"
                                                placeholder="9XXXXXXXXX"
                                                inputMode="numeric"
                                                pattern="[0-9]*"
                                                maxLength={10}
                                                value={
                                                    createForm.data
                                                        .guardian_contact_number
                                                }
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'guardian_contact_number',
                                                        normalizeMobileSubscriberDigits(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
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
                                    <div className="space-y-2">
                                        <Label htmlFor="contact-email">
                                            Contact Email
                                        </Label>
                                        <Input
                                            id="contact-email"
                                            type="email"
                                            placeholder="eg. guardian@example.com"
                                            value={createForm.data.email}
                                            onChange={(event) =>
                                                createForm.setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {createForm.errors.email && (
                                            <p className="text-sm text-destructive">
                                                {createForm.errors.email}
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
                                            <SelectTrigger className="w-full min-w-0">
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
                                                <SelectTrigger className="w-full min-w-0">
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
                                                <SelectTrigger className="w-full min-w-0">
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
                                            {formatMobileForDisplay(
                                                createForm.data
                                                    .guardian_contact_number,
                                            )}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Contact Email
                                        </p>
                                        <p>{createForm.data.email || '-'}</p>

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

                            <div className="sticky bottom-0 z-20 -mx-6 mt-auto flex flex-col gap-2 border-t bg-card px-6 pb-4 pt-4">
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
                                            isLookupLoading ||
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

                    <Card className="min-w-0 flex-1">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3">
                                <CardTitle>Enrollment Queue</CardTitle>
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <Tabs
                                        value={statusTab}
                                        onValueChange={switchStatusTab}
                                    >
                                        <TabsList>
                                            <TabsTrigger value="for_cashier_payment">
                                                For Cashier Payment
                                            </TabsTrigger>
                                            <TabsTrigger value="enrolled">
                                                Enrolled
                                            </TabsTrigger>
                                        </TabsList>
                                    </Tabs>
                                    <SearchAutocompleteInput
                                        wrapperClassName="w-full sm:max-w-sm"
                                        placeholder="Search by LRN or name..."
                                        value={searchQuery}
                                        onValueChange={applySearch}
                                        suggestions={searchSuggestions}
                                        showSuggestions={false}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={exportEnrollmentWorkbook}
                                    >
                                        <Download className="size-4" />
                                        Export Enrollment Workbook
                                    </Button>
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
                                    {enrollments.data.map((item) => (
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
                                                            window.open(
                                                                assessment(
                                                                    item.id,
                                                                ).url,
                                                                '_blank',
                                                                'noopener,noreferrer',
                                                            )
                                                        }
                                                    >
                                                        <Printer className="size-4" />
                                                    </Button>
                                                    {normalizeStatus(item.status) !==
                                                        'enrolled' && (
                                                        <>
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
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {enrollments.data.length === 0 && (
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
                            <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    {enrollments.total === 0
                                        ? 'No enrollment intakes found.'
                                        : `Showing ${enrollments.from}-${enrollments.to} of ${enrollments.total} intakes`}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            goToQueuePage(
                                                enrollments.current_page - 1,
                                            )
                                        }
                                        disabled={
                                            enrollments.current_page <= 1
                                        }
                                    >
                                        Previous
                                    </Button>
                                    <span className="text-sm text-muted-foreground">
                                        Page {enrollments.current_page} of{' '}
                                        {enrollments.last_page}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            goToQueuePage(
                                                enrollments.current_page + 1,
                                            )
                                        }
                                        disabled={
                                            enrollments.current_page >=
                                            enrollments.last_page
                                        }
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
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
                                        value={
                                            editForm.data.guardian_contact_number
                                        }
                                        onChange={(event) =>
                                            editForm.setData(
                                                'guardian_contact_number',
                                                normalizeMobileSubscriberDigits(
                                                    event.target.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
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

                        <div className="space-y-2">
                            <Label>Contact Email</Label>
                            <Input
                                type="email"
                                value={editForm.data.email}
                                onChange={(event) =>
                                    editForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                            />
                            {editForm.errors.email && (
                                <p className="text-sm text-destructive">
                                    {editForm.errors.email}
                                </p>
                            )}
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
