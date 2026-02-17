import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Batch Promotion',
        href: '/registrar/batch-promotion',
    },
];

export default function BatchPromotion() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Batch Promotion" />
            <div className="flex flex-col gap-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">Batch <span className="text-primary not-italic">Promotion</span></h1>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6">
                    <div className="flex flex-col items-center justify-center py-20 rounded-xl border-2 border-dashed border-muted bg-muted/10 text-center">
                        <h3 className="text-lg font-bold text-muted-foreground">Promotion Settings</h3>
                        <p className="text-sm text-muted-foreground/60 max-w-xs mt-1 italic">Configure batch promotion rules and thresholds here.</p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
