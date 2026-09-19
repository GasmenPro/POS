const CACHE_NAME = 'sari-pos-offline-v3-1.0.0';
const BASE = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const OFFLINE_PAGE = BASE + '/pos/offline.php';
const STATIC_ASSETS = [
    BASE + '/assets/css/offline-pos.css',
    BASE + '/assets/js/offline-pos.js',
    BASE + '/manifest.webmanifest'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function (cache) { return cache.addAll(STATIC_ASSETS); })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(keys.filter(function (key) {
                    return key.startsWith('sari-pos-offline-') && key !== CACHE_NAME;
                }).map(function (key) { return caches.delete(key); }));
            })
            .then(function () { return self.clients.claim(); })
    );
});

self.addEventListener('message', function (event) {
    if (!event.data || event.data.type !== 'CACHE_OFFLINE_PAGE') {
        return;
    }
    event.waitUntil(
        fetch(OFFLINE_PAGE, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (response) {
                if (!response.ok || response.redirected) {
                    throw new Error('Offline page was not authorized.');
                }
                return caches.open(CACHE_NAME).then(function (cache) {
                    return cache.put(OFFLINE_PAGE, response);
                });
            })
            .catch(function () { return null; })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname === OFFLINE_PAGE) {
        event.respondWith(
            fetch(event.request)
                .then(function (response) {
                    if (response.ok && !response.redirected) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) { cache.put(OFFLINE_PAGE, copy); });
                    }
                    return response;
                })
                .catch(function () { return caches.match(OFFLINE_PAGE); })
        );
        return;
    }

    if (STATIC_ASSETS.indexOf(url.pathname) !== -1) {
        event.respondWith(
            caches.match(event.request).then(function (cached) {
                return cached || fetch(event.request).then(function (response) {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, copy); });
                    }
                    return response;
                });
            })
        );
    }
});
