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
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Enrollment Form (Stub Entry) */}
                    <Card className="lg:col-span-1 border-primary/20 shadow-sm h-fit">
                        <CardHeader className="bg-primary/5 border-b">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <UserPlus className="size-5 text-primary" />
                                Quick Enrollment
                            </CardTitle>
                            <CardDescription>Create a student stub to enable immediate collection.</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="lrn">LRN (Unique Anchor)</Label>
                                <Input id="lrn" placeholder="12-digit LRN" className="font-mono" />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="first_name">First Name</Label>
                                    <Input id="first_name" placeholder="Juan" />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="last_name">Last Name</Label>
                                    <Input id="last_name" placeholder="Dela Cruz" />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="grade">Grade Level</Label>
                                <Select>
                                    <SelectTrigger id="grade">
                                        <SelectValue placeholder="Select Grade..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="7">Grade 7</SelectItem>
                                        <SelectItem value="8">Grade 8</SelectItem>
                                        <SelectItem value="9">Grade 9</SelectItem>
                                        <SelectItem value="10">Grade 10</SelectItem>
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
                                        <SelectItem value="full">Cash / Full Payment</SelectItem>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                        <SelectItem value="quarterly">Quarterly</SelectItem>
                                        <SelectItem value="semi">Semi-Annual</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button className="w-full h-11 font-bold tracking-wide mt-2">
                                Initialize Enrollment
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Recent Stubs Table */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between bg-muted/30 border-b space-y-0 py-4">
                            <div className="flex items-center gap-2">
                                <History className="size-5 text-muted-foreground" />
                                <CardTitle className="text-lg">Recent Enrollment Stubs</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader className="bg-muted/20">
                                    <TableRow>
                                        <TableHead className="pl-6">LRN</TableHead>
                                        <TableHead>Student Name</TableHead>
                                        <TableHead>Grade</TableHead>
                                        <TableHead>Term</TableHead>
                                        <TableHead className="text-center pr-6">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow className="hover:bg-muted/30 transition-colors">
                                        <TableCell className="pl-6 font-mono text-xs">123456789012</TableCell>
                                        <TableCell className="font-bold tracking-tight text-primary">Juan Dela Cruz</TableCell>
                                        <TableCell className="font-medium">Grade 7</TableCell>
                                        <TableCell className="text-xs uppercase font-bold text-muted-foreground">Monthly</TableCell>
                                        <TableCell className="text-center pr-6">
                                            <div className="flex items-center justify-center gap-1.5 text-xs font-bold text-orange-600 uppercase tracking-tighter">
                                                <div className="size-1.5 rounded-full bg-orange-600 animate-pulse" />
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
