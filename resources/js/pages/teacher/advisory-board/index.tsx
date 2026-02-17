import { Head } from '@inertiajs/react';
import { Printer, Info, Lock, ShieldCheck, Heart } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <ShieldCheck className="size-6 text-primary" />
                            <h1 className="text-2xl font-black tracking-tight">
                                Advisory: Grade 7 - Rizal
                            </h1>
                        </div>
                        <p className="text-sm font-medium text-muted-foreground">
                            Consolidated academic performance and character
                            assessment.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="gap-2 border-primary/20"
                        >
                            <Printer className="size-4 text-primary" />
                            Bulk Print SF9
                        </Button>
                        <Button
                            size="sm"
                            className="gap-2 bg-destructive/90 shadow-sm hover:bg-destructive"
                        >
                            <Lock className="size-4" />
                            Finalize & Lock Quarter
                        </Button>
                    </div>
                </div>

                <div className="flex items-center gap-4 rounded-xl border border-primary/5 bg-muted/30 p-4">
                    <div className="flex items-center gap-2">
                        <Label className="text-[10px] font-black tracking-widest text-muted-foreground uppercase">
                            Reporting Period:
                        </Label>
                        <Select defaultValue="1st">
                            <SelectTrigger className="h-8 w-28 font-bold">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1st">1st Quarter</SelectItem>
                                <SelectItem value="2nd">2nd Quarter</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="h-4 w-px bg-border" />
                    <div className="flex items-center gap-2">
                        <span className="text-[10px] font-black tracking-widest text-muted-foreground uppercase italic">
                            Sync Status:
                        </span>
                        <Badge
                            variant="outline"
                            className="border-green-200 bg-green-50 text-[10px] text-green-700"
                        >
                            All Subjects Finalized
                        </Badge>
                    </div>
                </div>

                <Tabs defaultValue="academic" className="w-full">
                    <TabsList className="mb-4 bg-muted/50 p-1">
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
                        <Card className="overflow-hidden border-primary/10 shadow-md">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="sticky left-0 z-20 min-w-64 border-r pl-6 text-[10px] font-black uppercase">
                                                Student Name
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Math
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Science
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                English
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Filipino
                                            </TableHead>
                                            <TableHead className="bg-primary/5 text-center text-[10px] font-black text-primary uppercase">
                                                Gen. Avg
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="transition-colors hover:bg-muted/30">
                                            <TableCell className="sticky left-0 z-10 border-r bg-background pl-6 font-bold">
                                                Dela Cruz, Juan
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                85
                                            </TableCell>
                                            <TableCell className="text-center font-black font-medium text-destructive">
                                                72
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                88
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                89
                                            </TableCell>
                                            <TableCell className="bg-primary/[0.02] text-center text-base font-black text-primary">
                                                83.5
                                            </TableCell>
                                        </TableRow>
                                        <TableRow className="transition-colors hover:bg-muted/30">
                                            <TableCell className="sticky left-0 z-10 border-r bg-background pl-6 font-bold">
                                                Santos, Maria
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                92
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                90
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                91
                                            </TableCell>
                                            <TableCell className="text-center font-medium">
                                                93
                                            </TableCell>
                                            <TableCell className="bg-primary/[0.02] text-center text-base font-black text-primary">
                                                91.5
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="conduct">
                        <Card className="overflow-hidden border-primary/10 shadow-md">
                            <CardHeader className="border-b bg-muted/10 py-4">
                                <div className="flex items-center gap-2">
                                    <Info className="size-4 text-blue-600" />
                                    <p className="text-xs font-medium text-blue-800">
                                        Use DepEd scale: <strong>AO</strong>{' '}
                                        (Always), <strong>SO</strong>{' '}
                                        (Sometimes), <strong>RO</strong>{' '}
                                        (Rarely), <strong>NO</strong> (Not
                                        Observed)
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader className="bg-muted/20">
                                        <TableRow>
                                            <TableHead className="pl-6 text-[10px] font-black uppercase">
                                                Student Name
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Maka-Diyos
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Makatao
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Makakalikasan
                                            </TableHead>
                                            <TableHead className="text-center text-[10px] font-bold uppercase">
                                                Makabansa
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow className="transition-colors hover:bg-muted/30">
                                            <TableCell className="pl-6 font-bold">
                                                Dela Cruz, Juan
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <BehaviorSelect defaultValue="AO" />
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <BehaviorSelect defaultValue="AO" />
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <BehaviorSelect defaultValue="SO" />
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <BehaviorSelect defaultValue="AO" />
                                            </TableCell>
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
            <SelectTrigger className="mx-auto h-8 w-16 border-primary/10 text-center text-xs font-black">
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
