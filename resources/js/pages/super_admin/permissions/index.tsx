import { Head } from '@inertiajs/react';
import { ShieldCheck, Check, X, Lock, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
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
import { cn } from '@/lib/utils';
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
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Access{' '}
                            <span className="text-primary not-italic">
                                Control Matrix
                            </span>
                        </h1>
                        <p className="text-sm text-[10px] font-medium tracking-widest text-muted-foreground uppercase">
                            Oversight of system-wide role permissions and module
                            access
                        </p>
                    </div>
                    <div className="flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-emerald-600 dark:text-emerald-400">
                        <ShieldCheck className="size-3.5" />
                        <span className="text-[10px] font-black tracking-tighter uppercase">
                            Read-Only Oversight
                        </span>
                    </div>
                </div>

                <Card className="overflow-hidden border-primary/10 shadow-md">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="w-[250px] pl-6 text-[10px] font-black uppercase">
                                        Module / Feature
                                    </TableHead>
                                    {roles.map((role) => (
                                        <TableHead
                                            key={role.value}
                                            className="min-w-[100px] text-center text-[10px] font-black uppercase"
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
                                                    colSpan={roles.length + 1}
                                                    className="py-2 pl-6"
                                                >
                                                    <span className="text-[9px] font-black tracking-widest text-primary/60 uppercase">
                                                        {category}
                                                    </span>
                                                </TableCell>
                                            </TableRow>
                                            {Object.entries(features).map(
                                                ([feature, allowedRoles]) => (
                                                    <TableRow
                                                        key={feature}
                                                        className="transition-colors hover:bg-muted/30"
                                                    >
                                                        <TableCell className="pl-8 text-xs font-bold text-muted-foreground">
                                                            {feature}
                                                        </TableCell>
                                                        {roles.map((role) => {
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
                                                                            <div className="flex size-5 items-center justify-center rounded-full bg-emerald-500/10">
                                                                                <Check className="size-3 font-black text-emerald-600" />
                                                                            </div>
                                                                        ) : (
                                                                            <div className="flex size-5 items-center justify-center rounded-full bg-muted opacity-30">
                                                                                <X className="size-3 text-muted-foreground" />
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </TableCell>
                                                            );
                                                        })}
                                                    </TableRow>
                                                ),
                                            )}
                                        </React.Fragment>
                                    ),
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                <div className="flex items-center gap-4 rounded-xl border border-primary/10 bg-primary/[0.03] p-4">
                    <Lock className="size-5 shrink-0 text-primary opacity-40" />
                    <p className="text-[11px] leading-relaxed text-muted-foreground italic">
                        This matrix represents the hardcoded security gates of
                        the application. Modification of these permissions
                        requires developer-level system policy updates to ensure
                        structural integrity and data safety.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}

import React from 'react';
