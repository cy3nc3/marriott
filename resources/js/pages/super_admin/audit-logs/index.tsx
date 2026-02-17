import { Head } from '@inertiajs/react';
import { Search, History, Eye, ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Logs',
        href: '/super-admin/audit-logs',
    },
];

interface Log {
    id: number;
    action: string;
    model_type: string;
    model_id: number;
    user: { name: string } | null;
    ip_address: string;
    created_at: string;
}

interface Props {
    logs: {
        data: Log[];
        links: any[];
    };
    filters: {
        search?: string;
    };
}

export default function AuditLogs({ logs, filters }: Props) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const handleSearch = (val: string) => {
        setSearchQuery(val);
        router.get(
            '/super-admin/audit-logs',
            { search: val },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />
            <div className="flex flex-col gap-6">
                <Card className="flex flex-col pt-0">
                    <CardHeader className="flex flex-col items-start justify-between gap-4 border-b md:flex-row md:items-center">
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search logs (action, user, model)..."
                                    className="h-9 w-[350px] pl-9 text-xs font-bold"
                                    value={searchQuery}
                                    onChange={(e) =>
                                        handleSearch(e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-2 rounded-lg border border-primary/10 bg-primary/5 px-3 py-1.5">
                            <ShieldAlert className="size-3.5 text-primary" />
                            <span className="text-[10px] font-black tracking-wider text-primary uppercase">
                                Security Monitoring Active
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="pl-6 text-[10px] font-black uppercase">
                                        Timestamp
                                    </TableHead>
                                    <TableHead className="text-[10px] font-black uppercase">
                                        User
                                    </TableHead>
                                    <TableHead className="text-[10px] font-black uppercase">
                                        Action
                                    </TableHead>
                                    <TableHead className="text-[10px] font-black uppercase">
                                        Target
                                    </TableHead>
                                    <TableHead className="text-[10px] font-black uppercase">
                                        IP Address
                                    </TableHead>
                                    <TableHead className="pr-6 text-right text-[10px] font-black uppercase">
                                        Details
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log) => (
                                    <TableRow
                                        key={log.id}
                                        className="transition-colors hover:bg-muted/30"
                                    >
                                        <TableCell className="pl-6 font-mono text-[10px] text-muted-foreground">
                                            {new Date(
                                                log.created_at,
                                            ).toLocaleString()}
                                        </TableCell>
                                        <TableCell className="text-xs font-bold">
                                            {log.user?.name || 'System'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className="bg-muted/50 text-[10px] font-bold uppercase"
                                            >
                                                {log.action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-xs text-muted-foreground italic">
                                            {log.model_type.split('').pop()} #
                                            {log.model_id}
                                        </TableCell>
                                        <TableCell className="font-mono text-[10px]">
                                            {log.ip_address}
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                            >
                                                <Eye className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {logs.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-32 text-center"
                                        >
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground/50">
                                                <History className="size-8 opacity-20" />
                                                <p className="text-xs font-medium italic">
                                                    No security logs recorded
                                                </p>
                                            </div>
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
