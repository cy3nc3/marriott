import { Head, Link, router } from '@inertiajs/react';
import { Bell, Check, Search } from 'lucide-react';
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
import AppLayout from '@/layouts/app-layout';
import notificationsAnnouncements from '@/routes/notifications/announcements';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: '/notifications',
    },
];

type InboxItem = {
    id: number;
    title: string;
    content_preview: string;
    created_at: string | null;
    publish_at: string | null;
    expires_at: string | null;
    is_read: boolean;
    show_url: string;
};

interface Props {
    announcements: {
        data: InboxItem[];
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

export default function NotificationInbox({ announcements, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState<'all' | 'read' | 'unread'>(
        filters.status ?? 'all',
    );

    const applyFilters = (
        nextSearch: string,
        nextStatus: 'all' | 'read' | 'unread',
    ) => {
        router.get(
            '/notifications',
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

    const handleStatus = (value: 'all' | 'read' | 'unread') => {
        setStatus(value);
        applyFilters(search, value);
    };

    const markAsRead = (announcementId: number) => {
        router.post(
            notificationsAnnouncements.read.url(announcementId),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const markAllAsRead = () => {
        router.post(
            notificationsAnnouncements.read_all.url(),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const formatDate = (value: string | null) => {
        if (!value) {
            return '-';
        }

        return new Date(value).toLocaleString();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle className="flex items-center gap-2">
                                <Bell className="size-4" />
                                Notification Inbox
                            </CardTitle>
                            <Button variant="outline" onClick={markAllAsRead}>
                                <Check className="size-4" />
                                Mark all read
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4 p-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div className="relative w-full sm:max-w-sm">
                                <Search className="pointer-events-none absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search notifications"
                                    value={search}
                                    onChange={(event) =>
                                        handleSearch(event.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>
                            <Select value={status} onValueChange={handleStatus}>
                                <SelectTrigger className="w-full sm:w-[180px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="unread">
                                        Unread
                                    </SelectItem>
                                    <SelectItem value="read">Read</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-3">
                            {announcements.data.length === 0 ? (
                                <div className="rounded-md border py-10 text-center text-sm text-muted-foreground">
                                    No notifications found.
                                </div>
                            ) : (
                                announcements.data.map((announcement) => (
                                    <div
                                        key={announcement.id}
                                        className={`rounded-md border p-3 ${announcement.is_read ? '' : 'bg-muted/20'}`}
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div className="space-y-1">
                                                <Link
                                                    href={announcement.show_url}
                                                    className="text-sm font-medium hover:underline"
                                                >
                                                    {announcement.title}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {
                                                        announcement.content_preview
                                                    }
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Badge
                                                    variant={
                                                        announcement.is_read
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {announcement.is_read
                                                        ? 'Read'
                                                        : 'Unread'}
                                                </Badge>
                                                {!announcement.is_read && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            markAsRead(
                                                                announcement.id,
                                                            )
                                                        }
                                                    >
                                                        Mark read
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            Published:{' '}
                                            {formatDate(
                                                announcement.publish_at ??
                                                    announcement.created_at,
                                            )}
                                        </p>
                                    </div>
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>

                {announcements.links.length > 3 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-muted-foreground">
                            {announcements.from ?? 0}-{announcements.to ?? 0}{' '}
                            out of {announcements.total}
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            {announcements.links.map((link, index) => {
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
