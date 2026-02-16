import { Head } from '@inertiajs/react';
import { BookOpen, GraduationCap, School, Users } from 'lucide-react';
import { EnrollmentChart } from '@/components/dashboard/enrollment-chart';
import { StatCard } from '@/components/dashboard/stat-card';
import { TrendChart } from '@/components/dashboard/trend-chart';
import { WorkloadChart } from '@/components/dashboard/workload-chart';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface DashboardProps {
    stats: {
        totalStudents: number;
        totalTeachers: number;
        activeSections: number;
        unassignedSubjects: number;
    };
    charts: {
        enrollmentByGrade: { name: string; count: number }[];
        teacherWorkload: { name: string; full_name: string; count: number }[];
        enrollmentTrends: { date: string; count: number }[];
    };
    currentYear?: {
        name: string;
        status: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard({ stats, charts, currentYear }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total Students"
                        value={stats.totalStudents}
                        description={currentYear ? `Active in ${currentYear.name}` : 'No active year'}
                        icon={GraduationCap}
                    />
                    <StatCard
                        title="Total Teachers"
                        value={stats.totalTeachers}
                        description="Active faculty members"
                        icon={Users}
                    />
                    <StatCard
                        title="Active Sections"
                        value={stats.activeSections}
                        description="Currently running sections"
                        icon={School}
                    />
                    <StatCard
                        title="Unassigned Subjects"
                        value={stats.unassignedSubjects}
                        description="Subjects without teachers"
                        icon={BookOpen}
                        className={stats.unassignedSubjects > 0 ? "border-destructive/50 bg-destructive/10" : ""}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                    <div className="col-span-4">
                        <TrendChart data={charts.enrollmentTrends} />
                    </div>
                    <div className="col-span-3">
                        <EnrollmentChart data={charts.enrollmentByGrade} />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                     <div className="col-span-3">
                        <WorkloadChart data={charts.teacherWorkload} />
                     </div>
                </div>
            </div>
        </AppLayout>
    );
}
