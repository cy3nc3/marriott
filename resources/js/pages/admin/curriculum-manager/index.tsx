import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Curriculum Manager',
        href: '/admin/curriculum-manager',
    },
];

export default function CurriculumManager() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Curriculum Manager" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Grade 7 Subjects </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add Subject</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subject Code</TableHead>
                                    <TableHead className="text-center">
                                        Descriptive Title
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Hours/Week
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        MATH7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Mathematics 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        SCI7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Science 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        ENG 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        English 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        FIL 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Filipino 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        AP7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Araling Panlipunan 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        TLE7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Technology and Livelihood Education 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        MAPEH7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Music, Arts, Physical Education, and
                                        Health 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        ESP7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Edukasyon sa Pagpapakatao 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        4
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Grade 8 Subjects </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add Subject</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subject Code</TableHead>
                                    <TableHead className="text-center">
                                        Descriptive Title
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Hours/Week
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody></TableBody>
                        </Table>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Grade 9 Subjects </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add Subject</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subject Code</TableHead>
                                    <TableHead className="text-center">
                                        Descriptive Title
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Hours/Week
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody></TableBody>
                        </Table>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Grade 10 Subjects </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add Subject</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subject Code</TableHead>
                                    <TableHead className="text-center">
                                        Descriptive Title
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Hours/Week
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody></TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
