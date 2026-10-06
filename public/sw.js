const SHELL_CACHE = 'appointly-shell-v2';
const ASSET_CACHE = 'appointly-assets-v2';
const APP_SHELL = ['/manifest.webmanifest', '/icons/app-icon.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(SHELL_CACHE).then((cache) => cache.addAll(APP_SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((cacheNames) => Promise.all(
        cacheNames
            .filter((cacheName) => cacheName.startsWith('appointly-shell-') || cacheName.startsWith('appointly-assets-'))
            .filter((cacheName) => cacheName !== SHELL_CACHE && cacheName !== ASSET_CACHE)
            .map((cacheName) => caches.delete(cacheName))
    )).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) return;

    if (requestUrl.pathname.startsWith('/build/assets/')) {
        event.respondWith(caches.open(ASSET_CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);
            if (cached) return cached;

            const response = await fetch(event.request);
            if (response.ok) await cache.put(event.request, response.clone());
            return response;
        }));
        return;
    }

    if (event.request.mode === 'navigate' || event.request.destination === 'document') {
        event.respondWith(fetch(event.request, { cache: 'no-store' }));
        return;
    }

    if (requestUrl.pathname === '/manifest.webmanifest' || requestUrl.pathname.startsWith('/icons/')) {
        event.respondWith(caches.open(SHELL_CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);
            if (cached) return cached;

            const response = await fetch(event.request);
            if (response.ok) await cache.put(event.request, response.clone());
            return response;
        }));
    }
});
