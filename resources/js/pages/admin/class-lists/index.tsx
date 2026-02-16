import { Head } from '@inertiajs/react';
import { AlertCircle, Printer, Users } from 'lucide-react';
import { useState, useMemo, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
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
} from '@/components/ui/table';
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
    activeYear: AcademicYear | null;
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
                return <Badge variant="outline" className="border-emerald-500 text-emerald-700 bg-emerald-50 hover:bg-emerald-100">Active</Badge>;
            case 'transferred':
                return <Badge variant="outline" className="border-blue-500 text-blue-700 bg-blue-50 hover:bg-blue-100">Transferred Out</Badge>;
            case 'dropped':
                return <Badge variant="outline" className="border-rose-500 text-rose-700 bg-rose-50 hover:bg-rose-100">Dropped Out</Badge>;
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
                            An active School Year must be initialized in <span className="font-semibold text-foreground">Academic Controls</span> before viewing class lists.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Class Lists" />
            <div className="flex flex-col gap-6 p-6 h-full">
                
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2">
                        <Users className="size-6 text-primary" />
                        <h1 className="text-2xl font-bold tracking-tight">Class Lists</h1>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        View and manage student distributions for <span className="font-medium text-foreground">{activeYear.name}</span>.
                    </p>
                </div>

                <Card className="flex-1 flex flex-col overflow-hidden">
                    <CardHeader className="border-b pb-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex flex-col sm:flex-row gap-4">
                                <div className="grid gap-2">
                                    <Label className="text-xs text-muted-foreground">Grade Level</Label>
                                    <Select value={selectedGradeId} onValueChange={setSelectedGradeId}>
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Grade" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {gradeLevels.map(g => (
                                                <SelectItem key={g.id} value={g.id.toString()}>{g.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label className="text-xs text-muted-foreground">Section</Label>
                                    <Select value={selectedSectionId} onValueChange={setSelectedSectionId}>
                                        <SelectTrigger className="w-[180px]">
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
                            <Button variant="outline" className="gap-2 sm:self-end">
                                <Printer className="size-4" />
                                Print Class List
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0 flex-1 overflow-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6 w-[150px]">LRN</TableHead>
                                    <TableHead className="text-center">Student Name</TableHead>
                                    <TableHead className="text-center w-[100px]">Gender</TableHead>
                                    <TableHead className="text-right pr-6 w-[150px]">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {currentSection?.enrollments.map((enrollment) => (
                                    <TableRow key={enrollment.id}>
                                        <TableCell className="pl-6 font-mono text-xs font-medium">
                                            {enrollment.student.lrn}
                                        </TableCell>
                                        <TableCell className="text-center font-medium">
                                            {enrollment.student.last_name}, {enrollment.student.first_name}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge variant="secondary" className="font-normal text-xs">
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
                                        <TableCell colSpan={4} className="h-24 text-center">
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                                <Users className="size-6 opacity-50" />
                                                <p className="text-sm">No students found in this section</p>
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
