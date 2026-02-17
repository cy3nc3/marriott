import { Head } from '@inertiajs/react';
import { Clock, Star, Wallet } from 'lucide-react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

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
            <div className="flex flex-col gap-4">
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {/* Happening Now */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Happening Now</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">Mathematics 7</div>
                            <p className="text-xs text-muted-foreground">with Teacher 1</p>
                            <div className="mt-4 flex gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">Duration</p>
                                    <p className="font-medium">08:00 AM - 09:00 AM</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Room</p>
                                    <p className="font-medium">Room 204</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-4">
                        {/* Outstanding Balance */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Account Balance</CardTitle>
                                <Wallet className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">₱ 15,000.00</div>
                                <p className="text-xs text-muted-foreground">Next due: Aug 30, 2024</p>
                            </CardContent>
                        </Card>

                        {/* Latest Grade */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Latest Score</CardTitle>
                                <Star className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">18/20</div>
                                <p className="text-xs text-muted-foreground">Unit Quiz 1 - Mathematics 7</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
