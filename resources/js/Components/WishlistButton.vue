<script setup>
import { HeartIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid } from '@heroicons/vue/24/solid';
import { useWishlist } from '@/Composables/useWishlist';

const props = defineProps({
    car: {
        type: Object,
        required: true,
    },
});

const wishlist = useWishlist();

const toggle = (e) => {
    e.preventDefault();
    e.stopPropagation();
    wishlist.toggle(props.car);
};
</script>

<template>
    <button
        @click="toggle"
        :class="[
            'inline-flex items-center justify-center rounded-full p-2 transition-all',
            wishlist.has(car.id)
                ? 'bg-rose-500 text-white hover:bg-rose-600'
                : 'bg-white/90 text-gray-700 hover:bg-rose-50 hover:text-rose-500',
        ]"
        :aria-label="wishlist.has(car.id) ? 'Quitar de favoritos' : 'Añadir a favoritos'"
        :title="wishlist.has(car.id) ? 'Quitar de favoritos' : 'Guardar en favoritos'"
    >
        <HeartIcon v-if="!wishlist.has(car.id)" class="h-5 w-5" />
        <HeartIconSolid v-else class="h-5 w-5" />
    </button>
</template>
