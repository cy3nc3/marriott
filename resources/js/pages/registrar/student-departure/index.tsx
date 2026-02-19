import { Head } from '@inertiajs/react';
import { AlertTriangle, Lock, Search } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Departure',
        href: '/registrar/student-departure',
    },
];

export default function StudentDeparture() {
    const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false);

    const departureLogRows = [
        {
            student: 'Maria Santos',
            lrn: '987654321098',
            reason: 'Transfer Out',
            effectivityDate: '02/18/2026',
            status: 'Processed',
        },
        {
            student: 'Carlo Reyes',
            lrn: '123123123123',
            reason: 'Dropped',
            effectivityDate: '02/15/2026',
            status: 'Processed',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Departure" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Student Lookup</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-2">
                            <Input placeholder="Search by LRN or student name" />
                            <Button variant="outline">
                                <Search className="size-4" />
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Selected Student</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Name
                                </p>
                                <p className="text-sm font-medium">
                                    Juan Dela Cruz
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    LRN
                                </p>
                                <p className="text-sm font-medium">
                                    123456789012
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Grade and Section
                                </p>
                                <p className="text-sm font-medium">
                                    Grade 7 - Rizal
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Enrollment Status
                                </p>
                                <Badge variant="outline">Active</Badge>
                            </div>

                            <div className="rounded-md border border-amber-200 bg-amber-50 p-3">
                                <div className="flex items-start gap-2">
                                    <AlertTriangle className="mt-0.5 size-4 text-amber-700" />
                                    <p className="text-xs text-amber-800">
                                        Processing departure removes this
                                        student from active class lists but
                                        keeps finance and academic history.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <CardTitle>Departure Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Reason for Leaving</Label>
                                    <Select defaultValue="transfer-out">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="transfer-out">
                                                Transfer Out
                                            </SelectItem>
                                            <SelectItem value="dropped">
                                                Dropped
                                            </SelectItem>
                                            <SelectItem value="moved-location">
                                                Moved to another location
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Other
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Effectivity Date</Label>
                                    <Input
                                        type="date"
                                        defaultValue="2026-02-20"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Clearance Status</Label>
                                    <Select defaultValue="pending">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">
                                                Pending Clearance
                                            </SelectItem>
                                            <SelectItem value="cleared">
                                                Cleared
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Credentials Released</Label>
                                    <Select defaultValue="no">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="yes">
                                                Yes
                                            </SelectItem>
                                            <SelectItem value="no">
                                                No
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Registrar Remarks</Label>
                                <Textarea
                                    placeholder="Add important notes for records and tracking."
                                    className="min-h-24"
                                />
                            </div>
                        </CardContent>
                        <div className="flex items-center justify-end gap-2 border-t px-4 py-3">
                            <Button variant="outline">Save Draft</Button>
                            <Button
                                variant="destructive"
                                onClick={() => setIsConfirmDialogOpen(true)}
                            >
                                <Lock className="size-4" />
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
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>Reason</TableHead>
                                    <TableHead>Effectivity Date</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {departureLogRows.map((row) => (
                                    <TableRow key={row.lrn}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.student}
                                        </TableCell>
                                        <TableCell>{row.lrn}</TableCell>
                                        <TableCell>{row.reason}</TableCell>
                                        <TableCell>
                                            {row.effectivityDate}
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <Badge variant="secondary">
                                                {row.status}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={isConfirmDialogOpen}
                onOpenChange={setIsConfirmDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirm Student Departure</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Confirm processing departure for Juan Dela Cruz. This
                        will mark the student as inactive for current class
                        lists.
                    </p>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsConfirmDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setIsConfirmDialogOpen(false)}
                        >
                            Confirm Process
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
