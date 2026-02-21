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
        title: 'Teacher Dashboard',
        href: dashboard().url,
    },
];

type QuarterGradeCompletion = {
    total_classes: number;
    finalized_classes: number;
    unfinalized_classes: number;
};

interface Props {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    action_links: DashboardActionLink[];
    quarter_grade_completion: QuarterGradeCompletion;
}

export default function Dashboard({
    kpis,
    alerts,
    trends,
    action_links,
    quarter_grade_completion,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Dashboard" />
            <DashboardAnalyticsPanel
                kpis={kpis}
                alerts={alerts}
                trends={trends}
                actionLinks={action_links}
            >
                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">
                            Quarter Grade Completion
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 pt-4 md:grid-cols-3">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Total Classes
                            </p>
                            <p className="text-2xl font-semibold">
                                {quarter_grade_completion.total_classes}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Finalized Classes
                            </p>
                            <p className="text-2xl font-semibold">
                                {quarter_grade_completion.finalized_classes}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Unfinalized Classes
                            </p>
                            <p className="text-2xl font-semibold">
                                {quarter_grade_completion.unfinalized_classes}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </DashboardAnalyticsPanel>
        </AppLayout>
    );
}
