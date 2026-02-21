import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell>
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent className="[&_[data-slot=card]]:pt-0! [&_[data-slot=card]]:gap-0! [&_[data-slot=card-header].border-b]:pb-4! [&_[data-slot=card-header]:not(:has([data-slot=card-description])):not(:has(p)):not(:has(svg))]:gap-0!">
                {children}
            </AppContent>
        </AppShell>
    );
}
