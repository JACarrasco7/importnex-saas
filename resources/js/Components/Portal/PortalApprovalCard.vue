<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    token: { type: String, required: true },
    approval: { type: Object, required: true },
});

const { t } = useTranslations();
const { currency, datetime } = useFormat();

const codeSent = ref(false);
const form = useForm({ approved: null, decision: '', comment: '', code: '' });
const codeForm = useForm({});

const isDecision = computed(() => props.approval.kind === 'unit_decision');
const choosingApprove = computed(() => (isDecision.value ? !!form.decision : form.approved === true));
const needsCode = computed(() => props.approval.requires_otp && choosingApprove.value);
const canSubmit = computed(() => {
    if (isDecision.value && !form.decision) return false;
    if (!isDecision.value && form.approved === null) return false;
    if (needsCode.value && form.code.length < 6) return false;
    return !form.processing;
});

const decisions = computed(() => [
    { value: 'continue', label: t('portal.approvals.decision_continue'), help: t('portal.approvals.decision_continue_help') },
    { value: 'replace', label: t('portal.approvals.decision_replace'), help: t('portal.approvals.decision_replace_help') },
    { value: 'cancel', label: t('portal.approvals.decision_cancel'), help: t('portal.approvals.decision_cancel_help') },
]);

const sendCode = () => {
    codeForm.post(route('portal.approvals.code', { token: props.token, approval: props.approval.id }), {
        preserveScroll: true,
        onSuccess: () => { codeSent.value = true; },
    });
};

const submit = () => {
    form.post(route('portal.approvals.resolve', { token: props.token, approval: props.approval.id }), { preserveScroll: true });
};

const inputClass = 'block w-full rounded-xl border-asphalt-200 px-4 py-3 text-base shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <article :class="['rounded-xl p-4 ring-1', approval.is_pending ? 'bg-amber-50/60 ring-amber-200' : 'bg-asphalt-50 ring-asphalt-100']">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-sm font-bold text-asphalt-900">{{ approval.title }}</h3>
            <span :class="['shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold', approval.is_pending ? 'bg-amber-100 text-amber-800' : 'bg-white text-asphalt-600 ring-1 ring-asphalt-200']">
                {{ approval.is_pending ? t('portal.approvals.pending') : approval.status_label }}
            </span>
        </div>
        <p v-if="approval.description" class="mt-2 whitespace-pre-line text-sm text-asphalt-700">{{ approval.description }}</p>
        <p v-if="approval.amount" class="mt-2 text-lg font-bold text-asphalt-900">{{ currency(approval.amount) }}</p>

        <div v-if="approval.media.length" class="mt-3 grid grid-cols-3 gap-2">
            <a v-for="item in approval.media" :key="item.id" :href="item.url" target="_blank" rel="noopener" class="group block overflow-hidden rounded-lg bg-asphalt-100 ring-1 ring-asphalt-200">
                <img v-if="item.is_image" :src="item.url" :alt="item.caption || item.name" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105" />
                <span v-else class="flex aspect-square items-center justify-center p-2 text-center text-xs text-asphalt-600">{{ item.name }}</span>
            </a>
        </div>

        <p v-if="!approval.is_pending && (approval.decision_label || approval.client_comment)" class="mt-3 text-sm text-asphalt-600">
            <span v-if="approval.decision_label" class="font-semibold">{{ approval.decision_label }}.</span>
            <span v-if="approval.client_comment"> «{{ approval.client_comment }}»</span>
        </p>

        <form v-if="approval.is_pending" class="mt-4 space-y-3" @submit.prevent="submit">
            <p v-if="approval.due_at" class="text-xs text-amber-800">{{ t('portal.approvals.due', { date: datetime(approval.due_at) }) }}</p>

            <fieldset v-if="isDecision" class="space-y-2">
                <legend class="sr-only">{{ t('portal.approvals.choose') }}</legend>
                <label v-for="option in decisions" :key="option.value" :class="['flex cursor-pointer items-start gap-3 rounded-xl bg-white p-3 ring-1 transition', form.decision === option.value ? 'ring-2 ring-estoril-600' : 'ring-asphalt-200 hover:ring-asphalt-300']">
                    <input v-model="form.decision" type="radio" :value="option.value" class="mt-1 h-4 w-4 border-asphalt-300 text-estoril-600 focus:ring-estoril-500" />
                    <span>
                        <span class="block text-sm font-semibold text-asphalt-900">{{ option.label }}</span>
                        <span class="block text-xs text-asphalt-500">{{ option.help }}</span>
                    </span>
                </label>
            </fieldset>

            <div v-else class="grid grid-cols-2 gap-2">
                <button type="button" :class="['rounded-xl px-4 py-3 text-sm font-semibold ring-1 transition', form.approved === true ? 'bg-estoril-700 text-white ring-estoril-700' : 'bg-white text-asphalt-800 ring-asphalt-200 hover:ring-asphalt-300']" @click="form.approved = true">
                    {{ t('portal.approvals.approve') }}
                </button>
                <button type="button" :class="['rounded-xl px-4 py-3 text-sm font-semibold ring-1 transition', form.approved === false ? 'bg-asphalt-700 text-white ring-asphalt-700' : 'bg-white text-asphalt-800 ring-asphalt-200 hover:ring-asphalt-300']" @click="form.approved = false">
                    {{ t('portal.approvals.reject') }}
                </button>
            </div>

            <div>
                <label :for="`approval-comment-${approval.id}`" class="mb-1 block text-sm font-medium text-asphalt-700">
                    {{ form.decision === 'cancel' ? t('portal.approvals.comment_required') : t('portal.approvals.comment') }}
                </label>
                <textarea :id="`approval-comment-${approval.id}`" v-model="form.comment" rows="2" :class="inputClass" />
            </div>

            <div v-if="needsCode" class="rounded-xl bg-white p-3 ring-1 ring-estoril-100">
                <p class="text-sm text-estoril-900">{{ t('portal.approvals.code_help') }}</p>
                <button type="button" :disabled="codeForm.processing" class="mt-2 rounded-lg bg-estoril-50 px-3 py-2 text-sm font-semibold text-estoril-800 ring-1 ring-estoril-200 hover:bg-estoril-100 disabled:opacity-50" @click="sendCode">
                    {{ codeSent ? t('portal.contract.resend_code') : t('portal.contract.send_code') }}
                </button>
                <input v-if="codeSent || form.code" v-model="form.code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" :aria-label="t('portal.contract.code')" :class="[inputClass, 'mt-2 text-center font-mono text-xl tracking-[0.5em]']" />
            </div>

            <p v-for="(error, key) in { ...form.errors, ...codeForm.errors }" :key="key" class="text-sm text-rose-600">{{ error }}</p>

            <button type="submit" :disabled="!canSubmit" class="inline-flex w-full items-center justify-center rounded-xl bg-estoril-700 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-estoril-600 disabled:cursor-not-allowed disabled:opacity-50">
                {{ form.processing ? t('portal.approvals.sending') : t('portal.approvals.send') }}
            </button>
        </form>
    </article>
</template>
