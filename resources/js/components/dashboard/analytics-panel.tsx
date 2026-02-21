import { Link } from '@inertiajs/react';
import { AlertCircle, ArrowRight, Bell, TrendingUp } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type {
    DashboardActionLink,
    DashboardAlert,
    DashboardTrend,
    DashboardTrendPoint,
    DashboardKpi,
} from '@/types';

interface DashboardAnalyticsPanelProps {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    actionLinks: DashboardActionLink[];
    children?: React.ReactNode;
}

const formatTrendValue = (value: DashboardTrendPoint['value']) => {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'number') {
        return Number.isInteger(value) ? value.toString() : value.toFixed(2);
    }

    return value;
};

const resolveAlertBadgeVariant = (severity: DashboardAlert['severity']) => {
    if (severity === 'critical') {
        return 'destructive';
    }

    if (severity === 'warning') {
        return 'secondary';
    }

    return 'outline';
};

const CHART_COLORS = [
    '#2563eb',
    '#f59e0b',
    '#10b981',
    '#a855f7',
    '#ef4444',
];

const resolveTrendDisplay = (trend: DashboardTrend): 'list' | 'line' | 'bar' => {
    return trend.display ?? 'list';
};

const hasChartData = (trend: DashboardTrend): boolean => {
    return Boolean(
        trend.chart &&
            trend.chart.rows.length > 0 &&
            trend.chart.series.length > 0 &&
            trend.chart.x_key,
    );
};

const renderListTrend = (trend: DashboardTrend) => {
    const points = trend.points ?? [];

    if (points.length === 0) {
        return (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <AlertCircle className="size-4" />
                <span>No trend points available.</span>
            </div>
        );
    }

    return points.map((point, index) => (
        <div
            key={`${trend.id}-${point.label}-${index}`}
            className="flex items-center justify-between text-sm"
        >
            <span className="text-muted-foreground">{point.label}</span>
            <span className="font-medium">{formatTrendValue(point.value)}</span>
        </div>
    ));
};

const renderLineTrend = (trend: DashboardTrend) => {
    if (!trend.chart || !hasChartData(trend)) {
        return renderListTrend(trend);
    }

    return (
        <div className="h-56 w-full">
            <ResponsiveContainer width="100%" height="100%">
                <LineChart data={trend.chart.rows}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                    <XAxis
                        dataKey={trend.chart.x_key}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                    />
                    <YAxis
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                    />
                    <Tooltip
                        content={({ active, payload, label }) => {
                            if (!active || !payload || payload.length === 0) {
                                return null;
                            }

                            return (
                                <div className="rounded-md border bg-background p-2 text-xs shadow-sm">
                                    <p className="mb-1 font-medium">{label}</p>
                                    <div className="space-y-1">
                                        {payload.map((row) => (
                                            <div
                                                key={row.dataKey as string}
                                                className="flex items-center justify-between gap-4"
                                            >
                                                <span className="text-muted-foreground">
                                                    {row.name}
                                                </span>
                                                <span className="font-medium">
                                                    {formatTrendValue(
                                                        row.value as
                                                            | string
                                                            | number
                                                            | null,
                                                    )}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        }}
                    />
                    <Legend />
                    {trend.chart.series.map((series, index) => (
                        <Line
                            key={`${trend.id}-${series.key}`}
                            type="monotone"
                            dataKey={series.key}
                            name={series.label}
                            stroke={CHART_COLORS[index % CHART_COLORS.length]}
                            strokeWidth={2}
                            dot={{ r: 3 }}
                            strokeDasharray={series.dashed ? '5 5' : undefined}
                            connectNulls
                        />
                    ))}
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
};

const renderBarTrend = (trend: DashboardTrend) => {
    if (!trend.chart || !hasChartData(trend)) {
        return renderListTrend(trend);
    }

    return (
        <div className="h-56 w-full">
            <ResponsiveContainer width="100%" height="100%">
                <BarChart data={trend.chart.rows}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                    <XAxis
                        dataKey={trend.chart.x_key}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                    />
                    <YAxis
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                    />
                    <Tooltip
                        content={({ active, payload, label }) => {
                            if (!active || !payload || payload.length === 0) {
                                return null;
                            }

                            return (
                                <div className="rounded-md border bg-background p-2 text-xs shadow-sm">
                                    <p className="mb-1 font-medium">{label}</p>
                                    <div className="space-y-1">
                                        {payload.map((row) => (
                                            <div
                                                key={row.dataKey as string}
                                                className="flex items-center justify-between gap-4"
                                            >
                                                <span className="text-muted-foreground">
                                                    {row.name}
                                                </span>
                                                <span className="font-medium">
                                                    {formatTrendValue(
                                                        row.value as
                                                            | string
                                                            | number
                                                            | null,
                                                    )}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        }}
                    />
                    <Legend />
                    {trend.chart.series.map((series, index) => (
                        <Bar
                            key={`${trend.id}-${series.key}`}
                            dataKey={series.key}
                            name={series.label}
                            fill={CHART_COLORS[index % CHART_COLORS.length]}
                            radius={[4, 4, 0, 0]}
                        />
                    ))}
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
};

const renderTrendBody = (trend: DashboardTrend) => {
    const display = resolveTrendDisplay(trend);
    if (display === 'line') {
        return renderLineTrend(trend);
    }

    if (display === 'bar') {
        return renderBarTrend(trend);
    }

    return renderListTrend(trend);
};

export function DashboardAnalyticsPanel({
    kpis,
    alerts,
    trends,
    actionLinks,
    children,
}: DashboardAnalyticsPanelProps) {
    return (
        <div className="flex flex-col gap-6">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {kpis.map((kpi) => (
                    <Card key={kpi.id}>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-sm font-medium">
                                {kpi.label}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 pt-4">
                            <p className="text-2xl font-semibold">{kpi.value}</p>
                            <p className="text-xs text-muted-foreground">
                                {kpi.meta ?? ''}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {children}

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader className="flex flex-row items-center gap-2 border-b py-4">
                        <Bell className="size-4 text-muted-foreground" />
                        <CardTitle className="text-base">Alerts</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 pt-4">
                        {alerts.map((alert) => (
                            <div
                                key={alert.id}
                                className="rounded-md border p-3"
                            >
                                <div className="mb-2 flex items-center justify-between gap-2">
                                    <p className="text-sm font-medium">
                                        {alert.title}
                                    </p>
                                    <Badge
                                        variant={resolveAlertBadgeVariant(
                                            alert.severity,
                                        )}
                                    >
                                        {alert.severity}
                                    </Badge>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {alert.message}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center gap-2 border-b py-4">
                        <ArrowRight className="size-4 text-muted-foreground" />
                        <CardTitle className="text-base">Action Links</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-2 pt-4">
                        {actionLinks.map((actionLink) => (
                            <Button key={actionLink.id} asChild variant="outline">
                                <Link href={actionLink.href}>
                                    {actionLink.label}
                                </Link>
                            </Button>
                        ))}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center gap-2 border-b py-4">
                    <TrendingUp className="size-4 text-muted-foreground" />
                    <CardTitle className="text-base">Trends</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 pt-4 md:grid-cols-2">
                    {trends.map((trend) => (
                        <Card key={trend.id}>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-sm font-medium">
                                    {trend.label}
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    {trend.summary}
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-2 pt-4">
                                {renderTrendBody(trend)}
                            </CardContent>
                        </Card>
                    ))}
                </CardContent>
            </Card>
        </div>
    );
}
