import { router, usePage } from '@inertiajs/react';
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
    const authenticatedRole = (page.props.auth?.user?.role as string) || '';
    const viewAsRole = (page.props.auth?.view_as_role as string | null) ?? null;
    const isSuperAdminViewAs = authenticatedRole === 'super_admin' && Boolean(viewAsRole);
    const viewedRoleLabel = viewAsRole
        ? viewAsRole
              .split('_')
              .filter((chunk) => chunk.length > 0)
              .map((chunk) => chunk[0].toUpperCase() + chunk.slice(1))
              .join(' ')
        : '';

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {isSuperAdminViewAs ? (
                    <div className="border-b bg-amber-50 px-4 py-2 text-xs text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        <div className="flex items-center justify-between gap-3">
                            <span>Viewing as {viewedRoleLabel}</span>
                            <button
                                type="button"
                                className="rounded border border-amber-300 px-2 py-1 text-[11px] font-medium hover:bg-amber-100 dark:border-amber-700 dark:hover:bg-amber-900/40"
                                onClick={() =>
                                    router.delete('/super-admin/view-as-role', {
                                        preserveScroll: true,
                                        preserveState: true,
                                    })
                                }
                            >
                                Return to Super Admin
                            </button>
                        </div>
                    </div>
                ) : null}
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
