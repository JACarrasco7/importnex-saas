<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    LinkIcon,
    EnvelopeIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import MapaLeaflet from '@/Components/MapaLeaflet.vue';
import Badge from '@/Components/Badge.vue';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';
import { photoUrl } from '@/Composables/usePhotoUrl';

const props = defineProps({
    car: Object,
    derived: Object,
    requestUrl: { type: String, default: null },
    relatedCars: { type: Array, default: () => [] },
});

const { t } = useTranslations();
const { currency, date, trafficLightVariant, verdictVariant, confidenceVariant } = useFormat();

// Galería: índice de la foto activa (empieza en 0)
const activePhotoIndex = ref(0);
const activePhoto = computed(() => {
    const photos = props.car?.photos || [];
    return photos[activePhotoIndex.value] || null;
});
const totalPhotos = computed(() => (props.car?.photos || []).length);
const goPrev = () => {
    if (totalPhotos.value === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value - 1 + totalPhotos.value) % totalPhotos.value;
};
const goNext = () => {
    if (totalPhotos.value === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value + 1) % totalPhotos.value;
};

const costItems = [
    { key: 'purchase_price', label: t('marketplace_show.cost_purchase') },
    { key: 'new_price', label: t('marketplace_show.cost_new') },
    { key: 'transport', label: t('marketplace_show.cost_transport') },
    { key: 'itv_fee', label: t('marketplace_show.cost_itv') },
    { key: 'coc_fee', label: t('marketplace_show.cost_coc') },
    { key: 'dgt_fees', label: t('marketplace_show.cost_dgt') },
    { key: 'professional_fees', label: t('marketplace_show.cost_professional') },
    { key: 'deposit', label: t('marketplace_show.cost_deposit') },
];

const specItems = [
    { key: 'brand', label: t('marketplace_show.brand') },
    { key: 'model', label: t('marketplace_show.model') },
    { key: 'year', label: t('marketplace_show.year') },
    { key: 'mileage', label: t('marketplace_show.mileage'), suffix: ' km' },
    { key: 'fuel', label: t('marketplace_show.fuel') },
    { key: 'transmission', label: t('marketplace_show.transmission') },
    { key: 'cv', label: t('marketplace_show.power'), suffix: ' CV' },
    { key: 'co2', label: t('marketplace_show.co2'), suffix: ' g/km' },
    { key: 'color', label: t('marketplace_show.color') },
    { key: 'vin', label: t('marketplace_show.vin') },
];

const ratingIcon = (r) => ({
    favorable: CheckCircleIcon, neutral: MinusCircleIcon, unfavorable: XCircleIcon,
}[r] || MinusCircleIcon);

const researchAspectLabels = {
    common_issues: t('marketplace_show.aspect_common_issues'),
    recalls: t('marketplace_show.aspect_recalls'),
    market_price: t('marketplace_show.aspect_market_price'),
    reliability: t('marketplace_show.aspect_reliability'),
    spain_homologation: t('marketplace_show.aspect_spain_homologation'),
    dgt_label: t('marketplace_show.aspect_dgt_label'),
    insurance_estimate: t('marketplace_show.aspect_insurance_estimate'),
    parts_maintenance: t('marketplace_show.aspect_parts_maintenance'),
    unit_specific: t('marketplace_show.aspect_unit_specific'),
};

const aspects = computed(() => {
    const research = props.car.research || {};
    const gaps = props.derived?.research_gaps || [];
    return Object.entries(researchAspectLabels).map(([key, label]) => {
        const entry = research[key] || [];
        return { key, label, ...entry, missing: gaps.includes(key) };
    });
});

const marketPosition = computed(() => {
    const stats = props.derived?.comparables_stats || {};
    const total = props.derived?.total_cost || 0;
    if (!stats.avg || !total) return null;
    const ratio = total / stats.avg;
    if (ratio <= 1.00) return { label: t('marketplace_show.market_below'), variant: 'success', ratio };
    if (ratio <= 1.05) return { label: t('marketplace_show.market_at'), variant: 'warning', ratio };
    return { label: t('marketplace_show.market_above'), variant: 'danger', ratio };
});
</script>

<template>
    <Head :title="`${car.brand} ${car.model} - ${t('public.marketplace_title')}`" />

    <MarketplaceLayout>
        <div class="min-h-screen bg-gradient-to-br from-platinum-100 via-white to-estoril-50 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800">
            <div class="py-8">
                <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <Link :href="route('marketplace.index')" class="hover:text-estoril-700 dark:hover:text-estoril-300">
                            {{ t('marketplace.breadcrumbs.home') }}
                        </Link>
                        <span>/</span>
                        <Link :href="route('marketplace.index')" class="hover:text-estoril-700 dark:hover:text-estoril-300">
                            {{ t('marketplace.breadcrumbs.catalog') }}
                        </Link>
                        <span>/</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ car.brand }} {{ car.model }}</span>
                    </nav>

                    <!-- Header -->
                    <div class="border-b border-gray-200 pb-4 dark:border-asphalt-700">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ car.brand }} {{ car.model }}</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ t('marketplace_show.vin_label') }} {{ car.vin || t('marketplace.not_available') }}</p>
                    </div>
                <!-- Status bar -->
                <div class="flex flex-wrap items-center gap-3">
                    <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                    <Badge :variant="verdictVariant(car.verdict)">{{ car.verdict }}</Badge>
                    <span v-if="car.year" class="text-sm text-gray-500">{{ car.year }}</span>
                </div>

                <!-- Hero photo gallery (marketplace item: ficha sin foto destacada) -->
                <div v-if="totalPhotos > 0" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="relative aspect-[16/9] bg-gradient-to-br from-asphalt-200 to-gray-300">
                        <img
                            v-if="activePhoto"
                            :src="photoUrl(activePhoto)"
                            :alt="`${car.brand} ${car.model} - foto ${activePhotoIndex + 1}`"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full flex-col items-center justify-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ car.brand }} {{ car.model }}</span>
                        </div>
                        <!-- Controles del carrusel -->
                        <template v-if="totalPhotos > 1">
                            <button
                                type="button"
                                @click="goPrev"
                                class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white shadow-sm transition hover:bg-black/70"
                                :aria-label="t('marketplace_show.prev_photo', { default: 'Foto anterior' })">
                                <ChevronLeftIcon class="h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                @click="goNext"
                                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white shadow-sm transition hover:bg-black/70"
                                :aria-label="t('marketplace_show.next_photo', { default: 'Foto siguiente' })">
                                <ChevronRightIcon class="h-5 w-5" />
                            </button>
                            <span class="absolute bottom-3 right-3 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold text-white">
                                {{ activePhotoIndex + 1 }} / {{ totalPhotos }}
                            </span>
                        </template>
                    </div>
                    <!-- Thumbnails -->
                    <div v-if="totalPhotos > 1" class="flex gap-2 overflow-x-auto p-3">
                        <button
                            v-for="(photo, idx) in car.photos"
                            :key="photo.id || idx"
                            type="button"
                            @click="activePhotoIndex = idx"
                            :class="[
                                'h-16 w-24 shrink-0 overflow-hidden rounded-md ring-2 transition',
                                idx === activePhotoIndex ? 'ring-estoril-600' : 'ring-transparent hover:ring-gray-300'
                            ]"
                            :aria-label="`Foto ${idx + 1}`">
                            <img :src="photoUrl(photo)" :alt="`${car.brand} ${car.model} miniatura ${idx + 1}`" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>
                <div v-else class="flex aspect-[16/9] w-full flex-col items-center justify-center rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 text-gray-400 shadow-sm ring-1 ring-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    <span class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ car.brand }} {{ car.model }}</span>
                </div>

                <!-- CTA: enviar solicitud / contactar -->
                <div v-if="requestUrl" class="flex flex-col items-center justify-between gap-3 rounded-2xl bg-gradient-to-br from-estoril-700 to-estoril-900 p-5 text-white shadow-sm sm:flex-row">
                    <div class="flex items-center gap-3">
                        <EnvelopeIcon class="h-6 w-6 shrink-0 text-estoril-100" />
                        <div>
                            <p class="text-sm font-semibold sm:text-base">{{ t('marketplace_show.cta_request_title', { default: '¿Te interesa este coche?' }) }}</p>
                            <p class="text-xs text-estoril-100 sm:text-sm">{{ t('marketplace_show.cta_request_desc', { default: 'Te enviamos más información y organizamos una visita sin compromiso.' }) }}</p>
                        </div>
                    </div>
                    <a
                        :href="requestUrl"
                        class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-estoril-800 shadow-sm transition hover:bg-estoril-50 sm:w-auto">
                        <EnvelopeIcon class="h-4 w-4" />
                        {{ t('marketplace_show.cta_request_form', { default: 'Enviar solicitud' }) }}
                    </a>
                </div>

                <!-- IEDMT estimation warning (solo si hay importación; en unidades ES no se devenga) -->
                <div v-if="derived?.iedmt > 0" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 text-amber-600" />
                    <div class="text-sm text-amber-900">
                        <p class="font-semibold">{{ t('marketplace_show.iedmt_warning_title') }}</p>
                        <p class="mt-1 text-amber-800">
                            {{ t('marketplace_show.iedmt_warning_desc', { amount: currency(derived?.iedmt), total: currency(derived?.total_cost) }) }}
                        </p>
                    </div>
                </div>

                <!-- Location -->
                <div v-if="car.lat && car.lng" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_location') }}</h3>
                    </div>
                    <div class="p-6">
                        <MapaLeaflet :lat="car.lat" :lng="car.lng" :marker-text="`${car.brand} ${car.model} - ${car.city || ''}`" height="300px" />
                    </div>
                </div>

                <!-- Technical specs -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_tech_specs') }}</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                        <div v-for="spec in specItems" :key="spec.key">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ spec.label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900" :class="{ 'font-mono text-xs': spec.key === 'vin' }">{{ car[spec.key] || t('marketplace.not_available') }}{{ spec.suffix || '' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Costs -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_costs') }}</h3>
                        <span class="text-sm text-gray-500">{{ t('marketplace_show.total_all_in') }}: <span class="font-mono font-semibold text-gray-900">{{ currency(derived?.total_cost) }}</span></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                        <div v-for="cost in costItems" :key="cost.key">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ cost.label }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ currency(car[cost.key]) }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Investigation -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_investigation') }}</h3>
                        <span v-if="derived?.research_gaps?.length" class="text-xs font-medium text-amber-700">
                            {{ derived.research_gaps.length === 1 ? t('marketplace_show.aspects_pending', { count: derived.research_gaps.length }) : t('marketplace_show.aspects_pending_plural', { count: derived.research_gaps.length }) }}
                        </span>
                    </div>

                    <!-- Verdict -->
                    <div v-if="car.verdict" class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge :variant="verdictVariant(car.verdict)" dot size="lg">{{ car.verdict }}</Badge>
                            <Badge v-if="car.verdict_confidence" :variant="confidenceVariant(car.verdict_confidence)">
                                {{ t('marketplace_show.confidence') }}: {{ car.verdict_confidence }}
                            </Badge>
                            <span v-if="car.verdict_at" class="text-xs text-gray-500">
                                {{ date(car.verdict_at) }}
                            </span>
                        </div>
                        <p v-if="car.verdict_reasoning" class="mt-3 text-sm text-gray-700">{{ car.verdict_reasoning }}</p>
                        <p v-if="car.verdict_changes" class="mt-2 text-xs italic text-gray-600">
                            <span class="font-semibold not-italic text-gray-700">{{ t('marketplace_show.what_would_change') }}</span>
                            {{ car.verdict_changes }}
                        </p>
                    </div>

                    <div v-if="!car.verdict && !derived?.research_gaps?.length" class="px-6 py-8 text-center text-sm text-gray-500">
                        {{ t('marketplace_show.no_valuation') }}
                    </div>

                    <!-- Balance pros / cons -->
                    <div v-if="(car.pros?.length || car.cons?.length)" class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-estoril-800">{{ t('marketplace_show.in_favor', { count: car.pros?.length || 0 }) }}</h4>
                            <ul v-if="car.pros?.length" class="space-y-2">
                                <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-estoril-200 bg-estoril-50 p-3">
                                    <CheckCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-estoril-700" />
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ pro.text }}</p>
                                        <span class="mt-1 inline-block text-xs font-medium uppercase text-estoril-800">{{ pro.weight }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-sm italic text-gray-500">{{ t('marketplace_show.no_points_in_favor') }}</p>
                        </div>
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-red-700">{{ t('marketplace_show.against', { count: car.cons?.length || 0 }) }}</h4>
                            <ul v-if="car.cons?.length" class="space-y-2">
                                <li v-for="(con, i) in car.cons" :key="i" class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3">
                                    <XCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-600" />
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ con.text }}</p>
                                        <span class="mt-1 inline-block text-xs font-medium uppercase text-red-700">{{ con.weight }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-sm italic text-gray-500">{{ t('marketplace_show.no_points_against') }}</p>
                        </div>
                    </div>

                    <!-- 9 research aspects -->
                    <div class="border-t border-gray-200 px-6 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.nine_research_aspects') }}</h4>
                        <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <li v-for="aspect in aspects" :key="aspect.key" class="rounded-lg border p-3"
                                :class="aspect.missing ? 'border-dashed border-gray-300 bg-gray-50' : 'border-gray-200 bg-white'">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900">{{ aspect.label }}</p>
                                    <component v-if="!aspect.missing" :is="ratingIcon(aspect.rating)" class="h-4 w-4 flex-shrink-0"
                                        :class="{
                                            'text-estoril-700': aspect.rating === 'favorable',
                                            'text-red-600': aspect.rating === 'unfavorable',
                                            'text-gray-500': aspect.rating === 'neutral' || !aspect.rating,
                                        }" />
                                </div>
                                <p v-if="aspect.missing" class="mt-2 text-xs italic text-gray-500">{{ t('marketplace_show.not_yet_investigated') }}</p>
                                <template v-else>
                                    <p v-if="aspect.finding" class="mt-2 text-sm text-gray-700">{{ aspect.finding }}</p>
                                    <a v-if="aspect.source" :href="aspect.source" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs text-estoril-600 hover:text-estoril-500">
                                        <LinkIcon class="h-3 w-3" />
                                        {{ t('marketplace_show.source') }}
                                    </a>
                                    <span v-if="aspect.date" class="ml-2 text-xs text-gray-400">{{ aspect.date }}</span>
                                </template>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Market comparables -->
                <div v-if="car.market_avg || derived?.comparables_stats?.count" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_market_comparables') }}</h3>
                        <Badge v-if="marketPosition" :variant="marketPosition.variant">
                            {{ marketPosition.label }} ({{ (marketPosition.ratio * 100).toFixed(1) }}%)
                        </Badge>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.avg') }}</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_avg) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.minimum') }}</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_min) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.maximum') }}</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_max) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.estimated_saving') }}</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-estoril-800">{{ currency(car.estimated_saving) }}</dd>
                        </div>
                    </div>

                    <div v-if="car.comparables_list?.length" class="border-t border-gray-200 px-6 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.comparables_count', { count: car.comparables_list.length }) }}</h4>
                        <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                            <li v-for="(comp, i) in car.comparables_list" :key="i" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ comp.title || comp.t }}</p>
                                    <p class="text-xs text-gray-500">
                                        <span v-if="comp.km">{{ comp.km.toLocaleString() }} km</span>
                                        <span v-if="comp.country"> &middot; {{ comp.country || comp.pais }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-sm font-semibold text-gray-900">{{ currency(comp.price ?? comp.p) }}</span>
                                    <a v-if="comp.url || comp.u" :href="comp.url || comp.u" target="_blank" rel="noopener" class="text-estoril-600 hover:text-estoril-500">
                                        <LinkIcon class="h-4 w-4" />
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Import progress (read-only) -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_import_progress') }}</h3>
                    </div>
                    <div class="bg-gray-50 px-6 py-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700">{{ t('marketplace_show.milestones') }}</h4>
                            <span class="text-sm font-mono font-semibold text-gray-900">
                                {{ derived?.milestones_progress?.completed || 0 }} / {{ derived?.milestones_progress?.total || 0 }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full bg-estoril-600 transition-all"
                                :style="{ width: derived?.milestones_progress?.total ? ((derived.milestones_progress.completed / derived.milestones_progress.total) * 100) + '%' : '0%' }" />
                        </div>
                        <p class="mt-3 text-sm text-gray-600">
                            {{ t('marketplace_show.inspections', { done: derived?.inspections_progress?.completed || 0, total: derived?.inspections_progress?.total || 0 }) }}
                        </p>
                    </div>
                </div>

                <!-- Notes (read-only) -->
                <div v-if="car.notes" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_notes') }}</h3>
                    </div>
                    <div class="p-6">
                        <pre class="whitespace-pre-wrap font-sans text-sm text-gray-700">{{ car.notes }}</pre>
                    </div>
                </div>

                <!-- Coches relacionados (misma marca) -->
                <div v-if="relatedCars.length > 0" class="mt-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('marketplace.related_cars', { default: 'Otros coches similares' }) }}
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="rcar in relatedCars"
                            :key="rcar.id"
                            :href="route('marketplace.show', rcar.id)"
                            class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-estoril-300 dark:bg-asphalt-800 dark:ring-asphalt-700">
                            <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-asphalt-200 to-gray-300">
                                <img v-if="photoUrl(rcar.photos?.[0])" :src="photoUrl(rcar.photos?.[0])" :alt="`${rcar.brand} ${rcar.model}`" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                <div v-else class="flex h-full w-full flex-col items-center justify-center text-gray-400">
                                    <span class="text-4xl">🚗</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ rcar.brand }} {{ rcar.model }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ rcar.year }} · {{ rcar.mileage ? `${(rcar.mileage / 1000).toFixed(0)}k km` : '—' }}</p>
                                <p class="mt-1 text-sm font-bold text-estoril-800 dark:text-estoril-300">{{ currency(rcar.purchase_price) }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-12 border-t border-gray-200 bg-white py-6 dark:border-asphalt-700 dark:bg-asphalt-900">
            <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
                &copy; {{ new Date().getFullYear() }} Importnex. {{ t('marketplace_show.footer_rights') }}
            </div>
        </footer>
        </div>
    </MarketplaceLayout>
</template>
