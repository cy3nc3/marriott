import { Head } from '@inertiajs/react';
import { Heart, Info, ShieldCheck, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grades',
        href: '/student/grades',
    },
];

export default function Grades() {
    const subjectRows = [
        {
            subject: 'Mathematics 7',
            q1: '86',
            q2: '88',
            q3: '-',
            q4: '-',
            final: '-',
        },
        {
            subject: 'English 7',
            q1: '85',
            q2: '87',
            q3: '-',
            q4: '-',
            final: '-',
        },
        {
            subject: 'Science 7',
            q1: '84',
            q2: '85',
            q3: '-',
            q4: '-',
            final: '-',
        },
        {
            subject: 'Filipino 7',
            q1: '88',
            q2: '89',
            q3: '-',
            q4: '-',
            final: '-',
        },
        {
            subject: 'Araling Panlipunan 7',
            q1: '87',
            q2: '88',
            q3: '-',
            q4: '-',
            final: '-',
        },
    ];

    const conductRows = [
        { coreValue: 'Maka-Diyos', q1: 'AO', q2: 'AO', q3: '-', q4: '-' },
        { coreValue: 'Makatao', q1: 'AO', q2: 'AO', q3: '-', q4: '-' },
        { coreValue: 'Makakalikasan', q1: 'SO', q2: 'AO', q3: '-', q4: '-' },
        { coreValue: 'Makabansa', q1: 'AO', q2: 'AO', q3: '-', q4: '-' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Grades" />

            <div className="flex flex-col gap-6">
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Current General Average</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="text-3xl font-semibold">87.40</p>
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <TrendingUp className="size-4 text-green-600" />
                                <span>+1.2 compared to last quarter</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <CardTitle>Report Context</CardTitle>
                                <div className="flex items-center gap-2">
                                    <Select defaultValue="sy-2025-2026">
                                        <SelectTrigger className="w-full sm:w-40">
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
                                    <Badge variant="outline">Live</Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Grades are shown as released by subject teachers
                                and verified by the registrar.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="academic" className="w-full">
                    <TabsList>
                        <TabsTrigger value="academic" className="gap-2">
                            <ShieldCheck className="size-4" />
                            Quarterly Grades
                        </TabsTrigger>
                        <TabsTrigger value="conduct" className="gap-2">
                            <Heart className="size-4" />
                            Character Ratings
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="academic">
                        <Card>
                            <CardHeader className="border-b">
                                <CardTitle>Subjects</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Subject
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q1
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q2
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q3
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q4
                                            </TableHead>
                                            <TableHead className="border-l pr-6 text-right">
                                                Final
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {subjectRows.map((row) => (
                                            <TableRow key={row.subject}>
                                                <TableCell className="pl-6 font-medium">
                                                    {row.subject}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {row.q1}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {row.q2}
                                                </TableCell>
                                                <TableCell className="border-l text-center text-muted-foreground">
                                                    {row.q3}
                                                </TableCell>
                                                <TableCell className="border-l text-center text-muted-foreground">
                                                    {row.q4}
                                                </TableCell>
                                                <TableCell className="border-l pr-6 text-right text-muted-foreground">
                                                    {row.final}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card>
                            <CardHeader className="border-b">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Info className="size-4" />
                                    <p>
                                        Legend: <strong>AO</strong> (Always),{' '}
                                        <strong>SO</strong> (Sometimes),{' '}
                                        <strong>RO</strong> (Rarely),{' '}
                                        <strong>NO</strong> (Not Observed)
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Core Value
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q1
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q2
                                            </TableHead>
                                            <TableHead className="border-l text-center">
                                                Q3
                                            </TableHead>
                                            <TableHead className="border-l pr-6 text-center">
                                                Q4
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {conductRows.map((row) => (
                                            <TableRow key={row.coreValue}>
                                                <TableCell className="pl-6 font-medium">
                                                    {row.coreValue}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {row.q1}
                                                </TableCell>
                                                <TableCell className="border-l text-center">
                                                    {row.q2}
                                                </TableCell>
                                                <TableCell className="border-l text-center text-muted-foreground">
                                                    {row.q3}
                                                </TableCell>
                                                <TableCell className="border-l pr-6 text-center text-muted-foreground">
                                                    {row.q4}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
