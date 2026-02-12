import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Printer } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grades',
        href: '/parent/grades',
    },
];

export default function Grades() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grades" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-6">
                        <div className="space-y-1">
                            <CardTitle>Report Card (Form 138)</CardTitle>
                            <p className="text-sm text-muted-foreground">SY 2024 - 2025 • Juan Dela Cruz</p>
                        </div>
                        <Button variant="outline" size="sm" className="gap-2" onClick={() => window.print()}>
                            <Printer className="size-4" />
                            Print Card
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-8">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 bg-muted/30 rounded-lg border text-sm">
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">Student Name</p>
                                <p className="font-bold">Dela Cruz, Juan</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">Grade & Section</p>
                                <p className="font-bold">Grade 7 - Rizal</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">LRN</p>
                                <p className="font-bold">1098239012</p>
                            </div>
                        </div>

                        <div className="border rounded-lg overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50">
                                        <TableHead className="font-bold">Learning Areas</TableHead>
                                        <TableHead className="text-center w-[80px] font-bold">Q1</TableHead>
                                        <TableHead className="text-center w-[80px] font-bold">Q2</TableHead>
                                        <TableHead className="text-center w-[80px] font-bold">Q3</TableHead>
                                        <TableHead className="text-center w-[80px] font-bold">Q4</TableHead>
                                        <TableHead className="text-center w-[100px] font-bold">Final</TableHead>
                                        <TableHead className="text-center w-[120px] font-bold">Remarks</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="font-bold">Filipino</TableCell>
                                        <TableCell className="text-center">88</TableCell>
                                        <TableCell className="text-center">89</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center font-bold text-primary">-</TableCell>
                                        <TableCell className="text-center text-xs text-muted-foreground">-</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-bold">English</TableCell>
                                        <TableCell className="text-center">85</TableCell>
                                        <TableCell className="text-center">87</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center font-bold text-primary">-</TableCell>
                                        <TableCell className="text-center text-xs text-muted-foreground">-</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-bold">Mathematics</TableCell>
                                        <TableCell className="text-center">86</TableCell>
                                        <TableCell className="text-center">88</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center font-bold text-primary">-</TableCell>
                                        <TableCell className="text-center text-xs text-muted-foreground">-</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-bold">Science</TableCell>
                                        <TableCell className="text-center">84</TableCell>
                                        <TableCell className="text-center">85</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center font-bold text-primary">-</TableCell>
                                        <TableCell className="text-center text-xs text-muted-foreground">-</TableCell>
                                    </TableRow>
                                    <TableRow className="bg-primary/5 font-bold">
                                        <TableCell className="text-right">General Average</TableCell>
                                        <TableCell className="text-center text-primary">85.75</TableCell>
                                        <TableCell className="text-center text-primary">87.25</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center">-</TableCell>
                                        <TableCell className="text-center text-primary font-black">-</TableCell>
                                        <TableCell className="text-center font-medium">Passed</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
