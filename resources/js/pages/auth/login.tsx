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

            <div className="grid min-h-svh bg-[#f3f5f9] lg:h-svh lg:grid-cols-2">
                <div className="hidden overflow-hidden lg:block">
                    <img
                        src="/images/auth/IMG_8884.jpeg"
                        alt="Marriott School building"
                        className="h-full w-full object-cover object-[center_85%]"
                    />
                </div>

                <div className="flex items-center justify-center p-6 md:p-10">
                    <div className="w-full max-w-md rounded-[2rem] border border-slate-200/80 bg-white px-8 py-10 shadow-2xl shadow-slate-900/8">
                        <div className="mb-10 flex items-center justify-center gap-3">
                            <img
                                src="/images/branding/marriott-school-seal.svg"
                                alt="MarriottConnect logo"
                                className="size-10 rounded-full object-contain ring-1 ring-slate-200"
                            />
                            <span className="text-2xl font-semibold tracking-tight text-slate-900">
                                MarriottConnect
                            </span>
                        </div>

                        <div className="mb-8 text-center">
                            <h1 className="text-4xl font-semibold tracking-tight text-slate-950">
                                Welcome Back
                            </h1>
                            <p className="mt-2 text-sm text-slate-500">
                                Please enter your details to continue
                            </p>
                        </div>

                        <div className="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200/70">
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
