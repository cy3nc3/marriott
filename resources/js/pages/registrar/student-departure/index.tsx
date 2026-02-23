import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Search, UserMinus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Departure',
        href: '/registrar/student-departure',
    },
];

type LookupStudent = {
    id: number;
    lrn: string;
    name: string;
};

type SelectedStudent = {
    id: number;
    name: string;
    lrn: string;
    grade_and_section: string;
    enrollment_status: string | null;
    academic_year: string | null;
    enrollment_id: number | null;
    account_expires_at: string | null;
};

type DepartureLogRow = {
    id: number;
    student_name: string;
    lrn: string;
    school_year: string | null;
    reason: string;
    effective_date: string | null;
    account_expires_at: string | null;
    processed_by: string;
};

interface Props {
    student_lookup: LookupStudent[];
    selected_student: SelectedStudent | null;
    departure_form_defaults: {
        reason: 'transfer_out' | 'dropped_out';
        effective_date: string;
        remarks: string;
    };
    recent_departures: DepartureLogRow[];
    filters: {
        search: string | null;
        student_id: number | null;
    };
}

export default function StudentDeparture({
    student_lookup,
    selected_student,
    departure_form_defaults,
    recent_departures,
    filters,
}: Props) {
    const [searchValue, setSearchValue] = useState(filters.search ?? '');
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);

    const departureForm = useForm({
        student_id: selected_student?.id ?? 0,
        enrollment_id: selected_student?.enrollment_id ?? 0,
        reason: departure_form_defaults.reason,
        effective_date: departure_form_defaults.effective_date,
        remarks: departure_form_defaults.remarks,
    });

    useEffect(() => {
        departureForm.setData({
            student_id: selected_student?.id ?? 0,
            enrollment_id: selected_student?.enrollment_id ?? 0,
            reason: departure_form_defaults.reason,
            effective_date: departure_form_defaults.effective_date,
            remarks: departure_form_defaults.remarks,
        });
    }, [selected_student, departure_form_defaults]);

    const applyLookupFilters = (next?: {
        search?: string;
        studentId?: number | null;
    }) => {
        const querySearch = next?.search ?? searchValue;
        const queryStudentId = next?.studentId ?? selected_student?.id ?? null;

        router.get(
            '/registrar/student-departure',
            {
                search: querySearch || undefined,
                student_id: queryStudentId || undefined,
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const processDeparture = () => {
        departureForm.post('/registrar/student-departure', {
            preserveScroll: true,
            onSuccess: () => {
                setConfirmDialogOpen(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Departure" />

            <div className="flex flex-col gap-6">
                <Card className="gap-2">
                    <CardHeader className="border-b">
                        <CardTitle>Student Lookup</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-[1fr_300px_auto]">
                            <div className="space-y-2">
                                <Label>Search</Label>
                                <Input
                                    placeholder="Search by LRN or student name"
                                    value={searchValue}
                                    onChange={(event) =>
                                        setSearchValue(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            applyLookupFilters();
                                        }
                                    }}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Selected Learner</Label>
                                <Select
                                    value={
                                        selected_student
                                            ? String(selected_student.id)
                                            : undefined
                                    }
                                    onValueChange={(value) =>
                                        applyLookupFilters({
                                            studentId: Number(value),
                                        })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select learner" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {student_lookup.map((student) => (
                                            <SelectItem
                                                key={student.id}
                                                value={String(student.id)}
                                            >
                                                {student.name} ({student.lrn})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    variant="outline"
                                    onClick={() => applyLookupFilters()}
                                >
                                    <Search className="size-4" />
                                    Search
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="gap-2">
                        <CardHeader className="border-b">
                            <CardTitle>Selected Student</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 pt-6">
                            {!selected_student ? (
                                <p className="text-sm text-muted-foreground">
                                    Select a learner to process departure.
                                </p>
                            ) : (
                                <>
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            Name
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selected_student.name}
                                        </p>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            LRN
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selected_student.lrn}
                                        </p>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            Grade and Section
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selected_student.grade_and_section}
                                        </p>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            Enrollment Status
                                        </p>
                                        <Badge variant="outline">
                                            {selected_student.enrollment_status ??
                                                'Unknown'}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            Account Expiry
                                        </p>
                                        <p className="text-sm font-medium">
                                            {selected_student.account_expires_at ??
                                                'Not set'}
                                        </p>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="gap-2 lg:col-span-2">
                        <CardHeader className="border-b">
                            <CardTitle>Departure Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Reason</Label>
                                    <Select
                                        value={departureForm.data.reason}
                                        onValueChange={(value) =>
                                            departureForm.setData(
                                                'reason',
                                                value as
                                                    | 'transfer_out'
                                                    | 'dropped_out',
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="transfer_out">
                                                Transfer Out
                                            </SelectItem>
                                            <SelectItem value="dropped_out">
                                                Dropped Out
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {departureForm.errors.reason && (
                                        <p className="text-sm text-destructive">
                                            {departureForm.errors.reason}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Effectivity Date</Label>
                                    <Input
                                        type="date"
                                        value={departureForm.data.effective_date}
                                        onChange={(event) =>
                                            departureForm.setData(
                                                'effective_date',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {departureForm.errors.effective_date && (
                                        <p className="text-sm text-destructive">
                                            {
                                                departureForm.errors
                                                    .effective_date
                                            }
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Remarks</Label>
                                <Textarea
                                    value={departureForm.data.remarks}
                                    onChange={(event) =>
                                        departureForm.setData(
                                            'remarks',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Record registrar notes for this departure"
                                    className="min-h-24"
                                />
                                {departureForm.errors.remarks && (
                                    <p className="text-sm text-destructive">
                                        {departureForm.errors.remarks}
                                    </p>
                                )}
                            </div>

                            <div className="rounded-md border p-3 text-sm text-muted-foreground">
                                <p className="flex items-start gap-2">
                                    <AlertTriangle className="mt-0.5 size-4" />
                                    Student history remains viewable in read-only
                                    mode until the account expiry date.
                                </p>
                            </div>
                        </CardContent>
                        <div className="flex items-center justify-end border-t px-4 py-3">
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmDialogOpen(true)}
                                disabled={
                                    !selected_student ||
                                    !selected_student.enrollment_id ||
                                    departureForm.processing
                                }
                            >
                                <UserMinus className="size-4" />
                                Process Departure
                            </Button>
                        </div>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Recent Departures</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Learner</TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>Reason</TableHead>
                                    <TableHead>Effective Date</TableHead>
                                    <TableHead>Account Expiry</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Processed By
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recent_departures.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-8 text-center text-sm text-muted-foreground"
                                            colSpan={6}
                                        >
                                            No departure logs yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    recent_departures.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="pl-6 font-medium">
                                                {row.student_name}
                                            </TableCell>
                                            <TableCell>{row.lrn}</TableCell>
                                            <TableCell>{row.reason}</TableCell>
                                            <TableCell>
                                                {row.effective_date ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {row.account_expires_at ?? '-'}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right text-sm text-muted-foreground">
                                                {row.processed_by || '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={confirmDialogOpen} onOpenChange={setConfirmDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirm Student Departure</DialogTitle>
                        <DialogDescription>
                            {selected_student?.name ?? 'Selected learner'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            Reason:{' '}
                            <strong className="text-foreground">
                                {departureForm.data.reason === 'transfer_out'
                                    ? 'Transfer Out'
                                    : 'Dropped Out'}
                            </strong>
                        </p>
                        <p>
                            Effective Date:{' '}
                            <strong className="text-foreground">
                                {departureForm.data.effective_date}
                            </strong>
                        </p>
                        <p>
                            This will update enrollment status and schedule
                            account expiry on the next school year boundary.
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={processDeparture}
                            disabled={departureForm.processing}
                        >
                            Confirm Process
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
