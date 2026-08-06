<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
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
    primaryAction: {
        type: Object,
        default: null,
    },
    secondaryAction: {
        type: Object,
        default: null,
    },
    actionText: { type: String, default: null },
    actionRoute: { type: String, default: null },
    actionMethod: { type: String, default: 'get' },
});

const primary = computed(() => {
    if (props.primaryAction) return props.primaryAction;
    if (props.actionText && props.actionRoute) {
        return { text: props.actionText, route: props.actionRoute, method: props.actionMethod };
    }
    return null;
});
</script>

<template>
    <div class="py-12 text-center">
        <div
            v-if="icon"
            class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-estoril-100 text-3xl dark:bg-estoril-900/40"
            v-html="icon"
        ></div>

        <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <p v-if="description" class="mx-auto mb-6 max-w-md text-sm text-gray-500 dark:text-gray-400">
            {{ description }}
        </p>

        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link
                v-if="primary"
                :href="primary.route"
                :method="primary.method || 'get'"
                as="button"
                class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500"
            >
                <span v-if="primary.icon" v-html="primary.icon" />
                {{ primary.text }}
            </Link>

            <Link
                v-if="secondaryAction"
                :href="secondaryAction.route"
                :method="secondaryAction.method || 'get'"
                as="button"
                class="inline-flex items-center gap-2 rounded-lg border border-estoril-200 bg-white px-5 py-2.5 text-sm font-semibold text-estoril-700 hover:bg-estoril-50 dark:border-estoril-700 dark:bg-asphalt-800 dark:text-estoril-300 dark:hover:bg-asphalt-700"
            >
                <span v-if="secondaryAction.icon" v-html="secondaryAction.icon" />
                {{ secondaryAction.text }}
            </Link>
        </div>

        <slot name="extra" />
    </div>
</template>