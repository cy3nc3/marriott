import { Head } from '@inertiajs/react';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Account settings',
        href: '/settings/account',
    },
];

export default function Account() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Account settings" />

            <h1 className="sr-only">Account Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Account Management"
                        description="Manage your account level actions and data"
                    />

                    <div className="rounded-lg border border-destructive/20 bg-destructive/5 p-6">
                        <DeleteUser />
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
