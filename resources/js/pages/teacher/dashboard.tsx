import { Head } from '@inertiajs/react';
import { AlertCircle, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teacher Dashboard',
        href: dashboard().url,
    },
];

type TodayScheduleItem = {
    id: number;
    start: string;
    end: string;
    time_label: string;
    title: string;
    section: string;
};

type PendingSummary = {
    pending_subjects_count: number;
    total_subjects_count: number;
    completed_subjects_count: number;
};

interface Props {
    today_schedule: TodayScheduleItem[];
    pending_summary: PendingSummary;
}

export default function Dashboard({ today_schedule, pending_summary }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Dashboard" />
            <div className="flex flex-col gap-6">
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 border-b py-4">
                            <Clock className="size-5 text-muted-foreground" />
                            <CardTitle className="text-lg">
                                My Schedule Today
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {today_schedule.length > 0 ? (
                                <ul className="divide-y">
                                    {today_schedule.map((scheduleItem) => (
                                        <li
                                            key={scheduleItem.id}
                                            className="flex items-center justify-between px-6 py-4"
                                        >
                                            <span className="text-sm text-muted-foreground">
                                                {scheduleItem.time_label}
                                            </span>
                                            <span className="text-sm font-medium">
                                                {scheduleItem.title}
                                                <span className="ml-2 text-xs text-muted-foreground">
                                                    {scheduleItem.section}
                                                </span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="px-6 py-5 text-sm text-muted-foreground">
                                    No schedule for today.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="h-fit">
                        <CardContent className="p-6">
                            <div className="flex items-start gap-4">
                                <div className="rounded-full border p-2">
                                    <AlertCircle className="size-6 text-muted-foreground" />
                                </div>
                                <div className="space-y-1">
                                    <h3 className="text-lg font-semibold">
                                        Action Required
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        You have{' '}
                                        <span className="font-semibold">
                                            {
                                                pending_summary.pending_subjects_count
                                            }
                                        </span>{' '}
                                        subject
                                        {pending_summary.pending_subjects_count ===
                                        1
                                            ? ''
                                            : 's'}{' '}
                                        pending grade submission.
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {pending_summary.completed_subjects_count}{' '}
                                        of {pending_summary.total_subjects_count}{' '}
                                        subjects complete.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
