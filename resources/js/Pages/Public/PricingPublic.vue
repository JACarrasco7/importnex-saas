<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CheckIcon,
    XMarkIcon,
    SparklesIcon,
    ArrowRightIcon,
    UsersIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const billingPeriod = ref('monthly');

const plans = computed(() => [
    {
        name: t('pricing_public.basic_plan'),
        description: t('pricing_public.basic_desc'),
        price: 29,
        yearlyPrice: 290,
        features: [
            t('pricing_public.basic_feature_1'),
            t('pricing_public.basic_feature_2'),
            t('pricing_public.basic_feature_3'),
            t('pricing_public.basic_feature_4'),
            t('pricing_public.basic_feature_5'),
        ],
        cta: t('pricing_public.start_basic'),
        popular: false,
    },
    {
        name: t('pricing_public.pro_plan'),
        description: t('pricing_public.pro_desc'),
        price: 79,
        yearlyPrice: 790,
        features: [
            t('pricing_public.pro_feature_1'),
            t('pricing_public.pro_feature_2'),
            t('pricing_public.pro_feature_3'),
            t('pricing_public.pro_feature_4'),
            t('pricing_public.pro_feature_5'),
            t('pricing_public.pro_feature_6'),
            t('pricing_public.pro_feature_7'),
        ],
        cta: t('pricing_public.start_pro'),
        popular: true,
    },
    {
        name: t('pricing_public.enterprise_plan'),
        description: t('pricing_public.enterprise_desc'),
        price: 199,
        yearlyPrice: 1990,
        features: [
            t('pricing_public.enterprise_feature_1'),
            t('pricing_public.enterprise_feature_2'),
            t('pricing_public.enterprise_feature_3'),
            t('pricing_public.enterprise_feature_4'),
            t('pricing_public.enterprise_feature_5'),
            t('pricing_public.enterprise_feature_6'),
            t('pricing_public.enterprise_feature_7'),
            t('pricing_public.enterprise_feature_8'),
        ],
        cta: t('pricing_public.contact_sales'),
        popular: false,
    },
]);

const savings = computed(() => {
    const baseTotal = plans.value.reduce((sum, p) => sum + p.price * 12, 0);
    const yearlyTotal = plans.value.reduce((sum, p) => sum + p.yearlyPrice, 0);
    return baseTotal - yearlyTotal;
});

const comparisonFeatures = computed(() => [
    {
        group: t('pricing_compare.group_inventory', {}, 'Inventario'),
        items: [
            { key: 'cars', label: t('pricing_compare.feat.cars', {}, 'Vehículos en catálogo'), values: { basic: '50', pro: '500', enterprise: t('pricing_compare.unlimited', {}, 'Ilimitado') } },
            { key: 'photos', label: t('pricing_compare.feat.photos', {}, 'Fotos por vehículo'), values: { basic: '5', pro: '20', enterprise: t('pricing_compare.unlimited', {}, 'Ilimitado') } },
            { key: 'valuation', label: t('pricing_compare.feat.valuation', {}, 'Paquete de valoración PDF'), values: { basic: false, pro: true, enterprise: true } },
            { key: 'kanban', label: t('pricing_compare.feat.kanban', {}, 'Vista Kanban'), values: { basic: true, pro: true, enterprise: true } },
        ],
    },
    {
        group: t('pricing_compare.group_crm', {}, 'CRM y clientes'),
        items: [
            { key: 'clients', label: t('pricing_compare.feat.clients', {}, 'Clientes en CRM'), values: { basic: '50', pro: '500', enterprise: t('pricing_compare.unlimited', {}, 'Ilimitado') } },
            { key: 'contacts', label: t('pricing_compare.feat.contacts', {}, 'Contactos'), values: { basic: '100', pro: '1000', enterprise: t('pricing_compare.unlimited', {}, 'Ilimitado') } },
            { key: 'marketplace', label: t('pricing_compare.feat.marketplace', {}, 'Marketplace público'), values: { basic: true, pro: true, enterprise: true } },
            { key: 'whatsapp', label: t('pricing_compare.feat.whatsapp', {}, 'Integración WhatsApp'), values: { basic: false, pro: true, enterprise: true } },
        ],
    },
    {
        group: t('pricing_compare.group_advanced', {}, 'Funciones avanzadas'),
        items: [
            { key: 'ai', label: t('pricing_compare.feat.ai_verification', {}, 'Verificación con IA'), values: { basic: false, pro: true, enterprise: true } },
            { key: 'map', label: t('pricing_compare.feat.map', {}, 'Mapa interactivo'), values: { basic: false, pro: true, enterprise: true } },
            { key: 'finance', label: t('pricing_compare.feat.finance', {}, 'Módulo Finanzas'), values: { basic: false, pro: false, enterprise: true } },
            { key: 'trips', label: t('pricing_compare.feat.trips', {}, 'Módulo Viajes'), values: { basic: false, pro: true, enterprise: true } },
            { key: 'webhooks', label: t('pricing_compare.feat.webhooks', {}, 'Webhooks salientes'), values: { basic: false, pro: false, enterprise: true } },
        ],
    },
    {
        group: t('pricing_compare.group_support', {}, 'Soporte y formación'),
        items: [
            { key: 'support', label: t('pricing_compare.feat.support', {}, 'Soporte'), values: { basic: t('pricing_compare.support_email', {}, 'Email'), pro: t('pricing_compare.support_priority', {}, 'Email prioritario'), enterprise: t('pricing_compare.support_dedicated', {}, 'Soporte dedicado 24/7') } },
            { key: 'sla', label: t('pricing_compare.feat.sla', {}, 'SLA garantizado (24h)'), values: { basic: false, pro: false, enterprise: true } },
            { key: 'training', label: t('pricing_compare.feat.training', {}, 'Formación personalizada'), values: { basic: false, pro: false, enterprise: true } },
        ],
    },
]);

function featureValueDisplay(value) {
    if (value === true) return { type: 'check' };
    if (value === false) return { type: 'cross' };
    return { type: 'text', text: value };
}
</script>

<template>
    <Head :title="t('pricing_public.title')" />

    <PublicLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden bg-linear-to-br from-estoril-50 via-white to-platinum-100 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800 py-20 sm:py-28">
            <div class="absolute -top-40 -right-40 h-[28rem] w-[28rem] rounded-full bg-estoril-300/60 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-[28rem] w-[28rem] rounded-full bg-platinum-400/50 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-estoril-100 px-4 py-1.5 text-sm font-semibold text-estoril-800 ring-1 ring-estoril-200 dark:bg-estoril-900/30 dark:text-estoril-300 dark:ring-estoril-800">
                        <SparklesIcon class="h-4 w-4" />
                        {{ t('pricing_public.tagline') }}
                    </span>
                    <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl dark:text-white">
                        {{ t('pricing_public.hero_title') }}
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                        {{ t('pricing_public.hero_subtitle') }}
                    </p>

                    <!-- Toggle -->
                    <div class="mt-8 flex items-center justify-center gap-4">
                        <span
                            class="text-sm font-medium transition-colors"
                            :class="billingPeriod === 'monthly' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                        >
                            {{ t('pricing_public.monthly') }}
                        </span>
                        <button
                            type="button"
                            class="relative h-6 w-11 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-estoril-500 focus:ring-offset-2"
                            :class="billingPeriod === 'yearly' ? 'bg-estoril-600' : 'bg-gray-300 dark:bg-asphalt-700'"
                            @click="billingPeriod = billingPeriod === 'monthly' ? 'yearly' : 'monthly'"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition"
                                :class="billingPeriod === 'yearly' ? 'translate-x-6' : 'translate-x-1'"
                            />
                        </button>
                        <span
                            class="text-sm font-medium transition-colors"
                            :class="billingPeriod === 'yearly' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                        >
                            {{ t('pricing_public.yearly') }}
                        </span>
                        <span v-if="billingPeriod === 'yearly'" class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            {{ t('pricing_public.save_amount', { amount: savings }) }}%
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Cards -->
        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-lg gap-8 lg:max-w-none lg:grid-cols-3">
                    <div
                        v-for="plan in plans"
                        :key="plan.name"
                        class="relative flex flex-col rounded-2xl border border-gray-200 bg-white p-8 shadow-sm transition-all hover:shadow-lg dark:border-asphalt-700 dark:bg-asphalt-800"
                        :class="plan.popular ? 'ring-2 ring-estoril-600 scale-105' : ''"
                    >
                        <!-- Popular Badge -->
                        <div v-if="plan.popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center rounded-full bg-estoril-600 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm">
                                {{ t('pricing_public.most_popular') }}
                            </span>
                        </div>

                        <!-- Header -->
                        <div class="mb-4">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ plan.name }}
                            </h2>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ plan.description }}
                            </p>
                        </div>

                        <!-- Price -->
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-gray-900 dark:text-white">
                                €{{ billingPeriod === 'yearly' ? plan.yearlyPrice : plan.price }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                /{{ billingPeriod === 'yearly' ? t('pricing_public.yearly_short') : t('pricing_public.monthly_short') }}
                            </span>
                        </div>

                        <!-- Features -->
                        <ul class="mb-8 flex-1 space-y-4">
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex items-start gap-3"
                            >
                                <CheckIcon class="h-5 w-5 shrink-0 text-green-600" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ feature }}
                                </span>
                            </li>
                        </ul>

                        <!-- CTA -->
                        <Link
                            :href="plan.name === t('pricing_public.enterprise_plan') ? 'mailto:ventas@jjimportmotors.com' : route('register')"
                            class="mt-auto block w-full rounded-xl px-4 py-3 text-center text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-estoril-500 focus:ring-offset-2"
                            :class="plan.popular
                                ? 'bg-estoril-600 text-white hover:bg-estoril-700'
                                : 'bg-white text-gray-900 ring-1 ring-gray-200 hover:bg-gray-50 hover:ring-estoril-300 dark:bg-asphalt-700 dark:text-white dark:ring-asphalt-600 dark:hover:bg-asphalt-600'"
                        >
                            {{ plan.cta }}
                            <ArrowRightIcon v-if="plan.popular" class="ml-2 inline-block h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comparison Table -->
        <section class="bg-white py-16 sm:py-24 dark:bg-asphalt-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ t('pricing_compare.compare_title', {}, 'Compara los planes') }}
                    </h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        {{ t('pricing_compare.compare_subtitle', {}, 'Detalle completo de lo que incluye cada plan.') }}
                    </p>
                </div>

                <div class="mt-12 space-y-12">
                    <div v-for="group in comparisonFeatures" :key="group.group">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ group.group }}
                        </h3>

                        <div class="mt-4 space-y-3 md:hidden">
                            <div v-for="item in group.items" :key="item.key" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-asphalt-700 dark:bg-asphalt-800">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.label }}</div>
                                <div class="mt-2 grid grid-cols-3 gap-2 text-center text-xs">
                                    <div v-for="plan in plans" :key="plan.name">
                                        <div class="text-[10px] uppercase text-gray-500">{{ plan.name }}</div>
                                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            <template v-if="featureValueDisplay(item.values[plan.key]).type === 'check'"><CheckIcon class="mx-auto h-5 w-5 text-green-600" /></template>
                                            <template v-else-if="featureValueDisplay(item.values[plan.key]).type === 'cross'"><XMarkIcon class="mx-auto h-5 w-5 text-gray-300" /></template>
                                            <template v-else>{{ featureValueDisplay(item.values[plan.key]).text }}</template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 hidden overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-asphalt-700 md:block">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-asphalt-700">
                                <thead class="bg-gray-50 dark:bg-asphalt-800">
                                    <tr>
                                        <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ t('pricing_compare.feature_label', {}, 'Característica') }}</th>
                                        <th v-for="plan in plans" :key="plan.name" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" :class="plan.popular ? 'bg-estoril-50 text-estoril-700 dark:bg-estoril-900/20 dark:text-estoril-300' : ''">{{ plan.name }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-asphalt-700 dark:bg-asphalt-900">
                                    <tr v-for="item in group.items" :key="item.key">
                                        <td class="py-4 pl-6 pr-3 text-sm font-medium text-gray-900 dark:text-white">{{ item.label }}</td>
                                        <td v-for="plan in plans" :key="plan.name" class="px-3 py-4 text-center text-sm text-gray-600 dark:text-gray-300" :class="plan.popular ? 'bg-estoril-50/40 dark:bg-estoril-900/10' : ''">
                                            <template v-if="featureValueDisplay(item.values[plan.key]).type === 'check'"><CheckIcon class="mx-auto h-5 w-5 text-green-600" /></template>
                                            <template v-else-if="featureValueDisplay(item.values[plan.key]).type === 'cross'"><XMarkIcon class="mx-auto h-5 w-5 text-gray-300" /></template>
                                            <template v-else><span class="font-semibold">{{ featureValueDisplay(item.values[plan.key]).text }}</span></template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust & FAQ -->
        <section class="bg-gray-50 py-16 dark:bg-asphalt-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ t('pricing_public.faq_title') }}
                    </h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        {{ t('pricing_public.faq_desc') }}
                    </p>
                </div>

                <div class="mt-12 mx-auto max-w-3xl space-y-6">
                    <details class="group rounded-lg bg-white p-6 shadow-sm dark:bg-asphalt-800">
                        <summary class="flex cursor-pointer items-center justify-between text-gray-900 dark:text-white">
                            <span class="font-medium">{{ t('pricing_public.faq_q1') }}</span>
                            <XMarkIcon class="h-5 w-5 transition group-open:rotate-45 dark:text-gray-400" />
                        </summary>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">
                            {{ t('pricing_public.faq_a1') }}
                        </p>
                    </details>

                    <details class="group rounded-lg bg-white p-6 shadow-sm dark:bg-asphalt-800">
                        <summary class="flex cursor-pointer items-center justify-between text-gray-900 dark:text-white">
                            <span class="font-medium">{{ t('pricing_public.faq_q2') }}</span>
                            <XMarkIcon class="h-5 w-5 transition group-open:rotate-45 dark:text-gray-400" />
                        </summary>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">
                            {{ t('pricing_public.faq_a2') }}
                        </p>
                    </details>

                    <details class="group rounded-lg bg-white p-6 shadow-sm dark:bg-asphalt-800">
                        <summary class="flex cursor-pointer items-center justify-between text-gray-900 dark:text-white">
                            <span class="font-medium">{{ t('pricing_public.faq_q3') }}</span>
                            <XMarkIcon class="h-5 w-5 transition group-open:rotate-45 dark:text-gray-400" />
                        </summary>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">
                            {{ t('pricing_public.faq_a3') }}
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-linear-to-r from-estoril-600 to-estoril-800 px-6 py-12 sm:px-12 sm:py-16 text-center">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">
                        {{ t('pricing_public.cta_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-estoril-100">
                        {{ t('pricing_public.cta_subtitle') }}
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-estoril-700 shadow-sm transition hover:bg-estoril-50"
                        >
                            {{ t('pricing_public.start_free_trial') }}
                            <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                        <a
                            href="mailto:ventas@jjimportmotors.com"
                            class="inline-flex items-center gap-2 rounded-xl bg-estoril-700 px-8 py-3.5 text-base font-semibold text-white ring-1 ring-white/20 transition hover:bg-estoril-800"
                        >
                            <TruckIcon class="h-5 w-5" />
                            {{ t('pricing_public.contact_sales') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
