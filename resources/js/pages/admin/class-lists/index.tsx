import { Head } from '@inertiajs/react';
import { AlertCircle, Printer, Users } from 'lucide-react';
import { useState, useMemo, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
    const [selectedGradeId, setSelectedGradeId] = useState(
        gradeLevels[0]?.id.toString() || '',
    );
    const [selectedSectionId, setSelectedSectionId] = useState('');
    const [genderFilter, setGenderFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

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

    const currentSection = useMemo(() => {
        const grade = gradeLevels.find(
            (g) => g.id.toString() === selectedGradeId,
        );
        return grade?.sections.find(
            (s) => s.id.toString() === selectedSectionId,
        );
    }, [selectedGradeId, selectedSectionId, gradeLevels]);

    const filteredEnrollments = useMemo(() => {
        if (!currentSection) return [];
        let filtered = currentSection.enrollments || [];

        if (genderFilter !== 'all') {
            filtered = filtered.filter(
                (e) =>
                    e.student.gender.toLowerCase() ===
                    genderFilter.toLowerCase(),
            );
        }

        if (statusFilter !== 'all') {
            filtered = filtered.filter(
                (e) => e.status.toLowerCase() === statusFilter.toLowerCase(),
            );
        }

        return filtered;
    }, [currentSection, genderFilter, statusFilter]);

    const sectionStats = useMemo(() => {
        if (!currentSection) return { total: 0, male: 0, female: 0 };
        const enrollments = currentSection.enrollments || [];
        return {
            total: enrollments.length,
            male: enrollments.filter(
                (e) => e.student.gender.toLowerCase() === 'male',
            ).length,
            female: enrollments.filter(
                (e) => e.student.gender.toLowerCase() === 'female',
            ).length,
        };
    }, [currentSection]);

    const getStatusBadge = (status: string) => {
        switch (status.toLowerCase()) {
            case 'enrolled':
                return <Badge variant="outline">Active</Badge>;
            case 'transferred':
                return <Badge variant="outline">Transferred</Badge>;
            case 'dropped':
                return <Badge variant="outline">Dropped</Badge>;
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
                        <h2 className="text-xl font-bold tracking-tight">
                            System Notice
                        </h2>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            An active School Year must be initialized in{' '}
                            <span className="font-semibold text-foreground">
                                School Year Manager
                            </span>{' '}
                            before viewing class lists.
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
                <Card>
                    <Tabs
                        value={selectedGradeId}
                        onValueChange={setSelectedGradeId}
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
                                <div className="flex flex-wrap items-center gap-3">
                                    <div className="flex items-center gap-2">
                                        <Label>Section</Label>
                                        <Select
                                            value={selectedSectionId}
                                            onValueChange={setSelectedSectionId}
                                        >
                                            <SelectTrigger className="w-[160px]">
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

                                    <div className="flex items-center gap-2">
                                        <Label>Gender</Label>
                                        <Select
                                            value={genderFilter}
                                            onValueChange={setGenderFilter}
                                        >
                                            <SelectTrigger className="w-[120px]">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All
                                                </SelectItem>
                                                <SelectItem value="male">
                                                    Male
                                                </SelectItem>
                                                <SelectItem value="female">
                                                    Female
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Label>Status</Label>
                                        <Select
                                            value={statusFilter}
                                            onValueChange={setStatusFilter}
                                        >
                                            <SelectTrigger className="w-[140px]">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All Status
                                                </SelectItem>
                                                <SelectItem value="enrolled">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="transferred">
                                                    Transferred
                                                </SelectItem>
                                                <SelectItem value="dropped">
                                                    Dropped
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <Button variant="outline" size="sm">
                                        <Printer className="size-4" />
                                        Print List
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
                                                <TableHead className="w-[150px] pl-6">
                                                    LRN
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Student Name
                                                </TableHead>
                                                <TableHead className="w-[100px] text-center">
                                                    Gender
                                                </TableHead>
                                                <TableHead className="w-[150px] pr-6 text-right">
                                                    Status
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredEnrollments.map(
                                                (enrollment) => (
                                                    <TableRow
                                                        key={enrollment.id}
                                                    >
                                                        <TableCell className="pl-6 font-mono text-xs font-medium">
                                                            {
                                                                enrollment
                                                                    .student.lrn
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-center font-medium">
                                                            {
                                                                enrollment
                                                                    .student
                                                                    .last_name
                                                            }
                                                            ,{' '}
                                                            {
                                                                enrollment
                                                                    .student
                                                                    .first_name
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            <Badge variant="secondary">
                                                                {
                                                                    enrollment
                                                                        .student
                                                                        .gender
                                                                }
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="pr-6 text-right">
                                                            {getStatusBadge(
                                                                enrollment.status,
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                            {filteredEnrollments.length ===
                                                0 && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={4}
                                                        className="h-32 text-center"
                                                    >
                                                        <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                                            <Users className="size-8 opacity-40" />
                                                            <p className="text-sm">
                                                                No enrollees
                                                                found matching
                                                                the filters.
                                                            </p>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                        {currentSection && (
                                            <TableFooter>
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={4}
                                                        className="px-6 py-3"
                                                    >
                                                        <div className="flex items-center justify-end gap-8">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-xs text-muted-foreground">
                                                                    Male:
                                                                </span>
                                                                <span className="text-sm font-semibold">
                                                                    {
                                                                        sectionStats.male
                                                                    }
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-xs text-muted-foreground">
                                                                    Female:
                                                                </span>
                                                                <span className="text-sm font-semibold">
                                                                    {
                                                                        sectionStats.female
                                                                    }
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center gap-2 border-l pl-8">
                                                                <span className="text-xs text-muted-foreground">
                                                                    Total
                                                                    Enrollees:
                                                                </span>
                                                                <span className="text-sm font-semibold">
                                                                    {
                                                                        sectionStats.total
                                                                    }
                                                                </span>
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
