import { Head } from '@inertiajs/react';
import { DashboardAnalyticsPanel } from '@/components/dashboard/analytics-panel';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    DashboardActionLink,
    DashboardAlert,
    DashboardKpi,
    DashboardTrend,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Registrar Dashboard',
        href: dashboard().url,
    },
];

interface Props {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    action_links: DashboardActionLink[];
}

export default function Dashboard({ kpis, alerts, trends, action_links }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrar Dashboard" />
            <DashboardAnalyticsPanel
                kpis={kpis}
                alerts={alerts}
                trends={trends}
                actionLinks={action_links}
            />
        </AppLayout>
    );
}
