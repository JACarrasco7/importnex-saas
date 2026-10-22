<script setup>
import { useForm } from '@inertiajs/vue3';
import { AdjustmentsHorizontalIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    order: { type: Object, required: true },
    team: { type: Array, required: true },
});

const { t } = useTranslations();
const form = useForm({
    fee_amount: props.order.fee_amount,
    expected_delivery_date: props.order.expected_delivery_date || '',
    assigned_to: props.order.assigned_to || '',
    internal_notes: props.order.internal_notes || '',
});
const save = () => form.patch(route('import-orders.update', props.order.id), { preserveScroll: true });
const input = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 disabled:bg-gray-100 disabled:text-gray-500 dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-100';
</script>

<template>
    <OrderCard :title="t('orders.details.title')" :icon="AdjustmentsHorizontalIcon">
        <form class="space-y-3" @submit.prevent="save">
            <div>
                <label for="order-fee" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ t('orders.details.fee') }}</label>
                <input id="order-fee" v-model="form.fee_amount" type="number" step="0.01" min="0" :disabled="order.fee_locked" :class="input" />
                <p class="mt-1 text-[11px] text-gray-500">{{ order.fee_locked ? t('orders.details.fee_locked') : t('orders.details.fee_help') }}</p>
                <p v-if="form.errors.fee_amount" class="text-xs text-rose-600">{{ form.errors.fee_amount }}</p>
            </div>
            <div>
                <label for="order-eta" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ t('orders.details.eta') }}</label>
                <input id="order-eta" v-model="form.expected_delivery_date" type="date" :class="input" />
            </div>
            <div>
                <label for="order-assignee" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ t('orders.details.assignee') }}</label>
                <select id="order-assignee" v-model="form.assigned_to" :class="input">
                    <option value="">—</option>
                    <option v-for="member in team" :key="member.id" :value="member.id">{{ member.name }}</option>
                </select>
            </div>
            <div>
                <label for="order-notes" class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">{{ t('orders.details.notes') }}</label>
                <textarea id="order-notes" v-model="form.internal_notes" rows="3" :class="input" />
            </div>
            <button type="submit" :disabled="form.processing || !form.isDirty" class="rounded-lg bg-estoril-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50">{{ t('common.save') }}</button>
        </form>
    </OrderCard>
</template>
