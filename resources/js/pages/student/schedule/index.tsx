import { Head } from '@inertiajs/react';
import { Printer, CalendarDays } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
        title: 'Schedule',
        href: '/student/schedule',
    },
];

export default function Schedule() {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Schedule" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="mb-2 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex items-center gap-2">
                        <CalendarDays className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">
                            Weekly Class Schedule
                        </h1>
                    </div>
                    <Button
                        variant="outline"
                        className="gap-2 border-primary/20 hover:bg-primary/5"
                    >
                        <Printer className="size-4 text-primary" />
                        Print Schedule
                    </Button>
                </div>

                <Card className="overflow-hidden border-primary/10 shadow-md">
                    <Table className="border-collapse">
                        <TableHeader>
                            <TableRow className="bg-muted/30">
                                <TableHead className="w-28 border-r text-center text-[10px] font-black uppercase">
                                    Time
                                </TableHead>
                                {days.map((day) => (
                                    <TableHead
                                        key={day}
                                        className="border-r text-center text-[10px] font-black uppercase"
                                    >
                                        {day}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {/* 7:00 AM Row */}
                            <TableRow className="h-12">
                                <TableCell className="border-r bg-muted/5 text-center font-mono text-[11px] font-bold text-muted-foreground">
                                    07:00 AM
                                </TableCell>
                                <TableCell colSpan={5} className="p-1.5">
                                    <div className="flex h-full items-center justify-center rounded border-l-4 border-primary bg-primary/10 p-2 text-[10px] font-black tracking-widest text-primary uppercase">
                                        Flag Ceremony
                                    </div>
                                </TableCell>
                            </TableRow>

                            {/* 8:00 AM Row */}
                            <TableRow className="h-24">
                                <TableCell className="border-r bg-muted/5 text-center font-mono text-[11px] font-bold text-muted-foreground">
                                    08:00 AM
                                </TableCell>
                                {[1, 2, 3, 4].map((i) => (
                                    <TableCell
                                        key={i}
                                        className="border-r p-1.5"
                                    >
                                        <div className="h-full rounded border-l-4 border-blue-500 bg-blue-50 p-2 shadow-sm">
                                            <p className="text-xs font-black text-blue-700 uppercase">
                                                Mathematics 7
                                            </p>
                                            <p className="text-[9px] font-bold text-blue-600/80 uppercase">
                                                Teacher 1
                                            </p>
                                        </div>
                                    </TableCell>
                                ))}
                                <TableCell className="p-1.5">
                                    <div className="h-full rounded border-l-4 border-amber-500 bg-amber-50 p-2 shadow-sm">
                                        <p className="text-xs font-black text-amber-700 uppercase">
                                            Values Ed (EsP)
                                        </p>
                                        <p className="text-[9px] font-bold text-amber-600/80 uppercase">
                                            Teacher 2
                                        </p>
                                    </div>
                                </TableCell>
                            </TableRow>

                            {/* 10:00 AM Recess */}
                            <TableRow className="h-10 bg-muted/20">
                                <TableCell className="border-r text-center font-mono text-[10px] font-black">
                                    10:00 AM
                                </TableCell>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-[10px] font-black tracking-[0.3em] text-muted-foreground/50 uppercase"
                                >
                                    Recess
                                </TableCell>
                            </TableRow>

                            {/* 10:30 AM Row */}
                            <TableRow className="h-24">
                                <TableCell className="border-r bg-muted/5 text-center font-mono text-[11px] font-bold text-muted-foreground">
                                    10:30 AM
                                </TableCell>
                                {[1, 2, 3, 4].map((i) => (
                                    <TableCell
                                        key={i}
                                        className="border-r p-1.5"
                                    >
                                        <div className="h-full rounded border-l-4 border-green-500 bg-green-50 p-2 shadow-sm">
                                            <p className="text-xs font-black text-green-700 uppercase">
                                                Science 7
                                            </p>
                                            <p className="text-[9px] font-bold text-green-600/80 uppercase">
                                                Teacher 3
                                            </p>
                                        </div>
                                    </TableCell>
                                ))}
                                <TableCell className="p-1.5" />
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>
        </AppLayout>
    );
}
