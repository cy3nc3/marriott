import { Head } from '@inertiajs/react';
import { ShieldCheck, Check, X, Lock } from 'lucide-react';
import React from 'react';
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
        title: 'Permissions',
        href: '/super-admin/permissions',
    },
];

interface Props {
    permissions: Record<string, Record<string, string[]>>;
    roles: { value: string; label: string }[];
}

export default function Permissions({ permissions, roles }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Permissions" />
            <div className="flex flex-col gap-6">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex flex-col gap-4 border-b p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="size-4 text-muted-foreground" />
                                <span className="text-sm text-muted-foreground">
                                    Read-Only Oversight
                                </span>
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[250px] pl-6">
                                            Module / Feature
                                        </TableHead>
                                        {roles.map((role) => (
                                            <TableHead
                                                key={role.value}
                                                className="min-w-[100px] text-center"
                                            >
                                                {role.label}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {Object.entries(permissions).map(
                                        ([category, features]) => (
                                            <React.Fragment key={category}>
                                                <TableRow className="bg-muted/10">
                                                    <TableCell
                                                        colSpan={
                                                            roles.length + 1
                                                        }
                                                        className="py-2 pl-6 text-xs text-muted-foreground"
                                                    >
                                                        {category}
                                                    </TableCell>
                                                </TableRow>
                                                {Object.entries(features).map(
                                                    ([
                                                        feature,
                                                        allowedRoles,
                                                    ]) => (
                                                        <TableRow key={feature}>
                                                            <TableCell className="pl-8 text-sm text-muted-foreground">
                                                                {feature}
                                                            </TableCell>
                                                            {roles.map(
                                                                (role) => {
                                                                    const isAllowed =
                                                                        allowedRoles.includes(
                                                                            role.value,
                                                                        );
                                                                    return (
                                                                        <TableCell
                                                                            key={
                                                                                role.value
                                                                            }
                                                                            className="text-center"
                                                                        >
                                                                            <div className="flex justify-center">
                                                                                {isAllowed ? (
                                                                                    <Check className="size-4 text-emerald-600" />
                                                                                ) : (
                                                                                    <X className="size-4 text-muted-foreground" />
                                                                                )}
                                                                            </div>
                                                                        </TableCell>
                                                                    );
                                                                },
                                                            )}
                                                        </TableRow>
                                                    ),
                                                )}
                                            </React.Fragment>
                                        ),
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-center gap-4">
                        <Lock className="size-5 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            This matrix represents the hardcoded security gates
                            of the application. Modification of these
                            permissions requires developer-level system policy
                            updates to ensure structural integrity and data
                            safety.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
