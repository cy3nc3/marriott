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
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                    <div className="flex items-center gap-2">
                        <CalendarDays className="size-6 text-primary" />
                        <h1 className="text-2xl font-black tracking-tight">Weekly Class Schedule</h1>
                    </div>
                    <Button variant="outline" className="gap-2 border-primary/20 hover:bg-primary/5">
                        <Printer className="size-4 text-primary" />
                        Print Schedule
                    </Button>
                </div>

                <Card className="shadow-md border-primary/10 overflow-hidden">
                    <Table className="border-collapse">
                        <TableHeader>
                            <TableRow className="bg-muted/30">
                                <TableHead className="w-28 border-r text-center font-black text-[10px] uppercase">Time</TableHead>
                                {days.map(day => (
                                    <TableHead key={day} className="text-center font-black text-[10px] uppercase border-r">{day}</TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {/* 7:00 AM Row */}
                            <TableRow className="h-12">
                                <TableCell className="text-center font-mono text-[11px] font-bold text-muted-foreground border-r bg-muted/5">07:00 AM</TableCell>
                                <TableCell colSpan={5} className="p-1.5">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-[10px] font-black uppercase tracking-widest text-primary flex items-center justify-center">
                                        Flag Ceremony
                                    </div>
                                </TableCell>
                            </TableRow>

                            {/* 8:00 AM Row */}
                            <TableRow className="h-24">
                                <TableCell className="text-center font-mono text-[11px] font-bold text-muted-foreground border-r bg-muted/5">08:00 AM</TableCell>
                                {[1, 2, 3, 4].map(i => (
                                    <TableCell key={i} className="border-r p-1.5">
                                        <div className="bg-blue-50 border-l-4 border-blue-500 p-2 h-full rounded shadow-sm">
                                            <p className="font-black text-blue-700 text-xs uppercase">Mathematics 7</p>
                                            <p className="text-[9px] font-bold text-blue-600/80 uppercase">Mr. Arthur Santos</p>
                                        </div>
                                    </TableCell>
                                ))}
                                <TableCell className="p-1.5">
                                    <div className="bg-amber-50 border-l-4 border-amber-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-amber-700 text-xs uppercase">Values Ed (EsP)</p>
                                        <p className="text-[9px] font-bold text-amber-600/80 uppercase">Ms. Venus Cruz</p>
                                    </div>
                                </TableCell>
                            </TableRow>

                            {/* 10:00 AM Recess */}
                            <TableRow className="h-10 bg-muted/20">
                                <TableCell className="text-center font-mono text-[10px] font-black border-r">10:00 AM</TableCell>
                                <TableCell colSpan={5} className="text-center text-[10px] font-black tracking-[0.3em] text-muted-foreground/50 uppercase">Recess</TableCell>
                            </TableRow>

                            {/* 10:30 AM Row */}
                            <TableRow className="h-24">
                                <TableCell className="text-center font-mono text-[11px] font-bold text-muted-foreground border-r bg-muted/5">10:30 AM</TableCell>
                                {[1, 2, 3, 4].map(i => (
                                    <TableCell key={i} className="border-r p-1.5">
                                        <div className="bg-green-50 border-l-4 border-green-500 p-2 h-full rounded shadow-sm">
                                            <p className="font-black text-green-700 text-xs uppercase">Science 7</p>
                                            <p className="text-[9px] font-bold text-green-600/80 uppercase">Ms. Clara Oswald</p>
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
