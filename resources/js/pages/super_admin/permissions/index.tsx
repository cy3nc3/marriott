import { Head, useForm } from '@inertiajs/react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import { ShieldCheck, Check, X, Lock, Shield, Save, Loader2 } from 'lucide-react';
import React, { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { update } from '@/routes/super_admin/permissions';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissions',
        href: '/super-admin/permissions',
    },
];

interface Props {
    permissions: Record<string, Record<string, Record<string, number>>>;
    roles: { value: string; label: string }[];
}

export default function Permissions({ permissions, roles }: Props) {
    const { data, setData, post, processing, isDirty } = useForm({
        matrix: permissions,
    });
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const handleLevelChange = (category: string, feature: string, role: string, level: number) => {
        const newMatrix = { ...data.matrix };
        newMatrix[category] = { ...newMatrix[category] };
        newMatrix[category][feature] = { ...newMatrix[category][feature] };
        newMatrix[category][feature][role] = level;
        setData('matrix', newMatrix);
    };

    const submit = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        post(update().url, {
            onSuccess: () => setIsConfirmOpen(false),
        });
    };

    const getLevelIcon = (level: number) => {
        switch (level) {
            case 2:
                return <Check className="size-3.5 text-emerald-600" />;
            case 1:
                return <Shield className="size-3.5 text-blue-600" />;
            default:
                return <X className="size-3.5 text-muted-foreground" />;
        }
    };

    const getLevelColor = (level: number) => {
        switch (level) {
            case 2:
                return 'text-emerald-700 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20';
            case 1:
                return 'text-blue-700 bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20';
            default:
                return 'text-muted-foreground bg-muted/50 border-border/50';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Permissions" />
            <form onSubmit={submit} className="flex flex-col gap-6">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex flex-col gap-4 border-b p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-col gap-1">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="size-4 text-primary" />
                                    <h2 className="text-sm font-semibold">Access Control Matrix</h2>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Configure granular access levels for each system role and feature.
                                </p>
                            </div>
                            <Button 
                                type="button" 
                                onClick={() => setIsConfirmOpen(true)}
                                disabled={processing || !isDirty}
                                className="h-9 gap-2"
                            >
                                {processing ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <Save className="size-4" />
                                )}
                                Confirm & Save Changes
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="w-[280px] border-r bg-muted/5 pl-6">
                                            Feature Module
                                        </TableHead>
                                        {roles.map((role) => (
                                            <TableHead
                                                key={role.value}
                                                className="min-w-[140px] text-center"
                                            >
                                                {role.label}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {Object.entries(data.matrix).map(
                                        ([category, features]) => (
                                            <React.Fragment key={category}>
                                                <TableRow className="bg-muted/10 hover:bg-muted/15 border-y">
                                                    <TableCell
                                                        colSpan={
                                                            roles.length + 1
                                                        }
                                                        className="py-2.5 pl-6 text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                                                    >
                                                        {category}
                                                    </TableCell>
                                                </TableRow>
                                                {Object.entries(features).map(
                                                    ([
                                                        feature,
                                                        roleLevels,
                                                    ]) => (
                                                        <TableRow key={feature} className="hover:bg-muted/5 transition-colors">
                                                            <TableCell className="border-r border-dashed bg-muted/5 pl-8 text-sm font-medium">
                                                                {feature}
                                                            </TableCell>
                                                            {roles.map(
                                                                (role) => {
                                                                    const level = roleLevels[role.value] ?? 0;
                                                                    return (
                                                                        <TableCell
                                                                            key={role.value}
                                                                            className="p-1 text-center"
                                                                        >
                                                                            <div className="mx-auto w-[120px]">
                                                                                    <Select
                                                                                    value={level.toString()}
                                                                                    onValueChange={(val) => handleLevelChange(category, feature, role.value, parseInt(val))}
                                                                                >
                                                                                    <SelectTrigger 
                                                                                        className={cn(
                                                                                            "h-8 w-full border text-[10px] font-semibold transition-all",
                                                                                            getLevelColor(level)
                                                                                        )}
                                                                                    >
                                                                                        <SelectValue>
                                                                                            <span className="flex items-center gap-1.5">
                                                                                                {getLevelIcon(level)}
                                                                                                <span className="uppercase tracking-tight">
                                                                                                    {level === 2 ? 'Full' : level === 1 ? 'Read' : 'None'}
                                                                                                </span>
                                                                                            </span>
                                                                                        </SelectValue>
                                                                                    </SelectTrigger>
                                                                                    <SelectContent position="popper" sideOffset={4}>
                                                                                        <SelectItem value="2">
                                                                                            <div className="flex items-center gap-2">
                                                                                                <Check className="size-3.5 text-emerald-600" />
                                                                                                <span className="text-xs">Full Access</span>
                                                                                            </div>
                                                                                        </SelectItem>
                                                                                        <SelectItem value="1">
                                                                                            <div className="flex items-center gap-2">
                                                                                                <Shield className="size-3.5 text-blue-600" />
                                                                                                <span className="text-xs">Read Only</span>
                                                                                            </div>
                                                                                        </SelectItem>
                                                                                        <SelectItem value="0">
                                                                                            <div className="flex items-center gap-2">
                                                                                                <X className="size-3.5 text-muted-foreground" />
                                                                                                <span className="text-xs">No Access</span>
                                                                                            </div>
                                                                                        </SelectItem>
                                                                                    </SelectContent>
                                                                                </Select>
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

                <Card className="border-dashed bg-muted/20">
                    <CardContent className="flex items-start gap-4 pt-6">
                        <Lock className="mt-0.5 size-5 text-muted-foreground" />
                        <div className="space-y-1">
                            <p className="text-sm font-semibold">Security Policy Information</p>
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                Access levels strictly govern both UI visibility and API route execution.
                                <strong> Full Access</strong> allows modification, creation, and deletion.
                                <strong> Read-Only</strong> allows data viewing but blocks all modification attempts (POST/PATCH/DELETE).
                                <strong> No Access</strong> completely revokes credentials for that specific module.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <ActionConfirmDialog
                open={isConfirmOpen}
                onOpenChange={setIsConfirmOpen}
                title="Save Permissions Matrix"
                description="Are you sure you want to save these permission changes? This will immediately affect access levels for all users across the system."
                confirmLabel="Confirm & Save"
                loading={processing}
                onConfirm={() => submit()}
            />
        </AppLayout>
    );
}
