<template>
    <span :class="['inline-flex items-center gap-1 rounded-full font-semibold ring-1 ring-inset', sizeClass, classes]">
        <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotClass]"></span>
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'gray' },
    dot: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

// Aliases → canonical names
const variantAlias = {
    success: 'green',
    warning: 'amber',
    danger: 'red',
    neutral: 'gray',
};
const resolvedVariant = computed(() => variantAlias[props.variant] || props.variant);

const variantMap = {
    gray: 'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-asphalt-800 dark:text-gray-200 dark:ring-asphalt-700',
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
    amber: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
    red: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:ring-rose-800',
    blue: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800',
    indigo: 'bg-estoril-50 text-estoril-700 ring-estoril-200 dark:bg-estoril-900/30 dark:text-estoril-300 dark:ring-estoril-800',
    purple: 'bg-estoril-50 text-estoril-700 ring-estoril-200 dark:bg-estoril-900/30 dark:text-estoril-300 dark:ring-estoril-800',
    estoril: 'bg-estoril-50 text-estoril-700 ring-estoril-200 dark:bg-estoril-900/30 dark:text-estoril-300 dark:ring-estoril-800',
    sky: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800',
};

const dotColorMap = {
    gray: 'bg-gray-500 dark:bg-gray-400',
    green: 'bg-emerald-500 dark:bg-emerald-400',
    amber: 'bg-amber-500 dark:bg-amber-400',
    red: 'bg-rose-500 dark:bg-rose-400',
    blue: 'bg-blue-500 dark:bg-blue-400',
    indigo: 'bg-estoril-500 dark:bg-estoril-400',
    purple: 'bg-estoril-500 dark:bg-estoril-400',
    estoril: 'bg-estoril-500 dark:bg-estoril-400',
    sky: 'bg-sky-500 dark:bg-sky-400',
};

const sizeMap = {
    sm: 'px-2 py-0.5 text-[10px]',
    md: 'px-2.5 py-0.5 text-xs',
    lg: 'px-3 py-1 text-sm',
};

const classes = computed(() => variantMap[resolvedVariant.value] || variantMap.gray);
const dotClass = computed(() => dotColorMap[resolvedVariant.value] || dotColorMap.gray);
const sizeClass = computed(() => sizeMap[props.size] || sizeMap.md);
</script>
