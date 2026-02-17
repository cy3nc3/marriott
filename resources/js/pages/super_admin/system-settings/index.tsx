import { Head, useForm } from '@inertiajs/react';
import { Save, School, Cog, Image as ImageIcon, Database, Clock, RefreshCcw, UploadCloud, Settings2, History, RotateCcw, AlertTriangle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldGroup,
    FieldLabel,
    FieldDescription as UIFieldDescription,
} from '@/components/ui/field';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { useState } from 'react';
import type { BreadcrumbItem } from '@/types';
import { store } from '@/routes/super_admin/system_settings';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Settings',
        href: '/super-admin/system-settings',
    },
];

interface Props {
    settings: Record<string, string>;
}

export default function SystemSettings({ settings }: Props) {
    const { data, setData, post, processing, isDirty } = useForm({
        school_name: settings.school_name || 'Marriott School System',
        school_id: settings.school_id || '',
        address: settings.address || '',
        maintenance_mode: settings.maintenance_mode === '1',
        parent_portal: settings.parent_portal === '1',
        logo: null as File | null,
        header: null as File | null,
    });

    const [logoPreview, setLogoPreview] = useState<string | null>(settings.logo || null);
    const [headerPreview, setHeaderPreview] = useState<string | null>(settings.header || null);
    const [isBackupConfigOpen, setIsBackupConfigOpen] = useState(false);
    const [isRestoreOpen, setIsRestoreOpen] = useState(false);
    const [backupInterval, setBackupInterval] = useState('week');

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>, type: 'logo' | 'header') => {
        const file = e.target.files?.[0];
        if (file) {
            setData(type, file);
            const reader = new FileReader();
            reader.onloadend = () => {
                if (type === 'logo') setLogoPreview(reader.result as string);
                else setHeaderPreview(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSave = () => {
        post(store.url(), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />
            <div className="flex flex-col gap-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">System <span className="text-primary not-italic">Configuration</span></h1>

                    </div>
                    <Button className="gap-2 h-9" onClick={handleSave} disabled={processing || !isDirty}>
                        <Save className="size-4" />
                        <span className="text-xs font-bold">{processing ? 'Saving...' : 'Save All Changes'}</span>
                    </Button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* School Identity */}
                    <Card className="shadow-md border-primary/10 overflow-hidden">
                        <CardHeader className="bg-primary/[0.03] border-b border-primary/10 py-4">
                            <div className="flex items-center gap-2">
                                <School className="size-4 text-primary" />
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-primary">School Identity</CardTitle>
                            </div>
                            <CardDescription className="text-[10px] font-medium italic">Official details for reports and documents</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <FieldGroup className="gap-6">
                                <Field>
                                    <FieldLabel className="text-[10px] font-black uppercase text-muted-foreground mb-1">School Name</FieldLabel>
                                    <Input 
                                        id="school-name" 
                                        placeholder="Marriott School System" 
                                        value={data.school_name} 
                                        onChange={e => setData('school_name', e.target.value)}
                                        className="font-bold" 
                                    />
                                </Field>
                                <Field>
                                    <FieldLabel className="text-[10px] font-black uppercase text-muted-foreground mb-1">School ID (DepEd)</FieldLabel>
                                    <Input 
                                        id="school-id" 
                                        placeholder="123456" 
                                        value={data.school_id}
                                        onChange={e => setData('school_id', e.target.value)}
                                        className="font-bold" 
                                    />
                                </Field>
                                <Field>
                                    <FieldLabel className="text-[10px] font-black uppercase text-muted-foreground mb-1">Official Address</FieldLabel>
                                    <Textarea 
                                        id="address" 
                                        placeholder="123 Tolentino Street, Quezon City" 
                                        value={data.address}
                                        onChange={e => setData('address', e.target.value)}
                                        className="min-h-[100px] font-medium"
                                    />
                                </Field>
                            </FieldGroup>
                        </CardContent>
                    </Card>

                    {/* Branding */}
                    <Card className="shadow-md border-primary/10 overflow-hidden">
                        <CardHeader className="bg-primary/[0.03] border-b border-primary/10 py-4">
                            <div className="flex items-center gap-2">
                                <ImageIcon className="size-4 text-primary" />
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-primary">Visual Identity</CardTitle>
                            </div>
                            <CardDescription className="text-[10px] font-medium italic">Logo and report headers</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-8">
                            <div className="flex flex-col sm:flex-row gap-8 items-start">
                                <div className="flex flex-col gap-3 shrink-0">
                                    <Label className="text-[10px] font-black uppercase text-muted-foreground">School Logo</Label>
                                    <div className="relative group overflow-hidden bg-muted/20 border-2 border-dashed rounded-xl size-32 flex items-center justify-center">
                                        {logoPreview ? (
                                            <img src={logoPreview} alt="Logo Preview" className="size-full object-contain p-2" />
                                        ) : (
                                            <div className="flex flex-col items-center justify-center gap-1 text-muted-foreground/40">
                                                <UploadCloud className="size-6" />
                                                <span className="text-[8px] font-black uppercase">1:1 Square</span>
                                            </div>
                                        )}
                                    </div>
                                    <Input 
                                        id="school-logo" 
                                        type="file" 
                                        accept="image/*" 
                                        className="w-32 cursor-pointer text-[10px] h-8" 
                                        onChange={(e) => handleFileChange(e, 'logo')}
                                    />
                                </div>

                                <div className="flex flex-col gap-3 w-full">
                                    <Label className="text-[10px] font-black uppercase text-muted-foreground">Report Header</Label>
                                    <div className="relative group overflow-hidden bg-muted/20 border-2 border-dashed rounded-xl h-32 w-full flex items-center justify-center">
                                        {headerPreview ? (
                                            <img src={headerPreview} alt="Header Preview" className="size-full object-contain p-2" />
                                        ) : (
                                            <div className="flex flex-col items-center justify-center gap-1 text-muted-foreground/40">
                                                <ImageIcon className="size-6" />
                                                <span className="text-[8px] font-black uppercase">Standard Banner (Rectangle)</span>
                                            </div>
                                        )}
                                    </div>
                                    <Input 
                                        id="school-header" 
                                        type="file" 
                                        accept="image/*" 
                                        className="cursor-pointer text-[10px] h-8" 
                                        onChange={(e) => handleFileChange(e, 'header')}
                                    />
                                </div>
                            </div>
                            <div className="space-y-1">
                                <UIFieldDescription className="text-[9px] font-medium italic">Recommended size: 512x512px for Logo, 1200x200px for Header (PNG/JPG)</UIFieldDescription>
                            </div>
                        </CardContent>
                    </Card>

                    {/* System Controls */}
                    <Card className="shadow-md border-primary/10 overflow-hidden">
                        <CardHeader className="bg-primary/[0.03] border-b border-primary/10 py-4">
                            <div className="flex items-center gap-2">
                                <Cog className="size-4 text-primary" />
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-primary">System Controls</CardTitle>
                            </div>
                            <CardDescription className="text-[10px] font-medium italic">Global access and maintenance toggles</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <FieldGroup className="gap-4">
                                <div className="flex items-center justify-between p-4 bg-muted/20 rounded-xl border border-primary/5">
                                    <div className="space-y-0.5">
                                        <p className="text-xs font-black uppercase tracking-wider">Maintenance Mode</p>
                                        <p className="text-[10px] text-muted-foreground font-medium italic">Disable access for all non-admin users</p>
                                    </div>
                                    <Switch 
                                        id="maintenance-mode" 
                                        checked={data.maintenance_mode}
                                        onCheckedChange={val => setData('maintenance_mode', val)}
                                    />
                                </div>
                                <div className="flex items-center justify-between p-4 bg-muted/20 rounded-xl border border-primary/5">
                                    <div className="space-y-0.5">
                                        <p className="text-xs font-black uppercase tracking-wider">Parent Portal</p>
                                        <p className="text-[10px] text-muted-foreground font-medium italic">Allow parents to view grades and billing</p>
                                    </div>
                                    <Switch 
                                        id="parent-access" 
                                        checked={data.parent_portal}
                                        onCheckedChange={val => setData('parent_portal', val)}
                                    />
                                </div>
                            </FieldGroup>
                        </CardContent>
                    </Card>

                    {/* System Backup */}
                    <Card className="shadow-md border-primary/10 overflow-hidden">
                        <CardHeader className="bg-primary/[0.03] border-b border-primary/10 py-4">
                            <div className="flex items-center gap-2">
                                <Database className="size-4 text-primary" />
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-primary">System Backup</CardTitle>
                            </div>
                            <CardDescription className="text-[10px] font-medium italic">Data preservation and disaster recovery</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6 flex flex-col gap-6">
                            <div className="flex items-center justify-between p-4 bg-primary/[0.03] rounded-xl border border-primary/10">
                                <div className="space-y-1">
                                    <p className="text-xs font-black uppercase tracking-wider text-primary">Instant Backup</p>
                                    <p className="text-[10px] text-muted-foreground font-medium italic">Create a full snapshot of the database and files</p>
                                </div>
                                <div className="flex gap-2">
                                    <Dialog open={isRestoreOpen} onOpenChange={setIsRestoreOpen}>
                                        <DialogTrigger asChild>
                                            <Button size="sm" variant="outline" className="gap-2 h-8 border-primary/20 hover:bg-primary/5">
                                                <RotateCcw className="size-3.5" />
                                                <span className="text-[10px] font-bold uppercase">Restore</span>
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="sm:max-w-[600px]">
                                            <DialogHeader>
                                                <DialogTitle className="flex items-center gap-2 italic font-black">
                                                    <RotateCcw className="size-5 text-primary not-italic" />
                                                    Database <span className="text-primary not-italic">Restore</span>
                                                </DialogTitle>
                                                <DialogDescription className="text-xs">
                                                    Select a previous snapshot to revert the system. <span className="text-destructive font-bold uppercase">Warning: Current unsaved data will be lost.</span>
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="py-4">
                                                <div className="rounded-lg border overflow-hidden">
                                                    <Table>
                                                        <TableHeader className="bg-muted/50">
                                                            <TableRow>
                                                                <TableHead className="text-[10px] font-black uppercase">Backup Date</TableHead>
                                                                <TableHead className="text-[10px] font-black uppercase">Size</TableHead>
                                                                <TableHead className="text-right text-[10px] font-black uppercase">Action</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {[
                                                                { date: '2026-02-17 10:00 AM', size: '4.2 MB', id: 1 },
                                                                { date: '2026-02-16 12:00 AM', size: '4.1 MB', id: 2 },
                                                                { date: '2026-02-15 12:00 AM', size: '3.9 MB', id: 3 },
                                                            ].map((backup) => (
                                                                <TableRow key={backup.id}>
                                                                    <TableCell className="font-mono text-xs">{backup.date}</TableCell>
                                                                    <TableCell className="text-xs">{backup.size}</TableCell>
                                                                    <TableCell className="text-right">
                                                                        <Button size="sm" className="h-7 text-[10px] font-bold uppercase">Restore this</Button>
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </div>
                                            <DialogFooter className="bg-destructive/5 -m-6 mt-4 p-6 border-t border-destructive/10">
                                                <div className="flex items-center gap-3 mr-auto text-destructive">
                                                    <AlertTriangle className="size-5" />
                                                    <p className="text-[10px] font-bold uppercase leading-tight">Proceed with extreme caution. This action cannot be undone.</p>
                                                </div>
                                                <Button variant="outline" onClick={() => setIsRestoreOpen(false)} className="text-xs font-bold uppercase">Close</Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                    <Button size="sm" className="gap-2 h-8">
                                        <RefreshCcw className="size-3.5" />
                                        <span className="text-[10px] font-bold uppercase">Run Backup</span>
                                    </Button>
                                </div>
                            </div>

                            <div className="flex items-center justify-between p-4 bg-muted/20 rounded-xl border border-primary/5">
                                <div className="space-y-0.5">
                                    <p className="text-xs font-black uppercase tracking-wider">Automatic Scheduling</p>
                                    <p className="text-[10px] text-muted-foreground font-medium italic">Configure recurring background backups</p>
                                </div>
                                <Dialog open={isBackupConfigOpen} onOpenChange={setIsBackupConfigOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-primary hover:bg-primary/5">
                                            <Settings2 className="size-4" />
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent className="sm:max-w-[425px]">
                                        <DialogHeader>
                                            <DialogTitle className="flex items-center gap-2">
                                                <Settings2 className="size-5 text-primary" />
                                                Backup Configuration
                                            </DialogTitle>
                                            <DialogDescription className="text-xs italic">
                                                Define triggers and intervals for automated snapshots.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="grid gap-6 py-4">
                                            <div className="grid gap-2">
                                                <Label className="text-[10px] font-black uppercase text-muted-foreground">Automatically backup</Label>
                                                <Select value={backupInterval} onValueChange={setBackupInterval}>
                                                    <SelectTrigger className="font-bold">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="week">Every Week</SelectItem>
                                                        <SelectItem value="month">Every Month</SelectItem>
                                                        <SelectItem value="custom">Custom Interval</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            {backupInterval === 'custom' && (
                                                <div className="grid gap-2 animate-in slide-in-from-top-2 duration-200">
                                                    <Label className="text-[10px] font-black uppercase text-muted-foreground">Interval Days</Label>
                                                    <div className="flex items-center gap-3">
                                                        <Input type="number" defaultValue={15} className="font-bold w-24" min={1} />
                                                        <span className="text-xs font-medium text-muted-foreground italic">Run backup every X days</span>
                                                    </div>
                                                </div>
                                            )}

                                            <div className="h-px bg-border border-dashed" />

                                            <div className="space-y-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="space-y-0.5">
                                                        <p className="text-xs font-black uppercase tracking-wider">Quarterly Backup</p>
                                                        <p className="text-[10px] text-muted-foreground font-medium italic">Auto-backup when advancing quarters</p>
                                                    </div>
                                                    <Switch checked={true} />
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <div className="space-y-0.5">
                                                        <p className="text-xs font-black uppercase tracking-wider">Year-End Backup</p>
                                                        <p className="text-[10px] text-muted-foreground font-medium italic">Auto-backup when closing the school year</p>
                                                    </div>
                                                    <Switch checked={true} />
                                                </div>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setIsBackupConfigOpen(false)} className="text-xs font-bold uppercase">Cancel</Button>
                                            <Button onClick={() => setIsBackupConfigOpen(false)} className="text-xs font-bold uppercase">Update Schedule</Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>

                            <div className="flex items-center gap-3 px-4 py-3 rounded-lg bg-muted/30 border border-dashed">
                                <Clock className="size-4 text-muted-foreground" />
                                <div className="space-y-0.5">
                                    <p className="text-[9px] font-black uppercase text-muted-foreground leading-none">Latest Backup</p>
                                    <p className="text-xs font-bold">February 17, 2026 <span className="text-[10px] text-muted-foreground font-medium ml-1">10:00 AM</span></p>
                                </div>
                                <Badge variant="outline" className="ml-auto bg-emerald-50 text-emerald-700 border-emerald-200 font-bold text-[9px] uppercase">Success</Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
