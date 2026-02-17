import { Head } from '@inertiajs/react';
import { AlertCircle, Printer, Users, Filter } from 'lucide-react';
import { useState, useMemo, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableFooter,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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
    status: string;
}

interface Props {
    gradeLevels: GradeLevel[];
    activeYear: AcademicYear | null;
}

export default function ClassLists({ gradeLevels, activeYear }: Props) {
    const [selectedGradeId, setSelectedGradeId] = useState(gradeLevels[0]?.id.toString() || '');
    const [selectedSectionId, setSelectedSectionId] = useState('');
    const [genderFilter, setGenderFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

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

    const filteredEnrollments = useMemo(() => {
        if (!currentSection) return [];
        let filtered = currentSection.enrollments || [];

        if (genderFilter !== 'all') {
            filtered = filtered.filter(e => e.student.gender.toLowerCase() === genderFilter.toLowerCase());
        }

        if (statusFilter !== 'all') {
            filtered = filtered.filter(e => e.status.toLowerCase() === statusFilter.toLowerCase());
        }

        return filtered;
    }, [currentSection, genderFilter, statusFilter]);

    const sectionStats = useMemo(() => {
        if (!currentSection) return { total: 0, male: 0, female: 0 };
        const enrollments = currentSection.enrollments || [];
        return {
            total: enrollments.length,
            male: enrollments.filter(e => e.student.gender.toLowerCase() === 'male').length,
            female: enrollments.filter(e => e.student.gender.toLowerCase() === 'female').length,
        };
    }, [currentSection]);

    const getStatusBadge = (status: string) => {
        switch (status.toLowerCase()) {
            case 'enrolled':
                return <Badge variant="outline" className="border-emerald-500 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 font-bold text-[10px] uppercase">Active</Badge>;
            case 'transferred':
                return <Badge variant="outline" className="border-blue-500 text-blue-700 bg-blue-50 hover:bg-blue-100 font-bold text-[10px] uppercase">Transferred</Badge>;
            case 'dropped':
                return <Badge variant="outline" className="border-rose-500 text-rose-700 bg-rose-50 hover:bg-rose-100 font-bold text-[10px] uppercase">Dropped</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
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
                        <h2 className="text-xl font-bold tracking-tight">System Notice</h2>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            An active School Year must be initialized in <span className="font-semibold text-foreground">School Year Manager</span> before viewing class lists.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Class Lists" />
            <div className="flex flex-col gap-6">
                
                <Card className="flex flex-col pt-0">
                    <Tabs
                        value={selectedGradeId}
                        onValueChange={setSelectedGradeId}
                        className="flex w-full flex-1 flex-col gap-0"
                    >
                        <CardHeader className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b">
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
                            <div className="flex flex-wrap items-center gap-3">
                                <div className="flex items-center gap-2">
                                    <Label className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Section:</Label>
                                    <Select value={selectedSectionId} onValueChange={setSelectedSectionId}>
                                        <SelectTrigger className="h-8 w-[140px] text-xs font-bold">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {gradeLevels.find(g => g.id.toString() === selectedGradeId)?.sections.map(s => (
                                                <SelectItem key={s.id} value={s.id.toString()}>{s.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="h-4 w-px bg-border mx-1 hidden sm:block" />

                                <div className="flex items-center gap-2">
                                    <Label className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Gender:</Label>
                                    <Select value={genderFilter} onValueChange={setGenderFilter}>
                                        <SelectTrigger className="h-8 w-[100px] text-xs font-bold">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All</SelectItem>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Label className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Status:</Label>
                                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                                        <SelectTrigger className="h-8 w-[120px] text-xs font-bold">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="enrolled">Active</SelectItem>
                                            <SelectItem value="transferred">Transferred</SelectItem>
                                            <SelectItem value="dropped">Dropped</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Button variant="outline" size="sm" className="h-8 gap-2 border-primary/20 hover:bg-primary/5">
                                    <Printer className="size-3.5 text-primary" />
                                    <span className="text-xs font-bold">Print List</span>
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent className="p-0">
                            {gradeLevels.map((grade) => (
                                <TabsContent
                                    key={grade.id}
                                    value={grade.id.toString()}
                                    className="m-0 outline-none"
                                >
                                    <Table>
                                        <TableHeader className="bg-muted/30">
                                            <TableRow>
                                                <TableHead className="pl-6 w-[150px] font-black text-[10px] uppercase">LRN</TableHead>
                                                <TableHead className="text-center font-black text-[10px] uppercase">Student Name</TableHead>
                                                <TableHead className="text-center w-[100px] font-black text-[10px] uppercase">Gender</TableHead>
                                                <TableHead className="text-right pr-6 w-[150px] font-black text-[10px] uppercase">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredEnrollments.map((enrollment) => (
                                                <TableRow key={enrollment.id}>
                                                    <TableCell className="pl-6 font-mono text-xs font-medium">
                                                        {enrollment.student.lrn}
                                                    </TableCell>
                                                    <TableCell className="text-center font-medium">
                                                        {enrollment.student.last_name}, {enrollment.student.first_name}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge variant="secondary" className="font-normal text-[10px] uppercase tracking-tighter">
                                                            {enrollment.student.gender}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right pr-6">
                                                        {getStatusBadge(enrollment.status)}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            {filteredEnrollments.length === 0 && (
                                                <TableRow>
                                                    <TableCell colSpan={4} className="h-32 text-center">
                                                        <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground/50">
                                                            <Users className="size-8 opacity-20" />
                                                            <p className="text-xs font-medium italic">No enrollees found matching the filters</p>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                        {currentSection && (
                                            <TableFooter className="bg-muted/20 border-t-2">
                                                <TableRow>
                                                    <TableCell colSpan={4} className="py-3 px-6">
                                                        <div className="flex items-center justify-end gap-8">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-[10px] font-black uppercase text-muted-foreground">Male:</span>
                                                                <span className="text-sm font-black">{sectionStats.male}</span>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-[10px] font-black uppercase text-muted-foreground">Female:</span>
                                                                <span className="text-sm font-black">{sectionStats.female}</span>
                                                            </div>
                                                            <div className="flex items-center gap-2 border-l pl-8 border-border">
                                                                <span className="text-[10px] font-black uppercase text-primary">Total Enrollees:</span>
                                                                <span className="text-sm font-black text-primary">{sectionStats.total}</span>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            </TableFooter>
                                        )}
                                    </Table>
                                </TabsContent>
                            ))}
                        </CardContent>
                    </Tabs>
                </Card>
            </div>
        </AppLayout>
    );
}
