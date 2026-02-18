import { Head } from '@inertiajs/react';
import { 
    ShieldCheck,
    Check,
    X,
    Lock,
    Users
} from 'lucide-react';
import React from 'react';
import { Badge } from "@/components/ui/badge"
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription
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
    roles: { value: string, label: string }[];
}

export default function Permissions({ permissions, roles }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Permissions" />
            <div className="flex flex-col gap-6">
                
                <Card className="shadow-md border-primary/10 overflow-hidden">
                    <CardHeader className="flex flex-row items-center justify-between py-4 border-b bg-muted/10">
                         <div className="flex items-center gap-2">
                             <ShieldCheck className="size-4 text-primary" />
                             <CardTitle className="text-xs font-black uppercase tracking-widest text-primary">Permissions Matrix</CardTitle>
                         </div>
                         <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                            <ShieldCheck className="size-3.5" />
                            <span className="text-[10px] font-black uppercase tracking-tighter">Read-Only Oversight</span>
                        </div>
                    </CardHeader>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="pl-6 font-black text-[10px] uppercase w-[250px]">Module / Feature</TableHead>
                                    {roles.map((role) => (
                                        <TableHead key={role.value} className="text-center font-black text-[10px] uppercase min-w-[100px]">
                                            {role.label}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {Object.entries(permissions).map(([category, features]) => (
                                    <React.Fragment key={category}>
                                        <TableRow className="bg-muted/10">
                                            <TableCell colSpan={roles.length + 1} className="pl-6 py-2">
                                                <span className="text-[9px] font-black uppercase tracking-widest text-primary/60">{category}</span>
                                            </TableCell>
                                        </TableRow>
                                        {Object.entries(features).map(([feature, allowedRoles]) => (
                                            <TableRow key={feature} className="hover:bg-muted/30 transition-colors">
                                                <TableCell className="pl-8 text-xs font-bold text-muted-foreground">
                                                    {feature}
                                                </TableCell>
                                                {roles.map((role) => {
                                                    const isAllowed = allowedRoles.includes(role.value);
                                                    return (
                                                        <TableCell key={role.value} className="text-center">
                                                            <div className="flex justify-center">
                                                                {isAllowed ? (
                                                                    <div className="size-5 rounded-full bg-emerald-500/10 flex items-center justify-center">
                                                                        <Check className="size-3 text-emerald-600 font-black" />
                                                                    </div>
                                                                ) : (
                                                                    <div className="size-5 rounded-full bg-muted flex items-center justify-center opacity-30">
                                                                        <X className="size-3 text-muted-foreground" />
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                    );
                                                })}
                                            </TableRow>
                                        ))}
                                    </React.Fragment>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                <div className="flex items-center gap-4 p-4 rounded-xl bg-primary/[0.03] border border-primary/10">
                    <Lock className="size-5 text-primary opacity-40 shrink-0" />
                    <p className="text-[11px] text-muted-foreground leading-relaxed italic">
                        This matrix represents the hardcoded security gates of the application. 
                        Modification of these permissions requires developer-level system policy updates to ensure 
                        structural integrity and data safety.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}

