import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schedule',
        href: '/student/schedule',
    },
];

export default function Schedule() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Card className="overflow-hidden">
                    <Table className="border-collapse border">
                        <TableHeader>
                            <TableRow className="bg-muted/50">
                                <TableHead className="w-[100px] border text-center font-bold">Time</TableHead>
                                <TableHead className="border text-center font-bold">Monday</TableHead>
                                <TableHead className="border text-center font-bold">Tuesday</TableHead>
                                <TableHead className="border text-center font-bold">Wednesday</TableHead>
                                <TableHead className="border text-center font-bold">Thursday</TableHead>
                                <TableHead className="border text-center font-bold">Friday</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow className="h-16">
                                <TableCell className="border bg-muted/30 text-center font-bold text-xs">07:00 AM</TableCell>
                                <TableCell className="border p-1" colSpan={5}>
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">Flag Ceremony</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow className="h-20">
                                <TableCell className="border bg-muted/30 text-center font-bold text-xs">08:00 AM</TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">MATH7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Mr. A. Santos</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">MATH7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Mr. A. Santos</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">MATH7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Mr. A. Santos</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">MATH7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Mr. A. Santos</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-yellow-500/10 border-l-4 border-yellow-500 p-2 h-full rounded text-xs text-yellow-700 dark:text-yellow-500">
                                        <p className="font-bold">ESP7</p>
                                        <p className="text-[10px] opacity-80 italic">Ms. V. Cruz</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow className="h-20">
                                <TableCell className="border bg-muted/30 text-center font-bold text-xs">09:00 AM</TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">ENG7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Ms. B. Reyes</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">ENG7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Ms. B. Reyes</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">ENG7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Ms. B. Reyes</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1">
                                    <div className="bg-primary/10 border-l-4 border-primary p-2 h-full rounded text-xs">
                                        <p className="font-bold">ENG7</p>
                                        <p className="text-muted-foreground italic text-[10px]">Ms. B. Reyes</p>
                                    </div>
                                </TableCell>
                                <TableCell className="border p-1" />
                            </TableRow>
                            <TableRow className="bg-muted/50 h-8">
                                <TableCell className="border text-center font-bold text-[10px]">10:00 AM</TableCell>
                                <TableCell className="border text-center font-bold text-muted-foreground text-xs" colSpan={5}>RECESS</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>
        </AppLayout>
    );
}
