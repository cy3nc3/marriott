import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Users, GraduationCap, FileText, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Registrar Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrar Dashboard" />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Registrar{' '}
                            <span className="text-primary not-italic">
                                Dashboard
                            </span>
                        </h1>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="border-primary/10 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black tracking-wider text-muted-foreground uppercase">
                                Total Students
                            </CardTitle>
                            <Users className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">
                                1,234
                            </div>
                            <p className="mt-1 text-[10px] font-bold text-muted-foreground uppercase">
                                +12% from last sem
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-primary/10 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black tracking-wider text-muted-foreground uppercase">
                                Pending Enrollees
                            </CardTitle>
                            <Clock className="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">45</div>
                            <p className="mt-1 text-[10px] font-bold text-muted-foreground uppercase">
                                Awaiting approval
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-primary/10 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black tracking-wider text-muted-foreground uppercase">
                                Form 137 Requests
                            </CardTitle>
                            <FileText className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">12</div>
                            <p className="mt-1 text-[10px] font-bold text-muted-foreground uppercase">
                                3 urgent requests
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-primary/10 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black tracking-wider text-muted-foreground uppercase">
                                Candidates for Grad
                            </CardTitle>
                            <GraduationCap className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">
                                280
                            </div>
                            <p className="mt-1 text-[10px] font-bold text-muted-foreground uppercase">
                                98% cleared
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Placeholder for recent activities or charts */}
                    <Card className="flex h-[300px] items-center justify-center border-primary/10 bg-muted/5 shadow-sm">
                        <p className="text-xs font-bold text-muted-foreground uppercase">
                            Recent Activities Overview
                        </p>
                    </Card>
                    <Card className="flex h-[300px] items-center justify-center border-primary/10 bg-muted/5 shadow-sm">
                        <p className="text-xs font-bold text-muted-foreground uppercase">
                            Enrollment Trends
                        </p>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
