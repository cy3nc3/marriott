import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { AlertCircle } from 'lucide-react';

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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {/* Card 1: Student Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg font-medium">Student Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-lg">
                                <span className="font-bold">Juan Dela Cruz</span> is <span className="text-green-600 dark:text-green-400 font-semibold tracking-wide">ENROLLED</span>.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Card 2: Billing Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg font-medium">Billing Status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200">
                                <AlertCircle className="size-4 mr-2" />
                                Account Status: Action Required
                            </div>
                            
                            <div className="pt-2">
                                <Link 
                                    href="/parent/billing-information" 
                                    className="text-primary hover:underline font-semibold flex items-center gap-1"
                                >
                                    View Details
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AppLayout>
    );
}
