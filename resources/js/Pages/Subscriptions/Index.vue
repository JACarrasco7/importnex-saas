<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckIcon, CreditCardIcon, SparklesIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    plans: Object,
    currentPlan: String,
    isOwner: Boolean,
    subscription: Object,
    on_trial: Boolean,
    trial_ends_at: String,
    paymentFailed: Boolean,
});

const { t } = useTranslations();
const { date, currency } = useFormat();

const billingCycle = ref('monthly');

const planKeys = Object.keys(props.plans);

const features = [
    { key: 'vehicles', label: t('subscription.features.vehicles'), format: 'number', path: 'cars_limit' },
    { key: 'clients', label: t('subscription.features.clients'), format: 'number', path: 'clients_limit' },
    { key: 'contacts', label: t('subscription.features.contacts'), format: 'number', path: 'contacts_limit' },
    { key: 'email_support', label: t('subscription.features.email_support') },
    { key: 'marketplace_access', label: t('subscription.features.marketplace_access') },
    { key: 'ai_credits', label: t('subscription.features.ai_credits'), format: 'number' },
    { key: 'advanced_analytics', label: t('subscription.features.advanced_analytics') },
    { key: 'api_access', label: t('subscription.features.api_access') },
    { key: 'custom_branding', label: t('subscription.features.custom_branding') },
    { key: 'webhook_notifications', label: t('subscription.features.webhook_notifications') },
    { key: 'weekly_digest', label: t('subscription.features.weekly_digest') },
];

const annualDiscount = 0.2; // 20% discount for annual billing

const getAnnualPrice = (monthlyPrice) => {
    return Math.round(monthlyPrice * 12 * (1 - annualDiscount));
};

const formatFeature = (feature, plan) => {
    if (feature.path) {
        return plan[feature.path].toLocaleString();
    }
    if (feature.format === 'number') {
        return plan.features?.[feature.key]?.toLocaleString() || 0;
    }
    return plan.features?.[feature.key] ? '✓' : '✗';
};
</script>

<template>
    <Head :title="t('subscription.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('subscription.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('subscription.choose_plan')" :subtitle="t('subscription.subtitle')" />

                <!-- Toggle mensual/anual -->
                <div class="flex justify-center">
                    <div class="inline-flex items-center rounded-full bg-gray-100 p-1">
                        <button
                            @click="billingCycle = 'monthly'"
                            :class="['rounded-full px-4 py-2 text-sm font-medium transition-all', billingCycle === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900']"
                        >
                            Mensual
                        </button>
                        <button
                            @click="billingCycle = 'annual'"
                            :class="['rounded-full px-4 py-2 text-sm font-medium transition-all', billingCycle === 'annual' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900']"
                        >
                            Anual <span class="text-emerald-600">-20%</span>
                        </button>
                    </div>
                </div>

                <div v-if="paymentFailed" class="overflow-hidden rounded-2xl border border-rose-300 bg-rose-50 p-4">
                    <div class="flex items-center gap-3">
                        <SparklesIcon class="h-5 w-5 text-rose-600" />
                        <p class="text-sm text-rose-700">
                            <strong>{{ t('subscription.payment_failed') }}</strong> {{ t('subscription.degraded_to_starter') }}
                            <Link href="/billing" class="font-semibold underline">{{ t('subscription.manage_payment') }}</Link> {{ t('subscription.to_reactivate') }}
                        </p>
                    </div>
                </div>

                <div v-else-if="isOwner" class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center gap-3">
                        <SparklesIcon class="h-5 w-5 text-amber-600" />
                        <p class="text-sm text-amber-700">
                            <strong>{{ t('subscription.vitalicio_badge') }}</strong>
                            {{ t('subscription.vitalicio_detail') }}
                        </p>
                    </div>
                </div>

                <div v-else-if="on_trial" class="overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-center gap-3">
                        <SparklesIcon class="h-5 w-5 text-blue-600" />
                        <p class="text-sm text-blue-700"><strong>{{ t('subscription.trial_active') }}:</strong> {{ t('subscription.ends_on') }} {{ date(trial_ends_at) }}</p>
                    </div>
                </div>

                <div v-else-if="subscription && subscription.ends_at" class="overflow-hidden rounded-2xl border border-rose-200 bg-rose-50 p-4">
                    <div class="flex items-center gap-3">
                        <SparklesIcon class="h-5 w-5 text-rose-600" />
                        <p class="text-sm text-rose-700"><strong>{{ t('subscription.canceled') }}:</strong> {{ t('subscription.ends_on') }} {{ date(subscription.ends_at) }}. {{ t('subscription.after_cancel_fallback') }}</p>
                    </div>
                </div>

                <div v-if="subscription" class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center gap-3">
                        <CheckIcon class="h-5 w-5 text-emerald-600" />
                        <p class="text-sm text-emerald-700">
                            <strong>{{ t('subscription.active_banner') }}:</strong> {{ subscription.status }}
                            <span v-if="subscription.ends_at"> — {{ t('subscription.ends_on') }} {{ date(subscription.ends_at) }}</span>
                        </p>
                    </div>
                </div>

                <!-- Tabla comparativa de planes -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-900">Características</th>
                                <th v-for="key in planKeys" :key="key" :class="['py-4 px-6 text-center text-sm font-semibold', currentPlan === key ? 'text-estoril-600' : 'text-gray-900']">
                                    <div class="space-y-1">
                                        <div>{{ plans[key].name }}</div>
                                        <div class="text-2xl font-bold">
                                            {{ currency(billingCycle === 'annual' ? getAnnualPrice(plans[key].price) / 12 : plans[key].price) }}
                                            <span class="text-sm font-normal text-gray-500">/mes</span>
                                        </div>
                                        <div v-if="billingCycle === 'annual'" class="text-xs text-emerald-600">
                                            {{ currency(getAnnualPrice(plans[key].price)) }}/año
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- Límites -->
                            <tr>
                                <td class="py-4 px-6 text-sm text-gray-700 font-medium">Vehículos</td>
                                <td v-for="key in planKeys" :key="key" class="py-4 px-6 text-center text-sm text-gray-900">
                                    {{ plans[key].cars_limit.toLocaleString() }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-sm text-gray-700 font-medium">Clientes</td>
                                <td v-for="key in planKeys" :key="key" class="py-4 px-6 text-center text-sm text-gray-900">
                                    {{ plans[key].clients_limit.toLocaleString() }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6 text-sm text-gray-700 font-medium">Contactos</td>
                                <td v-for="key in planKeys" :key="key" class="py-4 px-6 text-center text-sm text-gray-900">
                                    {{ plans[key].contacts_limit.toLocaleString() }}
                                </td>
                            </tr>

                            <!-- Features -->
                            <tr v-for="feature in features" :key="feature.key">
                                <td class="py-4 px-6 text-sm text-gray-700 font-medium">
                                    {{ feature.label }}
                                </td>
                                <td v-for="key in planKeys" :key="key" class="py-4 px-6 text-center">
                                    <span v-if="formatFeature(feature, plans[key]) === '✓'" class="inline-flex items-center text-emerald-600">
                                        <CheckIcon class="h-5 w-5" />
                                    </span>
                                    <span v-else-if="formatFeature(feature, plans[key]) === '✗'" class="inline-flex items-center text-gray-300">
                                        <XMarkIcon class="h-5 w-5" />
                                    </span>
                                    <span v-else class="text-sm text-gray-900">
                                        {{ formatFeature(feature, plans[key]) }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Botones de acción -->
                            <tr>
                                <td class="py-4 px-6"></td>
                                <td v-for="key in planKeys" :key="key" class="py-4 px-6">
                                    <div v-if="isOwner" class="block w-full rounded-lg bg-amber-50 py-3 text-center text-sm font-semibold text-amber-700">
                                        {{ t('subscription.vitalicio_included') }}
                                    </div>
                                    <Link v-else-if="currentPlan === key" :href="route('subscriptions.show', key)" class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                        {{ t('subscription.view_details') }}
                                    </Link>
                                    <form v-else-if="on_trial || !subscription" method="POST" :action="route('subscriptions.create', key)">
                                        <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                        <input type="hidden" name="billing_cycle" :value="billingCycle" />
                                        <button type="submit" class="block w-full rounded-lg bg-estoril-600 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                                            {{ on_trial ? t('subscription.activate') : t('subscription.subscribe') }}
                                        </button>
                                    </form>
                                    <form v-else method="POST" :action="route('subscriptions.swap', key)">
                                        <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                        <input type="hidden" name="billing_cycle" :value="billingCycle" />
                                        <button type="submit" class="block w-full rounded-lg bg-estoril-600 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                                            {{ t('subscription.switch_to', { name: plans[key].name }) }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="subscription && !on_trial" class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Subscription actions</h3>
                            <p class="mt-1 text-sm text-gray-500">Manage your recurring subscription</p>
                        </div>
                        <div class="flex gap-2">
                            <form v-if="subscription.status === 'active'" method="POST" :action="route('subscriptions.cancel')" class="inline">
                                <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                <button type="submit" class="rounded-lg bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                    Cancelar suscripción
                                </button>
                            </form>
                            <form v-else method="POST" :action="route('subscriptions.resume')" class="inline">
                                <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                <button type="submit" class="rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                                    Resume subscription
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
