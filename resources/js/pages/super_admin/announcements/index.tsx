import { Head, useForm, router } from '@inertiajs/react';
import {
    Megaphone,
    Plus,
    Trash2,
    Users,
    Bell,
    Pencil,
    Filter,
    Search,
    XCircle,
} from 'lucide-react';
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
import type { BreadcrumbItem } from '@/types';
import super_admin from '@/routes/super_admin';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/super-admin/announcements',
    },
];

interface Announcement {
    id: number;
    title: string;
    content: string;
    priority: string;
    target_roles: string[] | null;
    is_active: boolean;
    created_at: string;
    expires_at?: string;
    user: { name: string };
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
        priority?: string;
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

    // Filters
    const [filterPriority, setFilterPriority] = useState<string>(
        filters.priority || 'all',
    );
    const [filterRole, setFilterRole] = useState<string>(filters.role || 'all');
    const [searchQuery, setSearchQuery] = useState<string>(
        filters.search || '',
    );

    const form = useForm({
        title: '',
        content: '',
        priority: 'normal',
        target_roles: [] as string[],
        expires_at: '',
    });

    const handleOpenDialog = (item?: Announcement) => {
        if (item) {
            setEditingItem(item);
            form.setData({
                title: item.title,
                content: item.content,
                priority: item.priority,
                target_roles: item.target_roles || [],
                expires_at: item.expires_at || '',
            });
        } else {
            setEditingItem(null);
            form.reset();
        }
        setIsAddOpen(true);
    };

    const handleSubmit = () => {
        if (editingItem) {
            form.put(super_admin.announcements.update.url(editingItem.id), {
                onSuccess: () => {
                    setIsAddOpen(false);
                    form.reset();
                    setEditingItem(null);
                },
            });
        } else {
            form.post(super_admin.announcements.store.url(), {
                onSuccess: () => {
                    setIsAddOpen(false);
                    form.reset();
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this announcement?')) {
            router.delete(super_admin.announcements.destroy.url(id));
        }
    };

    const handleDateChange = (date?: Date) => {
        form.setData('expires_at', date ? format(date, 'yyyy-MM-dd') : '');
    };

    const getPriorityBadge = (priority: string) => {
        return <Badge variant="outline">{priority}</Badge>;
    };

    const applyFilters = (search: string, priority: string, role: string) => {
        router.get(
            super_admin.announcements.url(),
            {
                search: search || undefined,
                priority: priority === 'all' ? undefined : priority,
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
        applyFilters(value, filterPriority, filterRole);
    };

    const handlePriorityFilter = (value: string) => {
        setFilterPriority(value);
        applyFilters(searchQuery, value, filterRole);
    };

    const handleRoleFilter = (value: string) => {
        setFilterRole(value);
        applyFilters(searchQuery, filterPriority, value);
    };

    const resetFilters = () => {
        setFilterPriority('all');
        setFilterRole('all');
        setSearchQuery('');
        applyFilters('', 'all', 'all');
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
                            value={filterPriority}
                            onValueChange={handlePriorityFilter}
                        >
                            <SelectTrigger className="w-[160px]">
                                <div className="flex items-center gap-2">
                                    <Filter className="size-4" />
                                    <SelectValue placeholder="Priority" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Priorities
                                </SelectItem>
                                <SelectItem value="critical">
                                    Critical
                                </SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="normal">Normal</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                            </SelectContent>
                        </Select>

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

                        {(searchQuery ||
                            filterPriority !== 'all' ||
                            filterRole !== 'all') && (
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
                        <Card key={item.id}>
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
                                                    Posted by {item.user.name} -{' '}
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
                                        {getPriorityBadge(item.priority)}
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
                        <Card>
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
                <DialogContent className="sm:max-w-[450px]">
                    <DialogHeader>
                        <DialogTitle>
                            {editingItem ? 'Edit' : 'New'} Broadcast
                        </DialogTitle>
                        <DialogDescription>
                            Define the message content and visibility
                            parameters.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-6 py-4">
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

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Priority Level</Label>
                                <Select
                                    value={form.data.priority}
                                    onValueChange={(val) =>
                                        form.setData('priority', val)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">
                                            Low (Info)
                                        </SelectItem>
                                        <SelectItem value="normal">
                                            Normal
                                        </SelectItem>
                                        <SelectItem value="high">
                                            High (Warning)
                                        </SelectItem>
                                        <SelectItem value="critical">
                                            Critical (Emergency)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
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
