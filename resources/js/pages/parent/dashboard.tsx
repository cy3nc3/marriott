import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ShieldCheck, Wallet, GraduationCap, ArrowRight, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Parent Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Parent Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-black tracking-tight">Parent Portal</h1>
                        <p className="text-sm font-medium text-muted-foreground italic">Monitoring progress for <span className="text-primary not-italic font-bold">Juan Dela Cruz</span></p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    {/* Enrollment Status */}
                    <Card className="border-primary/10 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 p-4 opacity-5">
                            <ShieldCheck className="size-16" />
                        </div>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Enrollment Status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-2">
                                <Badge className="bg-green-50 text-green-700 border-green-200 text-xs font-black uppercase tracking-widest">Enrolled</Badge>
                                <span className="text-xs text-muted-foreground font-medium italic">SY 2025-2026</span>
                            </div>
                            <p className="text-sm font-medium leading-relaxed">Your child is officially registered in <span className="font-bold">Grade 7 - Rizal</span>.</p>
                        </CardContent>
                    </Card>

                    {/* Financial Summary */}
                    <Card className="border-destructive/10 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 p-4 opacity-5 text-destructive">
                            <Wallet className="size-16" />
                        </div>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Outstanding Balance</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-3xl font-black tracking-tighter text-destructive">₱ 15,000.00</p>
                            <Link href="/parent/billing-information" className="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline group">
                                View Billing Details
                                <ArrowRight className="size-3 group-hover:translate-x-0.5 transition-transform" />
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Academic Performance */}
                    <Card className="border-primary/10 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 p-4 opacity-5 text-primary">
                            <GraduationCap className="size-16" />
                        </div>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Academic Standing</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-baseline gap-1">
                                <span className="text-3xl font-black tracking-tighter text-primary">83.5</span>
                                <span className="text-[10px] font-bold text-muted-foreground uppercase">Gen. Avg</span>
                            </div>
                            <Link href="/parent/grades" className="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline group">
                                View Progress Report
                                <ArrowRight className="size-3 group-hover:translate-x-0.5 transition-transform" />
                            </Link>
                        </CardContent>
                    </Card>

                </div>

                <Card className="border-primary/5 bg-muted/20">
                    <CardContent className="p-6 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-white rounded-full shadow-sm border border-primary/10">
                                <User className="size-6 text-primary" />
                            </div>
                            <div className="space-y-0.5">
                                <p className="text-sm font-black text-primary uppercase tracking-tighter leading-none">Class Adviser</p>
                                <p className="text-lg font-bold">Mr. Arthur Santos</p>
                            </div>
                        </div>
                        <div className="flex gap-3">
                            <Link href="/parent/schedule" className="px-6 py-2 bg-white border border-primary/20 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-primary/5 transition-colors">
                                View Class Schedule
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
