import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import {
    BarChart3,
    Bell,
    CalendarClock,
    Megaphone,
    Paperclip,
    Pencil,
    Plus,
    Trash2,
    UploadCloud,
    Users,
    X,
    XCircle,
} from 'lucide-react';
import { type ChangeEvent, useMemo, useRef, useState } from 'react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ResponsiveFormDialog } from '@/components/ui/responsive-form-dialog';
import { RolesCombobox } from '@/components/ui/roles-combobox';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { UsersCombobox } from '@/components/ui/users-combobox';
import AppLayout from '@/layouts/app-layout';
import { due_reminder_settings } from '@/routes/finance';
import announcementsRoutes from '@/routes/announcements';
import type { BreadcrumbItem, SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/announcements',
    },
];

interface AnnouncementAttachment {
    id: number;
    original_name: string;
    mime_type: string | null;
    file_size: number;
}

interface AnnouncementRecord {
    id: number;
    title: string;
    content: string;
    type: 'notice' | 'event';
    response_mode: 'none' | 'ack_rsvp';
    target_roles: string[] | null;
    target_user_ids: number[] | null;
    is_active: boolean;
    created_at: string;
    publish_at: string | null;
    event_starts_at: string | null;
    event_ends_at: string | null;
    response_deadline_at: string | null;
    expires_at: string | null;
    is_cancelled: boolean;
    cancelled_at: string | null;
    cancel_reason: string | null;
    user: { name: string };
    attachments: AnnouncementAttachment[];
    analytics: {
        recipient_count: number;
        read_count: number;
        unread_count: number;
        read_rate: number;
    };
    report_url: string;
    can_cancel: boolean;
}

interface RoleOption {
    value: string;
    label: string;
}

interface AudienceOption {
    id: number;
    label: string;
    role: string;
    role_label: string;
}

interface Props {
    announcements: {
        data: AnnouncementRecord[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    roles: RoleOption[];
    audience: {
        roles: RoleOption[];
        users: AudienceOption[];
    };
    filters: {
        search?: string;
        role?: string;
    };
    summary: {
        visible_announcements: number;
        scheduled_announcements: number;
        recipients: number;
        unread: number;
    };
}

export default function Announcements({
    announcements,
    roles,
    audience,
    filters,
    summary,
}: Props) {
    const page = usePage<SharedData>();
    const userRole = (page.props.auth.user.role as string) || '';
    const isHandheld = Boolean(page.props.ui?.is_handheld);
    const isFinanceUser = userRole === 'finance';

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<AnnouncementRecord | null>(
        null,
    );
    const [existingAttachments, setExistingAttachments] = useState<
        AnnouncementAttachment[]
    >([]);
    const [filterRole, setFilterRole] = useState<string>(filters.role || 'all');
    const [searchQuery, setSearchQuery] = useState<string>(
        filters.search || '',
    );
    const [idToDelete, setIdToDelete] = useState<number | null>(null);
    const [idToCancel, setIdToCancel] = useState<number | null>(null);
    const attachmentInputRef = useRef<HTMLInputElement | null>(null);

    const searchSuggestions = useMemo(
        () =>
            announcements.data.map((announcement) => ({
                id: announcement.id,
                label: announcement.title,
                value: announcement.title,
                description: announcement.content,
                keywords: announcement.user.name,
            })),
        [announcements.data],
    );

    const form = useForm({
        title: '',
        content: '',
        type: 'notice' as 'notice' | 'event',
        target_roles: [] as string[],
        target_user_ids: [] as number[],
        publish_at: '',
        event_starts_at: '',
        event_ends_at: '',
        response_deadline_at: '',
        expires_at: '',
        attachments: [] as File[],
        removed_attachment_ids: [] as number[],
    });

    const toDateTimeLocalValue = (value?: string | null): string => {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const year = date.getFullYear();
        const month = `${date.getMonth() + 1}`.padStart(2, '0');
        const day = `${date.getDate()}`.padStart(2, '0');
        const hours = `${date.getHours()}`.padStart(2, '0');
        const minutes = `${date.getMinutes()}`.padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };

    const toDateOnlyValue = (value?: string | null): string => {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return format(date, 'yyyy-MM-dd');
    };

    const toDateTimeValueFromDate = (date: Date): string => {
        const year = date.getFullYear();
        const month = `${date.getMonth() + 1}`.padStart(2, '0');
        const day = `${date.getDate()}`.padStart(2, '0');
        const hours = `${date.getHours()}`.padStart(2, '0');
        const minutes = `${date.getMinutes()}`.padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };

    const parseDateTimeValue = (value?: string): Date | undefined => {
        if (!value) {
            return undefined;
        }

        const parsedDate = new Date(value);
        if (Number.isNaN(parsedDate.getTime())) {
            return undefined;
        }

        return parsedDate;
    };

    const openDialog = (item?: AnnouncementRecord) => {
        if (item) {
            setEditingItem(item);
            setExistingAttachments(item.attachments);
            form.setData({
                title: item.title,
                content: item.content,
                type: item.type,
                target_roles: item.target_roles ?? [],
                target_user_ids: item.target_user_ids ?? [],
                publish_at: toDateTimeLocalValue(item.publish_at),
                event_starts_at: toDateTimeLocalValue(item.event_starts_at),
                event_ends_at: toDateTimeLocalValue(item.event_ends_at),
                response_deadline_at: toDateTimeLocalValue(
                    item.response_deadline_at,
                ),
                expires_at: toDateOnlyValue(item.expires_at),
                attachments: [],
                removed_attachment_ids: [],
            });
        } else {
            setEditingItem(null);
            setExistingAttachments([]);
            form.reset();
            form.setData({
                title: '',
                content: '',
                type: 'notice',
                target_roles: [],
                target_user_ids: [],
                publish_at: '',
                event_starts_at: '',
                event_ends_at: '',
                response_deadline_at: '',
                expires_at: '',
                attachments: [],
                removed_attachment_ids: [],
            });
        }

        setIsDialogOpen(true);
    };

    const closeDialog = () => {
        setIsDialogOpen(false);
        setEditingItem(null);
        setExistingAttachments([]);
        form.clearErrors();
    };

    const handleSubmit = () => {
        if (editingItem) {
            form.put(announcementsRoutes.update.url(editingItem.id), {
                forceFormData: true,
                onSuccess: closeDialog,
            });

            return;
        }

        form.post(announcementsRoutes.store.url(), {
            forceFormData: true,
            onSuccess: closeDialog,
        });
    };

    const submitDelete = () => {
        if (!idToDelete) return;
        router.delete(announcementsRoutes.destroy.url(idToDelete), {
            onSuccess: () => setIdToDelete(null),
        });
    };

    const submitCancel = () => {
        if (!idToCancel) return;
        router.post(
            announcementsRoutes.cancel.url(idToCancel),
            {},
            {
                onSuccess: () => setIdToCancel(null),
                preserveScroll: true,
            },
        );
    };

    // Cancellation is now handled via submitCancel after confirmation

    const applyFilters = (search: string, role: string) => {
        router.get(
            announcementsRoutes.index.url(),
            {
                search: search || undefined,
                role: role === 'all' ? undefined : role,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleSearch = (value: string) => {
        setSearchQuery(value);
        applyFilters(value, filterRole);
    };

    const handleRoleFilter = (value: string) => {
        setFilterRole(value);
        applyFilters(searchQuery, value);
    };

    const resetFilters = () => {
        setSearchQuery('');
        setFilterRole('all');
        applyFilters('', 'all');
    };

    const addSelectedAttachments = (event: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []);
        if (files.length === 0) {
            return;
        }

        form.setData('attachments', [...form.data.attachments, ...files]);
        event.target.value = '';
    };

    const removePendingAttachment = (index: number) => {
        form.setData(
            'attachments',
            form.data.attachments.filter((_, fileIndex) => fileIndex !== index),
        );
    };

    const removeExistingAttachment = (attachmentId: number) => {
        setExistingAttachments((previous) =>
            previous.filter((attachment) => attachment.id !== attachmentId),
        );

        const nextRemovedIds = new Set(form.data.removed_attachment_ids);
        nextRemovedIds.add(attachmentId);
        form.setData('removed_attachment_ids', Array.from(nextRemovedIds));
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

    const formatDateTime = (value?: string | null) => {
        if (!value) {
            return '--';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '--';
        }

        return date.toLocaleString();
    };

    const hasFilters = searchQuery !== '' || filterRole !== 'all';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Announcements" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap items-center gap-3 sm:flex-nowrap">
                        <SearchAutocompleteInput
                            placeholder="Search announcements..."
                            wrapperClassName="w-full sm:w-64"
                            value={searchQuery}
                            onValueChange={handleSearch}
                            suggestions={searchSuggestions}
                            showSuggestions={false}
                        />

                        <Select
                            value={filterRole}
                            onValueChange={handleRoleFilter}
                        >
                            <SelectTrigger className="w-[150px]">
                                <div className="flex items-center gap-2">
                                    <Users className="size-4" />
                                    <SelectValue placeholder="Target Role" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Roles</SelectItem>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {hasFilters && (
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={resetFilters}
                            >
                                <XCircle className="size-4" />
                            </Button>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        {isFinanceUser && (
                            <Button variant="outline" asChild>
                                <Link href={due_reminder_settings()}>
                                    Manage Due Reminders
                                </Link>
                            </Button>
                        )}
                        <Button onClick={() => openDialog()}>
                            <Plus className="size-4" />
                            New Announcement
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2 xl:grid-cols-4">
                    <div className="rounded-md border p-3">
                        <p className="text-xs text-muted-foreground">
                            Visible Announcements
                        </p>
                        <p className="text-base font-semibold">
                            {summary.visible_announcements}
                        </p>
                    </div>
                    <div className="rounded-md border p-3">
                        <p className="text-xs text-muted-foreground">
                            Scheduled
                        </p>
                        <p className="text-base font-semibold">
                            {summary.scheduled_announcements}
                        </p>
                    </div>
                    <div className="rounded-md border p-3">
                        <p className="text-xs text-muted-foreground">
                            Recipients
                        </p>
                        <p className="text-base font-semibold">
                            {summary.recipients}
                        </p>
                    </div>
                    <div className="rounded-md border p-3">
                        <p className="text-xs text-muted-foreground">Unread</p>
                        <p className="text-base font-semibold">
                            {summary.unread}
                        </p>
                    </div>
                </div>

                <Card className="gap-2">
                    <CardContent className="p-0">
                        {announcements.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-2 py-16 text-center text-muted-foreground">
                                <Megaphone className="size-10 opacity-40" />
                                <p className="text-sm font-medium">
                                    No announcements found.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {announcements.data.map((item) => (
                                    <div
                                        key={item.id}
                                        className="space-y-2.5 px-4 py-3"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <div className="rounded-md border p-1.5">
                                                        {item.type ===
                                                        'event' ? (
                                                            <CalendarClock className="size-3.5" />
                                                        ) : (
                                                            <Bell className="size-3.5" />
                                                        )}
                                                    </div>
                                                    <p className="text-sm font-semibold">
                                                        {item.title}
                                                    </p>
                                                    <Badge
                                                        variant="outline"
                                                        className="h-5 text-[10px]"
                                                    >
                                                        {item.type === 'event'
                                                            ? 'Event'
                                                            : 'Notice'}
                                                    </Badge>
                                                    {item.is_cancelled ? (
                                                        <Badge
                                                            variant="destructive"
                                                            className="h-5 text-[10px]"
                                                        >
                                                            Cancelled
                                                        </Badge>
                                                    ) : null}
                                                </div>
                                                <p className="line-clamp-1 text-xs text-muted-foreground">
                                                    {item.content}
                                                </p>
                                                <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                    <span>
                                                        Published:{' '}
                                                        {formatDateTime(
                                                            item.publish_at ??
                                                                item.created_at,
                                                        )}
                                                    </span>
                                                    {item.type === 'event' &&
                                                    item.event_starts_at ? (
                                                        <span>
                                                            Starts:{' '}
                                                            {formatDateTime(
                                                                item.event_starts_at,
                                                            )}
                                                        </span>
                                                    ) : null}
                                                    <span>
                                                        Read:{' '}
                                                        {
                                                            item.analytics
                                                                .read_count
                                                        }
                                                        /
                                                        {
                                                            item.analytics
                                                                .recipient_count
                                                        }
                                                    </span>
                                                    {item.attachments.length >
                                                    0 ? (
                                                        <span className="inline-flex items-center gap-1">
                                                            <Paperclip className="size-3" />
                                                            {
                                                                item.attachments
                                                                    .length
                                                            }
                                                        </span>
                                                    ) : null}
                                                </div>
                                            </div>

                                            <div className="flex shrink-0 items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    asChild
                                                >
                                                    <Link
                                                        href={item.report_url}
                                                    >
                                                        <BarChart3 className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() =>
                                                        openDialog(item)
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                {!isHandheld &&
                                                item.can_cancel ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        onClick={() =>
                                                            setIdToCancel(
                                                                item.id,
                                                            )
                                                        }
                                                    >
                                                        <XCircle className="size-4" />
                                                    </Button>
                                                ) : null}
                                                <Button
                                                    variant="destructive"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() =>
                                                        setIdToDelete(item.id)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </div>

                                        {item.can_cancel && isHandheld ? (
                                            <div className="flex justify-end">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setIdToCancel(
                                                            item.id,
                                                        )
                                                    }
                                                >
                                                    Cancel Event
                                                </Button>
                                            </div>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        )}
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

            <ResponsiveFormDialog
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                title={`${editingItem ? 'Edit' : 'New'} Announcement`}
                contentClassName="sm:max-w-[560px]"
                bodyClassName="grid max-h-[70vh] gap-4 overflow-y-auto py-4 pr-1"
                footer={
                    <div className="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button variant="outline" onClick={closeDialog}>
                            Cancel
                        </Button>
                        <Button
                            onClick={handleSubmit}
                            disabled={form.processing}
                        >
                            {editingItem ? 'Update' : 'Publish'}
                        </Button>
                    </div>
                }
            >
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label>Title</Label>
                        <Input
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                        />
                        {form.errors.title && (
                            <p className="text-xs text-destructive">
                                {form.errors.title}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label>Type</Label>
                        <Select
                            value={form.data.type}
                            onValueChange={(value: 'notice' | 'event') => {
                                form.setData('type', value);

                                if (value === 'notice') {
                                    form.setData('event_starts_at', '');
                                    form.setData('event_ends_at', '');
                                    form.setData('response_deadline_at', '');
                                }
                            }}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="notice">Notice</SelectItem>
                                <SelectItem value="event">Event</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label>Content</Label>
                        <Textarea
                            className="min-h-[120px]"
                            value={form.data.content}
                            onChange={(event) =>
                                form.setData('content', event.target.value)
                            }
                        />
                        {form.errors.content && (
                            <p className="text-xs text-destructive">
                                {form.errors.content}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label>Target Roles</Label>
                        <RolesCombobox
                            options={audience.roles}
                            selected={form.data.target_roles}
                            onChange={(selected) =>
                                form.setData('target_roles', selected)
                            }
                            placeholder="All allowed roles"
                        />
                        {form.errors.target_roles && (
                            <p className="text-xs text-destructive">
                                {form.errors.target_roles}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label>Target Users</Label>
                        <UsersCombobox
                            options={audience.users}
                            selected={form.data.target_user_ids}
                            onChange={(selected) =>
                                form.setData('target_user_ids', selected)
                            }
                            placeholder="All matched users"
                        />
                        {form.errors.target_user_ids && (
                            <p className="text-xs text-destructive">
                                {form.errors.target_user_ids}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label>Publish Schedule</Label>
                        <DateTimePicker
                            className="w-full"
                            date={parseDateTimeValue(form.data.publish_at)}
                            setDate={(date) =>
                                form.setData(
                                    'publish_at',
                                    date ? toDateTimeValueFromDate(date) : '',
                                )
                            }
                            placeholder="Publish immediately"
                        />
                    </div>

                    {form.data.type === 'event' && (
                        <>
                            <div className="grid gap-2">
                                <Label>Event Starts At</Label>
                                <DateTimePicker
                                    className="w-full"
                                    date={parseDateTimeValue(
                                        form.data.event_starts_at,
                                    )}
                                    setDate={(date) =>
                                        form.setData(
                                            'event_starts_at',
                                            date
                                                ? toDateTimeValueFromDate(date)
                                                : '',
                                        )
                                    }
                                    placeholder="Set event start"
                                />
                                {form.errors.event_starts_at && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.event_starts_at}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label>Event Ends At (Optional)</Label>
                                <DateTimePicker
                                    className="w-full"
                                    date={parseDateTimeValue(
                                        form.data.event_ends_at,
                                    )}
                                    setDate={(date) =>
                                        form.setData(
                                            'event_ends_at',
                                            date
                                                ? toDateTimeValueFromDate(date)
                                                : '',
                                        )
                                    }
                                    placeholder="Set event end"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Response Deadline (Optional)</Label>
                                <DateTimePicker
                                    className="w-full"
                                    date={parseDateTimeValue(
                                        form.data.response_deadline_at,
                                    )}
                                    setDate={(date) =>
                                        form.setData(
                                            'response_deadline_at',
                                            date
                                                ? toDateTimeValueFromDate(date)
                                                : '',
                                        )
                                    }
                                    placeholder="Set response deadline"
                                />
                            </div>
                        </>
                    )}

                    <div className="grid gap-2">
                        <Label>Auto Expiry Date</Label>
                        <DatePicker
                            className="w-full"
                            date={
                                form.data.expires_at
                                    ? new Date(form.data.expires_at)
                                    : undefined
                            }
                            setDate={(date) =>
                                form.setData(
                                    'expires_at',
                                    date ? format(date, 'yyyy-MM-dd') : '',
                                )
                            }
                            placeholder="No Expiry"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label>Attachments</Label>
                        <input
                            ref={attachmentInputRef}
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                            className="hidden"
                            onChange={addSelectedAttachments}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            className="justify-start"
                            onClick={() => attachmentInputRef.current?.click()}
                        >
                            <UploadCloud className="size-4" />
                            Add Files
                        </Button>

                        <div className="max-h-36 space-y-2 overflow-y-auto pr-1">
                            {existingAttachments.map((attachment) => (
                                <div
                                    key={attachment.id}
                                    className="flex items-center justify-between rounded-md border p-2 text-xs"
                                >
                                    <div className="truncate">
                                        <p className="truncate font-medium">
                                            {attachment.original_name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {formatFileSize(
                                                attachment.file_size,
                                            )}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="h-7 w-7"
                                        onClick={() =>
                                            removeExistingAttachment(
                                                attachment.id,
                                            )
                                        }
                                    >
                                        <X className="size-3.5" />
                                    </Button>
                                </div>
                            ))}

                            {form.data.attachments.map((file, index) => (
                                <div
                                    key={`${file.name}-${index}`}
                                    className="flex items-center justify-between rounded-md border p-2 text-xs"
                                >
                                    <div className="truncate">
                                        <p className="truncate font-medium">
                                            {file.name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {formatFileSize(file.size)}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="h-7 w-7"
                                        onClick={() =>
                                            removePendingAttachment(index)
                                        }
                                    >
                                        <X className="size-3.5" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </ResponsiveFormDialog>

            <ActionConfirmDialog
                open={!!idToDelete}
                onOpenChange={(open) => !open && setIdToDelete(null)}
                title="Delete Announcement"
                description="Are you sure you want to delete this announcement? This action is irreversible and it will be removed for all recipients."
                variant="destructive"
                confirmLabel="Delete"
                onConfirm={submitDelete}
            />

            <ActionConfirmDialog
                open={!!idToCancel}
                onOpenChange={(open) => !open && setIdToCancel(null)}
                title="Cancel Event"
                description="Are you sure you want to cancel this event? A cancellation notice will be sent to all recipients."
                variant="warning"
                confirmLabel="Cancel Event"
                onConfirm={submitCancel}
            />
        </AppLayout>
    );
}
