import { Head } from '@inertiajs/react';
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
            <div className="flex flex-col gap-4">
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-24 text-center">Time</TableHead>
                                    {days.map(day => (
                                        <TableHead key={day} className="text-center min-w-[120px]">{day}</TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {/* 7:00 AM Row */}
                                <TableRow className="h-16">
                                    <TableCell className="text-center font-medium text-muted-foreground align-top py-4">07:00 AM</TableCell>
                                    <TableCell colSpan={5} className="p-2">
                                        <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs font-bold uppercase tracking-wider text-primary flex items-center justify-center">
                                            Flag Ceremony
                                        </div>
                                    </TableCell>
                                </TableRow>

                                {/* 8:00 AM Row */}
                                <TableRow className="h-32">
                                    <TableCell className="text-center font-medium text-muted-foreground align-top py-4">08:00 AM</TableCell>
                                    {[1, 2, 3, 4].map(i => (
                                        <TableCell key={i} className="p-2 align-top">
                                            <div className="bg-blue-50 border-l-4 border-blue-500 p-2 h-full rounded">
                                                <p className="font-bold text-blue-700 text-xs">Mathematics 7</p>
                                                <p className="text-[10px] font-medium text-blue-600/80">Teacher 1</p>
                                            </div>
                                        </TableCell>
                                    ))}
                                    <TableCell className="p-2 align-top">
                                        <div className="bg-amber-50 border-l-4 border-amber-500 p-2 h-full rounded">
                                            <p className="font-bold text-amber-700 text-xs">Values Ed (EsP)</p>
                                            <p className="text-[10px] font-medium text-amber-600/80">Teacher 2</p>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                {/* 10:00 AM Recess */}
                                <TableRow className="h-12 bg-muted/20">
                                    <TableCell className="text-center font-medium align-middle">10:00 AM</TableCell>
                                    <TableCell colSpan={5} className="text-center text-xs font-bold tracking-widest text-muted-foreground uppercase align-middle">Recess</TableCell>
                                </TableRow>

                                {/* 10:30 AM Row */}
                                <TableRow className="h-32">
                                    <TableCell className="text-center font-medium text-muted-foreground align-top py-4">10:30 AM</TableCell>
                                    {[1, 2, 3, 4].map(i => (
                                        <TableCell key={i} className="p-2 align-top">
                                            <div className="bg-green-50 border-l-4 border-green-500 p-2 h-full rounded">
                                                <p className="font-bold text-green-700 text-xs">Science 7</p>
                                                <p className="text-[10px] font-medium text-green-600/80">Teacher 3</p>
                                            </div>
                                        </TableCell>
                                    ))}
                                    <TableCell className="p-2" />
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
