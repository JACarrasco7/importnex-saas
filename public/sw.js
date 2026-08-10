// JJ Import Motors Service Worker
// Maneja push notifications (Web Push API) y notifica al usuario.
// También cachea assets estáticos para mejorar TTI offline.

const CACHE_NAME = 'jj-import-v3';
const PRECACHE_URLS = ['/', '/offline', '/manifest.json'];

// Install: pre-cachear páginas críticas
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

// Fetch: network-first, cache fallback SOLO para offline-tolerant.
// - Rutas autenticadas (/cars/*, /billing/*, /admin/*) NUNCA se cachean para evitar 404/503 servidos desde SW.
// - Assets estáticos (/build/*, /img/*) sí se cachean.
const NO_CACHE_PREFIXES = ['/cars', '/billing', '/admin', '/subscriptions', '/valuations', '/imports', '/clients', '/settings'];
const STATIC_PREFIXES = ['/build/', '/img/', '/images/', '/fonts/'];
const STATIC_EXACT = ['/', '/manifest.json', '/offline'];

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    // Rutas autenticadas: pass-through directo, sin SW. Cualquier error 5xx será el real del servidor.
    if (NO_CACHE_PREFIXES.some((p) => url.pathname.startsWith(p))) {
        return;
    }

    // Solo cachear assets estáticos o páginas públicas conocidas.
    const isStatic = STATIC_PREFIXES.some((p) => url.pathname.startsWith(p)) || STATIC_EXACT.includes(url.pathname);
    if (!isStatic) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) return cached;
            return fetch(event.request).then((response) => {
                if (response.ok && response.status === 200) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            }).catch(() => caches.match('/offline').then((page) => page || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } })));
        })
    );
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
