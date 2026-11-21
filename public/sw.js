// JJ Import Motors Service Worker
// Maneja push notifications (Web Push API) y notifica al usuario.
// También cachea assets estáticos para mejorar TTI offline.

// v4 (21-nov-2026): las páginas ('/', '/offline') eran cache-first, así que
// quien ya había entrado veía la portada vieja (con los assets viejos) para
// siempre tras cada despliegue. Ahora el HTML va a red primero y la caché
// solo se usa sin conexión; /build/ (con hash) sigue cache-first.
const CACHE_NAME = 'jj-import-v4';
const PRECACHE_URLS = ['/offline', '/manifest.json'];

// Install: pre-cachear la página offline
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)).catch(() => {})
    );
    self.skipWaiting();
});

// Activate: limpiar caches antiguos
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Rutas autenticadas: nunca pasan por el SW (evita 404/503 servidos desde caché).
const NO_CACHE_PREFIXES = ['/cars', '/billing', '/admin', '/subscriptions', '/valuations', '/imports', '/clients', '/settings'];
// Assets con hash en el nombre: inmutables, cache-first.
const HASHED_PREFIXES = ['/build/'];
// Assets sin hash: se sirven de caché pero se refrescan en segundo plano.
const STATIC_PREFIXES = ['/img/', '/images/', '/fonts/'];
// Páginas que se guardan para poder abrirse sin conexión (red primero).
const PAGE_EXACT = ['/', '/manifest.json', '/offline'];

const offlineFallback = () => caches.match('/offline').then((page) => page || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } }));

const putInCache = (request, response) => {
    if (response && response.ok && response.status === 200) {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
    }
    return response;
};

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    if (NO_CACHE_PREFIXES.some((p) => url.pathname.startsWith(p))) {
        return;
    }

    if (HASHED_PREFIXES.some((p) => url.pathname.startsWith(p))) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => putInCache(event.request, response)))
        );
        return;
    }

    if (STATIC_PREFIXES.some((p) => url.pathname.startsWith(p))) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const network = fetch(event.request).then((response) => putInCache(event.request, response)).catch(() => cached);
                return cached || network;
            })
        );
        return;
    }

    if (PAGE_EXACT.includes(url.pathname)) {
        event.respondWith(
            fetch(event.request)
                .then((response) => putInCache(event.request, response))
                .catch(() => caches.match(event.request).then((cached) => cached || offlineFallback()))
        );
    }
});

// Push: muestra la notificación nativa (N6)
self.addEventListener('push', (event) => {
    let payload = {
        title: 'JJ Import Motors',
        body: 'Tienes una nueva alerta',
        url: '/alerts',
        alert_type: 'unknown',
        alert_id: 0,
    };

    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: '/img/icon-192.png',
        badge: '/img/icon-192.png',
        data: { url: payload.url, alert_type: payload.alert_type, alert_id: payload.alert_id },
        tag: 'alert-' + payload.alert_type,
        renotify: true,
        requireInteraction: false,
        actions: [
            { action: 'open', title: 'Ver' },
            { action: 'dismiss', title: 'Cerrar' },
        ],
    };

    event.waitUntil(self.registration.showNotification(payload.title, options));
});

// Notification click: abre la URL asociada
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const action = event.action;
    if (action === 'dismiss') return;

    const targetUrl = (event.notification.data && event.notification.data.url) || '/alerts';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
