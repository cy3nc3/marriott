import { router } from '@inertiajs/react';
import { RecaptchaVerifier, signInWithPhoneNumber, type ConfirmationResult } from 'firebase/auth';
import { useEffect, useMemo, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { firebaseAuth } from '@/lib/firebase';

type Props = {
    token: string;
    account_email: string;
    phone_number_redacted: string;
    phone_verified: boolean;
    is_expired: boolean;
};

export default function ClaimAccount({
    token,
    account_email,
    phone_number_redacted,
    phone_verified,
    is_expired,
}: Props) {
    const normalizeMobileSubscriberDigits = (value: string): string => {
        const digits = value.replace(/\D/g, '');

        if (digits.startsWith('9')) {
            return digits.slice(0, 10);
        }

        if (digits.startsWith('09')) {
            return digits.slice(1, 11);
        }

        if (digits.startsWith('63')) {
            return digits.slice(2, 12);
        }

        return digits.slice(0, 10);
    };

    const [phoneNumberInput, setPhoneNumberInput] = useState('');
    const [otpCode, setOtpCode] = useState('');
    const [otpSent, setOtpSent] = useState(false);
    const [sendingOtp, setSendingOtp] = useState(false);
    const [verifyingOtp, setVerifyingOtp] = useState(false);
    const [otpError, setOtpError] = useState<string | null>(null);
    const [statusMessage, setStatusMessage] = useState<string | null>(null);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [passwordError, setPasswordError] = useState<string | null>(null);
    const [savingPassword, setSavingPassword] = useState(false);

    const recaptchaVerifierRef = useRef<RecaptchaVerifier | null>(null);
    const confirmationResultRef = useRef<ConfirmationResult | null>(null);

    const canSetPassword = phone_verified && !is_expired;

    const normalizedSubscriberPhoneNumber = useMemo(() => {
        const digits = normalizeMobileSubscriberDigits(phoneNumberInput || '');

        return digits.length === 10 && digits.startsWith('9') ? digits : '';
    }, [phoneNumberInput]);

    const e164PhoneNumber = useMemo(() => {
        if (normalizedSubscriberPhoneNumber.length === 10) {
            return `+63${normalizedSubscriberPhoneNumber}`;
        }

        return '';
    }, [normalizedSubscriberPhoneNumber]);

    const resetRecaptchaVerifier = (): RecaptchaVerifier | null => {
        if (typeof window === 'undefined') {
            return null;
        }

        recaptchaVerifierRef.current?.clear();
        recaptchaVerifierRef.current = new RecaptchaVerifier(firebaseAuth, 'claim-recaptcha', {
            size: 'invisible',
        });

        return recaptchaVerifierRef.current;
    };

    useEffect(() => {
        resetRecaptchaVerifier();

        return () => {
            recaptchaVerifierRef.current?.clear();
            recaptchaVerifierRef.current = null;
        };
    }, []);

    const handleSendOtp = async (): Promise<void> => {
        setOtpError(null);
        setStatusMessage(null);
        setOtpSent(false);

        if (!normalizedSubscriberPhoneNumber) {
            setOtpError('Enter your enrolled number starting with 9.');

            return;
        }

        setSendingOtp(true);
        try {
            const getCookie = (name: string): string => {
                const cookie = document.cookie
                    .split('; ')
                    .find((row) => row.startsWith(`${name}=`))
                    ?.split('=')[1];

                return cookie ? decodeURIComponent(cookie) : '';
            };

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content')
                    ?.trim() ?? '';
            const xsrfToken = getCookie('XSRF-TOKEN');
            const effectiveToken = csrfToken || xsrfToken;

            const formData = new URLSearchParams();
            if (effectiveToken) {
                formData.set('_token', effectiveToken);
            }
            formData.set('phone_number', `+63${normalizedSubscriberPhoneNumber}`);

            const response = await fetch(`/account/claim/${token}/otp/send`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(effectiveToken ? { 'X-CSRF-TOKEN': effectiveToken } : {}),
                    ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
                },
                body: formData.toString(),
            });

            if (!response.ok) {
                const payload = (await response.json().catch(() => null)) as
                    | {
                          message?: string;
                          errors?: Record<string, string[]>;
                      }
                    | null;

                const phoneErrors = payload?.errors?.phone_number;
                const tokenErrors = payload?.errors?.token;
                setOtpError(
                    phoneErrors?.[0] ??
                        tokenErrors?.[0] ??
                        payload?.message ??
                        'Phone number does not match our enrollment record.',
                );
                setSendingOtp(false);

                return;
            }

            if (!e164PhoneNumber) {
                setSendingOtp(false);
                setOtpError('Invalid phone number format.');

                return;
            }

            const verifier = resetRecaptchaVerifier();
            if (!verifier) {
                setSendingOtp(false);
                setOtpError('reCAPTCHA is not ready yet. Please try again.');

                return;
            }

            try {
                confirmationResultRef.current = await signInWithPhoneNumber(
                    firebaseAuth,
                    e164PhoneNumber,
                    verifier,
                );
                setOtpSent(true);
                setStatusMessage('OTP sent to your phone. Enter the 6-digit code.');
            } catch (error: unknown) {
                const firebaseErrorCode =
                    typeof error === 'object' &&
                    error !== null &&
                    'code' in error &&
                    typeof (error as { code?: string }).code === 'string'
                        ? (error as { code: string }).code
                        : null;
                const firebaseErrorMessage =
                    typeof error === 'object' &&
                    error !== null &&
                    'message' in error &&
                    typeof (error as { message?: string }).message === 'string'
                        ? (error as { message: string }).message
                        : null;

                if (firebaseErrorCode === 'auth/unauthorized-domain') {
                    setOtpError(
                        'This domain is not authorized in Firebase Auth. Add your local host/domain in Firebase Authorized domains.',
                    );
                } else if (firebaseErrorCode === 'auth/invalid-phone-number') {
                    setOtpError('Invalid phone format. Enter 9XXXXXXXXX after +63.');
                } else if (firebaseErrorCode === 'auth/operation-not-allowed') {
                    setOtpError('Phone auth is disabled in Firebase. Enable Phone provider.');
                } else if (firebaseErrorCode === 'auth/too-many-requests') {
                    setOtpError('Too many attempts. Please wait and try again.');
                } else if (firebaseErrorCode === 'auth/captcha-check-failed') {
                    setOtpError(
                        'reCAPTCHA failed. Reload the page, disable ad/script blockers, and ensure this host is in Firebase Authorized domains.',
                    );
                } else {
                    setOtpError(
                        firebaseErrorCode
                            ? `Failed to send OTP (${firebaseErrorCode}). Check Firebase settings.`
                            : firebaseErrorMessage
                              ? `Failed to send OTP: ${firebaseErrorMessage}`
                              : 'Failed to send OTP. Check phone format and Firebase auth settings.',
                    );
                }
            } finally {
                setSendingOtp(false);
            }
        } catch {
            setSendingOtp(false);
            setOtpError('Unable to validate your phone number right now. Please retry.');
        }
    };

    const handleVerifyOtp = async (): Promise<void> => {
        setOtpError(null);
        setStatusMessage(null);

        if (!confirmationResultRef.current) {
            setOtpError('Send OTP first before verifying.');

            return;
        }

        if (otpCode.trim().length !== 6) {
            setOtpError('Enter the 6-digit OTP code.');

            return;
        }

        try {
            setVerifyingOtp(true);
            const credential = await confirmationResultRef.current.confirm(otpCode.trim());
            const idToken = await credential.user.getIdToken(true);

            router.post(
                `/account/claim/${token}/otp/verify`,
                { id_token: idToken },
                {
                    preserveScroll: true,
                    onError: () => {
                        setOtpError('OTP verification failed. Please retry.');
                    },
                },
            );
        } catch {
            setOtpError('Invalid or expired OTP code.');
        } finally {
            setVerifyingOtp(false);
        }
    };

    const handleSetPassword = (): void => {
        setPasswordError(null);

        if (!password || password !== passwordConfirmation) {
            setPasswordError('Passwords must match.');

            return;
        }

        setSavingPassword(true);

        router.post(
            `/account/claim/${token}`,
            {
                password,
                password_confirmation: passwordConfirmation,
            },
            {
                preserveScroll: true,
                onError: () => {
                    setPasswordError('Unable to set password. Check validation and try again.');
                },
                onFinish: () => setSavingPassword(false),
            },
        );
    };

    return (
        <AuthLayout
            title="Claim your account"
            description="Verify your phone and create a password to activate your MarriottConnect access."
        >
            <div className="grid gap-6">
                <div id="claim-recaptcha" className="hidden" />

                {!canSetPassword && (
                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p className="text-sm font-medium text-slate-700">Mobile Number:</p>
                        <p className="mt-1 text-base text-slate-900">{phone_number_redacted}</p>
                    </div>
                )}

                {!phone_verified && !otpSent && (
                    <div className="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p className="text-sm text-slate-600">
                            Enter the full phone number used during enrollment. We will verify it
                            before sending OTP.
                        </p>
                        <div className="grid gap-2">
                            <Label htmlFor="phone_number_input">Phone number</Label>
                            <div className="flex w-full min-w-0">
                                <span className="inline-flex items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                    +63
                                </span>
                                <Input
                                    id="phone_number_input"
                                    className="rounded-l-none"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={10}
                                    placeholder="9XXXXXXXXX"
                                    value={phoneNumberInput}
                                    onChange={(event) =>
                                        setPhoneNumberInput(
                                            normalizeMobileSubscriberDigits(
                                                event.target.value,
                                            ),
                                        )
                                    }
                                />
                            </div>
                            <InputError message={otpError || undefined} />
                            <InputError message={statusMessage || undefined} />
                        </div>
                        <Button type="button" onClick={handleSendOtp} disabled={sendingOtp}>
                            {sendingOtp && <Spinner />}
                            Verify phone and send OTP
                        </Button>
                    </div>
                )}

                {!phone_verified && otpSent && (
                    <div className="grid gap-4 rounded-xl border border-slate-200 p-4">
                        <p className="text-sm text-slate-700">
                            Phone number verified! Check the phone number for the one-time password
                            to claim your account.
                        </p>
                        <div className="grid gap-2">
                            <Label htmlFor="code">OTP code</Label>
                            <Input
                                id="code"
                                inputMode="numeric"
                                pattern="[0-9]*"
                                maxLength={6}
                                placeholder="Enter 6-digit code"
                                value={otpCode}
                                onChange={(event) =>
                                    setOtpCode(event.target.value.replace(/\D/g, '').slice(0, 6))
                                }
                            />
                            <InputError message={otpError || undefined} />
                        </div>
                        <Button type="button" onClick={handleVerifyOtp} disabled={verifyingOtp}>
                            {verifyingOtp && <Spinner />}
                            Verify OTP
                        </Button>
                    </div>
                )}

                {canSetPassword && (
                    <div className="grid gap-6 rounded-xl border border-slate-200 p-4">
                        <div className="grid gap-2">
                            <Label htmlFor="account_email">Account email</Label>
                            <Input id="account_email" type="email" value={account_email} readOnly />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <PasswordInput
                                id="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder="Password"
                                value={password}
                                onChange={(event) => setPassword(event.target.value)}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>
                            <PasswordInput
                                id="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Confirm password"
                                value={passwordConfirmation}
                                onChange={(event) => setPasswordConfirmation(event.target.value)}
                            />
                            <InputError message={passwordError || undefined} />
                        </div>

                        <Button type="button" className="mt-2 w-full" disabled={savingPassword} onClick={handleSetPassword}>
                            {savingPassword && <Spinner />}
                            Set password
                        </Button>
                    </div>
                )}

                {is_expired && !phone_verified && (
                    <p className="text-sm text-amber-700">
                        This claim link has expired. Verify OTP to generate a new claim link.
                    </p>
                )}
            </div>
        </AuthLayout>
    );
}
