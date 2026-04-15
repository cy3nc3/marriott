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

            <div className="grid min-h-svh lg:h-svh lg:grid-cols-2">
                <div className="hidden overflow-hidden lg:block">
                    <img
                        src="/images/auth/IMG_8884.jpeg"
                        alt="Marriott School building"
                        className="h-full w-full object-cover object-[center_85%]"
                    />
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
