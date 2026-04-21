import { Link, usePage } from '@inertiajs/react';
import {
    Banknote,
    BarChart3,
    Calendar,
    CheckSquare,
    ClipboardList,
    CreditCard,
    Folder,
    GraduationCap,
    HelpCircle,
    History,
    LayoutGrid,
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
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';

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
            title: 'Data Import',
            href: '/registrar/data-import',
            icon: Folder,
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
            title: 'Data Import',
            href: '/finance/data-import',
            icon: Folder,
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
            title: 'Attendance',
            href: '/teacher/attendance',
            icon: ClipboardList,
        },
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
        '/teacher/attendance',
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

const permissionFeatureByHref: Record<string, string> = {
    '/announcements': 'Announcements',
    '/super-admin/user-manager': 'User Manager',
    '/super-admin/audit-logs': 'Audit Logs',
    '/super-admin/system-settings': 'System Configuration',
    '/admin/class-lists': 'Class Lists',
    '/admin/grade-verification': 'Grade Verification',
    '/teacher/schedule': 'My Schedule',
    '/student/schedule': 'My Schedule',
    '/parent/schedule': 'My Schedule',
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



const formatRoleLabel = (roleValue: string): string => {
    if (!roleValue) {
        return 'User';
    }

    return roleValue
        .split('_')
        .filter((chunk) => chunk.length > 0)
        .map((chunk) => chunk[0].toUpperCase() + chunk.slice(1))
        .join(' ');
};

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const role = (auth.user.role as string) || '';
    const roleLabel = formatRoleLabel(role);
    const isHandheld = Boolean(page.props.ui?.is_handheld);


    const roleItems = roleNavItems[role] || [];
    const visibleRoleItems = isHandheld
        ? filterNavItemsForHandheld(role, roleItems)
        : roleItems;

    // Permissions Filtering Logic
    const permissions = (page.props.permissions as Record<string, number>) || {};

    const resolvePermissionFeature = (item: NavItem): string | null => {
        const itemHref = String(item.href);

        if (permissionFeatureByHref[itemHref]) {
            return permissionFeatureByHref[itemHref];
        }

        if (permissions[item.title] !== undefined) {
            return item.title;
        }

        return null;
    };
    
    const filterByPermissions = (items: NavItem[]): NavItem[] => {
        // Only enforce visibility when a sidebar item is mapped to a permission feature.
        
        return items.reduce((acc: NavItem[], item) => {
            const hasChildren = item.items && item.items.length > 0;
            const feature = resolvePermissionFeature(item);
            const level = feature ? permissions[feature] : undefined;

            // If it's a leaf node (no children) and level is 0, hide it
            if (!hasChildren && feature && level === 0) {
                return acc;
            }

            // If it has children, filter them recursively
            if (hasChildren) {
                const filteredChildren = filterByPermissions(item.items!);
                // If no children remain, hide the parent as well
                if (filteredChildren.length === 0) {
                    return acc;
                }
                acc.push({ ...item, items: filteredChildren });
                return acc;
            }

            // Normal case: level > 0 or not found in matrix (default show for now)
            acc.push(item);
            return acc;
        }, []);
    };

    const permittedRoleItems = filterByPermissions(visibleRoleItems);

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...permittedRoleItems,
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo title={roleLabel} />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>

                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>

        </Sidebar>
    );
}
