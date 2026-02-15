import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Printer, GraduationCap, MessageSquareText, TrendingUp, Info } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                    <div className="flex items-center gap-2">
                        <GraduationCap className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">Progress Report (Form 138)</h1>
                    </div>
                    <Button variant="outline" className="gap-2 border-primary/20 hover:bg-primary/5 shadow-sm">
                        <Printer className="size-4 text-primary" />
                        Print Certified Copy
                    </Button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {/* General Average Summary */}
                    <Card className="md:col-span-1 border-primary/10 shadow-md bg-primary/[0.02]">
                        <CardContent className="p-6 flex flex-col items-center justify-center text-center space-y-2">
                            <p className="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">SY 2025-2026 Average</p>
                            <div className="text-5xl font-black text-primary tracking-tighter">83.5</div>
                            <div className="flex items-center gap-1.5 text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">
                                <TrendingUp className="size-3" />
                                <span>Above Passing</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Message from Adviser */}
                    <Card className="md:col-span-3 border-primary/10 shadow-sm relative overflow-hidden bg-indigo-50/30">
                        <div className="absolute top-0 right-0 p-4 opacity-5 text-primary">
                            <MessageSquareText className="size-16" />
                        </div>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-black uppercase tracking-widest text-primary">Message from Class Adviser</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <blockquote className="italic text-sm font-medium text-muted-foreground border-l-4 border-primary/20 pl-4 py-1">
                                "Juan is showing significant improvement in Mathematics this quarter. He is more active in class discussions and completes his performance tasks on time. Keep up the good work!"
                            </blockquote>
                            <p className="mt-4 text-xs font-black text-primary">— MR. ARTHUR SANTOS</p>
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-md border-primary/10 overflow-hidden">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6 font-black text-[10px] uppercase">Subject</TableHead>
                                    <TableHead className="text-center font-bold text-[10px] uppercase">Q1</TableHead>
                                    <TableHead className="text-center font-bold text-[10px] uppercase">Q2</TableHead>
                                    <TableHead className="text-center font-bold text-[10px] uppercase">Q3</TableHead>
                                    <TableHead className="text-center font-bold text-[10px] uppercase">Q4</TableHead>
                                    <TableHead className="text-right pr-6 font-black text-[10px] uppercase text-primary bg-primary/5">Final</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {[
                                    { sub: 'Mathematics 7', q1: '86', q2: '88' },
                                    { sub: 'English 7', q1: '85', q2: '87' },
                                    { sub: 'Science 7', q1: '84', q2: '85' },
                                    { sub: 'Filipino 7', q1: '88', q2: '89' },
                                ].map((r, i) => (
                                    <TableRow key={i} className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-bold">{r.sub}</TableCell>
                                        <TableCell className="text-center font-medium">{r.q1}</TableCell>
                                        <TableCell className="text-center font-medium">{r.q2}</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-right pr-6 font-black text-primary bg-primary/[0.01]">-</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Alert className="bg-muted/30 border-primary/5">
                    <Info className="size-4 text-muted-foreground" />
                    <AlertDescription className="text-[10px] font-medium text-muted-foreground leading-relaxed">
                        Character ratings and core values are accessible via the student portal or by requesting a full SF9 printed copy. Final grades are subject to registrar verification.
                    </AlertDescription>
                </Alert>

            </div>
        </AppLayout>
    );
}
