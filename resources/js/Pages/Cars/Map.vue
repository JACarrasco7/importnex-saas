<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { MapPinIcon, TruckIcon, MapIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import MapaLeaflet from '@/Components/MapaLeaflet.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    clusters: Array,
    totalCars: Number,
    totalCities: Number,
});

const buildMarkerText = (cluster) => {
    if (!cluster?.cars?.length) return 'Empty';
    const first = cluster.cars[0];
    return `${cluster.count} cars in ${cluster.city} — ${first.brand} ${first.model}`;
};
</script>

<template>
    <Head title="Cars Map" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cars Map</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Geographic view" subtitle="See where your cars are located">
                    <template #actions>
                        <span class="rounded-lg bg-white px-4 py-2 text-sm text-gray-700 shadow-sm ring-1 ring-gray-200">
                            <strong>{{ totalCars }}</strong> cars · <strong>{{ totalCities }}</strong> cities
                        </span>
                    </template>
                </PageHeader>

                <EmptyState v-if="totalCars === 0" icon="📍" title="No location data yet" description="Add lat/lng to your cars to see them on the map." />

                <div v-else class="space-y-6">
                    <div v-for="(cluster, idx) in clusters" :key="idx" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                                    <MapPinIcon class="h-5 w-5 text-indigo-600" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ cluster.city }}</h3>
                            </div>
                            <Badge variant="indigo">{{ cluster.count }} cars</Badge>
                        </div>
                        <div class="p-6">
                            <MapaLeaflet v-if="cluster.lat && cluster.lng" :lat="cluster.lat" :lng="cluster.lng" :marker-text="buildMarkerText(cluster)" height="280px" />
                            <ul class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4">
                                <li v-for="car in cluster.cars" :key="car.id">
                                    <Link :href="route('cars.show', car.id)" class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 hover:bg-indigo-50 hover:text-indigo-700">
                                        <span class="flex items-center gap-2 truncate">
                                            <TruckIcon class="h-3.5 w-3.5 flex-shrink-0 text-gray-400" />
                                            <span class="truncate text-sm">{{ car.brand }} {{ car.model }}</span>
                                        </span>
                                        <span :class="['h-2 w-2 flex-shrink-0 rounded-full', { 'bg-emerald-500': car.traffic_light === 'green', 'bg-amber-500': car.traffic_light === 'amber', 'bg-rose-500': car.traffic_light === 'red', 'bg-gray-400': car.traffic_light === 'neutral' }]"></span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
