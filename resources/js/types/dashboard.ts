export type DashboardKpi = {
    id: string;
    label: string;
    value: string | number;
    meta?: string | null;
};

export type DashboardAlertSeverity = 'info' | 'warning' | 'critical';

export type DashboardAlert = {
    id: string;
    title: string;
    message: string;
    severity: DashboardAlertSeverity;
};

export type DashboardTrendPoint = {
    label: string;
    value: string | number | null;
};

export type DashboardTrendChartRow = Record<
    string,
    string | number | boolean | null
>;

export type DashboardTrendSeries = {
    key: string;
    label: string;
    dashed?: boolean;
};

export type DashboardTrend = {
    id: string;
    label: string;
    summary: string;
    display?: 'list' | 'line' | 'bar';
    points?: DashboardTrendPoint[];
    chart?: {
        x_key: string;
        rows: DashboardTrendChartRow[];
        series: DashboardTrendSeries[];
    };
};

export type DashboardActionLink = {
    id: string;
    label: string;
    href: string;
};
