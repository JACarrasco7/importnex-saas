<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ScaleIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';
import LazyImage from '@/Components/LazyImage.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    cars: { type: Array, default: () => [] },
    requestedIds: { type: Array, default: () => [] },
});

const { currency: formatCurrency } = useFormat();
const { t } = useTranslations();

const rows = computed(() => [
    { key: 'price', label: t('marketplace.compare.price', { default: 'Precio' }), format: (c) => formatCurrency(c.purchase_price) },
    { key: 'year', label: t('marketplace.compare.year', { default: 'Año' }), format: (c) => c.year },
    { key: 'mileage', label: t('marketplace.compare.mileage', { default: 'Kilometraje' }), format: (c) => c.mileage ? `${c.mileage.toLocaleString('es-ES')} km` : '—' },
    { key: 'fuel', label: t('marketplace.compare.fuel', { default: 'Combustible' }), format: (c) => c.fuel || '—' },
    { key: 'transmission', label: t('marketplace.compare.transmission', { default: 'Cambio' }), format: (c) => c.transmission || '—' },
    { key: 'power', label: t('marketplace.compare.power', { default: 'Potencia' }), format: (c) => c.power ? `${c.power} CV` : '—' },
    { key: 'doors', label: t('marketplace.compare.doors', { default: 'Puertas' }), format: (c) => c.doors || '—' },
    { key: 'color', label: t('marketplace.compare.color', { default: 'Color' }), format: (c) => c.color || '—' },
    { key: 'verdict', label: t('marketplace.compare.verdict', { default: 'Veredicto' }), format: (c) => c.verdict || '—' },
    { key: 'traffic_light', label: t('marketplace.compare.traffic_light', { default: 'Tráfico' }), format: (c) => c.traffic_light || '—' },
    { key: 'estimated_saving', label: t('marketplace.compare.savings', { default: 'Ahorro estimado' }), format: (c) => c.estimated_saving ? formatCurrency(c.estimated_saving) : '—' },
]);
</script>

<template>
    <Head :title="t('marketplace.compare.title', { default: 'Comparar vehículos' })" />

    <MarketplaceLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-estoril-100 dark:bg-estoril-900/40">
                        <ScaleIcon class="h-7 w-7 text-estoril-600" />
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                        {{ t('marketplace.compare.title', { default: 'Comparar vehículos' }) }}
                    </h1>
                    <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                        {{ t('marketplace.compare.subtitle', { default: 'Compara hasta 4 vehículos lado a lado' }) }}
                    </p>
                </div>

                <!-- Empty state -->
                <div v-if="cars.length === 0" class="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ t('marketplace.compare.empty', { default: 'No hay vehículos seleccionados. Vuelve al marketplace y selecciona los coches que quieres comparar.' }) }}
                    </p>
                    <Link :href="route('marketplace.index')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500">
                        {{ t('marketplace.compare.go_back', { default: 'Ir al marketplace' }) }}
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                </div>

                <!-- Compare table -->
                <div v-else class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-asphalt-700">
                        <thead class="bg-gray-50 dark:bg-asphalt-800">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-asphalt-800 dark:text-gray-400">
                                    {{ t('marketplace.compare.features', { default: 'Características' }) }}
                                </th>
                                <th v-for="car in cars" :key="car.id" class="px-4 py-4 text-center">
                                    <Link :href="route('marketplace.show', car.id)" class="block">
                                        <div class="aspect-video overflow-hidden rounded-lg bg-gray-100 dark:bg-asphalt-700">
                                            <LazyImage v-if="car.photos && car.photos.length > 0" :src="car.photos[0].url || car.photos[0]" :alt="car.brand + ' ' + car.model" ratio="aspect-video" fit="object-cover" />
                                            <div v-else class="flex h-full items-center justify-center">
                                                <span class="text-3xl">🚗</span>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ car.brand }} {{ car.model }}</p>
                                    </Link>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-asphalt-700">
                            <tr v-for="row in rows" :key="row.key">
                                <td class="sticky left-0 z-10 bg-white px-4 py-3 text-sm font-medium text-gray-700 dark:bg-asphalt-900 dark:text-gray-300">
                                    {{ row.label }}
                                </td>
                                <td v-for="car in cars" :key="car.id" class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white">
                                    {{ row.format(car) }}
                                </td>
                            </tr>
                            <!-- Action row -->
                            <tr class="bg-gray-50 dark:bg-asphalt-800">
                                <td class="sticky left-0 z-10 bg-gray-50 px-4 py-4 dark:bg-asphalt-800"></td>
                                <td v-for="car in cars" :key="car.id" class="px-4 py-4 text-center">
                                    <Link :href="route('marketplace.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-3 py-2 text-xs font-semibold text-white hover:bg-estoril-500">
                                        {{ t('marketplace.compare.view', { default: 'Ver detalles' }) }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MarketplaceLayout>
</template>
