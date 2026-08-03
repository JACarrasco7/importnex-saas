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
    gray: 'bg-gray-50 text-gray-700 ring-gray-200',
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    amber: 'bg-amber-50 text-amber-700 ring-amber-200',
    red: 'bg-rose-50 text-rose-700 ring-rose-200',
    blue: 'bg-blue-50 text-blue-700 ring-blue-200',
    indigo: 'bg-estoril-50 text-estoril-700 ring-estoril-200',
    purple: 'bg-estoril-50 text-estoril-700 ring-estoril-200',
    estoril: 'bg-estoril-50 text-estoril-700 ring-estoril-200',
    sky: 'bg-sky-50 text-sky-700 ring-sky-200',
};

const dotColorMap = {
    gray: 'bg-gray-500',
    green: 'bg-emerald-500',
    amber: 'bg-amber-500',
    red: 'bg-rose-500',
    blue: 'bg-blue-500',
    indigo: 'bg-estoril-500',
    purple: 'bg-estoril-500',
    estoril: 'bg-estoril-500',
    sky: 'bg-sky-500',
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
