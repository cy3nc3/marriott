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
        title: 'School Year Manager',
        href: '/super-admin/school-year-manager',
    },
];

export default function SchoolYearManager() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="School Year Manager" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {/* <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="absolute inset-0 flex items-center justify-center">
                        <span className="font-medium text-muted-foreground">
                            School Year Manager
                        </span>
                    </div>
                </div> */}

                <Card>
                    <CardHeader>
                        <CardTitle>Current School Year: </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add School Year</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>School Year</TableHead>
                                    <TableHead className="text-center">
                                        Start Date
                                    </TableHead>
                                    <TableHead className="text-center">
                                        End Date
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Admissions
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        2025-2026
                                    </TableCell>
                                    <TableCell className="text-center">
                                        June 1, 2025
                                    </TableCell>
                                    <TableCell className="text-center">
                                        March 31, 2026
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Elipsis Here
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
