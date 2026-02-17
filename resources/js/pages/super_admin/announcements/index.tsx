import { Head, useForm, router } from '@inertiajs/react';
import super_admin from '@/routes/super_admin';
import {
    Megaphone,
    Plus,
    Trash2,
    Calendar,
    Users,
    AlertCircle,
    Bell,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardDescription,
    CardTitle,
} from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

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
    is_active: boolean;
    created_at: string;
    user: { name: string };
}

interface Props {
    announcements: Announcement[];
}

export default function Announcements({ announcements }: Props) {
    const [isAddOpen, setIsAddOpen] = useState(false);

    const form = useForm({
        title: '',
        content: '',
        priority: 'normal',
        expires_at: '',
    });

    const handlePost = () => {
        form.post(super_admin.announcements.store.url(), {
            onSuccess: () => {
                setIsAddOpen(false);
                form.reset();
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this announcement?')) {
            router.delete(super_admin.announcements.destroy.url(id));
        }
    };

    const getPriorityBadge = (priority: string) => {
        const styles: Record<string, string> = {
            critical: 'bg-rose-500 text-white border-rose-600',
            high: 'bg-amber-500 text-white border-amber-600',
            normal: 'bg-blue-50 text-blue-700 border-blue-200',
            low: 'bg-muted text-muted-foreground border-border',
        };
        return (
            <Badge
                variant="outline"
                className={cn(
                    'text-[9px] font-black uppercase',
                    styles[priority],
                )}
            >
                {priority}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Announcements" />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            System{' '}
                            <span className="text-primary not-italic">
                                Announcements
                            </span>
                        </h1>
                    </div>
                    <Button
                        className="h-9 gap-2"
                        onClick={() => setIsAddOpen(true)}
                    >
                        <Plus className="size-4" />
                        <span className="text-xs font-bold">
                            New Announcement
                        </span>
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4">
                    {announcements.map((item) => (
                        <Card
                            key={item.id}
                            className="overflow-hidden border-primary/10 shadow-sm"
                        >
                            <CardHeader className="border-b bg-muted/10 py-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={cn(
                                                'rounded-lg p-2',
                                                item.priority === 'critical'
                                                    ? 'bg-rose-500/10 text-rose-600'
                                                    : 'bg-primary/5 text-primary',
                                            )}
                                        >
                                            <Bell className="size-4" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-sm font-bold">
                                                {item.title}
                                            </CardTitle>
                                            <p className="mt-0.5 text-[10px] font-medium text-muted-foreground uppercase">
                                                Posted by {item.user.name} •{' '}
                                                {new Date(
                                                    item.created_at,
                                                ).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {getPriorityBadge(item.priority)}
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                handleDelete(item.id)
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="py-4">
                                <p className="text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                    {item.content}
                                </p>
                            </CardContent>
                        </Card>
                    ))}

                    {announcements.length === 0 && (
                        <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted bg-muted/10 py-20 text-center">
                            <Megaphone className="mb-4 size-12 text-muted-foreground/20" />
                            <h3 className="text-lg font-bold text-muted-foreground">
                                No active announcements
                            </h3>
                            <p className="mt-1 max-w-xs text-sm text-muted-foreground/60 italic">
                                Post a new message to inform the school
                                community of system updates or news.
                            </p>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 font-black italic">
                            <Megaphone className="size-5 text-primary not-italic" />
                            New{' '}
                            <span className="text-primary not-italic">
                                Broadcast
                            </span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Define the message content and visibility
                            parameters.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-6 py-4">
                        <div className="grid gap-2">
                            <Label className="text-[10px] font-black text-muted-foreground uppercase">
                                Subject Title
                            </Label>
                            <Input
                                placeholder="System Maintenance Notice"
                                className="font-bold"
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label className="text-[10px] font-black text-muted-foreground uppercase">
                                Message Content
                            </Label>
                            <Textarea
                                placeholder="Write your announcement details here..."
                                className="min-h-[120px] font-medium"
                                value={form.data.content}
                                onChange={(e) =>
                                    form.setData('content', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label className="text-[10px] font-black text-muted-foreground uppercase">
                                    Priority Level
                                </Label>
                                <Select
                                    value={form.data.priority}
                                    onValueChange={(val) =>
                                        form.setData('priority', val)
                                    }
                                >
                                    <SelectTrigger className="font-bold">
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
                                <Label className="text-[10px] font-black text-muted-foreground uppercase">
                                    Expiry Date
                                </Label>
                                <Input
                                    type="date"
                                    className="font-bold"
                                    value={form.data.expires_at}
                                    onChange={(e) =>
                                        form.setData(
                                            'expires_at',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsAddOpen(false)}
                            className="text-xs font-bold uppercase"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handlePost}
                            disabled={form.processing}
                            className="gap-2 text-xs font-bold uppercase"
                        >
                            <Bell className="size-3.5" />
                            Post Announcement
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
