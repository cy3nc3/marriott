import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { 
    Search,
    History,
    Eye,
    ShieldAlert
} from 'lucide-react';
import { useState } from 'react';
import { format } from "date-fns";
import { Badge } from "@/components/ui/badge"
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
} from '@/components/ui/card';
import { Input } from "@/components/ui/input"
import { DatePicker } from "@/components/ui/date-picker";
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
        date?: string;
    };
}

export default function AuditLogs({ logs, filters }: Props) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [date, setDate] = useState<Date | undefined>(filters.date ? new Date(filters.date) : undefined);

    const handleSearch = (val: string) => {
        setSearchQuery(val);
        updateParams(val, date);
    };

    const handleDateChange = (newDate: Date | undefined) => {
        setDate(newDate);
        updateParams(searchQuery, newDate);
    };

    const updateParams = (search: string, dateParam: Date | undefined) => {
        router.get('/super-admin/audit-logs', { 
            search: search || undefined,
            date: dateParam ? format(dateParam, 'yyyy-MM-dd') : undefined
        }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />
            <div className="flex flex-col gap-6">
                <Card className="flex flex-col pt-0">
                    <CardHeader className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b">
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search logs (action, user, model)..."
                                    className="pl-9 h-9 w-[350px] text-xs font-bold"
                                    value={searchQuery}
                                    onChange={(e) => handleSearch(e.target.value)}
                                />
                            </div>
                            <DatePicker 
                                date={date} 
                                setDate={handleDateChange} 
                                className="h-9 w-[180px] text-xs"
                                placeholder="Filter by date"
                            />
                            {date && (
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    onClick={() => handleDateChange(undefined)}
                                    className="h-9 text-xs"
                                >
                                    Clear
                                </Button>
                            )}
                        </div>
                        
                        <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-primary/5 border border-primary/10">
                            <ShieldAlert className="size-3.5 text-primary" />
                            <span className="text-[10px] font-black uppercase tracking-wider text-primary">Security Monitoring Active</span>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="pl-6 font-black text-[10px] uppercase">Timestamp</TableHead>
                                    <TableHead className="font-black text-[10px] uppercase">User</TableHead>
                                    <TableHead className="font-black text-[10px] uppercase">Action</TableHead>
                                    <TableHead className="font-black text-[10px] uppercase">Target</TableHead>
                                    <TableHead className="font-black text-[10px] uppercase">IP Address</TableHead>
                                    <TableHead className="text-right pr-6 font-black text-[10px] uppercase">Details</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log) => (
                                    <TableRow key={log.id} className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-mono text-[10px] text-muted-foreground">
                                            {new Date(log.created_at).toLocaleString()}
                                        </TableCell>
                                        <TableCell className="font-bold text-xs">{log.user?.name || 'System'}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="font-bold text-[10px] uppercase bg-muted/50">
                                                {log.action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-xs italic text-muted-foreground">
                                            {log.model_type.split('').pop()} #{log.model_id}
                                        </TableCell>
                                        <TableCell className="font-mono text-[10px]">{log.ip_address}</TableCell>
                                        <TableCell className="text-right pr-6">
                                            <Button variant="ghost" size="icon" className="size-8">
                                                <Eye className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {logs.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-32 text-center">
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground/50">
                                                <History className="size-8 opacity-20" />
                                                <p className="text-xs font-medium italic">No security logs recorded</p>
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
