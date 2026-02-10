import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

export default function StudentDirectory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Directory" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="absolute inset-0 flex items-center justify-center">
                        <span className="font-medium text-muted-foreground">
                            Student Directory
                        </span>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
