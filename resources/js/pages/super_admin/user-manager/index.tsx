import { Head, useForm, router } from '@inertiajs/react';
import {
    UserPlus,
    Edit2,
    Search,
    CheckCircle2,
    KeyRound,
    UserX,
    UserCheck,
    MoreHorizontal,
    Users,
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { format } from 'date-fns';
import { DateOfBirthPicker } from '@/components/ui/date-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { Label } from '@/components/ui/label';
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
    TableFooter,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    store,
    update,
    reset_password,
    toggle_status,
} from '@/routes/super_admin/user_manager';

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
    birthday: string | null;
    role: string;
    is_active: boolean;
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
    filters: {
        search?: string;
        role?: string;
    };
}

export default function UserManager({ users, filters }: Props) {
    const [isAddUserOpen, setIsAddUserOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<User | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || 'all');

    const createForm = useForm({
        first_name: '',
        last_name: '',
        birthday: '',
        role: '',
    });

    const editForm = useForm({
        first_name: '',
        last_name: '',
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

    const handleSearch = (val: string) => {
        setSearchQuery(val);
        applyFilters(val, roleFilter);
    };

    const handleRoleFilter = (val: string) => {
        setRoleFilter(val);
        applyFilters(searchQuery, val);
    };

    const applyFilters = (search: string, role: string) => {
        router.get(
            '/super-admin/user-manager',
            {
                search: search || undefined,
                role: role === 'all' ? undefined : role,
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

    const handleResetPassword = (user: User) => {
        if (
            confirm(
                `Reset ${user.name}'s password to their birthday (YYYYMMDD)?`,
            )
        ) {
            router.post(
                reset_password(user.id).url,
                {},
                {
                    preserveScroll: true,
                },
            );
        }
    };

    const handleToggleStatus = (user: User) => {
        const action = user.is_active ? 'deactivate' : 'activate';
        if (
            confirm(
                `Are you sure you want to ${action} ${user.name}'s account?`,
            )
        ) {
            router.post(
                toggle_status(user.id).url,
                {},
                {
                    preserveScroll: true,
                },
            );
        }
    };

    const openEdit = (user: User) => {
        setEditingUser(user);
        editForm.setData({
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            birthday: user.birthday || '',
            role: user.role,
        });
    };

    const getRoleBadge = (role: string) => {
        const label =
            roleOptions.find((option) => option.value === role)?.label ||
            role.replace('_', ' ');

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
                                <div className="relative">
                                    <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search users..."
                                        className="w-[260px] pl-9"
                                        value={searchQuery}
                                        onChange={(e) =>
                                            handleSearch(e.target.value)
                                        }
                                    />
                                </div>

                                <div className="flex items-center gap-2">
                                    <Select
                                        value={roleFilter}
                                        onValueChange={handleRoleFilter}
                                    >
                                        <SelectTrigger className="w-[160px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All Roles
                                            </SelectItem>
                                            {roleOptions.map((roleOption) => (
                                                <SelectItem
                                                    key={roleOption.value}
                                                    value={roleOption.value}
                                                >
                                                    {roleOption.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
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
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="text-center">
                                        Status
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {user.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {user.email}
                                        </TableCell>
                                        <TableCell>
                                            {getRoleBadge(user.role)}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge variant="outline">
                                                {user.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
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
                                                            handleResetPassword(
                                                                user,
                                                            )
                                                        }
                                                        className="gap-2"
                                                    >
                                                        <KeyRound className="size-3.5" />
                                                        <span>
                                                            Reset Password
                                                        </span>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            handleToggleStatus(
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
                                ))}
                                {users.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-32 text-center"
                                        >
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                                <Users className="size-8 opacity-40" />
                                                <p className="text-sm">
                                                    No users found.
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                            {users.links.length > 3 && (
                                <TableFooter>
                                    <TableRow>
                                        <TableCell colSpan={5}>
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm text-muted-foreground">
                                                    {users.from ?? 0}-
                                                    {users.to ?? 0} out of{' '}
                                                    {users.total}
                                                </p>
                                                <div className="flex items-center gap-2">
                                                    {users.links.map(
                                                        (link, index) => {
                                                            let label =
                                                                link.label;
                                                            if (
                                                                label.includes(
                                                                    'Previous',
                                                                )
                                                            ) {
                                                                label =
                                                                    'Previous';
                                                            } else if (
                                                                label.includes(
                                                                    'Next',
                                                                )
                                                            ) {
                                                                label = 'Next';
                                                            } else {
                                                                label = label
                                                                    .replace(
                                                                        /&[^;]+;/g,
                                                                        '',
                                                                    )
                                                                    .trim();
                                                            }

                                                            return (
                                                                <Button
                                                                    key={`${link.label}-${index}`}
                                                                    variant="outline"
                                                                    size="sm"
                                                                    disabled={
                                                                        !link.url ||
                                                                        link.active
                                                                    }
                                                                    onClick={() => {
                                                                        if (
                                                                            link.url
                                                                        ) {
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
                                                        },
                                                    )}
                                                </div>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableFooter>
                            )}
                        </Table>
                    </CardContent>
                </Card>
            </div>

            {/* Create Dialog */}
            <Dialog open={isAddUserOpen} onOpenChange={setIsAddUserOpen}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Create Staff Account</DialogTitle>
                        <DialogDescription>
                            Add a new member to the school administration.
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
                                <Label htmlFor="birthday">Birthday</Label>
                                <DateOfBirthPicker
                                    date={
                                        createForm.data.birthday
                                            ? new Date(createForm.data.birthday)
                                            : undefined
                                    }
                                    setDate={(date) =>
                                        createForm.setData(
                                            'birthday',
                                            date
                                                ? format(date, 'yyyy-MM-dd')
                                                : '',
                                        )
                                    }
                                    className="w-full"
                                    placeholder="Select date of birth"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Password will be auto-generated from this
                                    date (YYYYMMDD).
                                </p>
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
                                        <SelectItem value="admin">
                                            Admin
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
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
                                Create Account
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
                                    placeholder="Select date of birth"
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
                                        <SelectItem value="super_admin">
                                            Super Admin
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
                                        <SelectItem value="admin">
                                            Admin
                                        </SelectItem>
                                        <SelectItem value="student">
                                            Student
                                        </SelectItem>
                                        <SelectItem value="parent">
                                            Parent
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
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
        </AppLayout>
    );
}
