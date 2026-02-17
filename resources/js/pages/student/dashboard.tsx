import { Head } from '@inertiajs/react';
import { Clock, GraduationCap, Wallet, Star } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-black tracking-tight italic">
                            Welcome back,{' '}
                            <span className="text-primary not-italic">
                                Juan!
                            </span>
                        </h1>
                        <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <GraduationCap className="size-4 text-primary" />
                            <span>Grade 7 - Rizal</span>
                            <span className="text-muted-foreground/30">|</span>
                            <span>Adviser: Teacher 1</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Happening Now */}
                    <Card className="relative overflow-hidden border-primary/10 bg-gradient-to-br from-primary/[0.03] to-transparent shadow-md lg:col-span-2">
                        <div className="absolute top-0 right-0 p-8 opacity-5">
                            <Clock className="size-32" />
                        </div>
                        <CardContent className="relative z-10 p-8 md:p-12">
                            <div className="mb-4 flex items-center gap-2">
                                <div className="size-2 animate-pulse rounded-full bg-green-500" />
                                <span className="text-xs font-black tracking-[0.2em] text-muted-foreground uppercase">
                                    Happening Now
                                </span>
                            </div>
                            <div className="space-y-2">
                                <h2 className="text-4xl font-black tracking-tighter text-foreground md:text-5xl">
                                    Mathematics 7
                                </h2>
                                <p className="text-xl font-medium text-muted-foreground italic">
                                    with{' '}
                                    <span className="font-bold text-primary not-italic">
                                        Teacher 1
                                    </span>
                                </p>
                            </div>
                            <div className="mt-8 flex gap-4">
                                <div className="flex-1 rounded-xl border border-primary/10 bg-background/80 p-4 shadow-sm backdrop-blur-sm">
                                    <p className="mb-1 text-[10px] font-black text-muted-foreground uppercase">
                                        Duration
                                    </p>
                                    <p className="text-sm font-bold">
                                        08:00 AM - 09:00 AM
                                    </p>
                                </div>
                                <div className="flex-1 rounded-xl border border-primary/10 bg-background/80 p-4 shadow-sm backdrop-blur-sm">
                                    <p className="mb-1 text-[10px] font-black text-muted-foreground uppercase">
                                        Room
                                    </p>
                                    <p className="text-sm font-bold">
                                        Room 204
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        {/* Outstanding Balance */}
                        <Card className="overflow-hidden border-destructive/10 shadow-sm">
                            <CardHeader className="border-b border-destructive/10 bg-destructive/[0.03] py-3">
                                <div className="flex items-center gap-2">
                                    <Wallet className="size-4 text-destructive" />
                                    <CardTitle className="text-xs font-black tracking-widest text-destructive uppercase">
                                        Account Balance
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <p className="text-3xl font-black tracking-tight">
                                    ₱ 15,000.00
                                </p>
                                <p className="mt-1 text-[10px] font-medium text-muted-foreground italic">
                                    Next due: Aug 30, 2024
                                </p>
                            </CardContent>
                        </Card>

                        {/* Latest Grade */}
                        <Card className="overflow-hidden border-primary/10 shadow-sm">
                            <CardHeader className="border-b border-primary/10 bg-primary/[0.03] py-3">
                                <div className="flex items-center gap-2">
                                    <Star className="size-4 text-primary" />
                                    <CardTitle className="text-xs font-black tracking-widest text-primary uppercase">
                                        Latest Score
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="flex items-center justify-between p-6">
                                <div>
                                    <p className="text-sm font-black">
                                        Unit Quiz 1
                                    </p>
                                    <p className="text-[10px] font-bold tracking-tighter text-muted-foreground uppercase">
                                        Mathematics 7
                                    </p>
                                </div>
                                <div className="text-2xl font-black tracking-tighter text-primary">
                                    18
                                    <span className="text-xs font-bold text-muted-foreground">
                                        /20
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
