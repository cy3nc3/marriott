import { useState, useEffect } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { AcademicYear } from '@/types/academic-year';
import {
    initialize,
    update_dates,
    simulate_opening,
    advance_quarter,
    reset_simulation,
} from '@/routes/admin/academic_controls';
import {
    curriculum_manager,
    section_manager,
    schedule_builder,
} from '@/routes/admin';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
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
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

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
                    <Badge
                        variant="outline"
                        className="border-emerald-200 bg-emerald-50 text-[10px] font-black tracking-widest text-emerald-700 uppercase dark:border-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400"
                    >
                        Ongoing
                    </Badge>
                );
            case 'upcoming':
                return (
                    <Badge
                        variant="outline"
                        className="border-amber-200 bg-amber-50 text-[10px] font-black tracking-widest text-amber-700 uppercase dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-400"
                    >
                        Pre-Opening
                    </Badge>
                );
            case 'completed':
                return (
                    <Badge
                        variant="outline"
                        className="border-indigo-200 bg-indigo-50 text-[10px] font-black tracking-widest text-indigo-700 uppercase dark:border-indigo-800 dark:bg-indigo-950/20 dark:text-indigo-400"
                    >
                        Awaiting Setup
                    </Badge>
                );
            default:
                return (
                    <Badge
                        variant="secondary"
                        className="text-[10px] font-black tracking-widest uppercase"
                    >
                        No Active Year
                    </Badge>
                );
        }
    };

    const getSmartButton = () => {
        if (!currentYear || currentYear.status === 'completed') {
            return (
                <Button
                    className="h-12 w-full gap-2 text-xs font-black tracking-widest uppercase"
                    onClick={() => setIsInitNextYearOpen(true)}
                >
                    <PlayCircle className="size-4" />
                    {currentYear
                        ? `Setup S.Y. ${nextYearName}`
                        : 'Initialize First School Year'}
                </Button>
            );
        }

        switch (currentYear.status) {
            case 'upcoming':
                return (
                    <Button
                        className="h-12 w-full gap-2 bg-amber-500 text-xs font-black tracking-widest uppercase hover:bg-amber-600"
                        disabled
                    >
                        <Clock className="size-4" />
                        Awaiting Start of Classes
                    </Button>
                );
            case 'ongoing':
                return (
                    <Button
                        className={cn(
                            'h-12 w-full gap-2 text-xs font-black tracking-widest uppercase',
                            currentYear.current_quarter === '4' &&
                                'bg-destructive hover:bg-destructive/90',
                        )}
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
            <div className="flex h-full flex-col gap-6 p-4 lg:p-6">
                {/* Page Header - Explicitly integrated */}
                <div className="flex flex-col justify-between gap-4 px-1 md:flex-row md:items-end">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <Settings2 className="size-6 text-primary" />
                            <h1 className="text-2xl font-black tracking-tight uppercase">
                                Academic Controls
                            </h1>
                        </div>
                        <p className="text-sm leading-none font-medium text-muted-foreground italic">
                            Configure institutional lifecycle, academic years,
                            and curriculum management.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-9 gap-2 self-center text-[10px] font-black tracking-widest uppercase"
                        onClick={() => setIsGuideOpen(true)}
                    >
                        <HelpCircle className="size-4" />
                        Operational Guide
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    {/* Main School Year Manager - Using py-0 to allow full-bleed headers/footers */}
                    <Card className="xl:col-span-2">
                        <CardHeader className="flex flex-row items-center gap-2 space-y-0 border-b">
                            <CalendarDays className="size-4 text-primary" />
                            <CardTitle className="text-sm font-black tracking-widest uppercase">
                                Active School Year
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 py-0">
                            <div className="mb-8 grid grid-cols-1 gap-8 md:grid-cols-3">
                                <div className="space-y-1">
                                    <Label className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                                        Cycle Name
                                    </Label>
                                    <p className="text-3xl font-black tracking-tighter">
                                        {currentYear?.status === 'completed'
                                            ? nextYearName
                                            : currentYear?.name ||
                                              '---- - ----'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                                        Operating Window
                                    </Label>
                                    <div
                                        className="group flex cursor-pointer items-center justify-between transition-colors hover:text-primary"
                                        onClick={() =>
                                            currentYear?.status !==
                                                'completed' &&
                                            setIsEditDatesOpen(true)
                                        }
                                    >
                                        <div className="space-y-0.5">
                                            <p className="flex items-center gap-2 text-sm font-bold">
                                                <Calendar className="size-3.5 opacity-50" />
                                                {currentYear?.status ===
                                                'completed'
                                                    ? 'Not Set'
                                                    : currentYear?.start_date ||
                                                      '---'}
                                            </p>
                                            <p className="flex items-center gap-2 pl-[22px] text-sm font-bold">
                                                {currentYear?.status ===
                                                'completed'
                                                    ? 'Not Set'
                                                    : currentYear?.end_date ||
                                                      '---'}
                                            </p>
                                        </div>
                                        {currentYear?.status !== 'completed' &&
                                            currentYear && (
                                                <Edit2 className="size-4 opacity-0 transition-opacity group-hover:opacity-100" />
                                            )}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                                        Current Progress
                                    </Label>
                                    <div className="flex items-center gap-3">
                                        <p className="text-sm font-black tracking-tight uppercase">
                                            {currentYear?.status ===
                                                'completed' || !currentYear
                                                ? 'Awaiting Setup'
                                                : currentYear?.status ===
                                                    'upcoming'
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
                                                className="h-8 gap-2 text-[10px] font-black uppercase"
                                                onClick={handleSimulateOpening}
                                            >
                                                <Zap className="size-3.5" />
                                                Simulate Opening Day
                                            </Button>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="h-8 gap-2 px-2 text-[10px] font-black text-muted-foreground uppercase hover:text-destructive"
                                            onClick={handleResetSimulation}
                                        >
                                            <RefreshCcw className="size-3" />
                                            Wipe System Data
                                        </Button>
                                    </div>
                                    <div className="flex items-center gap-2 text-[10px] font-black tracking-widest text-muted-foreground/40 uppercase">
                                        <FlaskConical className="size-3" />
                                        Simulation Mode
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Quick Links & Academic Management */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 px-1">
                            <ShieldCheck className="size-4 text-primary" />
                            <h2 className="text-xs font-black tracking-widest text-muted-foreground uppercase">
                                Academic Management
                            </h2>
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
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    className="group"
                                >
                                    <Card className="border-primary/10 bg-muted/5 py-4 shadow-none transition-all group-hover:border-primary/30 group-hover:bg-transparent">
                                        <CardContent className="flex items-center justify-between px-4 py-0">
                                            <div className="flex items-center gap-4">
                                                <div className="flex size-10 items-center justify-center rounded-lg border bg-background shadow-sm transition-colors group-hover:border-primary/20 group-hover:text-primary">
                                                    <item.icon className="size-5" />
                                                </div>
                                                <div>
                                                    <p className="text-sm leading-none font-black tracking-tight uppercase">
                                                        {item.title}
                                                    </p>
                                                    <p className="mt-1 text-[10px] font-medium text-muted-foreground">
                                                        {item.desc}
                                                    </p>
                                                </div>
                                            </div>
                                            <ArrowRightCircle className="size-4 text-muted-foreground/30 transition-colors group-hover:text-primary" />
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Modals */}
                <Dialog open={isGuideOpen} onOpenChange={setIsGuideOpen}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <div className="mb-2 flex items-center gap-2">
                                <Info className="size-5 text-primary" />
                                <DialogTitle className="text-xl font-black tracking-tight uppercase">
                                    Operational Guide
                                </DialogTitle>
                            </div>
                            <DialogDescription className="font-medium">
                                System logic and institutional lifecycle rules.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-6 py-4">
                            <div className="space-y-2 text-sm">
                                <p className="text-[10px] font-black tracking-widest text-primary uppercase">
                                    Smart Workflow
                                </p>
                                <p className="leading-relaxed font-medium text-muted-foreground">
                                    The system automatically transitions states
                                    based on administrative actions:
                                    <span className="text-xs font-bold text-foreground italic">
                                        {' '}
                                        Setup → Opening → Quarter Cycles →
                                        Archiving.
                                    </span>
                                </p>
                            </div>
                            <div className="space-y-2 text-sm">
                                <p className="text-[10px] font-black tracking-widest text-primary uppercase">
                                    Data Integrity
                                </p>
                                <p className="leading-relaxed font-medium text-muted-foreground">
                                    Finalizing a year archives all records and
                                    moves the system focus to the next
                                    calculated school year.
                                </p>
                            </div>
                            <div className="space-y-2 text-sm">
                                <p className="text-[10px] font-black tracking-widest text-primary uppercase">
                                    Service Availability
                                </p>
                                <p className="leading-relaxed font-medium text-muted-foreground">
                                    Enrollment and Finance features remain
                                    accessible. Initializing a school year
                                    designates the target cycle for upcoming
                                    records.
                                </p>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                className="h-11 w-full text-xs font-black tracking-widest uppercase"
                                onClick={() => setIsGuideOpen(false)}
                            >
                                Understood
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={isEditDatesOpen}
                    onOpenChange={setIsEditDatesOpen}
                >
                    <DialogContent className="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-black tracking-tight uppercase">
                                Modify Dates
                            </DialogTitle>
                            <DialogDescription className="text-xs font-medium">
                                Adjust the operational window for the current
                                cycle.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid grid-cols-2 gap-4 py-4">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="start_date"
                                    className="text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                                >
                                    Start Date
                                </Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    className="h-10 font-bold"
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
                                <Label
                                    htmlFor="end_date"
                                    className="text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                                >
                                    End Date
                                </Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    className="h-10 font-bold"
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
                                className="h-10 font-bold"
                                onClick={() => setIsEditDatesOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                className="h-10 font-black tracking-tight uppercase"
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
                            <DialogTitle className="text-xl font-black tracking-tight uppercase">
                                {nextYearName
                                    ? `Setup SY ${nextYearName}`
                                    : 'Initialize Year'}
                            </DialogTitle>
                            <DialogDescription className="text-xs font-medium">
                                Define the operational window for this academic
                                cycle.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-6 py-4">
                            {!nextYearName && (
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="year_name"
                                        className="text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                                    >
                                        School Year Name
                                    </Label>
                                    <Input
                                        id="year_name"
                                        placeholder="e.g. 2024-2025"
                                        className="h-10 font-bold"
                                        value={initForm.data.name}
                                        onChange={(e) =>
                                            initForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="setup_start"
                                        className="text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                                    >
                                        Opening Day
                                    </Label>
                                    <Input
                                        id="setup_start"
                                        type="date"
                                        className="h-10 font-bold"
                                        value={initForm.data.start_date}
                                        onChange={(e) =>
                                            initForm.setData(
                                                'start_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="setup_end"
                                        className="text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                                    >
                                        Closing Day
                                    </Label>
                                    <Input
                                        id="setup_end"
                                        type="date"
                                        className="h-10 font-bold"
                                        value={initForm.data.end_date}
                                        onChange={(e) =>
                                            initForm.setData(
                                                'end_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                className="h-10 font-bold"
                                onClick={() => setIsInitNextYearOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                className="h-10 font-black tracking-tight uppercase"
                                onClick={handleInitialize}
                                disabled={initForm.processing}
                            >
                                Initialize Cycle
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
