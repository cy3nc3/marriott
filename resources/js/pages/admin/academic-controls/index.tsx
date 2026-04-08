import { Head, useForm, router, Link } from '@inertiajs/react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import {
    ArrowRightCircle,
    Archive,
    PlayCircle,
    Calendar,
    Clock,
    FlaskConical,
    RefreshCcw,
    Zap,
    BookOpen,
    Layers,
    CalendarRange,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { AcademicYear } from '@/types/academic-year';
import {
    curriculum_manager,
    section_manager,
    schedule_builder,
} from '@/routes/admin';
import {
    initialize,
    update_dates,
    simulate_opening,
    advance_quarter,
    reset_simulation,
} from '@/routes/admin/academic_controls';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Academic Controls',
        href: '/admin/academic-controls',
    },
    {
        title: 'School Year Manager',
        href: '/admin/academic-controls',
    },
];

interface Props {
    currentYear: AcademicYear | null;
    upcomingYear: AcademicYear | null;
    nextYearName: string | null;
    allYears: AcademicYear[];
}

export default function AcademicControls({
    currentYear,
    upcomingYear,
    nextYearName,
    allYears,
}: Props) {
    const [isInitNextYearOpen, setIsInitNextYearOpen] = useState(false);
    const [isEditDatesOpen, setIsEditDatesOpen] = useState(false);
    const [isResetConfirmOpen, setIsResetConfirmOpen] = useState(false);
    const [isAdvanceConfirmOpen, setIsAdvanceConfirmOpen] = useState(false);
    const [isSimulateConfirmOpen, setIsSimulateConfirmOpen] = useState(false);
    const operationYear = currentYear ?? upcomingYear;

    const initForm = useForm({
        name: nextYearName || '',
    });

    const editForm = useForm({
        start_date: operationYear?.start_date || '',
        end_date: operationYear?.end_date || '',
    });

    useEffect(() => {
        editForm.setData({
            start_date: operationYear?.start_date || '',
            end_date: operationYear?.end_date || '',
        });
    }, [operationYear?.id, operationYear?.start_date, operationYear?.end_date]);

    useEffect(() => {
        initForm.setData('name', nextYearName || '');
    }, [nextYearName]);

    const handleInitialize = () => {
        initForm.submit(initialize(), {
            onSuccess: () => {
                setIsInitNextYearOpen(false);
                initForm.reset();
            },
        });
    };

    const handleUpdateDates = () => {
        if (!operationYear) return;
        editForm.submit(update_dates({ academicYear: operationYear.id }), {
            onSuccess: () => setIsEditDatesOpen(false),
        });
    };

    const submitSimulateOpening = () => {
        if (!upcomingYear) return;
        router.post(simulate_opening({ academicYear: upcomingYear.id }).url, {}, {
            onSuccess: () => setIsSimulateConfirmOpen(false)
        });
    };

    const submitAdvanceQuarter = () => {
        if (!currentYear) return;
        router.post(advance_quarter({ academicYear: currentYear.id }).url, {}, {
            onSuccess: () => setIsAdvanceConfirmOpen(false)
        });
    };

    const submitResetSimulation = () => {
        router.post(reset_simulation().url, {}, {
            onSuccess: () => setIsResetConfirmOpen(false)
        });
    };

    const getStatusBadge = () => {
        if (currentYear?.status === 'ongoing') {
            return (
                <Badge
                    variant="secondary"
                    className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400"
                >
                    Ongoing
                </Badge>
            );
        }

        if (!currentYear && upcomingYear) {
            return (
                <Badge
                    variant="secondary"
                    className="bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400"
                >
                    Pre-Opening
                </Badge>
            );
        }

        return <Badge variant="secondary">No Active Year</Badge>;
    };

    const getSmartButton = () => {
        if (!currentYear) {
            if (upcomingYear) {
                return (
                    <Button
                        className="w-full gap-2"
                        variant="secondary"
                        disabled
                    >
                        <Clock className="size-4" />
                        Pre-Opening Year Ready
                    </Button>
                );
            }

            return (
                <Button
                    className="w-full gap-2"
                    onClick={() => setIsInitNextYearOpen(true)}
                >
                    <PlayCircle className="size-4" />
                    Initialize First School Year
                </Button>
            );
        }

        return (
            <Button
                className="w-full gap-2"
                variant={
                    currentYear.current_quarter === '4'
                        ? 'destructive'
                        : 'default'
                }
                onClick={() => setIsAdvanceConfirmOpen(true)}
            >
                {currentYear.current_quarter === '4' ? (
                    <>
                        <Archive className="size-4" />
                        Close & Archive Year
                    </>
                ) : (
                    <>
                        <ArrowRightCircle className="size-4" />
                        Advance to{' '}
                        {currentYear.current_quarter === '1'
                            ? '2ND'
                            : currentYear.current_quarter === '2'
                              ? '3RD'
                              : '4TH'}{' '}
                        Quarter
                    </>
                )}
            </Button>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="School Year Manager" />
            <div className="flex flex-col gap-6">
                <Card className="max-h-fit gap-2">
                    <CardContent className="pt-6">
                        <div className="mb-4 grid grid-cols-1 gap-8 md:grid-cols-4">
                            <div className="space-y-1">
                                <Label className="text-xs text-muted-foreground">
                                    Active School Year
                                </Label>
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-2xl font-bold">
                                        {operationYear?.name || '---- - ----'}
                                    </p>
                                    {getStatusBadge()}
                                </div>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-muted-foreground">
                                    Current Quarter
                                </Label>
                                <div className="flex items-center gap-3">
                                    <p className="text-2xl font-bold">
                                        {currentYear
                                            ? `${currentYear.current_quarter}${currentYear.current_quarter === '1' ? 'st' : currentYear.current_quarter === '2' ? 'nd' : currentYear.current_quarter === '3' ? 'rd' : 'th'} Quarter`
                                            : upcomingYear
                                              ? 'Pre-Opening'
                                              : 'Awaiting Setup'}
                                    </p>
                                </div>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-muted-foreground">
                                    Start Date
                                </Label>
                                <p className="flex items-center gap-2 text-2xl font-bold">
                                    <Calendar className="size-3.5 opacity-50" />
                                    {operationYear?.start_date || '---'}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-muted-foreground">
                                    End Date
                                </Label>
                                <p className="flex items-center gap-2 text-2xl font-bold">
                                    <Calendar className="size-3.5 opacity-50" />
                                    {operationYear?.end_date || '---'}
                                </p>
                            </div>
                        </div>
                        {operationYear &&
                            operationYear.status !== 'completed' && (
                                <div className="mt-2 mb-4 grid grid-cols-1 gap-2 md:grid-cols-4">
                                    <div className="hidden md:col-span-2 md:block" />
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-8 w-full text-xs font-semibold md:col-span-2 md:col-start-3"
                                        onClick={() => setIsEditDatesOpen(true)}
                                    >
                                        Edit Dates
                                    </Button>
                                </div>
                            )}

                        <div className="space-y-4">
                            {getSmartButton()}

                            <div className="grid gap-3 rounded-lg border bg-background p-3 sm:grid-cols-3">
                                {[
                                    {
                                        label: 'Sections',
                                        value: '--',
                                    },
                                    {
                                        label: 'Enrolled Students',
                                        value: '--',
                                    },
                                    {
                                        label: 'Advisers Assigned',
                                        value: '--',
                                    },
                                ].map((item) => (
                                    <div key={item.label} className="space-y-1">
                                        <p className="text-xs text-muted-foreground">
                                            {item.label}
                                        </p>
                                        <p className="text-sm font-semibold">
                                            {item.value}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            <div className="flex items-center justify-between border-t border-dashed pt-4">
                                <div className="flex items-center gap-4">
                                    {!currentYear && upcomingYear && (
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            className="gap-2"
                                            onClick={() => setIsSimulateConfirmOpen(true)}
                                        >
                                            <Zap className="size-3.5" />
                                            Simulate Opening Day
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="gap-2 text-muted-foreground hover:text-destructive"
                                        onClick={() => setIsResetConfirmOpen(true)}
                                    >
                                        <RefreshCcw className="size-3" />
                                        Wipe System Data
                                    </Button>
                                </div>
                                <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground/60">
                                    <FlaskConical className="size-3" />
                                    Simulation Mode
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {[
                        {
                            title: 'Curriculum',
                            icon: BookOpen,
                            href: curriculum_manager().url,
                            desc: 'Subjects & Requirements',
                        },
                        {
                            title: 'Sections',
                            icon: Layers,
                            href: section_manager().url,
                            desc: 'Class Organizations',
                        },
                        {
                            title: 'Schedules',
                            icon: CalendarRange,
                            href: schedule_builder().url,
                            desc: 'Weekly Timetables',
                        },
                    ].map((item) => (
                        <Link
                            key={item.title}
                            href={item.href}
                            className="group"
                        >
                            <Card className="gap-2 transition-colors hover:bg-muted/50">
                                <CardContent className="flex items-center justify-between p-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex size-10 items-center justify-center rounded-lg border bg-background text-muted-foreground shadow-sm transition-colors group-hover:text-primary">
                                            <item.icon className="size-5" />
                                        </div>
                                        <div>
                                            <p className="leading-none font-semibold">
                                                {item.title}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {item.desc}
                                            </p>
                                        </div>
                                    </div>
                                    <ArrowRightCircle className="size-4 text-muted-foreground transition-colors group-hover:text-primary" />
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                <Dialog
                    open={isEditDatesOpen}
                    onOpenChange={setIsEditDatesOpen}
                >
                    <DialogContent className="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle>Modify Dates</DialogTitle>
                            <DialogDescription>
                                Adjust the operational window for the current
                                cycle.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid grid-cols-2 gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={editForm.data.start_date}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'start_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={editForm.data.end_date}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'end_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsEditDatesOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleUpdateDates}
                                disabled={editForm.processing}
                            >
                                Update Window
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={isInitNextYearOpen}
                    onOpenChange={setIsInitNextYearOpen}
                >
                    <DialogContent className="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle>Initialize School Year</DialogTitle>
                            <DialogDescription>
                                Set the school year label first. Dates can be
                                configured afterward.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            {initForm.errors.name && (
                                <div className="rounded-md border border-destructive/40 bg-destructive/10 p-2 text-xs text-destructive">
                                    Please review the school year name and try
                                    again.
                                </div>
                            )}
                            <div className="grid gap-2">
                                <Label htmlFor="setup_name">School Year</Label>
                                <Input
                                    id="setup_name"
                                    placeholder="e.g. 2026-2027"
                                    value={initForm.data.name}
                                    onChange={(e) =>
                                        initForm.setData('name', e.target.value)
                                    }
                                />
                                {initForm.errors.name && (
                                    <p className="text-xs text-destructive">
                                        {initForm.errors.name}
                                    </p>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsInitNextYearOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleInitialize}
                                disabled={initForm.processing}
                            >
                                Initialize Cycle
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <ActionConfirmDialog
                    open={isResetConfirmOpen}
                    onOpenChange={setIsResetConfirmOpen}
                    title="Wipe System Data"
                    description="Are you sure you want to wipe all school year data? This will delete all academic records, sections, and schedules for the current simulation. This action is irreversible."
                    variant="destructive"
                    confirmLabel="Wipe Data"
                    onConfirm={submitResetSimulation}
                />

                <ActionConfirmDialog
                    open={isAdvanceConfirmOpen}
                    onOpenChange={setIsAdvanceConfirmOpen}
                    title={currentYear?.current_quarter === '4' ? "Close School Year" : "Advance Quarter"}
                    description={
                        currentYear?.current_quarter === '4'
                            ? "Are you sure you want to close and archive the current school year? This will finalize all records and prepare the system for the next cycle."
                            : `Are you sure you want to advance to the next quarter? This will update the operational state for all classes and students.`
                    }
                    variant={currentYear?.current_quarter === '4' ? "destructive" : "warning"}
                    confirmLabel={currentYear?.current_quarter === '4' ? "Close & Archive" : "Advance Quarter"}
                    onConfirm={submitAdvanceQuarter}
                />

                <ActionConfirmDialog
                    open={isSimulateConfirmOpen}
                    onOpenChange={setIsSimulateConfirmOpen}
                    title="Simulate Opening Day"
                    description="Are you sure you want to simulate the opening day? This will transition the upcoming school year from 'Pre-Opening' to 'Ongoing' status."
                    variant="default"
                    confirmLabel="Simulate Opening"
                    onConfirm={submitSimulateOpening}
                />
            </div>
        </AppLayout>
    );
}
