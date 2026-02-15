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
    FieldSeparator,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    Search, 
    Printer, 
    FilePlus2, 
    GraduationCap, 
    Building2,
    Calendar,
    Plus
} from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';

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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                {/* Search Section */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Search Student Registry</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                                <Input placeholder="Enter LRN or Name to view SF10..." className="pl-10" />
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
                    <CardContent className="flex flex-col md:flex-row items-center justify-between p-6 gap-6">
                        <div className="flex flex-row items-center gap-6">
                            <Avatar size="2xl" className="border-4 border-background shadow-sm">
                                <AvatarImage src="" />
                                <AvatarFallback>JD</AvatarFallback>
                            </Avatar>
                            <div className="space-y-1">
                                <h2 className="text-2xl font-black tracking-tight text-primary">Juan Dela Cruz</h2>
                                <div className="flex gap-4 text-sm font-medium text-muted-foreground">
                                    <p>Grade 7 - Rizal</p>
                                    <span>•</span>
                                    <p>LRN: 1234567890123</p>
                                </div>
                            </div>
                        </div>
                        <Button variant="outline" size="lg" className="gap-2 font-bold uppercase tracking-wider text-xs">
                            <Printer className="size-4" />
                            Print SF10 (Form 137)
                        </Button>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-5 items-start gap-6">
                    {/* Academic History List (SF10 Content) */}
                    <div className="lg:col-span-3 space-y-6">
                        <div className="flex items-center gap-2 mb-2">
                            <GraduationCap className="size-5 text-primary" />
                            <h3 className="font-bold text-lg">Academic History</h3>
                        </div>

                        {/* Sample Grade 7 Card */}
                        <Card>
                            <CardHeader className="bg-muted/30 border-b flex flex-row justify-between items-center py-4 px-6">
                                <div className="space-y-0.5">
                                    <CardTitle className="text-base font-bold">Grade 7</CardTitle>
                                    <p className="text-xs text-muted-foreground font-medium">SY 2023-2024 • Marriott School System</p>
                                </div>
                                <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Promoted</Badge>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/10">
                                        <TableRow>
                                            <TableHead className="pl-6">Subject</TableHead>
                                            <TableHead className="text-right pr-6">Final Rating</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell className="pl-6 font-medium">Mathematics</TableCell>
                                            <TableCell className="text-right pr-6 font-bold">90</TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableCell className="pl-6 font-medium">Science</TableCell>
                                            <TableCell className="text-right pr-6 font-bold">92</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Add Historical Record Sidebar */}
                    <Card className="lg:col-span-2 sticky top-4 border-primary/10 shadow-sm">
                        <CardHeader className="bg-primary/5 border-b">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <FilePlus2 className="size-5 text-primary" />
                                Add Historical Record
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-6">
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                        <Building2 className="size-3 text-muted-foreground" />
                                        School Name
                                    </Label>
                                    <Input placeholder="Name of previous school..." />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                            <Calendar className="size-3 text-muted-foreground" />
                                            School Year
                                        </Label>
                                        <div className="flex items-center gap-2">
                                            <Input placeholder="2023" className="text-center" />
                                            <span className="text-muted-foreground">-</span>
                                            <Input placeholder="2024" className="text-center" />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                            <GraduationCap className="size-3 text-muted-foreground" />
                                            Grade Level
                                        </Label>
                                        <Input placeholder="e.g. 6" className="text-center" />
                                    </div>
                                </div>
                            </div>

                            <FieldSeparator />

                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <Label className="text-xs font-bold uppercase tracking-wider">Subjects & Final Grades</Label>
                                    <Button variant="ghost" size="xs" className="h-7 text-primary hover:bg-primary/5 gap-1 font-bold">
                                        <Plus className="size-3" />
                                        Add Subject
                                    </Button>
                                </div>
                                <div className="space-y-3">
                                    <div className="grid grid-cols-4 gap-2">
                                        <Input placeholder="Subject" className="col-span-3" />
                                        <Input placeholder="Grade" className="text-center font-bold" />
                                    </div>
                                    <div className="grid grid-cols-4 gap-2">
                                        <Input placeholder="Subject" className="col-span-3" />
                                        <Input placeholder="Grade" className="text-center font-bold" />
                                    </div>
                                </div>
                            </div>

                            <Button className="w-full h-11 font-bold tracking-wide mt-4">
                                Save to SF10 History
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
