import { Link, usePage } from '@inertiajs/react';
import {
    Banknote,
    BarChart3,
    BookOpen,
    Calendar,
    CheckSquare,
    ClipboardList,
    CreditCard,
    FileSpreadsheet,
    FileText,
    Folder,
    GraduationCap,
    History,
    LayoutGrid,
    Layers,
    Package,
    Receipt,
    Settings,
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
import type { NavItem, SharedData } from '@/types';
import AppLogo from './app-logo';
import { dashboard } from '@/routes';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
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
            title: 'System Settings',
            href: '/super-admin/system-settings',
            icon: Settings,
        },
    ],
    admin: [
        {
            title: 'Academic Controls',
            href: '/admin/academic-controls',
            icon: Calendar,
        },
        {
            title: 'Curriculum Manager',
            href: '/admin/curriculum-manager',
            icon: BookOpen,
        },
        {
            title: 'Section Manager',
            href: '/admin/section-manager',
            icon: Layers,
        },
        {
            title: 'Schedule Builder',
            href: '/admin/schedule-builder',
            icon: Calendar,
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

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const role = (auth.user.role as string) || '';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(roleNavItems[role] || []),
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
