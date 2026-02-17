import { Head, useForm, router } from '@inertiajs/react';
import {
    Plus,
    Layers,
    Edit2,
    Trash2,
    Search,
    User,
    X,
    AlertCircle,
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
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
import { store, update, destroy } from '@/routes/admin/section_manager';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Academic Controls',
        href: '/admin/academic-controls',
    },
    {
        title: 'Section Manager',
        href: '/admin/section-manager',
    },
];

interface Teacher {
    id: number;
    name: string;
    initial: string;
}

interface Section {
    id: number;
    name: string;
    grade_level_id: number;
    adviser_id: number | null;
    adviser: Teacher | null;
    students_count: number;
}

interface GradeLevel {
    id: number;
    name: string;
    sections: Section[];
}

interface Props {
    gradeLevels: GradeLevel[];
    teachers: Teacher[];
    activeYear: { id: number; name: string } | null;
}

export default function SectionManager({
    gradeLevels,
    teachers,
    activeYear,
}: Props) {
    const [activeTab, setActiveTab] = useState(
        gradeLevels[0]?.id.toString() || '',
    );
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [selectedSection, setSelectedSection] = useState<Section | null>(
        null,
    );
    const [searchQuery, setSearchQuery] = useState('');

    const addForm = useForm({
        grade_level_id: activeTab,
        name: '',
        adviser_id: null as number | null,
    });

    const editForm = useForm({
        name: '',
        adviser_id: null as number | null,
    });

    const currentGrade = useMemo(
        () => gradeLevels.find((g) => g.id.toString() === activeTab),
        [gradeLevels, activeTab],
    );

    const currentAdviser = useMemo(() => {
        const adviserId = isEditOpen
            ? editForm.data.adviser_id
            : addForm.data.adviser_id;
        return teachers.find((t) => t.id === adviserId);
    }, [
        teachers,
        editForm.data.adviser_id,
        addForm.data.adviser_id,
        isEditOpen,
    ]);

    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return [];
        return teachers.filter(
            (t) =>
                t.name.toLowerCase().includes(searchQuery.toLowerCase()) &&
                t.id !== currentAdviser?.id,
        );
    }, [teachers, searchQuery, currentAdviser]);

    const handleAdd = () => {
        addForm.submit(store(), {
            onSuccess: () => {
                setIsAddOpen(false);
                addForm.reset();
            },
        });
    };

    const handleUpdate = () => {
        if (!selectedSection) return;
        editForm.submit(update({ section: selectedSection.id }), {
            onSuccess: () => {
                setIsEditOpen(false);
                editForm.reset();
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to remove this section?')) {
            router.delete(destroy({ section: id }).url);
        }
    };

    const selectAdviser = (teacher: Teacher) => {
        if (isEditOpen) {
            editForm.setData('adviser_id', teacher.id);
        } else {
            addForm.setData('adviser_id', teacher.id);
        }
        setSearchQuery('');
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
                            managing sections.
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Section Manager" />
            <TooltipProvider>
                <div className="flex flex-col gap-6">
                    <Card className="flex flex-col pt-0">
                        <Tabs
                            value={activeTab}
                            onValueChange={(val) => {
                                setActiveTab(val);
                                addForm.setData('grade_level_id', val);
                            }}
                            className="flex w-full flex-1 flex-col gap-0"
                        >
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b">
                                <TabsList className="h-9">
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
                                        setIsAddOpen(true);
                                    }}
                                >
                                    <Plus className="size-4" />
                                    New Section
                                </Button>
                            </CardHeader>

                            <CardContent className="p-6 pb-0">
                                {gradeLevels.map((grade) => (
                                    <TabsContent
                                        key={grade.id}
                                        value={grade.id.toString()}
                                        className="m-0 outline-none"
                                    >
                                        <div className="overflow-hidden rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead className="pl-6">
                                                            Section Name
                                                        </TableHead>
                                                        <TableHead>
                                                            Class Adviser
                                                        </TableHead>
                                                        <TableHead className="text-center">
                                                            Students
                                                        </TableHead>
                                                        <TableHead className="pr-6 text-right">
                                                            Actions
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {grade.sections.map(
                                                        (sec) => (
                                                            <TableRow
                                                                key={sec.id}
                                                            >
                                                                <TableCell className="pl-6 font-semibold">
                                                                    {sec.name}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {sec.adviser ? (
                                                                        <div className="flex items-center gap-2">
                                                                            <Avatar className="size-7 border">
                                                                                <AvatarFallback className="text-[10px] font-bold">
                                                                                    {
                                                                                        sec
                                                                                            .adviser
                                                                                            .initial
                                                                                    }
                                                                                </AvatarFallback>
                                                                            </Avatar>
                                                                            <span className="text-sm">
                                                                                {
                                                                                    sec
                                                                                        .adviser
                                                                                        .name
                                                                                }
                                                                            </span>
                                                                        </div>
                                                                    ) : (
                                                                        <span className="text-xs text-muted-foreground italic">
                                                                            No
                                                                            Adviser
                                                                        </span>
                                                                    )}
                                                                </TableCell>
                                                                <TableCell className="text-center">
                                                                    <Badge variant="secondary">
                                                                        {
                                                                            sec.students_count
                                                                        }
                                                                    </Badge>
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
                                                                                    className="size-8 text-muted-foreground hover:text-primary"
                                                                                    onClick={() => {
                                                                                        setSelectedSection(
                                                                                            sec,
                                                                                        );
                                                                                        editForm.setData(
                                                                                            {
                                                                                                name: sec.name,
                                                                                                adviser_id:
                                                                                                    sec.adviser_id,
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
                                                                                Section
                                                                            </TooltipContent>
                                                                        </Tooltip>

                                                                        <Tooltip>
                                                                            <TooltipTrigger
                                                                                asChild
                                                                            >
                                                                                <Button
                                                                                    variant="ghost"
                                                                                    size="icon"
                                                                                    className="size-8 text-muted-foreground hover:text-destructive"
                                                                                    onClick={() =>
                                                                                        handleDelete(
                                                                                            sec.id,
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    <Trash2 className="size-4" />
                                                                                </Button>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                Remove
                                                                                Section
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    </div>
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                    {grade.sections.length ===
                                                        0 && (
                                                        <TableRow>
                                                            <TableCell
                                                                colSpan={4}
                                                                className="h-24 text-center"
                                                            >
                                                                <p className="text-sm text-muted-foreground">
                                                                    No sections
                                                                    defined for
                                                                    this level
                                                                </p>
                                                            </TableCell>
                                                        </TableRow>
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </TabsContent>
                                ))}
                            </CardContent>
                        </Tabs>
                    </Card>

                    {/* Modals */}
                    <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                        <DialogContent className="sm:max-w-[450px]">
                            <DialogHeader>
                                <DialogTitle>New Section</DialogTitle>
                                <DialogDescription>
                                    Create a new class organization for{' '}
                                    <span className="font-medium text-primary">
                                        {currentGrade?.name}
                                    </span>
                                    .
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-6 py-4">
                                <div className="grid gap-2">
                                    <Label className="text-xs text-muted-foreground">
                                        Section Name
                                    </Label>
                                    <Input
                                        placeholder="e.g. Diamond, Apollo, etc."
                                        value={addForm.data.name}
                                        onChange={(e) =>
                                            addForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <div className="grid gap-4">
                                    <Label className="text-xs text-muted-foreground">
                                        Class Adviser
                                    </Label>

                                    {currentAdviser ? (
                                        <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="size-10 border-2 border-background shadow-sm">
                                                    <AvatarFallback className="text-xs font-semibold">
                                                        {currentAdviser.initial}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {currentAdviser.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Selected Adviser
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-muted-foreground hover:text-destructive"
                                                onClick={() =>
                                                    addForm.setData(
                                                        'adviser_id',
                                                        null,
                                                    )
                                                }
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            <div className="relative">
                                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                                <Input
                                                    placeholder="Search faculty..."
                                                    className="pl-10"
                                                    value={searchQuery}
                                                    onChange={(e) =>
                                                        setSearchQuery(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                {searchQuery &&
                                                    filteredTeachers.length >
                                                        0 && (
                                                        <div className="absolute top-full right-0 left-0 z-50 mt-1 max-h-48 overflow-auto rounded-md border bg-popover p-1 shadow-md">
                                                            {filteredTeachers.map(
                                                                (teacher) => (
                                                                    <button
                                                                        key={
                                                                            teacher.id
                                                                        }
                                                                        type="button"
                                                                        className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                                                                        onClick={() =>
                                                                            selectAdviser(
                                                                                teacher,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Avatar className="size-6">
                                                                            <AvatarFallback className="text-[8px] font-bold">
                                                                                {
                                                                                    teacher.initial
                                                                                }
                                                                            </AvatarFallback>
                                                                        </Avatar>
                                                                        <span className="font-medium">
                                                                            {
                                                                                teacher.name
                                                                            }
                                                                        </span>
                                                                        <Plus className="ml-auto size-3 opacity-50" />
                                                                    </button>
                                                                ),
                                                            )}
                                                        </div>
                                                    )}
                                            </div>
                                            <p className="text-center text-xs text-muted-foreground italic">
                                                Search and select a teacher to
                                                assign as adviser.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setIsAddOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleAdd}
                                    disabled={addForm.processing}
                                >
                                    Create Section
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                        <DialogContent className="sm:max-w-[450px]">
                            <DialogHeader>
                                <DialogTitle>Edit Section</DialogTitle>
                                <DialogDescription>
                                    Update the details for this class
                                    organization.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-6 py-4">
                                <div className="grid gap-2">
                                    <Label className="text-xs text-muted-foreground">
                                        Section Name
                                    </Label>
                                    <Input
                                        value={editForm.data.name}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <div className="grid gap-4">
                                    <Label className="text-xs text-muted-foreground">
                                        Class Adviser
                                    </Label>

                                    {currentAdviser ? (
                                        <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="size-10 border-2 border-background shadow-sm">
                                                    <AvatarFallback className="text-xs font-semibold">
                                                        {currentAdviser.initial}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {currentAdviser.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Selected Adviser
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-muted-foreground hover:text-destructive"
                                                onClick={() =>
                                                    editForm.setData(
                                                        'adviser_id',
                                                        null,
                                                    )
                                                }
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            <div className="relative">
                                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                                <Input
                                                    placeholder="Search faculty..."
                                                    className="pl-10"
                                                    value={searchQuery}
                                                    onChange={(e) =>
                                                        setSearchQuery(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                {searchQuery &&
                                                    filteredTeachers.length >
                                                        0 && (
                                                        <div className="absolute top-full right-0 left-0 z-50 mt-1 max-h-48 overflow-auto rounded-md border bg-popover p-1 shadow-md">
                                                            {filteredTeachers.map(
                                                                (teacher) => (
                                                                    <button
                                                                        key={
                                                                            teacher.id
                                                                        }
                                                                        type="button"
                                                                        className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                                                                        onClick={() =>
                                                                            selectAdviser(
                                                                                teacher,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Avatar className="size-6">
                                                                            <AvatarFallback className="text-[8px] font-bold">
                                                                                {
                                                                                    teacher.initial
                                                                                }
                                                                            </AvatarFallback>
                                                                        </Avatar>
                                                                        <span className="font-medium">
                                                                            {
                                                                                teacher.name
                                                                            }
                                                                        </span>
                                                                        <Plus className="ml-auto size-3 opacity-50" />
                                                                    </button>
                                                                ),
                                                            )}
                                                        </div>
                                                    )}
                                            </div>
                                        </div>
                                    )}
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
                                    onClick={handleUpdate}
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
