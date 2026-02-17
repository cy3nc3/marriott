import { Head } from '@inertiajs/react';
import { Clock, GraduationCap, Wallet, Star } from 'lucide-react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                {/* Header Info */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-black tracking-tight italic">Welcome back, <span className="text-primary not-italic">Juan!</span></h1>
                        <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <GraduationCap className="size-4 text-primary" />
                            <span>Grade 7 - Rizal</span>
                            <span className="text-muted-foreground/30">|</span>
                            <span>Adviser: Teacher 1</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Happening Now */}
                    <Card className="lg:col-span-2 border-primary/10 shadow-md bg-gradient-to-br from-primary/[0.03] to-transparent overflow-hidden relative">
                        <div className="absolute top-0 right-0 p-8 opacity-5">
                            <Clock className="size-32" />
                        </div>
                        <CardContent className="p-8 md:p-12 relative z-10">
                            <div className="flex items-center gap-2 mb-4">
                                <div className="size-2 rounded-full bg-green-500 animate-pulse" />
                                <span className="text-xs font-black uppercase tracking-[0.2em] text-muted-foreground">Happening Now</span>
                            </div>
                            <div className="space-y-2">
                                <h2 className="text-4xl md:text-5xl font-black text-foreground tracking-tighter">
                                    Mathematics 7
                                </h2>
                                <p className="text-xl text-muted-foreground font-medium italic">
                                    with <span className="font-bold text-primary not-italic">Teacher 1</span>
                                </p>
                            </div>
                            <div className="mt-8 flex gap-4">
                                <div className="bg-background/80 backdrop-blur-sm border border-primary/10 rounded-xl p-4 flex-1 shadow-sm">
                                    <p className="text-[10px] font-black uppercase text-muted-foreground mb-1">Duration</p>
                                    <p className="font-bold text-sm">08:00 AM - 09:00 AM</p>
                                </div>
                                <div className="bg-background/80 backdrop-blur-sm border border-primary/10 rounded-xl p-4 flex-1 shadow-sm">
                                    <p className="text-[10px] font-black uppercase text-muted-foreground mb-1">Room</p>
                                    <p className="font-bold text-sm">Room 204</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        {/* Outstanding Balance */}
                        <Card className="shadow-sm border-destructive/10 overflow-hidden">
                            <CardHeader className="bg-destructive/[0.03] border-b border-destructive/10 py-3">
                                <div className="flex items-center gap-2">
                                    <Wallet className="size-4 text-destructive" />
                                    <CardTitle className="text-xs font-black uppercase tracking-widest text-destructive">Account Balance</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <p className="text-3xl font-black tracking-tight">₱ 15,000.00</p>
                                <p className="text-[10px] text-muted-foreground mt-1 font-medium italic">Next due: Aug 30, 2024</p>
                            </CardContent>
                        </Card>

                        {/* Latest Grade */}
                        <Card className="shadow-sm border-primary/10 overflow-hidden">
                            <CardHeader className="bg-primary/[0.03] border-b border-primary/10 py-3">
                                <div className="flex items-center gap-2">
                                    <Star className="size-4 text-primary" />
                                    <CardTitle className="text-xs font-black uppercase tracking-widest text-primary text-primary">Latest Score</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6 flex items-center justify-between">
                                <div>
                                    <p className="font-black text-sm">Unit Quiz 1</p>
                                    <p className="text-[10px] text-muted-foreground uppercase font-bold tracking-tighter">Mathematics 7</p>
                                </div>
                                <div className="text-2xl font-black text-primary tracking-tighter">
                                    18<span className="text-xs text-muted-foreground font-bold">/20</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
