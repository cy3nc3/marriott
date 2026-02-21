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
        title: 'Student Dashboard',
        href: dashboard().url,
    },
];

type LearningSummary = {
    current_or_upcoming_class: string;
    general_average: string;
    general_average_trend: number | null;
    latest_score: string;
    recent_score_average: string;
    recent_score_trend_delta: number | null;
    recent_score_records_count: number;
    upcoming_items_count: number;
};

interface Props {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    action_links: DashboardActionLink[];
    learning_summary: LearningSummary;
}

export default function Dashboard({
    kpis,
    alerts,
    trends,
    action_links,
    learning_summary,
}: Props) {
    const generalAverageTrendLabel =
        learning_summary.general_average_trend === null
            ? 'Trend unavailable'
            : learning_summary.general_average_trend >= 0
              ? `+${learning_summary.general_average_trend.toFixed(2)}`
              : learning_summary.general_average_trend.toFixed(2);

    const recentScoreTrendLabel =
        learning_summary.recent_score_trend_delta === null
            ? 'Trend unavailable'
            : learning_summary.recent_score_trend_delta >= 0
              ? `+${learning_summary.recent_score_trend_delta.toFixed(2)}`
              : learning_summary.recent_score_trend_delta.toFixed(2);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Dashboard" />
            <DashboardAnalyticsPanel
                kpis={kpis}
                alerts={alerts}
                trends={trends}
                actionLinks={action_links}
            >
                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">
                            Learning Summary
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 pt-4 md:grid-cols-4">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                General Average Trend
                            </p>
                            <p className="text-xl font-semibold">
                                {generalAverageTrendLabel}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Recent Score Average
                            </p>
                            <p className="text-xl font-semibold">
                                {learning_summary.recent_score_average}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {learning_summary.recent_score_records_count}{' '}
                                recent assessment(s)
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Recent Score Trend
                            </p>
                            <p className="text-xl font-semibold">
                                {recentScoreTrendLabel}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Upcoming Class Items
                            </p>
                            <p className="text-xl font-semibold">
                                {learning_summary.upcoming_items_count}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </DashboardAnalyticsPanel>
        </AppLayout>
    );
}
