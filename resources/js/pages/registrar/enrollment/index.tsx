import { Head, router, useForm, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Download, ListFilter, Pencil, Printer, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DateOfBirthPicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
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
import { Textarea } from '@/components/ui/textarea';
import { TooltipProvider } from '@/components/ui/tooltip';
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
    student_personal_email: string | null;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    gender: string | null;
    birthdate: string | null;
    guardian_name: string;
    guardian_contact_number: string;
    payment_term: string;
    downpayment: number;
    report_card_submitted: boolean;
    birth_certificate_submitted: boolean;
    status: string;
    grade_level_id: number | null;
    section_id: number | null;
    section_label: string | null;
    discount_id: number | null;
    discount_name: string | null;
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

interface Filters {
    search?: string;
    status?: 'for_cashier_payment' | 'enrolled';
    sort?: 'newest' | 'oldest';
    requirements?: 'all' | 'missing' | 'complete';
}

interface Props {
    enrollments: {
        data: EnrollmentRow[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    grade_level_options: GradeLevelOption[];
    section_options: SectionOption[];
    discount_options: {
        id: number;
        name: string;
    }[];
    active_school_year: {
        id: number;
        name: string;
        status: string;
    } | null;
    summary: {
        for_cashier_payment: number;
        enrolled: number;
    };
    filters: Filters;
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
        student_personal_email: string | null;
        recommended_grade_level_id: number | null;
    } | null;
    grade_prefill_mode?: 'next_grade' | 'same_grade' | 'none';
    grade_guardrail?: {
        allowed_exact_grade_level_id: number | null;
        min_allowed_grade_level_order: number | null;
        max_allowed_grade_level_order: number | null;
    };
    status_flags?: {
        has_previous_year_conditional: boolean;
        has_previous_year_retained: boolean;
        has_older_unresolved_conditional: boolean;
        has_older_unresolved_retained: boolean;
    };
    source_context?: {
        academic_year_id: number;
        academic_year_name: string;
        status: string | null;
        grade_level_id: number;
        grade_level_label: string;
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
    discount_options,
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
    const [sortFilter, setSortFilter] = useState<'newest' | 'oldest'>(
        filters.sort === 'oldest' ? 'oldest' : 'newest',
    );
    const [requirementsFilter, setRequirementsFilter] = useState<'all' | 'missing' | 'complete'>(
        filters.requirements === 'missing' || filters.requirements === 'complete'
            ? filters.requirements
            : 'all',
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
    const [lookupGuardrail, setLookupGuardrail] = useState<{
        allowed_exact_grade_level_id: number | null;
        min_allowed_grade_level_order: number | null;
        max_allowed_grade_level_order: number | null;
    } | null>(null);
    const [lookupStatusFlags, setLookupStatusFlags] = useState<{
        has_previous_year_conditional: boolean;
        has_previous_year_retained: boolean;
        has_older_unresolved_conditional: boolean;
        has_older_unresolved_retained: boolean;
    } | null>(null);
    const [lookupSourceContext, setLookupSourceContext] = useState<{
        academic_year_name: string;
        status: string | null;
        grade_level_label: string;
    } | null>(null);
    const [isOlderConditionalDialogOpen, setIsOlderConditionalDialogOpen] = useState(false);
    const [isOlderRetainedDialogOpen, setIsOlderRetainedDialogOpen] = useState(false);
    const openedAssessmentUrlRef = useRef<string | null>(null);
    const latestLookupLrnRef = useRef<string | null>(null);

    const activeFilterCount = useMemo(() => {
        let count = 0;
        if (sortFilter !== 'newest') count++;
        if (requirementsFilter !== 'all') count++;
        return count;
    }, [sortFilter, requirementsFilter]);

    const switchStatusTab = (val: string) => {
        const nextStatus = val as 'for_cashier_payment' | 'enrolled';
        setStatusTab(nextStatus);
        applyFilters({ status: nextStatus });
    };

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
        student_personal_email: '',
        grade_level_id: '',
        section_id: '',
        payment_term: 'monthly',
        downpayment: '',
        report_card_submitted: false,
        birth_certificate_submitted: false,
        resolve_older_conditional: false,
        resolve_older_retained: false,
        conditional_resolution_notes: '',
        retained_resolution_notes: '',
        discount_id: '',
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
        student_personal_email: '',
        grade_level_id: '',
        section_id: '',
        payment_term: 'monthly',
        downpayment: '',
        report_card_submitted: false,
        birth_certificate_submitted: false,
        discount_id: '',
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
    const gradeLevelOrderById = useMemo(() => {
        const sortedIds = [...grade_level_options]
            .map((item) => item.id)
            .sort((a, b) => a - b);
        const map = new Map<number, number>();
        sortedIds.forEach((gradeId, index) => {
            map.set(gradeId, index + 1);
        });

        return map;
    }, [grade_level_options]);

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

    const applyFilters = (overrides: Partial<Filters> = {}) => {
        const query = {
            status: overrides.status !== undefined ? overrides.status : statusTab,
            search: overrides.search !== undefined ? overrides.search : (searchQuery || undefined),
            sort: overrides.sort !== undefined ? overrides.sort : sortFilter,
            requirements: overrides.requirements !== undefined ? overrides.requirements : requirementsFilter,
            page: undefined,
        };

        router.get(registrar.enrollment.url({ query }), {}, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setSearchQuery('');
        setSortFilter('newest');
        setRequirementsFilter('all');
        applyFilters({ search: '', sort: 'newest', requirements: 'all' });
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
            createForm.setData('student_personal_email', '');
            createForm.setData('grade_level_id', '');
            createForm.setData('section_id', '');
            setLookupGuardrail(null);
            setLookupStatusFlags(null);
            setLookupSourceContext(null);

            return;
        }

        createForm.setData('first_name', payload.student.first_name ?? '');
        createForm.setData('middle_name', payload.student.middle_name ?? '');
        createForm.setData('last_name', payload.student.last_name ?? '');
        createForm.setData('gender', payload.student.gender ?? '');
        createForm.setData('birthdate', payload.student.birthdate ?? '');
        createForm.setData('guardian_name', payload.student.guardian_name ?? '');
        createForm.setData(
            'student_personal_email',
            payload.student.student_personal_email ?? '',
        );
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
        setLookupGuardrail(payload.grade_guardrail ?? null);
        setLookupStatusFlags(payload.status_flags ?? null);
        setLookupSourceContext(payload.source_context
            ? {
                  academic_year_name: payload.source_context.academic_year_name,
                  status: payload.source_context.status,
                  grade_level_label: payload.source_context.grade_level_label,
              }
            : null);
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
                createForm.setData('report_card_submitted', false);
                createForm.setData('birth_certificate_submitted', false);
                createForm.setData('resolve_older_conditional', false);
                createForm.setData('resolve_older_retained', false);
                createForm.setData('conditional_resolution_notes', '');
                createForm.setData('retained_resolution_notes', '');
                createForm.setData('discount_id', '');
                latestLookupLrnRef.current = null;
                setLookupGuardrail(null);
                setLookupStatusFlags(null);
                setLookupSourceContext(null);
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
            student_personal_email: item.student_personal_email || '',
            grade_level_id: item.grade_level_id
                ? String(item.grade_level_id)
                : sectionGradeLevelId
                  ? String(sectionGradeLevelId)
                  : '',
            section_id: item.section_id ? String(item.section_id) : '',
            payment_term: item.payment_term,
            downpayment: String(item.downpayment ?? 0),
            report_card_submitted: item.report_card_submitted,
            birth_certificate_submitted: item.birth_certificate_submitted,
            discount_id: item.discount_id ? String(item.discount_id) : '',
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
        const numericGradeLevelId = Number(gradeLevelId || 0);
        const selectedOrder = gradeLevelOrderById.get(numericGradeLevelId) ?? null;

        if (lookupGuardrail) {
            if (
                lookupGuardrail.allowed_exact_grade_level_id !== null &&
                numericGradeLevelId !== lookupGuardrail.allowed_exact_grade_level_id
            ) {
                return;
            }

            if (
                selectedOrder !== null &&
                lookupGuardrail.min_allowed_grade_level_order !== null &&
                selectedOrder < lookupGuardrail.min_allowed_grade_level_order
            ) {
                return;
            }

            if (
                selectedOrder !== null &&
                lookupGuardrail.max_allowed_grade_level_order !== null &&
                selectedOrder > lookupGuardrail.max_allowed_grade_level_order
            ) {
                return;
            }
        }

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
    const isCreateGradeLevelDisabled = (gradeLevelId: number): boolean => {
        if (!lookupGuardrail) {
            return false;
        }

        if (
            lookupGuardrail.allowed_exact_grade_level_id !== null &&
            lookupGuardrail.allowed_exact_grade_level_id !== gradeLevelId
        ) {
            return true;
        }

        const order = gradeLevelOrderById.get(gradeLevelId) ?? null;
        if (
            order !== null &&
            lookupGuardrail.min_allowed_grade_level_order !== null &&
            order < lookupGuardrail.min_allowed_grade_level_order
        ) {
            return true;
        }

        if (
            order !== null &&
            lookupGuardrail.max_allowed_grade_level_order !== null &&
            order > lookupGuardrail.max_allowed_grade_level_order
        ) {
            return true;
        }

        return false;
    };
    const handleCreateSaveClick = () => {
        if (lookupStatusFlags?.has_older_unresolved_conditional) {
            setIsOlderConditionalDialogOpen(true);

            return;
        }

        if (lookupStatusFlags?.has_older_unresolved_retained) {
            setIsOlderRetainedDialogOpen(true);

            return;
        }

        setIsSaveConfirmOpen(true);
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

    const triggerExport = (formatType: 'xlsx' | 'csv' | 'pdf') => {
        const params = new URLSearchParams();
        params.set('format', formatType);
        window.location.assign(`/registrar/enrollment/export?${params.toString()}`);
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
            <TooltipProvider>
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
                    <Card className="h-[calc(100svh-7rem)] gap-2 overflow-hidden lg:sticky lg:top-6 lg:w-[25rem] lg:flex-none xl:w-[27rem]">
                        <CardHeader className="border-b">
                            <CardTitle>New Enrollment</CardTitle>
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
                                    <div className="space-y-2">
                                        <Label htmlFor="student-personal-email">
                                            Student Personal Email
                                        </Label>
                                        <Input
                                            id="student-personal-email"
                                            type="email"
                                            placeholder="eg. student.personal@example.com"
                                            value={createForm.data.student_personal_email}
                                            onChange={(event) =>
                                                createForm.setData(
                                                    'student_personal_email',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Used for the student account claim link. If blank, the claim link will be sent to the contact email.
                                        </p>
                                        {createForm.errors
                                            .student_personal_email && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    createForm.errors
                                                        .student_personal_email
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
                                                                key={gradeLevel.id}
                                                                value={String(gradeLevel.id)}
                                                                disabled={isCreateGradeLevelDisabled(gradeLevel.id)}
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
                                            <Label>Discount / Scholarship</Label>
                                            <Select
                                                value={createForm.data.discount_id || 'none'}
                                                onValueChange={(value) =>
                                                    createForm.setData(
                                                        'discount_id',
                                                        value === 'none' ? '' : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full min-w-0">
                                                    <SelectValue placeholder="Select discount (optional)" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">None</SelectItem>
                                                    {discount_options.map((discount) => (
                                                        <SelectItem
                                                            key={discount.id}
                                                            value={String(discount.id)}
                                                        >
                                                            {discount.name}
                                                        </SelectItem>
                                                    ))}
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

                                        <div className="space-y-2 rounded-md border p-3 text-muted-foreground">
                                            <p className="text-sm font-medium">
                                                Submitted Requirements
                                            </p>
                                            <label className="flex items-center gap-2 text-sm">
                                                <Checkbox
                                                    checked={createForm.data.report_card_submitted}
                                                    onCheckedChange={(checked) =>
                                                        createForm.setData(
                                                            'report_card_submitted',
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                Previous Grade Level Report Card
                                            </label>
                                            <label className="flex items-center gap-2 text-sm">
                                                <Checkbox
                                                    checked={createForm.data.birth_certificate_submitted}
                                                    onCheckedChange={(checked) =>
                                                        createForm.setData(
                                                            'birth_certificate_submitted',
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                Birth Certificate
                                            </label>
                                            <p className="text-xs">
                                                Enrollment can proceed even if requirements are to-follow.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {createStep === 4 && (
                                <div className="rounded-md border p-4">
                                    <h4 className="text-sm font-medium">
                                        Enrollment Summary
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
                                            Student Personal Email
                                        </p>
                                        <p>
                                            {createForm.data
                                                .student_personal_email || '-'}
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

                                        <p className="text-muted-foreground">
                                            Discount / Scholarship
                                        </p>
                                        <p>
                                            {discount_options.find(
                                                (discount) =>
                                                    String(discount.id) ===
                                                    createForm.data.discount_id,
                                            )?.name ?? '-'}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Report Card Submitted
                                        </p>
                                        <p>
                                            {createForm.data.report_card_submitted ? 'Yes' : 'No'}
                                        </p>

                                        <p className="text-muted-foreground">
                                            Birth Certificate Submitted
                                        </p>
                                        <p>
                                            {createForm.data.birth_certificate_submitted ? 'Yes' : 'No'}
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
                                        onClick={handleCreateSaveClick}
                                        disabled={
                                            createForm.processing ||
                                            intakeCreationDisabled
                                        }
                                    >
                                        Save Enrollment
                                    </Button>
                                )}
                            </div>

                            {intakeCreationDisabled && (
                                <p className="text-sm text-muted-foreground text-center">
                                    Enrollment creation is disabled for completed
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
                                        onValueChange={(val) => switchStatusTab(val)}
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
                                        onValueChange={(val) => {
                                            setSearchQuery(val);
                                            applyFilters({ search: val });
                                        }}
                                        suggestions={searchSuggestions}
                                    />

                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button variant="outline" className="gap-2">
                                                <ListFilter className="size-4" />
                                                Filters
                                                {activeFilterCount > 0 && (
                                                    <Badge variant="secondary" className="ml-1 px-1 py-0 text-[10px]">
                                                        {activeFilterCount}
                                                    </Badge>
                                                )}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-80" align="end">
                                            <div className="grid gap-4">
                                                <div className="flex items-center justify-between">
                                                    <h4 className="font-medium leading-none">Filters</h4>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-auto p-0 text-xs text-muted-foreground"
                                                        onClick={resetFilters}
                                                    >
                                                        Reset
                                                    </Button>
                                                </div>
                                                <div className="grid gap-4">
                                                    <div className="grid gap-2">
                                                        <Label>Requirements</Label>
                                                        <Select
                                                            value={requirementsFilter}
                                                            onValueChange={(val) => {
                                                                const next = val as Filters['requirements'];
                                                                setRequirementsFilter(next);
                                                                applyFilters({ requirements: next });
                                                            }}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="All Requirements" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">All Requirements</SelectItem>
                                                                <SelectItem value="missing">Missing Requirements</SelectItem>
                                                                <SelectItem value="complete">Complete Requirements</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>Sort By</Label>
                                                        <Select
                                                            value={sortFilter}
                                                            onValueChange={(val) => {
                                                                const next = val as Filters['sort'];
                                                                setSortFilter(next);
                                                                applyFilters({ sort: next });
                                                            }}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Newest First" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="newest">Newest First</SelectItem>
                                                                <SelectItem value="oldest">Oldest First</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <Button size="sm" onClick={() => applyFilters()}>
                                                        Apply Filters
                                                    </Button>
                                                </div>
                                            </div>
                                        </PopoverContent>
                                    </Popover>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="outline" className="gap-2">
                                                <Download className="size-4" />
                                                Export
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem onClick={() => triggerExport('xlsx')}>
                                                Export as Excel (.xlsx)
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => triggerExport('csv')}>
                                                Export as CSV (.csv)
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => triggerExport('pdf')}>
                                                Export as PDF (.pdf)
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
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
                                                    {item.discount_name && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {item.discount_name}
                                                        </p>
                                                    )}
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
                                                No enrollments in queue.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                            <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    {enrollments.total === 0
                                        ? 'No enrollments found.'
                                        : `Showing ${enrollments.from}-${enrollments.to} of ${enrollments.total} enrollments`}
                                </p>
                                {enrollments.links.length > 3 && (
                                <div className="flex items-center gap-2">
                                    {enrollments.links.map((link, index) => {
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
                        </CardContent>
                    </Card>
                </div>
            </div>
            </TooltipProvider>

            <Dialog
                open={!!editingItem}
                onOpenChange={(open) => !open && setEditingItem(null)}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>Edit Enrollment</DialogTitle>
                    </DialogHeader>
                    {editingItem && (
                        <div className="grid gap-6 py-4">
                            <div className="grid gap-4 sm:grid-cols-3">
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
                                    {editForm.errors.first_name && (
                                        <p className="text-xs text-destructive">
                                            {editForm.errors.first_name}
                                        </p>
                                    )}
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
                                    {editForm.errors.last_name && (
                                        <p className="text-xs text-destructive">
                                            {editForm.errors.last_name}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
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
                                            <SelectValue />
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
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
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
                                    {editForm.errors.guardian_name && (
                                        <p className="text-xs text-destructive">
                                            {editForm.errors.guardian_name}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Guardian Contact</Label>
                                    <div className="flex w-full min-w-0">
                                        <span className="inline-flex items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                            +63
                                        </span>
                                        <Input
                                            className="rounded-l-none"
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
                                        <p className="text-xs text-destructive">
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
                                    <p className="text-xs text-destructive">
                                        {editForm.errors.email}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Student Personal Email</Label>
                                <Input
                                    type="email"
                                    value={editForm.data.student_personal_email}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'student_personal_email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Used for the student claim link. If blank,
                                    the contact email is used.
                                </p>
                                {editForm.errors.student_personal_email && (
                                    <p className="text-xs text-destructive">
                                        {
                                            editForm.errors
                                                .student_personal_email
                                        }
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
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
                                            <SelectValue />
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
                                    {editForm.errors.grade_level_id && (
                                        <p className="text-xs text-destructive">
                                            {editForm.errors.grade_level_id}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Section Assignment</Label>
                                    <Select
                                        value={
                                            editForm.data.section_id ||
                                            'unassigned'
                                        }
                                        onValueChange={(value) =>
                                            editForm.setData(
                                                'section_id',
                                                value === 'unassigned'
                                                    ? ''
                                                    : value,
                                            )
                                        }
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
                                    {editForm.errors.section_id && (
                                        <p className="text-xs text-destructive">
                                            {editForm.errors.section_id}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Payment Plan</Label>
                                    <Select
                                        value={editForm.data.payment_term}
                                        onValueChange={(value) => {
                                            editForm.setData(
                                                'payment_term',
                                                value,
                                            );
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
                                        step="0.01"
                                        value={editForm.data.downpayment}
                                        disabled={
                                            editForm.data.payment_term ===
                                            'cash'
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

                            <div className="space-y-2">
                                <Label>Discount / Scholarship</Label>
                                <Select
                                    value={editForm.data.discount_id || 'none'}
                                    onValueChange={(value) =>
                                        editForm.setData(
                                            'discount_id',
                                            value === 'none' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select discount (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None</SelectItem>
                                        {discount_options.map((discount) => (
                                            <SelectItem
                                                key={discount.id}
                                                value={String(discount.id)}
                                            >
                                                {discount.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-4 rounded-md border p-4">
                                <p className="text-sm font-medium">Requirements Checklist</p>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Checkbox
                                            checked={editForm.data.report_card_submitted}
                                            onCheckedChange={(checked) =>
                                                editForm.setData(
                                                    'report_card_submitted',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        Report Card Submitted
                                    </label>
                                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Checkbox
                                            checked={editForm.data.birth_certificate_submitted}
                                            onCheckedChange={(checked) =>
                                                editForm.setData(
                                                    'birth_certificate_submitted',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        Birth Certificate Submitted
                                    </label>
                                </div>
                            </div>
                        </div>
                    )}
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

            <ActionConfirmDialog
                open={isSaveConfirmOpen}
                onOpenChange={setIsSaveConfirmOpen}
                title="Confirm New Enrollment"
                description={`Are you sure you want to enroll Juan Dela Cruz to Grade 7 - St. Paul? This will generate their financial ledger and billing schedule.`}
                confirmLabel="Confirm Enrollment"
                loading={createForm.processing}
                onConfirm={submitCreate}
            />

            <ActionConfirmDialog
                open={!!itemToRemove}
                onOpenChange={(open) => !open && setItemToRemove(null)}
                title="Remove Enrollment Record"
                description="This will permanently delete this enrollment record and all associated financial data. This action cannot be undone."
                variant="destructive"
                confirmLabel="Delete Record"
                onConfirm={submitRemove}
            />

            <Dialog
                open={isOlderConditionalDialogOpen}
                onOpenChange={setIsOlderConditionalDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Trash2 className="size-5 text-amber-500" />
                            Unresolved Conditional Status
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <p className="text-sm text-muted-foreground">
                            This student has an unresolved **Conditional** status from a previous year (
                            <span className="font-semibold text-foreground">{lookupSourceContext?.academic_year_name}</span>).
                        </p>
                        <div className="rounded-md border bg-amber-500/5 p-4 text-sm">
                            <p>To proceed with enrollment for the current year, you must acknowledge that this condition will be resolved via remedial instructions.</p>
                        </div>
                        <div className="space-y-2">
                            <Label>Resolution Acknowledgment Notes</Label>
                            <Textarea
                                placeholder="State how the previous condition is being handled..."
                                value={createForm.data.conditional_resolution_notes}
                                onChange={(e) => createForm.setData('conditional_resolution_notes', e.target.value)}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsOlderConditionalDialogOpen(false)}>Cancel</Button>
                        <Button onClick={() => {
                            createForm.setData('resolve_older_conditional', true);
                            setIsOlderConditionalDialogOpen(false);
                            setIsSaveConfirmOpen(true);
                        }}>I Acknowledge</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={isOlderRetainedDialogOpen}
                onOpenChange={setIsOlderRetainedDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Trash2 className="size-5 text-red-500" />
                            Unresolved Retained Status
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <p className="text-sm text-muted-foreground">
                            This student was **Retained** in <span className="font-semibold text-foreground">{lookupSourceContext?.grade_level_label}</span> during <span className="font-semibold text-foreground">{lookupSourceContext?.academic_year_name}</span>.
                        </p>
                        <div className="rounded-md border bg-red-500/5 p-4 text-sm">
                            <p>The student is being re-enrolled in the same grade level. Please provide a brief note to resolve the historical retention flag.</p>
                        </div>
                        <div className="space-y-2">
                            <Label>Retention Resolution Notes</Label>
                            <Textarea
                                placeholder="State reason for re-enrollment..."
                                value={createForm.data.retained_resolution_notes}
                                onChange={(e) => createForm.setData('retained_resolution_notes', e.target.value)}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsOlderRetainedDialogOpen(false)}>Cancel</Button>
                        <Button onClick={() => {
                            createForm.setData('resolve_older_retained', true);
                            setIsOlderRetainedDialogOpen(false);
                            setIsSaveConfirmOpen(true);
                        }}>Resolve & Proceed</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
        </>
    );
}
