import { Head, useForm, router } from '@inertiajs/react';
import {
    Megaphone,
    Plus,
    Trash2,
    Users,
    Bell,
    Pencil,
    Search,
    XCircle,
    Paperclip,
    UploadCloud,
    X,
} from 'lucide-react';
import { type ChangeEvent, useRef, useState } from 'react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { DatePicker } from '@/components/ui/date-picker';
import { RolesCombobox } from '@/components/ui/roles-combobox';
import AppLayout from '@/layouts/app-layout';
import announcementsRoutes from '@/routes/announcements';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/announcements',
    },
];

interface Announcement {
    id: number;
    title: string;
    content: string;
    target_roles: string[] | null;
    is_active: boolean;
    created_at: string;
    expires_at?: string;
    user: { name: string };
    attachments: AnnouncementAttachment[];
}

interface AnnouncementAttachment {
    id: number;
    original_name: string;
    mime_type: string | null;
    file_size: number;
}

interface Props {
    announcements: {
        data: Announcement[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    roles: { value: string; label: string }[];
    filters: {
        search?: string;
        role?: string;
    };
}

export default function Announcements({
    announcements,
    roles,
    filters,
}: Props) {
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<Announcement | null>(null);
    const [existingAttachments, setExistingAttachments] = useState<
        AnnouncementAttachment[]
    >([]);
    const attachmentInputRef = useRef<HTMLInputElement | null>(null);

    // Filters
    const [filterRole, setFilterRole] = useState<string>(filters.role || 'all');
    const [searchQuery, setSearchQuery] = useState<string>(
        filters.search || '',
    );

    const form = useForm({
        title: '',
        content: '',
        target_roles: [] as string[],
        expires_at: '',
        attachments: [] as File[],
        removed_attachment_ids: [] as number[],
    });

    const handleOpenDialog = (item?: Announcement) => {
        if (item) {
            setEditingItem(item);
            setExistingAttachments(item.attachments || []);
            form.setData({
                title: item.title,
                content: item.content,
                target_roles: item.target_roles || [],
                expires_at: item.expires_at || '',
                attachments: [],
                removed_attachment_ids: [],
            });
        } else {
            setEditingItem(null);
            setExistingAttachments([]);
            form.reset();
            form.setData('attachments', []);
            form.setData('removed_attachment_ids', []);
        }
        setIsAddOpen(true);
    };

    const handleSubmit = () => {
        if (editingItem) {
            form.put(announcementsRoutes.update.url(editingItem.id), {
                forceFormData: true,
                onSuccess: () => {
                    setIsAddOpen(false);
                    form.reset();
                    setExistingAttachments([]);
                    setEditingItem(null);
                },
            });
        } else {
            form.post(announcementsRoutes.store.url(), {
                forceFormData: true,
                onSuccess: () => {
                    setIsAddOpen(false);
                    form.reset();
                    setExistingAttachments([]);
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this announcement?')) {
            router.delete(announcementsRoutes.destroy.url(id));
        }
    };

    const handleDateChange = (date?: Date) => {
        form.setData('expires_at', date ? format(date, 'yyyy-MM-dd') : '');
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

    const applyFilters = (search: string, role: string) => {
        router.get(
            announcementsRoutes.index.url(),
            {
                search: search || undefined,
                role: role === 'all' ? undefined : role,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
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
        setFilterRole('all');
        setSearchQuery('');
        applyFilters('', 'all');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Announcements" />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap items-center gap-3 sm:flex-nowrap">
                        <div className="relative w-full sm:w-72">
                            <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search announcements..."
                                className="pl-9"
                                value={searchQuery}
                                onChange={(e) => handleSearch(e.target.value)}
                            />
                        </div>

                        <Select
                            value={filterRole}
                            onValueChange={handleRoleFilter}
                        >
                            <SelectTrigger className="w-[160px]">
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

                        {(searchQuery || filterRole !== 'all') && (
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={resetFilters}
                            >
                                <XCircle className="size-4" />
                            </Button>
                        )}
                    </div>

                    <Button onClick={() => handleOpenDialog()}>
                        <Plus className="size-4" />
                        New Announcement
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4">
                    {announcements.data.map((item) => (
                        <Card key={item.id} className="gap-2">
                            <CardContent className="space-y-4 p-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-lg border p-2">
                                            <Bell className="size-4" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold">
                                                {item.title}
                                            </p>
                                            <div className="flex items-center gap-2">
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(
                                                        item.created_at,
                                                    ).toLocaleDateString()}
                                                </p>
                                                {item.target_roles &&
                                                    item.target_roles.length >
                                                        0 && (
                                                        <div className="flex items-center gap-1.5 border-l pl-2">
                                                            <Users className="size-3 text-muted-foreground" />
                                                            <div className="flex flex-wrap gap-1">
                                                                {item.target_roles.map(
                                                                    (role) => (
                                                                        <Badge
                                                                            key={
                                                                                role
                                                                            }
                                                                            variant="outline"
                                                                        >
                                                                            {roles.find(
                                                                                (
                                                                                    r,
                                                                                ) =>
                                                                                    r.value ===
                                                                                    role,
                                                                            )
                                                                                ?.label ||
                                                                                role}
                                                                        </Badge>
                                                                    ),
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {item.target_roles &&
                                        item.target_roles.length > 0 ? (
                                            <Badge variant="outline">
                                                {item.target_roles.length}{' '}
                                                Target
                                                {item.target_roles.length === 1
                                                    ? ''
                                                    : 's'}
                                            </Badge>
                                        ) : (
                                            <Badge variant="outline">
                                                All Roles
                                            </Badge>
                                        )}
                                        {item.attachments.length > 0 && (
                                            <Badge variant="outline">
                                                <Paperclip className="size-3" />
                                                {item.attachments.length}{' '}
                                                Attachment
                                                {item.attachments.length === 1
                                                    ? ''
                                                    : 's'}
                                            </Badge>
                                        )}
                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleOpenDialog(item)
                                                }
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="icon"
                                                onClick={() =>
                                                    handleDelete(item.id)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                    {item.content}
                                </p>
                            </CardContent>
                        </Card>
                    ))}

                    {announcements.data.length === 0 && (
                        <Card className="gap-2">
                            <CardContent className="flex flex-col items-center justify-center gap-2 py-16 text-center text-muted-foreground">
                                <Megaphone className="size-10 opacity-40" />
                                <p className="text-sm font-medium">
                                    No broadcasts found.
                                </p>
                                <p className="text-sm">
                                    Adjust your filters or post a new message to
                                    inform the school community.
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
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

            <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                <DialogContent className="max-h-[85vh] overflow-hidden sm:max-w-[450px]">
                    <DialogHeader>
                        <DialogTitle>
                            {editingItem ? 'Edit' : 'New'} Broadcast
                        </DialogTitle>
                        <DialogDescription>
                            Define the message content and visibility
                            parameters.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid max-h-[60vh] gap-4 overflow-y-auto py-4 pr-1">
                        <div className="grid gap-2">
                            <Label>Broadcast Title</Label>
                            <Input
                                placeholder="e.g., System Maintenance Notice"
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>Message Content</Label>
                            <Textarea
                                placeholder="Describe the announcement in detail..."
                                className="min-h-[120px]"
                                value={form.data.content}
                                onChange={(e) =>
                                    form.setData('content', e.target.value)
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label>Target Audience (Roles)</Label>
                            <RolesCombobox
                                options={roles}
                                selected={form.data.target_roles}
                                onChange={(selected) =>
                                    form.setData('target_roles', selected)
                                }
                                placeholder="Target all roles..."
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
                                {existingAttachments.length === 0 &&
                                    form.data.attachments.length === 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            No attachments added.
                                        </p>
                                    )}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label>Auto-Expiry Date</Label>
                            <DatePicker
                                date={
                                    form.data.expires_at
                                        ? new Date(form.data.expires_at)
                                        : undefined
                                }
                                setDate={handleDateChange}
                                className="w-full"
                                placeholder="No Expiry"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsAddOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleSubmit}
                            disabled={form.processing}
                        >
                            {editingItem ? 'Update' : 'Launch'} Broadcast
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
