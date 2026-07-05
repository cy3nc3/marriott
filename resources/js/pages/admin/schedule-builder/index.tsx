import { Head, useForm, router } from '@inertiajs/react';
import {
    Calendar,
    Clock,
    Trash2,
    User,
    AlertTriangle,
    Info,
    AlertCircle,
    Plus,
    BookOpen,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import InputError from '@/components/input-error';
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
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { store, update, destroy } from '@/routes/admin/schedule_builder';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Academic Controls',
        href: '/admin/academic-controls',
    },
    {
        title: 'Schedule Builder',
        href: '/admin/schedule-builder',
    },
];

// Configuration
const DEFAULT_START_HOUR = 7;
const DEFAULT_END_HOUR = 17;
const HOUR_HEIGHT = 96;
const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const formatDisplayTime = (time: string): string => {
    if (!time) return 'Select time';

    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHour = hours % 12 || 12;

    return `${displayHour}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const toTimeValue = (hour12: number, minute: number, period: 'AM' | 'PM') => {
    const normalizedHour =
        period === 'PM' ? (hour12 % 12) + 12 : hour12 % 12;

    return `${normalizedHour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
};

type TimeBlock = {
    id: number;
    day: string;
    start_time: string;
    end_time: string;
};

function TimePickerField({
    value,
    onChange,
    placeholder,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
}) {
    const [rawHours = '08', rawMinutes = '00'] = (value || '08:00').split(':');
    const hours24 = Number(rawHours);
    const minutes = Number(rawMinutes);
    const period: 'AM' | 'PM' = hours24 >= 12 ? 'PM' : 'AM';
    const hour12 = hours24 % 12 || 12;

    const updateHour = (nextValue: string) => {
        if (!/^\d{0,2}$/.test(nextValue)) return;

        const nextHour = Math.min(Math.max(Number(nextValue || 1), 1), 12);
        onChange(toTimeValue(nextHour, minutes, period));
    };

    const updateMinute = (nextValue: string) => {
        if (!/^\d{0,2}$/.test(nextValue)) return;

        const nextMinute = Math.min(Math.max(Number(nextValue || 0), 0), 59);
        onChange(toTimeValue(hour12, nextMinute, period));
    };

    const updatePeriod = (nextPeriod: 'AM' | 'PM') => {
        onChange(toTimeValue(hour12, minutes, nextPeriod));
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 w-full justify-start rounded-lg px-3 text-left font-normal"
                >
                    <Clock className="mr-2 size-4 text-muted-foreground" />
                    <span
                        className={cn(
                            !value && 'text-muted-foreground',
                        )}
                    >
                        {value ? formatDisplayTime(value) : placeholder}
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-64 p-3" align="start">
                <div className="space-y-3">
                    <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        Set Time
                    </div>
                    <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                        <Input
                            inputMode="numeric"
                            value={hour12.toString().padStart(2, '0')}
                            onChange={(event) => updateHour(event.target.value)}
                            className="h-11 rounded-lg text-center text-base font-semibold"
                            aria-label="Hour"
                        />
                        <span className="text-lg font-semibold text-muted-foreground">
                            :
                        </span>
                        <Input
                            inputMode="numeric"
                            value={minutes.toString().padStart(2, '0')}
                            onChange={(event) =>
                                updateMinute(event.target.value)
                            }
                            className="h-11 rounded-lg text-center text-base font-semibold"
                            aria-label="Minute"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        {(['AM', 'PM'] as const).map((option) => (
                            <Button
                                key={option}
                                type="button"
                                variant={
                                    period === option ? 'default' : 'outline'
                                }
                                className="h-9 rounded-lg"
                                onClick={() => updatePeriod(option)}
                            >
                                {option}
                            </Button>
                        ))}
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

interface Teacher {
    id: number;
    name: string;
    initial: string;
}

interface Subject {
    id: number;
    name: string;
    code: string;
    qualifiedTeachers: number[];
}

interface ScheduleItem {
    id: number;
    section_id: number;
    subject_assignment_id: number | null;
    type: 'academic' | 'break' | 'ceremony';
    label: string | null;
    day: string;
    start_time: string;
    end_time: string;
    section?: { id: number; name: string; grade_level?: { id: number; name: string } };
    _sections?: string[];
    subject_assignment?: {
        teacher_subject?: {
            subject?: { id: number; subject_name: string };
            teacher?: { id: number; name: string };
        };
    };
}

interface Section {
    id: number;
    name: string;
}

interface GradeLevel {
    id: number;
    name: string;
    sections: Section[];
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Props {
    gradeLevels: GradeLevel[];
    subjects: Subject[];
    teachers: Teacher[];
    sectionSchedules: ScheduleItem[];
    activeYear: AcademicYear | null;
}

export default function ScheduleBuilder({
    gradeLevels,
    subjects,
    teachers,
    sectionSchedules,
    activeYear,
}: Props) {
    const [selectedGradeId, setSelectedGradeId] = useState(
        gradeLevels[0]?.id.toString() || '',
    );
    const [selectedSectionId, setSelectedSectionId] = useState('');
    const [selectedSubjectId, setSelectedSubjectId] = useState<string | null>(
        null,
    );
    const [selectedTeacherId, setSelectedTeacherId] = useState<string | null>(
        null,
    );
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [selectedItem, setSelectedItem] = useState<ScheduleItem | null>(null);
    const [isDeleteConfirmOpen, setIsDeleteConfirmOpen] = useState(false);

    const handleGradeChange = (gradeId: string): void => {
        setSelectedGradeId(gradeId);

        const grade = gradeLevels.find(
            (g) => g.id.toString() === gradeId,
        );

        if (grade && grade.sections.length > 0) {
            setSelectedSectionId(grade.sections[0].id.toString());
        } else {
            setSelectedSectionId('');
        }
    };

    const addForm = useForm({
        section_id: 0,
        subject_id: null as number | null,
        teacher_id: null as number | null,
        type: 'academic' as 'academic' | 'break' | 'ceremony',
        label: '',
        day: 'Monday',
        start_time: '08:00',
        end_time: '09:00',
    });

    const editForm = useForm({
        type: 'academic' as 'academic' | 'break' | 'ceremony',
        subject_id: null as number | null,
        teacher_id: null as number | null,
        label: '',
        day: '',
        start_time: '',
        end_time: '',
    });

    const timeToMinutes = (time: string) => {
        if (!time) return 0;
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    };

    const getSubjectColor = (subjectName: string | null | undefined) => {
        if (!subjectName) return 'border-border bg-muted/70 text-foreground';
        
        const colors = [
            { bg: 'bg-red-500/10', border: 'border-red-500/30', text: 'text-red-700 dark:text-red-400', hover: 'hover:border-red-500/60' },
            { bg: 'bg-orange-500/10', border: 'border-orange-500/30', text: 'text-orange-700 dark:text-orange-400', hover: 'hover:border-orange-500/60' },
            { bg: 'bg-amber-500/10', border: 'border-amber-500/30', text: 'text-amber-700 dark:text-amber-400', hover: 'hover:border-amber-500/60' },
            { bg: 'bg-emerald-500/10', border: 'border-emerald-500/30', text: 'text-emerald-700 dark:text-emerald-400', hover: 'hover:border-emerald-500/60' },
            { bg: 'bg-cyan-500/10', border: 'border-cyan-500/30', text: 'text-cyan-700 dark:text-cyan-400', hover: 'hover:border-cyan-500/60' },
            { bg: 'bg-blue-500/10', border: 'border-blue-500/30', text: 'text-blue-700 dark:text-blue-400', hover: 'hover:border-blue-500/60' },
            { bg: 'bg-indigo-500/10', border: 'border-indigo-500/30', text: 'text-indigo-700 dark:text-indigo-400', hover: 'hover:border-indigo-500/60' },
            { bg: 'bg-violet-500/10', border: 'border-violet-500/30', text: 'text-violet-700 dark:text-violet-400', hover: 'hover:border-violet-500/60' },
            { bg: 'bg-fuchsia-500/10', border: 'border-fuchsia-500/30', text: 'text-fuchsia-700 dark:text-fuchsia-400', hover: 'hover:border-fuchsia-500/60' },
            { bg: 'bg-rose-500/10', border: 'border-rose-500/30', text: 'text-rose-700 dark:text-rose-400', hover: 'hover:border-rose-500/60' },
        ];
        
        const normalizedName = subjectName.trim().toUpperCase();
        let hash = 0;
        for (let i = 0; i < normalizedName.length; i++) {
            hash = normalizedName.charCodeAt(i) + ((hash << 5) - hash);
        }
        const index = Math.abs(hash) % colors.length;
        const color = colors[index];
        
        return `${color.bg} ${color.border} ${color.text} ${color.hover}`;
    };

    const getPosition = (time: string, startHour: number) =>
        ((timeToMinutes(time) - startHour * 60) / 60) * HOUR_HEIGHT;
    const getHeight = (start: string, end: string) =>
        ((timeToMinutes(end) - timeToMinutes(start)) / 60) * HOUR_HEIGHT;

    const filteredTeachers = useMemo(() => {
        if (!selectedSubjectId || selectedSubjectId === 'none') return teachers;
        const sub = subjects.find((s) => s.id.toString() === selectedSubjectId);
        return teachers.filter((t) => sub?.qualifiedTeachers.includes(t.id));
    }, [selectedSubjectId, subjects, teachers]);

    const availableTeachersForForm = useMemo(() => {
        const currentFormSubjectId = selectedItem
            ? editForm.data.subject_id
            : addForm.data.subject_id;

        if (!currentFormSubjectId) {
            return [];
        }

        const selectedFormSubject = subjects.find(
            (s) => s.id === currentFormSubjectId,
        );

        return teachers.filter((teacher) =>
            selectedFormSubject?.qualifiedTeachers.includes(teacher.id),
        );
    }, [
        selectedItem,
        editForm.data.subject_id,
        addForm.data.subject_id,
        subjects,
        teachers,
    ]);

    const currentSectionSchedules = useMemo(() => {
        const allowedTypes = new Set<ScheduleItem['type']>([
            'academic',
            'break',
            'ceremony',
        ]);

        const sectionItems = sectionSchedules
            .filter((s) => s.section_id.toString() === selectedSectionId)
            .filter((s): s is ScheduleItem => allowedTypes.has(s.type));

        const dedupedBySlot = new Map<string, ScheduleItem>();
        for (const item of sectionItems) {
            const slotKey = `${item.day}|${item.start_time}|${item.end_time}`;
            const existing = dedupedBySlot.get(slotKey);
            if (!existing || item.id > existing.id) {
                dedupedBySlot.set(slotKey, item);
            }
        }

        const deduped = Array.from(dedupedBySlot.values());
        const dayOrder = new Map(DAYS.map((day, index) => [day, index]));
        deduped.sort((a, b) => {
            const dayDiff =
                (dayOrder.get(a.day) ?? Number.MAX_SAFE_INTEGER) -
                (dayOrder.get(b.day) ?? Number.MAX_SAFE_INTEGER);
            if (dayDiff !== 0) return dayDiff;

            const startDiff = timeToMinutes(a.start_time) - timeToMinutes(b.start_time);
            if (startDiff !== 0) return startDiff;

            const endDiff = timeToMinutes(a.end_time) - timeToMinutes(b.end_time);
            if (endDiff !== 0) return endDiff;

            return b.id - a.id;
        });

        const pruned: ScheduleItem[] = [];
        for (const day of DAYS) {
            const dayItems = deduped.filter((item) => item.day === day);
            let lastEnd = -1;
            for (const item of dayItems) {
                const start = timeToMinutes(item.start_time);
                const end = timeToMinutes(item.end_time);
                if (start < lastEnd) {
                    continue;
                }
                pruned.push(item);
                lastEnd = Math.max(lastEnd, end);
            }
        }

        return pruned;
    }, [sectionSchedules, selectedSectionId]);

    const activeGhostBlocks = useMemo(() => {
        if (!selectedTeacherId || selectedTeacherId === 'none') return [];
        const ghosts = sectionSchedules.filter(
            (s) =>
                s.subject_assignment?.teacher_subject?.teacher?.id.toString() ===
                    selectedTeacherId &&
                s.section_id.toString() !== selectedSectionId,
        );

        const uniqueGhosts: ScheduleItem[] = [];
        const seen = new Map<string, number>();

        for (const g of ghosts) {
            const subjectName = g.subject_assignment?.teacher_subject?.subject?.subject_name || g.label || '';
            const key = `${g.day}-${g.start_time}-${g.end_time}-${subjectName}`;
            
            if (seen.has(key)) {
                // Add the section to the existing ghost
                const index = seen.get(key)!;
                if (g.section?.name) {
                    const sectionName = g.section.grade_level ? `${g.section.grade_level.name} - ${g.section.name}` : g.section.name;
                    if (!uniqueGhosts[index]._sections?.includes(sectionName)) {
                        uniqueGhosts[index]._sections!.push(sectionName);
                    }
                }
            } else {
                seen.set(key, uniqueGhosts.length);
                const sectionName = g.section?.grade_level ? `${g.section.grade_level.name} - ${g.section?.name}` : g.section?.name;
                const gClone = { ...g, _sections: sectionName ? [sectionName] : [] };
                uniqueGhosts.push(gClone);
            }
        }

        return uniqueGhosts;
    }, [sectionSchedules, selectedTeacherId, selectedSectionId]);

    const { displayStartHour, displayEndHour } = useMemo(() => {
        const defaultStartMinutes = DEFAULT_START_HOUR * 60;
        const defaultEndMinutes = DEFAULT_END_HOUR * 60;

        const allShownBlocks = [...currentSectionSchedules, ...activeGhostBlocks];
        if (allShownBlocks.length === 0) {
            return {
                displayStartHour: DEFAULT_START_HOUR,
                displayEndHour: DEFAULT_END_HOUR,
            };
        }

        let minStartMinutes = defaultStartMinutes;
        let maxEndMinutes = defaultEndMinutes;

        for (const block of allShownBlocks) {
            minStartMinutes = Math.min(minStartMinutes, timeToMinutes(block.start_time));
            maxEndMinutes = Math.max(maxEndMinutes, timeToMinutes(block.end_time));
        }

        return {
            displayStartHour: Math.floor(minStartMinutes / 60),
            displayEndHour: Math.ceil(maxEndMinutes / 60),
        };
    }, [currentSectionSchedules, activeGhostBlocks]);

    const selectedGrade = useMemo(() => {
        return gradeLevels.find((g) => g.id.toString() === selectedGradeId);
    }, [gradeLevels, selectedGradeId]);

    const selectedSection = useMemo(() => {
        return selectedGrade?.sections.find(
            (section) => section.id.toString() === selectedSectionId,
        );
    }, [selectedGrade, selectedSectionId]);

    const handleGridClick = (day: string) => {
        if (!selectedSectionId) {
            return;
        }

        setSelectedItem(null);
        addForm.setData({
            ...addForm.data,
            section_id: Number(selectedSectionId),
            subject_id:
                selectedSubjectId && selectedSubjectId !== 'none'
                    ? parseInt(selectedSubjectId)
                    : null,
            teacher_id:
                selectedTeacherId && selectedTeacherId !== 'none'
                    ? parseInt(selectedTeacherId)
                    : null,
            day: day,
        });
        setIsDialogOpen(true);
    };

    const handleItemClick = (e: React.MouseEvent, item: ScheduleItem) => {
        e.stopPropagation();
        setSelectedItem(item);
        editForm.setData({
            type: item.type,
            subject_id: item.subject_assignment?.teacher_subject?.subject?.id || null,
            teacher_id: item.subject_assignment?.teacher_subject?.teacher?.id || null,
            label: item.label || '',
            day: item.day,
            start_time: item.start_time.substring(0, 5),
            end_time: item.end_time.substring(0, 5),
        });
        setIsDialogOpen(true);
    };

    const handleAdd = () => {
        addForm.submit(store(), {
            onSuccess: () => {
                setIsDialogOpen(false);
                addForm.reset();
            },
        });
    };

    const handleUpdate = () => {
        if (!selectedItem) return;
        editForm.submit(update({ schedule: selectedItem.id }), {
            onSuccess: () => {
                setIsDialogOpen(false);
                editForm.reset();
            },
        });
    };

    const handleDelete = () => {
        setIsDeleteConfirmOpen(true);
    };

    const submitDelete = () => {
        if (!selectedItem) return;
        router.delete(destroy({ schedule: selectedItem.id }).url, {
            onSuccess: () => {
                setIsDeleteConfirmOpen(false);
                setIsDialogOpen(false);
            },
        });
    };

    if (!activeYear) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-[400px] flex-col items-center justify-center gap-4 text-center">
                    <div className="rounded-full bg-amber-100 p-4 dark:bg-amber-900/20">
                        <AlertCircle className="size-10 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-xl font-bold tracking-tight">
                            System Notice
                        </h2>
                        <p className="max-w-sm text-sm font-medium text-muted-foreground">
                            An active School Year must be initialized before
                            managing schedules.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        className="text-xs"
                        onClick={() => router.get('/admin/academic-controls')}
                    >
                        Go to Academic Controls
                    </Button>
                </div>
            </AppLayout>
        );
    }

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule Builder" />
            <TooltipProvider>
                <div className="flex flex-col gap-6">
                    <Card className="gap-2">
                        <CardContent className="space-y-4 p-4 sm:p-6">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="flex items-center gap-2">
                                    <Calendar className="size-4 text-muted-foreground" />
                                    <p className="text-sm font-medium">
                                        Weekly Schedule Grid
                                    </p>
                                    <Badge variant="outline">
                                        {activeYear.name}
                                    </Badge>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {currentSectionSchedules.length}{' '}
                                        {currentSectionSchedules.length === 1
                                            ? 'Slot'
                                            : 'Slots'}
                                    </Badge>
                                    {selectedSection && (
                                        <Badge variant="outline">
                                            {selectedSection.name}
                                        </Badge>
                                    )}
                                    {selectedTeacherId &&
                                        selectedTeacherId !== 'none' && (
                                            <Badge variant="outline">
                                                Overlay Active
                                            </Badge>
                                        )}
                                </div>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div className="grid gap-1.5">
                                    <Label className="text-xs text-muted-foreground">
                                        Grade Level
                                    </Label>
                                    <Select
                                        value={selectedGradeId}
                                        onValueChange={handleGradeChange}
                                    >
                                        <SelectTrigger className="h-9 w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {gradeLevels.map((g) => (
                                                <SelectItem
                                                    key={g.id}
                                                    value={g.id.toString()}
                                                >
                                                    {g.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label className="text-xs text-muted-foreground">
                                        Section
                                    </Label>
                                    <Select
                                        value={selectedSectionId}
                                        onValueChange={setSelectedSectionId}
                                    >
                                        <SelectTrigger className="h-9 w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {selectedGrade?.sections.map(
                                                (section) => (
                                                    <SelectItem
                                                        key={section.id}
                                                        value={section.id.toString()}
                                                    >
                                                        {section.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label className="text-xs text-muted-foreground">
                                        Highlight Subject
                                    </Label>
                                    <Select
                                        value={selectedSubjectId || 'none'}
                                        onValueChange={(val) =>
                                            setSelectedSubjectId(
                                                val === 'none' ? null : val,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-9 w-full">
                                            <SelectValue placeholder="All Subjects" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Show All
                                            </SelectItem>
                                            {subjects.map((s) => (
                                                <SelectItem
                                                    key={s.id}
                                                    value={s.id.toString()}
                                                >
                                                    {s.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label className="flex items-center gap-1 text-xs text-muted-foreground">
                                        Teacher Availability
                                        <Info className="size-3" />
                                    </Label>
                                    <Select
                                        value={selectedTeacherId || 'none'}
                                        onValueChange={(val) =>
                                            setSelectedTeacherId(
                                                val === 'none' ? null : val,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-9 w-full">
                                            <SelectValue placeholder="Select Teacher..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                No Overlay
                                            </SelectItem>
                                            {filteredTeachers.map((t) => (
                                                <SelectItem
                                                    key={t.id}
                                                    value={t.id.toString()}
                                                >
                                                    {t.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Plus className="size-3.5" />
                                Click any day column to add a schedule slot.
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="gap-2 overflow-hidden">
                        <CardContent className="relative p-0">
                            {selectedSection ? (
                                <div className="overflow-x-auto">
                                    <div
                                        className="relative flex min-w-[980px]"
                                        style={{
                                            height:
                                                (displayEndHour - displayStartHour) *
                                                    HOUR_HEIGHT +
                                                40,
                                        }}
                                    >
                                        {/* Time Rulers */}
                                        <div className="sticky left-0 z-30 w-20 shrink-0 border-r bg-card pt-10 pl-0.5">
                                            {Array.from({
                                                length:
                                                    displayEndHour - displayStartHour + 1,
                                            }).map((_, i) => (
                                                <div
                                                    key={i}
                                                    className="relative pr-2 text-right"
                                                    style={{
                                                        height:
                                                            i ===
                                                            displayEndHour -
                                                                displayStartHour
                                                                ? 0
                                                                : HOUR_HEIGHT,
                                                    }}
                                                >
                                                    <span className="absolute top-0 right-2 -translate-y-1/2 font-mono text-[10px] leading-none font-medium whitespace-nowrap text-muted-foreground uppercase">
                                                        {`${(displayStartHour + i) % 12 || 12}:00 ${displayStartHour + i >= 12 ? 'PM' : 'AM'}`}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="relative flex-1">
                                            {/* Days Header */}
                                            <div className="sticky top-0 z-40 flex h-10 border-b bg-card">
                                                {DAYS.map((day) => (
                                                    <div
                                                        key={day}
                                                        className="flex flex-1 items-center justify-center border-r text-xs font-semibold tracking-wider text-muted-foreground uppercase last:border-r-0"
                                                    >
                                                        {day}
                                                    </div>
                                                ))}
                                            </div>

                                            {/* Grid Background Lines */}
                                            <div className="pointer-events-none absolute inset-0 z-0 pt-10">
                                                {Array.from({
                                                    length:
                                                        displayEndHour - displayStartHour,
                                                }).map((_, i) => (
                                                    <div
                                                        key={i}
                                                        className="border-b border-dashed border-border/40"
                                                        style={{
                                                            height: HOUR_HEIGHT,
                                                        }}
                                                    />
                                                ))}
                                            </div>

                                            {/* Interactive Columns and Content */}
                                            <div className="absolute inset-0 z-10 flex pt-10">
                                                {DAYS.map((day) => (
                                                    <div
                                                        key={day}
                                                        className="relative flex-1 cursor-pointer border-r transition-colors last:border-r-0 hover:bg-muted/10"
                                                        onClick={() =>
                                                            handleGridClick(day)
                                                        }
                                                    >
                                                        {/* GHOST BLOCKS */}
                                                        {activeGhostBlocks
                                                            .filter(
                                                                (g) =>
                                                                    g.day ===
                                                                    day,
                                                            )
                                                            .map((ghost) => {
                                                                const isConflicting =
                                                                    currentSectionSchedules.some(
                                                                        (s) =>
                                                                            s.day ===
                                                                                day &&
                                                                            timeToMinutes(
                                                                                s.start_time,
                                                                            ) <
                                                                                timeToMinutes(
                                                                                    ghost.end_time,
                                                                                ) &&
                                                                            timeToMinutes(
                                                                                s.end_time,
                                                                            ) >
                                                                                timeToMinutes(
                                                                                    ghost.start_time,
                                                                                ),
                                                                    );

                                                                const ghostSubjectName = ghost.subject_assignment?.teacher_subject?.subject?.subject_name || ghost.label;
                                                                
                                                                return (
                                                                    <div
                                                                        key={
                                                                            ghost.id
                                                                        }
                                                                        className={cn(
                                                                            'absolute inset-x-1 z-0 rounded-md border px-1 py-1 opacity-[0.65]',
                                                                            isConflicting
                                                                                ? 'border-dashed border-destructive/50 bg-destructive/10 text-destructive grayscale-[0.2]'
                                                                                : 'border-dashed border-border/80 bg-muted/40 text-muted-foreground',
                                                                        )}
                                                                        style={{
                                                                            top: getPosition(
                                                                                ghost.start_time,
                                                                                displayStartHour,
                                                                            ),
                                                                            height: getHeight(
                                                                                ghost.start_time,
                                                                                ghost.end_time,
                                                                            ),
                                                                        }}
                                                                    >
                                                                        <div className="p-1">
                                                                            <p
                                                                                className={cn(
                                                                                    'truncate text-[10px] font-semibold tracking-wide uppercase',
                                                                                    isConflicting ? 'text-destructive' : ''
                                                                                )}
                                                                            >
                                                                                {isConflicting
                                                                                    ? 'Conflict'
                                                                                    : ghostSubjectName || 'Occupied'}
                                                                            </p>
                                                                            {ghost._sections && ghost._sections.length > 0 && !isConflicting && (
                                                                                <p className="mt-[2px] truncate text-[9px] leading-tight font-medium opacity-80 uppercase tracking-widest">
                                                                                    [{ghost._sections.join(', ')}]
                                                                                </p>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                );
                                                            })}

                                                        {/* REAL SCHEDULE CARDS */}
                                                        {currentSectionSchedules
                                                            .filter(
                                                                (s) =>
                                                                    s.day ===
                                                                    day,
                                                            )
                                                            .map((item) => {
                                                                const subjectName =
                                                                    item
                                                                        .subject_assignment
                                                                        ?.teacher_subject
                                                                        ?.subject
                                                                        ?.subject_name ||
                                                                    item.label;
                                                                const teacherName =
                                                                    item
                                                                        .subject_assignment
                                                                        ?.teacher_subject
                                                                        ?.teacher
                                                                        ?.name;

                                                                const isHighlighted =
                                                                    selectedSubjectId &&
                                                                    selectedSubjectId !==
                                                                        'none'
                                                                        ? subjects.find(
                                                                              (
                                                                                  s,
                                                                              ) =>
                                                                                  s.id.toString() ===
                                                                                  selectedSubjectId,
                                                                          )
                                                                              ?.name ===
                                                                          subjectName
                                                                        : true;
                                                                const hasTeacherConflict =
                                                                    selectedTeacherId &&
                                                                    selectedTeacherId !==
                                                                        'none' &&
                                                                    activeGhostBlocks.some(
                                                                        (g) =>
                                                                            g.day ===
                                                                                day &&
                                                                            timeToMinutes(
                                                                                item.start_time,
                                                                            ) <
                                                                                timeToMinutes(
                                                                                    g.end_time,
                                                                                ) &&
                                                                            timeToMinutes(
                                                                                item.end_time,
                                                                            ) >
                                                                                timeToMinutes(
                                                                                    g.start_time,
                                                                                ),
                                                                    );

                                                                return (
                                                                    <div
                                                                        key={
                                                                            item.id
                                                                        }
                                                                        className={cn(
                                                                            'group absolute z-10 cursor-pointer rounded-md border p-2 shadow-sm transition-[opacity,border-color,box-shadow]',
                                                                            isHighlighted
                                                                                ? 'opacity-100 shadow-sm'
                                                                                : 'opacity-40 grayscale',
                                                                            item.type ===
                                                                                'academic'
                                                                                ? getSubjectColor(subjectName)
                                                                                : 'border-border bg-muted/70 text-foreground',
                                                                            hasTeacherConflict
                                                                                ? 'right-1 left-[28%] border-destructive/50 bg-destructive/10 ring-1 ring-destructive/30'
                                                                                : 'right-1 left-1',
                                                                        )}
                                                                        style={{
                                                                            top: getPosition(
                                                                                item.start_time,
                                                                                displayStartHour,
                                                                            ),
                                                                            height: getHeight(
                                                                                item.start_time,
                                                                                item.end_time,
                                                                            ),
                                                                        }}
                                                                        onClick={(
                                                                            e,
                                                                        ) =>
                                                                            handleItemClick(
                                                                                e,
                                                                                item,
                                                                            )
                                                                        }
                                                                    >
                                                                        <div className="flex h-full flex-col justify-between overflow-hidden">
                                                                            <div className="space-y-1">
                                                                                <div className="flex items-center justify-between gap-1">
                                                                                    <p
                                                                                        className="truncate text-xs leading-tight font-semibold"
                                                                                    >
                                                                                        {
                                                                                            subjectName
                                                                                        }
                                                                                    </p>
                                                                                    {hasTeacherConflict && (
                                                                                        <AlertTriangle className="size-3 shrink-0 text-destructive" />
                                                                                    )}
                                                                                </div>
                                                                                {teacherName && (
                                                                                    <p className="flex items-center gap-1 truncate text-[10px] text-muted-foreground">
                                                                                        <User className="size-3" />
                                                                                        {
                                                                                            teacherName
                                                                                        }
                                                                                    </p>
                                                                                )}
                                                                            </div>
                                                                            <div className="flex items-center gap-1 opacity-75">
                                                                                <Clock className="size-3" />
                                                                                <span className="font-mono text-[10px]">
                                                                                    {item.start_time.substring(
                                                                                        0,
                                                                                        5,
                                                                                    )}{' '}
                                                                                    -{' '}
                                                                                    {item.end_time.substring(
                                                                                        0,
                                                                                        5,
                                                                                    )}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                );
                                                            })}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex h-[220px] items-center justify-center px-6 text-center">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">
                                            Select a section first
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Choose a grade level and section to
                                            start placing schedule blocks.
                                        </p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogContent className="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>
                                    {selectedItem
                                        ? 'Edit Scheduled Slot'
                                        : 'Assign New Schedule'}
                                </DialogTitle>
                                <DialogDescription>
                                    Configure timing and assignments for this
                                    period.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-5 py-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label className="text-sm font-medium">
                                            Start Time
                                        </Label>
                                        <TimePickerField
                                            value={
                                                selectedItem
                                                    ? editForm.data.start_time
                                                    : addForm.data.start_time
                                            }
                                            placeholder="Select start time"
                                            onChange={(value) => {
                                                if (selectedItem) {
                                                    editForm.setData(
                                                        'start_time',
                                                        value,
                                                    );
                                                } else {
                                                    addForm.setData(
                                                        'start_time',
                                                        value,
                                                    );
                                                }
                                            }}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label className="text-sm font-medium">
                                            End Time
                                        </Label>
                                        <TimePickerField
                                            value={
                                                selectedItem
                                                    ? editForm.data.end_time
                                                    : addForm.data.end_time
                                            }
                                            placeholder="Select end time"
                                            onChange={(value) => {
                                                if (selectedItem) {
                                                    editForm.setData(
                                                        'end_time',
                                                        value,
                                                    );
                                                } else {
                                                    addForm.setData(
                                                        'end_time',
                                                        value,
                                                    );
                                                }
                                            }}
                                        />
                                    </div>
                                </div>

                                <InputError
                                    message={
                                        selectedItem
                                            ? editForm.errors.start_time
                                            : addForm.errors.start_time || addForm.errors.teacher_id
                                    }
                                    className="col-span-2"
                                />

                                <div className="grid gap-2">
                                    <Label className="text-sm font-medium">
                                        Period Type
                                    </Label>
                                    <Select
                                        value={
                                            selectedItem
                                                ? editForm.data.type
                                                : addForm.data.type
                                        }
                                        onValueChange={(val) => {
                                            if (selectedItem) {
                                                editForm.setData(
                                                    'type',
                                                    val as
                                                        | 'academic'
                                                        | 'break'
                                                        | 'ceremony',
                                                );
                                            } else {
                                                addForm.setData(
                                                    'type',
                                                    val as
                                                        | 'academic'
                                                        | 'break'
                                                        | 'ceremony',
                                                );
                                            }
                                        }}
                                    >
                                        <SelectTrigger className="h-10 rounded-lg">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="academic">
                                                Academic Subject
                                            </SelectItem>
                                            <SelectItem value="break">
                                                Institutional Break
                                            </SelectItem>
                                            <SelectItem value="ceremony">
                                                Campus Ceremony
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {(selectedItem
                                    ? editForm.data.type
                                    : addForm.data.type) === 'academic' ? (
                                    <div className="space-y-4 rounded-xl border bg-muted/30 p-4">
                                        <div className="flex items-center gap-2 text-primary">
                                            <BookOpen className="size-4" />
                                            <p className="text-xs font-semibold tracking-wider uppercase">
                                                Academic Assignment
                                            </p>
                                        </div>

                                        <div className="grid gap-4">
                                            <div className="grid gap-2">
                                                <Label className="text-sm font-medium">
                                                    Subject
                                                </Label>
                                                <Select
                                                    value={(selectedItem ? editForm.data.subject_id : addForm.data.subject_id)?.toString() || ''}
                                                    onValueChange={(val) => {
                                                        const numVal = parseInt(val);
                                                        if (selectedItem) {
                                                            editForm.setData('subject_id', numVal);
                                                        } else {
                                                            addForm.setData('subject_id', numVal);
                                                        }
                                                    }}
                                                >
                                                    <SelectTrigger className="h-10 rounded-lg">
                                                        <SelectValue placeholder="Select subject..." />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-h-72">
                                                        {subjects.map((s) => (
                                                            <SelectItem key={s.id} value={s.id.toString()}>
                                                                {s.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="text-sm font-medium">
                                                    Assigned Teacher
                                                </Label>
                                                <Select
                                                    value={(selectedItem ? editForm.data.teacher_id : addForm.data.teacher_id)?.toString() || ''}
                                                    onValueChange={(val) => {
                                                        const numVal = parseInt(val);
                                                        if (selectedItem) {
                                                            editForm.setData('teacher_id', numVal);
                                                        } else {
                                                            addForm.setData('teacher_id', numVal);
                                                        }
                                                    }}
                                                    disabled={availableTeachersForForm.length === 0}
                                                >
                                                    <SelectTrigger className="h-10 rounded-lg">
                                                        <SelectValue
                                                            placeholder={
                                                                availableTeachersForForm.length > 0
                                                                    ? 'Select teacher...'
                                                                    : 'Select subject first'
                                                            }
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-h-72">
                                                        {availableTeachersForForm.map((t) => (
                                                            <SelectItem key={t.id} value={t.id.toString()}>
                                                                {t.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="grid gap-2">
                                        <Label className="text-sm font-medium">
                                            Label
                                        </Label>
                                        <Input
                                            className="h-10 rounded-lg"
                                            placeholder="e.g. Recess or Lunch"
                                            value={
                                                selectedItem
                                                    ? editForm.data.label
                                                    : addForm.data.label
                                            }
                                            onChange={(e) => {
                                                if (selectedItem) {
                                                    editForm.setData(
                                                        'label',
                                                        e.target.value,
                                                    );
                                                } else {
                                                    addForm.setData(
                                                        'label',
                                                        e.target.value,
                                                    );
                                                }
                                            }}
                                        />
                                    </div>
                                )}
                            </div>
                            <DialogFooter className="shrink-0 gap-2">
                                {selectedItem && (
                                    <Button
                                        variant="ghost"
                                        className="mr-auto gap-2 text-destructive"
                                        onClick={handleDelete}
                                    >
                                        <Trash2 className="size-4" /> Remove
                                    </Button>
                                )}
                                <Button
                                    variant="outline"
                                    onClick={() => setIsDialogOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={
                                        selectedItem ? handleUpdate : handleAdd
                                    }
                                    disabled={
                                        addForm.processing ||
                                        editForm.processing ||
                                        (selectedItem
                                            ? editForm.data.type === 'academic' && (!editForm.data.subject_id || !editForm.data.teacher_id)
                                            : addForm.data.type === 'academic' && (!addForm.data.subject_id || !addForm.data.teacher_id))
                                    }
                                >
                                    {selectedItem ? 'Update' : 'Create'}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </TooltipProvider>

            <ActionConfirmDialog
                open={isDeleteConfirmOpen}
                onOpenChange={setIsDeleteConfirmOpen}
                title="Remove Schedule Slot"
                description="Are you sure you want to remove this schedule slot? This will free up the time period for this section and teacher."
                variant="destructive"
                confirmLabel="Remove Slot"
                onConfirm={submitDelete}
            />
        </AppLayout>
    </>
);
}
