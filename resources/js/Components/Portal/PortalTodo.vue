<script setup>
import { computed } from 'vue';
import { ArrowRightIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    contract: { type: Object, default: null },
    payments: { type: Array, default: () => [] },
    approvals: { type: Array, default: () => [] },
    closed: { type: Boolean, default: false },
    state: { type: String, default: '' },
});

const { t } = useTranslations();
const { currency } = useFormat();

const items = computed(() => {
    const list = [];
    if (props.contract?.is_signable) {
        list.push({ key: 'sign', label: t('portal.todo.sign'), target: '#contrato' });
    }
    props.approvals.filter((a) => a.is_pending).forEach((a) => {
        list.push({ key: `approval-${a.id}`, label: t('portal.todo.approval', { title: a.title }), target: '#aprobaciones' });
    });
    const signed = props.contract?.status === 'signed';
    props.payments.filter((p) => p.status === 'pending' && signed && (p.due_date || props.state === 'ready_for_delivery')).slice(0, 1).forEach((p) => {
        list.push({ key: `pay-${p.id}`, label: t('portal.todo.pay', { amount: currency(p.amount), concept: p.concept }), target: '#pagos' });
    });
    return list;
});
</script>

<template>
    <div v-if="!closed" :class="['rounded-2xl p-5 ring-1', items.length ? 'bg-amber-50 ring-amber-200' : 'bg-emerald-50 ring-emerald-200']">
        <template v-if="items.length">
            <h2 class="text-sm font-bold uppercase tracking-wide text-amber-900">{{ t('portal.todo.title', { count: items.length }) }}</h2>
            <ul class="mt-3 space-y-2">
                <li v-for="item in items" :key="item.key">
                    <a :href="item.target" class="flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-asphalt-900 shadow-sm ring-1 ring-amber-200 transition hover:ring-amber-400 focus:outline-none focus:ring-2 focus:ring-estoril-500">
                        <span>{{ item.label }}</span>
                        <ArrowRightIcon class="h-4 w-4 shrink-0 text-amber-700" aria-hidden="true" />
                    </a>
                </li>
            </ul>
        </template>
        <p v-else class="flex items-center gap-2 text-sm font-semibold text-emerald-800">
            <CheckBadgeIcon class="h-5 w-5" aria-hidden="true" />
            {{ t('portal.todo.nothing') }}
        </p>
    </div>
</template>
