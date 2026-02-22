import { Link } from '@inertiajs/react';
import { AlertCircle, ArrowRight, Bell, TrendingUp } from 'lucide-react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    Cell,
    CartesianGrid,
    Line,
    LineChart,
    Pie,
    PieChart,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
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
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const LIS_DISTRIBUTION_COLORS: Record<string, string> = {
    synced: 'var(--primary)',
    pending: 'var(--chart-1)',
    errors: 'var(--destructive)',
};

const PAYMENT_METHOD_COLORS: Record<string, string> = {
    cash: 'var(--primary)',
    'e-wallet': 'var(--chart-1)',
    'bank transfer': 'var(--chart-3)',
    check: 'var(--chart-4)',
    other: 'var(--chart-5)',
};

const resolveSeriesColor = (index: number): string => {
    return CHART_COLORS[index % CHART_COLORS.length];
};

const resolvePieSliceColor = (
    trend: DashboardTrend,
    category: string,
    index: number,
): string => {
    const normalizedCategory = category.trim().toLowerCase();

    if (trend.id === 'lis-sync-distribution') {
        return (
            LIS_DISTRIBUTION_COLORS[normalizedCategory] ??
            resolveSeriesColor(index)
        );
    }

    if (trend.id === 'payment-method-mix') {
        return (
            PAYMENT_METHOD_COLORS[normalizedCategory] ??
            resolveSeriesColor(index)
        );
    }

    return resolveSeriesColor(index);
};

const buildSeriesGradientId = (trendId: string, seriesKey: string): string => {
    const normalize = (value: string): string => {
        return value.replace(/[^a-zA-Z0-9_-]/g, '-');
    };

    return `gradient-${normalize(trendId)}-${normalize(seriesKey)}`;
};

const buildTrendChartConfig = (trend: DashboardTrend): ChartConfig => {
    const chartConfig: ChartConfig = {};

    trend.chart?.series.forEach((series, index) => {
        chartConfig[series.key] = {
            label: series.label,
            color: resolveSeriesColor(index),
        };
    });

    return chartConfig;
};

const resolveTrendDisplay = (
    trend: DashboardTrend,
): 'list' | 'line' | 'bar' | 'area' | 'pie' => {
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

const renderTrendTooltipContent = (trend: DashboardTrend) => {
    if (trend.id === 'grade-level-enrollment') {
        return (
            <ChartTooltipContent
                indicator="dot"
                labelFormatter={(label, payload) => {
                    const payloadRow = payload?.[0]?.payload as
                        | {
                              total?: number;
                              male?: number;
                              female?: number;
                          }
                        | undefined;
                    const resolvedTotal =
                        typeof payloadRow?.total === 'number'
                            ? payloadRow.total
                            : (payloadRow?.male ?? 0) +
                              (payloadRow?.female ?? 0);
                    const displayLabel =
                        typeof label === 'string' || typeof label === 'number'
                            ? String(label)
                            : '';

                    return `${displayLabel} (Total: ${resolvedTotal.toLocaleString()})`;
                }}
            />
        );
    }

    return <ChartTooltipContent indicator="dot" />;
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

    const chartConfig = buildTrendChartConfig(trend);

    return (
        <div className="h-56 w-full">
            <ChartContainer
                config={chartConfig}
                className="!aspect-auto h-full w-full !justify-start"
            >
                <LineChart
                    accessibilityLayer
                    data={trend.chart.rows}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                >
                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                    <XAxis
                        dataKey={trend.chart.x_key}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                    />
                    <YAxis
                        width={36}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={6}
                    />
                    <ChartTooltip content={renderTrendTooltipContent(trend)} />
                    <ChartLegend content={<ChartLegendContent />} />
                    {trend.chart.series.map((series) => (
                        <Line
                            key={`${trend.id}-${series.key}`}
                            type="monotone"
                            dataKey={series.key}
                            name={series.label}
                            stroke={`var(--color-${series.key})`}
                            strokeWidth={2}
                            dot={{ r: 3 }}
                            strokeDasharray={series.dashed ? '5 5' : undefined}
                            connectNulls
                        />
                    ))}
                </LineChart>
            </ChartContainer>
        </div>
    );
};

const renderBarTrend = (trend: DashboardTrend) => {
    if (!trend.chart || !hasChartData(trend)) {
        return renderListTrend(trend);
    }

    const chartConfig = buildTrendChartConfig(trend);

    return (
        <div className="h-56 w-full">
            <ChartContainer
                config={chartConfig}
                className="!aspect-auto h-full w-full !justify-start"
            >
                <BarChart
                    accessibilityLayer
                    data={trend.chart.rows}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                >
                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                    <XAxis
                        dataKey={trend.chart.x_key}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                    />
                    <YAxis
                        width={36}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={6}
                    />
                    <ChartTooltip content={renderTrendTooltipContent(trend)} />
                    <ChartLegend content={<ChartLegendContent />} />
                    {trend.chart.series.map((series) => (
                        <Bar
                            key={`${trend.id}-${series.key}`}
                            dataKey={series.key}
                            name={series.label}
                            fill={`var(--color-${series.key})`}
                            radius={[4, 4, 0, 0]}
                        />
                    ))}
                </BarChart>
            </ChartContainer>
        </div>
    );
};

const renderAreaTrend = (trend: DashboardTrend) => {
    if (!trend.chart || !hasChartData(trend)) {
        return renderListTrend(trend);
    }

    const chartConfig = buildTrendChartConfig(trend);

    return (
        <div className="h-56 w-full">
            <ChartContainer
                config={chartConfig}
                className="!aspect-auto h-full w-full !justify-start"
            >
                <AreaChart
                    accessibilityLayer
                    data={trend.chart.rows}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                >
                    <defs>
                        {trend.chart.series.map((series) => {
                            const gradientId = buildSeriesGradientId(
                                trend.id,
                                series.key,
                            );
                            const topOpacity = series.dashed ? 0.25 : 0.35;

                            return (
                                <linearGradient
                                    key={gradientId}
                                    id={gradientId}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor={`var(--color-${series.key})`}
                                        stopOpacity={topOpacity}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor={`var(--color-${series.key})`}
                                        stopOpacity={0.05}
                                    />
                                </linearGradient>
                            );
                        })}
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                    <XAxis
                        dataKey={trend.chart.x_key}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                    />
                    <YAxis
                        width={36}
                        fontSize={12}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={6}
                    />
                    <ChartTooltip content={renderTrendTooltipContent(trend)} />
                    <ChartLegend content={<ChartLegendContent />} />
                    {trend.chart.series.map((series) => (
                        <Area
                            key={`${trend.id}-${series.key}`}
                            type="monotone"
                            dataKey={series.key}
                            name={series.label}
                            stroke={`var(--color-${series.key})`}
                            fill={`url(#${buildSeriesGradientId(trend.id, series.key)})`}
                            fillOpacity={1}
                            strokeWidth={2}
                            strokeDasharray={series.dashed ? '5 5' : undefined}
                            activeDot={{ r: 4 }}
                            connectNulls
                        />
                    ))}
                </AreaChart>
            </ChartContainer>
        </div>
    );
};

const renderPieTrend = (trend: DashboardTrend) => {
    if (!trend.chart || !hasChartData(trend)) {
        return renderListTrend(trend);
    }

    const chart = trend.chart;
    const chartConfig = buildTrendChartConfig(trend);
    const valueKey = chart.series[0]?.key;

    if (!valueKey) {
        return renderListTrend(trend);
    }

    return (
        <div className="h-56 w-full">
            <ChartContainer
                config={chartConfig}
                className="!aspect-auto h-full w-full !justify-start"
            >
                <PieChart margin={{ top: 8, right: 8, bottom: 8, left: 8 }}>
                    <ChartTooltip
                        content={
                            <ChartTooltipContent
                                indicator="dot"
                                nameKey={trend.chart.x_key}
                            />
                        }
                    />
                    <Pie
                        data={chart.rows}
                        dataKey={valueKey}
                        nameKey={chart.x_key}
                        cx="50%"
                        cy="50%"
                        outerRadius={80}
                        strokeWidth={1}
                    >
                        {chart.rows.map((row, index) => {
                            const category = String(row[chart.x_key] ?? index);

                            return (
                                <Cell
                                    key={`${trend.id}-${category}`}
                                    fill={resolvePieSliceColor(
                                        trend,
                                        category,
                                        index,
                                    )}
                                />
                            );
                        })}
                    </Pie>
                    <ChartLegend
                        content={<ChartLegendContent nameKey={chart.x_key} />}
                    />
                </PieChart>
            </ChartContainer>
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

    if (display === 'area') {
        return renderAreaTrend(trend);
    }

    if (display === 'pie') {
        return renderPieTrend(trend);
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
                            <p className="text-2xl font-semibold">
                                {kpi.value}
                            </p>
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
                        <CardTitle className="text-base">
                            Action Links
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-2 pt-4">
                        {actionLinks.map((actionLink) => (
                            <Button
                                key={actionLink.id}
                                asChild
                                variant="outline"
                            >
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
