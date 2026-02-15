import { useState, useMemo } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { 
    store, 
    update, 
    destroy, 
    certify 
} from '@/routes/admin/curriculum_manager';
import {
    Card,
    CardContent,
    CardHeader,
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { 
    Plus, 
    BookOpen, 
    UserPlus, 
    Edit2,
    Search,
    X,
    Users,
    Trash2
} from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { 
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
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
    filteredTeachers 
}: FacultyListProps) => {
    const selectedList = teachers.filter(t => selectedIds.includes(t.id));

    return (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Search Faculty</Label>
                <div className="relative">
                    <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                    <Input 
                        placeholder="Search and select qualified teachers..." 
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
                                    onClick={() => onToggle(teacher.id)}
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
                <p className="text-[10px] text-muted-foreground font-medium italic">Tip: You can search and select multiple teachers.</p>
            </div>

            <div className="space-y-3">
                <Label className="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                    Qualified Teachers ({selectedList.length}):
                </Label>
                
                {selectedList.length > 0 ? (
                    <div className="grid gap-2">
                        {selectedList.map((teacher) => (
                            <div key={teacher.id} className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                <div className="flex items-center gap-3">
                                    <Avatar className="size-10 border-2 border-background shadow-sm">
                                        <AvatarFallback className="bg-primary/10 text-primary font-bold text-xs">
                                            {teacher.initial}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p className="text-sm font-bold">{teacher.name}</p>
                                        <p className="text-[10px] font-medium text-muted-foreground uppercase tracking-widest">Certified Faculty</p>
                                    </div>
                                </div>
                                <Button 
                                    variant="ghost" 
                                    size="icon" 
                                    className="size-8 text-muted-foreground hover:text-destructive"
                                    onClick={() => onToggle(teacher.id)}
                                >
                                    <X className="size-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center bg-muted/10">
                        <Users className="size-8 text-muted-foreground/20" />
                        <p className="mt-2 text-xs font-medium text-muted-foreground uppercase tracking-widest">No Qualified Teachers Selected</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default function CurriculumManager({ gradeLevels, teachers }: { gradeLevels: GradeLevel[], teachers: Teacher[] }) {
    const [activeTab, setActiveTab] = useState(gradeLevels[0]?.id.toString() || '');
    const [isAddSubjectOpen, setIsAddSubjectOpen] = useState(false);
    const [isCertifyOpen, setIsCertifyOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [selectedSubject, setSelectedSubject] = useState<Subject | null>(null);
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

    const currentGrade = useMemo(() => 
        gradeLevels.find(g => g.id.toString() === activeTab), 
    [gradeLevels, activeTab]);

    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return [];
        const currentSelectedIds = isCertifyOpen ? certifyForm.data.teacher_ids : addForm.data.teacher_ids;
        return teachers.filter(t => 
            t.name.toLowerCase().includes(searchQuery.toLowerCase()) && 
            !currentSelectedIds.includes(t.id)
        );
    }, [teachers, searchQuery, addForm.data.teacher_ids, certifyForm.data.teacher_ids, isCertifyOpen]);

    const handleAddSubject = () => {
        addForm.post(store(), {
            onSuccess: () => {
                setIsAddSubjectOpen(false);
                addForm.reset();
            },
        });
    };

    const handleUpdateSubject = () => {
        if (!selectedSubject) return;
        editForm.patch(update({ subject: selectedSubject.id }), {
            onSuccess: () => {
                setIsEditOpen(false);
                editForm.reset();
            },
        });
    };

    const handleCertify = () => {
        if (!selectedSubject) return;
        certifyForm.post(certify({ subject: selectedSubject.id }), {
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
        const form = formType === 'add' ? addForm : certifyForm;
        const currentIds = form.data.teacher_ids;
        if (currentIds.includes(teacherId)) {
            form.setData('teacher_ids', currentIds.filter(id => id !== teacherId));
        } else {
            form.setData('teacher_ids', [...currentIds, teacherId]);
        }
        setSearchQuery('');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Curriculum Manager" />
            <TooltipProvider>
                <div className="flex flex-col gap-6 p-4 lg:p-6">
                    
                    <div className="flex flex-col gap-1 px-1">
                        <div className="flex items-center gap-2">
                            <BookOpen className="size-6 text-primary" />
                            <h1 className="text-2xl font-bold tracking-tight uppercase">Curriculum Management</h1>
                        </div>
                        <p className="text-sm text-muted-foreground font-medium">Define subjects and manage qualified faculty per grade level.</p>
                    </div>

                    <Card className="overflow-hidden border-primary/10">
                        <Tabs value={activeTab} onValueChange={(val) => {
                            setActiveTab(val);
                            addForm.setData('grade_level_id', val);
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
                                        addForm.setData('grade_level_id', activeTab);
                                        setIsAddSubjectOpen(true);
                                    }}
                                >
                                    <Plus className="size-4" />
                                    Add Subject for {currentGrade?.name}
                                </Button>
                            </CardHeader>
                            
                            <CardContent className="p-6">
                                {gradeLevels.map(grade => (
                                    <TabsContent key={grade.id} value={grade.id.toString()} className="m-0 outline-none">
                                        <div className="rounded-md border overflow-hidden">
                                            <Table>
                                                <TableHeader className="bg-muted/50">
                                                    <TableRow>
                                                        <TableHead className="w-[120px] pl-6 font-black text-[10px] uppercase tracking-widest">Code</TableHead>
                                                        <TableHead className="font-black text-[10px] uppercase tracking-widest">Descriptive Title</TableHead>
                                                        <TableHead className="font-black text-[10px] uppercase tracking-widest">Qualified Teachers</TableHead>
                                                        <TableHead className="text-right pr-6 font-black text-[10px] uppercase tracking-widest">Actions</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {grade.subjects.map((sub) => (
                                                        <TableRow key={sub.id}>
                                                            <TableCell className="pl-6">
                                                                <Badge variant="secondary" className="font-mono text-[11px] font-black uppercase tracking-wider">
                                                                    {sub.subject_code}
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell className="font-bold text-sm tracking-tight">{sub.subject_name}</TableCell>
                                                            <TableCell>
                                                                <div className="flex -space-x-2">
                                                                    {sub.teachers.map((t) => (
                                                                        <Tooltip key={t.id}>
                                                                            <TooltipTrigger asChild>
                                                                                <Avatar className="border-2 border-background size-8 ring-1 ring-primary/5">
                                                                                    <AvatarFallback className="text-[10px] font-bold bg-muted text-muted-foreground">
                                                                                        {t.initial}
                                                                                    </AvatarFallback>
                                                                                </Avatar>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent className="font-bold text-xs">{t.name}</TooltipContent>
                                                                        </Tooltip>
                                                                    ))}
                                                                    {sub.teachers.length === 0 && (
                                                                        <span className="text-[10px] font-medium text-muted-foreground uppercase italic">No qualified teachers</span>
                                                                    )}
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="text-right pr-6">
                                                                <div className="flex justify-end gap-2">
                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button 
                                                                                variant="ghost" 
                                                                                size="icon" 
                                                                                className="size-8 text-muted-foreground hover:text-primary hover:bg-primary/5"
                                                                                onClick={() => {
                                                                                    setSelectedSubject(sub);
                                                                                    certifyForm.setData('teacher_ids', sub.teachers.map(t => t.id));
                                                                                    setIsCertifyOpen(true);
                                                                                }}
                                                                            >
                                                                                <UserPlus className="size-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>Manage Qualified Teachers</TooltipContent>
                                                                    </Tooltip>

                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button 
                                                                                variant="ghost" 
                                                                                size="icon" 
                                                                                className="size-8 text-muted-foreground hover:text-primary hover:bg-primary/5"
                                                                                onClick={() => {
                                                                                    setSelectedSubject(sub);
                                                                                    editForm.setData({
                                                                                        subject_code: sub.subject_code,
                                                                                        subject_name: sub.subject_name,
                                                                                    });
                                                                                    setIsEditOpen(true);
                                                                                }}
                                                                            >
                                                                                <Edit2 className="size-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>Edit Details</TooltipContent>
                                                                    </Tooltip>

                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button 
                                                                                variant="ghost" 
                                                                                size="icon" 
                                                                                className="size-8 text-muted-foreground hover:text-destructive hover:bg-destructive/5"
                                                                                onClick={() => handleDeleteSubject(sub.id)}
                                                                            >
                                                                                <Trash2 className="size-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>Delete Subject</TooltipContent>
                                                                    </Tooltip>
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                    {grade.subjects.length === 0 && (
                                                        <TableRow>
                                                            <TableCell colSpan={4} className="h-24 text-center">
                                                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-widest">No subjects defined for this level</p>
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
                    <Dialog open={isAddSubjectOpen} onOpenChange={setIsAddSubjectOpen}>
                        <DialogContent className="sm:max-w-[500px]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-bold uppercase tracking-tight">New Subject Entry</DialogTitle>
                                <DialogDescription className="font-medium">
                                    Define a core academic subject for <span className="text-primary font-bold">{currentGrade?.name}</span>.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-6 py-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Subject Code</Label>
                                        <Input 
                                            placeholder="MATH7" 
                                            className="font-black uppercase tracking-wider" 
                                            value={addForm.data.subject_code}
                                            onChange={e => addForm.setData('subject_code', e.target.value)}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Descriptive Title</Label>
                                        <Input 
                                            placeholder="Mathematics 7" 
                                            className="font-bold" 
                                            value={addForm.data.subject_name}
                                            onChange={e => addForm.setData('subject_name', e.target.value)}
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
                                <Button variant="outline" className="font-bold" onClick={() => setIsAddSubjectOpen(false)}>Cancel</Button>
                                <Button className="font-black uppercase tracking-tight" onClick={handleAddSubject} disabled={addForm.processing}>Save to Curriculum</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={isCertifyOpen} onOpenChange={setIsCertifyOpen}>
                        <DialogContent className="sm:max-w-[500px]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-bold uppercase tracking-tight">Qualified Teachers</DialogTitle>
                                <DialogDescription className="font-medium">
                                    Managing qualified teachers for <span className="text-primary font-bold">{selectedSubject?.subject_name}</span>.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="py-4">
                                <FacultyCertificationList 
                                    teachers={teachers}
                                    selectedIds={certifyForm.data.teacher_ids}
                                    searchQuery={searchQuery}
                                    onSearchChange={setSearchQuery}
                                    onToggle={(id) => toggleTeacher(id, 'certify')}
                                    filteredTeachers={filteredTeachers}
                                />
                            </div>
                            <DialogFooter>
                                <Button variant="outline" className="font-bold" onClick={() => setIsCertifyOpen(false)}>Cancel</Button>
                                <Button className="font-black uppercase tracking-tight" onClick={handleCertify} disabled={certifyForm.processing}>Update List</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle className="text-xl font-bold uppercase tracking-tight">Modify Subject</DialogTitle>
                                <DialogDescription className="font-medium">Update the structural details for this academic subject.</DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Subject Code</Label>
                                    <Input 
                                        className="font-black uppercase tracking-wider" 
                                        value={editForm.data.subject_code}
                                        onChange={e => editForm.setData('subject_code', e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Descriptive Title</Label>
                                    <Input 
                                        className="font-bold" 
                                        value={editForm.data.subject_name}
                                        onChange={e => editForm.setData('subject_name', e.target.value)}
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" className="font-bold" onClick={() => setIsEditOpen(false)}>Cancel</Button>
                                <Button className="font-black uppercase tracking-tight" onClick={handleUpdateSubject} disabled={editForm.processing}>Update Details</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                </div>
            </TooltipProvider>
        </AppLayout>
    );
}
