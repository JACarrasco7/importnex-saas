import { ref, shallowRef } from 'vue';

/**
 * Lazy load heroicons por nombre.
 *
 * Reduce bundle size cargando solo los iconos que se necesitan en runtime.
 * Heroicons se carga en chunks: 'TruckIcon' -> /build/vendor-heroicons-XXXX.js
 *
 * Ejemplo:
 *   const { icon: TruckIcon } = useIcon('TruckIcon', 'outline');
 */
const cache = new Map();

export function useIcon(name, variant = 'outline') {
    const icon = shallowRef(null);
    const loading = ref(false);

    const load = async () => {
        if (icon.value) return;
        const cacheKey = `${variant}:${name}`;
        if (cache.has(cacheKey)) {
            icon.value = cache.get(cacheKey);
            return;
        }

        loading.value = true;
        try {
            const module = await import(`@heroicons/vue/24/${variant}/${name}.js`);
            const Icon = module.default;
            icon.value = Icon;
            cache.set(cacheKey, Icon);
        } catch (e) {
            // Fallback silencioso
            icon.value = null;
        } finally {
            loading.value = false;
        }
    };

    // Auto-load
    load();

    return { icon, loading };
}

/**
 * Versión que pre-carga múltiples iconos en un solo chunk.
 *
 * Ejemplo:
 *   const icons = useIcons(['TruckIcon', 'UsersIcon']);
 */
export function useIcons(names = [], variant = 'outline') {
    const icons = ref({});
    const loading = ref(true);

    const load = async () => {
        const promises = names.map(async (name) => {
            const cacheKey = `${variant}:${name}`;
            if (cache.has(cacheKey)) {
                icons.value[name] = cache.get(cacheKey);
                return;
            }
            try {
                const module = await import(`@heroicons/vue/24/${variant}/${name}.js`);
                const Icon = module.default;
                icons.value[name] = Icon;
                cache.set(cacheKey, Icon);
            } catch (e) {
                icons.value[name] = null;
            }
        });

        await Promise.all(promises);
        loading.value = false;
    };

    load();

    return { icons, loading };
}
