import { usePage } from '@inertiajs/react';
import { Download, Smartphone, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';

const DISMISS_STORAGE_KEY = 'pwa_install_banner_dismissed_at';
const DISMISS_COOLDOWN_MS = 1000 * 60 * 60 * 24 * 7;
const MOBILE_USER_AGENT_PATTERN =
    /Android|iPhone|iPad|iPod|Mobile|IEMobile|Opera Mini/i;

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
};

const isStandaloneMode = (): boolean => {
    const standaloneMediaQuery = window.matchMedia(
        '(display-mode: standalone)',
    ).matches;
    const iosStandalone = (
        window.navigator as Navigator & { standalone?: boolean }
    ).standalone;

    return standaloneMediaQuery || iosStandalone === true;
};

const isLikelyMobileDevice = (): boolean => {
    const coarsePointer = window.matchMedia('(pointer: coarse)').matches;
    const mobileUserAgent = MOBILE_USER_AGENT_PATTERN.test(
        window.navigator.userAgent,
    );

    return coarsePointer || mobileUserAgent;
};

export function PwaInstallBanner() {
    const { auth } = usePage<SharedData>().props;
    const showForRole = Boolean(auth.user?.id);

    const [deferredPrompt, setDeferredPrompt] =
        useState<BeforeInstallPromptEvent | null>(null);
    const [isInstalled, setIsInstalled] = useState(false);
    const [isDismissed, setIsDismissed] = useState(false);
    const [isInstalling, setIsInstalling] = useState(false);
    const [isUpdateAvailable, setIsUpdateAvailable] = useState(false);
    const [isUpdating, setIsUpdating] = useState(false);
    const [isMobileDevice, setIsMobileDevice] = useState(false);

    useEffect(() => {
        if (!showForRole || typeof window === 'undefined') {
            return;
        }

        setIsMobileDevice(isLikelyMobileDevice());

        if (isStandaloneMode()) {
            setIsInstalled(true);

            return;
        }

        const dismissedAtRaw = window.localStorage.getItem(DISMISS_STORAGE_KEY);
        if (dismissedAtRaw) {
            const dismissedAt = Number(dismissedAtRaw);
            if (
                Number.isFinite(dismissedAt) &&
                Date.now() - dismissedAt < DISMISS_COOLDOWN_MS
            ) {
                setIsDismissed(true);
            } else {
                window.localStorage.removeItem(DISMISS_STORAGE_KEY);
            }
        }

        const handleBeforeInstallPrompt = (event: Event) => {
            const promptEvent = event as BeforeInstallPromptEvent;
            promptEvent.preventDefault();
            setDeferredPrompt(promptEvent);
        };

        const handleAppInstalled = () => {
            setIsInstalled(true);
            setDeferredPrompt(null);
            setIsDismissed(true);
            window.localStorage.removeItem(DISMISS_STORAGE_KEY);
        };

        window.addEventListener(
            'beforeinstallprompt',
            handleBeforeInstallPrompt,
        );
        window.addEventListener('appinstalled', handleAppInstalled);

        const handleUpdateAvailable = () => {
            setIsUpdateAvailable(true);
        };

        window.addEventListener('pwa:update-available', handleUpdateAvailable);

        return () => {
            window.removeEventListener(
                'beforeinstallprompt',
                handleBeforeInstallPrompt,
            );
            window.removeEventListener('appinstalled', handleAppInstalled);
            window.removeEventListener(
                'pwa:update-available',
                handleUpdateAvailable,
            );
        };
    }, [showForRole]);

    const dismiss = () => {
        setIsDismissed(true);
        window.localStorage.setItem(DISMISS_STORAGE_KEY, String(Date.now()));
    };

    const install = async () => {
        if (!deferredPrompt || isInstalling) {
            return;
        }

        setIsInstalling(true);
        await deferredPrompt.prompt();
        const result = await deferredPrompt.userChoice;
        setIsInstalling(false);
        setDeferredPrompt(null);

        if (result.outcome === 'accepted') {
            setIsInstalled(true);
            setIsDismissed(true);
            window.localStorage.removeItem(DISMISS_STORAGE_KEY);
        }
    };

    const applyUpdate = async () => {
        if (isUpdating || typeof window === 'undefined') {
            return;
        }

        const registration = await navigator.serviceWorker.getRegistration();
        if (!registration?.waiting) {
            return;
        }

        setIsUpdating(true);

        const handleControllerChange = () => {
            window.location.reload();
        };

        navigator.serviceWorker.addEventListener(
            'controllerchange',
            handleControllerChange,
            { once: true },
        );

        registration.waiting.postMessage({ type: 'SKIP_WAITING' });
    };

    if (!showForRole || !isMobileDevice || isInstalled) {
        return null;
    }

    if (isUpdateAvailable) {
        return (
            <Alert className="mb-4">
                <Smartphone className="size-4" />
                <AlertTitle>Update available</AlertTitle>
                <AlertDescription>
                    <p>A new app version is ready to install.</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            className="h-8"
                            onClick={applyUpdate}
                            disabled={isUpdating}
                        >
                            <Download className="size-3.5" />
                            {isUpdating ? 'Updating...' : 'Update now'}
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>
        );
    }

    if (isDismissed || deferredPrompt === null) {
        return null;
    }

    return (
        <Alert className="mb-4">
            <Smartphone className="size-4" />
            <AlertTitle>Install this app</AlertTitle>
            <AlertDescription>
                <p>
                    Install for faster access to announcements, grades, and
                    reminders.
                </p>
                <div className="mt-2 flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        className="h-8"
                        onClick={install}
                        disabled={isInstalling}
                    >
                        <Download className="size-3.5" />
                        {isInstalling ? 'Installing...' : 'Install App'}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8"
                        onClick={dismiss}
                    >
                        <X className="size-3.5" />
                        Not now
                    </Button>
                </div>
            </AlertDescription>
        </Alert>
    );
}
