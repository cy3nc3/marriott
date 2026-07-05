import { cn } from '@/lib/utils';
import { Head, useForm, router } from '@inertiajs/react';
import { Download, Edit2, ListFilter, Plus, Search, Trash2, UserPlus, Users, X } from 'lucide-react';
import { useState, useMemo } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import {
    store,
    update,
    destroy,
    certify,
} from '@/routes/admin/curriculum_manager';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Academic Controls',
        href: '/admin/academic-controls',
    },
    {
        title: 'Curriculum Manager',
        href: '/admin/curriculum-manager',
    },
];

interface Teacher {
    id: number;
    name: string;
    initial: string;
    qualification_status?: string | null;
}

interface Subject {
    id: number;
    grade_level_id: number;
    subject_code: string;
    subject_name: string;
    required_weekly_minutes: number;
    teachers: Teacher[];
}

interface GradeLevel {
    id: number;
    name: string;
    level_order: number;
    subjects: Subject[];
}

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface FacultyListProps {
    allTeachers: Teacher[];
    selectedDetails: {
        id: number;
    }[];
    searchQuery: string;
    onSearchChange: (val: string) => void;
    onToggle: (id: number) => void;
    filteredTeachers: Teacher[];
}

const FacultyCertificationList = ({
    allTeachers,
    selectedDetails,
    searchQuery,
    onSearchChange,
    onToggle,
    filteredTeachers,
}: FacultyListProps) => {
    const getInitials = useInitials();

    return (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <Label>Search Faculty</Label>
                <div className="relative">
                    <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                    <Input
                        placeholder="Search and select qualified teachers..."
                        className="pl-10"
                        value={searchQuery}
                        onChange={(e) => onSearchChange(e.target.value)}
                    />
                    {searchQuery && (
                        <div className="absolute top-full right-0 left-0 z-50 mt-1 max-h-48 overflow-auto rounded-md border bg-popover p-1 shadow-md">
                            {filteredTeachers.length > 0 ? (
                                filteredTeachers.map((teacher) => (
                                    <button
                                        key={teacher.id}
                                        type="button"
                                        className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                                        onClick={() => onToggle(teacher.id)}
                                    >
                                        <Avatar className="size-6">
                                            <AvatarFallback>
                                                {getInitials(teacher.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="font-medium">
                                            {teacher.name}
                                        </span>
                                        <Plus className="ml-auto size-3 opacity-50" />
                                    </button>
                                ))
                            ) : (
                                <div className="px-2 py-1.5 text-sm text-muted-foreground">
                                    No matches found.
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            <div className="space-y-4">
                <Label>Qualified Teachers ({selectedDetails.length}):</Label>

                {selectedDetails.length > 0 ? (
                    <div className="grid gap-3">
                        {selectedDetails.map((detail) => {
                            const teacher = allTeachers.find((t) => t.id === detail.id);
                            if (!teacher) return null;

                            return (
                                <div
                                    key={detail.id}
                                    className="rounded-lg border bg-muted/20 p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <Avatar className="size-8">
                                                <AvatarFallback>
                                                    {getInitials(teacher.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {teacher.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Faculty Member
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => onToggle(detail.id)}
                                            className="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/10 p-8 text-center">
                        <Users className="size-8 text-muted-foreground/20" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No Qualified Teachers Selected
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default function CurriculumManager({
    gradeLevels,
    teachers,
}: {
    gradeLevels: GradeLevel[];
    teachers: Teacher[];
}) {
    const getInitials = useInitials();
    const [activeTab, setActiveTab] = useState(
        gradeLevels[0]?.id.toString() || '',
    );
    const [isAddSubjectOpen, setIsAddSubjectOpen] = useState(false);
    const [isCertifyOpen, setIsCertifyOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [selectedSubject, setSelectedSubject] = useState<Subject | null>(
        null,
    );
    const [searchQuery, setSearchQuery] = useState('');
    const [subjectIdToDelete, setSubjectIdToDelete] = useState<number | null>(
        null,
    );

    const addForm = useForm({
        grade_level_id: activeTab,
        subject_code: '',
        subject_name: '',
        required_weekly_minutes: 200,
        teacher_details: [] as {
            id: number;
        }[],
    });

    const editForm = useForm({
        subject_code: '',
        subject_name: '',
        required_weekly_minutes: 200,
    });

    const certifyForm = useForm({
        teacher_details: [] as {
            id: number;
        }[],
    });

    const currentGrade = useMemo(
        () => gradeLevels.find((g) => g.id.toString() === activeTab),
        [gradeLevels, activeTab],
    );

    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return [];
        const currentSelectedIds = (
            isCertifyOpen ? certifyForm.data.teacher_details : addForm.data.teacher_details
        ).map((d) => d.id);
        return teachers
            .filter(
                (t) =>
                    t.name.toLowerCase().includes(searchQuery.toLowerCase()) &&
                    !currentSelectedIds.includes(t.id),
            )
            .slice(0, 5);
    }, [
        teachers,
        searchQuery,
        addForm.data.teacher_details,
        certifyForm.data.teacher_details,
        isCertifyOpen,
    ]);

    const handleAddSubject = () => {
        addForm.post(store().url, {
            onSuccess: () => {
                setIsAddSubjectOpen(false);
                addForm.reset();
            },
        });
    };

    const handleUpdateSubject = () => {
        if (!selectedSubject) return;
        editForm.patch(update({ subject: selectedSubject.id }).url, {
            onSuccess: () => {
                setIsEditOpen(false);
                editForm.reset();
            },
        });
    };

    const handleCertify = () => {
        if (!selectedSubject) return;

        certifyForm.transform((data) => ({
            ...data,
            _method: 'patch',
        }));

        certifyForm.post(certify({ subject: selectedSubject.id }).url, {
            onSuccess: () => {
                setIsCertifyOpen(false);
                certifyForm.reset();
            },
        });
    };

    const handleDeleteSubject = (id: number) => {
        setSubjectIdToDelete(id);
    };

    const submitDeleteSubject = () => {
        if (!subjectIdToDelete) return;
        router.delete(destroy({ subject: subjectIdToDelete }).url, {
            onSuccess: () => setSubjectIdToDelete(null),
        });
    };

    const toggleTeacher = (teacherId: number, formType: 'add' | 'certify') => {
        const form = formType === 'add' ? addForm : certifyForm;
        const currentDetails = form.data.teacher_details;

        const exists = currentDetails.find((d) => d.id === teacherId);

        if (exists) {
            form.setData({
                ...form.data,
                teacher_details: currentDetails.filter((d) => d.id !== teacherId),
            });
        } else {
            form.setData({
                ...form.data,
                teacher_details: [
                    ...currentDetails,
                    {
                        id: teacherId,
                    },
                ],
            });
        }

        setSearchQuery('');
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Curriculum Manager" />
            <TooltipProvider>
                <div className="flex flex-col gap-6">
                    <Card>
                        <Tabs
                            value={activeTab}
                            onValueChange={(val) => {
                                setActiveTab(val);
                                addForm.setData('grade_level_id', val);
                            }}
                            className="flex w-full flex-1 flex-col gap-0"
                        >
                            <CardContent className="p-0">
                                <div className="flex flex-col gap-4 border-b p-6 lg:flex-row lg:items-center lg:justify-between">
                                    <TabsList>
                                        {gradeLevels.map((grade) => (
                                            <TabsTrigger
                                                key={grade.id}
                                                value={grade.id.toString()}
                                            >
                                                {grade.name}
                                            </TabsTrigger>
                                        ))}
                                    </TabsList>
                                    <div className="flex items-center gap-2">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="outline" size="sm" className="gap-2">
                                                    <Download className="size-4" />
                                                    Export
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => window.location.assign(`/admin/curriculum-manager/export?format=xlsx&grade_level_id=${activeTab}`)}>
                                                    Export as Excel (.xlsx)
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => window.location.assign(`/admin/curriculum-manager/export?format=csv&grade_level_id=${activeTab}`)}>
                                                    Export as CSV (.csv)
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => window.location.assign(`/admin/curriculum-manager/export?format=pdf&grade_level_id=${activeTab}`)}>
                                                    Export as PDF (.pdf)
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <Button
                                            size="sm"
                                            className="gap-2"
                                            onClick={() => {
                                                addForm.reset();
                                                addForm.setData({
                                                    grade_level_id: activeTab,
                                                    subject_code: '',
                                                    subject_name: '',
                                                    required_weekly_minutes: 200,
                                                    teacher_details: [],
                                                });
                                                setIsAddSubjectOpen(true);
                                            }}
                                        >
                                            <Plus className="size-4" />
                                            Add Subject
                                        </Button>
                                    </div>
                                </div>
                                {gradeLevels.map((grade) => (
                                    <TabsContent
                                        key={grade.id}
                                        value={grade.id.toString()}
                                        className="m-0 outline-none"
                                    >
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-[120px] pl-6">
                                                        Code
                                                    </TableHead>
                                                    <TableHead>
                                                        Descriptive Title
                                                    </TableHead>
                                                    <TableHead>
                                                        Weekly Minutes
                                                    </TableHead>
                                                    <TableHead>
                                                        Qualified Teachers
                                                    </TableHead>
                                                    <TableHead className="pr-6 text-right">
                                                        Actions
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {grade.subjects.map((sub) => (
                                                    <TableRow key={sub.id}>
                                                        <TableCell className="pl-6">
                                                            <Badge variant="outline">
                                                                {
                                                                    sub.subject_code
                                                                }
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="font-medium">
                                                            {sub.subject_name}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant="secondary">
                                                                {sub.required_weekly_minutes} min/week
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex -space-x-2">
                                                                {sub.teachers.slice(0, sub.teachers.length > 3 ? 2 : 3).map((t) => (
                                                                    <Tooltip key={t.id}>
                                                                        <TooltipTrigger asChild>
                                                                            <Avatar
                                                                                className={cn(
                                                                                    'size-8 border-2',
                                                                                    t.qualification_status ===
                                                                                        'fully_qualified'
                                                                                        ? 'border-emerald-500'
                                                                                        : 'border-amber-500',
                                                                                )}
                                                                            >
                                                                                <AvatarFallback>
                                                                                    {getInitials(
                                                                                        t.name,
                                                                                    )}
                                                                                </AvatarFallback>
                                                                            </Avatar>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent className="flex flex-col gap-1 p-2">
                                                                            <p className="text-xs font-bold leading-none">
                                                                                {t.name}
                                                                            </p>
                                                                            <p className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
                                                                                {(
                                                                                    t.qualification_status ||
                                                                                    'certified'
                                                                                ).replace(
                                                                                    '_',
                                                                                    ' ',
                                                                                )}
                                                                            </p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                ))}
                                                                {sub.teachers.length > 3 && (
                                                                    <div className="flex size-8 items-center justify-center rounded-full border-2 border-background bg-muted text-[10px] font-bold">
                                                                        +{sub.teachers.length - 2}
                                                                    </div>
                                                                )}
                                                                {sub.teachers.length === 0 && (
                                                                    <Badge variant="outline">
                                                                        No qualified teachers
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="pr-6 text-right">
                                                            <div className="flex justify-end gap-2">
                                                                <Tooltip>
                                                                    <TooltipTrigger
                                                                        asChild
                                                                    >
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            onClick={() => {
                                                                                setSelectedSubject(
                                                                                    sub,
                                                                                );
                                                                                certifyForm.setData(
                                                                                    'teacher_details',
                                                                                    sub.teachers.map(
                                                                                        (t) => ({
                                                                                            id: t.id,
                                                                                            qualification_status: t.qualification_status || 'fully_qualified',
                                                                                            retained_documents: t.eligibility_documents || [],
                                                                                            new_documents: [],
                                                                                        }),
                                                                                    ),
                                                                                );
                                                                                setIsCertifyOpen(
                                                                                    true,
                                                                                );
                                                                            }}
                                                                        >
                                                                            <UserPlus className="size-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        Manage
                                                                        Qualified
                                                                        Teachers
                                                                    </TooltipContent>
                                                                </Tooltip>

                                                                <Tooltip>
                                                                    <TooltipTrigger
                                                                        asChild
                                                                    >
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            onClick={() => {
                                                                                setSelectedSubject(
                                                                                    sub,
                                                                                );
                                                                                editForm.setData(
                                                                                    {
                                                                                        subject_code:
                                                                                            sub.subject_code,
                                                                                        subject_name:
                                                                                            sub.subject_name,
                                                                                        required_weekly_minutes:
                                                                                            sub.required_weekly_minutes,
                                                                                    },
                                                                                );
                                                                                setIsEditOpen(
                                                                                    true,
                                                                                );
                                                                            }}
                                                                        >
                                                                            <Edit2 className="size-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        Edit
                                                                        Details
                                                                    </TooltipContent>
                                                                </Tooltip>

                                                                <Tooltip>
                                                                    <TooltipTrigger
                                                                        asChild
                                                                    >
                                                                        <Button
                                                                            variant="destructive"
                                                                            size="icon"
                                                                            onClick={() =>
                                                                                handleDeleteSubject(
                                                                                    sub.id,
                                                                                )
                                                                            }
                                                                        >
                                                                            <Trash2 className="size-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        Delete
                                                                        Subject
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                                {grade.subjects.length ===
                                                    0 && (
                                                    <TableRow>
                                                        <TableCell
                                                            colSpan={4}
                                                            className="h-24 text-center"
                                                        >
                                                            <p className="text-sm text-muted-foreground">
                                                                No subjects
                                                                defined for this
                                                                level
                                                            </p>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </TabsContent>
                                ))}
                            </CardContent>
                        </Tabs>
                    </Card>

                    {/* Modals */}
                    <Dialog
                        open={isAddSubjectOpen}
                        onOpenChange={setIsAddSubjectOpen}
                    >
                        <DialogContent className="sm:max-w-[500px] max-h-[90vh] flex flex-col p-0">
                            <DialogHeader className="p-6 pb-2">
                                <DialogTitle>New Subject Entry</DialogTitle>
                                <DialogDescription>
                                    Define a core academic subject for{' '}
                                    <span className="font-medium text-primary">
                                        {currentGrade?.name}
                                    </span>
                                    .
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-6 py-4 overflow-y-auto px-6 flex-1">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label>Subject Code</Label>
                                        <Input
                                            placeholder="MATH7"
                                            value={addForm.data.subject_code}
                                            onChange={(e) =>
                                                addForm.setData(
                                                    'subject_code',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Descriptive Title</Label>
                                        <Input
                                            placeholder="Mathematics 7"
                                            value={addForm.data.subject_name}
                                            onChange={(e) =>
                                                addForm.setData(
                                                    'subject_name',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Weekly Minutes</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={1200}
                                            value={addForm.data.required_weekly_minutes}
                                            onChange={(e) =>
                                                addForm.setData(
                                                    'required_weekly_minutes',
                                                    Number(e.target.value),
                                                )
                                            }
                                        />
                                    </div>
                                </div>

                                <FacultyCertificationList
                                    allTeachers={teachers}
                                    selectedDetails={addForm.data.teacher_details}
                                    searchQuery={searchQuery}
                                    onSearchChange={setSearchQuery}
                                    onToggle={(id) => toggleTeacher(id, 'add')}
                                    filteredTeachers={filteredTeachers}
                                />
                            </div>
                            <DialogFooter className="p-6 pt-2 border-t">
                                <Button
                                    variant="outline"
                                    onClick={() => setIsAddSubjectOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleAddSubject}
                                    disabled={addForm.processing}
                                >
                                    Save to Curriculum
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        open={isCertifyOpen}
                        onOpenChange={setIsCertifyOpen}
                    >
                        <DialogContent className="sm:max-w-[500px] max-h-[90vh] flex flex-col p-0">
                            <DialogHeader className="p-6 pb-2">
                                <DialogTitle>Qualified Teachers</DialogTitle>
                                <DialogDescription>
                                    Managing qualified teachers for{' '}
                                    <span className="font-medium text-primary">
                                        {selectedSubject?.subject_name}
                                    </span>
                                    .
                                </DialogDescription>
                            </DialogHeader>
                            <div className="py-4 overflow-y-auto px-6 flex-1">
                                <FacultyCertificationList
                                    allTeachers={teachers}
                                    selectedDetails={certifyForm.data.teacher_details}
                                    searchQuery={searchQuery}
                                    onSearchChange={setSearchQuery}
                                    onToggle={(id) => toggleTeacher(id, 'certify')}
                                    filteredTeachers={filteredTeachers}
                                />
                            </div>
                            <DialogFooter className="p-6 pt-2 border-t">
                                <Button
                                    variant="outline"
                                    onClick={() => setIsCertifyOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleCertify}
                                    disabled={certifyForm.processing}
                                >
                                    Update List
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Modify Subject</DialogTitle>
                                <DialogDescription>
                                    Update the structural details for this
                                    academic subject.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label>Subject Code</Label>
                                    <Input
                                        value={editForm.data.subject_code}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'subject_code',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Descriptive Title</Label>
                                    <Input
                                        value={editForm.data.subject_name}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'subject_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Weekly Minutes</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        max={1200}
                                        value={editForm.data.required_weekly_minutes}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'required_weekly_minutes',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setIsEditOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleUpdateSubject}
                                    disabled={editForm.processing}
                                >
                                    Update Details
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </TooltipProvider>

            <ActionConfirmDialog
                open={subjectIdToDelete !== null}
                onOpenChange={(open) => !open && setSubjectIdToDelete(null)}
                title="Remove Subject"
                description="Are you sure you want to remove this subject? This will delete the subject from the curriculum and remove all associated teacher certifications."
                variant="destructive"
                confirmLabel="Remove Subject"
                onConfirm={submitDeleteSubject}
            />
        </AppLayout>
    </>
);
}
