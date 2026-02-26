const CACHE_NAME = 'marriott-pwa-v4';
const OFFLINE_URL = '/offline.html';
const STATIC_ASSETS = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/maskable-192.png',
    '/icons/maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (
        url.pathname === '/manifest.webmanifest' ||
        url.pathname === '/pwa-manifest.webmanifest' ||
        url.pathname === '/sw.js'
    ) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );

        return;
    }

    const canCache =
        request.destination === 'script' ||
        request.destination === 'style' ||
        request.destination === 'font' ||
        request.destination === 'image' ||
        url.pathname.startsWith('/build/');

    if (!canCache) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(request).then((networkResponse) => {
                const contentType = (
                    networkResponse.headers.get('content-type') || ''
                ).toLowerCase();

                const validCachedAsset =
                    (request.destination === 'script' &&
                        (contentType.includes('javascript') ||
                            contentType.includes('ecmascript'))) ||
                    (request.destination === 'style' &&
                        contentType.includes('text/css')) ||
                    (request.destination === 'font' &&
                        (contentType.startsWith('font/') ||
                            contentType.includes('application/font') ||
                            contentType.includes(
                                'application/octet-stream',
                            ))) ||
                    (request.destination === 'image' &&
                        contentType.startsWith('image/')) ||
                    (url.pathname.startsWith('/build/') &&
                        !contentType.includes('text/html'));

                if (networkResponse.ok && validCachedAsset) {
                    const clone = networkResponse.clone();
                    caches
                        .open(CACHE_NAME)
                        .then((cache) => cache.put(request, clone));
                }

                return networkResponse;
            });
        }),
    );
});
