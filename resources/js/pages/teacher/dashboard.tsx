import { Head } from '@inertiajs/react';
import { AlertCircle, Clock } from 'lucide-react';
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
        title: 'Teacher Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {/* Card 1: My Schedule Today */}
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 bg-muted/30 border-b space-y-0 py-4">
                            <Clock className="size-5 text-primary" />
                            <CardTitle className="text-lg">My Schedule Today</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <ul className="divide-y">
                                <li className="px-6 py-4 flex justify-between items-center hover:bg-muted/30 transition-colors">
                                    <span className="font-bold text-sm text-muted-foreground">08:00 AM - 09:00 AM</span>
                                    <span className="font-bold text-primary">Mathematics 7</span>
                                </li>
                                <li className="px-6 py-4 flex justify-between items-center hover:bg-muted/30 transition-colors">
                                    <span className="font-bold text-sm text-muted-foreground">09:00 AM - 10:00 AM</span>
                                    <span className="font-bold text-primary">Mathematics 7</span>
                                </li>
                                <li className="px-6 py-4 flex justify-between items-center hover:bg-muted/30 transition-colors">
                                    <span className="font-bold text-sm text-muted-foreground">10:30 AM - 11:30 AM</span>
                                    <span className="font-bold text-primary">Mathematics 8</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Card 2: Action Required */}
                    <Card className="border-l-4 border-l-yellow-500 bg-yellow-500/5 h-fit">
                        <CardContent className="p-6">
                            <div className="flex items-start gap-4">
                                <div className="p-2 bg-yellow-500/10 rounded-full">
                                    <AlertCircle className="size-6 text-yellow-600 dark:text-yellow-500" />
                                </div>
                                <div className="space-y-1">
                                    <h3 className="text-lg font-bold text-yellow-800 dark:text-yellow-500">Action Required</h3>
                                    <p className="text-sm text-yellow-700/80 dark:text-yellow-500/80 font-medium">
                                        You have <span className="font-black underline decoration-2">3</span> subjects pending Grade Submission.
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
