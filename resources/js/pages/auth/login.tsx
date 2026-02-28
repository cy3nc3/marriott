import { Head } from '@inertiajs/react';
import { LoginForm } from '@/components/login-form';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            <div className="grid min-h-svh lg:grid-cols-2">
                <div className="hidden overflow-hidden bg-muted lg:flex">
                    <div className="flex h-full w-full flex-col px-12 pt-10 pb-8">
                        <div className="flex items-start justify-between gap-6">
                            <div className="flex size-14 items-center justify-center rounded-full bg-primary text-3xl font-black text-primary-foreground">
                                M
                            </div>
                            <p className="max-w-sm text-right text-5xl leading-[0.95] font-black text-foreground">
                                Student
                                <span className="block text-primary">
                                    Information
                                </span>
                                <span className="block">System.</span>
                            </p>
                        </div>

                        <div className="mt-8 flex flex-1 items-end justify-center">
                            <img
                                src="/images/auth/login-online-learning.svg"
                                alt="Students and parents using the school portal"
                                className="w-full max-w-2xl object-contain"
                            />
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-center p-6 md:p-10">
                    <div className="w-full max-w-sm">
                        <LoginForm
                            status={status}
                            canResetPassword={canResetPassword}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
