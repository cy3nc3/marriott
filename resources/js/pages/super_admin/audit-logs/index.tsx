import { Head, router } from '@inertiajs/react';
import { Search, History, Eye, ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { DateRangePicker } from '@/components/ui/date-picker';
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
import type { DateRange } from 'react-day-picker';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Logs',
        href: '/super-admin/audit-logs',
    },
];

interface Log {
    id: number;
    action: string;
    model_type: string | null;
    model_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    user: { name: string } | null;
    ip_address: string;
    user_agent: string | null;
    created_at: string;
}

interface Props {
    logs: {
        data: Log[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
    };
    filters: {
        search?: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function AuditLogs({ logs, filters }: Props) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [dateRange, setDateRange] = useState<DateRange | undefined>({
        from: filters.date_from ? new Date(filters.date_from) : undefined,
        to: filters.date_to ? new Date(filters.date_to) : undefined,
    });
    const [selectedLog, setSelectedLog] = useState<Log | null>(null);
    const formatValue = (value: unknown) => {
        if (value === null || value === undefined) return '--';
        if (typeof value === 'string' || typeof value === 'number') {
            return String(value);
        }

        return JSON.stringify(value);
    };

    const targetLabel = (log: Log) => {
        const modelName = log.model_type?.split('\\').pop() || 'System';
        const modelId = log.model_id ? ` #${log.model_id}` : '';

        return `${modelName}${modelId}`;
    };

    const handleSearch = (val: string) => {
        setSearchQuery(val);
        updateParams(val, dateRange);
    };

    const handleDateChange = (newRange: DateRange | undefined) => {
        setDateRange(newRange);
        updateParams(searchQuery, newRange);
    };

    const updateParams = (search: string, dateParam: DateRange | undefined) => {
        router.get(
            '/super-admin/audit-logs',
            {
                search: search || undefined,
                date_from: dateParam?.from
                    ? format(dateParam.from, 'yyyy-MM-dd')
                    : undefined,
                date_to: dateParam?.to
                    ? format(dateParam.to, 'yyyy-MM-dd')
                    : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />
            <div className="flex flex-col gap-6">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex flex-col gap-4 border-b p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-wrap items-center gap-3 sm:flex-nowrap">
                                <div className="relative w-full sm:w-72">
                                    <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search logs (action, user, model)..."
                                        className="pl-9"
                                        value={searchQuery}
                                        onChange={(e) =>
                                            handleSearch(e.target.value)
                                        }
                                    />
                                </div>
                                <DateRangePicker
                                    dateRange={dateRange}
                                    setDateRange={handleDateChange}
                                    className="w-[260px]"
                                    placeholder="Filter by date range"
                                />
                                {dateRange?.from && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            handleDateChange(undefined)
                                        }
                                    >
                                        Clear
                                    </Button>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <ShieldAlert className="size-4 text-muted-foreground" />
                                <span className="text-sm text-muted-foreground">
                                    Security Monitoring Active
                                </span>
                            </div>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Timestamp
                                    </TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Target</TableHead>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Details
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="pl-6 font-mono text-xs text-muted-foreground">
                                            {new Date(
                                                log.created_at,
                                            ).toLocaleString()}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {log.user?.name || 'System'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {log.action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-xs text-muted-foreground italic">
                                            {targetLabel(log)}
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">
                                            {log.ip_address}
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    setSelectedLog(log)
                                                }
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
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                                <History className="size-8 opacity-40" />
                                                <p className="text-sm">
                                                    No security logs recorded.
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                        {logs.links?.length > 3 && (
                            <div className="flex flex-wrap items-center gap-2 border-t p-4">
                                {logs.links.map((link, index) => {
                                    let label = link.label;
                                    if (label.includes('Previous')) {
                                        label = 'Previous';
                                    } else if (label.includes('Next')) {
                                        label = 'Next';
                                    }
                                    label = label
                                        .replace(/&[^;]+;/g, '')
                                        .trim();

                                    return (
                                        <Button
                                            key={`${link.label}-${index}`}
                                            variant="outline"
                                            size="sm"
                                            disabled={!link.url || link.active}
                                            onClick={() => {
                                                if (link.url) {
                                                    router.get(
                                                        link.url,
                                                        {},
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            {label}
                                        </Button>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={Boolean(selectedLog)}
                onOpenChange={() => setSelectedLog(null)}
            >
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>Audit Log Details</DialogTitle>
                        <DialogDescription>
                            Review the action metadata captured in this log.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedLog && (
                        <div className="grid gap-4 py-2">
                            <div className="grid gap-1">
                                <p className="text-xs text-muted-foreground">
                                    Timestamp
                                </p>
                                <p className="text-sm font-medium">
                                    {new Date(
                                        selectedLog.created_at,
                                    ).toLocaleString()}
                                </p>
                            </div>
                            <div className="grid gap-1">
                                <p className="text-xs text-muted-foreground">
                                    User
                                </p>
                                <p className="text-sm font-medium">
                                    {selectedLog.user?.name || 'System'}
                                </p>
                            </div>
                            <div className="grid gap-1">
                                <p className="text-xs text-muted-foreground">
                                    Action
                                </p>
                                <Badge variant="outline">
                                    {selectedLog.action}
                                </Badge>
                            </div>
                            <div className="grid gap-1">
                                <p className="text-xs text-muted-foreground">
                                    Target
                                </p>
                                <p className="text-sm font-medium">
                                    {targetLabel(selectedLog)}
                                </p>
                            </div>
                            <div className="grid gap-1">
                                <p className="text-xs text-muted-foreground">
                                    IP Address
                                </p>
                                <p className="text-sm font-medium">
                                    {selectedLog.ip_address}
                                </p>
                            </div>
                            <div className="grid gap-1">
                                <p className="text-xs text-muted-foreground">
                                    User Agent
                                </p>
                                <p className="text-sm font-medium">
                                    {selectedLog.user_agent || '--'}
                                </p>
                            </div>
                            <div className="grid gap-2">
                                <p className="text-xs text-muted-foreground">
                                    Changes
                                </p>
                                {Object.keys(selectedLog.old_values || {})
                                    .length === 0 &&
                                Object.keys(selectedLog.new_values || {})
                                    .length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No field changes recorded.
                                    </p>
                                ) : (
                                    <div className="grid gap-2 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <p className="text-xs text-muted-foreground">
                                                Before
                                            </p>
                                            <div className="grid gap-2 rounded-md border p-3">
                                                {Array.from(
                                                    new Set([
                                                        ...Object.keys(
                                                            selectedLog.old_values ||
                                                                {},
                                                        ),
                                                        ...Object.keys(
                                                            selectedLog.new_values ||
                                                                {},
                                                        ),
                                                    ]),
                                                ).map((key) => (
                                                    <div
                                                        key={`old-${key}`}
                                                        className="grid gap-1"
                                                    >
                                                        <p className="text-xs text-muted-foreground">
                                                            {key}
                                                        </p>
                                                        <p className="text-sm font-medium">
                                                            {formatValue(
                                                                selectedLog
                                                                    .old_values?.[
                                                                    key
                                                                ],
                                                            )}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <p className="text-xs text-muted-foreground">
                                                After
                                            </p>
                                            <div className="grid gap-2 rounded-md border p-3">
                                                {Array.from(
                                                    new Set([
                                                        ...Object.keys(
                                                            selectedLog.old_values ||
                                                                {},
                                                        ),
                                                        ...Object.keys(
                                                            selectedLog.new_values ||
                                                                {},
                                                        ),
                                                    ]),
                                                ).map((key) => (
                                                    <div
                                                        key={`new-${key}`}
                                                        className="grid gap-1"
                                                    >
                                                        <p className="text-xs text-muted-foreground">
                                                            {key}
                                                        </p>
                                                        <p className="text-sm font-medium">
                                                            {formatValue(
                                                                selectedLog
                                                                    .new_values?.[
                                                                    key
                                                                ],
                                                            )}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                    <DialogFooter>
                        <Button onClick={() => setSelectedLog(null)}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
