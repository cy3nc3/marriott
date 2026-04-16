import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
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
import { cashier_panel, student_ledgers } from '@/routes/finance';
import { store_transaction } from '@/routes/finance/cashier_panel';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cashier Panel',
        href: cashier_panel().url,
    },
];

type StudentOption = {
    id: number;
    lrn: string;
    name: string;
};

type SelectedStudent = {
    id: number;
    lrn: string;
    name: string;
    grade_and_section: string;
    enrollment_status: string | null;
    payment_plan: string | null;
    stated_downpayment: number;
    remaining_balance: number;
    assessment_total_before_downpayment: number;
    remedial_case: {
        id: number;
        status: string;
        total_amount: number;
        amount_paid: number;
        balance: number;
    } | null;
};

type FeeOption = {
    id: number;
    name: string;
    type: 'assessment_fee' | 'remedial_fee';
    amount: number;
};

type InventoryOption = {
    id: number;
    name: string;
    type: string;
    price: number;
};

type PendingIntake = {
    id: number;
    student_id: number;
    lrn: string | null;
    student_name: string;
    grade_and_section: string;
    payment_plan: string | null;
    downpayment: number;
};

type PendingRemedialCase = {
    id: number;
    student_id: number;
    lrn: string | null;
    student_name: string;
    failed_subject_count: number;
    total_amount: number;
    amount_paid: number;
    balance: number;
    status: string;
};

type StudentSuggestionsResponse = {
    students: StudentOption[];
};

type OrNumberReservationResponse = {
    data: {
        token: string;
        or_number: string;
    };
};

type CurrentTransactionItem = {
    id: string;
    type: 'assessment_fee' | 'remedial_fee' | 'inventory' | 'custom';
    description: string;
    amount: number;
    fee_id: number | null;
    inventory_item_id: number | null;
};

const buildDownpaymentTransactionItem = (
    amount: number,
): CurrentTransactionItem => ({
    id: 'auto-downpayment',
    type: 'assessment_fee',
    description: 'Enrollment Downpayment',
    amount: Number(amount.toFixed(2)),
    fee_id: null,
    inventory_item_id: null,
});

interface Props {
    students: StudentOption[];
    selected_student: SelectedStudent | null;
    fee_options: FeeOption[];
    inventory_options: InventoryOption[];
    pending_intakes_count: number;
    pending_intakes: PendingIntake[];
    pending_remedial_cases_count: number;
    pending_remedial_cases: PendingRemedialCase[];
    filters: {
        search?: string;
        student_id?: string | number;
    };
}

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount || 0);

const formatPaymentPlan = (value: string | null) => {
    if (!value) {
        return '-';
    }

    if (value === 'semi-annual') {
        return 'Semi-Annual';
    }

    if (value === 'cash') {
        return 'Cash';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
};

export default function CashierPanel({
    students,
    selected_student,
    fee_options,
    inventory_options,
    pending_intakes_count,
    pending_intakes,
    pending_remedial_cases_count,
    pending_remedial_cases,
    filters,
}: Props) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedStudentId, setSelectedStudentId] = useState(
        selected_student?.id
            ? String(selected_student.id)
            : filters.student_id
              ? String(filters.student_id)
              : '',
    );
    const [transactionItems, setTransactionItems] = useState<
        CurrentTransactionItem[]
    >([]);
    const [isAddItemOpen, setIsAddItemOpen] = useState(false);
    const [isProcessDialogOpen, setIsProcessDialogOpen] = useState(false);
    const [isReservingOrNumber, setIsReservingOrNumber] = useState(false);
    const [orNumberReservationError, setOrNumberReservationError] = useState<
        string | null
    >(null);
    const [orNumberReservationToken, setOrNumberReservationToken] = useState<
        string | null
    >(null);
    const [isIntakesDialogOpen, setIsIntakesDialogOpen] = useState(false);
    const [isRemedialDialogOpen, setIsRemedialDialogOpen] = useState(false);
    const [intakeSearchQuery, setIntakeSearchQuery] = useState('');
    const [intakePage, setIntakePage] = useState(1);

    const [itemType, setItemType] = useState<
        'assessment_fee' | 'remedial_fee' | 'inventory' | 'custom'
    >(
        fee_options.length > 0
            ? fee_options[0].type
            : inventory_options.length > 0
              ? 'inventory'
              : 'custom',
    );
    const [selectedFeeId, setSelectedFeeId] = useState<string>(
        fee_options[0] ? String(fee_options[0].id) : '',
    );
    const [selectedInventoryId, setSelectedInventoryId] = useState<string>(
        inventory_options[0] ? String(inventory_options[0].id) : '',
    );
    const [itemDescription, setItemDescription] = useState('');
    const [itemAmount, setItemAmount] = useState('');
    const reserveOrNumberControllerRef = useRef<AbortController | null>(null);

    const transactionForm = useForm({
        student_id: '',
        reservation_token: '',
        or_number: '',
        payment_mode: 'cash',
        reference_no: '',
        remarks: '',
        tendered_amount: '',
        items: [] as Array<{
            type: 'assessment_fee' | 'remedial_fee' | 'inventory' | 'custom';
            description: string;
            amount: number;
            fee_id?: number;
            inventory_item_id?: number;
        }>,
    });

    const totalAmount = useMemo(() => {
        return transactionItems.reduce((sum, item) => sum + item.amount, 0);
    }, [transactionItems]);
    const intakePageSize = 8;

    const filteredPendingIntakes = useMemo(() => {
        const normalizedQuery = intakeSearchQuery.trim().toLowerCase();

        if (normalizedQuery === '') {
            return pending_intakes;
        }

        return pending_intakes.filter((intake) => {
            const studentName = intake.student_name.toLowerCase();
            const lrn = (intake.lrn ?? '').toLowerCase();
            const gradeAndSection = intake.grade_and_section.toLowerCase();

            return (
                studentName.includes(normalizedQuery) ||
                lrn.includes(normalizedQuery) ||
                gradeAndSection.includes(normalizedQuery)
            );
        });
    }, [intakeSearchQuery, pending_intakes]);

    const intakeTotalPages = useMemo(() => {
        return Math.max(
            1,
            Math.ceil(filteredPendingIntakes.length / intakePageSize),
        );
    }, [filteredPendingIntakes.length]);

    const paginatedPendingIntakes = useMemo(() => {
        const pageStart = (intakePage - 1) * intakePageSize;

        return filteredPendingIntakes.slice(
            pageStart,
            pageStart + intakePageSize,
        );
    }, [filteredPendingIntakes, intakePage]);

    const intakeRangeStart =
        filteredPendingIntakes.length === 0
            ? 0
            : (intakePage - 1) * intakePageSize + 1;
    const intakeRangeEnd = Math.min(
        intakePage * intakePageSize,
        filteredPendingIntakes.length,
    );

    const assessmentFeeOptions = useMemo(
        () => fee_options.filter((fee) => fee.type === 'assessment_fee'),
        [fee_options],
    );

    const remedialFeeOptions = useMemo(
        () => fee_options.filter((fee) => fee.type === 'remedial_fee'),
        [fee_options],
    );

    const selectedInventory = useMemo(() => {
        const parsedInventoryId = Number(selectedInventoryId);

        return (
            inventory_options.find(
                (inventoryItem) => inventoryItem.id === parsedInventoryId,
            ) ?? null
        );
    }, [inventory_options, selectedInventoryId]);

    const runSearch = (search = searchQuery, studentId = selectedStudentId) => {
        router.get(
            cashier_panel.url({
                query: {
                    search: search || undefined,
                    student_id: studentId || undefined,
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

    const baseSearchSuggestions = useMemo(
        () =>
            students.map((student) => ({
                id: student.id,
                label: student.name,
                value: student.name,
                description: `LRN: ${student.lrn}`,
                keywords: student.lrn,
            })),
        [students],
    );
    const [searchSuggestions, setSearchSuggestions] = useState(
        baseSearchSuggestions,
    );

    useEffect(() => {
        setSearchSuggestions(baseSearchSuggestions);
    }, [baseSearchSuggestions]);

    useEffect(() => {
        setIntakePage(1);
    }, [intakeSearchQuery]);

    useEffect(() => {
        if (intakePage > intakeTotalPages) {
            setIntakePage(intakeTotalPages);
        }
    }, [intakePage, intakeTotalPages]);

    useEffect(() => {
        if (!selected_student) {
            setTransactionItems([]);

            return;
        }

        const statedDownpayment = Number(selected_student.stated_downpayment);
        const shouldAutoSetDownpaymentItem =
            selected_student.enrollment_status === 'for_cashier_payment' &&
            Number.isFinite(statedDownpayment) &&
            statedDownpayment > 0;

        if (!shouldAutoSetDownpaymentItem) {
            setTransactionItems([]);

            return;
        }

        setTransactionItems([
            buildDownpaymentTransactionItem(statedDownpayment),
        ]);
    }, [
        selected_student?.id,
        selected_student?.enrollment_status,
        selected_student?.stated_downpayment,
    ]);

    useEffect(() => {
        const normalizedQuery = searchQuery.trim();
        if (normalizedQuery.length < 2) {
            setSearchSuggestions([]);
            return;
        }

        const controller = new AbortController();
        const timeoutId = window.setTimeout(async () => {
            try {
                const response = await fetch(
                    `${cashier_panel.url()}/student-suggestions?search=${encodeURIComponent(normalizedQuery)}`,
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    return;
                }

                const payload =
                    (await response.json()) as StudentSuggestionsResponse;
                setSearchSuggestions(
                    payload.students.map((student) => ({
                        id: student.id,
                        label: student.name,
                        value: student.name,
                        description: `LRN: ${student.lrn}`,
                        keywords: student.lrn,
                    })),
                );
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }
            }
        }, 350);

        return () => {
            window.clearTimeout(timeoutId);
            controller.abort();
        };
    }, [baseSearchSuggestions, searchQuery]);

    const selectIntakeForTransaction = (intake: PendingIntake) => {
        const nextStudentId = String(intake.student_id);
        setIsIntakesDialogOpen(false);
        setSearchQuery(intake.student_name);
        setSelectedStudentId(nextStudentId);
        runSearch(intake.student_name, nextStudentId);
    };

    const selectRemedialCaseForTransaction = (
        remedialCase: PendingRemedialCase,
    ) => {
        const nextStudentId = String(remedialCase.student_id);
        setIsRemedialDialogOpen(false);
        setSearchQuery(remedialCase.student_name);
        setSelectedStudentId(nextStudentId);
        runSearch(remedialCase.student_name, nextStudentId);
    };

    const openAddItemDialog = () => {
        if (fee_options.length > 0) {
            const defaultFee = fee_options[0];

            setItemType(defaultFee.type);
            setSelectedFeeId(String(defaultFee.id));
            setItemDescription(defaultFee.name);
            setItemAmount(String(defaultFee.amount));
        } else if (inventory_options.length > 0) {
            const defaultInventory = inventory_options[0];

            setItemType('inventory');
            setSelectedInventoryId(String(defaultInventory.id));
            setItemDescription(defaultInventory.name);
            setItemAmount(String(defaultInventory.price));
        } else {
            setItemType('custom');
            setItemDescription('');
            setItemAmount('');
        }

        setIsAddItemOpen(true);
    };

    const applyFeeSelection = (value: string) => {
        setSelectedFeeId(value);
        const option = fee_options.find((fee) => fee.id === Number(value));
        if (option) {
            setItemType(option.type);
            setItemDescription(option.name);
            setItemAmount(String(option.amount));
        }
    };

    const applyInventorySelection = (value: string) => {
        setSelectedInventoryId(value);
        const option = inventory_options.find(
            (inventoryItem) => inventoryItem.id === Number(value),
        );
        if (option) {
            setItemDescription(option.name);
            setItemAmount(String(option.price));
        }
    };

    const addItemToCurrentTransaction = () => {
        const parsedAmount = Number(itemAmount);
        if (!itemDescription.trim() || !Number.isFinite(parsedAmount)) {
            return;
        }

        if (parsedAmount <= 0) {
            return;
        }

        setTransactionItems((previousItems) => [
            ...previousItems,
            {
                id: `${Date.now()}-${Math.random()}`,
                type: itemType,
                description: itemDescription.trim(),
                amount: Number(parsedAmount.toFixed(2)),
                fee_id:
                    itemType === 'assessment_fee'
                        ? Number(selectedFeeId)
                        : null,
                inventory_item_id:
                    itemType === 'inventory'
                        ? Number(selectedInventoryId)
                        : null,
            },
        ]);

        setIsAddItemOpen(false);
        setItemDescription('');
        setItemAmount('');
    };

    const removeTransactionItem = (itemId: string) => {
        setTransactionItems((previousItems) =>
            previousItems.filter((item) => item.id !== itemId),
        );
    };

    const releaseOrNumberReservation = async (
        reservationToken: string,
    ): Promise<void> => {
        try {
            await fetch(
                `${cashier_panel.url()}/or-number-reservations/${reservationToken}`,
                {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );
        } catch {
            // ignore release failures and let timeout-based expiration handle cleanup
        }
    };

    const reserveOrNumberReservation = async (): Promise<void> => {
        if (isReservingOrNumber) {
            return;
        }

        reserveOrNumberControllerRef.current?.abort();
        const controller = new AbortController();
        reserveOrNumberControllerRef.current = controller;

        setIsReservingOrNumber(true);
        setOrNumberReservationError(null);

        try {
            const response = await fetch(
                `${cashier_panel.url()}/or-number-reservations`,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                },
            );

            if (!response.ok) {
                throw new Error('Reservation request failed');
            }

            const payload =
                (await response.json()) as OrNumberReservationResponse;

            setOrNumberReservationToken(payload.data.token);
            transactionForm.setData('reservation_token', payload.data.token);
            transactionForm.setData('or_number', payload.data.or_number);
        } catch (error) {
            if (
                error instanceof DOMException &&
                error.name === 'AbortError'
            ) {
                return;
            }

            setOrNumberReservationError(
                'Unable to reserve an OR number right now. You can still enter one manually.',
            );
        } finally {
            if (reserveOrNumberControllerRef.current === controller) {
                reserveOrNumberControllerRef.current = null;
            }

            setIsReservingOrNumber(false);
        }
    };

    const closeProcessDialog = (): void => {
        reserveOrNumberControllerRef.current?.abort();
        reserveOrNumberControllerRef.current = null;

        const tokenToRelease = orNumberReservationToken;
        setOrNumberReservationToken(null);
        setOrNumberReservationError(null);
        transactionForm.setData('reservation_token', '');
        setIsProcessDialogOpen(false);

        if (tokenToRelease) {
            void releaseOrNumberReservation(tokenToRelease);
        }
    };

    const openProcessDialog = () => {
        if (!selected_student || transactionItems.length === 0) {
            return;
        }

        transactionForm.clearErrors();
        transactionForm.setData({
            student_id: String(selected_student.id),
            reservation_token: '',
            or_number: '',
            payment_mode: 'cash',
            reference_no: '',
            remarks: '',
            tendered_amount: String(Number(totalAmount.toFixed(2))),
            items: [],
        });
        setOrNumberReservationToken(null);
        setOrNumberReservationError(null);
        setIsProcessDialogOpen(true);
        void reserveOrNumberReservation();
    };

    const postTransaction = () => {
        if (!selected_student || transactionItems.length === 0) {
            return;
        }

        const payloadItems = transactionItems.map((item) => ({
            type: item.type,
            description: item.description,
            amount: Number(item.amount.toFixed(2)),
            ...(item.fee_id ? { fee_id: item.fee_id } : {}),
            ...(item.inventory_item_id
                ? { inventory_item_id: item.inventory_item_id }
                : {}),
        }));

        transactionForm.transform((formData) => ({
            ...formData,
            student_id: Number(selected_student.id),
            tendered_amount: Number(formData.tendered_amount || 0),
            items: payloadItems,
        }));

        transactionForm.submit(store_transaction(), {
            preserveScroll: true,
            onSuccess: () => {
                reserveOrNumberControllerRef.current?.abort();
                reserveOrNumberControllerRef.current = null;

                setOrNumberReservationToken(null);
                transactionForm.setData('reservation_token', '');
                setOrNumberReservationError(null);
                setIsProcessDialogOpen(false);
                setTransactionItems([]);
                transactionForm.reset();
                transactionForm.setData('payment_mode', 'cash');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashier Panel" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <CardTitle>Student Lookup</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="flex gap-2">
                            <SearchAutocompleteInput
                                wrapperClassName="flex-1"
                                placeholder="Search by LRN or student name"
                                value={searchQuery}
                                onValueChange={setSearchQuery}
                                suggestions={searchSuggestions}
                                onEnterPress={() => {
                                    setSelectedStudentId('');
                                    runSearch(searchQuery, '');
                                }}
                                onSelectSuggestion={(option) => {
                                    const selectedId = String(option.id);
                                    setSearchQuery(
                                        option.value ?? option.label,
                                    );
                                    setSelectedStudentId(selectedId);
                                    runSearch(
                                        option.value ?? option.label,
                                        selectedId,
                                    );
                                }}
                            />

                            <Button
                                type="button"
                                onClick={() => {
                                    setSelectedStudentId('');
                                    runSearch(searchQuery, '');
                                }}
                            >
                                Search
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsIntakesDialogOpen(true)}
                            >
                                Enrollment Intakes ({pending_intakes_count})
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsRemedialDialogOpen(true)}
                            >
                                Remedial Intakes ({pending_remedial_cases_count}
                                )
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="gap-2 lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>Student Profile</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-6">
                            {selected_student ? (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Name
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.name}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                LRN
                                            </p>
                                            <p className="text-sm font-medium">
                                                {selected_student.lrn}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-sm text-muted-foreground">
                                            Grade and Section
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selected_student.grade_and_section}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-sm text-muted-foreground">
                                            Payment Plan
                                        </p>
                                        <p className="text-sm font-medium">
                                            {formatPaymentPlan(
                                                selected_student.payment_plan,
                                            )}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-sm text-muted-foreground">
                                            Total Assessment (Before
                                            Downpayment)
                                        </p>
                                        <p className="text-lg font-semibold">
                                            {formatCurrency(
                                                selected_student.assessment_total_before_downpayment,
                                            )}
                                        </p>
                                    </div>
                                    {selected_student.remedial_case ? (
                                        <div className="space-y-1">
                                            <p className="text-sm text-muted-foreground">
                                                Remedial Balance
                                            </p>
                                            <p className="text-sm font-medium">
                                                {formatCurrency(
                                                    selected_student
                                                        .remedial_case.balance,
                                                )}{' '}
                                                (
                                                {selected_student.remedial_case.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                                )
                                            </p>
                                        </div>
                                    ) : null}
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        asChild
                                    >
                                        <Link href={student_ledgers()}>
                                            View Ledger
                                        </Link>
                                    </Button>
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Search a student to begin a transaction.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex items-center justify-between gap-2">
                                <CardTitle>Current Transaction</CardTitle>
                                <Button
                                    size="sm"
                                    onClick={openAddItemDialog}
                                    disabled={!selected_student}
                                >
                                    <Plus className="size-4" />
                                    Add Item
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Item
                                        </TableHead>
                                        <TableHead className="border-l">
                                            Type
                                        </TableHead>
                                        <TableHead className="border-l text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactionItems.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="pl-6">
                                                {item.description}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {item.type === 'assessment_fee'
                                                    ? 'Assessment Fee'
                                                    : item.type === 'inventory'
                                                      ? 'Inventory'
                                                      : 'Custom'}
                                            </TableCell>
                                            <TableCell className="border-l text-right">
                                                {formatCurrency(item.amount)}
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    onClick={() =>
                                                        removeTransactionItem(
                                                            item.id,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {transactionItems.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                Add items to start this
                                                transaction.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                        <div className="flex items-center justify-between border-t px-6 py-4">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Total Amount
                                </p>
                                <p className="text-lg font-semibold">
                                    {formatCurrency(totalAmount)}
                                </p>
                            </div>
                            <Button
                                onClick={openProcessDialog}
                                disabled={
                                    !selected_student ||
                                    transactionItems.length === 0
                                }
                            >
                                Process Transaction
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>

            <Dialog
                open={isIntakesDialogOpen}
                onOpenChange={setIsIntakesDialogOpen}
            >
                <DialogContent className="sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Enrollment Intakes Queue</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <Input
                            placeholder="Search student, LRN, or section"
                            value={intakeSearchQuery}
                            onChange={(event) =>
                                setIntakeSearchQuery(event.target.value)
                            }
                        />
                    </div>
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    <TableHead className="border-l">
                                        LRN
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Grade and Section
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Payment Plan
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Downpayment
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginatedPendingIntakes.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            {pending_intakes.length === 0
                                                ? 'No intakes pending cashier payment.'
                                                : 'No intake matches your search.'}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    paginatedPendingIntakes.map((intake) => (
                                        <TableRow key={intake.id}>
                                            <TableCell className="pl-6 font-medium">
                                                {intake.student_name}
                                            </TableCell>
                                            <TableCell className="border-l text-muted-foreground">
                                                {intake.lrn ?? '-'}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {intake.grade_and_section}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {formatPaymentPlan(
                                                    intake.payment_plan,
                                                )}
                                            </TableCell>
                                            <TableCell className="border-l text-right">
                                                {formatCurrency(
                                                    intake.downpayment,
                                                )}
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        selectIntakeForTransaction(
                                                            intake,
                                                        )
                                                    }
                                                >
                                                    Select
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {intakeRangeStart}-{intakeRangeEnd} of{' '}
                            {filteredPendingIntakes.length} entries
                        </p>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setIntakePage((previousPage) =>
                                        Math.max(previousPage - 1, 1),
                                    )
                                }
                                disabled={intakePage <= 1}
                            >
                                Previous
                            </Button>
                            <p className="text-sm text-muted-foreground">
                                Page {intakePage} of {intakeTotalPages}
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setIntakePage((previousPage) =>
                                        Math.min(
                                            previousPage + 1,
                                            intakeTotalPages,
                                        ),
                                    )
                                }
                                disabled={intakePage >= intakeTotalPages}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog
                open={isRemedialDialogOpen}
                onOpenChange={setIsRemedialDialogOpen}
            >
                <DialogContent className="sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Remedial Intakes Queue</DialogTitle>
                    </DialogHeader>
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    <TableHead className="border-l">
                                        LRN
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Failed Subjects
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Amount Due
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Paid
                                    </TableHead>
                                    <TableHead className="border-l text-right">
                                        Balance
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pending_remedial_cases.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No remedial intakes pending payment.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    pending_remedial_cases.map(
                                        (remedialCase) => (
                                            <TableRow key={remedialCase.id}>
                                                <TableCell className="pl-6 font-medium">
                                                    {remedialCase.student_name}
                                                </TableCell>
                                                <TableCell className="border-l text-muted-foreground">
                                                    {remedialCase.lrn ?? '-'}
                                                </TableCell>
                                                <TableCell className="border-l">
                                                    {
                                                        remedialCase.failed_subject_count
                                                    }
                                                </TableCell>
                                                <TableCell className="border-l text-right">
                                                    {formatCurrency(
                                                        remedialCase.total_amount,
                                                    )}
                                                </TableCell>
                                                <TableCell className="border-l text-right">
                                                    {formatCurrency(
                                                        remedialCase.amount_paid,
                                                    )}
                                                </TableCell>
                                                <TableCell className="border-l text-right">
                                                    {formatCurrency(
                                                        remedialCase.balance,
                                                    )}
                                                </TableCell>
                                                <TableCell className="border-l pr-6 text-right">
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            selectRemedialCaseForTransaction(
                                                                remedialCase,
                                                            )
                                                        }
                                                    >
                                                        Select
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ),
                                    )
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={isAddItemOpen} onOpenChange={setIsAddItemOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add Item</DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="space-y-2">
                            <Label>Item Type</Label>
                            <Select
                                value={itemType}
                                onValueChange={(
                                    value:
                                        | 'assessment_fee'
                                        | 'remedial_fee'
                                        | 'inventory'
                                        | 'custom',
                                ) => {
                                    setItemType(value);

                                    if (
                                        value === 'assessment_fee' &&
                                        assessmentFeeOptions.length > 0
                                    ) {
                                        const defaultAssessmentFee =
                                            assessmentFeeOptions[0];
                                        setSelectedFeeId(
                                            String(defaultAssessmentFee.id),
                                        );
                                        setItemDescription(
                                            defaultAssessmentFee.name,
                                        );
                                        setItemAmount(
                                            String(defaultAssessmentFee.amount),
                                        );
                                    }

                                    if (
                                        value === 'remedial_fee' &&
                                        remedialFeeOptions.length > 0
                                    ) {
                                        const defaultRemedialFee =
                                            remedialFeeOptions[0];
                                        setSelectedFeeId(
                                            String(defaultRemedialFee.id),
                                        );
                                        setItemDescription(
                                            defaultRemedialFee.name,
                                        );
                                        setItemAmount(
                                            String(defaultRemedialFee.amount),
                                        );
                                    }

                                    if (
                                        value === 'inventory' &&
                                        selectedInventory
                                    ) {
                                        setItemDescription(
                                            selectedInventory.name,
                                        );
                                        setItemAmount(
                                            String(selectedInventory.price),
                                        );
                                    }

                                    if (value === 'custom') {
                                        setItemDescription('');
                                        setItemAmount('');
                                    }
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {assessmentFeeOptions.length > 0 && (
                                        <SelectItem value="assessment_fee">
                                            Assessment Fee
                                        </SelectItem>
                                    )}
                                    {remedialFeeOptions.length > 0 && (
                                        <SelectItem value="remedial_fee">
                                            Remedial Fee
                                        </SelectItem>
                                    )}
                                    <SelectItem value="inventory">
                                        Inventory
                                    </SelectItem>
                                    <SelectItem value="custom">
                                        Custom
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {(itemType === 'assessment_fee' ||
                            itemType === 'remedial_fee') && (
                            <div className="space-y-2">
                                <Label>
                                    {itemType === 'assessment_fee'
                                        ? 'Assessment Item'
                                        : 'Remedial Item'}
                                </Label>
                                <Select
                                    value={selectedFeeId}
                                    onValueChange={applyFeeSelection}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select fee item" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(itemType === 'assessment_fee'
                                            ? assessmentFeeOptions
                                            : remedialFeeOptions
                                        ).map((fee) => (
                                            <SelectItem
                                                key={fee.id}
                                                value={String(fee.id)}
                                            >
                                                {fee.name} (
                                                {formatCurrency(fee.amount)})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {itemType === 'inventory' && (
                            <div className="space-y-2">
                                <Label>Inventory Item</Label>
                                <Select
                                    value={selectedInventoryId}
                                    onValueChange={applyInventorySelection}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select inventory item" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {inventory_options.map(
                                            (inventoryItem) => (
                                                <SelectItem
                                                    key={inventoryItem.id}
                                                    value={String(
                                                        inventoryItem.id,
                                                    )}
                                                >
                                                    {inventoryItem.name} (
                                                    {formatCurrency(
                                                        inventoryItem.price,
                                                    )}
                                                    )
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label>Description</Label>
                            <Input
                                value={itemDescription}
                                disabled={itemType !== 'custom'}
                                onChange={(event) =>
                                    setItemDescription(event.target.value)
                                }
                                placeholder="Description"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Amount</Label>
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={itemAmount}
                                onChange={(event) =>
                                    setItemAmount(event.target.value)
                                }
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsAddItemOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button onClick={addItemToCurrentTransaction}>
                            Add to Transaction
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={isProcessDialogOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        closeProcessDialog();
                    }
                }}
            >
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Process Transaction</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="rounded-md border p-4">
                            <p className="text-sm font-medium">
                                Transaction Summary
                            </p>
                            <div className="mt-3 space-y-2">
                                {transactionItems.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <span className="text-muted-foreground">
                                            {item.description}
                                        </span>
                                        <span>
                                            {formatCurrency(item.amount)}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex items-center justify-between border-t pt-2 text-sm font-medium">
                                    <span>Total</span>
                                    <span>{formatCurrency(totalAmount)}</span>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="tendered-amount">
                                    Tendered Amount
                                </Label>
                                <Input
                                    id="tendered-amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={transactionForm.data.tendered_amount}
                                    onChange={(event) =>
                                        transactionForm.setData(
                                            'tendered_amount',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="0.00"
                                />
                                {transactionForm.errors.tendered_amount && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.tendered_amount}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="payment-method">
                                    Payment Method
                                </Label>
                                <Select
                                    value={transactionForm.data.payment_mode}
                                    onValueChange={(value) =>
                                        transactionForm.setData(
                                            'payment_mode',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger id="payment-method">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">
                                            Cash
                                        </SelectItem>
                                        <SelectItem value="gcash">
                                            GCash
                                        </SelectItem>
                                        <SelectItem value="bank_transfer">
                                            Bank Transfer
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {transactionForm.errors.payment_mode && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.payment_mode}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="or-number">OR Number</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        disabled={
                                            transactionForm.processing ||
                                            isReservingOrNumber
                                        }
                                        onClick={() => {
                                            const tokenToRelease =
                                                orNumberReservationToken;

                                            setOrNumberReservationToken(null);
                                            transactionForm.setData(
                                                'reservation_token',
                                                '',
                                            );
                                            setOrNumberReservationError(null);

                                            if (tokenToRelease) {
                                                void releaseOrNumberReservation(
                                                    tokenToRelease,
                                                );
                                            }

                                            void reserveOrNumberReservation();
                                        }}
                                    >
                                        {isReservingOrNumber
                                            ? 'Generating...'
                                            : 'Regenerate'}
                                    </Button>
                                </div>
                                <Input
                                    id="or-number"
                                    value={transactionForm.data.or_number}
                                    onChange={(event) =>
                                        transactionForm.setData(
                                            'or_number',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="OR-2026-0001"
                                />
                                {transactionForm.errors.or_number && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.or_number}
                                    </p>
                                )}
                                {orNumberReservationError && (
                                    <p className="text-sm text-muted-foreground">
                                        {orNumberReservationError}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reference-no">
                                    Reference Number
                                </Label>
                                <Input
                                    id="reference-no"
                                    value={transactionForm.data.reference_no}
                                    onChange={(event) =>
                                        transactionForm.setData(
                                            'reference_no',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Optional"
                                />
                                {transactionForm.errors.reference_no && (
                                    <p className="text-sm text-destructive">
                                        {transactionForm.errors.reference_no}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="remarks">Remarks</Label>
                            <Input
                                id="remarks"
                                value={transactionForm.data.remarks}
                                onChange={(event) =>
                                    transactionForm.setData(
                                        'remarks',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional note"
                            />
                            {transactionForm.errors.remarks && (
                                <p className="text-sm text-destructive">
                                    {transactionForm.errors.remarks}
                                </p>
                            )}
                            {transactionForm.errors.items && (
                                <p className="text-sm text-destructive">
                                    {transactionForm.errors.items}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={closeProcessDialog}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={postTransaction}
                            disabled={
                                transactionForm.processing || isReservingOrNumber
                            }
                        >
                            Confirm and Post
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
