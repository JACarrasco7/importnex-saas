import { ref, onMounted } from 'vue';
import axios from 'axios';

/**
 * Composable para gestionar suscripciones Web Push vía OneSignal.
 *
 * OneSignal maneja VAPID, service worker y suscripciones internamente.
 * El frontend solo necesita:
 *   1. Inicializar el SDK con el app_id
 *   2. Pedir permiso al usuario (button-triggered)
 *   3. OneSignal gestiona el resto (SW, suscripción, notificaciones)
 *
 * Si OneSignal no está configurado (response.enabled=false),
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
            if (r.data?.enabled && r.data?.app_id) {
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

    async function initOneSignal() {
        if (!window.OneSignal) {
            lastError.value = 'OneSignal SDK not loaded';
            return false;
        }

        try {
            await checkVapid();
            if (!enabled.value) {
                return false;
            }

            await window.OneSignal.init({
                appId: (await axios.get('/push/vapid-public-key')).data.app_id,
                allowLocalhost: true,
                autoRegister: false,
                notifyButton: {
                    enable: false,
                },
                promptOptions: {
                    // No auto-prompt — user triggers via button
                    slidedown: {
                        enabled: false,
                    },
                },
            });

            const sub = await window.OneSignal.getSubscription();
            subscribed.value = sub?.toSubscription || false;
            permission.value = Notification.permission;
        } catch (e) {
            lastError.value = e?.message || 'OneSignal init failed';
            return false;
        }
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

            if (!window.OneSignal) {
                lastError.value = 'OneSignal SDK not loaded';
                return false;
            }

            await window.OneSignal.showNativePrompt();
            permission.value = Notification.permission;

            if (permission.value !== 'granted') {
                lastError.value = 'Permiso denegado';
                return false;
            }

            const sub = await window.OneSignal.getSubscription();
            if (sub?.toSubscription) {
                subscribed.value = true;
                return true;
            }

            return false;
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
            if (window.OneSignal) {
                await window.OneSignal.removeSubscription();
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
        await initOneSignal();
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