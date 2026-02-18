import { Head, useForm, router } from '@inertiajs/react';
import { 
    Megaphone,
    Plus,
    Trash2,
    Calendar,
    Users,
    AlertCircle,
    Bell,
    Pencil,
    Filter,
    Search,
    XCircle
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { format } from "date-fns";
import { Badge } from "@/components/ui/badge"
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardDescription,
    CardTitle
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { DatePicker } from "@/components/ui/date-picker";
import { RolesCombobox } from "@/components/ui/roles-combobox";
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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
    announcements: Announcement[];
    roles: { value: string, label: string }[];
}

export default function Announcements({ announcements, roles }: Props) {
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<Announcement | null>(null);
    
    // Filters
    const [filterPriority, setFilterPriority] = useState<string>('all');
    const [filterRole, setFilterRole] = useState<string>('all');
    const [searchQuery, setSearchQuery] = useState<string>('');

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
                }
            });
        } else {
            form.post(super_admin.announcements.store.url(), {
                onSuccess: () => {
                    setIsAddOpen(false);
                    form.reset();
                }
            });
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this announcement?')) {
            router.delete(super_admin.announcements.destroy.url(id));
        }
    };
    
    const handleDateChange = (date?: Date) => {
        form.setData('expires_at', date ? format(date, "yyyy-MM-dd") : '');
    }

    const getPriorityBadge = (priority: string) => {
        const styles: Record<string, string> = {
            critical: "bg-rose-500 text-white border-rose-600",
            high: "bg-amber-500 text-white border-amber-600",
            normal: "bg-blue-50 text-blue-700 border-blue-200",
            low: "bg-muted text-muted-foreground border-border",
        };
        return (
            <Badge variant="outline" className={cn("font-black text-[9px] uppercase", styles[priority])}>
                {priority}
            </Badge>
        );
    };

    const filteredAnnouncements = useMemo(() => {
        return announcements.filter(item => {
            const matchesPriority = filterPriority === 'all' || item.priority === filterPriority;
            const matchesRole = filterRole === 'all' || (item.target_roles === null || item.target_roles.length === 0 || item.target_roles.includes(filterRole));
            const matchesSearch = item.title.toLowerCase().includes(searchQuery.toLowerCase()) || 
                                 item.content.toLowerCase().includes(searchQuery.toLowerCase());
            
            return matchesPriority && matchesRole && matchesSearch;
        });
    }, [announcements, filterPriority, filterRole, searchQuery]);

    const resetFilters = () => {
        setFilterPriority('all');
        setFilterRole('all');
        setSearchQuery('');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Announcements" />
            <div className="flex flex-col gap-6">
                
                {/* Filters Bar - Cleaned up */}
                <div className="flex flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                         <Select value={filterPriority} onValueChange={setFilterPriority}>
                            <SelectTrigger className="w-[140px] h-9 font-bold text-[10px] uppercase">
                                <div className="flex items-center gap-2">
                                    <Filter className="size-3 text-primary" />
                                    <SelectValue placeholder="Priority" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Priorities</SelectItem>
                                <SelectItem value="critical">Critical</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="normal">Normal</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={filterRole} onValueChange={setFilterRole}>
                            <SelectTrigger className="w-[140px] h-9 font-bold text-[10px] uppercase">
                                <div className="flex items-center gap-2">
                                    <Users className="size-3 text-primary" />
                                    <SelectValue placeholder="Target Role" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Roles</SelectItem>
                                {roles.map(role => (
                                    <SelectItem key={role.value} value={role.value}>{role.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {(filterPriority !== 'all' || filterRole !== 'all') && (
                            <Button variant="ghost" size="icon" onClick={() => { setFilterPriority('all'); setFilterRole('all'); }} className="size-9 text-muted-foreground hover:text-destructive shrink-0">
                                <XCircle className="size-4" />
                            </Button>
                        )}
                    </div>
                    
                    <Button className="gap-2 h-9 px-4" onClick={() => handleOpenDialog()}>
                        <Plus className="size-4" />
                        <span className="text-xs font-bold uppercase tracking-wider">New Announcement</span>
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4">
                    {filteredAnnouncements.map((item) => (
                        <Card key={item.id} className="shadow-sm border-primary/10 overflow-hidden">
                            <CardHeader className="py-4 border-b bg-muted/10">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className={cn(
                                            "p-2 rounded-lg",
                                            item.priority === 'critical' ? "bg-rose-500/10 text-rose-600" : "bg-primary/5 text-primary"
                                        )}>
                                            <Bell className="size-4" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-sm font-bold">{item.title}</CardTitle>
                                            <div className="flex items-center gap-2 mt-0.5">
                                                <p className="text-[10px] text-muted-foreground font-medium uppercase">
                                                    Posted by {item.user.name} • {new Date(item.created_at).toLocaleDateString()}
                                                </p>
                                                {item.target_roles && item.target_roles.length > 0 && (
                                                    <div className="flex items-center gap-1.5 ml-2 border-l pl-2 border-border/50">
                                                        <Users className="size-3 text-muted-foreground" />
                                                        <div className="flex gap-1">
                                                            {item.target_roles.map(role => (
                                                                <span key={role} className="text-[9px] font-bold uppercase text-primary/60">
                                                                    {roles.find(r => r.value === role)?.label || role}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {getPriorityBadge(item.priority)}
                                        <div className="flex items-center gap-1">
                                            <Button variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-primary" onClick={() => handleOpenDialog(item)}>
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-destructive" onClick={() => handleDelete(item.id)}>
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="py-4 px-6">
                                <p className="text-sm text-muted-foreground/90 font-medium leading-relaxed whitespace-pre-wrap">
                                    {item.content}
                                </p>
                            </CardContent>
                        </Card>
                    ))}

                    {filteredAnnouncements.length === 0 && (
                        <div className="flex flex-col items-center justify-center py-20 rounded-xl border-2 border-dashed border-muted bg-muted/5 text-center">
                            <Megaphone className="size-12 text-muted-foreground/20 mb-4" />
                            <h3 className="text-lg font-bold text-muted-foreground tracking-tight">No broadcasts found</h3>
                            <p className="text-sm text-muted-foreground/60 max-w-xs mt-1 font-medium">Adjust your filters or post a new message to inform the school community of system updates.</p>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                <DialogContent className="sm:max-w-[450px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 font-black">
                            <Megaphone className="size-5 text-primary not-italic" />
                            {editingItem ? 'Edit' : 'New'} <span className="text-primary not-italic">Broadcast</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Define the message content and visibility parameters.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-6 py-4">
                        <div className="grid gap-2">
                            <Label className="text-[10px] font-black uppercase text-muted-foreground">Broadcast Title</Label>
                            <Input 
                                placeholder="e.g., System Maintenance Notice" 
                                className="font-bold h-9"
                                value={form.data.title}
                                onChange={e => form.setData('title', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label className="text-[10px] font-black uppercase text-muted-foreground">Message Content</Label>
                            <Textarea 
                                placeholder="Describe the announcement in detail..."
                                className="min-h-[120px] font-medium leading-relaxed"
                                value={form.data.content}
                                onChange={e => form.setData('content', e.target.value)}
                            />
                        </div>
                        
                        <div className="grid gap-2">
                            <Label className="text-[10px] font-black uppercase text-muted-foreground">Target Audience (Roles)</Label>
                            <RolesCombobox 
                                options={roles}
                                selected={form.data.target_roles}
                                onChange={selected => form.setData('target_roles', selected)}
                                placeholder="Target all roles..."
                                className="h-9"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label className="text-[10px] font-black uppercase text-muted-foreground">Priority Level</Label>
                                <Select value={form.data.priority} onValueChange={val => form.setData('priority', val)}>
                                    <SelectTrigger className="h-9 font-bold uppercase text-[10px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Low (Info)</SelectItem>
                                        <SelectItem value="normal">Normal</SelectItem>
                                        <SelectItem value="high">High (Warning)</SelectItem>
                                        <SelectItem value="critical">Critical (Emergency)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label className="text-[10px] font-black uppercase text-muted-foreground">Auto-Expiry Date</Label>
                                <DatePicker 
                                    date={form.data.expires_at ? new Date(form.data.expires_at) : undefined}
                                    setDate={handleDateChange}
                                    className="w-full h-9 font-bold"
                                    placeholder="No Expiry"
                                />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsAddOpen(false)} className="text-xs font-bold uppercase">Cancel</Button>
                        <Button onClick={handleSubmit} disabled={form.processing} className="text-xs font-bold uppercase gap-2 px-6">
                            <Bell className="size-3.5" />
                            {editingItem ? 'Update' : 'Launch'} Broadcast
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
