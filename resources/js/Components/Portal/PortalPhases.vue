<script setup>
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/24/solid';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    phases: { type: Array, required: true },
    cancelled: { type: Boolean, default: false },
});

const { t } = useTranslations();

const current = computed(() => props.phases.find((p) => p.status === 'current') || props.phases[props.phases.length - 1]);
const doneCount = computed(() => props.phases.filter((p) => p.status === 'done').length);
</script>

<template>
    <div>
        <div v-if="cancelled" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200">
            {{ t('portal.phases.cancelled') }}
        </div>
        <template v-else>
            <ol class="grid grid-cols-6 gap-1.5" :aria-label="t('portal.phases.aria')">
                <li v-for="phase in phases" :key="phase.key" class="flex flex-col items-center gap-1.5 text-center">
                    <span
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ring-2 transition',
                            phase.status === 'done' ? 'bg-estoril-700 text-white ring-estoril-700' : '',
                            phase.status === 'current' ? 'bg-white text-estoril-700 ring-estoril-600 shadow-[0_0_0_4px_rgba(42,61,135,0.12)]' : '',
                            phase.status === 'upcoming' ? 'bg-asphalt-50 text-asphalt-400 ring-asphalt-200' : '',
                        ]"
                        :aria-current="phase.status === 'current' ? 'step' : undefined"
                    >
                        <CheckIcon v-if="phase.status === 'done'" class="h-4 w-4" aria-hidden="true" />
                        <template v-else>{{ phases.indexOf(phase) + 1 }}</template>
                    </span>
                    <span :class="['text-[11px] leading-tight sm:text-xs', phase.status === 'upcoming' ? 'text-asphalt-400' : 'font-semibold text-asphalt-800']">
                        {{ phase.label }}
                    </span>
                </li>
            </ol>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-asphalt-100" role="progressbar" :aria-valuenow="doneCount" aria-valuemin="0" :aria-valuemax="phases.length">
                <div class="h-full rounded-full bg-estoril-600 transition-all" :style="{ width: `${Math.max(4, (doneCount / phases.length) * 100)}%` }" />
            </div>
            <p v-if="current" class="mt-3 text-sm text-asphalt-600">
                <span class="font-semibold text-asphalt-900">{{ current.label }}:</span> {{ current.description }}
            </p>
        </template>
    </div>
</template>
