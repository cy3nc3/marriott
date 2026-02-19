import { Head } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
            <Head title="Enrollment" />

            <div className="flex flex-col gap-6">
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-1">
                        <CardHeader className="border-b">
                            <CardTitle>New Enrollment Intake</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="lrn">LRN</Label>
                                <Input id="lrn" placeholder="12-digit LRN" />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="first-name">
                                        First Name
                                    </Label>
                                    <Input id="first-name" placeholder="Juan" />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="last-name">Last Name</Label>
                                    <Input
                                        id="last-name"
                                        placeholder="Dela Cruz"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="emergency-contact">
                                    Emergency Contact
                                </Label>
                                <Input
                                    id="emergency-contact"
                                    placeholder="0917 123 4567"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>Payment Plan</Label>
                                <Select defaultValue="monthly">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">
                                            Cash
                                        </SelectItem>
                                        <SelectItem value="monthly">
                                            Monthly
                                        </SelectItem>
                                        <SelectItem value="quarterly">
                                            Quarterly
                                        </SelectItem>
                                        <SelectItem value="semi-annual">
                                            Semi-Annual
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="downpayment">Downpayment</Label>
                                <Input
                                    id="downpayment"
                                    type="number"
                                    placeholder="0.00"
                                />
                            </div>

                            <Button className="w-full">
                                Save Enrollment Intake
                            </Button>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader className="border-b">
                            <CardTitle>Enrollment Queue</CardTitle>
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <Badge variant="outline">
                                    Pending Intake: 38
                                </Badge>
                                <Badge variant="outline">
                                    For Cashier Payment: 21
                                </Badge>
                                <Badge variant="outline">
                                    Partial Payment: 9
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            LRN
                                        </TableHead>
                                        <TableHead>Student</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Downpayment</TableHead>
                                        <TableHead>Cashier Status</TableHead>
                                        <TableHead className="pr-6 text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="pl-6">
                                            123456789012
                                        </TableCell>
                                        <TableCell>Juan Dela Cruz</TableCell>
                                        <TableCell>Monthly</TableCell>
                                        <TableCell>PHP 3,000.00</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                Unpaid
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="pr-6">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="pl-6">
                                            987654321098
                                        </TableCell>
                                        <TableCell>Maria Santos</TableCell>
                                        <TableCell>Quarterly</TableCell>
                                        <TableCell>PHP 2,500.00</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                Partial
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="pr-6">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
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
