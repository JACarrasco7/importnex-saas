// JJ Import Motors Service Worker
// Maneja push notifications (Web Push API) y notifica al usuario.
// También cachea assets estáticos para mejorar TTI offline.

const CACHE_NAME = 'jj-import-v1';
const PRECACHE_URLS = ['/', '/marketplace', '/pricing'];

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

// Fetch: network-first, cache fallback para offline-tolerant
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const clone = response.clone();
                if (response.ok && response.status === 200) {
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => caches.match(event.request).then((cached) => cached || new Response('Offline', { status: 503 })))
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
        icon: '/images/icon-192.png',
        badge: '/images/badge-72.png',
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
