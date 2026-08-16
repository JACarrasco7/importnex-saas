<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { MapPinIcon, TruckIcon, MapIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import MapaLeaflet from '@/Components/MapaLeaflet.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Badge from '@/Components/Badge.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const { t } = useTranslations();
const { currency: formatCurrency } = useFormat();

const props = defineProps({
    clusters: Array,
    totalCars: Number,
    totalCities: Number,
});

const buildMarkerText = (cluster) => {
    if (!cluster?.cars?.length) return t('common.empty');
    const first = cluster.cars[0];
    return `${cluster.count} ${t('map.cars_in')} ${cluster.city} — ${first.brand} ${first.model}`;
};
</script>

<template>
    <Head :title="t('nav.map')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('nav.map') }}</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('map.title')" :subtitle="t('map.subtitle')">
                    <template #actions>
                        <span class="rounded-lg bg-white px-4 py-2 text-sm text-gray-700 shadow-sm ring-1 ring-gray-200">
                            <strong>{{ totalCars }}</strong> {{ t('map.cars') }} · <strong>{{ totalCities }}</strong> {{ t('map.cities') }}
                        </span>
                    </template>
                </PageHeader>

                <EmptyState v-if="totalCars === 0" icon="📍" :title="t('map.empty_title')" :description="t('map.empty_desc')" />

                <div v-else class="space-y-8">
                    <div v-for="(cluster, idx) in clusters" :key="idx" class="group overflow-hidden rounded-2xl bg-gradient-to-b from-white to-gray-50 shadow-lg ring-1 ring-gray-200/50 transition-all hover:shadow-xl">
                        <div class="border-b border-gray-100 bg-white/80 px-6 py-5 backdrop-blur-sm">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-estoril-50 to-estoril-100 ring-2 ring-estoril-200/50">
                                        <MapPinIcon class="h-6 w-6 text-estoril-600" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ cluster.city }}</h3>
                                        <p class="text-sm text-gray-500">{{ t('map.location_subtitle') }}</p>
                                    </div>
                                </div>
                                <Badge variant="indigo" class="shadow-md">{{ cluster.count }} {{ t('map.cars') }}</Badge>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-6 overflow-hidden rounded-xl shadow-md ring-1 ring-gray-200 transition-all group-hover:ring-2 group-hover:ring-estoril-300/50">
                                <MapaLeaflet v-if="cluster.lat && cluster.lng" :lat="cluster.lat" :lng="cluster.lng" :marker-text="buildMarkerText(cluster)" height="380px" />
                            </div>
                            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                        <TruckIcon class="h-4 w-4 text-estoril-500" />
                                        {{ t('map.vehicles_here') }}
                                    </h4>
                                    <span class="text-xs text-gray-400">{{ cluster.cars.length }} {{ t('map.vehicles') }}</span>
                                </div>
                                <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <li v-for="car in cluster.cars" :key="car.id">
                                        <Link :href="route('cars.show', car.id)" class="group/car flex items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-gray-50 to-white px-4 py-3 transition-all hover:from-estoril-50 hover:to-estoril-100 hover:shadow-md hover:ring-1 hover:ring-estoril-200">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-gray-200 group-hover/car:ring-estoril-300/50 transition-colors">
                                                    <TruckIcon class="h-5 w-5 text-gray-400 group-hover/car:text-estoril-500 transition-colors" />
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-medium text-gray-900 group-hover/car:text-estoril-700 transition-colors">{{ car.brand }} {{ car.model }}</p>
                                                    <p class="text-xs text-gray-500">{{ car.year }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium text-gray-600">{{ formatCurrency(car.purchase_price) }}</span>
                                                <span :class="['h-3 w-3 flex-shrink-0 rounded-full shadow-sm', { 'bg-emerald-500 shadow-emerald-500/30': car.traffic_light === 'green', 'bg-amber-500 shadow-amber-500/30': car.traffic_light === 'amber', 'bg-rose-500 shadow-rose-500/30': car.traffic_light === 'red', 'bg-gray-400 shadow-gray-400/30': car.traffic_light === 'neutral' }]"></span>
                                            </div>
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
