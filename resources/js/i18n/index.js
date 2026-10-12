/**
 * Plugin i18n — expone $t, $locale, $isEnglish, $isSpanish globalmente en Vue.
 *
 * Uso en componentes (sin import):
 *   {{ $t('cars.add') }}
 *   <span v-if="$isSpanish">Solo ES</span>
 *
 * Mantiene compatibilidad con useTranslations() composable (que sigue
 * funcionando para código existente).
 *
 * Se carga UNA vez al inicializar la app Vue (app.js), no necesita
 * importarse en cada componente.
 */

import { ref, computed, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import es from './es.js';
import en from './en.js';

// Estado compartido singleton. Se inicializa una vez al cargar el plugin.
// Vive fuera de useTranslations() para que ambos sistemas (composable y plugin)
// compartan la misma fuente de verdad.
const localeModules = { en, es };
const messages = ref({});
const ready = ref(false);
const currentLocale = ref('es');

function normalize(input) {
    if (!input) return {};
    const firstKey = Object.keys(input)[0];
    if (!firstKey) return input;
    if (!firstKey.includes('.')) return input;
    const tree = {};
    for (const flatKey in input) {
        const parts = flatKey.split('.');
        let cur = tree;
        for (let i = 0; i < parts.length - 1; i++) {
            cur[parts[i]] = cur[parts[i]] || {};
            cur = cur[parts[i]];
        }
        cur[parts[parts.length - 1]] = input[flatKey];
    }
    return tree;
}

/**
 * Carga inicial del locale. Llamado desde el install del plugin.
 * Usa props.translations si el backend las envía, sino módulo local.
 */
function loadInitial() {
    // Pre-cargar con módulo local 'es' por defecto (cubre el primer render).
    // Cuando se llama usePage() más tarde, hacemos sync con el backend.
    const fallback = localeModules.es;
    messages.value = normalize(fallback, 'es');
    currentLocale.value = 'es';
    ready.value = true;

    // Intentar sync con backend inmediatamente.
    syncFromBackend();
}

/**
 * Sincroniza con el backend usando usePage().
 * Llamado en install + cuando cambia page.props.locale.
 */
function syncFromBackend() {
    let page = null;
    try {
        page = usePage();
    } catch (e) {
        // usePage no disponible todavía; dejaremos el fallback local.
        return;
    }
    if (!page?.props) return;

    const fromServer = page.props.translations;
    const loc = page.props.locale || 'es';

    let result;
    if (fromServer && Object.keys(fromServer).length > 0) {
        result = fromServer;
    } else {
        const mod = localeModules[loc] || localeModules.es;
        result = mod;
    }

    messages.value = normalize(result, loc);
    currentLocale.value = loc;
}

/**
 * Función t() global — reutilizable en plugin y composable.
 * Misma lógica que el composable para mantener consistencia.
 */
function tFn(key, replacements = {}, fallback = null) {
    if (!ready.value) {
        return typeof fallback === 'string' ? fallback : key;
    }

    const keys = key.split('.');
    let value = messages.value;

    for (const k of keys) {
        if (value && typeof value === 'object' && k in value) {
            value = value[k];
        } else {
            return typeof fallback === 'string' ? fallback : key;
        }
    }

    if (Array.isArray(value)) return value;

    if (typeof value === 'object' && value !== null) {
        if (replacements.count !== undefined) {
            const n = replacements.count;
            const isPlural = n !== 1;
            if (isPlural && value._other) value = value._other;
            else if (!isPlural && value._one) value = value._one;
            else if (value._other) value = value._other;
            else return typeof fallback === 'string' ? fallback : key;
        } else {
            // Objeto sin plural keys: conservar el objeto entero (e.g. cars.fuel_options).
            if (value._other || value._one) {
                value = value._other || value._one;
            } else {
                return value;
            }
            if (typeof value !== 'string') {
                return typeof fallback === 'string' ? fallback : key;
            }
        }
    }

    if (typeof value !== 'string') {
        return typeof fallback === 'string' ? fallback : key;
    }

    if (value.includes('|') && replacements.count !== undefined) {
        const parts = value.split('|').map(s => s.trim());
        const n = replacements.count;
        let idx;
        if (n === 0) idx = 0;
        else if (n === 1) idx = 1;
        else idx = Math.min(2, parts.length - 1);
        value = parts[idx] || parts[0];
    }

    return value.replace(/:(\w+)/g, (m, name) =>
        name in replacements ? replacements[name] : m
    );
}

/**
 * Plugin Vue que expone $t, $locale, $isEnglish, $isSpanish como
 * propiedades globales. Se instala UNA vez en app.js.
 */
export const i18nPlugin = {
    install(app, options = {}) {
        // Cargar locale inicial antes de montar (fallback local 'es').
        loadInitial();

        // Sincronizar con backend usando usePage() ahora que Inertia ya cargó.
        syncFromBackend();

        // Watcher reactivo para cambios de locale (login/logout, switcher).
        // Usamos nextTick para asegurar que usePage() esté disponible.
        nextTick(() => {
            try {
                const page = usePage();
                watch(() => page.props.locale, () => syncFromBackend());
                watch(() => page.props.translations, () => syncFromBackend());
            } catch (e) {
                // usePage no disponible; sin watch reactivo.
            }
        });

        app.config.globalProperties.$t = tFn;
        app.config.globalProperties.$locale = computed(() => currentLocale.value);
        app.config.globalProperties.$isEnglish = computed(() => currentLocale.value === 'en');
        app.config.globalProperties.$isSpanish = computed(() => currentLocale.value === 'es');
    },
};

// Exportar t() por si algún módulo no-Vue lo necesita.
export { tFn as t };
export { messages, currentLocale };
