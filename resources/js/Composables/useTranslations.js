import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import en from '@/i18n/en.js';
import es from '@/i18n/es.js';

const messages = {
    en,
    es,
};
const cache = ref({});

/**
 * Carga diferida: si la traducción ya está en page.props (enviada por backend),
 * usa esa; si no, importa el módulo JS del idioma.
 */
async function loadLocale(locale) {
    const page = usePage();
    const fromServer = page.props.translations;

    if (fromServer && Object.keys(fromServer).length > 0) {
        return fromServer;
    }

    if (cache.value[locale]) {
        return cache.value[locale];
    }

    const mod = messages[locale] || messages.en;
    cache.value[locale] = mod;
    return mod;
}

/**
 * useTranslations()
 *
 * Si la página Inertia trae `props.translations` (enviadas desde el backend
 * mediante HandleInertiaRequests), las usa directamente sin descarga adicional.
 *
 * Si NO están (cliente sin backend configurado, tests, etc), hace fallback
 * a los módulos JS locales en resources/js/i18n/{locale}.js
 */
export function useTranslations() {
    const page = usePage();
    const messages = ref({});
    const ready = ref(false);

    const locale = computed(() => page.props.locale || 'es');
    const isEnglish = computed(() => locale.value === 'en');
    const isSpanish = computed(() => locale.value === 'es');

    async function init() {
        const loc = locale.value;
        const result = await loadLocale(loc);

        // Convierte dict plano (backend) o módulo anidado (frontend) en árbol
        messages.value = normalize(result, loc);
        ready.value = true;
    }

    if (page.props.translations && Object.keys(page.props.translations).length > 0) {
        messages.value = normalize(page.props.translations, locale.value);
        ready.value = true;
    } else {
        init();
    }

    watch(locale, () => init());

    /**
     * t('nav.dashboard', { count: 5 }, 'Dashboard')
     * Soporta dot-notation y placeholders :name.
     * Si la clave no existe y fallback es un string, lo devuelve.
     */
    function t(key, replacements = {}, fallback = null) {
        if (!ready.value && !messages.value) {
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

        if (typeof value !== 'string') {
            return typeof fallback === 'string' ? fallback : key;
        }

        return value.replace(/:(\w+)/g, (m, name) =>
            name in replacements ? replacements[name] : m
        );
    }

    return { t, locale, isEnglish, isSpanish, ready };
}

/**
 * Normaliza entre los dos formatos:
 * Backend plano: { 'nav.dashboard': 'Dashboard' }
 * Frontend árbol: { nav: { dashboard: 'Dashboard' } }
 *
 * Devuelve siempre árbol para que t() recorra dot-notation uniformemente.
 */
function normalize(input, locale) {
    if (!input) return {};

    // Si ya es árbol (módulo JS), devolvemos tal cual
    const firstKey = Object.keys(input)[0];
    if (firstKey && firstKey.includes('.')) {
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

    // Si ya es árbol, devolver
    return input;
}
