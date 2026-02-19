import { Head } from '@inertiajs/react';
import { CheckCircle2, Clock3, TriangleAlert, UploadCloud } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

export default function StudentDirectory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Directory" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 border-b sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle>Student Directory</CardTitle>
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <Badge variant="secondary">
                                    Matched: 1,214
                                </Badge>
                                <Badge variant="outline">
                                    Pending Match: 19
                                </Badge>
                                <Badge variant="destructive">
                                    Discrepancy: 4
                                </Badge>
                            </div>
                        </div>

                        <div className="flex flex-col items-end gap-2 text-right">
                            <div className="flex items-center gap-2">
                                <input
                                    id="sf1-upload-file"
                                    type="file"
                                    className="hidden"
                                />
                                <Button asChild>
                                    <label
                                        htmlFor="sf1-upload-file"
                                        className="cursor-pointer"
                                    >
                                        <UploadCloud className="size-4" />
                                        Upload SF1
                                    </label>
                                </Button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Last SF1 upload: June 10, 2026 at 7:30 AM.
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">LRN</TableHead>
                                    <TableHead>Student</TableHead>
                                    <TableHead>Grade and Section</TableHead>
                                    <TableHead className="pr-6">
                                        LIS Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        123456789012
                                    </TableCell>
                                    <TableCell>Juan Dela Cruz</TableCell>
                                    <TableCell>Grade 7 - Rizal</TableCell>
                                    <TableCell className="pr-6">
                                        <Badge variant="secondary">
                                            <CheckCircle2 className="size-3" />
                                            Matched
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        987654321098
                                    </TableCell>
                                    <TableCell>Maria Santos</TableCell>
                                    <TableCell>Unassigned</TableCell>
                                    <TableCell className="pr-6">
                                        <Badge variant="outline">
                                            <Clock3 className="size-3" />
                                            Pending
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="pl-6">
                                        555555555555
                                    </TableCell>
                                    <TableCell>Mark Typo</TableCell>
                                    <TableCell>Unassigned</TableCell>
                                    <TableCell className="pr-6">
                                        <Badge variant="destructive">
                                            <TriangleAlert className="size-3" />
                                            Discrepancy
                                        </Badge>
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
