import { Head } from '@inertiajs/react';
import { ShieldCheck, Users, Activity, Settings2 } from 'lucide-react';
import { StatCard } from '@/components/dashboard/stat-card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Super Admin Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Super Admin Dashboard" />
            <div className="flex flex-col gap-4">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total Staff"
                        value="24"
                        description="Registered system users"
                        icon={Users}
                    />
                    <StatCard
                        title="System Status"
                        value="Online"
                        description="Operational"
                        icon={Activity}
                        className="text-emerald-600"
                    />
                    <StatCard
                        title="Database"
                        value="Healthy"
                        description="Sync synchronized"
                        icon={ShieldCheck}
                    />
                    <StatCard
                        title="Active Settings"
                        value="Configured"
                        description="System-wide defaults"
                        icon={Settings2}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                    <div className="col-span-4 relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                        <div className="absolute inset-0 p-6">
                            <h3 className="text-sm font-black uppercase tracking-wider text-muted-foreground mb-4">System Activity</h3>
                            <PlaceholderPattern className="size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />
                        </div>
                    </div>
                    <div className="col-span-3 relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                        <div className="absolute inset-0 p-6">
                            <h3 className="text-sm font-black uppercase tracking-wider text-muted-foreground mb-4">User Distribution</h3>
                            <PlaceholderPattern className="size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                     <div className="col-span-3 relative min-h-[300px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                        <div className="absolute inset-0 p-6">
                            <h3 className="text-sm font-black uppercase tracking-wider text-muted-foreground mb-4">Audit Logs</h3>
                            <PlaceholderPattern className="size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />
                        </div>
                     </div>
                </div>
            </div>
        </AppLayout>
    );
}
