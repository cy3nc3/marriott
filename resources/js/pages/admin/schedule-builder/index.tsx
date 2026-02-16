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
import { useState, useMemo, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tooltip, TooltipProvider } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { store, update, destroy } from '@/routes/admin/schedule_builder';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schedule Builder',
        href: '/admin/schedule-builder',
    },
];

// Configuration
const START_HOUR = 7;
const END_HOUR = 17;
const HOUR_HEIGHT = 56;
const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

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

    useEffect(() => {
        const grade = gradeLevels.find(
            (g) => g.id.toString() === selectedGradeId,
        );
        if (grade && grade.sections.length > 0) {
            setSelectedSectionId(grade.sections[0].id.toString());
        } else {
            setSelectedSectionId('');
        }
    }, [selectedGradeId, gradeLevels]);

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

    const getPosition = (time: string) =>
        ((timeToMinutes(time) - START_HOUR * 60) / 60) * HOUR_HEIGHT;
    const getHeight = (start: string, end: string) =>
        ((timeToMinutes(end) - timeToMinutes(start)) / 60) * HOUR_HEIGHT;

    const filteredTeachers = useMemo(() => {
        if (!selectedSubjectId || selectedSubjectId === 'none') return teachers;
        const sub = subjects.find((s) => s.id.toString() === selectedSubjectId);
        return teachers.filter((t) => sub?.qualifiedTeachers.includes(t.id));
    }, [selectedSubjectId, subjects, teachers]);

    const currentSectionSchedules = useMemo(() => {
        return sectionSchedules.filter(
            (s) => s.section_id.toString() === selectedSectionId,
        );
    }, [sectionSchedules, selectedSectionId]);

    const activeGhostBlocks = useMemo(() => {
        if (!selectedTeacherId || selectedTeacherId === 'none') return [];
        return sectionSchedules.filter(
            (s) =>
                s.subject_assignment?.teacher_subject?.teacher?.id.toString() ===
                    selectedTeacherId &&
                s.section_id.toString() !== selectedSectionId,
        );
    }, [sectionSchedules, selectedTeacherId, selectedSectionId]);

    const handleGridClick = (day: string) => {
        setSelectedItem(null);
        addForm.setData({
            ...addForm.data,
            section_id: parseInt(selectedSectionId),
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
        if (!selectedItem) return;
        if (confirm('Are you sure you want to remove this slot?')) {
            router.delete(destroy({ schedule: selectedItem.id }).url, {
                onSuccess: () => setIsDialogOpen(false),
            });
        }
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

    const currentAddSubject = subjects.find(
        (s) => s.id === addForm.data.subject_id,
    );
    const currentAddTeacher = teachers.find(
        (t) => t.id === addForm.data.teacher_id,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule Builder" />
            <TooltipProvider>
                <div className="flex flex-col gap-6">
                    <Card className="gap-0 pt-0">
                        <CardHeader className="flex shrink-0 flex-col items-start justify-between gap-4 border-b md:flex-row md:items-center">
                            <div className="flex flex-wrap items-center gap-4">
                                <div className="grid gap-1">
                                    <Label className="text-xs text-muted-foreground">
                                        Grade Level
                                    </Label>
                                    <Select
                                        value={selectedGradeId}
                                        onValueChange={setSelectedGradeId}
                                    >
                                        <SelectTrigger className="h-9 w-[140px]">
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
                                <div className="grid gap-1">
                                    <Label className="text-xs text-muted-foreground">
                                        Section
                                    </Label>
                                    <Select
                                        value={selectedSectionId}
                                        onValueChange={setSelectedSectionId}
                                    >
                                        <SelectTrigger className="h-9 w-[160px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {gradeLevels
                                                .find(
                                                    (g) =>
                                                        g.id.toString() ===
                                                        selectedGradeId,
                                                )
                                                ?.sections.map((s) => (
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

                                <div className="mx-2 hidden h-15 w-px bg-border lg:block" />

                                <div className="grid gap-1">
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
                                        <SelectTrigger className="h-9 w-[180px]">
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

                                <div className="grid gap-1">
                                    <Label className="flex items-center gap-1 text-xs text-muted-foreground">
                                        Teacher Availability{' '}
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
                                        <SelectTrigger className="h-9 w-[180px]">
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
                        </CardHeader>

                        <CardContent className="relative p-0">
                            <div
                                className="relative flex w-full"
                                style={{
                                    height:
                                        (END_HOUR - START_HOUR) * HOUR_HEIGHT +
                                        40,
                                }}
                            >
                                {/* Time Rulers */}
                                <div className="sticky left-0 z-30 w-20 shrink-0 border-r bg-background pt-10 pl-0.5">
                                    {Array.from({
                                        length: END_HOUR - START_HOUR + 1,
                                    }).map((_, i) => (
                                        <div
                                            key={i}
                                            className="relative pr-2 text-right"
                                            style={{
                                                height:
                                                    i === END_HOUR - START_HOUR
                                                        ? 0
                                                        : HOUR_HEIGHT,
                                            }}
                                        >
                                            <span className="absolute top-0 right-2 -translate-y-1/2 font-mono text-[10px] leading-none font-medium whitespace-nowrap text-muted-foreground uppercase">
                                                {`${(START_HOUR + i) % 12 || 12}:00 ${START_HOUR + i >= 12 ? 'PM' : 'AM'}`}
                                            </span>
                                        </div>
                                    ))}
                                </div>

                                <div className="relative min-w-[800px] flex-1">
                                    {/* Days Header */}
                                    <div className="sticky top-0 z-40 flex h-10 border-b bg-background">
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
                                            length: END_HOUR - START_HOUR,
                                        }).map((_, i) => (
                                            <div
                                                key={i}
                                                className="border-b border-dashed border-border/40"
                                                style={{ height: HOUR_HEIGHT }}
                                            />
                                        ))}
                                    </div>

                                    {/* Interactive Columns and Content */}
                                    <div className="absolute inset-0 z-10 flex pt-10">
                                        {DAYS.map((day) => (
                                            <div
                                                key={day}
                                                className="relative flex-1 cursor-pointer border-r transition-colors last:border-r-0 hover:bg-muted/5"
                                                onClick={() =>
                                                    handleGridClick(day)
                                                }
                                            >
                                                {/* GHOST BLOCKS */}
                                                {activeGhostBlocks
                                                    .filter(
                                                        (g) => g.day === day,
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

                                                        return (
                                                            <div
                                                                key={ghost.id}
                                                                className={cn(
                                                                    'absolute left-0 z-0 border-y bg-[repeating-linear-gradient(45deg,transparent,transparent_10px,rgba(245,158,11,0.05)_10px,rgba(245,158,11,0.05)_20px)] transition-all',
                                                                    isConflicting
                                                                        ? 'w-[100%] border-destructive bg-destructive/5'
                                                                        : 'w-full border-amber-500/20',
                                                                )}
                                                                style={{
                                                                    top: getPosition(
                                                                        ghost.start_time,
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
                                                                            'truncate text-[10px] font-bold uppercase',
                                                                            isConflicting
                                                                                ? 'text-destructive'
                                                                                : 'text-amber-600/50',
                                                                        )}
                                                                    >
                                                                        {isConflicting
                                                                            ? 'CONFLICT'
                                                                            : 'OCCUPIED'}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}

                                                {/* REAL SCHEDULE CARDS */}
                                                {currentSectionSchedules
                                                    .filter(
                                                        (s) => s.day === day,
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
                                                                ?.teacher?.name;

                                                        const isHighlighted =
                                                            selectedSubjectId &&
                                                            selectedSubjectId !==
                                                                'none'
                                                                ? subjects.find(
                                                                      (s) =>
                                                                          s.id.toString() ===
                                                                          selectedSubjectId,
                                                                  )?.name ===
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
                                                                key={item.id}
                                                                className={cn(
                                                                    'group absolute z-10 cursor-pointer rounded-md border p-2 shadow-sm transition-all',
                                                                    isHighlighted
                                                                        ? 'z-20 scale-[1.01] opacity-100 shadow-md'
                                                                        : 'opacity-40 grayscale',
                                                                    item.type ===
                                                                        'academic'
                                                                        ? 'border-primary/20 bg-background hover:border-primary'
                                                                        : 'border-transparent bg-muted',
                                                                    hasTeacherConflict
                                                                        ? 'right-1 left-[30%] border-destructive bg-destructive/5 ring-1 ring-destructive/20'
                                                                        : 'right-1 left-1',
                                                                )}
                                                                style={{
                                                                    top: getPosition(
                                                                        item.start_time,
                                                                    ),
                                                                    height: getHeight(
                                                                        item.start_time,
                                                                        item.end_time,
                                                                    ),
                                                                }}
                                                                onClick={(e) =>
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
                                                                                className={cn(
                                                                                    'truncate text-xs leading-tight font-semibold',
                                                                                    item.type ===
                                                                                        'academic'
                                                                                        ? 'text-primary'
                                                                                        : 'text-muted-foreground',
                                                                                )}
                                                                            >
                                                                                {
                                                                                    subjectName
                                                                                }
                                                                            </p>
                                                                            {hasTeacherConflict && (
                                                                                <AlertTriangle className="size-3 shrink-0 animate-pulse text-destructive" />
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
                                                                    <div className="flex items-center gap-1 opacity-70">
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
                        </CardContent>
                    </Card>

                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogContent className="sm:max-w-[450px]">
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
                            <div className="space-y-6 py-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label className="text-xs text-muted-foreground">
                                            Start Time
                                        </Label>
                                        <Input
                                            type="time"
                                            value={
                                                selectedItem
                                                    ? editForm.data.start_time
                                                    : addForm.data.start_time
                                            }
                                            onChange={(e) => {
                                                if (selectedItem) {
                                                    editForm.setData(
                                                        'start_time',
                                                        e.target.value,
                                                    );
                                                } else {
                                                    addForm.setData(
                                                        'start_time',
                                                        e.target.value,
                                                    );
                                                }
                                            }}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label className="text-xs text-muted-foreground">
                                            End Time
                                        </Label>
                                        <Input
                                            type="time"
                                            value={
                                                selectedItem
                                                    ? editForm.data.end_time
                                                    : addForm.data.end_time
                                            }
                                            onChange={(e) => {
                                                if (selectedItem) {
                                                    editForm.setData(
                                                        'end_time',
                                                        e.target.value,
                                                    );
                                                } else {
                                                    addForm.setData(
                                                        'end_time',
                                                        e.target.value,
                                                    );
                                                }
                                            }}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label className="text-xs text-muted-foreground">
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
                                                    val as any,
                                                );
                                            } else {
                                                addForm.setData(
                                                    'type',
                                                    val as any,
                                                );
                                            }
                                        }}
                                    >
                                        <SelectTrigger>
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
                                    <div className="space-y-3 rounded-lg border bg-muted/20 p-3">
                                        <div className="flex items-center gap-2 text-primary">
                                            <BookOpen className="size-4" />
                                            <p className="text-xs font-semibold tracking-wider uppercase">
                                                Assignment Preview
                                            </p>
                                        </div>

                                        <div className="grid gap-2">
                                            <div className="grid gap-1">
                                                <Label className="text-xs text-muted-foreground">
                                                    Subject
                                                </Label>
                                                <div className="rounded-md border bg-background px-3 py-2 text-sm font-medium">
                                                    {currentAddSubject
                                                        ? currentAddSubject.name
                                                        : 'Select subject above'}
                                                </div>
                                            </div>

                                            <div className="grid gap-1">
                                                <Label className="text-xs text-muted-foreground">
                                                    Assigned Teacher
                                                </Label>
                                                <div className="rounded-md border bg-background px-3 py-2 text-sm font-medium">
                                                    {currentAddTeacher
                                                        ? currentAddTeacher.name
                                                        : 'Select teacher above'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="grid gap-2">
                                        <Label className="text-xs text-muted-foreground">
                                            Label
                                        </Label>
                                        <Input
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
                                        (addForm.data.type === 'academic' &&
                                            (!addForm.data.subject_id ||
                                                !addForm.data.teacher_id))
                                    }
                                >
                                    {selectedItem ? 'Update' : 'Create'}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </TooltipProvider>
        </AppLayout>
    );
}
