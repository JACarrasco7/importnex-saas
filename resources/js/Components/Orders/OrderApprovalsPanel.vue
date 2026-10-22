<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CheckBadgeIcon, PlusIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    order: { type: Object, required: true },
    approvals: { type: Array, required: true },
    kinds: { type: Array, required: true },
    otpThreshold: { type: Number, default: 300 },
    whatsapp: { type: Object, required: true },
});

const { t } = useTranslations();
const { currency, datetime } = useFormat();

const showForm = ref(false);
const form = useForm({ kind: 'extra_cost', title: '', description: '', amount: '', due_hours: 48, photos: [] });
const canAskDecision = computed(() => props.order.state === 'inspecting');

const open = (kind) => {
    form.reset();
    form.kind = kind;
    if (kind === 'unit_decision') {
        form.title = t('orders.approvals.default_decision_title');
    }
    showForm.value = true;
};
const onFiles = (event) => { form.photos = Array.from(event.target.files || []); };
const submit = () => {
    form.post(route('import-orders.approvals.store', props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
};
const withdraw = (approval) => {
    if (!confirm(t('orders.approvals.confirm_withdraw'))) return;
    router.post(route('import-orders.approvals.withdraw', [props.order.id, approval.id]), {}, { preserveScroll: true });
};

defineExpose({ open });

const variant = (status) => ({ pending: 'warning', approved: 'success', rejected: 'danger', expired: 'gray', withdrawn: 'gray' }[status] || 'gray');
const input = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-100';
</script>

<template>
    <OrderCard id="aprobaciones" :title="t('orders.approvals.title')" :icon="CheckBadgeIcon">
        <template #actions>
            <button v-if="!order.is_terminal" type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-estoril-700 hover:text-estoril-600 dark:text-estoril-300" @click="open('extra_cost')">
                <PlusIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.approvals.new_cost') }}
            </button>
            <button v-if="canAskDecision" type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-600" @click="open('unit_decision')">
                <PlusIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.approvals.new_decision') }}
            </button>
        </template>

        <form v-if="showForm" class="mb-4 space-y-2 rounded-xl bg-gray-50 p-3 dark:bg-asphalt-900" @submit.prevent="submit">
            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ form.kind === 'unit_decision' ? t('orders.approvals.decision_help') : t('orders.approvals.cost_help', { amount: currency(otpThreshold) }) }}</p>
            <input v-model="form.title" type="text" required :placeholder="t('orders.approvals.title_placeholder')" :aria-label="t('orders.approvals.field_title')" :class="input" />
            <textarea v-model="form.description" rows="3" :placeholder="t('orders.approvals.description_placeholder')" :aria-label="t('orders.approvals.field_description')" :class="input" />
            <div class="grid grid-cols-2 gap-2">
                <input v-if="form.kind === 'extra_cost'" v-model="form.amount" type="number" step="0.01" min="0" required :placeholder="t('orders.approvals.amount')" :aria-label="t('orders.approvals.amount')" :class="input" />
                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    {{ t('orders.approvals.due_hours') }}
                    <input v-model="form.due_hours" type="number" min="1" max="720" class="w-20 rounded-lg border-gray-300 text-sm dark:border-asphalt-600 dark:bg-asphalt-900" />
                </label>
            </div>
            <label class="block text-xs text-gray-600 dark:text-gray-400">
                {{ t('orders.approvals.photos') }}
                <input type="file" multiple accept="image/*,application/pdf" class="mt-1 block w-full text-xs" @change="onFiles" />
            </label>
            <p v-for="(error, key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</p>
            <div class="flex gap-2">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-estoril-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50">{{ t('orders.approvals.send') }}</button>
                <button type="button" class="text-xs font-semibold text-gray-500" @click="showForm = false">{{ t('common.cancel') }}</button>
            </div>
        </form>

        <p v-if="!approvals.length && !showForm" class="text-sm text-gray-600 dark:text-gray-400">{{ t('orders.approvals.none') }}</p>

        <ul class="space-y-3">
            <li v-for="approval in approvals" :key="approval.id" class="rounded-xl p-3 ring-1 ring-gray-200 dark:ring-asphalt-700">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ approval.kind_label }}</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ approval.title }}</p>
                    </div>
                    <Badge :variant="variant(approval.status)" size="sm">{{ approval.status_label }}</Badge>
                </div>
                <p v-if="approval.amount" class="mt-1 text-sm font-bold text-gray-900 dark:text-gray-100">{{ currency(approval.amount) }}</p>
                <p v-if="approval.description" class="mt-1 whitespace-pre-line text-xs text-gray-600 dark:text-gray-400">{{ approval.description }}</p>
                <div v-if="approval.media.length" class="mt-2 flex gap-2 overflow-x-auto">
                    <a v-for="m in approval.media" :key="m.id" :href="m.url" target="_blank" rel="noopener" class="block h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200">
                        <img v-if="m.is_image" :src="m.url" :alt="m.name" class="h-full w-full object-cover" loading="lazy" />
                    </a>
                </div>
                <p v-if="approval.decision_label || approval.client_comment" class="mt-2 text-xs text-gray-700 dark:text-gray-300">
                    <span v-if="approval.decision_label" class="font-semibold">{{ approval.decision_label }}</span>
                    <span v-if="approval.client_comment"> · «{{ approval.client_comment }}»</span>
                    <span v-if="approval.otp_verified" class="text-emerald-700"> · {{ t('orders.approvals.verified') }}</span>
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-gray-500">
                    <span v-if="approval.is_pending && approval.due_at">{{ t('orders.approvals.due', { date: datetime(approval.due_at) }) }}</span>
                    <span v-else-if="approval.resolved_at">{{ datetime(approval.resolved_at) }}</span>
                    <a v-if="approval.is_pending && whatsapp.reminder" :href="whatsapp.reminder" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:underline">{{ t('orders.approvals.whatsapp') }}</a>
                    <button v-if="approval.is_pending" type="button" class="font-semibold text-rose-600 hover:underline" @click="withdraw(approval)">{{ t('orders.approvals.withdraw') }}</button>
                </div>
            </li>
        </ul>
    </OrderCard>
</template>
