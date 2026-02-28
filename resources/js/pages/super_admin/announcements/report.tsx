import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
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
import type { BreadcrumbItem, SharedData } from '@/types';

type Recipient = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    is_read: boolean;
    read_at: string | null;
    response_status: 'none' | 'pending' | 'ack_only' | 'yes' | 'no' | 'maybe';
    responded_at: string | null;
};

interface Props {
    announcement: {
        id: number;
        title: string;
        type: 'notice' | 'event';
        response_mode: 'none' | 'ack_rsvp';
        publish_at: string | null;
        event_starts_at: string | null;
        event_ends_at: string | null;
        response_deadline_at: string | null;
        expires_at: string | null;
        is_cancelled: boolean;
        cancelled_at: string | null;
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
        response_summary: {
            recipients: number;
            viewed: number;
            acknowledged: number;
            pending: number;
            yes: number;
            no: number;
            maybe: number;
            ack_only: number;
        };
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
        status?:
            | 'all'
            | 'read'
            | 'unread'
            | 'pending'
            | 'acknowledged'
            | 'yes'
            | 'no'
            | 'maybe';
    };
}

export default function AnnouncementReport({
    announcement,
    analytics,
    recipients,
    filters,
}: Props) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(
        (filters.status ?? 'all') as
            | 'all'
            | 'read'
            | 'unread'
            | 'pending'
            | 'acknowledged'
            | 'yes'
            | 'no'
            | 'maybe',
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
        nextStatus:
            | 'all'
            | 'read'
            | 'unread'
            | 'pending'
            | 'acknowledged'
            | 'yes'
            | 'no'
            | 'maybe',
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

    const handleStatusChange = (
        value:
            | 'all'
            | 'read'
            | 'unread'
            | 'pending'
            | 'acknowledged'
            | 'yes'
            | 'no'
            | 'maybe',
    ) => {
        setStatus(value);
        applyFilters(search, value);
    };

    const responseLabelByStatus = {
        none: 'Not Applicable',
        pending: 'Pending',
        ack_only: 'Acknowledged',
        yes: 'Yes',
        no: 'No',
        maybe: 'Maybe',
    };

    const searchSuggestions = useMemo(
        () =>
            recipients.data.map((recipient) => ({
                id: recipient.id,
                label: recipient.name,
                value: recipient.name,
                description: recipient.email,
                keywords: `${recipient.role_label} ${recipient.response_status}`,
            })),
        [recipients.data],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Announcement Report - ${announcement.title}`} />

            <div className="flex flex-col gap-4">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="space-y-1">
                                <CardTitle>{announcement.title}</CardTitle>
                                <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    <span>
                                        Published:{' '}
                                        {formatDate(announcement.publish_at)}
                                    </span>
                                    {announcement.type === 'event' && (
                                        <>
                                            <span>
                                                Starts:{' '}
                                                {formatDate(
                                                    announcement.event_starts_at,
                                                )}
                                            </span>
                                            <span>
                                                Response Deadline:{' '}
                                                {formatDate(
                                                    announcement.response_deadline_at,
                                                )}
                                            </span>
                                        </>
                                    )}
                                    <span>
                                        Expires:{' '}
                                        {formatDate(announcement.expires_at)}
                                    </span>
                                    <Badge variant="outline">
                                        {announcement.type === 'event'
                                            ? 'Event'
                                            : 'Notice'}
                                    </Badge>
                                    {announcement.is_cancelled && (
                                        <Badge variant="destructive">
                                            Cancelled
                                        </Badge>
                                    )}
                                </div>
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={announcementsRoutes.index.url()}>
                                    <ArrowLeft className="size-4" />
                                    Back to Announcements
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent className="grid grid-cols-2 gap-2 p-3 lg:grid-cols-4">
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">
                                Recipients
                            </p>
                            <p className="text-base font-semibold">
                                {analytics.recipient_count}
                            </p>
                        </div>
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">
                                Viewed (Read)
                            </p>
                            <p className="text-base font-semibold">
                                {analytics.read_count}
                            </p>
                        </div>
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">
                                Unread
                            </p>
                            <p className="text-base font-semibold">
                                {analytics.unread_count}
                            </p>
                        </div>
                        <div className="rounded-md border p-3">
                            <p className="text-xs text-muted-foreground">
                                Read Rate
                            </p>
                            <p className="text-base font-semibold">
                                {analytics.read_rate}%
                            </p>
                        </div>
                    </CardContent>
                </Card>

                {announcement.type === 'event' && (
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Event Response Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-2 p-3 lg:grid-cols-4">
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Acknowledged / Responded
                                </p>
                                <p className="text-base font-semibold">
                                    {analytics.response_summary.acknowledged}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Pending
                                </p>
                                <p className="text-base font-semibold">
                                    {analytics.response_summary.pending}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Yes / No / Maybe
                                </p>
                                <p className="text-base font-semibold">
                                    {analytics.response_summary.yes} /{' '}
                                    {analytics.response_summary.no} /{' '}
                                    {analytics.response_summary.maybe}
                                </p>
                            </div>
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">
                                    Acknowledge Only
                                </p>
                                <p className="text-base font-semibold">
                                    {analytics.response_summary.ack_only}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle className="flex items-center gap-2">
                            <Users className="size-4" />
                            Read Summary by Role
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {isHandheld ? (
                            analytics.role_breakdown.length === 0 ? (
                                <div className="py-6 text-center text-sm text-muted-foreground">
                                    No recipients found.
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {analytics.role_breakdown.map((row) => (
                                        <div
                                            key={row.role}
                                            className="grid grid-cols-2 gap-2 px-4 py-3 text-xs"
                                        >
                                            <p className="col-span-2 text-sm font-medium">
                                                {row.label}
                                            </p>
                                            <p className="text-muted-foreground">
                                                Recipients
                                            </p>
                                            <p className="text-right font-medium">
                                                {row.recipient_count}
                                            </p>
                                            <p className="text-muted-foreground">
                                                Read
                                            </p>
                                            <p className="text-right font-medium">
                                                {row.read_count}
                                            </p>
                                            <p className="text-muted-foreground">
                                                Unread
                                            </p>
                                            <p className="text-right font-medium">
                                                {row.unread_count}
                                            </p>
                                            <p className="text-muted-foreground">
                                                Read Rate
                                            </p>
                                            <p className="text-right font-medium">
                                                {row.read_rate}%
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )
                        ) : (
                            <div className="p-3">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Role</TableHead>
                                            <TableHead className="text-right">
                                                Recipients
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Read
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Unread
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Read Rate
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {analytics.role_breakdown.length ===
                                        0 ? (
                                            <TableRow>
                                                <TableCell
                                                    className="py-6 text-center text-muted-foreground"
                                                    colSpan={5}
                                                >
                                                    No recipients found.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            analytics.role_breakdown.map(
                                                (row) => (
                                                    <TableRow key={row.role}>
                                                        <TableCell>
                                                            {row.label}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {
                                                                row.recipient_count
                                                            }
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
                                                ),
                                            )
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <CardTitle>Recipient Details</CardTitle>
                            <div className="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                                <SearchAutocompleteInput
                                    wrapperClassName="w-full md:w-72"
                                    value={search}
                                    onValueChange={handleSearch}
                                    placeholder="Search name or email"
                                    suggestions={searchSuggestions}
                                    showSuggestions={false}
                                />
                                <Select
                                    value={status}
                                    onValueChange={handleStatusChange}
                                >
                                    <SelectTrigger className="w-full md:w-[220px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All</SelectItem>
                                        <SelectItem value="read">
                                            Read
                                        </SelectItem>
                                        <SelectItem value="unread">
                                            Unread
                                        </SelectItem>
                                        {announcement.type === 'event' && (
                                            <>
                                                <SelectItem value="pending">
                                                    Pending Response
                                                </SelectItem>
                                                <SelectItem value="acknowledged">
                                                    Acknowledged / Responded
                                                </SelectItem>
                                                <SelectItem value="yes">
                                                    RSVP Yes
                                                </SelectItem>
                                                <SelectItem value="no">
                                                    RSVP No
                                                </SelectItem>
                                                <SelectItem value="maybe">
                                                    RSVP Maybe
                                                </SelectItem>
                                            </>
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {isHandheld ? (
                            recipients.data.length === 0 ? (
                                <div className="py-8 text-center text-sm text-muted-foreground">
                                    No recipients found for this filter.
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {recipients.data.map((recipient) => (
                                        <div
                                            key={recipient.id}
                                            className="space-y-1.5 px-4 py-3"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {recipient.name}
                                                    </p>
                                                    <p className="truncate text-xs text-muted-foreground">
                                                        {recipient.email}
                                                    </p>
                                                </div>
                                                <Badge variant="outline">
                                                    {recipient.role_label}
                                                </Badge>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                                <Badge
                                                    variant={
                                                        recipient.is_read
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {recipient.is_read
                                                        ? 'Read'
                                                        : 'Unread'}
                                                </Badge>
                                                <Badge
                                                    variant={
                                                        recipient.response_status ===
                                                        'pending'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {
                                                        responseLabelByStatus[
                                                            recipient
                                                                .response_status
                                                        ]
                                                    }
                                                </Badge>
                                                <span className="text-muted-foreground">
                                                    {formatDate(
                                                        recipient.responded_at,
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )
                        ) : (
                            <div className="p-3">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>View Status</TableHead>
                                            <TableHead>Response</TableHead>
                                            <TableHead>Response Time</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recipients.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="py-8 text-center text-muted-foreground"
                                                >
                                                    No recipients found for this
                                                    filter.
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
                                                                {
                                                                    recipient.email
                                                                }
                                                            </p>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {recipient.role_label}
                                                    </TableCell>
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
                                                        <Badge
                                                            variant={
                                                                recipient.response_status ===
                                                                'pending'
                                                                    ? 'secondary'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {
                                                                responseLabelByStatus[
                                                                    recipient
                                                                        .response_status
                                                                ]
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatDate(
                                                            recipient.responded_at,
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
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
