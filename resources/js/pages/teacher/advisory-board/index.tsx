import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Printer, Info } from 'lucide-react';

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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                {/* Control Bar */}
                <Card className="bg-muted/30">
                    <CardContent className="p-4 flex flex-col md:flex-row justify-between items-end gap-4">
                        <div className="space-y-1">
                            <h2 className="text-lg font-bold">Advisory Section: <span className="text-primary">Grade 7 - Rizal</span></h2>
                            <p className="text-sm text-muted-foreground">View consolidated grades and manage student conduct.</p>
                        </div>
                        <div className="flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-2">
                                <Label htmlFor="quarter" className="text-sm font-medium">Quarter:</Label>
                                <Select defaultValue="1st">
                                    <SelectTrigger id="quarter" className="w-32">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1st">1st</SelectItem>
                                        <SelectItem value="2nd">2nd</SelectItem>
                                        <SelectItem value="3rd">3rd</SelectItem>
                                        <SelectItem value="4th">4th</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button variant="outline" className="gap-2">
                                <Printer className="size-4" />
                                Print Report Cards
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs Section */}
                <Tabs defaultValue="academic" className="space-y-6">
                    <TabsList className="bg-muted/50 p-1">
                        <TabsTrigger value="academic" className="px-6">Academic Summary</TabsTrigger>
                        <TabsTrigger value="conduct" className="px-6">Conduct / Values Grading</TabsTrigger>
                    </TabsList>

                    <TabsContent value="academic">
                        <Card className="overflow-hidden">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/50">
                                        <TableRow>
                                            <TableHead className="sticky left-0 bg-muted/50 z-20 pl-6 min-w-[200px] border-r">Student Name</TableHead>
                                            <TableHead className="text-center">English</TableHead>
                                            <TableHead className="text-center">Math</TableHead>
                                            <TableHead className="text-center">Science</TableHead>
                                            <TableHead className="text-center">Filipino</TableHead>
                                            <TableHead className="text-center font-bold pr-6">General Average</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="sticky left-0 bg-background z-10 pl-6 font-medium border-r">Dela Cruz, Juan</TableCell>
                                            <TableCell className="text-center">88</TableCell>
                                            <TableCell className="text-center">85</TableCell>
                                            <TableCell className="text-center text-destructive font-bold">72</TableCell>
                                            <TableCell className="text-center">89</TableCell>
                                            <TableCell className="text-center font-black text-primary pr-6">83.50</TableCell>
                                        </TableRow>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="sticky left-0 bg-background z-10 pl-6 font-medium border-r">Santos, Maria</TableCell>
                                            <TableCell className="text-center text-green-600 font-bold">92</TableCell>
                                            <TableCell className="text-center text-green-600 font-bold">90</TableCell>
                                            <TableCell className="text-center text-green-600 font-bold">91</TableCell>
                                            <TableCell className="text-center text-green-600 font-bold">93</TableCell>
                                            <TableCell className="text-center font-black text-primary pr-6">91.50</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card className="overflow-hidden">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/50 text-[10px] uppercase tracking-wider">
                                        <TableRow>
                                            <TableHead className="pl-6 min-w-[200px]">Student Name</TableHead>
                                            <TableHead className="text-center">Maka-Diyos</TableHead>
                                            <TableHead className="text-center">Makatao</TableHead>
                                            <TableHead className="text-center">Makakalikasan</TableHead>
                                            <TableHead className="text-center pr-6">Makabansa</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="pl-6 font-medium">Dela Cruz, Juan</TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="SO" /></TableCell>
                                            <TableCell className="text-center pr-6"><BehaviorSelect defaultValue="AO" /></TableCell>
                                        </TableRow>
                                        <TableRow className="hover:bg-muted/30 transition-colors">
                                            <TableCell className="pl-6 font-medium">Santos, Maria</TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center"><BehaviorSelect defaultValue="AO" /></TableCell>
                                            <TableCell className="text-center pr-6"><BehaviorSelect defaultValue="AO" /></TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                            <div className="p-4 bg-muted/30 border-t flex flex-col sm:flex-row justify-between items-center px-6 gap-4">
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Info className="size-3" />
                                    <span>Legend: <strong>AO</strong> (Always Observed), <strong>SO</strong> (Sometimes Observed), <strong>RO</strong> (Rarely Observed), <strong>NO</strong> (Not Observed)</span>
                                </div>
                                <p className="text-[10px] italic text-muted-foreground/60">Changes are saved automatically</p>
                            </div>
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
            <SelectTrigger className="w-16 h-8 text-center mx-auto text-xs font-bold">
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
