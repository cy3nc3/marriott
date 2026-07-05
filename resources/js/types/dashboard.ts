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
    display?: 'list' | 'line' | 'bar' | 'area' | 'pie';
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

export type DashboardDecisionCard = {
    id: string;
    title: string;
    decision?: string | null;
    metric: string;
    status: 'on_track' | 'watch' | 'at_risk';
    confidence?: 'high' | 'medium' | 'low';
    trigger?: string | null;
    rationale: string;
    recommended_actions: string[];
    basis_points?: Array<{
        label: string;
        value: string | number;
        explanation?: string | null;
    }>;
};

export type DashboardActionQueueItem = {
    id: string;
    title: string;
    impact: string;
    urgency: string;
    priority_score: number;
    reason: string;
    href?: string | null;
};
