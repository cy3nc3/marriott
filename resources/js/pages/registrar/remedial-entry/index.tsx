import { Head } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Remedial Entry',
        href: '/registrar/remedial-entry',
    },
];

export default function RemedialEntry() {
    const remedialRows = [
        {
            subject: 'Mathematics 7',
            originalGrade: '72',
            remedialGrade: '81',
            finalRating: '75',
            status: 'Passed',
        },
        {
            subject: 'Science 7',
            originalGrade: '73',
            remedialGrade: '79',
            finalRating: '75',
            status: 'Passed',
        },
    ];

    const encodedRows = [
        {
            student: 'Juan Dela Cruz',
            lrn: '123456789012',
            schoolYear: 'SY 2025-2026',
            updatedAt: '02/20/2026',
            status: 'Submitted',
        },
        {
            student: 'Carlo Reyes',
            lrn: '987654321098',
            schoolYear: 'SY 2025-2026',
            updatedAt: '02/19/2026',
            status: 'Draft',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Remedial Entry" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Remedial Context</CardTitle>
                            <Badge variant="outline">Status: Draft</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="space-y-2">
                                <Label>School Year</Label>
                                <Select defaultValue="sy-2025-2026">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sy-2025-2026">
                                            SY 2025-2026
                                        </SelectItem>
                                        <SelectItem value="sy-2024-2025">
                                            SY 2024-2025
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Grade Level</Label>
                                <Select defaultValue="grade-7">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="grade-7">
                                            Grade 7
                                        </SelectItem>
                                        <SelectItem value="grade-8">
                                            Grade 8
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Search Student</Label>
                                <div className="flex gap-2">
                                    <Input placeholder="LRN or student name" />
                                    <Button variant="outline">Find</Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Selected Student</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Name
                                </p>
                                <p className="text-sm font-medium">
                                    Juan Dela Cruz
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    LRN
                                </p>
                                <p className="text-sm font-medium">
                                    123456789012
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Section
                                </p>
                                <p className="text-sm font-medium">
                                    Grade 7 - Rizal
                                </p>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Overall Result
                                </p>
                                <Badge variant="outline">For Encoding</Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <CardTitle>Remedial Subject Ratings</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Subject
                                        </TableHead>
                                        <TableHead className="border-l text-center">
                                            Original
                                        </TableHead>
                                        <TableHead className="border-l text-center">
                                            Remedial
                                        </TableHead>
                                        <TableHead className="border-l text-center">
                                            Final Rating
                                        </TableHead>
                                        <TableHead className="border-l pr-6 text-right">
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {remedialRows.map((row) => (
                                        <TableRow key={row.subject}>
                                            <TableCell className="pl-6 font-medium">
                                                {row.subject}
                                            </TableCell>
                                            <TableCell className="border-l text-center">
                                                {row.originalGrade}
                                            </TableCell>
                                            <TableCell className="border-l text-center">
                                                <Input
                                                    defaultValue={
                                                        row.remedialGrade
                                                    }
                                                    className="mx-auto w-20"
                                                />
                                            </TableCell>
                                            <TableCell className="border-l text-center">
                                                {row.finalRating}
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                <Badge
                                                    variant={
                                                        row.status === 'Passed'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {row.status}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                        <div className="flex items-center justify-end gap-2 border-t px-4 py-3">
                            <Button variant="outline">Save Draft</Button>
                            <Button>
                                <Save className="size-4" />
                                Submit Remedial Results
                            </Button>
                        </div>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Recent Remedial Encodings</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Student
                                    </TableHead>
                                    <TableHead>LRN</TableHead>
                                    <TableHead>School Year</TableHead>
                                    <TableHead>Last Updated</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {encodedRows.map((row) => (
                                    <TableRow key={row.lrn}>
                                        <TableCell className="pl-6 font-medium">
                                            {row.student}
                                        </TableCell>
                                        <TableCell>{row.lrn}</TableCell>
                                        <TableCell>{row.schoolYear}</TableCell>
                                        <TableCell>{row.updatedAt}</TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <Badge
                                                variant={
                                                    row.status === 'Submitted'
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {row.status}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
