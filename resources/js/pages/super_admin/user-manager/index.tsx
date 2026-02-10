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
        title: 'User Manager',
        href: '/super-admin/user-manager',
    },
];

export default function UserManager() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Manager" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Users: </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Add School Year</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead className="text-center">
                                        Email
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Role
                                    </TableHead>
                                    {/* <TableHead className="text-center">
                                        Permissions
                                    </TableHead> */}
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Jade
                                    </TableCell>
                                    <TableCell className="text-center">
                                        jade@marriott.edu
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Super Admin
                                    </TableCell>
                                    {/* <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell> */}
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Edson
                                    </TableCell>
                                    <TableCell className="text-center">
                                        edson@marriott.edu
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Admin
                                    </TableCell>
                                    {/* <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell> */}
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Francis
                                    </TableCell>
                                    <TableCell className="text-center">
                                        francis@marriott.edu
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Registrar
                                    </TableCell>
                                    {/* <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell> */}
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Glezyl
                                    </TableCell>
                                    <TableCell className="text-center">
                                        glezyl@marriott.edu
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Finance
                                    </TableCell>
                                    {/* <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell> */}
                                    <TableCell className="text-right">
                                        Actions Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        Laurence
                                    </TableCell>
                                    <TableCell className="text-center">
                                        laurence@marriott.edu
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Teacher
                                    </TableCell>
                                    {/* <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell> */}
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
