import { Head } from '@inertiajs/react';
import { Printer, Info, Lock, ShieldCheck, Heart } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Advisory Board',
        href: '/teacher/advisory-board',
    },
];

export default function AdvisoryBoard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Advisory Board" />
            <div className="flex flex-col gap-4">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="flex items-center gap-4">
                        <Select defaultValue="1st">
                            <SelectTrigger className="w-[180px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1st">1st Quarter</SelectItem>
                                <SelectItem value="2nd">2nd Quarter</SelectItem>
                            </SelectContent>
                        </Select>

                        <div className="hidden h-4 w-px bg-border md:block" />

                        <div className="flex items-center gap-2">
                            <span className="hidden text-xs text-muted-foreground md:inline-block">Sync Status:</span>
                            <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">All Subjects Finalized</Badge>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" className="gap-2">
                            <Printer className="size-4" />
                            Bulk Print SF9
                        </Button>
                        <Button size="sm" variant="destructive" className="gap-2">
                            <Lock className="size-4" />
                            Finalize & Lock Quarter
                        </Button>
                    </div>
                </div>

                <Tabs defaultValue="academic" className="w-full">
                    <TabsList className="mb-4">
                        <TabsTrigger value="academic" className="gap-2">
                            <ShieldCheck className="size-4" />
                            Academic Summary
                        </TabsTrigger>
                        <TabsTrigger value="conduct" className="gap-2">
                            <Heart className="size-4" />
                            Character & Conduct
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="academic">
                        <Card>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="sticky left-0 z-20 min-w-64 border-r pl-6 font-black text-[10px] uppercase">Student Name</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Math</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Science</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">English</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Filipino</TableHead>
                                            <TableHead className="text-center font-black text-[10px] uppercase bg-primary/5 text-primary">Gen. Avg</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="sticky left-0 z-10 border-r bg-background pl-6 font-bold">Dela Cruz, Juan</TableCell>
                                            <TableCell className="text-center font-medium">85</TableCell>
                                            <TableCell className="text-center font-medium text-destructive font-black">72</TableCell>
                                            <TableCell className="text-center font-medium">88</TableCell>
                                            <TableCell className="text-center font-medium">89</TableCell>
                                            <TableCell className="text-center font-black bg-primary/[0.02] text-primary text-base">83.5</TableCell>
                                        </TableRow>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="sticky left-0 z-10 border-r bg-background pl-6 font-bold">Santos, Maria</TableCell>
                                            <TableCell className="text-center font-medium">92</TableCell>
                                            <TableCell className="text-center font-medium">90</TableCell>
                                            <TableCell className="text-center font-medium">91</TableCell>
                                            <TableCell className="text-center font-medium">93</TableCell>
                                            <TableCell className="text-center font-black bg-primary/[0.02] text-primary text-base">91.5</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card>
                            <CardHeader className="bg-muted/10 border-b py-4">
                                <div className="flex items-center gap-2">
                                    <Info className="size-4 text-blue-600" />
                                    <p className="text-xs font-medium text-blue-800">Use DepEd scale: <strong>AO</strong> (Always), <strong>SO</strong> (Sometimes), <strong>RO</strong> (Rarely), <strong>NO</strong> (Not Observed)</p>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6 font-black text-[10px] uppercase">Student Name</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Maka-Diyos</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Makatao</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Makakalikasan</TableHead>
                                            <TableHead className="text-center font-bold text-[10px] uppercase">Makabansa</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="pl-6 font-bold">Dela Cruz, Juan</TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="SO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}

function BehaviorSelect({ defaultValue }: { defaultValue: string }) {
    return (
        <Select defaultValue={defaultValue}>
            <SelectTrigger className="mx-auto h-8 w-20">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="AO">AO</SelectItem>
                <SelectItem value="SO">SO</SelectItem>
                <SelectItem value="RO">RO</SelectItem>
                <SelectItem value="NO">NO</SelectItem>
            </SelectContent>
        </Select>
    );
}
