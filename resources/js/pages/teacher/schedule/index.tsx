import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
        title: 'My Schedule',
        href: '/teacher/schedule',
    },
];

export default function Schedule() {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Schedule" />
            <div className="flex flex-col gap-4">
                <div className="flex justify-end">
                    <Button variant="outline" className="gap-2">
                        <Printer className="size-4" />
                        Print My Schedule
                    </Button>
                </div>

                <Card>
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
                            {/* 8:00 AM Row */}
                            <TableRow className="h-24">
                                <TableCell className="text-center font-mono text-[11px] font-bold text-muted-foreground border-r bg-muted/5">08:00 AM</TableCell>
                                <TableCell className="border-r p-1.5">
                                    <div className="bg-blue-50 border-l-4 border-blue-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-blue-700 text-xs">MATH 7</p>
                                        <p className="text-[9px] font-bold text-blue-600/80 uppercase">Grade 7 - Rizal</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border-r p-1.5">
                                    <div className="bg-purple-50 border-l-4 border-purple-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-purple-700 text-xs">MATH 8</p>
                                        <p className="text-[9px] font-bold text-purple-600/80 uppercase">Grade 8 - Gusion</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border-r p-1.5">
                                    <div className="bg-blue-50 border-l-4 border-blue-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-blue-700 text-xs">MATH 7</p>
                                        <p className="text-[9px] font-bold text-blue-600/80 uppercase">Grade 7 - Rizal</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border-r p-1.5">
                                    <div className="bg-purple-50 border-l-4 border-purple-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-purple-700 text-xs">MATH 8</p>
                                        <p className="text-[9px] font-bold text-purple-600/80 uppercase">Grade 8 - Gusion</p>
                                    </div>
                                </TableCell>
                                <TableCell className="p-1.5">
                                    <div className="bg-amber-50 border-l-4 border-amber-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-amber-700 text-xs">ADVISORY</p>
                                        <p className="text-[9px] font-bold text-amber-600/80 uppercase">Rizal</p>
                                    </div>
                                </TableCell>
                            </TableRow>

                            {/* 10:00 AM Recess */}
                            <TableRow className="h-10 bg-muted/20">
                                <TableCell className="text-center font-mono text-[10px] font-black border-r">10:00 AM</TableCell>
                                <TableCell colSpan={5} className="text-center text-[10px] font-black tracking-[0.3em] text-muted-foreground/50 uppercase">Recess Break</TableCell>
                            </TableRow>

                            {/* 10:30 AM Row */}
                            <TableRow className="h-24">
                                <TableCell className="text-center font-mono text-[11px] font-bold text-muted-foreground border-r bg-muted/5">10:30 AM</TableCell>
                                <TableCell className="border-r p-1.5" />
                                <TableCell className="border-r p-1.5">
                                    <div className="bg-blue-50 border-l-4 border-blue-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-blue-700 text-xs">MATH 7</p>
                                        <p className="text-[9px] font-bold text-blue-600/80 uppercase">Grade 7 - Bonifacio</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border-r p-1.5" />
                                <TableCell className="border-r p-1.5">
                                    <div className="bg-blue-50 border-l-4 border-blue-500 p-2 h-full rounded shadow-sm">
                                        <p className="font-black text-blue-700 text-xs">MATH 7</p>
                                        <p className="text-[9px] font-bold text-blue-600/80 uppercase">Grade 7 - Bonifacio</p>
                                    </div>
                                </TableCell>
                                <TableCell className="p-1.5" />
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>
        </AppLayout>
    );
}
