import { Head } from '@inertiajs/react';
import { Activity, Bell, Database, ShieldCheck, Users } from 'lucide-react';
import { StatCard } from '@/components/dashboard/stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Super Admin Dashboard',
        href: dashboard().url,
    },
];

interface Props {
    metrics: {
        total_users: number;
        staff_users: number;
        active_users: number;
        announcements: number;
        audit_logs_today: number;
        maintenance_mode: boolean;
        parent_portal_enabled: boolean;
        latest_backup_at: string | null;
    };
    role_distribution: {
        role: string;
        label: string;
        count: number;
    }[];
    recent_logs: {
        id: number;
        action: string;
        target: string;
        user: string;
        created_at: string | null;
    }[];
}

export default function Dashboard({
    metrics,
    role_distribution,
    recent_logs,
}: Props) {
    const latestBackupLabel = metrics.latest_backup_at
        ? new Date(metrics.latest_backup_at).toLocaleString()
        : 'No backups yet';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Super Admin Dashboard" />
            <div className="flex flex-col gap-6">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total Users"
                        value={metrics.total_users}
                        description={`${metrics.staff_users} staff accounts`}
                        icon={Users}
                    />
                    <StatCard
                        title="Active Accounts"
                        value={metrics.active_users}
                        description="Users currently enabled"
                        icon={Activity}
                    />
                    <StatCard
                        title="Announcements"
                        value={metrics.announcements}
                        description="Total system broadcasts"
                        icon={Bell}
                    />
                    <StatCard
                        title="Audit Logs Today"
                        value={metrics.audit_logs_today}
                        description="Security events recorded"
                        icon={ShieldCheck}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-7">
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Database className="size-4" />
                                System Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <p className="text-sm">Maintenance Mode</p>
                                <Badge
                                    variant={
                                        metrics.maintenance_mode
                                            ? 'destructive'
                                            : 'outline'
                                    }
                                >
                                    {metrics.maintenance_mode
                                        ? 'Enabled'
                                        : 'Disabled'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <p className="text-sm">Parent Portal</p>
                                <Badge
                                    variant={
                                        metrics.parent_portal_enabled
                                            ? 'outline'
                                            : 'secondary'
                                    }
                                >
                                    {metrics.parent_portal_enabled
                                        ? 'Enabled'
                                        : 'Disabled'}
                                </Badge>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Latest Backup
                                </p>
                                <p className="text-sm font-medium">
                                    {latestBackupLabel}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle className="text-base">
                                User Distribution
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Role
                                        </TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Users
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {role_distribution.map((role) => (
                                        <TableRow key={role.role}>
                                            <TableCell className="pl-6">
                                                {role.label}
                                            </TableCell>
                                            <TableCell className="pr-6 text-right font-medium">
                                                {role.count}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Recent Audit Activity
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Timestamp
                                    </TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead className="pr-6">
                                        Target
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recent_logs.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="pl-6 text-xs text-muted-foreground">
                                            {log.created_at
                                                ? new Date(
                                                      log.created_at,
                                                  ).toLocaleString()
                                                : '--'}
                                        </TableCell>
                                        <TableCell>{log.user}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {log.action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="pr-6 text-sm text-muted-foreground">
                                            {log.target}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {recent_logs.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="h-24 text-center text-sm text-muted-foreground"
                                        >
                                            No activity yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
