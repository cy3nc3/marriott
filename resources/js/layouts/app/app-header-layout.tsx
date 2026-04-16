import { usePage } from '@inertiajs/react';
import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { MobileQuickNav } from '@/components/mobile-quick-nav';
import { SavedAccountLoginSync } from '@/components/saved-account-login-sync';
import { cn } from '@/lib/utils';
import type { AppLayoutProps } from '@/types';
import type { SharedData } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    const page = usePage<SharedData>();
    const isHandheld = Boolean(page.props.ui?.is_handheld);

    return (
        <AppShell>
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent
                className={cn(
                    '[&_[data-slot=card-header].border-b]:pb-4! [&_[data-slot=card-header]:not(:has([data-slot=card-description])):not(:has(p)):not(:has(svg))]:gap-0! [&_[data-slot=card]]:gap-0! [&_[data-slot=card]]:pt-0!',
                    isHandheld && 'pb-20',
                )}
            >
                {children}
                <MobileQuickNav />
            </AppContent>
            <SavedAccountLoginSync />
        </AppShell>
    );
}
