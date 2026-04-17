import { Form, Head, usePage, router } from '@inertiajs/react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import { formatDistanceToNow } from 'date-fns';
import { 
    ShieldBan, ShieldCheck, Monitor, Smartphone, LogOut, Loader2, 
    AlertTriangle, ShieldAlert, KeyRound, History, Lock 
} from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { Separator } from '@/components/ui/separator';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import { edit } from '@/routes/user-password';
import { disable, enable } from '@/routes/two-factor';

interface Session {
    id: string;
    ip_address: string;
    is_current_device: boolean;
    last_active: string;
    user_agent: string;
}

interface Props {
    sessions: Session[];
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security',
        href: edit().url,
    },
];

export default function Security({
    sessions = [],
    twoFactorEnabled = false,
    requiresConfirmation = false,
}: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const { auth } = usePage<SharedData>().props;
    const requiresPasswordChange = Boolean(auth.user?.must_change_password);
    const passwordUpdatedAt = auth.user?.password_updated_at;

    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors: tfaErrors,
    } = useTwoFactorAuth();
    
    const [showPasswordDialog, setShowPasswordDialog] = useState<boolean>(false);
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const [revokingSession, setRevokingSession] = useState<string | null>(null);
    const [sessionIdToRevoke, setSessionIdToRevoke] = useState<string | null>(null);
    const [isRevokeOthersOpen, setIsRevokeOthersOpen] = useState<boolean>(false);

    const revokeSession = (id: string) => {
        setSessionIdToRevoke(id);
    };

    const submitRevokeSession = () => {
        if (!sessionIdToRevoke) return;
        setRevokingSession(sessionIdToRevoke);
        router.delete(`/settings/sessions/${sessionIdToRevoke}`, {
            preserveScroll: true,
            onSuccess: () => setSessionIdToRevoke(null),
            onFinish: () => setRevokingSession(null),
        });
    };

    const revokeOthers = () => {
        setIsRevokeOthersOpen(true);
    };

    const submitRevokeOthers = () => {
        router.delete('/settings/sessions', {
            preserveScroll: true,
            onSuccess: () => setIsRevokeOthersOpen(false),
        });
    };

    const isMobile = (ua: string) => {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Security Settings" />

            <h1 className="sr-only">Security Settings</h1>

            <SettingsLayout>
                <div className="space-y-12">
                    {/* Password Section */}
                    <section className="space-y-6">
                        <Heading
                            variant="small"
                            title="Password"
                            description="Maintain a secure password to protect your account"
                        />

                        {requiresPasswordChange ? (
                            <Alert variant="destructive">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertTitle>Default Password Detected</AlertTitle>
                                <AlertDescription>
                                    You are using a default password. Update your password to continue using the system securely.
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        <div className="flex flex-col items-start gap-4 rounded-lg border p-6 bg-muted/5">
                            <div className="flex items-center gap-4 w-full">
                                <div className="rounded-full bg-primary/10 p-3">
                                    <Lock className="h-6 w-6 text-primary" />
                                </div>
                                <div className="flex-1 space-y-1">
                                    <p className="text-sm font-medium leading-none">Account Password</p>
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                        <History className="h-3 w-3" />
                                        {passwordUpdatedAt ? (
                                            <span>Last updated {formatDistanceToNow(new Date(passwordUpdatedAt as string))} ago</span>
                                        ) : (
                                            <span>No recent update history</span>
                                        )}
                                    </div>
                                </div>
                                <Button onClick={() => setShowPasswordDialog(true)}>
                                    <KeyRound className="mr-2 h-4 w-4" />
                                    Change Password
                                </Button>
                            </div>
                        </div>

                        <Dialog open={showPasswordDialog} onOpenChange={setShowPasswordDialog}>
                            <DialogContent className="sm:max-w-[425px]">
                                <DialogHeader>
                                    <DialogTitle>Change Password</DialogTitle>
                                    <DialogDescription>
                                        Enter your current password and a new, secure one to update your account.
                                    </DialogDescription>
                                </DialogHeader>
                                <Form
                                    action={PasswordController.update().url}
                                    method={PasswordController.update().method}
                                    options={{
                                        preserveScroll: true,
                                    }}
                                    resetOnError={[
                                        'password',
                                        'password_confirmation',
                                        'current_password',
                                    ]}
                                    resetOnSuccess
                                    onSuccess={() => setShowPasswordDialog(false)}
                                    onError={(errors) => {
                                        if (errors.password) {
                                            passwordInput.current?.focus();
                                        }
                                        if (
                                            !requiresPasswordChange &&
                                            errors.current_password
                                        ) {
                                            currentPasswordInput.current?.focus();
                                        }
                                    }}
                                    className="space-y-4 py-4"
                                >
                                    {({ errors, processing, recentlySuccessful }) => (
                                        <>
                                            {!requiresPasswordChange && (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="current_password">
                                                        Current password
                                                    </Label>
                                                    <PasswordInput
                                                        id="current_password"
                                                        ref={currentPasswordInput}
                                                        name="current_password"
                                                        className="block w-full"
                                                        autoComplete="current-password"
                                                        placeholder="Current password"
                                                    />
                                                    <InputError message={errors.current_password} />
                                                </div>
                                            )}

                                            <div className="grid gap-2">
                                                <Label htmlFor="password">
                                                    New password
                                                </Label>
                                                <PasswordInput
                                                    id="password"
                                                    ref={passwordInput}
                                                    name="password"
                                                    className="block w-full"
                                                    autoComplete="new-password"
                                                    placeholder="New password"
                                                />
                                                <InputError message={errors.password} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="password_confirmation">
                                                    Confirm password
                                                </Label>
                                                <PasswordInput
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    className="block w-full"
                                                    autoComplete="new-password"
                                                    placeholder="Confirm password"
                                                />
                                                <InputError message={errors.password_confirmation} />
                                            </div>

                                            <DialogFooter className="pt-4">
                                                <Button 
                                                    type="button" 
                                                    variant="ghost" 
                                                    onClick={() => setShowPasswordDialog(false)}
                                                    disabled={processing}
                                                >
                                                    Cancel
                                                </Button>
                                                <Button type="submit" disabled={processing}>
                                                    {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                                    Update Password
                                                </Button>
                                            </DialogFooter>
                                        </>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </section>

                    <Separator />

                    {/* Two Factor Authentication Section */}
                    <section className="space-y-6">
                        <Heading
                            variant="small"
                            title="Two-Factor Authentication"
                            description="Add an extra layer of security to your account"
                        />

                        {twoFactorEnabled ? (
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Badge variant="outline" className="bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">
                                        <ShieldCheck className="mr-1 h-3 w-3" />
                                        Currently Enabled
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from a TOTP-supported application on your phone (like Google Authenticator or Authy).
                                </p>

                                <TwoFactorRecoveryCodes
                                    recoveryCodesList={recoveryCodesList}
                                    fetchRecoveryCodes={fetchRecoveryCodes}
                                    errors={tfaErrors}
                                />

                                <Form
                                    action={disable().url}
                                    method={disable().method}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            type="submit"
                                            disabled={processing}
                                            size="sm"
                                        >
                                            <ShieldBan className="mr-2 h-4 w-4" /> Disable 2FA
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Badge variant="outline" className="bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-800">
                                        <ShieldAlert className="mr-1 h-3 w-3" />
                                        Disabled
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.
                                </p>

                                <div>
                                    {hasSetupData ? (
                                        <Button
                                            onClick={() => setShowSetupModal(true)}
                                            size="sm"
                                        >
                                            <ShieldCheck className="mr-2 h-4 w-4" />
                                            Continue Setup
                                        </Button>
                                    ) : (
                                        <Form
                                            action={enable().url}
                                            method={enable().method}
                                            onSuccess={() => setShowSetupModal(true)}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    size="sm"
                                                >
                                                    <ShieldCheck className="mr-2 h-4 w-4" />
                                                    Enable 2FA
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </div>
                        )}

                        <TwoFactorSetupModal
                            isOpen={showSetupModal}
                            onClose={() => setShowSetupModal(false)}
                            requiresConfirmation={requiresConfirmation}
                            twoFactorEnabled={twoFactorEnabled}
                            qrCodeSvg={qrCodeSvg}
                            manualSetupKey={manualSetupKey}
                            clearSetupData={clearSetupData}
                            fetchSetupData={fetchSetupData}
                            errors={tfaErrors}
                        />
                    </section>

                    <Separator />

                    {/* Sessions Section */}
                    <section className="space-y-6">
                        <div className="flex items-center justify-between">
                            <Heading
                                variant="small"
                                title="Browser Sessions"
                                description="Manage and log out your active sessions on other browsers and devices"
                            />
                            {sessions.length > 1 && (
                                <Button variant="outline" size="sm" onClick={revokeOthers}>
                                    Log out other sessions
                                </Button>
                            )}
                        </div>

                        <div className="space-y-4">
                            {sessions.map((session) => (
                                <div key={session.id} className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50">
                                    <div className="flex items-center gap-4">
                                        <div className="rounded-full bg-muted p-2">
                                            {isMobile(session.user_agent) ? (
                                                <Smartphone className="h-5 w-5 text-muted-foreground" />
                                            ) : (
                                                <Monitor className="h-5 w-5 text-muted-foreground" />
                                            )
                                            }
                                        </div>
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    {isMobile(session.user_agent) ? 'Mobile Web' : 'Desktop Web'}
                                                </span>
                                                {session.is_current_device && (
                                                    <Badge variant="outline" className="text-xs bg-muted/50 border-none font-normal">This device</Badge>
                                                )}
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {session.ip_address} • Last active {session.last_active}
                                            </p>
                                        </div>
                                    </div>
                                    {!session.is_current_device && (
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            className="text-muted-foreground hover:text-destructive"
                                            onClick={() => revokeSession(session.id)}
                                            disabled={revokingSession === session.id}
                                        >
                                            {revokingSession === session.id ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                <LogOut className="h-4 w-4" />
                                            )}
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
                    </SettingsLayout>
                </AppLayout>

                <ActionConfirmDialog
                    open={sessionIdToRevoke !== null}
                    onOpenChange={(open) => !open && setSessionIdToRevoke(null)}
                    title="Sign Out of Device"
                    description="Are you sure you want to sign out of this device? You will need to log in again to access your account from that device."
                    variant="destructive"
                    confirmLabel="Sign Out"
                    onConfirm={submitRevokeSession}
                />

                <ActionConfirmDialog
                    open={isRevokeOthersOpen}
                    onOpenChange={setIsRevokeOthersOpen}
                    title="Sign Out All Other Devices"
                    description="Are you sure you want to sign out of all other active sessions? This will log you out of every device except for the one you are currently using."
                    variant="destructive"
                    confirmLabel="Sign Out Others"
                    onConfirm={submitRevokeOthers}
                />
            </>
        );
}
