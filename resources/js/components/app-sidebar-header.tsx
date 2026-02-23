import { Breadcrumbs } from '@/components/breadcrumbs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import notificationsAnnouncements from '@/routes/notifications/announcements';
import { Link, router, usePage } from '@inertiajs/react';
import { Bell, Check } from 'lucide-react';
import type { BreadcrumbItem as BreadcrumbItemType, SharedData } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const page = usePage<SharedData>();
    const notifications = page.props.notifications;
    const notificationItems = notifications?.announcements ?? [];
    const unreadNotificationCount = notifications?.unread_count ?? 0;

    const handleMarkAsRead = (announcementId: number) => {
        router.post(notificationsAnnouncements.read.url(announcementId), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleMarkAllAsRead = () => {
        router.post(notificationsAnnouncements.read_all.url(), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const formatNotificationDate = (value: string | null) => {
        if (!value) {
            return '--';
        }

        return new Date(value).toLocaleDateString();
    };

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 bg-transparent px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="relative h-9 w-9"
                    >
                        <Bell className="size-5 opacity-80" />
                        {unreadNotificationCount > 0 && (
                            <span className="absolute top-1 right-1 inline-flex min-w-[1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-none text-white">
                                {unreadNotificationCount > 9
                                    ? '9+'
                                    : unreadNotificationCount}
                            </span>
                        )}
                        <span className="sr-only">Notifications</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-[360px] p-0" align="end">
                    <div className="flex items-center justify-between border-b px-4 py-3">
                        <div className="flex items-center gap-2">
                            <p className="text-sm font-semibold">
                                Announcements
                            </p>
                            <Badge variant="outline">
                                {unreadNotificationCount} unread
                            </Badge>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            disabled={unreadNotificationCount === 0}
                            onClick={handleMarkAllAsRead}
                        >
                            <Check className="size-3.5" />
                            Mark all read
                        </Button>
                    </div>

                    {notificationItems.length === 0 ? (
                        <div className="px-4 py-6 text-center text-sm text-muted-foreground">
                            No announcements right now.
                        </div>
                    ) : (
                        <div className="max-h-[320px] overflow-y-auto">
                            {notificationItems.map((announcement) => (
                                <div
                                    key={announcement.id}
                                    className={`border-b px-4 py-3 last:border-b-0 ${!announcement.is_read ? 'bg-muted/20' : ''}`}
                                >
                                    <Link
                                        href={notificationsAnnouncements.show.url(
                                            announcement.id,
                                        )}
                                        className="block"
                                        prefetch
                                    >
                                        <div className="mb-1">
                                            <p className="text-sm font-medium">
                                                {announcement.title}
                                            </p>
                                        </div>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {announcement.content_preview}
                                        </p>
                                    </Link>
                                    <div className="mt-2 flex items-center justify-between">
                                        <p className="text-xs text-muted-foreground">
                                            {formatNotificationDate(
                                                announcement.created_at,
                                            )}
                                        </p>
                                        {!announcement.is_read && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-7 px-2 text-xs"
                                                onClick={() =>
                                                    handleMarkAsRead(
                                                        announcement.id,
                                                    )
                                                }
                                            >
                                                Mark read
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
        </header>
    );
}
