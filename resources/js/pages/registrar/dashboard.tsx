import { Head } from '@inertiajs/react';
import { Users, GraduationCap, FileText, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

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
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle>Total Students</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">1,234</div>
                            <p className="text-sm text-muted-foreground">
                                +12% from last sem
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle>Pending Enrollees</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">45</div>
                            <p className="text-sm text-muted-foreground">
                                Awaiting approval
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle>Form 137 Requests</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">12</div>
                            <p className="text-sm text-muted-foreground">
                                3 urgent requests
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle>Candidates for Grad</CardTitle>
                            <GraduationCap className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">280</div>
                            <p className="text-sm text-muted-foreground">
                                98% cleared
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card className="flex h-[300px] items-center justify-center">
                        <p className="text-sm text-muted-foreground">
                            Recent Activities Overview
                        </p>
                    </Card>
                    <Card className="flex h-[300px] items-center justify-center">
                        <p className="text-sm text-muted-foreground">
                            Enrollment Trends
                        </p>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
