<template>
    <div :class="['relative overflow-hidden rounded-2xl shadow-sm ring-1 ring-gray-200 bg-white p-6 transition hover:shadow-md', cardClass]">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500">{{ label }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
                    <slot name="value">{{ value }}</slot>
                </p>
                <p v-if="hint || $slots.hint" class="mt-1 text-xs text-gray-400">
                    <slot name="hint">{{ hint }}</slot>
                </p>
            </div>
            <div v-if="icon || $slots.icon" :class="['flex h-12 w-12 items-center justify-center rounded-xl', iconBgClass]">
                <slot name="icon">
                    <component :is="icon" class="h-6 w-6" :class="iconColorClass" aria-hidden="true" />
                </slot>
            </div>
        </div>
        <div v-if="trend !== null && trend !== undefined" class="mt-4 flex items-center text-xs">
            <span :class="['inline-flex items-center gap-1 font-semibold', trend > 0 ? 'text-emerald-600' : trend < 0 ? 'text-rose-600' : 'text-gray-500']">
                <svg v-if="trend > 0" class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <svg v-else-if="trend < 0" class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                {{ Math.abs(trend) }}%
            </span>
            <span class="ml-2 text-gray-400">vs last period</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
    hint: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    color: { type: String, default: 'indigo' },
    trend: { type: Number, default: null },
    cardClass: { type: String, default: '' },
});

const colorMap = {
    indigo: { bg: 'bg-indigo-50', text: 'text-indigo-600' },
    blue: { bg: 'bg-blue-50', text: 'text-blue-600' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-600' },
    amber: { bg: 'bg-amber-50', text: 'text-amber-600' },
    rose: { bg: 'bg-rose-50', text: 'text-rose-600' },
    purple: { bg: 'bg-purple-50', text: 'text-purple-600' },
    sky: { bg: 'bg-sky-50', text: 'text-sky-600' },
    gray: { bg: 'bg-gray-100', text: 'text-gray-600' },
};

const iconBgClass = computed(() => colorMap[props.color]?.bg || colorMap.indigo.bg);
const iconColorClass = computed(() => colorMap[props.color]?.text || colorMap.indigo.text);
</script>
