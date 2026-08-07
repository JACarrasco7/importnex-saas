import { ref, computed } from 'vue';

/**
 * Wishlist con localStorage (sin login).
 *
 * Permite a visitantes guardar coches favoritos sin necesidad de autenticación.
 * Los datos se persisten en localStorage con clave `importnex_wishlist`.
 *
 * Limitaciones:
 * - No se sincroniza entre dispositivos (por diseño, es local).
 * - Se pierden al limpiar el navegador.
 * - Si el usuario se loguea, los datos quedan aquí (no se migran a BD).
 */
const STORAGE_KEY = 'importnex_wishlist';

const wishlist = ref([]);
let initialized = false;

function load() {
    if (initialized) return;
    initialized = true;
    if (typeof window === 'undefined') return;
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed)) {
                wishlist.value = parsed;
            }
        }
    } catch (e) {
        // localStorage corrupto, empezamos vacíos
        wishlist.value = [];
    }
}

function save() {
    if (typeof window === 'undefined') return;
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(wishlist.value));
    } catch (e) {
        // Sin espacio o sin permisos, silencioso
    }
}

export function useWishlist() {
    load();

    const items = computed(() => wishlist.value);
    const count = computed(() => wishlist.value.length);
    const ids = computed(() => wishlist.value.map(item => item.id));

    const has = (carId) => {
        return wishlist.value.some(item => item.id === carId);
    };

    const add = (car) => {
        if (has(car.id)) return;
        wishlist.value.push({
            id: car.id,
            brand: car.brand,
            model: car.model,
            year: car.year,
            price: car.price,
            photo: car.photo || (car.photos?.[0] ?? null),
            verdict: car.verdict,
            added_at: Date.now(),
        });
        save();
    };

    const remove = (carId) => {
        const idx = wishlist.value.findIndex(item => item.id === carId);
        if (idx >= 0) {
            wishlist.value.splice(idx, 1);
            save();
        }
    };

    const toggle = (car) => {
        if (has(car.id)) {
            remove(car.id);
            return false;
        }
        add(car);
        return true;
    };

    const clear = () => {
        wishlist.value = [];
        save();
    };

    return {
        items,
        count,
        ids,
        has,
        add,
        remove,
        toggle,
        clear,
    };
}
