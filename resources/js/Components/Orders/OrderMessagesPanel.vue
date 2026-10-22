<script setup>
import { useForm } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    order: { type: Object, required: true },
    messages: { type: Array, required: true },
});

const { t } = useTranslations();
const { datetime } = useFormat();
const form = useForm({ body: '', internal: false });
const send = () => {
    form.post(route('import-orders.messages.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('body'),
    });
};
</script>

<template>
    <OrderCard id="mensajes" :title="t('orders.messages.title')" :icon="ChatBubbleLeftRightIcon">
        <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
            <p v-if="!messages.length" class="text-sm text-gray-600 dark:text-gray-400">{{ t('orders.messages.none') }}</p>
            <div v-for="m in messages" :key="m.id" :class="['flex', m.author_type === 'client' ? 'justify-start' : 'justify-end']">
                <div :class="['max-w-[90%] rounded-2xl px-3 py-2 text-sm', m.internal ? 'bg-amber-50 text-amber-900 ring-1 ring-amber-200' : m.author_type === 'client' ? 'bg-gray-100 text-gray-900 dark:bg-asphalt-700 dark:text-gray-100' : 'bg-estoril-700 text-white']">
                    <p class="text-[11px] font-semibold opacity-75">{{ m.author_name }}<template v-if="m.internal"> · {{ t('orders.messages.internal_badge') }}</template></p>
                    <p class="whitespace-pre-line">{{ m.body }}</p>
                    <p class="mt-0.5 text-[10px] opacity-70">{{ datetime(m.created_at) }}<template v-if="!m.internal && m.author_type === 'team' && m.read_by_client_at"> · {{ t('orders.messages.read') }}</template></p>
                </div>
            </div>
        </div>
        <form class="mt-3 space-y-2" @submit.prevent="send">
            <textarea v-model="form.body" rows="3" :placeholder="form.internal ? t('orders.messages.internal_placeholder') : t('orders.messages.placeholder')" :aria-label="t('orders.messages.placeholder')" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-100" />
            <p v-if="form.errors.body" class="text-xs text-rose-600">{{ form.errors.body }}</p>
            <div class="flex items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <input v-model="form.internal" type="checkbox" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
                    {{ t('orders.messages.internal') }}
                </label>
                <button type="submit" :disabled="form.processing || form.body.trim().length < 2" class="rounded-lg bg-estoril-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50">
                    {{ form.internal ? t('orders.messages.save_note') : t('orders.messages.send') }}
                </button>
            </div>
        </form>
    </OrderCard>
</template>
