import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Remedial Entry',
        href: '/registrar/remedial-entry',
    },
];

export default function RemedialEntry() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Remedial Entry" />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Remedial{' '}
                            <span className="text-primary not-italic">
                                Entry
                            </span>
                        </h1>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6">
                    <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted bg-muted/10 py-20 text-center">
                        <h3 className="text-lg font-bold text-muted-foreground">
                            Remedial Classes
                        </h3>
                        <p className="mt-1 max-w-xs text-sm text-muted-foreground/60 italic">
                            Manage summer classes and remedial grades here.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
