import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldGroup,
    FieldLabel,
    FieldDescription as UIFieldDescription,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Save, School, Cog, Image as ImageIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Settings',
        href: '/super-admin/system-settings',
    },
];

export default function SystemSettings() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="flex justify-between items-center">
                    <h2 className="text-2xl font-black tracking-tight">Configuration</h2>
                    <Button className="gap-2">
                        <Save className="size-4" />
                        Save All Changes
                    </Button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* School Identity */}
                    <Card>
                        <CardHeader className="bg-muted/30 border-b">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <School className="size-5 text-primary" />
                                School Identity
                            </CardTitle>
                            <CardDescription>Official details for reports and documents</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <FieldGroup className="gap-6">
                                <Field>
                                    <FieldLabel htmlFor="school-name">School Name</FieldLabel>
                                    <Input id="school-name" placeholder="Marriott School System" defaultValue="Marriott School System" />
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="school-id">School ID (DepEd)</FieldLabel>
                                    <Input id="school-id" placeholder="123456" defaultValue="123456" />
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="address">Official Address</FieldLabel>
                                    <Textarea 
                                        id="address" 
                                        placeholder="123 Tolentino Street, Quezon City" 
                                        defaultValue="123 Tolentino Street, Quezon City"
                                        className="min-h-[100px]"
                                    />
                                </Field>
                            </FieldGroup>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        {/* System Controls */}
                        <Card>
                            <CardHeader className="bg-muted/30 border-b">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Cog className="size-5 text-primary" />
                                    System Controls
                                </CardTitle>
                                <CardDescription>Global access and maintenance toggles</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <FieldGroup className="gap-4">
                                    <div className="flex items-center justify-between p-4 bg-muted/30 rounded-lg border">
                                        <div className="space-y-0.5">
                                            <p className="text-sm font-bold uppercase tracking-wider">Maintenance Mode</p>
                                            <p className="text-xs text-muted-foreground">Disable access for all non-admin users</p>
                                        </div>
                                        <Switch id="maintenance-mode" />
                                    </div>
                                    <div className="flex items-center justify-between p-4 bg-muted/30 rounded-lg border">
                                        <div className="space-y-0.5">
                                            <p className="text-sm font-bold uppercase tracking-wider">Parent Portal</p>
                                            <p className="text-xs text-muted-foreground">Allow parents to view grades and billing</p>
                                        </div>
                                        <Switch id="parent-access" checked={true} />
                                    </div>
                                </FieldGroup>
                            </CardContent>
                        </Card>

                        {/* Branding */}
                        <Card>
                            <CardHeader className="bg-muted/30 border-b">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <ImageIcon className="size-5 text-primary" />
                                    Visual Identity
                                </CardTitle>
                                <CardDescription>Logo and report headers</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-6">
                                <Field>
                                    <FieldLabel htmlFor="school-logo">School Logo</FieldLabel>
                                    <Input id="school-logo" type="file" accept="image/*" className="cursor-pointer" />
                                    <UIFieldDescription>Recommended size: 512x512px (PNG/JPG)</UIFieldDescription>
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="school-header">Report Header</FieldLabel>
                                    <Input id="school-header" type="file" accept="image/*" className="cursor-pointer" />
                                    <UIFieldDescription>Used for SF9 and SF10 top banners</UIFieldDescription>
                                </Field>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
