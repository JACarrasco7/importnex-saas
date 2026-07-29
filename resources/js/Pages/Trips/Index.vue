<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { TruckIcon, MapPinIcon, BanknotesIcon, LightBulbIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    trips: Array,
    totalCars: Number,
    totalTransport: Number,
});

const { currency, statusVariant } = useFormat();
</script>

<template>
    <Head title="Trip Planner" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trip Planner</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Trip Planner" subtitle="Optimize logistics by grouping cars by city">
                    <template #actions>
                        <span class="rounded-lg bg-white px-4 py-2 text-sm text-gray-700 shadow-sm ring-1 ring-gray-200">
                            <strong>{{ totalCars }}</strong> cars · <strong>{{ currency(totalTransport) }}</strong> transport
                        </span>
                    </template>
                </PageHeader>

                <EmptyState v-if="!trips?.length" icon="🚛" title="No trips to plan" description="No cars in Purchased or In transit status with location data yet." />

                <div v-else class="space-y-6">
                    <div v-for="trip in trips" :key="trip.city" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4 flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                                    <MapPinIcon class="h-5 w-5 text-indigo-600" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ trip.city }}</h3>
                                    <p class="text-sm text-gray-500">
                                        {{ trip.count }} cars · Transport {{ currency(trip.total_transport) }} · Value {{ currency(trip.total_value) }}
                                    </p>
                                </div>
                            </div>
                            <Badge v-if="trip.potential_savings > 0" variant="green">
                                Save {{ currency(trip.potential_savings) }}
                            </Badge>
                        </div>
                        <div v-if="trip.recommendation" class="border-b border-gray-200 bg-blue-50 px-6 py-3">
                            <div class="flex items-start gap-2">
                                <LightBulbIcon class="h-5 w-5 flex-shrink-0 text-blue-600" />
                                <p class="text-sm text-blue-900">{{ trip.recommendation }}</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Transport</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="car in trip.cars" :key="car.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-3">
                                            <Link :href="route('cars.show', car.id)" class="inline-flex items-center gap-1 font-medium text-gray-900 hover:text-indigo-600">
                                                <TruckIcon class="h-4 w-4 text-gray-400" />
                                                {{ car.brand }} {{ car.model }}
                                                <ArrowRightIcon class="h-3 w-3 text-gray-400" />
                                            </Link>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-500">{{ car.year }}</td>
                                        <td class="px-6 py-3"><Badge :variant="statusVariant(car.status)">{{ car.status }}</Badge></td>
                                        <td class="px-6 py-3 text-right text-sm text-gray-700">{{ currency(car.transport) }}</td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ currency(car.purchase_price) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
