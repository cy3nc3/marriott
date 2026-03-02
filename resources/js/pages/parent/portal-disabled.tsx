import { Head, Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { logout } from '@/routes';

type Props = {
    title: string;
    message: string;
    requested_path: string;
};

export default function ParentPortalDisabled({
    title,
    message,
    requested_path,
}: Props) {
    return (
        <div className="min-h-screen bg-background">
            <Head title={title} />

            <div className="mx-auto flex min-h-screen w-full max-w-2xl items-center px-4 py-10">
                <Card className="w-full gap-2">
                    <CardHeader className="border-b">
                        <CardTitle className="flex items-center gap-2">
                            <AlertTriangle className="size-5" />
                            {title}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 pt-6">
                        <p className="text-sm text-muted-foreground">
                            {message}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Requested path: {requested_path}
                        </p>
                        <div className="pt-1">
                            <Button asChild>
                                <Link href={logout()} as="button">
                                    Back to Login
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
