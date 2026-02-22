import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
    last_name: string;
    emergency_contact: string;
    payment_term: string;
    downpayment: number;
    status: string;
}

interface Props {
    enrollments: EnrollmentRow[];
    summary: {
        pending_intake: number;
        for_cashier_payment: number;
        partial_payment: number;
    };
    filters: {
        search?: string;
    };
}

export default function Enrollment({ enrollments, summary, filters }: Props) {
    const [editingItem, setEditingItem] = useState<EnrollmentRow | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const createForm = useForm({
        lrn: '',
        first_name: '',
        last_name: '',
        emergency_contact: '',
        payment_term: 'monthly',
        downpayment: '',
    });

    const editForm = useForm({
        first_name: '',
        last_name: '',
        emergency_contact: '',
        payment_term: 'monthly',
        downpayment: '',
        status: 'pending_intake',
    });

    const applySearch = (value: string) => {
        setSearchQuery(value);
        router.get(
            registrar.enrollment.url({
                query: {
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
                createForm.reset();
                createForm.setData('payment_term', 'monthly');
            },
        });
    };

    const openEdit = (item: EnrollmentRow) => {
        setEditingItem(item);
        editForm.setData({
            first_name: item.first_name,
            last_name: item.last_name,
            emergency_contact: item.emergency_contact || '',
            payment_term: item.payment_term,
            downpayment: String(item.downpayment ?? 0),
            status: normalizeStatus(item.status),
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

    const removeRow = (item: EnrollmentRow) => {
        if (
            !confirm(`Remove ${item.first_name} ${item.last_name} from queue?`)
        ) {
            return;
        }

        router.delete(destroy(item.id).url, {
            preserveScroll: true,
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

    const normalizeStatus = (status: string) => {
        if (status === 'pending') return 'pending_intake';

        return status;
    };

    const statusBadge = (status: string) => {
        const normalized = normalizeStatus(status);
        const labelMap: Record<string, string> = {
            pending_intake: 'Pending Intake',
            for_cashier_payment: 'For Cashier Payment',
            partial_payment: 'Partial Payment',
        };

        return (
            <Badge variant="outline">
                {labelMap[normalized] || normalized}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Enrollment" />

            <div className="flex flex-col gap-6">
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="gap-2 lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>New Enrollment Intake</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-6">
                            <div className="space-y-2">
                                <Label htmlFor="lrn">LRN</Label>
                                <Input
                                    id="lrn"
                                    placeholder="12-digit LRN"
                                    value={createForm.data.lrn}
                                    onChange={(event) =>
                                        createForm.setData(
                                            'lrn',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="first-name">
                                        First Name
                                    </Label>
                                    <Input
                                        id="first-name"
                                        placeholder="Juan"
                                        value={createForm.data.first_name}
                                        onChange={(event) =>
                                            createForm.setData(
                                                'first_name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="last-name">Last Name</Label>
                                    <Input
                                        id="last-name"
                                        placeholder="Dela Cruz"
                                        value={createForm.data.last_name}
                                        onChange={(event) =>
                                            createForm.setData(
                                                'last_name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="emergency-contact">
                                    Emergency Contact
                                </Label>
                                <Input
                                    id="emergency-contact"
                                    placeholder="0917 123 4567"
                                    value={createForm.data.emergency_contact}
                                    onChange={(event) =>
                                        createForm.setData(
                                            'emergency_contact',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>Payment Plan</Label>
                                <Select
                                    value={createForm.data.payment_term}
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
                                <Label htmlFor="downpayment">Downpayment</Label>
                                <Input
                                    id="downpayment"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    value={createForm.data.downpayment}
                                    disabled={
                                        createForm.data.payment_term === 'cash'
                                    }
                                    onChange={(event) =>
                                        createForm.setData(
                                            'downpayment',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <Button
                                className="w-full"
                                onClick={submitCreate}
                                disabled={createForm.processing}
                            >
                                Save Enrollment Intake
                            </Button>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3">
                                <CardTitle>Enrollment Queue</CardTitle>
                                <div className="flex flex-wrap items-center gap-2 text-sm">
                                    <Badge variant="outline">
                                        Pending Intake: {summary.pending_intake}
                                    </Badge>
                                    <Badge variant="outline">
                                        For Cashier Payment:{' '}
                                        {summary.for_cashier_payment}
                                    </Badge>
                                    <Badge variant="outline">
                                        Partial Payment:{' '}
                                        {summary.partial_payment}
                                    </Badge>
                                </div>
                                <div className="relative w-full max-w-sm">
                                    <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search by LRN or name..."
                                        className="pl-9"
                                        value={searchQuery}
                                        onChange={(event) =>
                                            applySearch(event.target.value)
                                        }
                                    />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            LRN
                                        </TableHead>
                                        <TableHead>Student</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Downpayment</TableHead>
                                        <TableHead>Cashier Status</TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {enrollments.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="pl-6">
                                                {item.lrn}
                                            </TableCell>
                                            <TableCell>
                                                {item.first_name}{' '}
                                                {item.last_name}
                                            </TableCell>
                                            <TableCell>
                                                {formatPaymentTerm(
                                                    item.payment_term,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatCurrency(
                                                    item.downpayment,
                                                )}
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
                                                            removeRow(item)
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
                                                colSpan={6}
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
                        <div className="grid grid-cols-2 gap-4">
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

                        <div className="space-y-2">
                            <Label>Emergency Contact</Label>
                            <Input
                                value={editForm.data.emergency_contact}
                                onChange={(event) =>
                                    editForm.setData(
                                        'emergency_contact',
                                        event.target.value,
                                    )
                                }
                            />
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

                        <div className="space-y-2">
                            <Label>Status</Label>
                            <Select
                                value={editForm.data.status}
                                onValueChange={(value) =>
                                    editForm.setData('status', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending_intake">
                                        Pending Intake
                                    </SelectItem>
                                    <SelectItem value="for_cashier_payment">
                                        For Cashier Payment
                                    </SelectItem>
                                    <SelectItem value="partial_payment">
                                        Partial Payment
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
    );
}
