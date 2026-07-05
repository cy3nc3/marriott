import { Head } from '@inertiajs/react';
import { Calendar, GraduationCap, Users } from 'lucide-react';
import { DashboardAnalyticsPanel } from '@/components/dashboard/analytics-panel';
import { Badge } from '@/components/ui/badge';
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

type TodayScheduleRow = {
    id: number;
    title: string;
    section: string;
    time: string;
    is_academic: boolean;
    is_advisory: boolean;
};

interface Props {
    today_schedules: TodayScheduleRow[];
    kpis: DashboardKpi[];
    alerts: DashboardAlert[];
    trends: DashboardTrend[];
    action_links: DashboardActionLink[];
    quarter_grade_completion: QuarterGradeCompletion;
}

export default function Dashboard({
    today_schedules,
    kpis,
    alerts,
    trends,
    action_links,
    quarter_grade_completion,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Dashboard" />

            <div className="mb-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-base font-medium">
                            Today's Schedule
                        </CardTitle>
                        <Calendar className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        {today_schedules.length === 0 ? (
                            <div className="py-4 text-center text-sm text-muted-foreground">
                                No classes or advisory schedules for today.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {today_schedules.map((schedule) => (
                                    <div
                                        key={schedule.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium leading-none">
                                                {schedule.title}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {schedule.section}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <div className="text-right">
                                                <p className="text-xs font-medium">
                                                    {schedule.time}
                                                </p>
                                            </div>
                                            <div className="flex flex-col gap-1">
                                                {schedule.is_advisory && (
                                                    <Badge variant="secondary" className="text-[10px]">
                                                        Advisory
                                                    </Badge>
                                                )}
                                                {!schedule.is_academic && !schedule.is_advisory && (
                                                    <Badge variant="outline" className="text-[10px]">
                                                        Event
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-base font-medium">
                            Academic Progress
                        </CardTitle>
                        <GraduationCap className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <p className="text-sm font-medium">Total Classes</p>
                                    <p className="text-xs text-muted-foreground">Assigned for current SY</p>
                                </div>
                                <span className="text-xl font-bold">{quarter_grade_completion.total_classes}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <p className="text-sm font-medium">Finalized</p>
                                    <p className="text-xs text-muted-foreground">Grades posted and locked</p>
                                </div>
                                <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700">
                                    {quarter_grade_completion.finalized_classes}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <p className="text-sm font-medium">Remaining</p>
                                    <p className="text-xs text-muted-foreground">Pending encoding/review</p>
                                </div>
                                <Badge variant="secondary" className="bg-amber-500/15 text-amber-700">
                                    {quarter_grade_completion.unfinalized_classes}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <DashboardAnalyticsPanel
                kpis={kpis}
                alerts={alerts}
                trends={trends}
                actionLinks={action_links}
            />
        </AppLayout>
    );
}
