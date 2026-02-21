import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance Dashboard',
        href: dashboard().url,
    },
];

type Metrics = {
    collection_efficiency_percent: number;
    total_charges: number;
    total_payments: number;
    outstanding_balance: number;
    cash_in_drawer_today: number;
    revenue_forecast_next_month: number;
    forecast_month_label: string;
};

interface Props {
    metrics: Metrics;
}

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount || 0);

export default function Dashboard({ metrics }: Props) {
    const boundedEfficiency = Math.min(
        Math.max(metrics.collection_efficiency_percent, 0),
        100,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Finance Dashboard" />

            <div className="flex flex-col gap-6">
                <div className="grid gap-6 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Collection Efficiency</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-sm font-semibold">
                                    {metrics.collection_efficiency_percent.toFixed(
                                        2,
                                    )}
                                    % Collected
                                </span>
                            </div>
                            <div className="h-4 w-full rounded-full bg-muted">
                                <div
                                    className="h-4 rounded-full bg-primary"
                                    style={{ width: `${boundedEfficiency}%` }}
                                />
                            </div>
                            <p className="mt-4 text-center text-sm text-muted-foreground">
                                {formatCurrency(metrics.total_payments)} Paid /{' '}
                                {formatCurrency(metrics.outstanding_balance)}{' '}
                                Outstanding
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="flex flex-col items-center justify-center text-center">
                        <CardContent className="p-6">
                            <div className="mb-4 text-sm text-muted-foreground">
                                Cash in Drawer (Today)
                            </div>
                            <div className="text-4xl font-semibold">
                                {formatCurrency(metrics.cash_in_drawer_today)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="flex flex-col items-center justify-center text-center">
                        <CardContent className="p-6">
                            <div className="mb-4 text-sm text-muted-foreground">
                                Revenue Forecast ({metrics.forecast_month_label})
                            </div>
                            <div className="text-3xl font-semibold">
                                {formatCurrency(
                                    metrics.revenue_forecast_next_month,
                                )}
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Based on unpaid and partially paid dues
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
