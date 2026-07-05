import { Head } from '@inertiajs/react';
import { DashboardAnalyticsPanel } from '@/components/dashboard/analytics-panel';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    DashboardActionLink,
    DashboardActionQueueItem,
    DashboardAlert,
    DashboardDecisionCard,
    DashboardKpi,
    DashboardTrend,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance Dashboard',
        href: dashboard().url,
    },
];

interface Props {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    action_links: DashboardActionLink[];
    action_queue?: DashboardActionQueueItem[];
    decision_cards?: DashboardDecisionCard[];
}

export default function Dashboard({ kpis, alerts, trends, action_links, action_queue, decision_cards }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Finance Dashboard" />
            <DashboardAnalyticsPanel
                kpis={kpis}
                alerts={alerts}
                trends={trends}
                actionLinks={action_links}
                actionQueue={action_queue}
                decisionCards={decision_cards}
            />
        </AppLayout>
    );
}
