import { Head, Link } from '@inertiajs/react';
import {
    ShieldCheck,
    Wallet,
    GraduationCap,
    ArrowRight,
    User,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Parent Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Parent Dashboard" />
            <div className="flex flex-col gap-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {/* Enrollment Status */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Enrollment Status
                            </CardTitle>
                            <ShieldCheck className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="mb-2 flex items-center gap-2">
                                <Badge variant="secondary">Enrolled</Badge>
                                <span className="text-xs text-muted-foreground">
                                    SY 2025-2026
                                </span>
                            </div>
                            <p className="text-sm font-medium">
                                Grade 7 - Rizal
                            </p>
                        </CardContent>
                    </Card>

                    {/* Financial Summary */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Outstanding Balance
                            </CardTitle>
                            <Wallet className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                ₱ 15,000.00
                            </div>
                            <Link
                                href="/parent/billing-information"
                                className="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                            >
                                View Billing Details
                                <ArrowRight className="size-3" />
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Academic Performance */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Academic Standing
                            </CardTitle>
                            <GraduationCap className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">83.5</div>
                            <p className="mb-2 text-xs text-muted-foreground">
                                General Average
                            </p>
                            <Link
                                href="/parent/grades"
                                className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                            >
                                View Progress Report
                                <ArrowRight className="size-3" />
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-1">
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-4 space-y-0">
                            <div className="rounded-full bg-muted p-2">
                                <User className="size-6 text-primary" />
                            </div>
                            <div>
                                <CardTitle className="text-base">
                                    Class Adviser
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Mr. Arthur Santos
                                </p>
                            </div>
                            <div className="ml-auto">
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/parent/schedule">
                                        View Class Schedule
                                    </Link>
                                </Button>
                            </div>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
