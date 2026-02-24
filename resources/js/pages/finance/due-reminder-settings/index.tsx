import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import {
    destroy,
    store,
    update,
} from '@/routes/finance/due_reminder_settings';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/announcements',
    },
    {
        title: 'Due Reminder Settings',
        href: '/finance/due-reminder-settings',
    },
];

type ReminderRuleRow = {
    id: number;
    days_before_due: number;
    label: string;
    is_active: boolean;
    dispatch_count: number;
    last_sent_at: string | null;
};

interface Props {
    rules: ReminderRuleRow[];
}

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

export default function DueReminderSettings({ rules }: Props) {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [editingRule, setEditingRule] = useState<ReminderRuleRow | null>(null);

    const createForm = useForm({
        days_before_due: '3',
        is_active: true,
    });

    const editForm = useForm({
        days_before_due: '',
        is_active: true,
    });

    const openCreateDialog = () => {
        createForm.setData({
            days_before_due: '3',
            is_active: true,
        });
        createForm.clearErrors();
        setIsCreateDialogOpen(true);
    };

    const submitCreate = () => {
        createForm.submit(store(), {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                createForm.reset();
            },
        });
    };

    const openEditDialog = (rule: ReminderRuleRow) => {
        setEditingRule(rule);
        editForm.setData({
            days_before_due: String(rule.days_before_due),
            is_active: rule.is_active,
        });
        editForm.clearErrors();
    };

    const submitEdit = () => {
        if (!editingRule) {
            return;
        }

        editForm.submit(update({ financeDueReminderRule: editingRule.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setEditingRule(null);
                editForm.reset();
            },
        });
    };

    const handleToggleRule = (rule: ReminderRuleRow, checked: boolean) => {
        router.patch(
            update({ financeDueReminderRule: rule.id }).url,
            {
                days_before_due: rule.days_before_due,
                is_active: checked,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    const removeRule = (rule: ReminderRuleRow) => {
        if (!confirm(`Delete reminder rule: ${rule.label}?`)) {
            return;
        }

        router.delete(destroy({ financeDueReminderRule: rule.id }).url, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Due Reminder Settings" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Auto Due Reminder Rules</CardTitle>
                            <Button onClick={openCreateDialog}>
                                <Plus className="size-4" />
                                Add Rule
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3 p-4">
                        {rules.map((rule) => (
                            <div
                                key={rule.id}
                                className="flex items-center justify-between rounded-md border p-3"
                            >
                                <div className="space-y-1">
                                    <p className="text-sm font-medium">
                                        {rule.label}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Sent {rule.dispatch_count} time
                                        {rule.dispatch_count === 1 ? '' : 's'} •
                                        Last sent {formatDateTime(rule.last_sent_at)}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Switch
                                        checked={rule.is_active}
                                        onCheckedChange={(checked) =>
                                            handleToggleRule(rule, checked)
                                        }
                                    />
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        onClick={() => openEditDialog(rule)}
                                    >
                                        <Pencil className="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        onClick={() => removeRule(rule)}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}

                        {rules.length === 0 && (
                            <div className="rounded-md border py-8 text-center text-sm text-muted-foreground">
                                No reminder rules yet.
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Dialog
                    open={isCreateDialogOpen}
                    onOpenChange={setIsCreateDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add Reminder Rule</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label htmlFor="create-days-before-due">
                                    N Days Before Due
                                </Label>
                                <Input
                                    id="create-days-before-due"
                                    type="number"
                                    min="0"
                                    max="60"
                                    value={createForm.data.days_before_due}
                                    onChange={(event) =>
                                        createForm.setData(
                                            'days_before_due',
                                            event.target.value,
                                        )
                                    }
                                />
                                {createForm.errors.days_before_due && (
                                    <p className="text-sm text-destructive">
                                        {createForm.errors.days_before_due}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center justify-between rounded-md border p-3">
                                <p className="text-sm font-medium">
                                    Active after save
                                </p>
                                <Switch
                                    checked={createForm.data.is_active}
                                    onCheckedChange={(checked) =>
                                        createForm.setData('is_active', checked)
                                    }
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsCreateDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitCreate}
                                disabled={createForm.processing}
                            >
                                Save Rule
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={!!editingRule}
                    onOpenChange={(open) => {
                        if (!open) {
                            setEditingRule(null);
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Reminder Rule</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-2">
                            <div className="space-y-2">
                                <Label htmlFor="edit-days-before-due">
                                    N Days Before Due
                                </Label>
                                <Input
                                    id="edit-days-before-due"
                                    type="number"
                                    min="0"
                                    max="60"
                                    value={editForm.data.days_before_due}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'days_before_due',
                                            event.target.value,
                                        )
                                    }
                                />
                                {editForm.errors.days_before_due && (
                                    <p className="text-sm text-destructive">
                                        {editForm.errors.days_before_due}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center justify-between rounded-md border p-3">
                                <p className="text-sm font-medium">Rule Active</p>
                                <Switch
                                    checked={editForm.data.is_active}
                                    onCheckedChange={(checked) =>
                                        editForm.setData('is_active', checked)
                                    }
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setEditingRule(null)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={submitEdit}
                                disabled={editForm.processing}
                            >
                                Update Rule
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
