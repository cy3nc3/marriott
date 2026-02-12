import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
} from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6 justify-center">
                
                {/* Focus Card: Happening Now */}
                <Card className="max-w-2xl w-full mx-auto shadow-lg border-primary/10">
                    <CardContent className="p-12 text-center space-y-4">
                        <h3 className="text-xl font-medium text-muted-foreground uppercase tracking-widest">Happening Now</h3>
                        <div className="space-y-2">
                            <div className="text-4xl font-black text-foreground tracking-tight">
                                Mathematics 7
                            </div>
                            <div className="text-xl text-muted-foreground font-medium">
                                with <span className="font-bold text-primary">Mr. Arthur Santos</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
