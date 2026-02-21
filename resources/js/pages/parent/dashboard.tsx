import { Head } from '@inertiajs/react';
import { DashboardAnalyticsPanel } from '@/components/dashboard/analytics-panel';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
        title: 'Parent Dashboard',
        href: dashboard().url,
    },
];

type ChildContext = {
    student_name: string | null;
    section_label: string;
    adviser_name: string;
    next_due_label: string;
    due_risk_level: string;
    due_risk_rate: string;
};

interface Props {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    action_links: DashboardActionLink[];
    child_context: ChildContext;
}

export default function Dashboard({
    kpis,
    alerts,
    trends,
    action_links,
    child_context,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Parent Dashboard" />
            <DashboardAnalyticsPanel
                kpis={kpis}
                alerts={alerts}
                trends={trends}
                actionLinks={action_links}
            >
                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">
                            Child Context
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 pt-4 md:grid-cols-2">
                        <div className="space-y-1">
                            <p className="text-xs text-muted-foreground">
                                Student
                            </p>
                            <p className="text-sm font-semibold">
                                {child_context.student_name ?? 'No linked student'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {child_context.section_label}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Adviser: {child_context.adviser_name}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-xs text-muted-foreground">
                                Next Due
                            </p>
                            <p className="text-sm font-semibold">
                                {child_context.next_due_label}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Due Risk
                            </p>
                            <p className="text-sm font-semibold">
                                {child_context.due_risk_level}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {child_context.due_risk_rate}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </DashboardAnalyticsPanel>
        </AppLayout>
    );
}
