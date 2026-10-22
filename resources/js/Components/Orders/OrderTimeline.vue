<script setup>
import { ref, computed } from 'vue';
import { ClockIcon, EyeIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({ events: { type: Array, required: true } });

const { t } = useTranslations();
const { datetime } = useFormat();
const expanded = ref(false);
const visible = computed(() => (expanded.value ? props.events : props.events.slice(0, 12)));
</script>

<template>
    <OrderCard :title="t('orders.timeline.title')" :icon="ClockIcon">
        <ol class="relative space-y-3 border-l border-gray-200 pl-4 dark:border-asphalt-700">
            <li v-for="event in visible" :key="event.id" class="relative">
                <span :class="['absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-asphalt-800', event.type === 'notification_sent' ? 'bg-gray-300' : event.actor_type === 'client' ? 'bg-amber-500' : 'bg-estoril-500']" />
                <p :class="event.type === 'notification_sent' ? 'text-xs text-gray-500' : 'text-sm text-gray-900 dark:text-gray-100'">
                    {{ event.title }}
                    <EyeIcon v-if="event.visible_to_client" class="ml-1 inline h-3.5 w-3.5 text-emerald-600" :title="t('orders.timeline.visible')" aria-hidden="true" />
                </p>
                <p v-if="event.body" class="whitespace-pre-line text-xs text-gray-600 dark:text-gray-400">{{ event.body }}</p>
                <p class="text-[11px] text-gray-500">{{ event.actor }} · {{ datetime(event.created_at) }}</p>
            </li>
        </ol>
        <button v-if="events.length > 12" type="button" class="mt-3 text-xs font-semibold text-estoril-700 hover:underline dark:text-estoril-300" @click="expanded = !expanded">
            {{ expanded ? t('orders.timeline.less') : t('orders.timeline.more', { count: events.length - 12 }) }}
        </button>
        <p class="mt-3 flex items-center gap-1 text-[11px] text-gray-500"><EyeIcon class="h-3.5 w-3.5 text-emerald-600" aria-hidden="true" />{{ t('orders.timeline.visible') }}</p>
    </OrderCard>
</template>
