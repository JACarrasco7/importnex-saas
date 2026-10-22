<script setup>
import { CheckIcon } from '@heroicons/vue/24/solid';

defineProps({ phases: { type: Array, required: true } });
</script>

<template>
    <ol class="grid grid-cols-6 gap-2">
        <li v-for="(phase, index) in phases" :key="phase.key" class="flex items-center gap-2">
            <span :class="[
                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2',
                phase.status === 'done' ? 'bg-estoril-700 text-white ring-estoril-700' : '',
                phase.status === 'current' ? 'bg-white text-estoril-700 ring-estoril-600 dark:bg-asphalt-900' : '',
                phase.status === 'upcoming' ? 'bg-gray-50 text-gray-400 ring-gray-200 dark:bg-asphalt-900 dark:ring-asphalt-700' : '',
            ]" :aria-current="phase.status === 'current' ? 'step' : undefined">
                <CheckIcon v-if="phase.status === 'done'" class="h-4 w-4" aria-hidden="true" />
                <template v-else>{{ index + 1 }}</template>
            </span>
            <span :class="['hidden truncate text-xs sm:inline', phase.status === 'upcoming' ? 'text-gray-400' : 'font-semibold text-gray-800 dark:text-gray-200']">{{ phase.label }}</span>
        </li>
    </ol>
</template>
