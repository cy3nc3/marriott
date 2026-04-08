import { Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    AlertTriangle,
    ArrowRight,
    Bell,
    BookOpen,
    CheckCircle2,
    ClipboardList,
    Clock,
    CloudOff,
    DatabaseBackup,
    GraduationCap,
    HardDrive,
    LayoutDashboard,
    ListChecks,
    Percent,
    PiggyBank,
    RefreshCw,
    ShieldAlert,
    TrendingDown,
    TrendingUp,
    Users,
    Wallet,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import type {
    DashboardActionLink,
    DashboardAlert,
    DashboardTrend,
    DashboardTrendPoint,
    DashboardKpi,
    SharedData,
} from '@/types';

interface DashboardAnalyticsPanelProps {
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    actionLinks: DashboardActionLink[];
    children?: React.ReactNode;
}

// ─── KPI Icon Registry ──────────────────────────────────────────────────────

type KpiIconConfig = {
    icon: LucideIcon;
    color: string;
};

const KPI_ICON_MAP: Record<string, KpiIconConfig> = {
    // Admin
    'enrollment-yoy-growth':    { icon: TrendingUp,       color: 'text-emerald-500' },
    'unassigned-subjects':      { icon: BookOpen,          color: 'text-amber-500' },
    'sections-without-adviser': { icon: Users,             color: 'text-orange-500' },
    'schedule-conflicts':       { icon: AlertTriangle,     color: 'text-red-500' },
    // Finance
    'collection-efficiency':    { icon: Percent,           color: 'text-emerald-500' },
    'outstanding-receivables':  { icon: Wallet,            color: 'text-amber-500' },
    'overdue-concentration':    { icon: TrendingDown,      color: 'text-red-500' },
    'next-month-forecast':      { icon: PiggyBank,         color: 'text-sky-500' },
    // Registrar
    'intake-queue':             { icon: ClipboardList,     color: 'text-sky-500' },
    'cashier-pipeline':         { icon: ListChecks,        color: 'text-violet-500' },
    'lis-sync-rate':            { icon: RefreshCw,         color: 'text-emerald-500' },
    'sync-error-backlog':       { icon: CloudOff,          color: 'text-red-500' },
    // Super Admin
    'system-health':            { icon: HardDrive,         color: 'text-emerald-500' },
    'account-governance':       { icon: Users,             color: 'text-sky-500' },
    'audit-risk':               { icon: ShieldAlert,       color: 'text-red-500' },
    'backup-freshness':         { icon: DatabaseBackup,    color: 'text-amber-500' },
    // Teacher
    'classes-today':            { icon: Clock,             color: 'text-sky-500' },
    'quarter-grade-completion': { icon: CheckCircle2,      color: 'text-emerald-500' },
    'grade-rows-pending':       { icon: GraduationCap,     color: 'text-amber-500' },
    'at-risk-learners':         { icon: AlertTriangle,     color: 'text-red-500' },
};

const DEFAULT_KPI_ICON: KpiIconConfig = { icon: LayoutDashboard, color: 'text-primary' };

const resolveKpiIcon = (kpiId: string): KpiIconConfig =>
    KPI_ICON_MAP[kpiId] ?? DEFAULT_KPI_ICON;

// ─────────────────────────────────────────────────────────────────────────────

const formatTrendValue = (value: DashboardTrendPoint['value']) => {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'number') {
        return Number.isInteger(value) ? value.toString() : value.toFixed(2);
    }

    return value;
};

const resolveAlertBadgeClass = (severity: DashboardAlert['severity']) => {
    if (severity === 'critical') {
        return 'bg-red-500/15 text-red-700 hover:bg-red-500/25 dark:text-red-400 border-red-200 dark:border-red-800';
    }

    if (severity === 'warning') {
        return 'bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400 border-amber-200 dark:border-amber-800';
    }

    return 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
};

const capitalize = (str: string) =>
    str.charAt(0).toUpperCase() + str.slice(1);

const CHART_COLORS = [
    '#6366f1', // Indigo 500
    '#10b981', // Emerald 500
    '#f59e0b', // Amber 500
    '#f43f5e', // Rose 500
    '#8b5cf6', // Violet 500
];

const LIS_DISTRIBUTION_COLORS: Record<string, string> = {
    synced: '#10b981',
    pending: '#f59e0b',
    errors: '#f43f5e',
};

const PAYMENT_METHOD_COLORS: Record<string, string> = {
    cash: '#10b981',
    'e-wallet': '#3b82f6',
    'bank transfer': '#8b5cf6',
    check: '#f59e0b',
    other: '#94a3b8',
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

type ListTrendRenderOptions = {
    isHandheld: boolean;
    isExpanded: boolean;
    onToggle: () => void;
};

const renderListTrend = (
    trend: DashboardTrend,
    options: ListTrendRenderOptions,
) => {
    const points = trend.points ?? [];

    if (points.length === 0) {
        return (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <AlertCircle className="size-4" />
                <span>No trend points available.</span>
            </div>
        );
    }

    const visiblePoints =
        options.isHandheld && !options.isExpanded
            ? points.slice(0, 4)
            : points;
    const hasMorePoints = options.isHandheld && points.length > 4;

    return (
        <div className="space-y-2">
            {visiblePoints.map((point, index) => (
                <div
                    key={`${trend.id}-${point.label}-${index}`}
                    className="flex items-center justify-between text-sm"
                >
                    <span className="text-muted-foreground">{point.label}</span>
                    <span className="font-medium">
                        {formatTrendValue(point.value)}
                    </span>
                </div>
            ))}

            {hasMorePoints ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-0 text-xs"
                    onClick={options.onToggle}
                >
                    {options.isExpanded ? 'Show less' : 'Show more'}
                </Button>
            ) : null}
        </div>
    );
};

const renderLineTrend = (
    trend: DashboardTrend,
    isHandheld: boolean,
) => {
    if (!trend.chart || !hasChartData(trend)) {
        return null;
    }

    const chartConfig = buildTrendChartConfig(trend);

    return (
        <div className={cn('w-full', isHandheld ? 'h-44' : 'h-48')}>
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

const renderBarTrend = (
    trend: DashboardTrend,
    isHandheld: boolean,
) => {
    if (!trend.chart || !hasChartData(trend)) {
        return null;
    }

    const chartConfig = buildTrendChartConfig(trend);

    return (
        <div className={cn('w-full', isHandheld ? 'h-44' : 'h-48')}>
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

const renderAreaTrend = (
    trend: DashboardTrend,
    isHandheld: boolean,
) => {
    if (!trend.chart || !hasChartData(trend)) {
        return null;
    }

    const chartConfig = buildTrendChartConfig(trend);

    return (
        <div className={cn('w-full', isHandheld ? 'h-44' : 'h-48')}>
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

const renderPieTrend = (
    trend: DashboardTrend,
    isHandheld: boolean,
) => {
    if (!trend.chart || !hasChartData(trend)) {
        return null;
    }

    const chart = trend.chart;
    const chartConfig = buildTrendChartConfig(trend);
    const valueKey = chart.series[0]?.key;

    if (!valueKey) {
        return null;
    }

    return (
        <div className={cn('w-full', isHandheld ? 'h-44' : 'h-48')}>
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

const renderTrendBody = (
    trend: DashboardTrend,
    options: ListTrendRenderOptions,
) => {
    const display = resolveTrendDisplay(trend);
    if (display === 'line') {
        return (
            renderLineTrend(trend, options.isHandheld) ??
            renderListTrend(trend, options)
        );
    }

    if (display === 'bar') {
        return (
            renderBarTrend(trend, options.isHandheld) ??
            renderListTrend(trend, options)
        );
    }

    if (display === 'area') {
        return (
            renderAreaTrend(trend, options.isHandheld) ??
            renderListTrend(trend, options)
        );
    }

    if (display === 'pie') {
        return (
            renderPieTrend(trend, options.isHandheld) ??
            renderListTrend(trend, options)
        );
    }

    return renderListTrend(trend, options);
};

export function DashboardAnalyticsPanel({
    kpis,
    alerts,
    trends,
    actionLinks,
    children,
}: DashboardAnalyticsPanelProps) {
    const page = usePage<SharedData>();
    const isHandheld = Boolean(page.props.ui?.is_handheld);
    const [handheldTab, setHandheldTab] = useState<'alerts' | 'trends' | 'actions'>(
        'alerts',
    );
    const [expandedTrendIds, setExpandedTrendIds] = useState<
        Record<string, boolean>
    >({});

    const toggleTrendExpansion = (trendId: string) => {
        setExpandedTrendIds((previous) => ({
            ...previous,
            [trendId]: !previous[trendId],
        }));
    };

    const renderAlertsPanel = () => {
        if (alerts.length === 0) {
            return (
                <div className="rounded-md border p-3 text-sm text-muted-foreground">
                    No active alerts.
                </div>
            );
        }

        return alerts.map((alert) => (
            <div key={alert.id} className="rounded-md border p-3">
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    <p className="text-sm font-medium">{alert.title}</p>
                    <Badge variant="outline" className={resolveAlertBadgeClass(alert.severity)}>
                        {capitalize(alert.severity)}
                    </Badge>
                </div>
                <p className="text-xs text-muted-foreground">{alert.message}</p>
            </div>
        ));
    };

    const renderActionLinksPanel = () => {
        if (actionLinks.length === 0) {
            return (
                <div className="rounded-md border p-3 text-sm text-muted-foreground">
                    No quick actions available.
                </div>
            );
        }

        return actionLinks.map((actionLink) => (
            <Button
                key={actionLink.id}
                asChild
                variant="outline"
                className="h-9 justify-between"
            >
                <Link href={actionLink.href}>
                    {actionLink.label}
                    <ArrowRight className="size-4" />
                </Link>
            </Button>
        ));
    };

    const renderTrendBlocks = (compact: boolean) => {
        if (trends.length === 0) {
            return (
                <div className="rounded-md border p-3 text-sm text-muted-foreground">
                    No trend data available.
                </div>
            );
        }

        return trends.map((trend) => (
            <div key={trend.id} className="rounded-md border">
                <div className="border-b px-3 py-2.5">
                    <p className="text-sm font-medium">{trend.label}</p>
                    <p className="text-xs text-muted-foreground">
                        {trend.summary}
                    </p>
                </div>
                <div className={cn('space-y-2', compact ? 'p-3' : 'p-3.5')}>
                    {renderTrendBody(trend, {
                        isHandheld: compact,
                        isExpanded: Boolean(expandedTrendIds[trend.id]),
                        onToggle: () => toggleTrendExpansion(trend.id),
                    })}
                </div>
            </div>
        ));
    };

    return (
        <div className="flex flex-col gap-4">
            <div
                className={cn(
                    'grid gap-3',
                    isHandheld ? 'grid-cols-2' : 'md:grid-cols-2 xl:grid-cols-4',
                )}
            >
                {kpis.map((kpi) => {
                    const { icon: KpiIcon, color: kpiColor } = resolveKpiIcon(kpi.id);
                    return (
                    <Card key={kpi.id}>
                        <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                            <KpiIcon className={cn('size-4 shrink-0', kpiColor)} />
                            <CardTitle className="text-sm font-medium">
                                {kpi.label}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-0.5 pb-3 pt-3">
                            <p
                                className={cn(
                                    'font-semibold leading-tight',
                                    isHandheld ? 'text-xl' : 'text-2xl',
                                )}
                            >
                                {kpi.value}
                            </p>
                            {kpi.meta ? (
                                <p className="text-xs text-muted-foreground">
                                    {kpi.meta}
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>
                    );
                })}
            </div>

            {children ? <div className="grid gap-3">{children}</div> : null}

            {isHandheld ? (
                <Tabs
                    value={handheldTab}
                    onValueChange={(value) =>
                        setHandheldTab(value as 'alerts' | 'trends' | 'actions')
                    }
                    className="flex flex-col gap-3"
                >
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="alerts">Alerts</TabsTrigger>
                        <TabsTrigger value="trends">Trends</TabsTrigger>
                        <TabsTrigger value="actions">Actions</TabsTrigger>
                    </TabsList>

                    <TabsContent value="alerts" className="m-0">
                        <Card>
                            <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                                <Bell className="size-4 text-amber-500" />
                                <CardTitle className="text-sm">Alerts</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2.5 pt-3">
                                {renderAlertsPanel()}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="trends" className="m-0">
                        <Card>
                            <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                                <TrendingUp className="size-4 text-emerald-500" />
                                <CardTitle className="text-sm">Trends</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2.5 pt-3">
                                {renderTrendBlocks(true)}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="actions" className="m-0">
                        <Card>
                            <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                                <ArrowRight className="size-4 text-primary" />
                                <CardTitle className="text-sm">
                                    Action Links
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-2.5 pt-3">
                                {renderActionLinksPanel()}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            ) : (
                <>
                    <div className="grid gap-3 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                                <Bell className="size-4 text-amber-500" />
                                <CardTitle className="text-base">Alerts</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2.5 pt-3">
                                {renderAlertsPanel()}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                                <ArrowRight className="size-4 text-primary" />
                                <CardTitle className="text-base">
                                    Action Links
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-2.5 pt-3">
                                {renderActionLinksPanel()}
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 border-b py-3">
                            <TrendingUp className="size-4 text-emerald-500" />
                            <CardTitle className="text-base">Trends</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 pt-3 md:grid-cols-2">
                            {renderTrendBlocks(false)}
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    );
}
