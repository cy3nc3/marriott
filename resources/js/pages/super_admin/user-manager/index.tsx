import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { 
    UserPlus, 
    MoreVertical, 
    Mail, 
    ShieldCheck, 
    Calendar 
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Manager',
        href: '/super-admin/user-manager',
    },
];

export default function UserManager() {
    const [isAddUserOpen, setIsAddUserOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Manager" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/30">
                        <CardTitle className="text-lg">Staff Accounts</CardTitle>
                        
                        <Dialog open={isAddUserOpen} onOpenChange={setIsAddUserOpen}>
                            <DialogTrigger asChild>
                                <Button size="sm" className="gap-2">
                                    <UserPlus className="size-4" />
                                    Create Staff Account
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-[425px]">
                                <DialogHeader>
                                    <DialogTitle>Create Staff Account</DialogTitle>
                                    <DialogDescription>
                                        Add a new member to the school administration.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Full Name</Label>
                                        <Input id="name" placeholder="Enter full name..." />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email Address</Label>
                                        <Input id="email" type="email" placeholder="email@marriott.edu" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="role">System Role</Label>
                                        <Select>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a role..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="registrar">Registrar</SelectItem>
                                                <SelectItem value="finance">Finance</SelectItem>
                                                <SelectItem value="teacher">Teacher</SelectItem>
                                                <SelectItem value="admin">Admin (Principal)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="password">Temporary Password</Label>
                                        <Input id="password" type="password" />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button variant="outline" onClick={() => setIsAddUserOpen(false)}>Cancel</Button>
                                    <Button onClick={() => setIsAddUserOpen(false)}>Create Account</Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6">Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="text-center">Status</TableHead>
                                    <TableHead className="text-right pr-6">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="hover:bg-muted/30 transition-colors">
                                    <TableCell className="pl-6 font-bold">Francis Raagas</TableCell>
                                    <TableCell className="text-muted-foreground">francis@marriott.edu</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" className="bg-indigo-50 text-indigo-700 border-indigo-200">Registrar</Badge>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Active</Badge>
                                    </TableCell>
                                    <TableCell className="text-right pr-6">
                                        <Button variant="ghost" size="icon" className="size-8">
                                            <MoreVertical className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="hover:bg-muted/30 transition-colors">
                                    <TableCell className="pl-6 font-bold">Glezyl Solitario</TableCell>
                                    <TableCell className="text-muted-foreground">glezyl@marriott.edu</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" className="bg-purple-50 text-purple-700 border-purple-200">Finance</Badge>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Active</Badge>
                                    </TableCell>
                                    <TableCell className="text-right pr-6">
                                        <Button variant="ghost" size="icon" className="size-8">
                                            <MoreVertical className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
