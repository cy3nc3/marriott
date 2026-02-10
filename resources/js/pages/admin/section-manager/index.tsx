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
        title: 'Section Manager',
        href: '/admin/section-manager',
    },
];

export default function SectionManager() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Section Manager" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Sections: </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add Section</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Section Name</TableHead>
                                    <TableHead className="text-center">
                                        Grade Level
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Adviser
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Capacity
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Aldous
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 7
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Mr. Godalle
                                    </TableCell>
                                    <TableCell className="text-center">
                                        20 Students
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Lancelot
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 8
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Mr. Godalle
                                    </TableCell>
                                    <TableCell className="text-center">
                                        20 Students
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Laayla
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 9
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Mr. Godalle
                                    </TableCell>
                                    <TableCell className="text-center">
                                        20 Students
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Gusion
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 10
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Mr. Godalle
                                    </TableCell>
                                    <TableCell className="text-center">
                                        20 Students
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
