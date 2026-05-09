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

            <div className="grid min-h-svh bg-[#f3f5f9] dark:bg-slate-950 lg:h-svh lg:grid-cols-2">
                <div className="hidden overflow-hidden lg:block">
                    <img
                        src="/images/auth/IMG_8884.jpeg"
                        alt="Marriott School building"
                        className="h-full w-full object-cover object-[center_85%]"
                    />
                </div>

                <div className="flex h-svh items-center justify-center overflow-hidden p-4 md:p-6">
                    <div className="w-full max-w-md rounded-[1.5rem] border border-slate-200/80 bg-white px-6 py-6 shadow-2xl shadow-slate-900/8 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                        <div className="mb-5 flex flex-col items-center justify-center gap-2">
                            <img
                                src="/images/branding/marriott-school-seal.svg"
                                alt="MarriottConnect logo"
                                className="size-16 object-contain"
                            />
                            <span className="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                                MarriottConnect
                            </span>
                        </div>

                        <div className="mb-5 text-center">
                            <h1 className="text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">
                                Welcome Back
                            </h1>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Please enter your details to continue
                            </p>
                        </div>

                        <div className="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70 dark:bg-slate-950/60 dark:ring-slate-800">
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
