import { Head } from '@inertiajs/react';
import { Heart, Info, Printer, ShieldCheck, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
        href: '/parent/grades',
    },
];

type SubjectRow = {
    subject: string;
    q1: string;
    q2: string;
    q3: string;
    q4: string;
    final: string;
};

type ConductRow = {
    core_value: string;
    q1: string;
    q2: string;
    q3: string;
    q4: string;
};

interface Props {
    summary: {
        general_average: string | null;
        trend_text: string;
    };
    context: {
        student_name: string | null;
        school_year: string | null;
        adviser_name: string | null;
        adviser_remarks: string | null;
        is_verified: boolean;
    };
    subject_rows: SubjectRow[];
    conduct_rows: ConductRow[];
}

export default function Grades({
    summary,
    context,
    subject_rows,
    conduct_rows,
}: Props) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Report Card" />

            <div className="flex flex-col gap-6">
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>General Average</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="text-3xl font-semibold">
                                {summary.general_average ?? '-'}
                            </p>
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <TrendingUp className="size-4 text-green-600" />
                                <span>{summary.trend_text}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <CardTitle>Report Context</CardTitle>
                                <div className="flex items-center gap-2">
                                    <Badge variant="outline">
                                        {context.school_year ?? 'No school year'}
                                    </Badge>
                                    <Button variant="outline">
                                        <Printer className="size-4" />
                                        Print Copy
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {context.is_verified ? (
                                <Badge variant="outline">
                                    Verified by Registrar
                                </Badge>
                            ) : (
                                <Badge variant="outline">For Verification</Badge>
                            )}
                            <blockquote className="border-l-2 pl-3 text-sm text-muted-foreground italic">
                                {context.adviser_remarks ??
                                    'No adviser remarks available yet.'}
                            </blockquote>
                            <p className="text-sm font-medium">
                                Student: {context.student_name ?? '-'}
                            </p>
                            <p className="text-sm font-medium">
                                Adviser: {context.adviser_name ?? '-'}
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
                                        {subject_rows.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    className="py-8 text-center text-sm text-muted-foreground"
                                                    colSpan={6}
                                                >
                                                    No grade records available.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            subject_rows.map((row) => (
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
                                            ))
                                        )}
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
                                        {conduct_rows.map((row) => (
                                            <TableRow key={row.core_value}>
                                                <TableCell className="pl-6 font-medium">
                                                    {row.core_value}
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
