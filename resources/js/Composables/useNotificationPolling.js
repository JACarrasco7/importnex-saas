import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * useNotificationPolling
 *
 * Polling reactivo de alertas cada N segundos. Pensado para reemplazar
 * (o complementar) la falta de WebSockets en MVP sin sobrecargar BD.
 *
 * Devuelve:
 *   - count:        numero de alertas pendientes (cached)
 *   - latestId:     id de la alerta mas reciente (para detectar nuevas)
 *   - recent:       lista corta de alertas recientes (para toasts)
 *   - newCount:     contador de alertas NUEVAS desde la ultima carga de pagina
 *   - toasts:       cola de toasts a renderizar
 *   - markSeen():   limpia el contador de nuevas (llamar al visitar /alerts)
 *   - refresh():    polling manual
 *
 * Persiste lastSeenId en localStorage para que las toasts sobrevivan a
 * recargas de pagina dentro de la misma sesion del navegador.
 */
export function useNotificationPolling({ intervalMs = 30000, storageKey = 'notif:lastSeenId' } = {}) {
    const count = ref(0);
    const latestId = ref(null);
    const recent = ref([]);
    const newCount = ref(0);
    const toasts = ref([]);
    const isPolling = ref(false);
    const lastError = ref(null);

    let timer = null;

    function readLastSeen() {
        try {
            const v = localStorage.getItem(storageKey);
            return v ? Number(v) : null;
        } catch (e) {
            return null;
        }
    }

    function writeLastSeen(id) {
        try {
            if (id !== null && id !== undefined) {
                localStorage.setItem(storageKey, String(id));
            }
        } catch (e) {
            // localStorage disabled / private mode
        }
    }

    async function refresh() {
        if (isPolling.value) return;
        isPolling.value = true;
        lastError.value = null;
        try {
            const resp = await window.axios.get(route('alerts.pending'));
            const data = resp.data || { count: 0, latest_id: null, recent: [] };

            count.value = data.count || 0;
            latestId.value = data.latest_id || null;
            recent.value = data.recent || [];

            // Detectar alertas nuevas comparando con lastSeenId
            const lastSeen = readLastSeen();
            if (latestId.value && lastSeen && latestId.value > lastSeen) {
                newCount.value = latestId.value - lastSeen;
                // Solo emitimos toast para las que el usuario no ha visto
                const unseen = recent.value.filter((a) => a.id > lastSeen);
                unseen.reverse().forEach((a) => pushToast(a));
            } else if (latestId.value && !lastSeen) {
                // Primera vez: no spameamos toast, solo sincronizamos el seen
                writeLastSeen(latestId.value);
            }
        } catch (e) {
            lastError.value = e?.message || 'polling failed';
        } finally {
            isPolling.value = false;
        }
    }

    function pushToast(alert) {
        // Limite: max 5 toasts visibles, descartamos los antiguos
        toasts.value.push({ ...alert, toastId: Date.now() + Math.random(), at: new Date() });
        if (toasts.value.length > 5) toasts.value.shift();
        // Auto-dismiss a los 8s
        setTimeout(() => dismissToast(toasts.value.find((t) => t.id === alert.id)?.toastId), 8000);
    }

    function dismissToast(toastId) {
        if (toastId === undefined) return;
        toasts.value = toasts.value.filter((t) => t.toastId !== toastId);
    }

    function markSeen() {
        if (latestId.value) {
            writeLastSeen(latestId.value);
        }
        newCount.value = 0;
    }

    function start() {
        // Primer fetch inmediato, luego cada intervalMs
        refresh();
        if (timer) clearInterval(timer);
        timer = setInterval(refresh, intervalMs);
    }

    function stop() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Auto start/stop si se monta dentro de un componente
    onMounted(start);
    onUnmounted(stop);

    return {
        // State
        count: computed(() => count.value),
        latestId: computed(() => latestId.value),
        recent: computed(() => recent.value),
        newCount: computed(() => newCount.value),
        toasts: computed(() => toasts.value),
        isPolling: computed(() => isPolling.value),
        lastError: computed(() => lastError.value),
        // Actions
        refresh,
        markSeen,
        dismissToast,
        start,
        stop,
    };
}
