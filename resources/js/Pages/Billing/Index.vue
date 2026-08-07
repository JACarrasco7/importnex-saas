<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CreditCardIcon,
    BanknotesIcon,
    ReceiptPercentIcon,
    ArrowDownTrayIcon,
    CalendarIcon,
    ArrowTopRightOnSquareIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    invoices: Array,
    subscription: Object,
    paymentMethod: Object,
    upcomingInvoice: Object,
    stats: Object,
    hasStripeId: Boolean,
    stripePortalUrl: String,
});

const { t } = useTranslations();

const { currency, date } = useFormat();

const cardBrandIcon = (brand) => {
    if (!brand) return CreditCardIcon;
    return CreditCardIcon;
};
</script>

<template>
    <Head :title="t('nav.billing')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('nav.billing') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('nav.billing')" :subtitle="t('billing.subtitle')">
                    <template #actions>
                        <Link v-if="subscription?.stripe_status && subscription?.stripe_status !== 'canceled'" :href="route('subscriptions.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            {{ t('billing.manage_plan') }}
                        </Link>
                        <a v-if="stripePortalUrl" :href="stripePortalUrl" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                            {{ t('billing.stripe_portal') }}
                        </a>
                    </template>
                </PageHeader>

                <!-- No Stripe -->
                <div v-if="!hasStripeId" class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100">
                            <ExclamationCircleIcon class="h-6 w-6 text-amber-600" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-amber-900">{{ t('cars.no_payment_method_on_file') }}</h3>
                            <p class="mt-1 text-sm text-amber-700">{{ t('cars.subscribe_to_plan') }}</p>
                            <Link :href="route('subscriptions.index')" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">
                                {{ t('nav.view_plans') }}
                                <ArrowLeftIcon class="h-4 w-4 rotate-180" />
                            </Link>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <!-- Stats -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <StatCard :label="t('cars.total_paid')" :value="currency(stats.total_paid)" :hint="t('cars.all_time')" :icon="BanknotesIcon" color="emerald" />
                        <StatCard :label="t('billing.invoices')" :value="stats.invoice_count" :hint="t('cars.last_50')" :icon="DocumentTextIcon" color="indigo" />
                        <StatCard :label="t('cars.upcoming')" :value="upcomingInvoice ? currency(upcomingInvoice.total / 100) : 'â€”'" :hint="t('cars.next_invoice')" :icon="CalendarIcon" color="amber" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Subscription -->
                        <div class="lg:col-span-2 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h3 class="text-base font-semibold text-gray-900">{{ t('subscription.title') }}</h3>
                            </div>
                            <div v-if="subscription" class="grid grid-cols-1 gap-x-6 gap-y-4 p-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.plan_label') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ subscription.name || 'â€”' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.status') }}</dt>
                                    <dd class="mt-1">
                                        <Badge :variant="subscription.stripe_status === 'active' ? 'green' : subscription.stripe_status === 'trialing' ? 'blue' : 'amber'">
                                            {{ subscription.stripe_status }}
                                        </Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.trial') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ subscription.on_trial ? t('cars.yes') : t('cars.no_label') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.renews') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ subscription.ends_at ? date(subscription.ends_at) : 'â€”' }}</dd>
                                </div>
                                <div v-if="upcomingInvoice" class="sm:col-span-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.next_invoice') }}</dt>
                                    <dd class="mt-1 flex items-center gap-2 rounded-lg bg-amber-50 p-3">
                                        <ReceiptPercentIcon class="h-5 w-5 text-amber-600" />
                                        <span class="text-sm text-amber-900">{{ currency(upcomingInvoice.total / 100) }} {{ t('cars.on') }} {{ date(upcomingInvoice.date) }}</span>
                                    </dd>
                                </div>
                            </div>
                            <p v-else class="p-6 text-sm text-gray-500">{{ t('cars.no_active_subscription') }}</p>
                        </div>

                        <!-- Método de pago -->
                        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.metodo_de_pago') }}</h3>
                            </div>
                            <div v-if="paymentMethod" class="p-6">
                                <div class="rounded-xl bg-linear-to-br from-gray-900 to-estoril-900 p-5 text-white shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <component :is="cardBrandIcon(paymentMethod.brand)" class="h-6 w-6" />
                                        <span class="text-xs font-semibold uppercase tracking-wider">{{ paymentMethod.brand }}</span>
                                    </div>
                                    <p class="mt-8 text-xl font-mono tracking-wider">â€¢â€¢â€¢â€¢ {{ paymentMethod.last4 }}</p>
                                    <p class="mt-2 text-xs text-gray-300">{{ t('cars.expires') }} {{ String(paymentMethod.exp_month).padStart(2, '0') }} / {{ paymentMethod.exp_year }}</p>
                                </div>
                                <a v-if="stripePortalUrl" :href="stripePortalUrl" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-estoril-600 hover:text-estoril-500">
                                    {{ t('cars.update_card') }}
                                    <ArrowTopRightOnSquareIcon class="h-3 w-3" />
                                </a>
                            </div>
                            <div v-else class="p-6">
                                <EmptyState icon="ðŸ’³" :title="t('cars.no_payment_method')" :description="t('cars.add_card_portal')" />
                            </div>
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">{{ t('billing.invoices') }}</h3>
                            <span class="text-sm text-gray-500">{{ invoices.length }} {{ t('cars.total_label') }}</span>
                        </div>
                        <EmptyState v-if="!invoices.length" icon="ðŸ§¾" :title="t('cars.no_invoices_yet')" :description="t('cars.first_invoice_desc')" />
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Invoice</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <Link :href="route('billing.show', invoice.id)" class="font-medium text-gray-900 hover:text-estoril-600">
                                                {{ invoice.number || invoice.id }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ date(invoice.date) }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ currency(invoice.total / 100) }}</td>
                                        <td class="px-6 py-4">
                                            <Badge :variant="invoice.status === 'paid' ? 'green' : 'amber'">
                                                <CheckCircleIcon v-if="invoice.status === 'paid'" class="h-3 w-3" />
                                                {{ invoice.status }}
                                            </Badge>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a v-if="invoice.pdf_url" :href="invoice.pdf_url" target="_blank" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Descargar PDF">
                                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                                </a>
                                                <Link :href="route('billing.download', invoice.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600" title="Descargar">
                                                    <DocumentTextIcon class="h-4 w-4" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
