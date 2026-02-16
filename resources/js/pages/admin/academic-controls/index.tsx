import { Head, useForm, router, Link } from '@inertiajs/react';
import {
    CalendarDays,
    ArrowRightCircle,
    Archive,
    PlayCircle,
    Edit2,
    Calendar,
    Clock,
    ShieldCheck,
    Info,
    FlaskConical,
    RefreshCcw,
    Zap,
    HelpCircle,
    BookOpen,
    Layers,
    CalendarRange,
    Settings2,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
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
import { cn } from '@/lib/utils';
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
import type { BreadcrumbItem } from '@/types';
import type { AcademicYear } from '@/types/academic-year';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Academic Controls',
        href: '/admin/academic-controls',
    },
];

interface Props {
    currentYear: AcademicYear | null;
    nextYearName: string | null;
    allYears: AcademicYear[];
}

export default function AcademicControls({
    currentYear,
    nextYearName,
    allYears,
}: Props) {
    const [isInitNextYearOpen, setIsInitNextYearOpen] = useState(false);
    const [isEditDatesOpen, setIsEditDatesOpen] = useState(false);
    const [isGuideOpen, setIsGuideOpen] = useState(false);

    const initForm = useForm({
        name: nextYearName || '',
        start_date: '',
        end_date: '',
    });

    const editForm = useForm({
        start_date: currentYear?.start_date || '',
        end_date: currentYear?.end_date || '',
    });

    useEffect(() => {
        if (nextYearName) {
            initForm.setData('name', nextYearName);
        }
    }, [nextYearName]);

    const handleInitialize = () => {
        initForm.post(initialize(), {
            onSuccess: () => {
                setIsInitNextYearOpen(false);
                initForm.reset();
            },
        });
    };

    const handleUpdateDates = () => {
        if (!currentYear) return;
        editForm.patch(update_dates({ academicYear: currentYear.id }), {
            onSuccess: () => setIsEditDatesOpen(false),
        });
    };

    const handleSimulateOpening = () => {
        if (!currentYear) return;
        router.post(simulate_opening({ academicYear: currentYear.id }).url);
    };

    const handleAdvanceQuarter = () => {
        if (!currentYear) return;
        router.post(advance_quarter({ academicYear: currentYear.id }).url);
    };

    const handleResetSimulation = () => {
        if (confirm('This will wipe all school year data. Continue?')) {
            router.post(reset_simulation().url);
        }
    };

    const getStatusBadge = () => {
        switch (currentYear?.status) {
            case 'ongoing':
                return (
                    <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400">
                        Ongoing
                    </Badge>
                );
            case 'upcoming':
                return (
                    <Badge variant="secondary" className="bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400">
                        Pre-Opening
                    </Badge>
                );
            case 'completed':
                return (
                    <Badge variant="secondary" className="bg-indigo-500/15 text-indigo-700 hover:bg-indigo-500/25 dark:text-indigo-400">
                        Awaiting Setup
                    </Badge>
                );
            default:
                return <Badge variant="secondary">No Active Year</Badge>;
        }
    };

    const getSmartButton = () => {
        if (!currentYear || currentYear.status === 'completed') {
            return (
                <Button className="w-full gap-2" onClick={() => setIsInitNextYearOpen(true)}>
                    <PlayCircle className="size-4" />
                    {currentYear ? `Setup S.Y. ${nextYearName}` : 'Initialize First School Year'}
                </Button>
            );
        }

        switch (currentYear.status) {
            case 'upcoming':
                return (
                    <Button className="w-full gap-2" variant="secondary" disabled>
                        <Clock className="size-4" />
                        Awaiting Start of Classes
                    </Button>
                );
            case 'ongoing':
                return (
                    <Button
                        className="w-full gap-2"
                        variant={currentYear.current_quarter === '4' ? "destructive" : "default"}
                        onClick={handleAdvanceQuarter}
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
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Academic Controls" />
            <div className="flex h-full flex-col gap-6 p-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <Settings2 className="size-6 text-primary" />
                            <h1 className="text-2xl font-bold tracking-tight">Academic Controls</h1>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Configure institutional lifecycle, academic years, and curriculum management.
                        </p>
                    </div>
                    <Button variant="outline" size="sm" className="gap-2" onClick={() => setIsGuideOpen(true)}>
                        <HelpCircle className="size-4" />
                        Operational Guide
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader className="flex flex-row items-center gap-2 space-y-0 border-b pb-4">
                            <CalendarDays className="size-4 text-primary" />
                            <CardTitle className="text-base">Active School Year</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="mb-8 grid grid-cols-1 gap-8 md:grid-cols-3">
                                <div className="space-y-1">
                                    <Label className="text-xs text-muted-foreground">Cycle Name</Label>
                                    <p className="text-2xl font-bold">
                                        {currentYear?.status === 'completed'
                                            ? nextYearName
                                            : currentYear?.name || '---- - ----'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs text-muted-foreground">Operating Window</Label>
                                    <div
                                        className="group flex cursor-pointer items-center justify-between transition-colors hover:text-primary"
                                        onClick={() =>
                                            currentYear?.status !== 'completed' && setIsEditDatesOpen(true)
                                        }
                                    >
                                        <div className="space-y-1">
                                            <p className="flex items-center gap-2 text-sm font-medium">
                                                <Calendar className="size-3.5 opacity-50" />
                                                {currentYear?.status === 'completed'
                                                    ? 'Not Set'
                                                    : currentYear?.start_date || '---'}
                                            </p>
                                            <p className="flex items-center gap-2 pl-5 text-sm font-medium">
                                                {currentYear?.status === 'completed'
                                                    ? 'Not Set'
                                                    : currentYear?.end_date || '---'}
                                            </p>
                                        </div>
                                        {currentYear?.status !== 'completed' && currentYear && (
                                            <Edit2 className="size-4 opacity-0 transition-opacity group-hover:opacity-100" />
                                        )}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs text-muted-foreground">Current Progress</Label>
                                    <div className="flex items-center gap-3">
                                        <p className="text-sm font-semibold">
                                            {currentYear?.status === 'completed' || !currentYear
                                                ? 'Awaiting Setup'
                                                : currentYear?.status === 'upcoming'
                                                  ? 'Pre-Opening'
                                                  : `${currentYear.current_quarter}${currentYear.current_quarter === '1' ? 'st' : currentYear.current_quarter === '2' ? 'nd' : currentYear.current_quarter === '3' ? 'rd' : 'th'} Quarter`}
                                        </p>
                                        {getStatusBadge()}
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-4">
                                {getSmartButton()}

                                <div className="flex items-center justify-between border-t border-dashed pt-4">
                                    <div className="flex items-center gap-4">
                                        {currentYear?.status === 'upcoming' && (
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                className="gap-2"
                                                onClick={handleSimulateOpening}
                                            >
                                                <Zap className="size-3.5" />
                                                Simulate Opening Day
                                            </Button>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="gap-2 text-muted-foreground hover:text-destructive"
                                            onClick={handleResetSimulation}
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

                    <div className="space-y-6">
                        <div className="flex items-center gap-2">
                            <ShieldCheck className="size-4 text-primary" />
                            <h2 className="text-sm font-medium text-muted-foreground">Academic Management</h2>
                        </div>

                        <div className="grid grid-cols-1 gap-4">
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
                                <Link key={item.title} href={item.href} className="group">
                                    <Card className="transition-colors hover:bg-muted/50">
                                        <CardContent className="flex items-center justify-between p-4">
                                            <div className="flex items-center gap-4">
                                                <div className="flex size-10 items-center justify-center rounded-lg border bg-background text-muted-foreground shadow-sm transition-colors group-hover:text-primary">
                                                    <item.icon className="size-5" />
                                                </div>
                                                <div>
                                                    <p className="font-semibold leading-none">{item.title}</p>
                                                    <p className="mt-1 text-xs text-muted-foreground">{item.desc}</p>
                                                </div>
                                            </div>
                                            <ArrowRightCircle className="size-4 text-muted-foreground transition-colors group-hover:text-primary" />
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                <Dialog open={isGuideOpen} onOpenChange={setIsGuideOpen}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-xl">
                                <Info className="size-5 text-primary" />
                                Operational Guide
                            </DialogTitle>
                            <DialogDescription>
                                System logic and institutional lifecycle rules.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-6 py-4">
                            <div className="space-y-2 text-sm">
                                <p className="font-semibold text-primary">Smart Workflow</p>
                                <p className="leading-relaxed text-muted-foreground">
                                    The system automatically transitions states based on administrative actions:
                                    <span className="font-medium text-foreground"> Setup → Opening → Quarter Cycles → Archiving.</span>
                                </p>
                            </div>
                            <div className="space-y-2 text-sm">
                                <p className="font-semibold text-primary">Data Integrity</p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Finalizing a year archives all records and moves the system focus to the next calculated school year.
                                </p>
                            </div>
                            <div className="space-y-2 text-sm">
                                <p className="font-semibold text-primary">Service Availability</p>
                                <p className="leading-relaxed text-muted-foreground">
                                    Enrollment and Finance features remain accessible. Initializing a school year designates the target cycle for upcoming records.
                                </p>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button className="w-full" onClick={() => setIsGuideOpen(false)}>
                                Understood
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog open={isEditDatesOpen} onOpenChange={setIsEditDatesOpen}>
                    <DialogContent className="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle>Modify Dates</DialogTitle>
                            <DialogDescription>
                                Adjust the operational window for the current cycle.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid grid-cols-2 gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={editForm.data.start_date}
                                    onChange={(e) => editForm.setData('start_date', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={editForm.data.end_date}
                                    onChange={(e) => editForm.setData('end_date', e.target.value)}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsEditDatesOpen(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleUpdateDates} disabled={editForm.processing}>
                                Update Window
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog open={isInitNextYearOpen} onOpenChange={setIsInitNextYearOpen}>
                    <DialogContent className="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle>
                                {nextYearName ? `Setup SY ${nextYearName}` : 'Initialize Year'}
                            </DialogTitle>
                            <DialogDescription>
                                Define the operational window for this academic cycle.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            {!nextYearName && (
                                <div className="grid gap-2">
                                    <Label htmlFor="year_name">School Year Name</Label>
                                    <Input
                                        id="year_name"
                                        placeholder="e.g. 2024-2025"
                                        value={initForm.data.name}
                                        onChange={(e) => initForm.setData('name', e.target.value)}
                                    />
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="setup_start">Opening Day</Label>
                                    <Input
                                        id="setup_start"
                                        type="date"
                                        value={initForm.data.start_date}
                                        onChange={(e) => initForm.setData('start_date', e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="setup_end">Closing Day</Label>
                                    <Input
                                        id="setup_end"
                                        type="date"
                                        value={initForm.data.end_date}
                                        onChange={(e) => initForm.setData('end_date', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsInitNextYearOpen(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleInitialize} disabled={initForm.processing}>
                                Initialize Cycle
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
