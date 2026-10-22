import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Web pública de la empresa en contexto (prop `marketplace`, que envían
 * PublicMarketplaceController y el formulario de solicitud). Cada empresa
 * tiene su web en /web/{slug}; la operadora conserva /marketplace.
 * Nunca enlazar a route('marketplace.*') desde las páginas públicas.
 */
export function useSite() {
    const page = usePage();
    const site = computed(() => page.props.marketplace ?? {});

    const homeUrl = computed(() => site.value.homeUrl || '/marketplace');
    const legalUrl = computed(() => site.value.legalUrl || '/legal');
    const carUrl = (id) => `${site.value.carUrlBase || '/marketplace'}/${id}`;
    const compareUrl = (ids = []) => {
        const base = site.value.compareUrl || '/marketplace/compare';
        const list = Array.isArray(ids) ? ids.join(',') : String(ids);
        return list ? `${base}?ids=${list}` : base;
    };

    return { site, homeUrl, legalUrl, carUrl, compareUrl };
}
