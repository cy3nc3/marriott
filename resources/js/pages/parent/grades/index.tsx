import { Head } from '@inertiajs/react';
import { Printer, Info, TrendingUp } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
        title: 'Grades',
        href: '/parent/grades',
    },
];

export default function Grades() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Report Card" />
            <div className="flex flex-col gap-6">
                
                <div className="flex justify-end">
                    <Button variant="outline">
                        <Printer className="mr-2 h-4 w-4" />
                        Print Certified Copy
                    </Button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {/* General Average Summary */}
                    <Card className="md:col-span-1">
                        <CardHeader>
                            <CardTitle>Average</CardTitle>
                            <CardDescription>SY 2025-2026</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col items-center justify-center pt-6">
                            <div className="text-5xl font-bold mb-4">83.5</div>
                            <div className="flex items-center gap-2">
                                <TrendingUp className="h-4 w-4 text-green-600" />
                                <Badge variant="secondary" className="text-green-600 bg-green-50 hover:bg-green-100">
                                    Above Passing
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Message from Adviser */}
                    <Card className="md:col-span-3">
                        <CardHeader>
                            <CardTitle>Adviser's Message</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <blockquote className="border-l-4 border-primary/20 pl-4 italic text-muted-foreground">
                                "Juan is showing significant improvement in Mathematics this quarter. He is more active in class discussions and completes his performance tasks on time. Keep up the good work!"
                            </blockquote>
                            <p className="mt-4 font-bold text-sm">— Mr. Arthur Santos</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Subject Grades</CardTitle>
                        <CardDescription>Quarterly performance breakdown</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subject</TableHead>
                                    <TableHead className="text-center">Q1</TableHead>
                                    <TableHead className="text-center">Q2</TableHead>
                                    <TableHead className="text-center">Q3</TableHead>
                                    <TableHead className="text-center">Q4</TableHead>
                                    <TableHead className="text-right">Final</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {[
                                    { sub: 'Mathematics 7', q1: '86', q2: '88' },
                                    { sub: 'English 7', q1: '85', q2: '87' },
                                    { sub: 'Science 7', q1: '84', q2: '85' },
                                    { sub: 'Filipino 7', q1: '88', q2: '89' },
                                ].map((r, i) => (
                                    <TableRow key={i}>
                                        <TableCell className="font-medium">{r.sub}</TableCell>
                                        <TableCell className="text-center">{r.q1}</TableCell>
                                        <TableCell className="text-center">{r.q2}</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-right font-bold text-muted-foreground">-</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertTitle>Note</AlertTitle>
                    <AlertDescription>
                        Character ratings and core values are accessible via the student portal or by requesting a full SF9 printed copy. Final grades are subject to registrar verification.
                    </AlertDescription>
                </Alert>

            </div>
        </AppLayout>
    );
}
