import { router, useForm } from '@inertiajs/react';
import { Mail, UserRound, X } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { Spinner } from '@/components/ui/spinner';
import {
    clearStoredLoginDeviceToken,
    getSavedAccountDeviceId,
    isDeviceLoginTokenExpired,
    readStoredLoginAccounts,
    type StoredLoginAccount,
    upsertStoredLoginAccount,
    writeStoredLoginAccounts,
} from '@/lib/saved-login-accounts';
import { store } from '@/routes/login';
import { store as storeSavedAccountLogin } from '@/routes/login/saved-account';
import { request } from '@/routes/password';

interface LoginFormProps {
    status?: string;
    canResetPassword: boolean;
}

export function LoginForm({ status, canResetPassword }: LoginFormProps) {
    const deviceId = useMemo(() => getSavedAccountDeviceId(), []);
    const [storedAccounts, setStoredAccounts] = useState<StoredLoginAccount[]>(
        [],
    );
    const [isUsingAnotherAccount, setIsUsingAnotherAccount] = useState(false);
    const [selectedAccountEmail, setSelectedAccountEmail] = useState<
        string | null
    >(null);
    const [isRememberAccountLoading, setIsRememberAccountLoading] =
        useState(false);
    const [didRememberAutoLoginFail, setDidRememberAutoLoginFail] =
        useState(false);

    const { data, setData, submit, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
        saved_account_device_id: deviceId,
    });

    useEffect(() => {
        setData('saved_account_device_id', deviceId);
    }, [deviceId, setData]);

    useEffect(() => {
        const accounts = readStoredLoginAccounts();
        setStoredAccounts(accounts);

        if (accounts.length === 0) {
            setIsUsingAnotherAccount(true);
            return;
        }

        setSelectedAccountEmail(null);
        setIsUsingAnotherAccount(false);
        setIsRememberAccountLoading(false);
        setDidRememberAutoLoginFail(false);
        setData('email', '');
        setData('password', '');
        setData('remember', false);
    }, [setData]);

    const selectedAccount = useMemo(() => {
        return storedAccounts.find(
            (account) => account.email === selectedAccountEmail,
        );
    }, [selectedAccountEmail, storedAccounts]);

    const showingSavedAccounts =
        storedAccounts.length > 0 && !isUsingAnotherAccount;
    const hasSelectedSavedAccount = Boolean(selectedAccount);
    const showSavedAccountsList =
        showingSavedAccounts && !hasSelectedSavedAccount;
    const shouldShowSavedAccountPasswordField =
        showingSavedAccounts &&
        hasSelectedSavedAccount &&
        !isRememberAccountLoading &&
        (!selectedAccount?.remember || didRememberAutoLoginFail);

    const updateStoredAccounts = (nextAccounts: StoredLoginAccount[]): void => {
        setStoredAccounts(nextAccounts);
        writeStoredLoginAccounts(nextAccounts);
    };

    const persistAccount = (email: string, remember: boolean): void => {
        const nextAccounts = upsertStoredLoginAccount(storedAccounts, {
            email,
            remember,
            deviceLogin: remember
                ? undefined
                : null,
            lastUsedAt: new Date().toISOString(),
        });

        updateStoredAccounts(nextAccounts);
    };

    const markStoredAccountAsNotRemembered = (
        email: string,
        accountDeviceId?: string,
    ): void => {
        const nextAccounts = clearStoredLoginDeviceToken(
            storedAccounts,
            email,
            accountDeviceId,
        );

        updateStoredAccounts(nextAccounts);
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        submit(store(), {
            onSuccess: () => {
                persistAccount(data.email, data.remember);
            },
            onFinish: () => {
                reset('password');
            },
        });
    };

    const handleRemoveStoredAccount = (email: string): void => {
        if (processing || isRememberAccountLoading) {
            return;
        }

        const nextAccounts = storedAccounts.filter(
            (account) => account.email !== email,
        );

        if (nextAccounts.length === storedAccounts.length) {
            return;
        }

        updateStoredAccounts(nextAccounts);

        if (selectedAccountEmail === email) {
            setSelectedAccountEmail(null);
            setIsRememberAccountLoading(false);
            setDidRememberAutoLoginFail(false);
            setData('email', '');
            setData('password', '');
            setData('remember', false);
        }

        if (nextAccounts.length === 0) {
            setIsUsingAnotherAccount(true);
        }
    };

    const handleChooseStoredAccount = (account: StoredLoginAccount): void => {
        if (processing) {
            return;
        }

        setSelectedAccountEmail(account.email);
        setDidRememberAutoLoginFail(false);
        setData('email', account.email);
        setData('password', '');
        setData('remember', account.remember);

        if (!account.remember) {
            return;
        }

        if (
            !account.deviceLogin ||
            isDeviceLoginTokenExpired(account.deviceLogin)
        ) {
            setDidRememberAutoLoginFail(true);
            setData('remember', false);
            markStoredAccountAsNotRemembered(
                account.email,
                account.deviceLogin?.device_id,
            );

            return;
        }

        setIsRememberAccountLoading(true);

        router.post(
            storeSavedAccountLogin().url,
            {
                email: account.email,
                device_id: account.deviceLogin.device_id,
                selector: account.deviceLogin.selector,
                token: account.deviceLogin.token,
            },
            {
                preserveState: false,
                preserveScroll: true,
                onError: () => {
                    setDidRememberAutoLoginFail(true);
                    setData('remember', false);
                    markStoredAccountAsNotRemembered(
                        account.email,
                        account.deviceLogin?.device_id,
                    );
                },
                onFinish: () => {
                    setIsRememberAccountLoading(false);
                },
            },
        );
    };

    const handleUseAnotherAccount = (): void => {
        setIsUsingAnotherAccount(true);
        setSelectedAccountEmail(null);
        setIsRememberAccountLoading(false);
        setDidRememberAutoLoginFail(false);
        setData('email', '');
        setData('password', '');
        setData('remember', false);
    };

    const handleBackToSavedAccounts = (): void => {
        if (storedAccounts.length === 0) {
            return;
        }

        setIsUsingAnotherAccount(false);
        setSelectedAccountEmail(null);
        setIsRememberAccountLoading(false);
        setDidRememberAutoLoginFail(false);
        setData('email', '');
        setData('password', '');
        setData('remember', false);
    };

    const handleBackToAccountList = (): void => {
        setSelectedAccountEmail(null);
        setIsRememberAccountLoading(false);
        setDidRememberAutoLoginFail(false);
        setData('email', '');
        setData('password', '');
        setData('remember', false);
    };

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-5">
            {status && (
                <div className="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            {showSavedAccountsList && (
                <div className="space-y-3">
                    <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
                        Saved Accounts
                    </p>
                    <div className="max-h-64 space-y-2 overflow-y-auto pr-1">
                        {storedAccounts.map((account) => (
                            <div key={account.email} className="group relative">
                                <button
                                    type="button"
                                    onClick={() =>
                                        handleChooseStoredAccount(account)
                                    }
                                    className="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 pr-12 text-left transition hover:border-slate-300"
                                >
                                    <div className="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                        <UserRound className="size-4" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-slate-800">
                                            {account.email}
                                        </p>
                                    </div>
                                </button>
                                <button
                                    type="button"
                                    onClick={() =>
                                        handleRemoveStoredAccount(account.email)
                                    }
                                    aria-label={`Remove ${account.email}`}
                                    className="absolute top-1/2 right-3 -translate-y-1/2 rounded-full p-1 text-slate-400 opacity-0 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:opacity-100 group-hover:opacity-100"
                                    disabled={
                                        processing || isRememberAccountLoading
                                    }
                                >
                                    <X className="size-3.5" />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {showingSavedAccounts && hasSelectedSavedAccount && (
                <div className="space-y-4">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
                            {isRememberAccountLoading
                                ? 'Logging in'
                                : 'Log in as'}
                        </p>
                        <div className="mt-2 flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div className="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                <UserRound className="size-4" />
                            </div>
                            <p className="min-w-0 truncate text-sm font-medium text-slate-800">
                                {selectedAccount?.email}
                            </p>
                        </div>
                    </div>

                    {isRememberAccountLoading ? (
                        <div className="flex items-center justify-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">
                            <Spinner />
                            Logging in as {selectedAccount?.email}
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <Label htmlFor="password" className="font-medium">
                                Password
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                value={data.password}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                className="h-12 rounded-xl border-slate-200 bg-white text-base"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Enter your password"
                            />
                            <InputError message={errors.password} />
                        </div>
                    )}

                    <Button
                        type="button"
                        variant="ghost"
                        className="h-11 w-full rounded-xl text-sm text-slate-700"
                        onClick={handleBackToAccountList}
                        disabled={processing || isRememberAccountLoading}
                    >
                        Choose a different account
                    </Button>
                </div>
            )}

            {(!showingSavedAccounts || isUsingAnotherAccount) && (
                <>
                    <div className="space-y-2">
                        <Label htmlFor="email" className="font-medium">
                            Email Address
                        </Label>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                className="h-12 rounded-xl border-slate-200 bg-white pl-10 text-base"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="email@example.com"
                            />
                        </div>
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center">
                            <Label htmlFor="password" className="font-medium">
                                Password
                            </Label>
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
                        <PasswordInput
                            id="password"
                            name="password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            className="h-12 rounded-xl border-slate-200 bg-white text-base"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>
                </>
            )}

            <div className="flex items-center justify-between">
                <label
                    htmlFor="remember"
                    className="flex cursor-pointer items-center gap-2"
                >
                    <Checkbox
                        id="remember"
                        checked={data.remember}
                        onCheckedChange={(checked) =>
                            setData('remember', checked === true)
                        }
                    />
                    <span className="text-sm text-slate-700">Remember me</span>
                </label>

                {canResetPassword && shouldShowSavedAccountPasswordField && (
                    <TextLink href={request()} className="text-sm" tabIndex={4}>
                        Forgot password?
                    </TextLink>
                )}
            </div>

            <Button
                type="submit"
                className="h-12 w-full rounded-xl text-base"
                tabIndex={3}
                disabled={processing || isRememberAccountLoading}
                data-test="login-button"
            >
                {processing && <Spinner />}
                {isRememberAccountLoading ? 'Logging in...' : 'Continue'}
            </Button>

            {showSavedAccountsList ? (
                <Button
                    type="button"
                    variant="ghost"
                    className="h-11 w-full rounded-xl text-sm text-slate-700"
                    onClick={handleUseAnotherAccount}
                >
                    Login with another account
                </Button>
            ) : (
                storedAccounts.length > 0 &&
                !hasSelectedSavedAccount && (
                    <Button
                        type="button"
                        variant="ghost"
                        className="h-11 w-full rounded-xl text-sm text-slate-700"
                        onClick={handleBackToSavedAccounts}
                    >
                        Back to saved accounts
                    </Button>
                )
            )}
        </form>
    );
}
