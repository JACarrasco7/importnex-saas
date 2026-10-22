<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowPathIcon, ArrowTopRightOnSquareIcon, ClipboardDocumentIcon, LinkIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    order: { type: Object, required: true },
    whatsapp: { type: Object, required: true },
});

const { t } = useTranslations();
const { fromNow } = useFormat();
const copied = ref(false);

const logShare = (channel, what = 'portal') => {
    router.post(route('import-orders.share-log', props.order.id), { channel, what }, { preserveScroll: true, preserveState: true });
};

const copy = async () => {
    if (typeof window === 'undefined' || !navigator.clipboard || !props.order.portal.url) return;
    await navigator.clipboard.writeText(props.order.portal.url);
    copied.value = true;
    logShare('copy');
};

const rotate = () => {
    if (!confirm(t('orders.portal.confirm_rotate'))) return;
    router.post(route('import-orders.portal.rotate', props.order.id), {}, { preserveScroll: true });
};
</script>

<template>
    <OrderCard :title="t('orders.portal.title')" :icon="LinkIcon">
        <p class="text-xs text-gray-600 dark:text-gray-400">{{ t('orders.portal.help') }}</p>
        <div class="mt-3 flex gap-2">
            <input :value="order.portal.url" readonly :aria-label="t('orders.portal.title')" class="block w-full min-w-0 rounded-lg border-gray-300 bg-gray-50 text-xs text-gray-700 dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-300" @focus="$event.target.select()" />
            <button type="button" class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-estoril-700 px-3 text-xs font-semibold text-white hover:bg-estoril-600" @click="copy">
                <ClipboardDocumentIcon class="h-4 w-4" aria-hidden="true" />{{ copied ? t('orders.portal.copied') : t('orders.portal.copy') }}
            </button>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <a :href="whatsapp.portal" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500" @click="logShare('whatsapp')">
                {{ t('orders.portal.send_whatsapp') }}
            </a>
            <a :href="order.portal.url" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-1 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-asphalt-900 dark:text-gray-200 dark:ring-asphalt-600">
                <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.portal.open') }}
            </a>
        </div>
        <p v-if="!whatsapp.has_phone" class="mt-2 text-[11px] text-amber-700">{{ t('orders.portal.no_phone') }}</p>
        <div class="mt-3 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
            <span>{{ t('orders.portal.views', { count: order.portal.views }) }}<template v-if="order.portal.last_seen_at"> · {{ t('orders.portal.last_seen', { when: fromNow(order.portal.last_seen_at) }) }}</template></span>
            <button type="button" class="inline-flex items-center gap-1 font-semibold text-gray-500 hover:text-rose-600" @click="rotate">
                <ArrowPathIcon class="h-3.5 w-3.5" aria-hidden="true" />{{ t('orders.portal.rotate') }}
            </button>
        </div>
    </OrderCard>
</template>
