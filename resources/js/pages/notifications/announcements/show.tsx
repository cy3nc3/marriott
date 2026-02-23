import { Head } from '@inertiajs/react';
import {
    CalendarClock,
    Download,
    FileText,
    ImageIcon,
    Paperclip,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface AnnouncementAttachment {
    id: number;
    original_name: string;
    mime_type: string | null;
    file_size: number;
    is_image: boolean;
    view_url: string;
    download_url: string;
}

interface Props {
    announcement: {
        id: number;
        title: string;
        content: string;
        created_at: string | null;
        expires_at: string | null;
        attachments: AnnouncementAttachment[];
    };
}

export default function AnnouncementShow({ announcement }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Announcement',
            href: `/notifications/announcements/${announcement.id}`,
        },
    ];

    const formatDateTime = (value: string | null) => {
        if (!value) {
            return '--';
        }

        return new Date(value).toLocaleString();
    };

    const formatFileSize = (size: number) => {
        if (size < 1024) {
            return `${size} B`;
        }

        if (size < 1024 * 1024) {
            return `${(size / 1024).toFixed(1)} KB`;
        }

        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={announcement.title} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="space-y-2">
                            <CardTitle>{announcement.title}</CardTitle>
                            <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground sm:text-sm">
                                <span className="inline-flex items-center gap-1.5">
                                    <CalendarClock className="size-3.5" />
                                    {formatDateTime(announcement.created_at)}
                                </span>
                                {announcement.expires_at && (
                                    <span className="inline-flex items-center gap-1.5">
                                        Expires {formatDateTime(announcement.expires_at)}
                                    </span>
                                )}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6 p-6">
                        <div className="rounded-md border p-4 text-sm leading-6 whitespace-pre-wrap">
                            {announcement.content}
                        </div>

                        {announcement.attachments.length > 0 && (
                            <div className="space-y-3">
                                <div className="flex items-center gap-2 text-sm font-semibold">
                                    <Paperclip className="size-4" />
                                    Attachments
                                </div>

                                <div className="grid gap-3 lg:grid-cols-2">
                                    {announcement.attachments.map((attachment) => (
                                        <div
                                            key={attachment.id}
                                            className="rounded-md border p-3"
                                        >
                                            <div className="mb-3 flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {attachment.original_name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {attachment.mime_type ??
                                                            'File'}{' '}
                                                        •{' '}
                                                        {formatFileSize(
                                                            attachment.file_size,
                                                        )}
                                                    </p>
                                                </div>

                                                <Button asChild size="sm" variant="outline">
                                                    <a href={attachment.download_url}>
                                                        <Download className="size-3.5" />
                                                        Download
                                                    </a>
                                                </Button>
                                            </div>

                                            {attachment.is_image ? (
                                                <a
                                                    href={attachment.view_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="block overflow-hidden rounded-md border"
                                                >
                                                    <img
                                                        src={attachment.view_url}
                                                        alt={attachment.original_name}
                                                        className="h-44 w-full object-cover"
                                                    />
                                                </a>
                                            ) : (
                                                <a
                                                    href={attachment.view_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex items-center gap-2 text-xs text-muted-foreground hover:text-foreground"
                                                >
                                                    <FileText className="size-3.5" />
                                                    Open file preview
                                                </a>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {announcement.attachments.length === 0 && (
                            <div className="rounded-md border p-4 text-sm text-muted-foreground">
                                <div className="inline-flex items-center gap-2">
                                    <ImageIcon className="size-4" />
                                    No attachments for this announcement.
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
