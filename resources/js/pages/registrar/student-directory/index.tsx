import { Head } from '@inertiajs/react';
import {
    Users,
    UploadCloud,
    Search,
    AlertTriangle,
    CheckCircle2,
    Clock,
    MoreVertical,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
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
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

export default function StudentDirectory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Directory" />
            <div className="flex flex-col gap-6">
                <div className="mb-2 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Student{' '}
                            <span className="text-primary not-italic">
                                Directory
                            </span>
                        </h1>
                    </div>
                    <Button
                        variant="outline"
                        className="gap-2 border-primary/20 hover:bg-primary/5"
                    >
                        <UploadCloud className="size-4 text-primary" />
                        Upload SF1 (LIS Sync)
                    </Button>
                </div>

                <Card>
                    <CardHeader className="border-b bg-muted/30 py-4">
                        <div className="flex flex-wrap items-center gap-4">
                            <div className="relative w-full max-w-sm">
                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search name or LRN..."
                                    className="pl-10"
                                />
                            </div>
                            <Select defaultValue="all">
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Verification" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All Status
                                    </SelectItem>
                                    <SelectItem value="verified">
                                        LIS Verified
                                    </SelectItem>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                    <SelectItem value="error">
                                        Discrepancy
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select>
                                <SelectTrigger className="w-[150px]">
                                    <SelectValue placeholder="Grade Level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="7">Grade 7</SelectItem>
                                    <SelectItem value="8">Grade 8</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/20">
                                <TableRow>
                                    <TableHead className="pl-6">LRN</TableHead>
                                    <TableHead>Student Name</TableHead>
                                    <TableHead>Grade & Section</TableHead>
                                    <TableHead className="text-center">
                                        Verification
                                    </TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="transition-colors hover:bg-muted/30">
                                    <TableCell className="pl-6 font-mono text-xs">
                                        123456789012
                                    </TableCell>
                                    <TableCell className="font-bold">
                                        Juan Dela Cruz
                                    </TableCell>
                                    <TableCell>
                                        <div className="text-sm font-medium">
                                            Grade 7 - Rizal
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge
                                            variant="outline"
                                            className="gap-1 border-green-200 bg-green-50 text-green-700"
                                        >
                                            <CheckCircle2 className="size-3" />
                                            LIS Verified
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                        >
                                            <MoreVertical className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="transition-colors hover:bg-muted/30">
                                    <TableCell className="pl-6 font-mono text-xs">
                                        987654321098
                                    </TableCell>
                                    <TableCell className="font-bold">
                                        Maria Santos
                                    </TableCell>
                                    <TableCell>
                                        <div className="text-sm text-muted-foreground italic">
                                            Unassigned
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge
                                            variant="outline"
                                            className="gap-1 border-orange-200 bg-orange-50 text-orange-700"
                                        >
                                            <Clock className="size-3" />
                                            Pending Sync
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                        >
                                            <MoreVertical className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow className="bg-destructive/5 transition-colors hover:bg-muted/30">
                                    <TableCell className="pl-6 font-mono text-xs text-destructive">
                                        555555555555
                                    </TableCell>
                                    <TableCell className="font-bold">
                                        Mark Typo
                                    </TableCell>
                                    <TableCell>
                                        <div className="text-sm text-muted-foreground italic">
                                            Unassigned
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge
                                            variant="destructive"
                                            className="animate-pulse gap-1 text-[10px] uppercase"
                                        >
                                            <AlertTriangle className="size-3" />
                                            Discrepancy
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                        >
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
