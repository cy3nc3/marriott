import { useState, useMemo } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { 
    store, 
    update, 
    destroy 
} from '@/routes/admin/section_manager';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import { 
    Plus, 
    Layers, 
    Edit2,
    Trash2,
    Search,
    User,
    X,
    AlertCircle
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { 
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import { cn } from '@/lib/utils';

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
    students_count?: number; // Added for later when students are connected
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
    filteredTeachers 
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
        <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Class Adviser</Label>
        <div className="relative">
            <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
            <Input 
                placeholder="Search faculty name..." 
                className="pl-10 font-medium"
                value={searchQuery}
                onChange={(e) => onSearchChange(e.target.value)}
            />
            {searchQuery && filteredTeachers.length > 0 && (
                <div className="absolute top-full left-0 right-0 z-50 mt-1 max-h-48 overflow-auto rounded-md border bg-popover p-1 shadow-md">
                    {filteredTeachers.map(teacher => (
                        <button
                            key={teacher.id}
                            type="button"
                            className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                            onClick={() => onSelect(teacher)}
                        >
                            <Avatar className="size-6">
                                <AvatarFallback className="text-[8px] font-bold">{teacher.initial}</AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{teacher.name}</span>
                            <Plus className="ml-auto size-3 opacity-50" />
                        </button>
                    ))}
                </div>
            )}
        </div>
        
        {selectedAdviser && (
            <div className="mt-2 flex items-center justify-between rounded-lg border bg-muted/30 p-3 animate-in fade-in slide-in-from-top-1">
                <div className="flex items-center gap-3">
                    <Avatar className="size-10 border-2 border-background shadow-sm">
                        <AvatarFallback className="bg-primary/10 text-primary font-bold text-xs">
                            {selectedAdviser.initial}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <p className="text-sm font-bold">{selectedAdviser.name}</p>
                        <p className="text-[10px] font-medium text-muted-foreground uppercase tracking-widest">Selected Adviser</p>
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
            <div className="mt-2 flex flex-col items-center justify-center rounded-lg border border-dashed p-6 text-center bg-muted/5">
                <User className="size-8 text-muted-foreground/20" />
                <p className="mt-2 text-xs font-medium text-muted-foreground">No adviser selected. Search above to assign.</p>
            </div>
        )}
    </div>
);

export default function SectionManager({ gradeLevels, teachers, activeYear }: Props) {
    const [activeTab, setActiveTab] = useState(gradeLevels[0]?.id.toString() || '');
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [selectedSection, setSelectedSection] = useState<Section | null>(null);
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

    const currentGrade = useMemo(() => 
        gradeLevels.find(g => g.id.toString() === activeTab), 
    [gradeLevels, activeTab]);

    const currentAdviser = useMemo(() => {
        const id = isEditOpen ? editForm.data.adviser_id : addForm.data.adviser_id;
        return teachers.find(t => t.id === id) || null;
    }, [teachers, addForm.data.adviser_id, editForm.data.adviser_id, isEditOpen]);

    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return [];
        return teachers.filter(t => 
            t.name.toLowerCase().includes(searchQuery.toLowerCase()) && 
            t.id !== currentAdviser?.id
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
        <div className="rounded-md border overflow-hidden">
            <Table>
                <TableHeader className="bg-muted/50">
                    <TableRow>
                        <TableHead className="pl-6 font-black text-[10px] uppercase tracking-widest">Section Name</TableHead>
                        <TableHead className="font-black text-[10px] uppercase tracking-widest">Class Adviser</TableHead>
                        <TableHead className="text-center font-black text-[10px] uppercase tracking-widest">Population</TableHead>
                        <TableHead className="text-right pr-6 font-black text-[10px] uppercase tracking-widest">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {sections.map((section) => (
                        <TableRow key={section.id}>
                            <TableCell className="pl-6 font-bold text-primary">{section.name}</TableCell>
                            <TableCell>
                                {section.adviser ? (
                                    <div className="flex items-center gap-2">
                                        <Avatar className="size-8 ring-1 ring-primary/5">
                                            <AvatarFallback className="text-[10px] font-bold bg-muted text-muted-foreground">
                                                {section.adviser.initial}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="font-medium text-sm">{section.adviser.name}</span>
                                    </div>
                                ) : (
                                    <span className="text-xs font-medium text-muted-foreground uppercase italic tracking-wider">No Adviser Assigned</span>
                                )}
                            </TableCell>
                            <TableCell className="text-center font-medium">
                                {section.students_count || 0}
                            </TableCell>
                            <TableCell className="text-right pr-6">
                                <div className="flex justify-end gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button 
                                                    variant="ghost" 
                                                    size="icon" 
                                                    className="size-8 text-muted-foreground hover:text-primary hover:bg-primary/5"
                                                    onClick={() => {
                                                        setSelectedSection(section);
                                                        editForm.setData({
                                                            name: section.name,
                                                            adviser_id: section.adviser_id,
                                                        });
                                                        setIsEditOpen(true);
                                                    }}
                                                >
                                                    <Edit2 className="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent className="font-bold text-xs">Edit Section</TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button 
                                                    variant="ghost" 
                                                    size="icon" 
                                                    className="size-8 text-muted-foreground hover:text-destructive hover:bg-destructive/5"
                                                    onClick={() => handleDelete(section.id)}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent className="font-bold text-xs">Remove Section</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                    {sections.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={4} className="h-24 text-center">
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-widest">No sections defined for this level</p>
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
                    <div className="rounded-full bg-amber-50 p-4 dark:bg-amber-950/20">
                        <AlertCircle className="size-10 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-xl font-black uppercase tracking-tight">System Notice</h2>
                        <p className="max-w-sm text-sm text-muted-foreground font-medium">
                            An active School Year must be initialized in <span className="text-foreground font-bold">Academic Controls</span> before managing sections.
                        </p>
                    </div>
                    <Button variant="outline" className="font-bold uppercase text-xs" onClick={() => router.get(route('admin.academic_controls'))}>
                        Go to Academic Controls
                    </Button>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Section Manager" />
            <div className="flex flex-col gap-6 p-4 lg:p-6">
                
                <div className="flex flex-col gap-1 px-1">
                    <div className="flex items-center gap-2">
                        <Layers className="size-6 text-primary" />
                        <h1 className="text-2xl font-bold tracking-tight uppercase">Section Management</h1>
                    </div>
                    <p className="text-sm text-muted-foreground font-medium">Organize class groups and assign faculty mentors for <span className="text-foreground font-bold uppercase tracking-wider">{activeYear.name}</span>.</p>
                </div>

                <Card className="overflow-hidden border-primary/10">
                    <Tabs value={activeTab} onValueChange={(val) => {
                        setActiveTab(val);
                        addForm.setData('grade_level_id', parseInt(val));
                    }} className="w-full">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/5 py-3 px-6">
                            <TabsList className="h-9">
                                {gradeLevels.map(grade => (
                                    <TabsTrigger key={grade.id} value={grade.id.toString()} className="text-xs font-bold uppercase tracking-wider">
                                        {grade.name}
                                    </TabsTrigger>
                                ))}
                            </TabsList>
                            <Button 
                                size="sm" 
                                className="gap-2 font-bold uppercase tracking-tight h-9" 
                                onClick={() => {
                                    addForm.reset();
                                    addForm.setData({
                                        academic_year_id: activeYear.id,
                                        grade_level_id: parseInt(activeTab),
                                        name: '',
                                        adviser_id: null
                                    });
                                    setIsAddOpen(true);
                                }}
                            >
                                <Plus className="size-4" />
                                Add Section for {currentGrade?.name}
                            </Button>
                        </CardHeader>
                        
                        <CardContent className="p-6">
                            {gradeLevels.map(grade => (
                                <TabsContent key={grade.id} value={grade.id.toString()} className="m-0 outline-none">
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
                            <DialogTitle className="text-xl font-bold uppercase tracking-tight">Create New Section</DialogTitle>
                            <DialogDescription className="font-medium">
                                Define a new class group for <span className="text-primary font-bold">{currentGrade?.name}</span>.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-6 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name" className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Section Name</Label>
                                <Input 
                                    id="name" 
                                    placeholder="e.g. Rizal" 
                                    className="font-bold h-10"
                                    value={addForm.data.name}
                                    onChange={e => addForm.setData('name', e.target.value)}
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
                            <Button variant="outline" className="font-bold" onClick={() => setIsAddOpen(false)}>Cancel</Button>
                            <Button className="font-black uppercase tracking-tight" onClick={handleAdd} disabled={addForm.processing}>Create Section</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                    <DialogContent className="sm:max-w-[450px]">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-bold uppercase tracking-tight">Edit Section Details</DialogTitle>
                            <DialogDescription className="font-medium">Update organization details for this class group.</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-6 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-name" className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Section Name</Label>
                                <Input 
                                    id="edit-name" 
                                    className="font-bold h-10"
                                    value={editForm.data.name}
                                    onChange={e => editForm.setData('name', e.target.value)}
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
                            <Button variant="outline" className="font-bold" onClick={() => setIsEditOpen(false)}>Cancel</Button>
                            <Button className="font-black uppercase tracking-tight" onClick={handleUpdate} disabled={editForm.processing}>Update Section</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

            </div>
        </AppLayout>
    );
}
