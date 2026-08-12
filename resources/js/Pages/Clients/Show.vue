<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, PencilIcon, TruckIcon, ChatBubbleLeftRightIcon, UserIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    client: Object,
});

const { currency, date, statusVariant } = useFormat();
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ client.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="client.name" :subtitle="client.looking_for || 'CRM client'">
                    <template #actions>
                        <Link :href="route('clients.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                        <Link :href="route('clients.edit', client.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PencilIcon class="h-4 w-4" />
                            {{ t('common.edit') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Profile card -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Profile</h3>
                        <Badge :variant="statusVariant(client.status)">{{ client.status }}</Badge>
                    </div>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-if="client.contact_info">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Contact info</dt>
                            <dd class="mt-1 inline-flex items-center gap-1.5 text-sm text-gray-900">
                                <UserIcon class="h-4 w-4 text-gray-400" />
                                {{ client.contact_info }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Budget</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">
                                <span v-if="client.budget_min || client.budget_max">{{ currency(client.budget_min) }} – {{ currency(client.budget_max) }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </dd>
                        </div>
                        <div v-if="client.notes" class="sm:col-span-2 lg:col-span-4">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-700">{{ client.notes }}</dd>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Cars -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4 flex items-center gap-2">
                            <TruckIcon class="h-5 w-5 text-gray-400" />
                            <h3 class="text-base font-semibold text-gray-900">Cars</h3>
                            <span class="ml-auto text-sm text-gray-500">{{ client.cars?.length || 0 }}</span>
                        </div>
                        <div v-if="client.cars?.length" class="divide-y divide-gray-200">
                            <Link v-for="car in client.cars" :key="car.id" :href="route('cars.show', car.id)" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                <span class="font-medium text-gray-900">{{ car.brand }} {{ car.model }}</span>
                                <Badge :variant="statusVariant(car.status)">{{ car.status }}</Badge>
                            </Link>
                        </div>
                        <p v-else class="p-6 text-center text-sm text-gray-500">{{ t('cars.no_cars_assigned') }}</p>
                    </div>

                    <!-- Contact log -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ChatBubbleLeftRightIcon class="h-5 w-5 text-gray-400" />
                                <h3 class="text-base font-semibold text-gray-900">Contact log</h3>
                            </div>
                            <Link :href="route('clients.contact-logs.index', client.id)" class="text-sm font-semibold text-estoril-600 hover:text-estoril-500">
                                {{ t('cars.view_all') }} →
                            </Link>
                        </div>
                        <div v-if="client.contact_logs?.length" class="divide-y divide-gray-200">
                            <div v-for="log in client.contact_logs.slice(0, 5)" :key="log.id" class="px-6 py-3">
                                <p class="text-xs font-medium text-gray-500">{{ date(log.contact_date) }} · {{ log.channel }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ log.summary }}</p>
                            </div>
                        </div>
                        <p v-else class="p-6 text-center text-sm text-gray-500">{{ t('cars.no_contact_history') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
