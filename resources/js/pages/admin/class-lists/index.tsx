import { useState, useMemo, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardAction,
    CardContent,
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
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, Printer, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Class Lists',
        href: '/admin/class-lists',
    },
];

interface Student {
    id: number;
    lrn: string;
    first_name: string;
    last_name: string;
    gender: string;
}

interface Enrollment {
    id: number;
    status: string;
    student: Student;
}

interface Section {
    id: number;
    name: string;
    enrollments: Enrollment[];
    enrollments_count: number;
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
    activeYear: AcademicYear | null;
}

export default function ClassLists({ gradeLevels, activeYear }: Props) {
    const [selectedGradeId, setSelectedGradeId] = useState(gradeLevels[0]?.id.toString() || '');
    const [selectedSectionId, setSelectedSectionId] = useState('');

    useEffect(() => {
        const grade = gradeLevels.find(g => g.id.toString() === selectedGradeId);
        if (grade && grade.sections.length > 0) {
            setSelectedSectionId(grade.sections[0].id.toString());
        } else {
            setSelectedSectionId('');
        }
    }, [selectedGradeId, gradeLevels]);

    const currentSection = useMemo(() => {
        const grade = gradeLevels.find(g => g.id.toString() === selectedGradeId);
        return grade?.sections.find(s => s.id.toString() === selectedSectionId);
    }, [selectedGradeId, selectedSectionId, gradeLevels]);

    const getStatusBadge = (status: string) => {
        switch (status.toLowerCase()) {
            case 'enrolled':
                return <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 uppercase text-[10px] font-black">Active</Badge>;
            case 'transferred':
                return <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200 uppercase text-[10px] font-black">Transferred Out</Badge>;
            case 'dropped':
                return <Badge variant="outline" className="bg-rose-50 text-rose-700 border-rose-200 uppercase text-[10px] font-black">Dropped Out</Badge>;
            default:
                return <Badge variant="secondary" className="uppercase text-[10px] font-black">{status}</Badge>;
        }
    };

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
                            An active School Year must be initialized in <span className="text-foreground font-bold">Academic Controls</span> before viewing class lists.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Class Lists" />
            <div className="flex flex-col gap-6 p-4 lg:p-6 h-full">
                
                <div className="flex flex-col gap-1 px-1">
                    <div className="flex items-center gap-2 text-primary">
                        <Users className="size-6" />
                        <h1 className="text-2xl font-bold tracking-tight uppercase">Class Lists</h1>
                    </div>
                    <p className="text-sm text-muted-foreground font-medium italic leading-none">View and manage student distributions for <span className="text-foreground font-bold uppercase tracking-wider">{activeYear.name}</span>.</p>
                </div>

                <Card className="overflow-hidden border-primary/10">
                    <CardHeader className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b bg-muted/5 py-4 px-6 shrink-0">
                        <div className="flex flex-wrap items-center gap-6">
                            <div className="grid gap-1.5">
                                <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Grade Level</Label>
                                <Select value={selectedGradeId} onValueChange={setSelectedGradeId}>
                                    <SelectTrigger className="w-full sm:w-[140px] h-9 font-bold uppercase text-xs tracking-wider">
                                        <SelectValue placeholder="Grade" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {gradeLevels.map(g => (
                                            <SelectItem key={g.id} value={g.id.toString()}>{g.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1.5">
                                <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Section</Label>
                                <Select value={selectedSectionId} onValueChange={setSelectedSectionId}>
                                    <SelectTrigger className="w-full sm:w-[180px] h-9 font-bold uppercase text-xs tracking-wider">
                                        <SelectValue placeholder="Section" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {gradeLevels.find(g => g.id.toString() === selectedGradeId)?.sections.map(s => (
                                            <SelectItem key={s.id} value={s.id.toString()}>{s.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <CardAction>
                            <Button variant="outline" className="gap-2 font-bold uppercase tracking-tight h-9">
                                <Printer className="size-4" />
                                Print Class List
                            </Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent className="p-0 overflow-auto flex-1">
                        <Table>
                            <TableHeader className="bg-muted/50 sticky top-0 z-10 shadow-sm">
                                <TableRow>
                                    <TableHead className="pl-6 font-black text-[10px] uppercase tracking-widest">LRN</TableHead>
                                    <TableHead className="text-center font-black text-[10px] uppercase tracking-widest">Student Name</TableHead>
                                    <TableHead className="text-center font-black text-[10px] uppercase tracking-widest">Gender</TableHead>
                                    <TableHead className="text-right pr-6 font-black text-[10px] uppercase tracking-widest">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {currentSection?.enrollments.map((enrollment) => (
                                    <TableRow key={enrollment.id} className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-mono text-xs font-bold text-primary">
                                            {enrollment.student.lrn}
                                        </TableCell>
                                        <TableCell className="text-center font-bold text-sm tracking-tight">
                                            {enrollment.student.last_name}, {enrollment.student.first_name}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge variant="secondary" className="uppercase text-[9px] font-black tracking-tighter bg-muted/50 text-muted-foreground border-none">
                                                {enrollment.student.gender}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right pr-6">
                                            {getStatusBadge(enrollment.status)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(!currentSection || currentSection.enrollments.length === 0) && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-32 text-center">
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground/40">
                                                <Users className="size-8" />
                                                <p className="text-xs font-medium uppercase tracking-widest">No students found in this section</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
