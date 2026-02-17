import { Head } from '@inertiajs/react';
import { UserPlus, History } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Enrollment',
        href: '/registrar/enrollment',
    },
];

export default function Enrollment() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Quick Enrollment" />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Student{' '}
                            <span className="text-primary not-italic">
                                Enrollment
                            </span>
                        </h1>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Enrollment Form (Stub Entry) */}
                    <Card className="h-fit border-primary/20 shadow-sm lg:col-span-1">
                        <CardHeader className="border-b bg-primary/5">
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <UserPlus className="size-5 text-primary" />
                                Quick Enrollment
                            </CardTitle>
                            <CardDescription>
                                Create a student stub to enable immediate
                                collection.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-6">
                            <div className="space-y-2">
                                <Label htmlFor="lrn">LRN (Unique Anchor)</Label>
                                <Input
                                    id="lrn"
                                    placeholder="12-digit LRN"
                                    className="font-mono"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="first_name">
                                        First Name
                                    </Label>
                                    <Input id="first_name" placeholder="Juan" />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="last_name">Last Name</Label>
                                    <Input
                                        id="last_name"
                                        placeholder="Dela Cruz"
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="grade">Grade Level</Label>
                                <Select>
                                    <SelectTrigger id="grade">
                                        <SelectValue placeholder="Select Grade..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="7">
                                            Grade 7
                                        </SelectItem>
                                        <SelectItem value="8">
                                            Grade 8
                                        </SelectItem>
                                        <SelectItem value="9">
                                            Grade 9
                                        </SelectItem>
                                        <SelectItem value="10">
                                            Grade 10
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="term">Payment Term</Label>
                                <Select defaultValue="monthly">
                                    <SelectTrigger id="term">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="full">
                                            Cash / Full Payment
                                        </SelectItem>
                                        <SelectItem value="monthly">
                                            Monthly
                                        </SelectItem>
                                        <SelectItem value="quarterly">
                                            Quarterly
                                        </SelectItem>
                                        <SelectItem value="semi">
                                            Semi-Annual
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button className="mt-2 h-11 w-full font-bold tracking-wide">
                                Initialize Enrollment
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Recent Stubs Table */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/30 py-4">
                            <div className="flex items-center gap-2">
                                <History className="size-5 text-muted-foreground" />
                                <CardTitle className="text-lg">
                                    Recent Enrollment Stubs
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader className="bg-muted/20">
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            LRN
                                        </TableHead>
                                        <TableHead>Student Name</TableHead>
                                        <TableHead>Grade</TableHead>
                                        <TableHead>Term</TableHead>
                                        <TableHead className="pr-6 text-center">
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow className="transition-colors hover:bg-muted/30">
                                        <TableCell className="pl-6 font-mono text-xs">
                                            123456789012
                                        </TableCell>
                                        <TableCell className="font-bold tracking-tight text-primary">
                                            Juan Dela Cruz
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            Grade 7
                                        </TableCell>
                                        <TableCell className="text-xs font-bold text-muted-foreground uppercase">
                                            Monthly
                                        </TableCell>
                                        <TableCell className="pr-6 text-center">
                                            <div className="flex items-center justify-center gap-1.5 text-xs font-bold tracking-tighter text-orange-600 uppercase">
                                                <div className="size-1.5 animate-pulse rounded-full bg-orange-600" />
                                                Pending Sync
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
