import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Marca de la organización en contexto (prop compartido `brand`, que las
 * páginas de una organización concreta sobrescriben). Nunca escribir el
 * nombre, teléfono o email de una empresa en los componentes.
 */
export function useBrand() {
    const page = usePage();
    const brand = computed(() => page.props.orgBrand || {});
    const appName = computed(() => page.props.appName || brand.value.name || '');

    const digits = (value) => String(value || '').replace(/\D/g, '');
    const waNumber = computed(() => {
        const d = digits(brand.value.whatsapp || brand.value.phone);
        if (!d) return '';
        return d.length === 9 ? `34${d}` : d;
    });

    const whatsappUrl = (text = '') => {
        const base = waNumber.value ? `https://wa.me/${waNumber.value}` : 'https://wa.me/';
        return text ? `${base}?text=${encodeURIComponent(text)}` : base;
    };
    const telUrl = computed(() => {
        const d = digits(brand.value.phone);
        if (!d) return null;
        return `tel:+${d.length === 9 ? `34${d}` : d}`;
    });
    const mailUrl = computed(() => (brand.value.email ? `mailto:${brand.value.email}` : null));

    return { brand, appName, waNumber, whatsappUrl, telUrl, mailUrl };
}
