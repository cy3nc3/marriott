import { Head } from '@inertiajs/react';
import {
    Search,
    Printer,
    FilePlus2,
    GraduationCap,
    Building2,
    Calendar,
    Plus,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FieldSeparator } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        title: 'Permanent Records',
        href: '/registrar/permanent-records',
    },
];

export default function PermanentRecords() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permanent Records" />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex flex-col">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Permanent{' '}
                            <span className="text-primary not-italic">
                                Records
                            </span>
                        </h1>
                    </div>
                </div>

                {/* Search Section */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">
                            Search Student Registry
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    placeholder="Enter LRN or Name to view SF10..."
                                    className="pl-10"
                                />
                            </div>
                            <Button className="gap-2">
                                <Search className="size-4" />
                                Find Record
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Selected Student Profile */}
                <Card className="border-primary/20">
                    <CardContent className="flex flex-col items-center justify-between gap-6 p-6 md:flex-row">
                        <div className="flex flex-row items-center gap-6">
                            <Avatar
                                size="2xl"
                                className="border-4 border-background shadow-sm"
                            >
                                <AvatarImage src="" />
                                <AvatarFallback>JD</AvatarFallback>
                            </Avatar>
                            <div className="space-y-1">
                                <h2 className="text-2xl font-black tracking-tight text-primary">
                                    Juan Dela Cruz
                                </h2>
                                <div className="flex gap-4 text-sm font-medium text-muted-foreground">
                                    <p>Grade 7 - Rizal</p>
                                    <span>•</span>
                                    <p>LRN: 1234567890123</p>
                                </div>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="lg"
                            className="gap-2 text-xs font-bold tracking-wider uppercase"
                        >
                            <Printer className="size-4" />
                            Print SF10 (Form 137)
                        </Button>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
                    {/* Academic History List (SF10 Content) */}
                    <div className="space-y-6 lg:col-span-3">
                        <div className="mb-2 flex items-center gap-2">
                            <GraduationCap className="size-5 text-primary" />
                            <h3 className="text-lg font-bold">
                                Academic History
                            </h3>
                        </div>

                        {/* Sample Grade 7 Card */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between border-b bg-muted/30 px-6 py-4">
                                <div className="space-y-0.5">
                                    <CardTitle className="text-base font-bold">
                                        Grade 7
                                    </CardTitle>
                                    <p className="text-xs font-medium text-muted-foreground">
                                        SY 2023-2024 • Marriott School System
                                    </p>
                                </div>
                                <Badge
                                    variant="outline"
                                    className="border-green-200 bg-green-50 text-green-700"
                                >
                                    Promoted
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/10">
                                        <TableRow>
                                            <TableHead className="pl-6">
                                                Subject
                                            </TableHead>
                                            <TableHead className="pr-6 text-right">
                                                Final Rating
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell className="pl-6 font-medium">
                                                Mathematics
                                            </TableCell>
                                            <TableCell className="pr-6 text-right font-bold">
                                                90
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableCell className="pl-6 font-medium">
                                                Science
                                            </TableCell>
                                            <TableCell className="pr-6 text-right font-bold">
                                                92
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Add Historical Record Sidebar */}
                    <Card className="sticky top-4 border-primary/10 shadow-sm lg:col-span-2">
                        <CardHeader className="border-b bg-primary/5">
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <FilePlus2 className="size-5 text-primary" />
                                Add Historical Record
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6 pt-6">
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2 text-xs font-bold tracking-wider uppercase">
                                        <Building2 className="size-3 text-muted-foreground" />
                                        School Name
                                    </Label>
                                    <Input placeholder="Name of previous school..." />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="flex items-center gap-2 text-xs font-bold tracking-wider uppercase">
                                            <Calendar className="size-3 text-muted-foreground" />
                                            School Year
                                        </Label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                placeholder="2023"
                                                className="text-center"
                                            />
                                            <span className="text-muted-foreground">
                                                -
                                            </span>
                                            <Input
                                                placeholder="2024"
                                                className="text-center"
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="flex items-center gap-2 text-xs font-bold tracking-wider uppercase">
                                            <GraduationCap className="size-3 text-muted-foreground" />
                                            Grade Level
                                        </Label>
                                        <Input
                                            placeholder="e.g. 6"
                                            className="text-center"
                                        />
                                    </div>
                                </div>
                            </div>

                            <FieldSeparator />

                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-bold tracking-wider uppercase">
                                        Subjects & Final Grades
                                    </Label>
                                    <Button
                                        variant="ghost"
                                        size="xs"
                                        className="h-7 gap-1 font-bold text-primary hover:bg-primary/5"
                                    >
                                        <Plus className="size-3" />
                                        Add Subject
                                    </Button>
                                </div>
                                <div className="space-y-3">
                                    <div className="grid grid-cols-4 gap-2">
                                        <Input
                                            placeholder="Subject"
                                            className="col-span-3"
                                        />
                                        <Input
                                            placeholder="Grade"
                                            className="text-center font-bold"
                                        />
                                    </div>
                                    <div className="grid grid-cols-4 gap-2">
                                        <Input
                                            placeholder="Subject"
                                            className="col-span-3"
                                        />
                                        <Input
                                            placeholder="Grade"
                                            className="text-center font-bold"
                                        />
                                    </div>
                                </div>
                            </div>

                            <Button className="mt-4 h-11 w-full font-bold tracking-wide">
                                Save to SF10 History
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
