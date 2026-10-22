<script setup>
import { router } from '@inertiajs/vue3';
import { CheckCircleIcon, FlagIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

defineProps({ milestones: { type: Array, required: true } });

const { t } = useTranslations();
const { date } = useFormat();

const toggle = (milestone) => {
    if (!milestone.toggle_url) return;
    router.post(milestone.toggle_url, {}, { preserveScroll: true });
};
</script>

<template>
    <OrderCard :title="t('orders.milestones.title')" :icon="FlagIcon">
        <p class="mb-3 text-xs text-gray-600 dark:text-gray-400">{{ t('orders.milestones.help') }}</p>
        <ul class="space-y-1">
            <li v-for="m in milestones" :key="m.key">
                <button type="button" :disabled="!m.toggle_url" class="flex w-full items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-left text-sm hover:bg-gray-50 disabled:cursor-not-allowed dark:hover:bg-asphalt-700/50" @click="toggle(m)">
                    <span :class="['flex items-center gap-2', m.completed ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500']">
                        <CheckCircleIcon :class="['h-5 w-5', m.completed ? 'text-emerald-600' : 'text-gray-300']" aria-hidden="true" />
                        {{ m.name }}
                    </span>
                    <span class="text-xs text-gray-500">{{ m.completed_at ? date(m.completed_at) : t('orders.milestones.mark') }}</span>
                </button>
            </li>
        </ul>
    </OrderCard>
</template>
