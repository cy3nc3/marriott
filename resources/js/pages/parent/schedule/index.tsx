import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';
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
        title: 'Schedule',
        href: '/parent/schedule',
    },
];

export default function Schedule() {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Class Schedule" />
            <div className="flex flex-col gap-6">
                
                <div className="flex justify-end">
                    <Button variant="outline">
                        <Printer className="mr-2 h-4 w-4" />
                        Print Schedule
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Weekly Schedule</CardTitle>
                        <CardDescription>Class timings and subjects</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[100px] text-center">Time</TableHead>
                                    {days.map(day => (
                                        <TableHead key={day} className="text-center">{day}</TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {/* 7:00 AM Row */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">07:00 AM</TableCell>
                                    <TableCell colSpan={5} className="p-2">
                                        <div className="bg-muted p-2 rounded text-center text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                            Flag Ceremony
                                        </div>
                                    </TableCell>
                                </TableRow>

                                {/* 8:00 AM Row */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">08:00 AM</TableCell>
                                    {[1, 2, 3, 4].map(i => (
                                        <TableCell key={i} className="p-2">
                                            <div className="bg-blue-50 border-l-4 border-blue-500 p-2 rounded shadow-sm">
                                                <p className="font-bold text-blue-700 text-xs">Mathematics 7</p>
                                                <p className="text-[10px] text-blue-600/80 uppercase">Mr. Arthur Santos</p>
                                            </div>
                                        </TableCell>
                                    ))}
                                    <TableCell className="p-2">
                                        <div className="bg-amber-50 border-l-4 border-amber-500 p-2 rounded shadow-sm">
                                            <p className="font-bold text-amber-700 text-xs">Values Ed (EsP)</p>
                                            <p className="text-[10px] text-amber-600/80 uppercase">Ms. Venus Cruz</p>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                {/* 10:00 AM Recess */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">10:00 AM</TableCell>
                                    <TableCell colSpan={5} className="text-center text-xs font-bold uppercase text-muted-foreground bg-muted/20">
                                        Recess
                                    </TableCell>
                                </TableRow>

                                {/* 10:30 AM Row */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">10:30 AM</TableCell>
                                    {[1, 2, 3, 4].map(i => (
                                        <TableCell key={i} className="p-2">
                                            <div className="bg-green-50 border-l-4 border-green-500 p-2 rounded shadow-sm">
                                                <p className="font-bold text-green-700 text-xs">Science 7</p>
                                                <p className="text-[10px] text-green-600/80 uppercase">Ms. Clara Oswald</p>
                                            </div>
                                        </TableCell>
                                    ))}
                                    <TableCell className="p-2" />
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
