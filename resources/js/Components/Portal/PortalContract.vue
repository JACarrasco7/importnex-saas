<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, CheckCircleIcon, DocumentTextIcon, EnvelopeIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import PortalSection from '@/Components/Portal/PortalSection.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    token: { type: String, required: true },
    contract: { type: Object, required: true },
    maskedEmail: { type: String, default: null },
});

const { t } = useTranslations();
const { currency, datetime, date } = useFormat();

const showText = ref(props.contract.is_signable);
const codeSent = ref(false);
const showDoubts = ref(false);

const signForm = useForm({
    name: props.contract.prefill_name || '',
    dni: '',
    code: '',
    accept: false,
});
const codeForm = useForm({});
const doubtsForm = useForm({ reason: '' });

const statusMessage = computed(() => ({
    rejected: t('portal.contract.rejected_msg'),
    expired: t('portal.contract.expired_msg'),
    voided: t('portal.contract.expired_msg'),
}[props.contract.status] || null));

const sendCode = () => {
    codeForm.post(route('portal.contract.code', props.token), {
        preserveScroll: true,
        onSuccess: () => { codeSent.value = true; },
    });
};

const sign = () => {
    signForm.post(route('portal.contract.sign', props.token), { preserveScroll: true });
};

const sendDoubts = () => {
    doubtsForm.post(route('portal.contract.reject', props.token), {
        preserveScroll: true,
        onSuccess: () => { showDoubts.value = false; doubtsForm.reset(); },
    });
};

const inputClass = 'block w-full rounded-xl border-asphalt-200 px-4 py-3 text-base shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <PortalSection id="contrato" :title="t('portal.contract.title')" :subtitle="contract.is_signable ? t('portal.contract.subtitle_pending') : null" :icon="DocumentTextIcon">
        <!-- Firmado -->
        <div v-if="contract.status === 'signed'" class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
            <p class="flex items-center gap-2 text-sm font-semibold text-emerald-800">
                <CheckCircleIcon class="h-5 w-5" aria-hidden="true" />
                {{ t('portal.contract.signed_on', { date: datetime(contract.signed_at) }) }}
            </p>
            <p class="mt-1 break-all text-xs text-emerald-700">{{ t('portal.contract.hash') }} {{ contract.hash }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a :href="contract.pdf_url" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-emerald-800 shadow-sm ring-1 ring-emerald-200 hover:bg-emerald-100">
                    <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                    {{ t('portal.contract.download') }}
                </a>
                <button type="button" class="text-sm font-semibold text-emerald-800 underline-offset-2 hover:underline" @click="showText = !showText">
                    {{ showText ? t('portal.contract.hide_text') : t('portal.contract.show_text') }}
                </button>
            </div>
        </div>

        <!-- No firmable (rechazado / caducado / anulado) -->
        <p v-else-if="!contract.is_signable" class="rounded-xl bg-asphalt-50 p-4 text-sm text-asphalt-700 ring-1 ring-asphalt-200">
            {{ statusMessage }}
        </p>

        <!-- Resumen en tarjetas -->
        <dl v-if="contract.is_signable" class="grid grid-cols-2 gap-3">
            <div class="col-span-2 rounded-xl bg-asphalt-50 p-3">
                <dt class="text-xs text-asphalt-500">{{ t('portal.contract.summary_vehicle') }}</dt>
                <dd class="text-sm font-semibold text-asphalt-900">{{ contract.summary.vehicle }}</dd>
            </div>
            <div class="rounded-xl bg-asphalt-50 p-3">
                <dt class="text-xs text-asphalt-500">{{ t('portal.contract.summary_fee') }}</dt>
                <dd class="text-base font-bold text-asphalt-900">{{ currency(contract.summary.fee) }}</dd>
            </div>
            <div class="rounded-xl bg-estoril-50 p-3">
                <dt class="text-xs text-estoril-700">{{ t('portal.contract.summary_deposit') }}</dt>
                <dd class="text-base font-bold text-estoril-800">{{ currency(contract.summary.deposit) }}</dd>
            </div>
            <div v-if="contract.summary.purchase_price" class="col-span-2 rounded-xl bg-asphalt-50 p-3">
                <dt class="text-xs text-asphalt-500">{{ t('portal.contract.summary_purchase') }}</dt>
                <dd class="text-sm font-semibold text-asphalt-900">{{ contract.summary.purchase_price }} €</dd>
            </div>
        </dl>
        <p v-if="contract.is_signable && contract.expires_at" class="mt-3 text-xs text-asphalt-500">
            {{ t('portal.contract.expires', { date: date(contract.expires_at) }) }}
        </p>

        <!-- Texto completo -->
        <div class="mt-4">
            <button v-if="contract.status !== 'signed'" type="button" class="text-sm font-semibold text-estoril-700 underline-offset-2 hover:underline" :aria-expanded="showText" @click="showText = !showText">
                {{ showText ? t('portal.contract.hide_text') : t('portal.contract.show_text_count', { n: contract.clauses_count }) }}
            </button>
            <!-- HTML generado y escapado en servidor (ContractDocument::toHtml) -->
            <div v-if="showText" class="mt-3 max-h-[28rem] space-y-2 overflow-y-auto rounded-xl border border-asphalt-100 bg-white p-4" v-html="contract.html" />
        </div>

        <!-- Firma -->
        <form v-if="contract.is_signable" class="mt-6 space-y-4 border-t border-asphalt-100 pt-5" @submit.prevent="sign">
            <h3 class="text-sm font-bold text-asphalt-900">{{ t('portal.contract.sign_title') }}</h3>
            <div>
                <label for="sign-name" class="mb-1 block text-sm font-medium text-asphalt-700">{{ t('portal.contract.name') }}</label>
                <input id="sign-name" v-model="signForm.name" type="text" autocomplete="name" required :class="inputClass" />
                <p v-if="signForm.errors.name" class="mt-1 text-sm text-rose-600">{{ signForm.errors.name }}</p>
            </div>
            <div>
                <label for="sign-dni" class="mb-1 block text-sm font-medium text-asphalt-700">{{ t('portal.contract.dni') }}</label>
                <input id="sign-dni" v-model="signForm.dni" type="text" autocomplete="off" required :placeholder="contract.masked_dni || '12345678Z'" :class="[inputClass, 'uppercase']" />
                <p class="mt-1 text-xs text-asphalt-500">{{ t('portal.contract.dni_help') }}</p>
                <p v-if="signForm.errors.dni" class="mt-1 text-sm text-rose-600">{{ signForm.errors.dni }}</p>
            </div>
            <label class="flex items-start gap-3 rounded-xl bg-asphalt-50 p-3">
                <input v-model="signForm.accept" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-asphalt-300 text-estoril-600 focus:ring-estoril-500" />
                <span class="text-sm text-asphalt-700">{{ t('portal.contract.accept', { n: contract.clauses_count }) }}</span>
            </label>
            <p v-if="signForm.errors.accept" class="text-sm text-rose-600">{{ signForm.errors.accept }}</p>

            <div class="rounded-xl bg-estoril-50 p-4 ring-1 ring-estoril-100">
                <p class="flex items-center gap-2 text-sm font-semibold text-estoril-900">
                    <ShieldCheckIcon class="h-5 w-5" aria-hidden="true" />
                    {{ t('portal.contract.code_title') }}
                </p>
                <p class="mt-1 text-sm text-estoril-800">{{ t('portal.contract.code_help', { email: maskedEmail || '—' }) }}</p>
                <button type="button" :disabled="codeForm.processing" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-estoril-800 shadow-sm ring-1 ring-estoril-200 hover:bg-estoril-100 disabled:opacity-50" @click="sendCode">
                    <EnvelopeIcon class="h-4 w-4" aria-hidden="true" />
                    {{ codeSent ? t('portal.contract.resend_code') : t('portal.contract.send_code') }}
                </button>
                <div v-if="codeSent || signForm.code" class="mt-3">
                    <label for="sign-code" class="mb-1 block text-sm font-medium text-estoril-900">{{ t('portal.contract.code') }}</label>
                    <input id="sign-code" v-model="signForm.code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" :class="[inputClass, 'text-center font-mono text-2xl tracking-[0.5em]']" />
                </div>
                <p v-if="signForm.errors.code || codeForm.errors.code" class="mt-2 text-sm text-rose-600">{{ signForm.errors.code || codeForm.errors.code }}</p>
                <p v-if="codeForm.errors.general" class="mt-2 text-sm text-rose-600">{{ codeForm.errors.general }}</p>
            </div>

            <p v-if="signForm.errors.general" class="text-sm text-rose-600">{{ signForm.errors.general }}</p>
            <button type="submit" :disabled="signForm.processing || !signForm.accept || signForm.code.length < 6" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-estoril-700 px-6 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-estoril-600 focus:outline-none focus:ring-2 focus:ring-estoril-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                {{ signForm.processing ? t('portal.contract.signing') : t('portal.contract.sign') }}
            </button>
            <p class="text-center text-xs text-asphalt-500">{{ contract.legal_notice }}</p>

            <div class="border-t border-asphalt-100 pt-4 text-center">
                <button type="button" class="text-sm font-semibold text-asphalt-600 underline-offset-2 hover:underline" @click="showDoubts = !showDoubts">
                    {{ t('portal.contract.doubts') }}
                </button>
            </div>
        </form>

        <form v-if="contract.is_signable && showDoubts" class="mt-3 space-y-3" @submit.prevent="sendDoubts">
            <label for="doubts" class="block text-sm font-medium text-asphalt-700">{{ t('portal.contract.doubts_label') }}</label>
            <textarea id="doubts" v-model="doubtsForm.reason" rows="3" required :class="inputClass" />
            <p v-if="doubtsForm.errors.reason || doubtsForm.errors.general" class="text-sm text-rose-600">{{ doubtsForm.errors.reason || doubtsForm.errors.general }}</p>
            <button type="submit" :disabled="doubtsForm.processing" class="inline-flex items-center rounded-lg bg-asphalt-700 px-4 py-2 text-sm font-semibold text-white hover:bg-asphalt-600 disabled:opacity-50">
                {{ t('portal.contract.doubts_send') }}
            </button>
        </form>
    </PortalSection>
</template>
