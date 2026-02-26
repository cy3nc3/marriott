import { Link, usePage } from '@inertiajs/react';
import {
    Banknote,
    BarChart3,
    BookOpen,
    Calendar,
    CheckSquare,
    ClipboardList,
    CreditCard,
    Download,
    FileSpreadsheet,
    FileText,
    Folder,
    GraduationCap,
    HelpCircle,
    History,
    LayoutGrid,
    Layers,
    Megaphone,
    Package,
    Receipt,
    Settings,
    ShieldCheck,
    Tag,
    TrendingUp,
    UserCheck,
    UserPlus,
    UserMinus,
    Users,
    Wallet,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { NavFooter } from '@/components/nav-footer';
import { PwaInstallGuideDialog } from '@/components/pwa-install-guide-dialog';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    getDeferredInstallPrompt,
    getInstallUnavailableMessage,
    isLikelyAlreadyInstalled,
    onInstallPromptAvailable,
    onPwaAppInstalled,
} from '@/lib/pwa-install';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem, SharedData } from '@/types';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    {
        title: 'System Help',
        href: '#',
        icon: HelpCircle,
    },
];

const roleNavItems: Record<string, NavItem[]> = {
    super_admin: [
        {
            title: 'User Manager',
            href: '/super-admin/user-manager',
            icon: Users,
        },
        {
            title: 'Announcements',
            href: '/announcements',
            icon: Megaphone,
        },
        {
            title: 'Audit Logs',
            href: '/super-admin/audit-logs',
            icon: History,
        },
        {
            title: 'Permissions',
            href: '/super-admin/permissions',
            icon: ShieldCheck,
        },
        {
            title: 'System Settings',
            href: '/super-admin/system-settings',
            icon: Settings,
        },
    ],
    admin: [
        {
            title: 'Announcements',
            href: '/announcements',
            icon: Megaphone,
        },
        {
            title: 'Academic Controls',
            href: '/admin/academic-controls',
            icon: Calendar,
            items: [
                {
                    title: 'School Year Manager',
                    href: '/admin/academic-controls',
                },
                {
                    title: 'Curriculum Manager',
                    href: '/admin/curriculum-manager',
                },
                {
                    title: 'Section Manager',
                    href: '/admin/section-manager',
                },
                {
                    title: 'Schedule Builder',
                    href: '/admin/schedule-builder',
                },
            ],
        },
        {
            title: 'Grade Verification',
            href: '/admin/grade-verification',
            icon: CheckSquare,
        },
        { title: 'Class Lists', href: '/admin/class-lists', icon: Users },
        {
            title: 'DepEd Reports',
            href: '/admin/deped-reports',
            icon: FileText,
        },
        {
            title: 'SF9 Generator',
            href: '/admin/sf9-generator',
            icon: FileSpreadsheet,
        },
    ],
    registrar: [
        {
            title: 'Announcements',
            href: '/announcements',
            icon: Megaphone,
        },
        {
            title: 'Student Directory',
            href: '/registrar/student-directory',
            icon: Users,
        },
        { title: 'Enrollment', href: '/registrar/enrollment', icon: UserPlus },
        {
            title: 'Permanent Records',
            href: '/registrar/permanent-records',
            icon: ClipboardList,
        },
        {
            title: 'Batch Promotion',
            href: '/registrar/batch-promotion',
            icon: TrendingUp,
        },
        {
            title: 'Remedial Entry',
            href: '/registrar/remedial-entry',
            icon: CheckSquare,
        },
        {
            title: 'Student Departure',
            href: '/registrar/student-departure',
            icon: UserMinus,
        },
    ],
    finance: [
        {
            title: 'Announcements',
            href: '/announcements',
            icon: Megaphone,
        },
        {
            title: 'Student Ledgers',
            href: '/finance/student-ledgers',
            icon: Wallet,
        },
        {
            title: 'Cashier Panel',
            href: '/finance/cashier-panel',
            icon: Banknote,
        },
        {
            title: 'Transaction History',
            href: '/finance/transaction-history',
            icon: History,
        },
        {
            title: 'Product Inventory',
            href: '/finance/product-inventory',
            icon: Package,
        },
        {
            title: 'Discount Manager',
            href: '/finance/discount-manager',
            icon: Tag,
        },
        {
            title: 'Fee Structure',
            href: '/finance/fee-structure',
            icon: CreditCard,
        },
        {
            title: 'Daily Reports',
            href: '/finance/daily-reports',
            icon: BarChart3,
        },
    ],
    teacher: [
        {
            title: 'Announcements',
            href: '/announcements',
            icon: Megaphone,
        },
        { title: 'Schedule', href: '/teacher/schedule', icon: Calendar },
        {
            title: 'Grading Sheet',
            href: '/teacher/grading-sheet',
            icon: CheckSquare,
        },
        {
            title: 'Advisory Board',
            href: '/teacher/advisory-board',
            icon: UserCheck,
        },
    ],
    student: [
        { title: 'Schedule', href: '/student/schedule', icon: Calendar },
        { title: 'Grades', href: '/student/grades', icon: GraduationCap },
    ],
    parent: [
        { title: 'Schedule', href: '/parent/schedule', icon: Calendar },
        { title: 'Grades', href: '/parent/grades', icon: GraduationCap },
        {
            title: 'Billing Information',
            href: '/parent/billing-information',
            icon: Receipt,
        },
    ],
};

const handheldAllowedHrefMap: Record<string, string[]> = {
    super_admin: ['/announcements', '/super-admin/audit-logs'],
    admin: ['/announcements', '/admin/grade-verification'],
    registrar: ['/announcements', '/registrar/student-directory'],
    finance: [
        '/announcements',
        '/finance/student-ledgers',
        '/finance/daily-reports',
    ],
    teacher: [
        '/announcements',
        '/teacher/schedule',
        '/teacher/grading-sheet',
        '/teacher/advisory-board',
    ],
    student: ['/student/schedule', '/student/grades'],
    parent: [
        '/parent/schedule',
        '/parent/grades',
        '/parent/billing-information',
    ],
};

const filterNavItemsForHandheld = (
    role: string,
    items: NavItem[],
): NavItem[] => {
    const allowedHrefs = handheldAllowedHrefMap[role];
    if (!allowedHrefs) {
        return items;
    }

    const visibleItems: NavItem[] = [];

    for (const item of items) {
        const filteredChildren = item.items
            ? item.items.filter((subItem) =>
                  allowedHrefs.includes(String(subItem.href)),
              )
            : [];

        if (filteredChildren.length > 0) {
            visibleItems.push({
                ...item,
                items: filteredChildren,
            });
            continue;
        }

        if (allowedHrefs.includes(String(item.href))) {
            visibleItems.push(item);
        }
    }

    return visibleItems;
};

const isStandaloneMode = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    const standaloneMediaQuery = window.matchMedia(
        '(display-mode: standalone)',
    ).matches;
    const iosStandalone = (
        window.navigator as Navigator & { standalone?: boolean }
    ).standalone;

    return standaloneMediaQuery || iosStandalone === true;
};

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const role = (auth.user.role as string) || '';
    const isHandheld = Boolean(page.props.ui?.is_handheld);
    const [deferredPrompt, setDeferredPrompt] = useState(
        getDeferredInstallPrompt,
    );
    const [isInstalled, setIsInstalled] = useState(false);
    const [isInstalling, setIsInstalling] = useState(false);
    const [isGuideOpen, setIsGuideOpen] = useState(false);
    const [installGuideMessage, setInstallGuideMessage] = useState('');

    useEffect(() => {
        if (!auth.user?.id || typeof window === 'undefined') {
            return;
        }

        let isUnmounted = false;

        if (isStandaloneMode()) {
            setIsInstalled(true);

            return;
        }

        void isLikelyAlreadyInstalled().then((alreadyInstalled) => {
            if (alreadyInstalled && !isUnmounted) {
                setIsInstalled(true);
            }
        });

        const handleInstallPromptAvailable = () => {
            setDeferredPrompt(getDeferredInstallPrompt());
        };

        const handleAppInstalled = () => {
            setIsInstalled(true);
            setDeferredPrompt(null);
        };

        handleInstallPromptAvailable();
        const removeInstallPromptListener = onInstallPromptAvailable(
            handleInstallPromptAvailable,
        );
        const removeAppInstalledListener =
            onPwaAppInstalled(handleAppInstalled);

        return () => {
            isUnmounted = true;
            removeInstallPromptListener();
            removeAppInstalledListener();
        };
    }, [auth.user?.id]);

    const installApp = async () => {
        if (!deferredPrompt || isInstalling) {
            const message = await getInstallUnavailableMessage();
            setInstallGuideMessage(message);
            setIsGuideOpen(true);

            return;
        }

        setIsInstalling(true);
        await deferredPrompt.prompt();
        const result = await deferredPrompt.userChoice;
        setIsInstalling(false);
        setDeferredPrompt(null);

        if (result.outcome === 'accepted') {
            setIsInstalled(true);
        }
    };

    const roleItems = roleNavItems[role] || [];
    const visibleRoleItems = isHandheld
        ? filterNavItemsForHandheld(role, roleItems)
        : roleItems;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...visibleRoleItems,
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {auth.user?.id && !isInstalled ? (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                onClick={installApp}
                                tooltip="Install App"
                                disabled={isInstalling}
                            >
                                <Download className="h-5 w-5" />
                                <span>
                                    {isInstalling
                                        ? 'Installing...'
                                        : 'Install App'}
                                </span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                ) : null}
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
            <PwaInstallGuideDialog
                open={isGuideOpen}
                onOpenChange={setIsGuideOpen}
                message={installGuideMessage}
            />
        </Sidebar>
    );
}
