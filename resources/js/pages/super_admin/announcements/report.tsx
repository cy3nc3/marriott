import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Search, Users } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import announcementsRoutes from '@/routes/announcements';
import type { BreadcrumbItem } from '@/types';

type Recipient = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    is_read: boolean;
    read_at: string | null;
};

interface Props {
    announcement: {
        id: number;
        title: string;
        publish_at: string | null;
        expires_at: string | null;
    };
    analytics: {
        recipient_count: number;
        read_count: number;
        unread_count: number;
        read_rate: number;
        role_breakdown: {
            role: string;
            label: string;
            recipient_count: number;
            read_count: number;
            unread_count: number;
            read_rate: number;
        }[];
    };
    recipients: {
        data: Recipient[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search?: string | null;
        status?: 'all' | 'read' | 'unread';
    };
}

export default function AnnouncementReport({
    announcement,
    analytics,
    recipients,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState<'all' | 'read' | 'unread'>(
        filters.status ?? 'all',
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Announcements',
            href: announcementsRoutes.index.url(),
        },
        {
            title: 'Read Report',
            href: announcementsRoutes.report.url(announcement.id),
        },
    ];

    const formatDate = (value: string | null) => {
        if (!value) {
            return '--';
        }

        return new Date(value).toLocaleString();
    };

    const applyFilters = (
        nextSearch: string,
        nextStatus: 'all' | 'read' | 'unread',
    ) => {
        router.get(
            announcementsRoutes.report.url(announcement.id),
            {
                search: nextSearch || undefined,
                status: nextStatus === 'all' ? undefined : nextStatus,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        applyFilters(value, status);
    };

    const handleStatusChange = (value: 'all' | 'read' | 'unread') => {
        setStatus(value);
        applyFilters(search, value);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Announcement Report - ${announcement.title}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle>{announcement.title}</CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    Published: {formatDate(announcement.publish_at)}
                                    {'  '}| Expires: {formatDate(announcement.expires_at)}
                                </p>
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={announcementsRoutes.index.url()}>
                                    <ArrowLeft className="size-4" />
                                    Back to Announcements
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-3 p-4 lg:grid-cols-4">
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">Recipients</p>
                            <p className="text-lg font-semibold">
                                {analytics.recipient_count}
                            </p>
                        </div>
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">Read</p>
                            <p className="text-lg font-semibold">{analytics.read_count}</p>
                        </div>
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">Unread</p>
                            <p className="text-lg font-semibold">{analytics.unread_count}</p>
                        </div>
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">Read Rate</p>
                            <p className="text-lg font-semibold">{analytics.read_rate}%</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle className="flex items-center gap-2">
                            <Users className="size-4" />
                            Read Summary by Role
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="text-right">Recipients</TableHead>
                                    <TableHead className="text-right">Read</TableHead>
                                    <TableHead className="text-right">Unread</TableHead>
                                    <TableHead className="text-right">Read Rate</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {analytics.role_breakdown.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            className="py-6 text-center text-muted-foreground"
                                            colSpan={5}
                                        >
                                            No recipients found for this announcement.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    analytics.role_breakdown.map((row) => (
                                        <TableRow key={row.role}>
                                            <TableCell>{row.label}</TableCell>
                                            <TableCell className="text-right">
                                                {row.recipient_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {row.read_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {row.unread_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {row.read_rate}%
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <CardTitle>Recipient Details</CardTitle>
                            <div className="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                                <div className="relative w-full md:w-72">
                                    <Search className="pointer-events-none absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                                    <Input
                                        value={search}
                                        onChange={(event) =>
                                            handleSearch(event.target.value)
                                        }
                                        placeholder="Search name or email"
                                        className="pl-9"
                                    />
                                </div>
                                <Select
                                    value={status}
                                    onValueChange={handleStatusChange}
                                >
                                    <SelectTrigger className="w-full md:w-[160px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All</SelectItem>
                                        <SelectItem value="read">Read</SelectItem>
                                        <SelectItem value="unread">
                                            Unread
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Read At</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recipients.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No recipients found for this filter.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    recipients.data.map((recipient) => (
                                        <TableRow key={recipient.id}>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <p className="text-sm font-medium">
                                                        {recipient.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {recipient.email}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>{recipient.role_label}</TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        recipient.is_read
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {recipient.is_read ? (
                                                        <span className="inline-flex items-center gap-1">
                                                            <CheckCircle2 className="size-3" />
                                                            Read
                                                        </span>
                                                    ) : (
                                                        'Unread'
                                                    )}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(recipient.read_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {recipients.links.length > 3 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-muted-foreground">
                            {recipients.from ?? 0}-{recipients.to ?? 0} out of{' '}
                            {recipients.total}
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            {recipients.links.map((link, index) => {
                                let label = link.label;
                                if (label.includes('Previous')) {
                                    label = 'Previous';
                                } else if (label.includes('Next')) {
                                    label = 'Next';
                                } else {
                                    label = label
                                        .replace(/&[^;]+;/g, '')
                                        .trim();
                                }

                                return (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
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
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
