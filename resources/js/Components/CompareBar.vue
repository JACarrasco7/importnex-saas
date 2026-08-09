<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { XMarkIcon, ScaleIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { useWishlist } from '@/Composables/useWishlist';

const wishlist = useWishlist();
const isOpen = ref(false);

const items = computed(() => wishlist.items.value);
const count = computed(() => items.value.length);

const remove = (id) => {
    wishlist.remove(id);
};

const clear = () => {
    wishlist.clear();
    isOpen.value = false;
};

const goCompare = () => {
    if (count.value < 2) return;
    const ids = items.value.map(i => i.id).join(',');
    router.visit(`/marketplace/compare?ids=${ids}`);
};

const features = [
    { key: 'brand', label: 'Marca' },
    { key: 'model', label: 'Modelo' },
    { key: 'year', label: 'Año' },
    { key: 'price', label: 'Precio' },
    { key: 'km', label: 'Kilometraje' },
    { key: 'fuel', label: 'Combustible' },
    { key: 'transmission', label: 'Cambio' },
    { key: 'power', label: 'Potencia' },
    { key: 'color', label: 'Color' },
    { key: 'doors', label: 'Puertas' },
    { key: 'verdict', label: 'Veredicto' },
    { key: 'observations_count', label: 'Puntos de inspección' },
];

defineExpose({ features, items });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div v-if="count > 0" class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 shadow-2xl backdrop-blur dark:border-asphalt-700 dark:bg-asphalt-900/95">
                <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <ScaleIcon class="h-5 w-5 text-estoril-600" />
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ count }} {{ count === 1 ? 'coche' : 'coches' }} en comparador
                            </span>
                        </div>

                        <div class="hidden items-center gap-2 md:flex">
                            <div
                                v-for="item in items.slice(0, 4)"
                                :key="item.id"
                                class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-1.5 dark:bg-asphalt-800"
                            >
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ item.brand }} {{ item.model }}</span>
                                <button
                                    @click="remove(item.id)"
                                    class="rounded-full p-0.5 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:text-gray-400 dark:hover:bg-asphalt-700 dark:hover:text-gray-300"
                                    :aria-label="`Quitar ${item.brand} ${item.model}`"
                                >
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="clear"
                                class="rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-asphalt-800"
                            >
                                Limpiar
                            </button>
                            <button
                                @click="goCompare"
                                :disabled="count < 2"
                                :class="[
                                    'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition',
                                    count >= 2
                                        ? 'bg-estoril-600 text-white hover:bg-estoril-500'
                                        : 'cursor-not-allowed bg-gray-200 text-gray-400',
                                ]"
                            >
                                Comparar
                                <ArrowRightIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
