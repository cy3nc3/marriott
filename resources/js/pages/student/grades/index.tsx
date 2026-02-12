import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
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
        title: 'Grades',
        href: '/student/grades',
    },
];

export default function Grades() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grades" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Progress Report</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="border rounded-lg overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50 text-[10px] uppercase tracking-wider font-bold">
                                        <TableHead className="pl-6">Subject</TableHead>
                                        <TableHead className="text-center w-[80px]">Q1</TableHead>
                                        <TableHead className="text-center w-[80px]">Q2</TableHead>
                                        <TableHead className="text-center w-[80px]">Q3</TableHead>
                                        <TableHead className="text-center w-[80px]">Q4</TableHead>
                                        <TableHead className="text-center w-[100px] pr-6">Final</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-medium">Mathematics 7</TableCell>
                                        <TableCell className="text-center font-bold">86</TableCell>
                                        <TableCell className="text-center font-bold">88</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center pr-6 font-black text-primary">-</TableCell>
                                    </TableRow>
                                    <TableRow className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-medium">English 7</TableCell>
                                        <TableCell className="text-center font-bold">85</TableCell>
                                        <TableCell className="text-center font-bold">87</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center pr-6 font-black text-primary">-</TableCell>
                                    </TableRow>
                                    <TableRow className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-medium">Science 7</TableCell>
                                        <TableCell className="text-center font-bold">84</TableCell>
                                        <TableCell className="text-center font-bold">85</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center pr-6 font-black text-primary">-</TableCell>
                                    </TableRow>
                                    <TableRow className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-medium">Filipino 7</TableCell>
                                        <TableCell className="text-center font-bold">88</TableCell>
                                        <TableCell className="text-center font-bold">89</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center text-muted-foreground">-</TableCell>
                                        <TableCell className="text-center pr-6 font-black text-primary">-</TableCell>
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
