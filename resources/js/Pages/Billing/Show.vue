<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, ArrowDownTrayIcon, ArrowTopRightOnSquareIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    invoice: Object,
});

const { currency, date } = useFormat();
</script>

<template>
    <Head :title="`Invoice ${invoice.number || invoice.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Invoice {{ invoice.number || invoice.id }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="`Invoice ${invoice.number || invoice.id}`" :subtitle="date(invoice.date)">
                    <template #actions>
                        <Link :href="route('billing.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                        <a v-if="invoice.hosted_url" :href="invoice.hosted_url" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                            View on Stripe
                        </a>
                        <Link :href="route('billing.download', invoice.id)" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <ArrowDownTrayIcon class="h-4 w-4" />
                            Download PDF
                        </Link>
                    </template>
                </PageHeader>

                <FormSection title="Summary">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <Badge :variant="invoice.status === 'paid' ? 'green' : 'amber'">
                                    <CheckCircleIcon v-if="invoice.status === 'paid'" class="h-3 w-3" />
                                    <ClockIcon v-else class="h-3 w-3" />
                                    {{ invoice.status }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Date</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ date(invoice.date) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total</dt>
                            <dd class="mt-1 text-lg font-bold text-gray-900">{{ currency(invoice.total / 100) }}</dd>
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Line items">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(line, idx) in invoice.lines" :key="idx">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ line.description || '—' }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700">{{ line.quantity || 1 }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ currency(line.amount / 100) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <div class="w-full max-w-xs space-y-2 rounded-lg bg-gray-50 p-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-gray-900">{{ currency(invoice.subtotal / 100) }}</span>
                            </div>
                            <div v-if="invoice.tax" class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium text-gray-900">{{ currency(invoice.tax / 100) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 text-base">
                                <span class="font-semibold text-gray-900">Total</span>
                                <span class="font-bold text-gray-900">{{ currency(invoice.total / 100) }}</span>
                            </div>
                        </div>
                    </div>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
