import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { GraduationCap, ShieldCheck, Heart, Info, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grades',
        href: '/student/grades',
    },
];

export default function Grades() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Grades" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        < GraduationCap className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">Academic Progress</h1>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* General Average Summary */}
                    <Card className="md:col-span-1 border-primary/10 shadow-md bg-primary/[0.02]">
                        <CardContent className="p-6 flex flex-col items-center justify-center text-center space-y-2">
                            <p className="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Current General Average</p>
                            <div className="text-5xl font-black text-primary tracking-tighter">83.5</div>
                            <div className="flex items-center gap-1.5 text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">
                                <TrendingUp className="size-3" />
                                <span>+1.2 from last qtr</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2 border-primary/10 shadow-sm">
                        <CardContent className="p-6 flex items-center gap-4">
                            <div className="p-3 bg-primary/10 rounded-xl text-primary">
                                <Info className="size-6" />
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-bold">SY 2025-2026 Reporting</p>
                                <p className="text-xs text-muted-foreground leading-relaxed">
                                    Grades are updated in real-time as teachers finalize their quarterly assessments. Please contact the registrar for certified Form 138 copies.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="academic" className="w-full">
                    <TabsList className="bg-muted/50 p-1 mb-4">
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
                        <Card className="shadow-md border-primary/10 overflow-hidden">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6 font-black text-[10px] uppercase">Subject Name</TableHead>
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
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card className="shadow-md border-primary/10 overflow-hidden">
                            <CardHeader className="bg-muted/10 border-b py-4">
                                <div className="flex items-center gap-2">
                                    <Info className="size-4 text-blue-600" />
                                    <p className="text-xs font-medium text-blue-800">Legend: <strong>AO</strong> (Always Observed), <strong>SO</strong> (Sometimes), <strong>RO</strong> (Rarely), <strong>NO</strong> (Not Observed)</p>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6 font-black text-[10px] uppercase">Core Value</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Q1</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Q2</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Q3</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Q4</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {['Maka-Diyos', 'Makatao', 'Makakalikasan', 'Makabansa'].map((v, i) => (
                                            <TableRow key={i} className="hover:bg-muted/30 transition-colors">
                                                <TableCell className="pl-6 font-bold">{v}</TableCell>
                                                <TableCell className="text-center font-black text-primary">AO</TableCell>
                                                <TableCell className="text-center font-black text-primary">AO</TableCell>
                                                <TableCell className="text-center text-muted-foreground">-</TableCell>
                                                <TableCell className="text-center text-muted-foreground">-</TableCell>
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
