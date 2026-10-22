<script setup>
import { computed, ref } from 'vue';
import { BanknotesIcon, ClipboardDocumentIcon, CreditCardIcon } from '@heroicons/vue/24/outline';
import PortalSection from '@/Components/Portal/PortalSection.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    payments: { type: Array, required: true },
    bank: { type: Object, required: true },
    contactWhatsapp: { type: String, default: null },
});

const { t } = useTranslations();
const { currency, date } = useFormat();

const copied = ref(null);
const pending = computed(() => props.payments.filter((p) => p.status === 'pending'));
const paidTotal = computed(() => props.payments.filter((p) => p.status === 'paid').reduce((sum, p) => sum + p.amount, 0));

const copy = async (value, key) => {
    if (typeof window === 'undefined' || !navigator.clipboard) return;
    try {
        await navigator.clipboard.writeText(value);
        copied.value = key;
    } catch (e) {
        copied.value = null;
    }
};
</script>

<template>
    <PortalSection id="pagos" :title="t('portal.payments.title')" :subtitle="t('portal.payments.subtitle', { paid: currency(paidTotal) })" :icon="BanknotesIcon">
        <ul class="divide-y divide-asphalt-100">
            <li v-for="payment in payments" :key="payment.id" class="flex items-start justify-between gap-3 py-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-asphalt-900">{{ payment.concept }}</p>
                    <p class="text-xs text-asphalt-500">
                        <template v-if="payment.status === 'paid'">{{ t('portal.payments.paid_on', { date: date(payment.paid_at) }) }}<template v-if="payment.method_label"> · {{ payment.method_label }}</template></template>
                        <template v-else-if="payment.due_date">{{ t('portal.payments.due', { date: date(payment.due_date) }) }}</template>
                        <template v-else>{{ t('portal.payments.on_delivery') }}</template>
                    </p>
                    <a v-if="payment.stripe_payment_link" :href="payment.stripe_payment_link" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-3 py-2 text-sm font-semibold text-white hover:bg-estoril-600">
                        <CreditCardIcon class="h-4 w-4" aria-hidden="true" />
                        {{ t('portal.payments.pay_card') }}
                    </a>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-bold text-asphalt-900">{{ currency(payment.amount) }}</p>
                    <span :class="['mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold', payment.status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800']">
                        {{ payment.status_label }}
                    </span>
                </div>
            </li>
        </ul>

        <div v-if="pending.length" class="mt-4 rounded-xl bg-asphalt-50 p-4 ring-1 ring-asphalt-100">
            <h3 class="text-sm font-bold text-asphalt-900">{{ t('portal.payments.transfer_title') }}</h3>
            <template v-if="bank.iban">
                <dl class="mt-2 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <dt class="text-xs text-asphalt-500">IBAN</dt>
                            <dd class="break-all font-mono font-semibold text-asphalt-900">{{ bank.iban }}</dd>
                        </div>
                        <button type="button" class="shrink-0 rounded-lg p-2 text-asphalt-500 hover:bg-white hover:text-estoril-700" :aria-label="t('portal.payments.copy')" @click="copy(bank.iban, 'iban')">
                            <ClipboardDocumentIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                    <div>
                        <dt class="text-xs text-asphalt-500">{{ t('portal.payments.holder') }}</dt>
                        <dd class="font-semibold text-asphalt-900">{{ bank.holder }}<template v-if="bank.bank"> · {{ bank.bank }}</template></dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <dt class="text-xs text-asphalt-500">{{ t('portal.payments.concept') }}</dt>
                            <dd class="font-semibold text-asphalt-900">{{ bank.concept }}</dd>
                        </div>
                        <button type="button" class="shrink-0 rounded-lg p-2 text-asphalt-500 hover:bg-white hover:text-estoril-700" :aria-label="t('portal.payments.copy')" @click="copy(bank.concept, 'concept')">
                            <ClipboardDocumentIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </dl>
                <p v-if="copied" class="mt-2 text-xs font-semibold text-emerald-700" aria-live="polite">{{ t('portal.payments.copied') }}</p>
                <p class="mt-3 text-xs text-asphalt-500">{{ t('portal.payments.transfer_note') }}</p>
            </template>
            <p v-else class="mt-2 text-sm text-asphalt-600">
                {{ t('portal.payments.no_iban') }}
                <a v-if="contactWhatsapp" :href="contactWhatsapp" target="_blank" rel="noopener" class="font-semibold text-estoril-700 hover:underline">{{ t('portal.contact.whatsapp') }}</a>
            </p>
        </div>
    </PortalSection>
</template>
