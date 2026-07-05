import { Head, useForm, router } from '@inertiajs/react';
import { format } from 'date-fns';
import {
    UserPlus,
    Edit2,
    CheckCircle2,
    KeyRound,
    UserX,
    UserCheck,
    MoreHorizontal,
    Users,
    Filter,
    Download,
    X,
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DateOfBirthPicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import {
    store,
    update,
    reset_password,
    toggle_status,
} from '@/routes/super_admin/user_manager';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Manager',
        href: '/super-admin/user-manager',
    },
];

const roleOptions = [
    { value: 'super_admin', label: 'Super Admin' },
    { value: 'admin', label: 'Admin' },
    { value: 'registrar', label: 'Registrar' },
    { value: 'finance', label: 'Finance' },
    { value: 'teacher', label: 'Teacher' },
    { value: 'student', label: 'Student' },
    { value: 'parent', label: 'Parent' },
];

interface User {
    id: number;
    first_name: string | null;
    last_name: string | null;
    name: string;
    email: string;
    personal_email: string | null;
    birthday: string | null;
    role: string;
    is_active: boolean;
}

interface RoleLimit {
    limit: number;
    count: number;
}

interface Props {
    users: {
        data: User[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters?: {
        search?: string;
        role?: string;
        sort?: string;
        claim_status?: string;
        login_activity?: string;
    };
    role_limits?: {
        super_admin: RoleLimit;
        admin: RoleLimit;
    };
}

export default function UserManager({ users, filters, role_limits }: Props) {
    const normalizedFilters = useMemo(() => {
        if (!filters || Array.isArray(filters)) {
            return {
                search: '',
                role: 'all',
                sort: 'newest',
                claim_status: 'all',
                login_activity: 'all',
            };
        }

        return {
            search: filters.search || '',
            role: filters.role || 'all',
            sort: filters.sort || 'newest',
            claim_status: filters.claim_status || 'all',
            login_activity: filters.login_activity || 'all',
        };
    }, [filters]);

    const [isAddUserOpen, setIsAddUserOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<User | null>(null);
    const [searchQuery, setSearchQuery] = useState(normalizedFilters.search);
    const [roleFilter, setRoleFilter] = useState(normalizedFilters.role);
    const [sortFilter, setSortFilter] = useState(normalizedFilters.sort);
    const [claimStatusFilter, setClaimStatusFilter] = useState(normalizedFilters.claim_status);
    const [loginActivityFilter, setLoginActivityFilter] = useState(normalizedFilters.login_activity);
    const [confirmResetUser, setConfirmResetUser] = useState<User | null>(null);
    const [confirmToggleUser, setConfirmToggleUser] = useState<User | null>(null);

    const activeFilterCount = useMemo(() => {
        let count = 0;
        if (roleFilter !== 'all') count++;
        if (sortFilter !== 'newest') count++;
        if (claimStatusFilter !== 'all') count++;
        if (loginActivityFilter !== 'all') count++;
        return count;
    }, [roleFilter, sortFilter, claimStatusFilter, loginActivityFilter]);

    const handleResetFilters = () => {
        setRoleFilter('all');
        setSortFilter('newest');
        setClaimStatusFilter('all');
        setLoginActivityFilter('all');
        applyFilters(searchQuery, 'all', 'newest', 'all', 'all');
    };

    const createForm = useForm({
        first_name: '',
        last_name: '',
        personal_email: '',
        role: '',
    });

    const editForm = useForm({
        first_name: '',
        last_name: '',
        personal_email: '',
        birthday: '',
        role: '',
    });

    // Auto-generate email preview
    const emailPreview = useMemo(() => {
        const fnPart = createForm.data.first_name
            .trim()
            .split(' ')[0]
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '');
        const lnPart = createForm.data.last_name
            .trim()
            .toLowerCase()
            .replace(/\s+/g, '')
            .replace(/[^a-z0-9]/g, '');

        if (!fnPart && !lnPart) return '';
        return `${fnPart}${fnPart && lnPart ? '.' : ''}${lnPart}@marriott.edu`;
    }, [createForm.data.first_name, createForm.data.last_name]);

    const searchSuggestions = useMemo(
        () =>
            users.data.map((user) => ({
                id: user.id,
                label: user.name,
                value: user.name,
                description: user.email,
                keywords: user.role,
            })),
        [users.data],
    );

    const handleSearch = (val: string) => {
        setSearchQuery(val);
        applyFilters(val, roleFilter, sortFilter, claimStatusFilter, loginActivityFilter);
    };

    const handleRoleFilter = (val: string) => {
        setRoleFilter(val);
        applyFilters(searchQuery, val, sortFilter, claimStatusFilter, loginActivityFilter);
    };

    const handleSortFilter = (val: string) => {
        setSortFilter(val);
        applyFilters(searchQuery, roleFilter, val, claimStatusFilter, loginActivityFilter);
    };

    const handleClaimStatusFilter = (val: string) => {
        setClaimStatusFilter(val);
        applyFilters(searchQuery, roleFilter, sortFilter, val, loginActivityFilter);
    };

    const handleLoginActivityFilter = (val: string) => {
        setLoginActivityFilter(val);
        applyFilters(searchQuery, roleFilter, sortFilter, claimStatusFilter, val);
    };

    const applyFilters = (
        search: string,
        role: string,
        sort: string,
        claimStatus: string,
        loginActivity: string,
    ) => {
        router.get(
            '/super-admin/user-manager',
            {
                search: search || undefined,
                role: role === 'all' ? undefined : role,
                sort: sort === 'newest' ? undefined : sort,
                claim_status: claimStatus === 'all' ? undefined : claimStatus,
                login_activity: loginActivity === 'all' ? undefined : loginActivity,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleCreateAccount = () => {
        createForm.post(store().url, {
            onSuccess: () => {
                setIsAddUserOpen(false);
                createForm.reset();
            },
        });
    };

    const handleUpdateAccount = () => {
        if (!editingUser) return;
        editForm.patch(update(editingUser.id).url, {
            onSuccess: () => {
                setEditingUser(null);
                editForm.reset();
            },
        });
    };

    const submitUpdatePassword = () => {
        if (!confirmResetUser) return;
        router.post(
            reset_password(confirmResetUser.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => setConfirmResetUser(null),
            },
        );
    };

    const submitToggleStatus = () => {
        if (!confirmToggleUser) return;
        router.post(
            toggle_status(confirmToggleUser.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => setConfirmToggleUser(null),
            },
        );
    };

    const openEdit = (user: User) => {
        setEditingUser(user);
        editForm.setData({
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            personal_email: user.personal_email || '',
            birthday: user.birthday || '',
            role: user.role,
        });
    };

    const isStaffRole = (role?: string | null) =>
        ['super_admin', 'admin', 'registrar', 'finance', 'teacher'].includes(
            role || '',
        );

    const getLimitedRoleStatus = (
        role?: string | null,
        currentUser?: User | null,
    ) => {
        if (role !== 'super_admin' && role !== 'admin') {
            return { isLimited: false, isReached: false };
        }

        const roleLimit = role_limits?.[role];
        const currentUserHasRole = currentUser?.role === role;
        const usedCount = Math.max(
            0,
            (roleLimit?.count ?? 0) - (currentUserHasRole ? 1 : 0),
        );

        return {
            isLimited: true,
            isReached: usedCount >= (roleLimit?.limit ?? 1),
        };
    };

    const getRoleLabel = (role: string) =>
        roleOptions.find((option) => option.value === role)?.label ||
        role.replace('_', ' ');

    const getLimitedRoleLabel = (role: string, currentUser?: User | null) => {
        const label = getRoleLabel(role);

        return getLimitedRoleStatus(role, currentUser).isReached
            ? `${label} (limit reached)`
            : label;
    };

    const getRoleBadge = (role: string) => {
        const label = getRoleLabel(role);

        return <Badge variant="outline">{label}</Badge>;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Manager" />
            <div className="flex flex-col gap-6">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex flex-col gap-4 border-b p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-wrap items-center gap-3">
                                <Badge variant="secondary" className="h-9 px-3 text-sm">
                                    Total Users: {users.total}
                                </Badge>
                                <SearchAutocompleteInput
                                    placeholder="Search users..."
                                    wrapperClassName="w-[260px]"
                                    value={searchQuery}
                                    onValueChange={handleSearch}
                                    suggestions={searchSuggestions}
                                />

                                <div className="flex items-center gap-2">
                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-9 gap-2"
                                            >
                                                <Filter className="size-4" />
                                                Filters
                                                {activeFilterCount > 0 && (
                                                    <>
                                                        <Separator
                                                            orientation="vertical"
                                                            className="mx-1 h-4"
                                                        />
                                                        <Badge
                                                            variant="secondary"
                                                            className="rounded-sm px-1 font-normal"
                                                        >
                                                            {activeFilterCount}
                                                        </Badge>
                                                    </>
                                                )}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent
                                            className="w-[280px] p-4"
                                            align="start"
                                        >
                                            <div className="space-y-4">
                                                <div className="flex items-center justify-between">
                                                    <h4 className="font-medium leading-none">
                                                        Filters
                                                    </h4>
                                                    {activeFilterCount > 0 && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={
                                                                handleResetFilters
                                                            }
                                                            className="h-auto p-0 text-xs text-muted-foreground hover:text-foreground"
                                                        >
                                                            Reset
                                                            <X className="ml-1 size-3" />
                                                        </Button>
                                                    )}
                                                </div>
                                                <Separator />
                                                <div className="grid gap-4">
                                                    <div className="space-y-2">
                                                        <Label>Role</Label>
                                                        <Select
                                                            value={roleFilter}
                                                            onValueChange={
                                                                handleRoleFilter
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">
                                                                    All Roles
                                                                </SelectItem>
                                                                {roleOptions.map(
                                                                    (
                                                                        roleOption,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                roleOption.value
                                                                            }
                                                                            value={
                                                                                roleOption.value
                                                                            }
                                                                        >
                                                                            {
                                                                                roleOption.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label>Sort By</Label>
                                                        <Select
                                                            value={sortFilter}
                                                            onValueChange={
                                                                handleSortFilter
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="newest">
                                                                    Newest First
                                                                </SelectItem>
                                                                <SelectItem value="oldest">
                                                                    Oldest First
                                                                </SelectItem>
                                                                <SelectItem value="az">
                                                                    Name (A-Z)
                                                                </SelectItem>
                                                                <SelectItem value="za">
                                                                    Name (Z-A)
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label>
                                                            Claim Status
                                                        </Label>
                                                        <Select
                                                            value={
                                                                claimStatusFilter
                                                            }
                                                            onValueChange={
                                                                handleClaimStatusFilter
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">
                                                                    All Claim
                                                                    Status
                                                                </SelectItem>
                                                                <SelectItem value="claimed">
                                                                    Claimed
                                                                </SelectItem>
                                                                <SelectItem value="unclaimed">
                                                                    Unclaimed
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label>
                                                            Login Activity
                                                        </Label>
                                                        <Select
                                                            value={
                                                                loginActivityFilter
                                                            }
                                                            onValueChange={
                                                                handleLoginActivityFilter
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">
                                                                    All Login
                                                                    Activity
                                                                </SelectItem>
                                                                <SelectItem value="never">
                                                                    Never Logged
                                                                    In
                                                                </SelectItem>
                                                                <SelectItem value="stale_90">
                                                                    Stale (90+
                                                                    days)
                                                                </SelectItem>
                                                                <SelectItem value="recent_30">
                                                                    Active (30
                                                                    days)
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>
                                            </div>
                                        </PopoverContent>
                                    </Popover>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="outline" size="sm">
                                                <Download className="mr-2 size-4" />
                                                Export
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem>
                                                Export as Excel (.xlsx)
                                            </DropdownMenuItem>
                                            <DropdownMenuItem>
                                                Export as CSV (.csv)
                                            </DropdownMenuItem>
                                            <DropdownMenuItem>
                                                Export as PDF (.pdf)
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>

                            <Button
                                size="sm"
                                onClick={() => setIsAddUserOpen(true)}
                            >
                                <UserPlus className="size-4" />
                                Create Account
                            </Button>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Name</TableHead>
                                    <TableHead className="border-l">
                                        Account Email
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Personal Email
                                    </TableHead>
                                    <TableHead className="border-l">
                                        Role
                                    </TableHead>
                                    <TableHead className="border-l text-center">
                                        Status
                                    </TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24">
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                                <Users className="size-8 opacity-40" />
                                                <p className="text-sm">
                                                    No users found.
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    users.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="pl-6 font-medium">
                                                {user.name}
                                            </TableCell>
                                            <TableCell className="border-l text-muted-foreground">
                                                {user.email}
                                            </TableCell>
                                            <TableCell className="border-l text-muted-foreground">
                                                {user.personal_email || '—'}
                                            </TableCell>
                                            <TableCell className="border-l">
                                                {getRoleBadge(user.role)}
                                            </TableCell>
                                            <TableCell className="border-l text-center">
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        user.is_active
                                                            ? 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
                                                            : 'bg-red-500/15 text-red-700 hover:bg-red-500/25 dark:text-red-400 border-red-200 dark:border-red-800'
                                                    }
                                                >
                                                    {user.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="border-l pr-6 text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <MoreHorizontal className="size-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuLabel>
                                                            Actions
                                                        </DropdownMenuLabel>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                openEdit(user)
                                                            }
                                                            className="gap-2"
                                                        >
                                                            <Edit2 className="size-3.5" />
                                                            <span>
                                                                Edit Details
                                                            </span>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                setConfirmResetUser(
                                                                    user,
                                                                )
                                                            }
                                                            className="gap-2"
                                                        >
                                                            <KeyRound className="size-3.5" />
                                                            <span>
                                                                {isStaffRole(
                                                                    user.role,
                                                                )
                                                                    ? 'Send Claim Email'
                                                                    : 'Reset Password'}
                                                            </span>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                setConfirmToggleUser(
                                                                    user,
                                                                )
                                                            }
                                                            className="gap-2"
                                                        >
                                                            {user.is_active ? (
                                                                <>
                                                                    <UserX className="size-3.5" />
                                                                    <span>
                                                                        Deactivate
                                                                        Account
                                                                    </span>
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <UserCheck className="size-3.5" />
                                                                    <span>
                                                                        Activate
                                                                        Account
                                                                    </span>
                                                                </>
                                                            )}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        {users.links.length > 3 && (
                            <div className="flex items-center justify-between border-t p-4">
                                <p className="text-sm text-muted-foreground">
                                    {users.from ?? 0}-{users.to ?? 0} out of{' '}
                                    {users.total}
                                </p>
                                <div className="flex items-center gap-2">
                                    {users.links.map((link, index) => {
                                        let label = link.label;
                                        if (label.includes('Previous')) {
                                            label = 'Previous';
                                        } else if (label.includes('Next')) {
                                            label = 'Next';
                                        } else {
                                            label = label
                                                .replace(/&[^;]+;/g, '')
                                                .trim();
                                        }

                                        return (
                                            <Button
                                                key={`${link.label}-${index}`}
                                                variant="outline"
                                                size="sm"
                                                disabled={
                                                    !link.url || link.active
                                                }
                                                onClick={() => {
                                                    if (link.url) {
                                                        router.get(
                                                            link.url,
                                                            {},
                                                            {
                                                                preserveState: true,
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                {label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Create Dialog */}
            <Dialog open={isAddUserOpen} onOpenChange={setIsAddUserOpen}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Create Staff Account</DialogTitle>
                        <DialogDescription>
                            Add a new staff account and send a claim link to
                            their personal email.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            handleCreateAccount();
                        }}
                    >
                        <div className="grid gap-4 py-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">
                                        First Name
                                    </Label>
                                    <Input
                                        id="first_name"
                                        placeholder="Juan"
                                        value={createForm.data.first_name}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'first_name',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">Last Name</Label>
                                    <Input
                                        id="last_name"
                                        placeholder="Dela Cruz"
                                        value={createForm.data.last_name}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'last_name',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                            </div>

                            {emailPreview && (
                                <div className="flex items-center justify-between rounded-lg border p-3">
                                    <div className="space-y-0.5">
                                        <p className="text-xs text-muted-foreground">
                                            Email Preview
                                        </p>
                                        <p className="text-sm font-medium">
                                            {emailPreview}
                                        </p>
                                    </div>
                                    <CheckCircle2 className="size-4 text-muted-foreground" />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="personal_email">
                                    Personal Email
                                </Label>
                                <Input
                                    id="personal_email"
                                    type="email"
                                    placeholder="staff.personal@gmail.com"
                                    value={createForm.data.personal_email}
                                    onChange={(e) =>
                                        createForm.setData(
                                            'personal_email',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    The claim email will be sent here. The
                                    staff member will set their own password
                                    through the claim link.
                                </p>
                                <InputError
                                    message={createForm.errors.personal_email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">System Role</Label>
                                <Select
                                    value={createForm.data.role}
                                    onValueChange={(val) =>
                                        createForm.setData('role', val)
                                    }
                                    required
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="registrar">
                                            Registrar
                                        </SelectItem>
                                        <SelectItem value="finance">
                                            Finance
                                        </SelectItem>
                                        <SelectItem value="teacher">
                                            Teacher
                                        </SelectItem>
                                        <SelectItem
                                            value="admin"
                                            disabled={
                                                getLimitedRoleStatus('admin')
                                                    .isReached
                                            }
                                        >
                                            {getLimitedRoleLabel('admin')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    Admin and Super Admin accounts are limited
                                    to one account each.
                                </p>
                                <InputError message={createForm.errors.role} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAddUserOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                            >
                                Create Account & Send Claim Email
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <Dialog
                open={!!editingUser}
                onOpenChange={() => setEditingUser(null)}
            >
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Staff Account</DialogTitle>
                        <DialogDescription>
                            Update account details for{' '}
                            <span className="font-medium text-foreground">
                                {editingUser?.name}
                            </span>
                            .
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            handleUpdateAccount();
                        }}
                    >
                        <div className="grid gap-4 py-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit_first_name">
                                        First Name
                                    </Label>
                                    <Input
                                        id="edit_first_name"
                                        value={editForm.data.first_name}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'first_name',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="edit_last_name">
                                        Last Name
                                    </Label>
                                    <Input
                                        id="edit_last_name"
                                        value={editForm.data.last_name}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'last_name',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit_personal_email">
                                    Personal Email
                                </Label>
                                <Input
                                    id="edit_personal_email"
                                    type="email"
                                    value={editForm.data.personal_email}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'personal_email',
                                            e.target.value,
                                        )
                                    }
                                    required={isStaffRole(editForm.data.role)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Required for staff claim emails and password
                                    reset claim links.
                                </p>
                                <InputError
                                    message={editForm.errors.personal_email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit_birthday">Birthday</Label>
                                <DateOfBirthPicker
                                    date={
                                        editForm.data.birthday
                                            ? new Date(editForm.data.birthday)
                                            : undefined
                                    }
                                    setDate={(date) =>
                                        editForm.setData(
                                            'birthday',
                                            date
                                                ? format(date, 'yyyy-MM-dd')
                                                : '',
                                        )
                                    }
                                    className="w-full"
                                    placeholder="Optional date of birth"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit_role">System Role</Label>
                                <Select
                                    value={editForm.data.role || ''}
                                    onValueChange={(val) =>
                                        editForm.setData('role', val)
                                    }
                                    required
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            value="super_admin"
                                            disabled={
                                                getLimitedRoleStatus(
                                                    'super_admin',
                                                    editingUser,
                                                ).isReached
                                            }
                                        >
                                            {getLimitedRoleLabel(
                                                'super_admin',
                                                editingUser,
                                            )}
                                        </SelectItem>
                                        <SelectItem value="registrar">
                                            Registrar
                                        </SelectItem>
                                        <SelectItem value="finance">
                                            Finance
                                        </SelectItem>
                                        <SelectItem value="teacher">
                                            Teacher
                                        </SelectItem>
                                        <SelectItem
                                            value="admin"
                                            disabled={
                                                getLimitedRoleStatus(
                                                    'admin',
                                                    editingUser,
                                                ).isReached
                                            }
                                        >
                                            {getLimitedRoleLabel(
                                                'admin',
                                                editingUser,
                                            )}
                                        </SelectItem>
                                        <SelectItem value="student">
                                            Student
                                        </SelectItem>
                                        <SelectItem value="parent">
                                            Parent
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    Admin and Super Admin accounts are limited
                                    to one account each.
                                </p>
                                <InputError message={editForm.errors.role} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingUser(null)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                            >
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ActionConfirmDialog
                open={!!confirmResetUser}
                onOpenChange={(open) => !open && setConfirmResetUser(null)}
                title={
                    isStaffRole(confirmResetUser?.role)
                        ? 'Send Claim Email'
                        : 'Reset Password'
                }
                description={
                    isStaffRole(confirmResetUser?.role)
                        ? `Send a new account-claim email to ${confirmResetUser?.personal_email || 'the staff personal email'}? Their current password will be disabled until they set a new one.`
                        : `Are you sure you want to reset ${confirmResetUser?.name}'s password? It will be set back to the default format ([first-name-token]@MMDDYYYY).`
                }
                confirmLabel={
                    isStaffRole(confirmResetUser?.role)
                        ? 'Send Claim Email'
                        : 'Reset Password'
                }
                variant="warning"
                onConfirm={submitUpdatePassword}
            />

            <ActionConfirmDialog
                open={!!confirmToggleUser}
                onOpenChange={(open) => !open && setConfirmToggleUser(null)}
                title={
                    confirmToggleUser?.is_active
                        ? 'Deactivate Account'
                        : 'Activate Account'
                }
                description={
                    confirmToggleUser?.is_active
                        ? `Are you sure you want to deactivate ${confirmToggleUser?.name}'s account? They will no longer be able to log in.`
                        : `Are you sure you want to reactivate ${confirmToggleUser?.name}'s account?`
                }
                confirmLabel={
                    confirmToggleUser?.is_active
                        ? 'Deactivate'
                        : 'Activate'
                }
                variant={
                    confirmToggleUser?.is_active ? 'destructive' : 'default'
                }
                onConfirm={submitToggleStatus}
            />
        </AppLayout>
    );
}
