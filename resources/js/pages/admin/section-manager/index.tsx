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
    academic_year_id: number;
    adviser_id: number | null;
    adviser: Teacher | null;
    students_count?: number;
}

interface GradeLevel {
    id: number;
    name: string;
    level_order: number;
    sections: Section[];
}

interface AcademicYear {
    id: number;
    name: string;
    status: string;
}

interface Props {
    gradeLevels: GradeLevel[];
    teachers: Teacher[];
    activeYear: AcademicYear | null;
}

// Stable search component
const AdviserSearchField = ({
    teachers,
    selectedAdviser,
    searchQuery,
    onSearchChange,
    onSelect,
    onRemove,
    filteredTeachers,
}: {
    teachers: Teacher[];
    selectedAdviser: Teacher | null;
    searchQuery: string;
    onSearchChange: (val: string) => void;
    onSelect: (teacher: Teacher) => void;
    onRemove: () => void;
    filteredTeachers: Teacher[];
}) => (
    <div className="grid gap-2">
        <Label className="text-xs text-muted-foreground">Class Adviser</Label>
        <div className="relative">
            <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
            <Input
                placeholder="Search faculty name..."
                className="pl-10"
                value={searchQuery}
                onChange={(e) => onSearchChange(e.target.value)}
            />
            {searchQuery && filteredTeachers.length > 0 && (
                <div className="absolute top-full right-0 left-0 z-50 mt-1 max-h-48 overflow-auto rounded-md border bg-popover p-1 shadow-md">
                    {filteredTeachers.map((teacher) => (
                        <button
                            key={teacher.id}
                            type="button"
                            className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                            onClick={() => onSelect(teacher)}
                        >
                            <Avatar className="size-6">
                                <AvatarFallback className="text-[8px] font-bold">
                                    {teacher.initial}
                                </AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{teacher.name}</span>
                            <Plus className="ml-auto size-3 opacity-50" />
                        </button>
                    ))}
                </div>
            )}
        </div>

        {selectedAdviser && (
            <div className="mt-2 flex animate-in items-center justify-between rounded-lg border bg-muted/30 p-3 fade-in slide-in-from-top-1">
                <div className="flex items-center gap-3">
                    <Avatar className="size-10 border-2 border-background shadow-sm">
                        <AvatarFallback className="bg-primary/10 text-xs font-bold text-primary">
                            {selectedAdviser.initial}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <p className="text-sm font-bold">
                            {selectedAdviser.name}
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
                    onClick={onRemove}
                >
                    <X className="size-4" />
                </Button>
            </div>
        )}

        {!selectedAdviser && (
            <div className="mt-2 flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/5 p-6 text-center">
                <User className="size-8 text-muted-foreground/20" />
                <p className="mt-2 text-xs text-muted-foreground">
                    No adviser selected. Search above to assign.
                </p>
            </div>
        )}
    </div>
);

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
        academic_year_id: activeYear?.id || 0,
        grade_level_id: parseInt(activeTab),
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
        const id = isEditOpen
            ? editForm.data.adviser_id
            : addForm.data.adviser_id;
        return teachers.find((t) => t.id === id) || null;
    }, [
        teachers,
        addForm.data.adviser_id,
        editForm.data.adviser_id,
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
        addForm.post(store(), {
            onSuccess: () => {
                setIsAddOpen(false);
                addForm.reset();
            },
        });
    };

    const handleUpdate = () => {
        if (!selectedSection) return;
        editForm.patch(update({ section: selectedSection.id }), {
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

    const removeAdviser = () => {
        if (isEditOpen) {
            editForm.setData('adviser_id', null);
        } else {
            addForm.setData('adviser_id', null);
        }
    };

    const SectionTable = ({ sections }: { sections: Section[] }) => (
        <div className="overflow-hidden rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="pl-6">Section Name</TableHead>
                        <TableHead>Class Adviser</TableHead>
                        <TableHead className="text-center">
                            Population
                        </TableHead>
                        <TableHead className="pr-6 text-right">
                            Actions
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {sections.map((section) => (
                        <TableRow key={section.id}>
                            <TableCell className="pl-6 font-medium text-primary">
                                {section.name}
                            </TableCell>
                            <TableCell>
                                {section.adviser ? (
                                    <div className="flex items-center gap-2">
                                        <Avatar className="size-8 ring-1 ring-primary/5">
                                            <AvatarFallback className="bg-muted text-xs font-semibold text-muted-foreground">
                                                {section.adviser.initial}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="text-sm font-medium">
                                            {section.adviser.name}
                                        </span>
                                    </div>
                                ) : (
                                    <span className="text-xs text-muted-foreground italic">
                                        No Adviser Assigned
                                    </span>
                                )}
                            </TableCell>
                            <TableCell className="text-center font-medium">
                                {section.students_count || 0}
                            </TableCell>
                            <TableCell className="pr-6 text-right">
                                <div className="flex justify-end gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-muted-foreground hover:text-primary"
                                                    onClick={() => {
                                                        setSelectedSection(
                                                            section,
                                                        );
                                                        editForm.setData({
                                                            name: section.name,
                                                            adviser_id:
                                                                section.adviser_id,
                                                        });
                                                        setIsEditOpen(true);
                                                    }}
                                                >
                                                    <Edit2 className="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Edit Section
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-muted-foreground hover:text-destructive"
                                                    onClick={() =>
                                                        handleDelete(section.id)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Remove Section
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                    {sections.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={4} className="h-24 text-center">
                                <p className="text-sm text-muted-foreground">
                                    No sections defined for this level
                                </p>
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );

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
                        <p className="max-w-sm text-sm text-muted-foreground">
                            An active School Year must be initialized in{' '}
                            <span className="font-semibold text-foreground">
                                Academic Controls
                            </span>{' '}
                            before managing sections.
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
            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2">
                        <Layers className="size-6 text-primary" />
                        <h1 className="text-2xl font-bold tracking-tight">
                            Section Management
                        </h1>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Organize class groups and assign faculty mentors for{' '}
                        <span className="font-semibold text-foreground">
                            {activeYear.name}
                        </span>
                        .
                    </p>
                </div>

                <Card className="flex flex-col">
                    <Tabs
                        value={activeTab}
                        onValueChange={(val) => {
                            setActiveTab(val);
                            addForm.setData('grade_level_id', parseInt(val));
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
                                    addForm.setData({
                                        academic_year_id: activeYear.id,
                                        grade_level_id: parseInt(activeTab),
                                        name: '',
                                        adviser_id: null,
                                    });
                                    setIsAddOpen(true);
                                }}
                            >
                                <Plus className="size-4" />
                                Add Section
                            </Button>
                        </CardHeader>

                        <CardContent className="p-6 pb-0">
                            {gradeLevels.map((grade) => (
                                <TabsContent
                                    key={grade.id}
                                    value={grade.id.toString()}
                                    className="m-0 outline-none"
                                >
                                    <SectionTable sections={grade.sections} />
                                </TabsContent>
                            ))}
                        </CardContent>
                    </Tabs>
                </Card>

                {/* Modals */}
                <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                    <DialogContent className="sm:max-w-[450px]">
                        <DialogHeader>
                            <DialogTitle>Create New Section</DialogTitle>
                            <DialogDescription>
                                Define a new class group for{' '}
                                <span className="font-medium text-primary">
                                    {currentGrade?.name}
                                </span>
                                .
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-6 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Section Name</Label>
                                <Input
                                    id="name"
                                    placeholder="e.g. Rizal"
                                    value={addForm.data.name}
                                    onChange={(e) =>
                                        addForm.setData('name', e.target.value)
                                    }
                                />
                            </div>

                            <AdviserSearchField
                                teachers={teachers}
                                selectedAdviser={currentAdviser}
                                searchQuery={searchQuery}
                                onSearchChange={setSearchQuery}
                                onSelect={selectAdviser}
                                onRemove={removeAdviser}
                                filteredTeachers={filteredTeachers}
                            />
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
                            <DialogTitle>Edit Section Details</DialogTitle>
                            <DialogDescription>
                                Update organization details for this class
                                group.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-6 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-name">Section Name</Label>
                                <Input
                                    id="edit-name"
                                    value={editForm.data.name}
                                    onChange={(e) =>
                                        editForm.setData('name', e.target.value)
                                    }
                                />
                            </div>

                            <AdviserSearchField
                                teachers={teachers}
                                selectedAdviser={currentAdviser}
                                searchQuery={searchQuery}
                                onSearchChange={setSearchQuery}
                                onSelect={selectAdviser}
                                onRemove={removeAdviser}
                                filteredTeachers={filteredTeachers}
                            />
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
                                Update Section
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
