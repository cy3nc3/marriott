import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarClock,
    CheckCircle2,
    Download,
    FileText,
    ImageIcon,
    Paperclip,
} from 'lucide-react';
import { useMemo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
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
        publish_at: string | null;
        event_starts_at: string | null;
        event_ends_at: string | null;
        response_deadline_at: string | null;
        expires_at: string | null;
        type: 'notice' | 'event';
        response_mode: 'none' | 'ack_rsvp';
        is_cancelled: boolean;
        cancelled_at: string | null;
        cancel_reason: string | null;
        viewer_response_status: 'none' | 'ack_only' | 'yes' | 'no' | 'maybe';
        viewer_responded_at: string | null;
        viewer_response_note: string | null;
        requires_action: boolean;
        action_urls: {
            acknowledge: string;
            respond: string;
        };
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

    const responseForm = useForm({
        note: announcement.viewer_response_note ?? '',
    });

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

    const responseLabel = useMemo(() => {
        if (announcement.viewer_response_status === 'none') {
            return 'No response yet';
        }

        if (announcement.viewer_response_status === 'ack_only') {
            return 'Acknowledged';
        }

        if (announcement.viewer_response_status === 'yes') {
            return 'RSVP: Yes';
        }

        if (announcement.viewer_response_status === 'no') {
            return 'RSVP: No';
        }

        return 'RSVP: Maybe';
    }, [announcement.viewer_response_status]);

    const acknowledge = () => {
        router.post(
            announcement.action_urls.acknowledge,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const submitResponse = (response: 'yes' | 'no' | 'maybe') => {
        router.post(
            announcement.action_urls.respond,
            {
                response,
                note: responseForm.data.note || undefined,
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={announcement.title} />

            <div className="flex flex-col gap-4">
                <Card>
                    <CardHeader className="border-b">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <CardTitle>{announcement.title}</CardTitle>
                                <Badge variant="outline">
                                    {announcement.type === 'event'
                                        ? 'Event'
                                        : 'Notice'}
                                </Badge>
                                {announcement.is_cancelled && (
                                    <Badge variant="destructive">Cancelled</Badge>
                                )}
                                {announcement.requires_action && (
                                    <Badge>Action Required</Badge>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span className="inline-flex items-center gap-1.5">
                                    <CalendarClock className="size-3.5" />
                                    Published {formatDateTime(announcement.publish_at ?? announcement.created_at)}
                                </span>
                                {announcement.event_starts_at && (
                                    <span>
                                        Event Start:{' '}
                                        {formatDateTime(announcement.event_starts_at)}
                                    </span>
                                )}
                                {announcement.response_deadline_at && (
                                    <span>
                                        Response Deadline:{' '}
                                        {formatDateTime(
                                            announcement.response_deadline_at,
                                        )}
                                    </span>
                                )}
                                {announcement.expires_at && (
                                    <span>
                                        Expires {formatDateTime(announcement.expires_at)}
                                    </span>
                                )}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-4 p-3 sm:p-4">
                        <div className="rounded-md border p-4 text-sm leading-6 whitespace-pre-wrap">
                            {announcement.content}
                        </div>

                        {announcement.is_cancelled && (
                            <div className="rounded-md border border-destructive/30 p-4 text-sm">
                                <p className="font-medium text-destructive">
                                    This event has been cancelled.
                                </p>
                                {announcement.cancel_reason && (
                                    <p className="mt-2 text-muted-foreground">
                                        Reason: {announcement.cancel_reason}
                                    </p>
                                )}
                            </div>
                        )}

                        {announcement.type === 'event' && (
                            <div className="rounded-md border p-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-sm font-medium">
                                        Response Status: {responseLabel}
                                    </p>
                                    {announcement.viewer_responded_at && (
                                        <Badge variant="outline">
                                            Submitted{' '}
                                            {formatDateTime(
                                                announcement.viewer_responded_at,
                                            )}
                                        </Badge>
                                    )}
                                </div>

                                {announcement.requires_action &&
                                    !announcement.is_cancelled && (
                                        <div className="mt-3 space-y-2.5">
                                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                                <Button
                                                    variant="outline"
                                                    onClick={acknowledge}
                                                    className="w-full"
                                                >
                                                    <CheckCircle2 className="size-4" />
                                                    Acknowledge
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    onClick={() =>
                                                        submitResponse('yes')
                                                    }
                                                    disabled={
                                                        responseForm.processing
                                                    }
                                                    className="w-full"
                                                >
                                                    RSVP Yes
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    onClick={() =>
                                                        submitResponse('no')
                                                    }
                                                    disabled={
                                                        responseForm.processing
                                                    }
                                                    className="w-full"
                                                >
                                                    RSVP No
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    onClick={() =>
                                                        submitResponse('maybe')
                                                    }
                                                    disabled={
                                                        responseForm.processing
                                                    }
                                                    className="w-full"
                                                >
                                                    RSVP Maybe
                                                </Button>
                                            </div>

                                            <div className="space-y-1.5">
                                                <p className="text-xs text-muted-foreground">
                                                    Optional note
                                                </p>
                                                <Textarea
                                                    value={responseForm.data.note}
                                                    onChange={(event) =>
                                                        responseForm.setData(
                                                            'note',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="min-h-[80px]"
                                                    placeholder="Add a note for your response"
                                                />
                                            </div>
                                        </div>
                                    )}

                                {!announcement.requires_action &&
                                    announcement.viewer_response_note && (
                                        <div className="mt-3 rounded-md border p-3 text-sm text-muted-foreground whitespace-pre-wrap">
                                            {announcement.viewer_response_note}
                                        </div>
                                    )}
                            </div>
                        )}

                        {announcement.attachments.length > 0 && (
                            <div className="space-y-2.5">
                                <div className="flex items-center gap-2 text-sm font-semibold">
                                    <Paperclip className="size-4" />
                                    Attachments
                                </div>

                                <div className="grid gap-2.5 lg:grid-cols-2">
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

                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                    className="w-full sm:w-auto"
                                                >
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
