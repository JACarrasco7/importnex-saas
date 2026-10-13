<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ScaleIcon,
    ArrowRightIcon,
    ChatBubbleLeftRightIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';
import LazyImage from '@/Components/LazyImage.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';
import { photoUrl } from '@/Composables/usePhotoUrl';

const props = defineProps({
    cars: { type: Array, default: () => [] },
    requestedIds: { type: Array, default: () => [] },
    requestUrl: { type: String, default: null },
    whatsappNumber: { type: String, default: '34675701439' },
});

const { currency: formatCurrency } = useFormat();
const { t } = useTranslations();

// Filas comparadas. IMPORTANTE: solo campos PUBLICOS (verdict y
// traffic_light son admin-only y publicCarPayload() los filtra antes
// de llegar aqui, por eso NO aparecen en la tabla). lowerIsBetter
// marca si el minimo es el ganador (true para precio/km, false para
// año/potencia/ahorro).
const rows = computed(() => [
    { key: 'purchase_price', label: t('marketplace.compare.price'), format: (c) => formatCurrency(c.purchase_price), numeric: true, lowerIsBetter: true },
    { key: 'market_avg', label: t('marketplace.compare.market_avg'), format: (c) => c.market_avg ? formatCurrency(c.market_avg) : '—', numeric: true, lowerIsBetter: true },
    { key: 'estimated_saving', label: t('marketplace.compare.savings'), format: (c) => c.estimated_saving ? formatCurrency(c.estimated_saving) : '—', numeric: true, lowerIsBetter: false },
    { key: 'year', label: t('marketplace.compare.year'), format: (c) => c.year, numeric: true, lowerIsBetter: false },
    { key: 'mileage', label: t('marketplace.compare.mileage'), format: (c) => c.mileage ? `${c.mileage.toLocaleString('es-ES')} km` : '—', numeric: true, lowerIsBetter: true },
    { key: 'fuel', label: t('marketplace.compare.fuel'), format: (c) => c.fuel || '—' },
    { key: 'transmission', label: t('marketplace.compare.transmission'), format: (c) => c.transmission || '—' },
    { key: 'power', label: t('marketplace.compare.power'), format: (c) => c.power ? `${c.power} CV` : '—', numeric: true, lowerIsBetter: false },
    { key: 'drivetrain', label: t('marketplace.compare.drivetrain', { default: 'Tracción' }), format: (c) => c.drivetrain || '—' },
    { key: 'pais_origen', label: t('marketplace.compare.origin', { default: 'Origen' }), format: (c) => c.pais_origen || c.country_origin || '—' },
]);

// Calcula el ganador por fila (min si lowerIsBetter, max si no) y le
// aplica fondo verde. El peor valor lleva fondo rojo para que el
// cliente lo vea de un vistazo. allEqual desactiva el resaltado.
const highlights = computed(() => {
    const out = {};
    for (const row of rows.value) {
        if (!row.numeric) continue;
        const values = props.cars
            .map((c) => parseFloat(c[row.key]))
            .filter((v) => !Number.isNaN(v) && v !== null && v !== undefined);
        if (values.length < 2) continue;
        const min = Math.min(...values);
        const max = Math.max(...values);
        out[row.key] = {
            best: row.lowerIsBetter ? min : max,
            worst: row.lowerIsBetter ? max : min,
            allEqual: min === max,
        };
    }
    return out;
});

const cellClass = (car, row) => {
    if (!row.numeric) return '';
    const h = highlights.value[row.key];
    if (!h || h.allEqual) return '';
    const v = parseFloat(car[row.key]);
    if (Number.isNaN(v)) return '';
    if (v === h.best) return 'font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50/60 dark:bg-emerald-900/20';
    if (v === h.worst) return 'font-bold text-rose-700 dark:text-rose-300 bg-rose-50/60 dark:bg-rose-900/20';
    return '';
};

const whatsappHref = (car) => {
    const text = t('marketplace_show.whatsapp_text', { brand: car.brand, model: car.model });
    return `https://wa.me/${props.whatsappNumber}?text=${encodeURIComponent(text)}`;
};
</script>

<template>
    <Head :title="t('marketplace.compare.title')" />

    <MarketplaceLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-estoril-100 dark:bg-estoril-900/40">
                        <ScaleIcon class="h-7 w-7 text-estoril-600" />
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                        {{ t('marketplace.compare.title') }}
                    </h1>
                    <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                        {{ t('marketplace.compare.subtitle') }}
                    </p>
                </div>

                <!-- Tip: como leer la tabla -->
                <div class="mb-6 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:ring-amber-800/50">
                    <SparklesIcon class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div class="text-sm text-amber-900 dark:text-amber-100">
                        <p class="font-semibold">{{ t('marketplace.compare.tip_title', { default: 'Cómo leer esta tabla' }) }}</p>
                        <p class="mt-1 text-amber-800/80 dark:text-amber-200/80">
                            {{ t('marketplace.compare.tip_body', { default: 'Resaltamos en verde el mejor valor de cada fila (precio más bajo, km más bajos, año más reciente, etc.) y en rojo el más alto. El resto queda neutro para que compares de un vistazo.' }) }}
                        </p>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="cars.length === 0" class="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ t('marketplace.compare.empty') }}
                    </p>
                    <Link :href="route('marketplace.index')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500">
                        {{ t('marketplace.compare.go_back') }}
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                </div>

                <!-- Compare table -->
                <div v-else class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-asphalt-700">
                        <thead class="bg-gray-50 dark:bg-asphalt-800">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-asphalt-800 dark:text-gray-400">
                                    {{ t('marketplace.compare.features') }}
                                </th>
                                <th v-for="car in cars" :key="car.id" class="px-4 py-4 text-center">
                                    <Link :href="route('marketplace.show', car.id)" class="block">
                                        <div class="aspect-video overflow-hidden rounded-lg bg-gray-100 dark:bg-asphalt-700">
                                            <LazyImage v-if="photoUrl(car.photos?.[0])" :src="photoUrl(car.photos?.[0])" :alt="car.brand + ' ' + car.model" ratio="aspect-video" fit="object-cover" />
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
                                <td v-for="car in cars" :key="car.id" class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white" :class="cellClass(car, row)">
                                    {{ row.format(car) }}
                                </td>
                            </tr>
                            <!-- Action row: Ver ficha + WhatsApp por coche -->
                            <tr class="bg-gray-50 dark:bg-asphalt-800">
                                <td class="sticky left-0 z-10 bg-gray-50 px-4 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-asphalt-800 dark:text-gray-400"></td>
                                <td v-for="car in cars" :key="car.id" class="px-2 py-4">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Link :href="route('marketplace.show', car.id)" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-estoril-600 px-3 py-2 text-xs font-semibold text-white hover:bg-estoril-500">
                                            {{ t('marketplace.compare.view', { default: 'Ver ficha' }) }}
                                            <ArrowRightIcon class="h-3.5 w-3.5" />
                                        </Link>
                                        <a :href="whatsappHref(car)" target="_blank" rel="noopener" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                                            <ChatBubbleLeftRightIcon class="h-3.5 w-3.5" />
                                            {{ t('marketplace.compare.whatsapp', { default: 'WhatsApp' }) }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Leyenda del resaltado -->
                <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-block h-3 w-3 rounded bg-emerald-200 dark:bg-emerald-900/60"></span>
                        {{ t('marketplace.compare.legend_best', { default: 'Mejor valor' }) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-block h-3 w-3 rounded bg-rose-200 dark:bg-rose-900/60"></span>
                        {{ t('marketplace.compare.legend_worst', { default: 'Valor más alto' }) }}
                    </span>
                </div>

                <!-- CTA: pedir uno a medida (NO es consulta sobre los coches visualizados) -->
                <div v-if="requestUrl" class="mt-8 flex flex-col items-center justify-between gap-3 rounded-2xl bg-gradient-to-br from-estoril-700 to-estoril-900 p-5 text-white shadow-sm sm:flex-row">
                    <p class="text-sm font-semibold sm:text-base">{{ t('marketplace.compare.contact_cta') }}</p>
                    <a :href="requestUrl" class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-estoril-800 shadow-sm transition hover:bg-estoril-50 sm:w-auto">
                        {{ t('marketplace.cta_request_notify', { default: 'Pedir uno a medida' }) }}
                    </a>
                </div>
            </div>
        </div>
    </MarketplaceLayout>
</template>
