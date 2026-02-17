import { Head } from '@inertiajs/react';
import {
    GraduationCap,
    ShieldCheck,
    Heart,
    Info,
    TrendingUp,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex items-center gap-2">
                        <GraduationCap className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">
                            Academic Progress
                        </h1>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    {/* General Average Summary */}
                    <Card className="border-primary/10 bg-primary/[0.02] shadow-md md:col-span-1">
                        <CardContent className="flex flex-col items-center justify-center space-y-2 p-6 text-center">
                            <p className="text-[10px] font-black tracking-[0.2em] text-muted-foreground uppercase">
                                Current General Average
                            </p>
                            <div className="text-5xl font-black tracking-tighter text-primary">
                                83.5
                            </div>
                            <div className="flex items-center gap-1.5 rounded-full border border-green-100 bg-green-50 px-2 py-0.5 text-xs font-bold text-green-600">
                                <TrendingUp className="size-3" />
                                <span>+1.2 from last qtr</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-primary/10 shadow-sm md:col-span-2">
                        <CardContent className="flex items-center gap-4 p-6">
                            <div className="rounded-xl bg-primary/10 p-3 text-primary">
                                <Info className="size-6" />
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-bold">
                                    SY 2025-2026 Reporting
                                </p>
                                <p className="text-xs leading-relaxed text-muted-foreground">
                                    Grades are updated in real-time as teachers
                                    finalize their quarterly assessments. Please
                                    contact the registrar for certified Form 138
                                    copies.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="academic" className="w-full">
                    <TabsList className="mb-4 bg-muted/50 p-1">
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
                        <Card className="overflow-hidden border-primary/10 shadow-md">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6 text-[10px] font-black uppercase">
                                                Subject Name
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q1
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q2
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q3
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q4
                                            </TableHead>
                                            <TableHead className="bg-primary/5 pr-6 text-right text-[10px] font-black text-primary uppercase">
                                                Final
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {[
                                            {
                                                sub: 'Mathematics 7',
                                                q1: '86',
                                                q2: '88',
                                            },
                                            {
                                                sub: 'English 7',
                                                q1: '85',
                                                q2: '87',
                                            },
                                            {
                                                sub: 'Science 7',
                                                q1: '84',
                                                q2: '85',
                                            },
                                            {
                                                sub: 'Filipino 7',
                                                q1: '88',
                                                q2: '89',
                                            },
                                        ].map((r, i) => (
                                            <TableRow
                                                key={i}
                                                className="transition-colors hover:bg-muted/30"
                                            >
                                                <TableCell className="pl-6 font-bold">
                                                    {r.sub}
                                                </TableCell>
                                                <TableCell className="text-center font-medium">
                                                    {r.q1}
                                                </TableCell>
                                                <TableCell className="text-center font-medium">
                                                    {r.q2}
                                                </TableCell>
                                                <TableCell className="text-center text-muted-foreground">
                                                    -
                                                </TableCell>
                                                <TableCell className="text-center text-muted-foreground">
                                                    -
                                                </TableCell>
                                                <TableCell className="bg-primary/[0.01] pr-6 text-right font-black text-primary">
                                                    -
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card className="overflow-hidden border-primary/10 shadow-md">
                            <CardHeader className="border-b bg-muted/10 py-4">
                                <div className="flex items-center gap-2">
                                    <Info className="size-4 text-blue-600" />
                                    <p className="text-xs font-medium text-blue-800">
                                        Legend: <strong>AO</strong> (Always
                                        Observed), <strong>SO</strong>{' '}
                                        (Sometimes), <strong>RO</strong>{' '}
                                        (Rarely), <strong>NO</strong> (Not
                                        Observed)
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6 text-[10px] font-black uppercase">
                                                Core Value
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q1
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q2
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q3
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Q4
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {[
                                            'Maka-Diyos',
                                            'Makatao',
                                            'Makakalikasan',
                                            'Makabansa',
                                        ].map((v, i) => (
                                            <TableRow
                                                key={i}
                                                className="transition-colors hover:bg-muted/30"
                                            >
                                                <TableCell className="pl-6 font-bold">
                                                    {v}
                                                </TableCell>
                                                <TableCell className="text-center font-black text-primary">
                                                    AO
                                                </TableCell>
                                                <TableCell className="text-center font-black text-primary">
                                                    AO
                                                </TableCell>
                                                <TableCell className="text-center text-muted-foreground">
                                                    -
                                                </TableCell>
                                                <TableCell className="text-center text-muted-foreground">
                                                    -
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
