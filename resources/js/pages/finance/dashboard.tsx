import { Head } from '@inertiajs/react';
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
        title: 'Finance Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Finance Dashboard" />
            <div className="flex flex-col gap-6">
                <div className="grid gap-6 md:grid-cols-3">
                    {/* Card 1: Collection Efficiency */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg font-medium">Collection Efficiency</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-semibold">85% Collected</span>
                            </div>
                            <div className="w-full bg-muted rounded-full h-4">
                                <div className="bg-primary h-4 rounded-full w-[85%]"></div>
                            </div>
                            <p className="mt-4 text-sm font-semibold text-muted-foreground text-center">
                                85% Paid / 15% Outstanding
                            </p>
                        </CardContent>
                    </Card>

                    {/* Card 2: Cash in Drawer */}
                    <Card className="flex flex-col items-center justify-center text-center">
                        <CardContent className="p-6">
                            <div className="text-sm uppercase tracking-wide font-semibold text-muted-foreground mb-4">Cash in Drawer (Today)</div>
                            <div className="text-4xl font-extrabold text-primary">
                                ₱ 45,200.00
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 3: Revenue Forecast */}
                    <Card className="flex flex-col items-center justify-center text-center">
                        <CardContent className="p-6">
                            <div className="text-sm uppercase tracking-wide font-semibold text-muted-foreground mb-4">Revenue Forecast (Next Month)</div>
                            <div className="text-3xl font-bold">
                                ₱ 1,250,000
                            </div>
                            <p className="mt-2 text-sm font-semibold text-muted-foreground italic">Based on upcoming due dates</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
