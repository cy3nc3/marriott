import { Head } from '@inertiajs/react';
import { ShieldCheck, Heart, Info, TrendingUp } from 'lucide-react';
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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
            <div className="flex flex-col gap-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {/* General Average Summary */}
                    <Card className="md:col-span-1">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">Current General Average</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-4xl font-bold">83.5</div>
                            <div className="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                                <TrendingUp className="h-4 w-4 text-green-500" />
                                <span className="text-green-600 font-medium">+1.2 from last qtr</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2">
                         <CardHeader className="flex flex-row items-center gap-4 space-y-0 pb-2">
                            <Info className="h-4 w-4 text-muted-foreground" />
                            <CardTitle className="text-sm font-medium">SY 2025-2026 Reporting</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Grades are updated in real-time as teachers finalize their quarterly assessments. Please contact the registrar for certified Form 138 copies.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="academic" className="w-full">
                    <TabsList>
                        <TabsTrigger value="academic" className="gap-2">
                            <ShieldCheck className="h-4 w-4" />
                            Quarterly Grades
                        </TabsTrigger>
                        <TabsTrigger value="conduct" className="gap-2">
                            <Heart className="h-4 w-4" />
                            Character Ratings
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="academic">
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Subject Name</TableHead>
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
                                                <TableCell className="text-right text-muted-foreground">-</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card>
                            <CardHeader className="pb-2">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Info className="h-4 w-4" />
                                    <span>Legend: <strong>AO</strong> (Always Observed), <strong>SO</strong> (Sometimes), <strong>RO</strong> (Rarely), <strong>NO</strong> (Not Observed)</span>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Core Value</TableHead>
                                            <TableHead className="text-center">Q1</TableHead>
                                            <TableHead className="text-center">Q2</TableHead>
                                            <TableHead className="text-center">Q3</TableHead>
                                            <TableHead className="text-center">Q4</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {['Maka-Diyos', 'Makatao', 'Makakalikasan', 'Makabansa'].map((v, i) => (
                                            <TableRow key={i}>
                                                <TableCell className="font-medium">{v}</TableCell>
                                                <TableCell className="text-center">AO</TableCell>
                                                <TableCell className="text-center">AO</TableCell>
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
