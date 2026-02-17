import { Head, Link } from '@inertiajs/react';
import { ShieldCheck, Wallet, GraduationCap, ArrowRight, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
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
        title: 'Parent Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Parent Dashboard" />
            <div className="flex flex-col gap-4">
                
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    
                    {/* Enrollment Status */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Enrollment Status</CardTitle>
                            <ShieldCheck className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2 mb-2">
                                <div className="text-2xl font-bold">Enrolled</div>
                                <Badge variant="secondary">SY 2025-2026</Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Grade 7 - Rizal
                            </p>
                        </CardContent>
                    </Card>

                    {/* Financial Summary */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Outstanding Balance</CardTitle>
                            <Wallet className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold mb-2">₱ 15,000.00</div>
                            <Link href="/parent/billing-information" className="text-xs text-muted-foreground hover:text-primary flex items-center gap-1">
                                View Billing Details
                                <ArrowRight className="h-3 w-3" />
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Academic Performance */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Academic Standing</CardTitle>
                            <GraduationCap className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold mb-2">83.5</div>
                            <p className="text-xs text-muted-foreground mb-2">General Average</p>
                            <Link href="/parent/grades" className="text-xs text-muted-foreground hover:text-primary flex items-center gap-1">
                                View Progress Report
                                <ArrowRight className="h-3 w-3" />
                            </Link>
                        </CardContent>
                    </Card>

                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-muted-foreground" />
                            <CardTitle className="text-sm font-medium">Class Adviser</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between">
                        <div>
                            <div className="text-xl font-bold">Mr. Arthur Santos</div>
                            <p className="text-xs text-muted-foreground">Class Adviser</p>
                        </div>
                        <Link href="/parent/schedule" className="text-sm font-medium hover:underline">
                            View Class Schedule
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
