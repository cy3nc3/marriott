export type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
};

type PwaWindow = Window & {
    __PWA_DEFERRED_PROMPT__?: BeforeInstallPromptEvent | null;
    __PWA_INSTALL_CAPTURE_INITIALIZED__?: boolean;
};

export type PwaInstallGuide = {
    title: string;
    steps: string[];
    note?: string;
};

type NavigatorWithInstalledRelatedApps = Navigator & {
    getInstalledRelatedApps?: () => Promise<
        Array<{
            id?: string;
            url?: string;
            platform?: string;
        }>
    >;
};

const PWA_INSTALL_PROMPT_AVAILABLE_EVENT = 'pwa:install-prompt-available';
const PWA_APP_INSTALLED_EVENT = 'pwa:app-installed';
const PWA_INSTALLED_HINT_STORAGE_KEY = 'pwa_installed_hint';

const isLocalhost = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1' ||
        window.location.hostname === '[::1]'
    );
};

const isInstallableContext = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.isSecureContext || isLocalhost();
};

const setDeferredInstallPrompt = (
    promptEvent: BeforeInstallPromptEvent | null,
): void => {
    (window as PwaWindow).__PWA_DEFERRED_PROMPT__ = promptEvent;
};

export const getDeferredInstallPrompt = (): BeforeInstallPromptEvent | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return (window as PwaWindow).__PWA_DEFERRED_PROMPT__ ?? null;
};

export const initializePwaInstallPromptCapture = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    const pwaWindow = window as PwaWindow;
    if (pwaWindow.__PWA_INSTALL_CAPTURE_INITIALIZED__) {
        return;
    }

    pwaWindow.__PWA_INSTALL_CAPTURE_INITIALIZED__ = true;

    window.addEventListener('beforeinstallprompt', (event) => {
        const promptEvent = event as BeforeInstallPromptEvent;
        promptEvent.preventDefault();
        setDeferredInstallPrompt(promptEvent);
        window.dispatchEvent(new Event(PWA_INSTALL_PROMPT_AVAILABLE_EVENT));
    });

    window.addEventListener('appinstalled', () => {
        window.localStorage.setItem(PWA_INSTALLED_HINT_STORAGE_KEY, '1');
        setDeferredInstallPrompt(null);
        window.dispatchEvent(new Event(PWA_APP_INSTALLED_EVENT));
    });
};

export const isLikelyAlreadyInstalled = async (): Promise<boolean> => {
    if (typeof window === 'undefined') {
        return false;
    }

    if (window.localStorage.getItem(PWA_INSTALLED_HINT_STORAGE_KEY) === '1') {
        return true;
    }

    const navigatorWithInstalledApps =
        navigator as NavigatorWithInstalledRelatedApps;

    if (!navigatorWithInstalledApps.getInstalledRelatedApps) {
        return false;
    }

    try {
        const installedApps =
            await navigatorWithInstalledApps.getInstalledRelatedApps();
        const currentOrigin = window.location.origin;

        return installedApps.some((installedApp) => {
            if (installedApp.platform !== 'webapp') {
                return false;
            }

            return (
                typeof installedApp.url === 'string' &&
                installedApp.url.startsWith(currentOrigin)
            );
        });
    } catch {
        return false;
    }
};

export const onInstallPromptAvailable = (
    callback: () => void,
): (() => void) => {
    if (typeof window === 'undefined') {
        return () => undefined;
    }

    const handler = () => callback();
    window.addEventListener(PWA_INSTALL_PROMPT_AVAILABLE_EVENT, handler);

    return () => {
        window.removeEventListener(PWA_INSTALL_PROMPT_AVAILABLE_EVENT, handler);
    };
};

export const onPwaAppInstalled = (callback: () => void): (() => void) => {
    if (typeof window === 'undefined') {
        return () => undefined;
    }

    const handler = () => callback();
    window.addEventListener(PWA_APP_INSTALLED_EVENT, handler);

    return () => {
        window.removeEventListener(PWA_APP_INSTALLED_EVENT, handler);
    };
};

export const shouldRegisterServiceWorker = (): boolean => {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return false;
    }

    return 'serviceWorker' in navigator && isInstallableContext();
};

export const getInstallUnavailableMessage = async (): Promise<string> => {
    if (typeof window === 'undefined') {
        return 'Install is not available in this environment.';
    }

    if (await isLikelyAlreadyInstalled()) {
        return 'This app appears to be already installed in this Chrome profile. Open it from Chrome Apps or remove the installed app first if you want to reinstall.';
    }

    if (!isInstallableContext()) {
        return 'Install is only available on HTTPS (or localhost). Open this app using HTTPS and try again.';
    }

    if (!('serviceWorker' in navigator)) {
        return 'This browser does not support service workers, so app install is unavailable.';
    }

    const registration = await navigator.serviceWorker.getRegistration();
    if (!registration) {
        return 'Install is not ready yet because service worker is not active. Reload this page and try again.';
    }

    if (!navigator.serviceWorker.controller) {
        return 'Install is not ready yet because service worker is still initializing. Reload this page once, then try Install App again.';
    }

    return 'Chrome has not exposed the install prompt in this session yet. In Chrome, open menu (three dots) then choose Install app. If still missing, clear site data and reload.';
};

export const getInstallGuide = (): PwaInstallGuide => {
    if (typeof window === 'undefined') {
        return {
            title: 'Install not available',
            steps: ['Open this app in a supported mobile or desktop browser.'],
        };
    }

    const userAgent = window.navigator.userAgent;
    const isAndroid = /Android/i.test(userAgent);
    const isIOS = /iPhone|iPad|iPod/i.test(userAgent);
    const isEdge = /Edg/i.test(userAgent);
    const isChrome = /Chrome|CriOS/i.test(userAgent) && !isEdge;
    const isSafari =
        /Safari/i.test(userAgent) && !/Chrome|CriOS|FxiOS|Edg/i.test(userAgent);

    if (isIOS && isSafari) {
        return {
            title: 'Install on iPhone or iPad',
            steps: [
                'Tap the Share button in Safari.',
                'Choose Add to Home Screen.',
                'Tap Add to confirm.',
            ],
            note: 'Safari on iOS does not support one-tap install prompts from website buttons.',
        };
    }

    if (isAndroid && isChrome) {
        return {
            title: 'Install on Android Chrome',
            steps: [
                'Open Chrome menu (three dots).',
                'Choose Install app or Add to Home screen.',
                'Tap Install.',
            ],
            note: 'If the option does not appear, reload once and try again.',
        };
    }

    if (isEdge) {
        return {
            title: 'Install on Microsoft Edge',
            steps: [
                'Open Edge menu (three dots).',
                'Choose Apps > Install this site as an app.',
                'Confirm installation.',
            ],
        };
    }

    if (isChrome) {
        return {
            title: 'Install on Chrome',
            steps: [
                'Open Chrome menu (three dots).',
                'Choose Install app.',
                'Confirm installation.',
            ],
            note: 'You can also use the install icon in the address bar when available.',
        };
    }

    return {
        title: 'Install this app',
        steps: [
            'Open your browser menu.',
            'Look for Install app or Add to Home Screen.',
            'Confirm installation.',
        ],
    };
};
