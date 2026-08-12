<script setup>
import { ref, computed, watch } from 'vue';
import { CalculatorIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    price: { type: Number, required: true },
    currency: { type: String, default: 'EUR' },
    locale: { type: String, default: 'es' },
});

const principal = ref(props.price);
const downPaymentPct = ref(20);
const termMonths = ref(60);
const annualRate = ref(5.5);

const downPayment = computed(() => Math.round(principal.value * (downPaymentPct.value / 100)));
const financedAmount = computed(() => principal.value - downPayment.value);
const monthlyRate = computed(() => annualRate.value / 100 / 12);

const monthlyPayment = computed(() => {
    if (monthlyRate.value === 0) return financedAmount.value / termMonths.value;
    const x = Math.pow(1 + monthlyRate.value, termMonths.value);
    return Math.round((financedAmount.value * monthlyRate.value * x) / (x - 1));
});

const totalPaid = computed(() => monthlyPayment.value * termMonths.value + downPayment.value);
const totalInterest = computed(() => totalPaid.value - principal.value);

const formatCurrency = (value) => {
    const symbol = props.currency === 'EUR' ? '€' : props.currency === 'USD' ? '$' : '£';
    return `${symbol}${value.toLocaleString(props.locale, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
};

watch(() => props.price, (newPrice) => {
    principal.value = newPrice;
});
</script>

<template>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-estoril-50">
                <CalculatorIcon class="h-5 w-5 text-estoril-600" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ t('finance_calc.title') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('finance_calc.subtitle') }}</p>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <!-- Entrada -->
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ t('finance_calc.vehicle_price') }}
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input
                        v-model.number="principal"
                        type="range"
                        :min="10000"
                        :max="200000"
                        step="1000"
                        class="flex-1 accent-estoril-600 dark:accent-estoril-400"
                    />
                    <span class="w-24 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(principal) }}</span>
                </div>
            </div>

            <!-- Entrada inicial -->
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ t('finance_calc.down_payment_pct', { pct: downPaymentPct }) }}
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input
                        v-model.number="downPaymentPct"
                        type="range"
                        min="0"
                        max="50"
                        step="5"
                        class="flex-1 accent-estoril-600 dark:accent-estoril-400"
                    />
                    <span class="w-24 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(downPayment) }}</span>
                </div>
            </div>

            <!-- Plazo -->
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ t('finance_calc.term_months') }}
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input
                        v-model.number="termMonths"
                        type="range"
                        min="12"
                        max="84"
                        step="12"
                        class="flex-1 accent-estoril-600 dark:accent-estoril-400"
                    />
                    <span class="w-24 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ t('finance_calc.months_count', { count: termMonths }) }}</span>
                </div>
            </div>

            <!-- Tasa -->
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ t('finance_calc.annual_rate') }}
                </label>
                <div class="mt-1 flex items-center gap-3">
                    <input
                        v-model.number="annualRate"
                        type="range"
                        min="3"
                        max="12"
                        step="0.5"
                        class="flex-1 accent-estoril-600 dark:accent-estoril-400"
                    />
                    <span class="w-24 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ annualRate.toFixed(1) }}%</span>
                </div>
            </div>
        </div>

        <!-- Resultado -->
        <div class="mt-6 space-y-2 rounded-lg bg-linear-to-br from-estoril-50 to-estoril-100 p-4 dark:from-estoril-900/20 dark:to-estoril-800/20">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('finance_calc.monthly_payment') }}</span>
                <span class="text-2xl font-bold text-estoril-700 dark:text-estoril-400">{{ formatCurrency(monthlyPayment) }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-estoril-200 pt-2 dark:border-asphalt-700">
                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t('finance_calc.total_interest') }}</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(totalInterest) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t('finance_calc.total_paid') }}</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(totalPaid) }}</span>
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            {{ t('finance_calc.disclaimer') }}
        </p>
    </div>
</template>
