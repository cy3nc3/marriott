import { Head } from '@inertiajs/react';
import { 
    Users, 
    GraduationCap, 
    FileText, 
    Clock 
} from 'lucide-react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="shadow-sm border-primary/10">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black uppercase tracking-wider text-muted-foreground">
                                Total Students
                            </CardTitle>
                            <Users className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">1,234</div>
                            <p className="text-[10px] text-muted-foreground font-bold uppercase mt-1">
                                +12% from last sem
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="shadow-sm border-primary/10">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black uppercase tracking-wider text-muted-foreground">
                                Pending Enrollees
                            </CardTitle>
                            <Clock className="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">45</div>
                            <p className="text-[10px] text-muted-foreground font-bold uppercase mt-1">
                                Awaiting approval
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="shadow-sm border-primary/10">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black uppercase tracking-wider text-muted-foreground">
                                Form 137 Requests
                            </CardTitle>
                            <FileText className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">12</div>
                            <p className="text-[10px] text-muted-foreground font-bold uppercase mt-1">
                                3 urgent requests
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="shadow-sm border-primary/10">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-black uppercase tracking-wider text-muted-foreground">
                                Candidates for Grad
                            </CardTitle>
                            <GraduationCap className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-black italic">280</div>
                            <p className="text-[10px] text-muted-foreground font-bold uppercase mt-1">
                                98% cleared
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                     {/* Placeholder for recent activities or charts */}
                    <Card className="shadow-sm border-primary/10 h-[300px] flex items-center justify-center bg-muted/5">
                        <p className="text-xs font-bold text-muted-foreground uppercase">Recent Activities Overview</p>
                    </Card>
                    <Card className="shadow-sm border-primary/10 h-[300px] flex items-center justify-center bg-muted/5">
                        <p className="text-xs font-bold text-muted-foreground uppercase">Enrollment Trends</p>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
