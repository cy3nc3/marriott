import SimpleBar from 'simplebar-react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';
import 'simplebar-react/dist/simplebar.min.css';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="flex flex-1 flex-col min-h-0 overflow-hidden"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="relative flex-1 min-h-0">
                    <SimpleBar
                        style={{ position: 'absolute', inset: 0 }}
                        className="overflow-x-hidden"
                    >
                        <div className="flex flex-col p-4 [&_[data-slot=card]]:pt-0! [&_[data-slot=card]]:gap-0! [&_[data-slot=card-header].border-b]:pb-4! [&_[data-slot=card-header]:not(:has([data-slot=card-description])):not(:has(p)):not(:has(svg))]:gap-0!">
                            {children}
                        </div>
                    </SimpleBar>
                </div>
            </AppContent>
        </AppShell>
    );
}
