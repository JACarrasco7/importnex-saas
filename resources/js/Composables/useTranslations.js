import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { t as tFn, messages, currentLocale } from '@/i18n';

/**
 * useTranslations() — composable retrocompatible.
 *
 * Usa el singleton global inicializado por el plugin i18n (resources/js/i18n/index.js)
 * instalado en app.js. NO re-inicializa nada — comparte estado con el plugin.
 *
 * Devuelve:
 *   t(key, replacements?, fallback?) — función de traducción
 *   locale — ComputedRef del locale actual
 *   isEnglish / isSpanish — ComputedRef helpers
 *   ready — ComputedRef (true si cargado)
 *
 * Uso retrocompatible (código viejo sigue funcionando):
 *   import { useTranslations } from '@/Composables/useTranslations';
 *   const { t } = useTranslations();
 *
 * Para código NUEVO, se recomienda usar el plugin global directamente:
 *   <template>{{ $t('cars.add') }}</template>
 */
export function useTranslations() {
    const page = usePage();

    watch(() => page.props.locale, (newLoc) => {
        if (newLoc && newLoc !== currentLocale.value) {
            // El plugin ya se encarga; aquí solo forzamos reactividad local.
            // No-op: el watch del plugin actualiza `messages` y `currentLocale`.
        }
    });

    const locale = computed(() => currentLocale.value);
    const isEnglish = computed(() => currentLocale.value === 'en');
    const isSpanish = computed(() => currentLocale.value === 'es');
    const ready = computed(() => messages.value && Object.keys(messages.value).length > 0);

    return { t: tFn, locale, isEnglish, isSpanish, ready };
}
