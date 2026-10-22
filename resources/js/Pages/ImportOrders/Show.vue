<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, ExclamationTriangleIcon, LightBulbIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import OrderPhaseStepper from '@/Components/Orders/OrderPhaseStepper.vue';
import OrderContractPanel from '@/Components/Orders/OrderContractPanel.vue';
import OrderPaymentsPanel from '@/Components/Orders/OrderPaymentsPanel.vue';
import OrderApprovalsPanel from '@/Components/Orders/OrderApprovalsPanel.vue';
import OrderMediaPanel from '@/Components/Orders/OrderMediaPanel.vue';
import OrderMilestonesPanel from '@/Components/Orders/OrderMilestonesPanel.vue';
import OrderPortalPanel from '@/Components/Orders/OrderPortalPanel.vue';
import OrderMessagesPanel from '@/Components/Orders/OrderMessagesPanel.vue';
import OrderStatePanel from '@/Components/Orders/OrderStatePanel.vue';
import OrderDetailsPanel from '@/Components/Orders/OrderDetailsPanel.vue';
import OrderTimeline from '@/Components/Orders/OrderTimeline.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    order: { type: Object, required: true },
    phases: { type: Array, required: true },
    transitions: { type: Array, required: true },
    next_action: { type: Object, default: null },
    client: { type: Object, default: null },
    car: { type: Object, default: null },
    car_request: { type: Object, default: null },
    contracts: { type: Array, required: true },
    payments: { type: Array, required: true },
    approvals: { type: Array, required: true },
    messages: { type: Array, required: true },
    media: { type: Array, required: true },
    events: { type: Array, required: true },
    milestones: { type: Array, required: true },
    replacement_cars: { type: Array, default: () => [] },
    team: { type: Array, required: true },
    whatsapp: { type: Object, required: true },
    warnings: { type: Array, required: true },
    options: { type: Object, required: true },
});

const { t } = useTranslations();
const { currency, datetime } = useFormat();
const approvalsPanel = ref(null);

const issueForm = useForm({});
const purchaseForm = useForm({ to: 'purchasing' });

const actionTarget = computed(() => ({
    record_deposit: '#pagos',
    replace: null,
    wait_decision: '#aprobaciones',
}[props.next_action?.key] ?? null));

const issueContract = () => issueForm.post(route('import-orders.contracts.issue', props.order.id), { preserveScroll: true });
const goPurchase = () => {
    if (!confirm(t('orders.next.confirm_purchase'))) return;
    purchaseForm.post(route('import-orders.transition', props.order.id), { preserveScroll: true });
};
const askDecision = () => {
    approvalsPanel.value?.open('unit_decision');
    if (typeof window !== 'undefined') {
        window.location.hash = 'aprobaciones';
    }
};
</script>

<template>
    <Head :title="`${order.reference} · ${t('orders.show.title')}`" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <Link :href="route('import-orders.index')" class="inline-flex items-center gap-1 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-300">
                    <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />{{ t('orders.show.back') }}
                </Link>

                <!-- Cabecera -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="font-mono text-lg font-bold text-estoril-700 dark:text-estoril-300">{{ order.reference }}</h1>
                                <Badge :variant="order.state_variant">{{ order.state_label }}</Badge>
                            </div>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">
                                <Link v-if="client" :href="route('clients.show', client.id)" class="hover:underline">{{ client.name }}</Link>
                                <span class="text-gray-400"> · </span>
                                <Link v-if="car" :href="car.url" class="hover:underline">{{ car.label }}</Link>
                                <span v-else>{{ t('orders.show.no_car') }}</span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ t('orders.show.created', { date: datetime(order.created_at) }) }} · {{ t('orders.show.fee', { amount: currency(order.fee_amount) }) }}
                                <template v-if="client?.email"> · {{ client.email }}</template>
                                <template v-if="client?.phone"> · {{ client.phone }}</template>
                                <template v-if="car_request"> · <Link :href="car_request.url" class="hover:underline">{{ t('orders.show.request') }}</Link></template>
                            </p>
                        </div>
                    </div>
                    <div class="mt-5"><OrderPhaseStepper :phases="phases" /></div>
                </div>

                <!-- Avisos -->
                <div v-if="order.cancellation_requested_at && !order.is_terminal" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200" role="alert">
                    <p class="font-semibold">{{ t('orders.show.cancellation_requested', { date: datetime(order.cancellation_requested_at) }) }}</p>
                    <p class="mt-1">«{{ order.cancellation_request_reason }}»</p>
                </div>
                <ul v-if="warnings.length" class="space-y-2">
                    <li v-for="warning in warnings" :key="warning.key" class="flex items-start gap-2 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                        <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>{{ warning.message }}</span>
                        <Link v-if="warning.key.startsWith('client_') && client" :href="route('clients.edit', client.id)" class="ml-auto shrink-0 font-semibold underline">{{ t('orders.show.edit_client') }}</Link>
                    </li>
                </ul>

                <!-- Siguiente acción -->
                <div v-if="next_action" class="flex flex-col gap-3 rounded-2xl bg-estoril-700 p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <LightBulbIcon class="mt-0.5 h-6 w-6 shrink-0 text-platinum-400" aria-hidden="true" />
                        <div>
                            <p class="text-xs uppercase tracking-wide text-platinum-400">{{ t('orders.next.label') }}</p>
                            <p class="text-lg font-bold">{{ next_action.label }}</p>
                            <p class="text-sm text-estoril-100">{{ next_action.hint }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button v-if="next_action.key === 'issue_contract'" type="button" :disabled="issueForm.processing" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-estoril-800 hover:bg-estoril-50 disabled:opacity-50" @click="issueContract">
                            {{ t('orders.next.issue') }}
                        </button>
                        <a v-if="next_action.key === 'remind_signature' && whatsapp.reminder" :href="whatsapp.reminder" target="_blank" rel="noopener" class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-400">{{ t('orders.next.remind') }}</a>
                        <template v-if="next_action.key === 'inspection'">
                            <button type="button" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-estoril-800 hover:bg-estoril-50" @click="goPurchase">{{ t('orders.next.all_good') }}</button>
                            <button type="button" class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-asphalt-900 hover:bg-amber-300" @click="askDecision">{{ t('orders.next.ask_decision') }}</button>
                        </template>
                        <a v-if="actionTarget" :href="actionTarget" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-estoril-800 hover:bg-estoril-50">{{ t('orders.next.go') }}</a>
                    </div>
                    <p v-if="issueForm.errors.email || issueForm.errors.general || purchaseForm.errors.state" class="text-sm text-rose-200 sm:basis-full">{{ issueForm.errors.email || issueForm.errors.general || purchaseForm.errors.state }}</p>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="space-y-5 lg:col-span-2">
                        <OrderContractPanel :order="order" :contracts="contracts" :whatsapp="whatsapp" />
                        <OrderPaymentsPanel :order="order" :payments="payments" :methods="options.payment_methods" :whatsapp="whatsapp" />
                        <OrderApprovalsPanel ref="approvalsPanel" :order="order" :approvals="approvals" :kinds="options.approval_kinds" :otp-threshold="options.otp_amount_threshold" :whatsapp="whatsapp" />
                        <OrderMediaPanel :order="order" :media="media" :collections="options.media_collections" />
                        <OrderMilestonesPanel v-if="milestones.length" :milestones="milestones" />
                    </div>
                    <div class="space-y-5">
                        <OrderPortalPanel :order="order" :whatsapp="whatsapp" />
                        <OrderMessagesPanel :order="order" :messages="messages" />
                        <OrderStatePanel :order="order" :transitions="transitions" :replacement-cars="replacement_cars" />
                        <OrderDetailsPanel :order="order" :team="team" />
                        <OrderTimeline :events="events" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
