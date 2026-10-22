<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowRightIcon, ChatBubbleLeftEllipsisIcon, ClipboardDocumentCheckIcon, RocketLaunchIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    car: { type: Object, required: true },
    order: { type: Object, default: null },
    linkedRequest: { type: Object, default: null },
});

const { t } = useTranslations();
const { currency } = useFormat();

const form = useForm({ car_id: props.car.id, car_request_id: props.linkedRequest?.id ?? null });
const create = () => form.post(route('import-orders.store'), { preserveScroll: true });

const steps = computed(() => [t('orders.car_card.step_contract'), t('orders.car_card.step_deposit'), t('orders.car_card.step_tracking')]);
</script>

<template>
    <div>
        <!-- Con encargo -->
        <div v-if="order" class="flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-estoril-200 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-estoril-600">{{ t('orders.car_card.order') }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <span class="font-mono text-sm font-bold text-estoril-800">{{ order.reference }}</span>
                    <Badge :variant="order.state_variant" size="sm">{{ order.state_label }}</Badge>
                    <span class="text-xs text-gray-500">{{ t('orders.car_card.phase', { phase: order.phase_label }) }} · {{ currency(order.fee_amount) }}</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-600">
                    <span v-if="order.unread_messages" class="inline-flex items-center gap-1 font-semibold text-rose-700">
                        <ChatBubbleLeftEllipsisIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.car_card.unread', { count: order.unread_messages }) }}
                    </span>
                    <span v-if="order.pending_approvals" class="inline-flex items-center gap-1 font-semibold text-amber-700">
                        <ClipboardDocumentCheckIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.car_card.approvals', { count: order.pending_approvals }) }}
                    </span>
                    <span>{{ t('orders.car_card.views', { count: order.portal_views }) }}</span>
                </div>
            </div>
            <Link :href="order.url" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-600">
                {{ t('orders.car_card.manage') }}
                <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
            </Link>
        </div>

        <!-- Sin encargo -->
        <div v-else class="flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-estoril-200 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <RocketLaunchIcon class="mt-0.5 h-6 w-6 shrink-0 text-estoril-600" aria-hidden="true" />
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ t('orders.car_card.create_title') }}</p>
                    <p class="mt-0.5 text-xs text-gray-600">{{ t('orders.car_card.create_help') }}</p>
                    <ol class="mt-2 flex flex-wrap gap-2 text-[11px] text-gray-500">
                        <li v-for="(step, i) in steps" :key="i" class="rounded-full bg-gray-100 px-2 py-0.5">{{ i + 1 }}. {{ step }}</li>
                    </ol>
                </div>
            </div>
            <button type="button" :disabled="form.processing" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-600 disabled:opacity-50" @click="create">
                {{ form.processing ? t('orders.car_card.creating') : t('orders.car_card.create') }}
            </button>
        </div>
    </div>
</template>
