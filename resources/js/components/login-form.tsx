import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

interface LoginFormProps {
    status?: string;
    canResetPassword: boolean;
}

export function LoginForm({ status, canResetPassword }: LoginFormProps) {
    return (
        <Form
            action={store().url}
            method={store().method}
            resetOnSuccess={['password']}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="flex flex-col items-center gap-2 text-center">
                        <h1 className="text-2xl font-bold">Log in</h1>
                        <p className="text-balance text-sm text-muted-foreground">
                            Enter your email below to log in to your account
                        </p>
                    </div>

                    {status && (
                        <div className="text-center text-sm font-medium text-green-600">
                            {status}
                        </div>
                    )}

                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="email@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Password</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-sm"
                                        tabIndex={4}
                                    >
                                        Forgot password?
                                    </TextLink>
                                )}
                            </div>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            tabIndex={3}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Log in
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
