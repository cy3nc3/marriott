import { Head, useForm, router } from '@inertiajs/react';
import {
    UserPlus,
    Edit2,
    Search,
    ShieldCheck,
    Filter,
    CheckCircle2,
    KeyRound,
    UserX,
    UserCheck,
    MoreHorizontal,
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
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
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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
    users: User[];
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
        const colors: Record<string, string> = {
            admin: 'bg-amber-50 text-amber-700 border-amber-200',
            registrar: 'bg-indigo-50 text-indigo-700 border-indigo-200',
            finance: 'bg-purple-50 text-purple-700 border-purple-200',
            teacher: 'bg-blue-50 text-blue-700 border-blue-200',
            super_admin: 'bg-rose-50 text-rose-700 border-rose-200',
        };
        return (
            <Badge
                variant="outline"
                className={`${colors[role] || 'bg-muted text-muted-foreground'} text-[10px] font-bold tracking-tighter uppercase`}
            >
                {role.replace('_', ' ')}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Manager" />
            <div className="flex flex-col gap-6">
                <Card className="flex flex-col pt-0">
                    <CardHeader className="flex flex-col items-start justify-between gap-4 border-b md:flex-row md:items-center">
                        <div className="flex flex-wrap items-center gap-3">
                            <div className="relative">
                                <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search users..."
                                    className="h-9 w-[250px] pl-9 text-xs font-bold"
                                    value={searchQuery}
                                    onChange={(e) =>
                                        handleSearch(e.target.value)
                                    }
                                />
                            </div>

                            <div className="mx-1 hidden h-4 w-px bg-border sm:block" />

                            <div className="flex items-center gap-2">
                                <Label className="text-[10px] font-black tracking-wider text-muted-foreground uppercase">
                                    Role:
                                </Label>
                                <Select
                                    value={roleFilter}
                                    onValueChange={handleRoleFilter}
                                >
                                    <SelectTrigger className="h-8 w-[130px] text-xs font-bold">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Roles
                                        </SelectItem>
                                        <SelectItem value="admin">
                                            Admin
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
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <Button
                            size="sm"
                            className="h-9 gap-2"
                            onClick={() => setIsAddUserOpen(true)}
                        >
                            <UserPlus className="size-4" />
                            <span className="text-xs font-bold">
                                Create Account
                            </span>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="pl-6 text-[10px] font-black uppercase">
                                        Name
                                    </TableHead>
                                    <TableHead className="text-[10px] font-black uppercase">
                                        Email
                                    </TableHead>
                                    <TableHead className="text-[10px] font-black uppercase">
                                        Role
                                    </TableHead>
                                    <TableHead className="text-center text-[10px] font-black uppercase">
                                        Status
                                    </TableHead>
                                    <TableHead className="pr-6 text-right text-[10px] font-black uppercase">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.map((user) => (
                                    <TableRow
                                        key={user.id}
                                        className="transition-colors hover:bg-muted/30"
                                    >
                                        <TableCell className="pl-6 font-bold">
                                            {user.name}
                                        </TableCell>
                                        <TableCell className="text-xs text-muted-foreground">
                                            {user.email}
                                        </TableCell>
                                        <TableCell>
                                            {getRoleBadge(user.role)}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    'text-[10px] font-bold tracking-tighter uppercase',
                                                    user.is_active
                                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                        : 'border-rose-200 bg-rose-50 text-rose-700',
                                                )}
                                            >
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
                                                        className="size-8"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent
                                                    align="end"
                                                    className="w-48"
                                                >
                                                    <DropdownMenuLabel className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
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
                                                        className="gap-2 text-amber-600 focus:bg-amber-50 focus:text-amber-700"
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
                                                        className={cn(
                                                            'gap-2',
                                                            user.is_active
                                                                ? 'text-destructive focus:text-destructive'
                                                                : 'text-emerald-600 focus:bg-emerald-50 focus:text-emerald-700',
                                                        )}
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
                                {users.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-32 text-center"
                                        >
                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground/50">
                                                <Users className="size-8 opacity-20" />
                                                <p className="text-xs font-medium italic">
                                                    No users found
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            {/* Create Dialog */}
            <Dialog open={isAddUserOpen} onOpenChange={setIsAddUserOpen}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <UserPlus className="size-5 text-primary" />
                            Create Staff Account
                        </DialogTitle>
                        <DialogDescription className="text-xs italic">
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
                                    <Label
                                        htmlFor="first_name"
                                        className="text-[10px] font-black text-muted-foreground uppercase"
                                    >
                                        First Name
                                    </Label>
                                    <Input
                                        id="first_name"
                                        placeholder="Juan"
                                        className="font-bold"
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
                                    <Label
                                        htmlFor="last_name"
                                        className="text-[10px] font-black text-muted-foreground uppercase"
                                    >
                                        Last Name
                                    </Label>
                                    <Input
                                        id="last_name"
                                        placeholder="Dela Cruz"
                                        className="font-bold"
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
                                <div className="flex items-center justify-between rounded-lg border border-primary/10 bg-primary/5 p-3">
                                    <div className="space-y-0.5">
                                        <p className="text-[9px] font-black text-primary uppercase">
                                            Email Preview
                                        </p>
                                        <p className="text-xs font-bold text-muted-foreground">
                                            {emailPreview}
                                        </p>
                                    </div>
                                    <CheckCircle2 className="size-4 text-emerald-500" />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="birthday"
                                    className="text-[10px] font-black text-muted-foreground uppercase"
                                >
                                    Birthday
                                </Label>
                                <Input
                                    id="birthday"
                                    type="date"
                                    className="font-bold"
                                    value={createForm.data.birthday}
                                    onChange={(e) =>
                                        createForm.setData(
                                            'birthday',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <p className="mt-1 text-[9px] text-muted-foreground italic">
                                    Password will be auto-generated from this
                                    date (YYYYMMDD).
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="role"
                                    className="text-[10px] font-black text-muted-foreground uppercase"
                                >
                                    System Role
                                </Label>
                                <Select
                                    value={createForm.data.role}
                                    onValueChange={(val) =>
                                        createForm.setData('role', val)
                                    }
                                    required
                                >
                                    <SelectTrigger className="font-bold">
                                        <SelectValue placeholder="Select a role..." />
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
                                            Admin (Principal)
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
                                className="text-xs font-bold uppercase"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                                className="text-xs font-bold uppercase"
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
                        <DialogTitle className="flex items-center gap-2">
                            <Edit2 className="size-5 text-primary" />
                            Edit Staff Account
                        </DialogTitle>
                        <DialogDescription className="text-xs italic">
                            Update account details for{' '}
                            <span className="font-bold text-primary">
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
                                    <Label
                                        htmlFor="edit_first_name"
                                        className="text-[10px] font-black text-muted-foreground uppercase"
                                    >
                                        First Name
                                    </Label>
                                    <Input
                                        id="edit_first_name"
                                        className="font-bold"
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
                                    <Label
                                        htmlFor="edit_last_name"
                                        className="text-[10px] font-black text-muted-foreground uppercase"
                                    >
                                        Last Name
                                    </Label>
                                    <Input
                                        id="edit_last_name"
                                        className="font-bold"
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
                                <Label
                                    htmlFor="edit_birthday"
                                    className="text-[10px] font-black text-muted-foreground uppercase"
                                >
                                    Birthday
                                </Label>
                                <Input
                                    id="edit_birthday"
                                    type="date"
                                    className="font-bold"
                                    value={editForm.data.birthday}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'birthday',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="edit_role"
                                    className="text-[10px] font-black text-muted-foreground uppercase"
                                >
                                    System Role
                                </Label>
                                <Select
                                    value={editForm.data.role}
                                    onValueChange={(val) =>
                                        editForm.setData('role', val)
                                    }
                                    required
                                >
                                    <SelectTrigger className="font-bold">
                                        <SelectValue placeholder="Select a role..." />
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
                                            Admin (Principal)
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
                                className="text-xs font-bold uppercase"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="text-xs font-bold uppercase"
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
