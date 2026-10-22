<script setup>
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, DocumentCheckIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    order: { type: Object, required: true },
    contracts: { type: Array, required: true },
    whatsapp: { type: Object, required: true },
});

const { t } = useTranslations();
const { datetime, date } = useFormat();

const latest = computed(() => props.contracts[0] || null);
const history = computed(() => props.contracts.slice(1));
const canIssue = computed(() => ['draft', 'awaiting_signature', 'contract_rejected'].includes(props.order.state));
const issueLabel = computed(() => (latest.value ? t('orders.contract.reissue') : t('orders.contract.issue')));
const variant = (status) => ({ signed: 'success', sent: 'blue', viewed: 'blue', rejected: 'danger', expired: 'warning', voided: 'gray' }[status] || 'gray');

const issueForm = useForm({});
const issue = () => {
    if (latest.value?.is_signable && !confirm(t('orders.contract.confirm_reissue'))) return;
    issueForm.post(route('import-orders.contracts.issue', props.order.id), { preserveScroll: true });
};

const voidContract = () => {
    const reason = prompt(t('orders.contract.void_prompt'));
    if (!reason) return;
    router.post(route('import-orders.contracts.void', props.order.id), { reason }, { preserveScroll: true });
};

const logShare = () => router.post(route('import-orders.share-log', props.order.id), { channel: 'whatsapp', what: 'contract' }, { preserveScroll: true, preserveState: true });
</script>

<template>
    <OrderCard id="contrato" :title="t('orders.contract.title')" :icon="DocumentCheckIcon">
        <template #actions>
            <Badge v-if="latest" :variant="variant(latest.status)" size="sm">{{ latest.status_label }}</Badge>
        </template>

        <p v-if="!latest" class="text-sm text-gray-600 dark:text-gray-400">{{ t('orders.contract.none') }}</p>

        <div v-else class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
            <p>{{ t('orders.contract.version', { version: latest.version, date: datetime(latest.created_at) }) }}</p>
            <p v-if="latest.is_signable && latest.expires_at" class="text-xs text-gray-500">{{ t('orders.contract.expires', { date: date(latest.expires_at) }) }} · {{ t('orders.contract.reminders', { count: latest.reminders_sent }) }}</p>
            <p v-if="latest.viewed_at && !latest.signed_at" class="text-xs text-gray-500">{{ t('orders.contract.viewed', { date: datetime(latest.viewed_at) }) }}</p>
            <div v-if="latest.signed_at" class="rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800">
                <p class="font-semibold">{{ t('orders.contract.signed', { date: datetime(latest.signed_at), ip: latest.signed_ip || '—' }) }}</p>
                <p v-if="latest.otp_sent_to">{{ t('orders.contract.otp', { email: latest.otp_sent_to }) }}</p>
                <p class="break-all">SHA-256 {{ latest.hash }}</p>
                <p v-if="latest.signer_corrections" class="mt-1 text-amber-700">{{ t('orders.contract.corrections') }}</p>
            </div>
            <p v-if="latest.rejection_reason" class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800 ring-1 ring-rose-200">{{ t('orders.contract.rejected_reason') }} «{{ latest.rejection_reason }}»</p>
            <p v-if="latest.void_reason" class="text-xs text-gray-500">{{ t('orders.contract.void_reason') }} {{ latest.void_reason }}</p>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button v-if="canIssue" type="button" :disabled="issueForm.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-3 py-2 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50" @click="issue">
                <PaperAirplaneIcon class="h-4 w-4" aria-hidden="true" />{{ issueLabel }}
            </button>
            <a v-if="latest?.is_signable && whatsapp.contract" :href="whatsapp.contract" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500" @click="logShare">
                {{ t('orders.contract.whatsapp') }}
            </a>
            <a v-if="latest?.download_url" :href="latest.download_url" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-asphalt-900 dark:text-gray-200 dark:ring-asphalt-600">
                <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.contract.download') }}
            </a>
            <button v-if="latest?.is_signable" type="button" class="rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50" @click="voidContract">
                {{ t('orders.contract.void') }}
            </button>
        </div>
        <p v-if="issueForm.errors.email || issueForm.errors.general" class="mt-2 text-xs text-rose-600">{{ issueForm.errors.email || issueForm.errors.general }}</p>

        <details v-if="history.length" class="mt-4 text-xs text-gray-500">
            <summary class="cursor-pointer font-semibold">{{ t('orders.contract.history', { count: history.length }) }}</summary>
            <ul class="mt-2 space-y-1">
                <li v-for="c in history" :key="c.id">#{{ c.id }} · {{ c.status_label }} · {{ datetime(c.created_at) }}<template v-if="c.void_reason"> · {{ c.void_reason }}</template></li>
            </ul>
        </details>
    </OrderCard>
</template>
