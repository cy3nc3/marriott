import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
            <div className="flex flex-col gap-4">
                <div className="flex justify-end">
                    <Button variant="outline" size="sm">
                        <Printer className="mr-2 h-4 w-4" />
                        Print Schedule
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Weekly Schedule</CardTitle>
                        <CardDescription>
                            Class schedule for the current semester.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[100px] text-center">
                                        Time
                                    </TableHead>
                                    {days.map((day) => (
                                        <TableHead
                                            key={day}
                                            className="text-center"
                                        >
                                            {day}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {/* 7:00 AM Row */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">
                                        07:00 AM
                                    </TableCell>
                                    <TableCell
                                        colSpan={5}
                                        className="text-center"
                                    >
                                        <div className="rounded-md bg-muted p-2 text-sm font-medium">
                                            Flag Ceremony
                                        </div>
                                    </TableCell>
                                </TableRow>

                                {/* 8:00 AM Row */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">
                                        08:00 AM
                                    </TableCell>
                                    {[1, 2, 3, 4].map((i) => (
                                        <TableCell
                                            key={i}
                                            className="text-center"
                                        >
                                            <div className="rounded-md border border-blue-100 bg-blue-50 p-2 text-blue-700">
                                                <p className="text-xs font-semibold">
                                                    Mathematics 7
                                                </p>
                                                <p className="text-[10px] opacity-80">
                                                    Mr. Arthur Santos
                                                </p>
                                            </div>
                                        </TableCell>
                                    ))}
                                    <TableCell className="text-center">
                                        <div className="rounded-md border border-amber-100 bg-amber-50 p-2 text-amber-700">
                                            <p className="text-xs font-semibold">
                                                Values Ed
                                            </p>
                                            <p className="text-[10px] opacity-80">
                                                Ms. Venus Cruz
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                {/* 10:00 AM Recess */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">
                                        10:00 AM
                                    </TableCell>
                                    <TableCell
                                        colSpan={5}
                                        className="bg-muted/20 text-center text-sm font-medium text-muted-foreground"
                                    >
                                        Recess
                                    </TableCell>
                                </TableRow>

                                {/* 10:30 AM Row */}
                                <TableRow>
                                    <TableCell className="text-center font-medium">
                                        10:30 AM
                                    </TableCell>
                                    {[1, 2, 3, 4].map((i) => (
                                        <TableCell
                                            key={i}
                                            className="text-center"
                                        >
                                            <div className="rounded-md border border-green-100 bg-green-50 p-2 text-green-700">
                                                <p className="text-xs font-semibold">
                                                    Science 7
                                                </p>
                                                <p className="text-[10px] opacity-80">
                                                    Ms. Clara Oswald
                                                </p>
                                            </div>
                                        </TableCell>
                                    ))}
                                    <TableCell />
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
