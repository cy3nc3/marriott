import { usePage } from '@inertiajs/react';
import SimpleBar from 'simplebar-react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { LoginWelcomeToast } from '@/components/login-welcome-toast';
import { MobileQuickNav } from '@/components/mobile-quick-nav';
import { SavedAccountLoginSync } from '@/components/saved-account-login-sync';
import { cn } from '@/lib/utils';
import type { AppLayoutProps } from '@/types';
import type { SharedData } from '@/types';
import 'simplebar-react/dist/simplebar.min.css';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const page = usePage<SharedData>();
    const isHandheld = Boolean(page.props.ui?.is_handheld);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="relative min-h-0 flex-1">
                    <SimpleBar
                        style={{ position: 'absolute', inset: 0 }}
                        className="overflow-x-hidden"
                    >
                        <div
                            className={cn(
                                'flex flex-col p-4 [&_[data-slot=card-header].border-b]:pb-4! [&_[data-slot=card-header]:not(:has([data-slot=card-description])):not(:has(p)):not(:has(svg))]:gap-0! [&_[data-slot=card]]:gap-0! [&_[data-slot=card]]:pt-0!',
                                isHandheld && 'pb-20',
                            )}
                        >
                            {children}
                        </div>
                    </SimpleBar>
                </div>
                <MobileQuickNav />
            </AppContent>
            <LoginWelcomeToast />
            <SavedAccountLoginSync />
        </AppShell>
    );
}
