<script setup>
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const { t } = useTranslations();
const { currency } = useFormat();

defineProps({
    expenses: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.expenses_vs_estimated') }}</h3>
        </div>
        <div v-if="expenses?.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.expense_concept') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.expense_estimated') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.expense_actual') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.expense_diff') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="exp in expenses" :key="exp.id">
                        <td class="px-6 py-3 text-sm text-gray-900">{{ exp.concept }}</td>
                        <td class="px-6 py-3 text-right font-mono text-sm text-gray-700">{{ currency(exp.estimated) }}</td>
                        <td class="px-6 py-3 text-right font-mono text-sm text-gray-900">{{ currency(exp.actual) }}</td>
                        <td class="px-6 py-3 text-right font-mono text-sm" :class="(exp.actual - exp.estimated) > 0 ? 'text-red-600' : 'text-green-600'">
                            {{ currency(exp.actual - exp.estimated) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="p-6 text-center text-sm text-gray-500">{{ t('cars.no_expenses_logged') }}</p>
    </div>
</template>
