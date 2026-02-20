import { Head } from '@inertiajs/react';
import { AlertCircle, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teacher Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Dashboard" />
            <div className="flex flex-col gap-6">
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Card 1: My Schedule Today */}
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 border-b py-4">
                            <Clock className="size-5 text-muted-foreground" />
                            <CardTitle className="text-lg">
                                My Schedule Today
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <ul className="divide-y">
                                <li className="flex items-center justify-between px-6 py-4">
                                    <span className="text-sm text-muted-foreground">
                                        08:00 AM - 09:00 AM
                                    </span>
                                    <span className="text-sm font-medium">
                                        Mathematics 7
                                    </span>
                                </li>
                                <li className="flex items-center justify-between px-6 py-4">
                                    <span className="text-sm text-muted-foreground">
                                        09:00 AM - 10:00 AM
                                    </span>
                                    <span className="text-sm font-medium">
                                        Mathematics 7
                                    </span>
                                </li>
                                <li className="flex items-center justify-between px-6 py-4">
                                    <span className="text-sm text-muted-foreground">
                                        10:30 AM - 11:30 AM
                                    </span>
                                    <span className="text-sm font-medium">
                                        Mathematics 8
                                    </span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Card 2: Action Required */}
                    <Card className="h-fit">
                        <CardContent className="p-6">
                            <div className="flex items-start gap-4">
                                <div className="rounded-full border p-2">
                                    <AlertCircle className="size-6 text-muted-foreground" />
                                </div>
                                <div className="space-y-1">
                                    <h3 className="text-lg font-semibold">
                                        Action Required
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        You have{' '}
                                        <span className="font-semibold">3</span>{' '}
                                        subjects pending Grade Submission.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
