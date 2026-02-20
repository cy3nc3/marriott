import { Head, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    Clock,
    Cog,
    Database,
    Image as ImageIcon,
    RefreshCcw,
    RotateCcw,
    Save,
    School,
    Settings2,
    UploadCloud,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    Field,
    FieldGroup,
    FieldLabel,
    FieldDescription as UIFieldDescription,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
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
    backups: {
        file_name: string;
        created_at: string;
        size: string;
        reason: string;
    }[];
}

const isEnabled = (value: string | undefined, defaultValue = false) => {
    if (value === undefined) {
        return defaultValue;
    }

    return value === '1' || value.toLowerCase() === 'true';
};

export default function SystemSettings({ settings, backups }: Props) {
    const { data, setData, post, processing, isDirty, errors } = useForm({
        school_name: settings.school_name || 'Marriott School System',
        school_id: settings.school_id || '',
        address: settings.address || '',
        maintenance_mode: isEnabled(settings.maintenance_mode),
        parent_portal: isEnabled(settings.parent_portal),
        backup_interval: settings.backup_interval || 'week',
        backup_interval_days: settings.backup_interval_days || '15',
        backup_on_quarter: isEnabled(settings.backup_on_quarter, true),
        backup_on_year_end: isEnabled(settings.backup_on_year_end, true),
        latest_backup_at: settings.latest_backup_at || '',
        logo: null as File | null,
        header: null as File | null,
    });

    const [logoPreview, setLogoPreview] = useState<string | null>(
        settings.logo || null,
    );
    const [headerPreview, setHeaderPreview] = useState<string | null>(
        settings.header || null,
    );
    const [isBackupConfigOpen, setIsBackupConfigOpen] = useState(false);
    const [isRestoreOpen, setIsRestoreOpen] = useState(false);

    const handleFileChange = (
        e: React.ChangeEvent<HTMLInputElement>,
        type: 'logo' | 'header',
    ) => {
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

    const handleRunBackup = () => {
        router.post(
            store.url(),
            {
                run_backup: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setData('latest_backup_at', new Date().toISOString());
                    setIsRestoreOpen(false);
                },
            },
        );
    };

    const handleRestoreBackup = (fileName: string) => {
        if (
            !confirm(
                `Restore backup "${fileName}"? This will replace current data.`,
            )
        ) {
            return;
        }

        router.post(
            store.url(),
            {
                restore_file: fileName,
            },
            {
                preserveScroll: true,
                onSuccess: () => setIsRestoreOpen(false),
            },
        );
    };

    const latestBackupDate = data.latest_backup_at
        ? new Date(data.latest_backup_at)
        : null;
    const hasValidLatestBackup =
        latestBackupDate !== null && !Number.isNaN(latestBackupDate.getTime());
    const latestBackupLabel =
        hasValidLatestBackup && latestBackupDate
            ? latestBackupDate.toLocaleString()
            : 'No backups yet';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />
            <div className="flex flex-col gap-6">
                <div>
                    <Button
                        onClick={handleSave}
                        disabled={processing || !isDirty}
                    >
                        <Save className="size-4" />
                        {processing ? 'Saving...' : 'Save All Changes'}
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* School Identity */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <School className="size-4 text-muted-foreground" />
                                <CardTitle>School Identity</CardTitle>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Official details for reports and documents
                            </p>
                        </CardHeader>
                        <CardContent>
                            <FieldGroup className="gap-6">
                                <Field>
                                    <FieldLabel>School Name</FieldLabel>
                                    <Input
                                        id="school-name"
                                        placeholder="Marriott School System"
                                        value={data.school_name}
                                        onChange={(e) =>
                                            setData(
                                                'school_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.school_name && (
                                        <p className="text-sm text-destructive">
                                            {errors.school_name}
                                        </p>
                                    )}
                                </Field>
                                <Field>
                                    <FieldLabel>School ID (DepEd)</FieldLabel>
                                    <Input
                                        id="school-id"
                                        placeholder="123456"
                                        value={data.school_id}
                                        onChange={(e) =>
                                            setData('school_id', e.target.value)
                                        }
                                    />
                                    {errors.school_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.school_id}
                                        </p>
                                    )}
                                </Field>
                                <Field>
                                    <FieldLabel>Official Address</FieldLabel>
                                    <Textarea
                                        id="address"
                                        placeholder="123 Tolentino Street, Quezon City"
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                        className="min-h-[100px]"
                                    />
                                    {errors.address && (
                                        <p className="text-sm text-destructive">
                                            {errors.address}
                                        </p>
                                    )}
                                </Field>
                            </FieldGroup>
                        </CardContent>
                    </Card>

                    {/* Branding */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <ImageIcon className="size-4 text-muted-foreground" />
                                <CardTitle>Visual Identity</CardTitle>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Logo and report headers
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-8">
                            <div className="flex flex-col items-start gap-8 sm:flex-row">
                                <div className="flex shrink-0 flex-col gap-3">
                                    <Label>School Logo</Label>
                                    <div className="group relative flex size-32 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed bg-muted/20">
                                        {logoPreview ? (
                                            <img
                                                src={logoPreview}
                                                alt="Logo Preview"
                                                className="size-full object-contain p-2"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center justify-center gap-1 text-muted-foreground/40">
                                                <UploadCloud className="size-6" />
                                                <span className="text-xs">
                                                    1:1 Square
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    <Input
                                        id="school-logo"
                                        type="file"
                                        accept="image/*"
                                        className="w-32 cursor-pointer"
                                        onChange={(e) =>
                                            handleFileChange(e, 'logo')
                                        }
                                    />
                                    {errors.logo && (
                                        <p className="text-sm text-destructive">
                                            {errors.logo}
                                        </p>
                                    )}
                                </div>

                                <div className="flex w-full flex-col gap-3">
                                    <Label>Report Header</Label>
                                    <div className="group relative flex h-32 w-full items-center justify-center overflow-hidden rounded-xl border-2 border-dashed bg-muted/20">
                                        {headerPreview ? (
                                            <img
                                                src={headerPreview}
                                                alt="Header Preview"
                                                className="size-full object-contain p-2"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center justify-center gap-1 text-muted-foreground/40">
                                                <ImageIcon className="size-6" />
                                                <span className="text-xs">
                                                    Standard Banner (Rectangle)
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    <Input
                                        id="school-header"
                                        type="file"
                                        accept="image/*"
                                        className="cursor-pointer"
                                        onChange={(e) =>
                                            handleFileChange(e, 'header')
                                        }
                                    />
                                    {errors.header && (
                                        <p className="text-sm text-destructive">
                                            {errors.header}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div className="space-y-1">
                                <UIFieldDescription>
                                    Recommended size: 512x512px for Logo,
                                    1200x200px for Header (PNG/JPG)
                                </UIFieldDescription>
                            </div>
                        </CardContent>
                    </Card>

                    {/* System Controls */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Cog className="size-4 text-muted-foreground" />
                                <CardTitle>System Controls</CardTitle>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Global access and maintenance toggles
                            </p>
                        </CardHeader>
                        <CardContent>
                            <FieldGroup className="gap-4">
                                <div className="flex items-center justify-between rounded-xl border p-4">
                                    <div className="space-y-0.5">
                                        <p className="text-sm font-medium">
                                            Maintenance Mode
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Disable access for all non-admin
                                            users
                                        </p>
                                    </div>
                                    <Switch
                                        id="maintenance-mode"
                                        checked={data.maintenance_mode}
                                        onCheckedChange={(val) =>
                                            setData('maintenance_mode', val)
                                        }
                                    />
                                </div>
                                <div className="flex items-center justify-between rounded-xl border p-4">
                                    <div className="space-y-0.5">
                                        <p className="text-sm font-medium">
                                            Parent Portal
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Allow parents to view grades and
                                            billing
                                        </p>
                                    </div>
                                    <Switch
                                        id="parent-access"
                                        checked={data.parent_portal}
                                        onCheckedChange={(val) =>
                                            setData('parent_portal', val)
                                        }
                                    />
                                </div>
                            </FieldGroup>
                        </CardContent>
                    </Card>

                    {/* System Backup */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Database className="size-4 text-muted-foreground" />
                                <CardTitle>System Backup</CardTitle>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Data preservation and disaster recovery
                            </p>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-6">
                            <div className="flex items-center justify-between rounded-xl border p-4">
                                <div className="space-y-1">
                                    <p className="text-sm font-medium">
                                        Instant Backup
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Create a full snapshot of the database
                                        and files
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Dialog
                                        open={isRestoreOpen}
                                        onOpenChange={setIsRestoreOpen}
                                    >
                                        <DialogTrigger asChild>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="gap-2"
                                            >
                                                <RotateCcw className="size-3.5" />
                                                Restore
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="sm:max-w-[600px]">
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Database Restore
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Select a previous snapshot
                                                    to revert the system.
                                                    Warning: Current unsaved
                                                    data will be lost.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="py-4">
                                                <div className="overflow-hidden rounded-lg border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>
                                                                    Backup Date
                                                                </TableHead>
                                                                <TableHead>
                                                                    Size
                                                                </TableHead>
                                                                <TableHead className="text-right">
                                                                    Action
                                                                </TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {backups.map(
                                                                (backup) => (
                                                                    <TableRow
                                                                        key={
                                                                            backup.file_name
                                                                        }
                                                                    >
                                                                        <TableCell className="font-mono text-xs">
                                                                            {new Date(
                                                                                backup.created_at,
                                                                            ).toLocaleString()}
                                                                        </TableCell>
                                                                        <TableCell className="text-xs">
                                                                            {
                                                                                backup.size
                                                                            }
                                                                        </TableCell>
                                                                        <TableCell className="text-right">
                                                                            <Button
                                                                                size="sm"
                                                                                onClick={() =>
                                                                                    handleRestoreBackup(
                                                                                        backup.file_name,
                                                                                    )
                                                                                }
                                                                            >
                                                                                Restore
                                                                                this
                                                                            </Button>
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ),
                                                            )}
                                                            {backups.length ===
                                                                0 && (
                                                                <TableRow>
                                                                    <TableCell
                                                                        colSpan={
                                                                            3
                                                                        }
                                                                        className="text-center text-sm text-muted-foreground"
                                                                    >
                                                                        No
                                                                        backup
                                                                        snapshots
                                                                        available.
                                                                    </TableCell>
                                                                </TableRow>
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <div className="mr-auto flex items-center gap-3 text-muted-foreground">
                                                    <AlertTriangle className="size-5" />
                                                    <p className="text-sm">
                                                        Proceed with extreme
                                                        caution. This action
                                                        cannot be undone.
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    onClick={() =>
                                                        setIsRestoreOpen(false)
                                                    }
                                                >
                                                    Close
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                    <Button
                                        size="sm"
                                        className="gap-2"
                                        onClick={handleRunBackup}
                                    >
                                        <RefreshCcw className="size-3.5" />
                                        Run Backup
                                    </Button>
                                </div>
                            </div>

                            <div className="flex items-center justify-between rounded-xl border p-4">
                                <div className="space-y-0.5">
                                    <p className="text-sm font-medium">
                                        Automatic Scheduling
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Configure recurring background backups
                                    </p>
                                </div>
                                <Dialog
                                    open={isBackupConfigOpen}
                                    onOpenChange={setIsBackupConfigOpen}
                                >
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" size="icon">
                                            <Settings2 className="size-4" />
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent className="sm:max-w-[425px]">
                                        <DialogHeader>
                                            <DialogTitle>
                                                Backup Configuration
                                            </DialogTitle>
                                            <DialogDescription>
                                                Define triggers and intervals
                                                for automated snapshots.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="grid gap-6 py-4">
                                            <div className="grid gap-2">
                                                <Label>
                                                    Automatically backup
                                                </Label>
                                                <Select
                                                    value={data.backup_interval}
                                                    onValueChange={(value) =>
                                                        setData(
                                                            'backup_interval',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="week">
                                                            Every Week
                                                        </SelectItem>
                                                        <SelectItem value="month">
                                                            Every Month
                                                        </SelectItem>
                                                        <SelectItem value="custom">
                                                            Custom Interval
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            {data.backup_interval ===
                                                'custom' && (
                                                <div className="grid gap-2">
                                                    <Label>Interval Days</Label>
                                                    <div className="flex items-center gap-3">
                                                        <Input
                                                            type="number"
                                                            value={
                                                                data.backup_interval_days
                                                            }
                                                            onChange={(e) =>
                                                                setData(
                                                                    'backup_interval_days',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="w-24"
                                                            min={1}
                                                        />
                                                        <span className="text-sm text-muted-foreground">
                                                            Run backup every X
                                                            days
                                                        </span>
                                                    </div>
                                                    {errors.backup_interval_days && (
                                                        <p className="text-sm text-destructive">
                                                            {
                                                                errors.backup_interval_days
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                            )}

                                            <div className="h-px border-dashed bg-border" />

                                            <div className="space-y-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="space-y-0.5">
                                                        <p className="text-sm font-medium">
                                                            Quarterly Backup
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            Auto-backup when
                                                            advancing quarters
                                                        </p>
                                                    </div>
                                                    <Switch
                                                        checked={
                                                            data.backup_on_quarter
                                                        }
                                                        onCheckedChange={(
                                                            value,
                                                        ) =>
                                                            setData(
                                                                'backup_on_quarter',
                                                                value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <div className="space-y-0.5">
                                                        <p className="text-sm font-medium">
                                                            Year-End Backup
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            Auto-backup when
                                                            closing the school
                                                            year
                                                        </p>
                                                    </div>
                                                    <Switch
                                                        checked={
                                                            data.backup_on_year_end
                                                        }
                                                        onCheckedChange={(
                                                            value,
                                                        ) =>
                                                            setData(
                                                                'backup_on_year_end',
                                                                value,
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button
                                                variant="outline"
                                                onClick={() =>
                                                    setIsBackupConfigOpen(false)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                onClick={() =>
                                                    setIsBackupConfigOpen(false)
                                                }
                                            >
                                                Update Schedule
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>

                            <div className="flex items-center gap-3 rounded-lg border border-dashed px-4 py-3">
                                <Clock className="size-4 text-muted-foreground" />
                                <div className="space-y-0.5">
                                    <p className="text-xs leading-none text-muted-foreground">
                                        Latest Backup
                                    </p>
                                    <p className="text-sm font-medium">
                                        {latestBackupLabel}
                                    </p>
                                </div>
                                <Badge variant="outline" className="ml-auto">
                                    {hasValidLatestBackup ? 'Recorded' : 'None'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
