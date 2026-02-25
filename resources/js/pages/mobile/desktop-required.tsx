import { Head, Link } from '@inertiajs/react';
import { MonitorSmartphone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    title: string;
    message: string;
    role: string;
    requested_path: string;
};

export default function DesktopRequired({
    title,
    message,
    role,
    requested_path,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Access Restricted',
            href: requested_path,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="mx-auto w-full max-w-2xl">
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle className="flex items-center gap-2">
                            <MonitorSmartphone className="size-5" />
                            {title}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 pt-4">
                        <p className="text-sm text-muted-foreground">{message}</p>
                        <p className="text-xs text-muted-foreground">
                            Requested path: {requested_path}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Current role: {role}
                        </p>
                        <div className="pt-1">
                            <Button asChild>
                                <Link href={dashboard()}>Back to Dashboard</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
