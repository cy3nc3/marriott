import { Link, usePage } from '@inertiajs/react';
import type { ComponentType } from 'react';
import {
    Bell,
    BookOpenCheck,
    CalendarDays,
    GraduationCap,
    LayoutGrid,
    Megaphone,
    ReceiptText,
    UserRound,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

type QuickNavItem = {
    label: string;
    href: string;
    icon: ComponentType<{ className?: string }>;
};

const staffTabs: QuickNavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { label: 'Notifications', href: '/notifications', icon: Bell },
    { label: 'Announcements', href: '/announcements', icon: Megaphone },
    { label: 'Profile', href: '/settings/profile', icon: UserRound },
];

const teacherTabs: QuickNavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { label: 'Schedule', href: '/teacher/schedule', icon: CalendarDays },
    { label: 'Notifications', href: '/notifications', icon: Bell },
    { label: 'Profile', href: '/settings/profile', icon: UserRound },
];

const studentTabs: QuickNavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { label: 'Schedule', href: '/student/schedule', icon: CalendarDays },
    { label: 'Grades', href: '/student/grades', icon: GraduationCap },
    { label: 'Notifications', href: '/notifications', icon: Bell },
];

const parentTabs: QuickNavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { label: 'Billing', href: '/parent/billing-information', icon: ReceiptText },
    { label: 'Grades', href: '/parent/grades', icon: BookOpenCheck },
    { label: 'Notifications', href: '/notifications', icon: Bell },
];

const tabMap: Record<string, QuickNavItem[]> = {
    super_admin: staffTabs,
    admin: staffTabs,
    registrar: staffTabs,
    finance: staffTabs,
    teacher: teacherTabs,
    student: studentTabs,
    parent: parentTabs,
};

export function MobileQuickNav() {
    const page = usePage<SharedData>();
    const role = String(page.props.auth.user.role ?? '');
    const isHandheld = Boolean(page.props.ui?.is_handheld);

    if (!isHandheld) {
        return null;
    }

    const currentPath = `/${String(page.url ?? '').replace(/^\//, '')}`;
    const tabs = tabMap[role] ?? staffTabs;

    return (
        <nav className="fixed inset-x-0 bottom-0 z-40 border-t bg-card">
            <div className="grid grid-cols-4 gap-1 px-2 py-2">
                {tabs.map((tab) => {
                    const isActive =
                        currentPath === tab.href ||
                        currentPath.startsWith(`${tab.href}/`);

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            prefetch
                            className={cn(
                                'flex min-h-12 flex-col items-center justify-center gap-1 rounded-md px-1 py-1 text-[11px] leading-none',
                                isActive
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted',
                            )}
                        >
                            <tab.icon className="size-4" />
                            <span className="truncate">{tab.label}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
