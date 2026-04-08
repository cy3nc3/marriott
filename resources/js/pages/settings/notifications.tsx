import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { Bell, Mail, Monitor, Loader2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface NotificationPreferences {
    email: {
        announcements: boolean;
        grade_submissions: boolean;
        system_alerts: boolean;
    };
    in_app: {
        announcements: boolean;
        grade_submissions: boolean;
        system_alerts: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: '/settings/notifications',
    },
];

export default function Notifications({ settings }: { settings: NotificationPreferences }) {
    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        settings: settings,
    });

    const updateSetting = (channel: keyof NotificationPreferences, type: string, value: boolean) => {
        setData('settings', {
            ...data.settings,
            [channel]: {
                ...data.settings[channel],
                [type]: value,
            },
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('notifications.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification Settings" />

            <h1 className="sr-only">Notification Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Notification Management"
                        description="Choose how you want to be notified about updates and activity"
                    />

                    <form onSubmit={submit} className="space-y-8">
                        <div className="space-y-6">
                            {/* Email Notifications */}
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                    <h3 className="text-sm font-medium">Email Notifications</h3>
                                </div>
                                <div className="space-y-4 rounded-lg border p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="email_announcements">Announcements</Label>
                                            <p className="text-xs text-muted-foreground">Receive emails for new school-wide announcements</p>
                                        </div>
                                        <Switch
                                            id="email_announcements"
                                            checked={data.settings.email.announcements}
                                            onCheckedChange={(val) => updateSetting('email', 'announcements', val)}
                                        />
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="email_grades">Grade Submissions</Label>
                                            <p className="text-xs text-muted-foreground">Get notified when new grades are submitted or updated</p>
                                        </div>
                                        <Switch
                                            id="email_grades"
                                            checked={data.settings.email.grade_submissions}
                                            onCheckedChange={(val) => updateSetting('email', 'grade_submissions', val)}
                                        />
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="email_alerts">System Alerts</Label>
                                            <p className="text-xs text-muted-foreground">Important security and system maintenance alerts</p>
                                        </div>
                                        <Switch
                                            id="email_alerts"
                                            checked={data.settings.email.system_alerts}
                                            onCheckedChange={(val) => updateSetting('email', 'system_alerts', val)}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* In-App Notifications */}
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Monitor className="h-4 w-4 text-muted-foreground" />
                                    <h3 className="text-sm font-medium">Desktop/Push Notifications</h3>
                                </div>
                                <div className="space-y-4 rounded-lg border p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="app_announcements">Announcements</Label>
                                            <p className="text-xs text-muted-foreground">Show in-app alerts for new school-wide announcements</p>
                                        </div>
                                        <Switch
                                            id="app_announcements"
                                            checked={data.settings.in_app.announcements}
                                            onCheckedChange={(val) => updateSetting('in_app', 'announcements', val)}
                                        />
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="app_grades">Grade Submissions</Label>
                                            <p className="text-xs text-muted-foreground">Show in-app alerts for grade updates</p>
                                        </div>
                                        <Switch
                                            id="app_grades"
                                            checked={data.settings.in_app.grade_submissions}
                                            onCheckedChange={(val) => updateSetting('in_app', 'grade_submissions', val)}
                                        />
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="app_alerts">System Alerts</Label>
                                            <p className="text-xs text-muted-foreground">In-app alerts for security events</p>
                                        </div>
                                        <Switch
                                            id="app_alerts"
                                            checked={data.settings.in_app.system_alerts}
                                            onCheckedChange={(val) => updateSetting('in_app', 'system_alerts', val)}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Save Preferences
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Preferences updated</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
