<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { BanknotesIcon, PlusIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    order: { type: Object, required: true },
    payments: { type: Array, required: true },
    methods: { type: Array, required: true },
    whatsapp: { type: Object, required: true },
});

const { t } = useTranslations();
const { currency, date } = useFormat();

const payingId = ref(null);
const editingId = ref(null);
const showNew = ref(false);

const paidForm = useForm({ method: 'transfer', reference: '', paid_at: '' });
const editForm = useForm({ stripe_payment_link: '', amount: '', due_date: '' });
const newForm = useForm({ concept: '', amount: '', due_date: '', stripe_payment_link: '' });

const startPaying = (payment) => {
    payingId.value = payment.id;
    editingId.value = null;
    paidForm.reset();
};
const markPaid = (payment) => {
    paidForm.post(route('import-orders.payments.paid', [props.order.id, payment.id]), {
        preserveScroll: true,
        onSuccess: () => { payingId.value = null; },
    });
};
const startEditing = (payment) => {
    editingId.value = payment.id;
    payingId.value = null;
    editForm.stripe_payment_link = payment.stripe_payment_link || '';
    editForm.amount = payment.amount;
    editForm.due_date = payment.due_date || '';
};
const saveEdit = (payment) => {
    editForm.patch(route('import-orders.payments.update', [props.order.id, payment.id]), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
};
const simple = (payment, action, question) => {
    if (question && !confirm(question)) return;
    router.post(route(`import-orders.payments.${action}`, [props.order.id, payment.id]), {}, { preserveScroll: true });
};
const createPayment = () => {
    newForm.post(route('import-orders.payments.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showNew.value = false; newForm.reset(); },
    });
};

const variant = (status) => ({ paid: 'success', pending: 'warning', cancelled: 'gray' }[status] || 'gray');
const input = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-100';
</script>

<template>
    <OrderCard id="pagos" :title="t('orders.payments.title')" :icon="BanknotesIcon">
        <template #actions>
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-estoril-700 hover:text-estoril-600 dark:text-estoril-300" @click="showNew = !showNew">
                <PlusIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.payments.add') }}
            </button>
        </template>

        <p v-if="!payments.length" class="text-sm text-gray-600 dark:text-gray-400">{{ t('orders.payments.none') }}</p>

        <ul class="divide-y divide-gray-100 dark:divide-asphalt-700">
            <li v-for="payment in payments" :key="payment.id" class="py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p :class="['text-sm font-semibold', payment.status === 'cancelled' ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-gray-100']">{{ payment.concept }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <template v-if="payment.status === 'paid'">{{ t('orders.payments.paid_info', { date: date(payment.paid_at), method: payment.method_label, by: payment.recorded_by || '—' }) }}<template v-if="payment.reference"> · {{ payment.reference }}</template></template>
                            <template v-else-if="payment.due_date">{{ t('orders.payments.due', { date: date(payment.due_date) }) }}</template>
                        </p>
                        <p v-if="payment.stripe_payment_link && payment.status === 'pending'" class="truncate text-xs text-estoril-700 dark:text-estoril-300">{{ t('orders.payments.stripe_ready') }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ currency(payment.amount) }}</p>
                        <Badge :variant="variant(payment.status)" size="sm">{{ payment.status_label }}</Badge>
                    </div>
                </div>

                <div v-if="payment.status === 'pending'" class="mt-2 flex flex-wrap gap-2 text-xs">
                    <button type="button" class="rounded-lg bg-emerald-600 px-3 py-1.5 font-semibold text-white hover:bg-emerald-500" @click="startPaying(payment)">{{ t('orders.payments.mark_paid') }}</button>
                    <button type="button" class="rounded-lg bg-white px-3 py-1.5 font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-asphalt-900 dark:text-gray-200 dark:ring-asphalt-600" @click="startEditing(payment)">{{ t('orders.payments.edit') }}</button>
                    <a v-if="whatsapp.payment" :href="whatsapp.payment" target="_blank" rel="noopener" class="rounded-lg px-3 py-1.5 font-semibold text-emerald-700 hover:bg-emerald-50">{{ t('orders.payments.whatsapp') }}</a>
                    <button type="button" class="rounded-lg px-3 py-1.5 font-semibold text-rose-600 hover:bg-rose-50" @click="simple(payment, 'cancel', t('orders.payments.confirm_cancel'))">{{ t('orders.payments.cancel') }}</button>
                </div>
                <div v-else-if="payment.status === 'paid'" class="mt-2 text-xs">
                    <button type="button" class="font-semibold text-gray-500 hover:text-gray-700" @click="simple(payment, 'pending', t('orders.payments.confirm_undo'))">{{ t('orders.payments.undo') }}</button>
                </div>

                <form v-if="payingId === payment.id" class="mt-3 grid grid-cols-1 gap-2 rounded-xl bg-gray-50 p-3 sm:grid-cols-3 dark:bg-asphalt-900" @submit.prevent="markPaid(payment)">
                    <select v-model="paidForm.method" :aria-label="t('orders.payments.method')" :class="input">
                        <option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                    <input v-model="paidForm.reference" type="text" :placeholder="t('orders.payments.reference')" :aria-label="t('orders.payments.reference')" :class="input" />
                    <input v-model="paidForm.paid_at" type="date" :aria-label="t('orders.payments.paid_at')" :class="input" />
                    <p v-for="(error, key) in paidForm.errors" :key="key" class="text-xs text-rose-600 sm:col-span-3">{{ error }}</p>
                    <div class="flex gap-2 sm:col-span-3">
                        <button type="submit" :disabled="paidForm.processing" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">{{ t('orders.payments.confirm_paid') }}</button>
                        <button type="button" class="text-xs font-semibold text-gray-500" @click="payingId = null">{{ t('common.cancel') }}</button>
                    </div>
                </form>

                <form v-if="editingId === payment.id" class="mt-3 grid grid-cols-1 gap-2 rounded-xl bg-gray-50 p-3 sm:grid-cols-2 dark:bg-asphalt-900" @submit.prevent="saveEdit(payment)">
                    <input v-model="editForm.stripe_payment_link" type="url" placeholder="https://buy.stripe.com/…" :aria-label="t('orders.payments.stripe_link')" :class="[input, 'sm:col-span-2']" />
                    <input v-model="editForm.amount" type="number" step="0.01" min="0.01" :aria-label="t('orders.payments.amount')" :class="input" />
                    <input v-model="editForm.due_date" type="date" :aria-label="t('orders.payments.due_date')" :class="input" />
                    <p class="text-[11px] text-gray-500 sm:col-span-2">{{ t('orders.payments.stripe_help') }}</p>
                    <p v-for="(error, key) in editForm.errors" :key="key" class="text-xs text-rose-600 sm:col-span-2">{{ error }}</p>
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" :disabled="editForm.processing" class="rounded-lg bg-estoril-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50">{{ t('common.save') }}</button>
                        <button type="button" class="text-xs font-semibold text-gray-500" @click="editingId = null">{{ t('common.cancel') }}</button>
                    </div>
                </form>
            </li>
        </ul>

        <form v-if="showNew" class="mt-3 grid grid-cols-1 gap-2 rounded-xl bg-gray-50 p-3 sm:grid-cols-2 dark:bg-asphalt-900" @submit.prevent="createPayment">
            <input v-model="newForm.concept" type="text" required :placeholder="t('orders.payments.concept')" :aria-label="t('orders.payments.concept')" :class="[input, 'sm:col-span-2']" />
            <input v-model="newForm.amount" type="number" step="0.01" min="0.01" required :placeholder="t('orders.payments.amount')" :aria-label="t('orders.payments.amount')" :class="input" />
            <input v-model="newForm.due_date" type="date" :aria-label="t('orders.payments.due_date')" :class="input" />
            <input v-model="newForm.stripe_payment_link" type="url" :placeholder="t('orders.payments.stripe_optional')" :aria-label="t('orders.payments.stripe_link')" :class="[input, 'sm:col-span-2']" />
            <p v-for="(error, key) in newForm.errors" :key="key" class="text-xs text-rose-600 sm:col-span-2">{{ error }}</p>
            <div class="flex gap-2 sm:col-span-2">
                <button type="submit" :disabled="newForm.processing" class="rounded-lg bg-estoril-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50">{{ t('orders.payments.create') }}</button>
                <button type="button" class="text-xs font-semibold text-gray-500" @click="showNew = false">{{ t('common.cancel') }}</button>
            </div>
        </form>
    </OrderCard>
</template>
