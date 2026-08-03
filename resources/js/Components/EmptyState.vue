<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
    actionText: {
        type: String,
        default: null,
    },
    actionRoute: {
        type: String,
        default: null,
    },
    actionMethod: {
        type: String,
        default: 'get',
    },
});
</script>

<template>
    <div class="text-center py-12">
        <div v-if="icon" class="mb-4 inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full" v-html="icon"></div>

        <h3 class="text-lg font-medium text-gray-900 mb-1">{{ title }}</h3>
        <p v-if="description" class="text-sm text-gray-500 mb-4">{{ description }}</p>

        <Link
            v-if="actionText && actionRoute"
            :href="actionRoute"
            :method="actionMethod"
            as="button"
            class="px-4 py-2 bg-estoril-600 text-white text-sm rounded-md hover:bg-estoril-700"
        >
            {{ actionText }}
        </Link>
    </div>
</template>
