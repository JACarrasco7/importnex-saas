import { ref, onMounted } from 'vue';
import axios from 'axios';

/**
 * Composable para gestionar suscripciones Web Push en el cliente.
 *
 * Flujo:
 *  1. Comprobar si el navegador soporta Push API y service workers.
 *  2. Pedir permiso al usuario (button-triggered, nunca auto-prompt).
 *  3. Registrar el service worker (/sw.js).
 *  4. Subscribirse vía PushManager con la VAPID key del backend.
 *  5. Enviar la suscripción al backend (POST /push/subscribe).
 *
 * Si VAPID no está configurado en backend (response.enabled=false),
 * el composable degrada gracefully: UI muestra "no disponible" sin error.
 */
export function usePushNotifications() {
    const supported = ref(false);
    const permission = ref('default');
    const subscribed = ref(false);
    const enabled = ref(true);
    const vapidConfigured = ref(false);
    const loading = ref(false);
    const lastError = ref('');

    onMounted(() => {
        if (typeof window === 'undefined') return;
        supported.value = 'serviceWorker' in navigator && 'PushManager' in window;
        if (supported.value) {
            permission.value = Notification.permission;
        }
    });

    async function checkVapid() {
        try {
            const r = await axios.get('/push/vapid-public-key');
            if (r.data?.enabled && r.data?.public_key) {
                enabled.value = true;
                vapidConfigured.value = true;
            } else {
                enabled.value = false;
                vapidConfigured.value = false;
            }
        } catch (e) {
            enabled.value = false;
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }

    async function subscribe() {
        if (!supported.value) {
            lastError.value = 'Push no soportado en este navegador';
            return false;
        }
        loading.value = true;
        lastError.value = '';
        try {
            await checkVapid();
            if (!enabled.value) {
                lastError.value = 'Push no configurado en el servidor';
                return false;
            }

            const perm = await Notification.requestPermission();
            permission.value = perm;
            if (perm !== 'granted') {
                lastError.value = 'Permiso denegado';
                return false;
            }

            const reg = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            const r = await axios.get('/push/vapid-public-key');
            const key = r.data.public_key;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(key),
            });

            await axios.post('/push/subscribe', sub.toJSON());
            subscribed.value = true;
            return true;
        } catch (e) {
            lastError.value = e?.response?.data?.message || e.message;
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function unsubscribe() {
        loading.value = true;
        try {
            const reg = await navigator.serviceWorker.getRegistration('/sw.js');
            if (reg) {
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await axios.delete('/push/subscribe', { data: { endpoint: sub.endpoint } });
                    await sub.unsubscribe();
                }
            }
            subscribed.value = false;
            return true;
        } catch (e) {
            lastError.value = e.message;
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function init() {
        if (!supported.value) return;
        try {
            const reg = await navigator.serviceWorker.getRegistration('/sw.js');
            if (reg) {
                const sub = await reg.pushManager.getSubscription();
                subscribed.value = !!sub;
            }
        } catch (e) {
            // silent
        }
    }

    return {
        supported,
        permission,
        subscribed,
        enabled,
        vapidConfigured,
        loading,
        lastError,
        init,
        subscribe,
        unsubscribe,
    };
}