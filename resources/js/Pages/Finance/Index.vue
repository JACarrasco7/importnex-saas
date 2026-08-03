<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { BanknotesIcon, TruckIcon, ReceiptPercentIcon, CalculatorIcon, CurrencyEuroIcon, ChartBarIcon, ScaleIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    kpis: Object,
    actualExpenses: Array,
    estimatedExpenses: Array,
    byStatus: Object,
    monthly: Array,
});

const { t } = useTranslations();
const { currency } = useFormat();

const maxMonthlyTotal = computed(() => Math.max(1, ...(props.monthly?.map((m) => Number(m.total)) || [0])));

const costCategories = computed(() => [
    { label: t('finance.transport'), value: props.kpis.totalTransport, icon: TruckIcon, color: 'amber' },
    { label: t('finance.taxes'), value: props.kpis.totalTaxes, icon: ReceiptPercentIcon, color: 'rose' },
    { label: t('finance.professional_fees'), value: props.kpis.totalFees, icon: CalculatorIcon, color: 'purple' },
]);
</script>

<template>
    <Head :title="t('finance.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('finance.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('finance.title')" :subtitle="t('finance.p_and_l')" />

                <!-- KPIs -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Grand total" :value="currency(kpis.grandTotal)" hint="All costs across fleet" :icon="BanknotesIcon" color="indigo" />
                    <StatCard label="Purchase investment" :value="currency(kpis.totalInvestment)" hint="Vehicle acquisition" :icon="CurrencyEuroIcon" color="blue" />
                    <StatCard label="Cars in pipeline" :value="kpis.carsCount" hint="Currently active" :icon="TruckIcon" color="purple" />
                    <StatCard label="Avg purchase" :value="currency(kpis.averagePurchase)" hint="Per car" :icon="ChartBarIcon" color="emerald" />
                </div>

                <!-- Cost breakdown -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div v-for="cat in costCategories" :key="cat.label" class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <div class="flex items-center gap-3">
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', `bg-${cat.color}-50`]">
                                <component :is="cat.icon" class="h-5 w-5" :class="`text-${cat.color}-600`" />
                            </div>
                            <p class="text-sm font-semibold text-gray-900">{{ cat.label }}</p>
                        </div>
                        <p class="mt-4 text-2xl font-bold text-gray-900">{{ currency(cat.value) }}</p>
                    </div>
                </div>

                <!-- Estimated vs Actual -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center gap-2">
                        <ScaleIcon class="h-5 w-5 text-gray-400" />
                        <h3 class="text-base font-semibold text-gray-900">Estimated vs Actual</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Estimated</h4>
                            <table class="min-w-full text-sm">
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="expense in estimatedExpenses" :key="expense.concept">
                                        <td class="py-2 text-gray-700">{{ expense.concept }}</td>
                                        <td class="py-2 text-right font-medium text-gray-900">{{ currency(expense.total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Actual</h4>
                            <table class="min-w-full text-sm">
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="expense in actualExpenses" :key="expense.concept">
                                        <td class="py-2 text-gray-700">{{ expense.concept }}</td>
                                        <td class="py-2 text-right font-medium text-gray-900">{{ currency(expense.total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- By status -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">By status</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Cars</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total investment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(data, status) in byStatus" :key="status" class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ status }}</td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-700">{{ data.count }}</td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ currency(data.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly chart -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Last 6 months</h3>
                    </div>
                    <div v-if="monthly.length === 0" class="p-6 text-center text-sm text-gray-500">No data yet.</div>
                    <div v-else class="space-y-3 p-6">
                        <div v-for="entry in monthly" :key="entry.month" class="flex items-center gap-3">
                            <div class="w-20 text-sm font-medium text-gray-700">{{ entry.month }}</div>
                            <div class="relative flex-1 h-9 overflow-hidden rounded-lg bg-gray-100">
                                <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-indigo-500 to-purple-600 transition-all" :style="{ width: ((Number(entry.total) / maxMonthlyTotal) * 100) + '%' }"></div>
                                <div class="absolute inset-0 flex items-center justify-between px-3 text-xs font-medium text-gray-700">
                                    <span>{{ entry.count }} cars</span>
                                    <span class="font-semibold text-gray-900">{{ currency(entry.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
