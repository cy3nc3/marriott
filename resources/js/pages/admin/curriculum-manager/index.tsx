import { Head, useForm, router } from '@inertiajs/react';
import { Plus, UserPlus, Edit2, Search, X, Users, Trash2 } from 'lucide-react';
import { useState, useMemo } from 'react';
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    store,
    update,
    destroy,
    certify,
} from '@/routes/admin/curriculum_manager';

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
}

interface Subject {
    id: number;
    grade_level_id: number;
    subject_code: string;
    subject_name: string;
    teachers: Teacher[];
}

interface GradeLevel {
    id: number;
    name: string;
    level_order: number;
    subjects: Subject[];
}

interface FacultyListProps {
    teachers: Teacher[];
    selectedIds: number[];
    searchQuery: string;
    onSearchChange: (val: string) => void;
    onToggle: (id: number) => void;
    filteredTeachers: Teacher[];
}

// Stable component to prevent focus loss
const FacultyCertificationList = ({
    teachers,
    selectedIds,
    searchQuery,
    onSearchChange,
    onToggle,
    filteredTeachers,
}: FacultyListProps) => {
    const selectedList = teachers.filter((t) => selectedIds.includes(t.id));

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
                                                {teacher.initial}
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
                <p className="text-xs text-muted-foreground italic">
                    Tip: You can search and select multiple teachers.
                </p>
            </div>

            <div className="space-y-3">
                <Label>Qualified Teachers ({selectedList.length}):</Label>

                {selectedList.length > 0 ? (
                    <div className="grid gap-2">
                        {selectedList.map((teacher) => (
                            <div
                                key={teacher.id}
                                className="flex items-center justify-between rounded-lg border bg-muted/30 p-3"
                            >
                                <div className="flex items-center gap-3">
                                    <Avatar className="size-8">
                                        <AvatarFallback>
                                            {teacher.initial}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p className="text-sm font-medium">
                                            {teacher.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Certified Faculty
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => onToggle(teacher.id)}
                                >
                                    <X className="size-4" />
                                </Button>
                            </div>
                        ))}
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

    const addForm = useForm({
        grade_level_id: activeTab,
        subject_code: '',
        subject_name: '',
        teacher_ids: [] as number[],
    });

    const editForm = useForm({
        subject_code: '',
        subject_name: '',
    });

    const certifyForm = useForm({
        teacher_ids: [] as number[],
    });

    const currentGrade = useMemo(
        () => gradeLevels.find((g) => g.id.toString() === activeTab),
        [gradeLevels, activeTab],
    );

    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return [];
        const currentSelectedIds = isCertifyOpen
            ? certifyForm.data.teacher_ids
            : addForm.data.teacher_ids;
        return teachers.filter(
            (t) =>
                t.name.toLowerCase().includes(searchQuery.toLowerCase()) &&
                !currentSelectedIds.includes(t.id),
        );
    }, [
        teachers,
        searchQuery,
        addForm.data.teacher_ids,
        certifyForm.data.teacher_ids,
        isCertifyOpen,
    ]);

    const handleAddSubject = () => {
        addForm.submit(store(), {
            onSuccess: () => {
                setIsAddSubjectOpen(false);
                addForm.reset();
            },
        });
    };

    const handleUpdateSubject = () => {
        if (!selectedSubject) return;
        editForm.submit(update({ subject: selectedSubject.id }), {
            onSuccess: () => {
                setIsEditOpen(false);
                editForm.reset();
            },
        });
    };

    const handleCertify = () => {
        if (!selectedSubject) return;
        certifyForm.submit(certify({ subject: selectedSubject.id }), {
            onSuccess: () => {
                setIsCertifyOpen(false);
                certifyForm.reset();
            },
        });
    };

    const handleDeleteSubject = (id: number) => {
        if (confirm('Are you sure you want to remove this subject?')) {
            router.delete(destroy({ subject: id }).url);
        }
    };

    const toggleTeacher = (teacherId: number, formType: 'add' | 'certify') => {
        const currentIds =
            formType === 'add'
                ? addForm.data.teacher_ids
                : certifyForm.data.teacher_ids;

        const newIds = currentIds.includes(teacherId)
            ? currentIds.filter((id) => id !== teacherId)
            : [...currentIds, teacherId];

        if (formType === 'add') {
            addForm.setData('teacher_ids', newIds);
        } else {
            certifyForm.setData('teacher_ids', newIds);
        }

        setSearchQuery('');
    };

    return (
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
                                    <Button
                                        size="sm"
                                        className="gap-2"
                                        onClick={() => {
                                            addForm.reset();
                                            addForm.setData(
                                                'grade_level_id',
                                                activeTab,
                                            );
                                            setIsAddSubjectOpen(true);
                                        }}
                                    >
                                        <Plus className="size-4" />
                                        Add Subject
                                    </Button>
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
                                                            <div className="flex -space-x-2">
                                                                {sub.teachers.map(
                                                                    (t) => (
                                                                        <Tooltip
                                                                            key={
                                                                                t.id
                                                                            }
                                                                        >
                                                                            <TooltipTrigger
                                                                                asChild
                                                                            >
                                                                                <Avatar className="size-8">
                                                                                    <AvatarFallback>
                                                                                        {
                                                                                            t.initial
                                                                                        }
                                                                                    </AvatarFallback>
                                                                                </Avatar>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                {
                                                                                    t.name
                                                                                }
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    ),
                                                                )}
                                                                {sub.teachers
                                                                    .length ===
                                                                    0 && (
                                                                    <Badge variant="outline">
                                                                        No
                                                                        qualified
                                                                        teachers
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
                                                                                    'teacher_ids',
                                                                                    sub.teachers.map(
                                                                                        (
                                                                                            t,
                                                                                        ) =>
                                                                                            t.id,
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
                        <DialogContent className="sm:max-w-[500px]">
                            <DialogHeader>
                                <DialogTitle>New Subject Entry</DialogTitle>
                                <DialogDescription>
                                    Define a core academic subject for{' '}
                                    <span className="font-medium text-primary">
                                        {currentGrade?.name}
                                    </span>
                                    .
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-6 py-4">
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
                                </div>

                                <FacultyCertificationList
                                    teachers={teachers}
                                    selectedIds={addForm.data.teacher_ids}
                                    searchQuery={searchQuery}
                                    onSearchChange={setSearchQuery}
                                    onToggle={(id) => toggleTeacher(id, 'add')}
                                    filteredTeachers={filteredTeachers}
                                />
                            </div>
                            <DialogFooter>
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
                        <DialogContent className="sm:max-w-[500px]">
                            <DialogHeader>
                                <DialogTitle>Qualified Teachers</DialogTitle>
                                <DialogDescription>
                                    Managing qualified teachers for{' '}
                                    <span className="font-medium text-primary">
                                        {selectedSubject?.subject_name}
                                    </span>
                                    .
                                </DialogDescription>
                            </DialogHeader>
                            <div className="py-4">
                                <FacultyCertificationList
                                    teachers={teachers}
                                    selectedIds={certifyForm.data.teacher_ids}
                                    searchQuery={searchQuery}
                                    onSearchChange={setSearchQuery}
                                    onToggle={(id) =>
                                        toggleTeacher(id, 'certify')
                                    }
                                    filteredTeachers={filteredTeachers}
                                />
                            </div>
                            <DialogFooter>
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
        </AppLayout>
    );
}
