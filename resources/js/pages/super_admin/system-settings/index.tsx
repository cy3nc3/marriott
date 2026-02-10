import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
    FieldTitle,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

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
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid h-full grid-cols-2 gap-6">
                    <Card>
                        <CardContent>
                            <FieldSet>
                                <FieldLegend>School Identity</FieldLegend>
                                {/* <FieldDescription>
                                This appears on invoices and emails.
                            </FieldDescription> */}
                                <FieldGroup>
                                    <Field>
                                        <FieldLabel htmlFor="school-name">
                                            School Name
                                        </FieldLabel>
                                        <Input
                                            id="school-name"
                                            autoComplete="off"
                                            placeholder="Marriott School"
                                        />
                                        {/* <FieldDescription>
                                        This appears on invoices and emails.
                                    </FieldDescription> */}
                                    </Field>
                                    <Field>
                                        <FieldLabel htmlFor="school-id">
                                            School ID
                                        </FieldLabel>
                                        <Input
                                            id="school-id"
                                            autoComplete="off"
                                            // aria-invalid
                                        />
                                        {/* <FieldError>
                                        Choose another username.
                                    </FieldError> */}
                                    </Field>
                                    <Field>
                                        {/* <Switch id="newsletter" /> */}
                                        <FieldLabel htmlFor="address">
                                            School Address
                                        </FieldLabel>
                                        <Textarea
                                            id="address"
                                            autoComplete="off"
                                            placeholder="123 Tolentino Street, Quezon City"
                                        />
                                    </Field>
                                </FieldGroup>
                            </FieldSet>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent>
                            <FieldSet>
                                <FieldLegend>System Settings</FieldLegend>
                                <FieldGroup>
                                    <Field orientation="horizontal">
                                        <FieldLabel htmlFor="maintenance-mode">
                                            Maintenance Mode
                                        </FieldLabel>
                                        <Switch id="maintenance-mode" />
                                    </Field>
                                    <Field orientation="horizontal">
                                        <FieldLabel htmlFor="parent-portal-access">
                                            Allow Parent Portal Access
                                        </FieldLabel>
                                        <Switch id="parent-portal-access" />
                                    </Field>
                                </FieldGroup>
                            </FieldSet>
                        </CardContent>
                    </Card>
                    <Card className="col-span-2">
                        <CardContent>
                            <FieldLegend className="mb-2">Branding</FieldLegend>
                            <FieldDescription className="mb-6">
                                Upload your school logo and header here. These
                                will appear on invoices and emails.
                            </FieldDescription>
                            <FieldGroup className="flex flex-row justify-between">
                                <FieldSet>
                                    <Field>
                                        <FieldLabel htmlFor="school-logo">
                                            School Logo
                                        </FieldLabel>
                                        <Input
                                            id="school-logo"
                                            type="file"
                                            accept="image/*"
                                        />
                                        <FieldDescription>
                                            Upload school logo here.
                                        </FieldDescription>
                                    </Field>
                                </FieldSet>

                                <FieldSet>
                                    <Field>
                                        <FieldLabel htmlFor="school-header">
                                            Header
                                        </FieldLabel>
                                        <Input
                                            id="school-header"
                                            type="file"
                                            accept="image/*"
                                        />
                                        <FieldDescription>
                                            Upload school header here.
                                        </FieldDescription>
                                    </Field>
                                </FieldSet>
                            </FieldGroup>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
