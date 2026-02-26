import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';
import {
    initializePwaInstallPromptCapture,
    shouldRegisterServiceWorker,
} from './lib/pwa-install';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

initializePwaInstallPromptCapture();

if (shouldRegisterServiceWorker()) {
    window.addEventListener('load', () => {
        const pwaVersion =
            (window as Window & { __PWA_VERSION__?: string }).__PWA_VERSION__ ??
            '1';
        const serviceWorkerUrl = `/sw.js?v=${encodeURIComponent(pwaVersion)}`;

        navigator.serviceWorker
            .register(serviceWorkerUrl, { updateViaCache: 'none' })
            .then((registration) => {
                const notifyUpdate = () => {
                    if (registration.waiting) {
                        window.dispatchEvent(new Event('pwa:update-available'));
                    }
                };

                notifyUpdate();

                registration.addEventListener('updatefound', () => {
                    const installingWorker = registration.installing;
                    if (!installingWorker) {
                        return;
                    }

                    installingWorker.addEventListener('statechange', () => {
                        if (
                            installingWorker.state === 'installed' &&
                            navigator.serviceWorker.controller
                        ) {
                            notifyUpdate();
                        }
                    });
                });
            })
            .catch((error) => {
                console.error('Service worker registration failed:', error);
            });
    });
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
