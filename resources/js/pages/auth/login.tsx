import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LoginForm } from '@/components/login-form';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { home } from '@/routes';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            <div className="grid min-h-svh lg:grid-cols-2">
                <div className="bg-muted relative hidden lg:block">
                    <div className="absolute inset-0 bg-muted/30" />
                    <PlaceholderPattern className="absolute inset-0 h-full w-full stroke-muted-foreground/25" />
                    <div className="relative z-10 flex h-full items-center justify-center p-10">
                        <div className="space-y-4 text-center">
                            <AppLogoIcon className="mx-auto size-16 fill-current text-[var(--foreground)] dark:text-white" />
                            <p className="text-sm text-muted-foreground">
                                School operations platform for registrar,
                                finance, teachers, students, and parents.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-4 p-6 md:p-10">
                    <div className="flex justify-center gap-2 md:justify-start">
                        <Link
                            href={home()}
                            className="flex items-center gap-2 font-medium"
                        >
                            <div className="bg-primary text-primary-foreground flex size-6 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-4 fill-current" />
                            </div>
                            Marriott School Suite
                        </Link>
                    </div>
                    <div className="flex flex-1 items-center justify-center">
                        <div className="w-full max-w-xs">
                            <LoginForm
                                status={status}
                                canResetPassword={canResetPassword}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
