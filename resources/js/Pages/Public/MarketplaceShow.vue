<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    LinkIcon,
} from '@heroicons/vue/24/outline';
import MapaLeaflet from '@/Components/MapaLeaflet.vue';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    car: Object,
    derived: Object,
});

const { t } = useTranslations();
const { currency, date, trafficLightVariant, verdictVariant, confidenceVariant } = useFormat();

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
    <Head :title="`${car.brand} ${car.model} - Marketplace`" />

    <div class="min-h-screen bg-gradient-to-br from-platinum-100 via-white to-estoril-50">
        <!-- Public header -->
        <header class="border-b border-gray-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link :href="route('marketplace.index')" class="flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900">
                    <ArrowLeftIcon class="h-4 w-4" />
                    {{ t('marketplace_show.back_to_marketplace') }}
                </Link>
                <a :href="route('login')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ t('marketplace_show.sign_in') }}</a>
            </div>
        </header>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="border-b border-gray-200 pb-4">
                    <h1 class="text-3xl font-bold text-gray-900">{{ car.brand }} {{ car.model }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ t('marketplace_show.vin_label') }} {{ car.vin || t('marketplace.not_available') }}</p>
                </div>

                <!-- Status bar -->
                <div class="flex flex-wrap items-center gap-3">
                    <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                    <Badge :variant="verdictVariant(car.verdict)">{{ car.verdict }}</Badge>
                    <span v-if="car.year" class="text-sm text-gray-500">{{ car.year }}</span>
                </div>

                <!-- IEDMT estimation warning -->
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
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

                <!-- Photos (read-only gallery) -->
                <div v-if="car.photos?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_photos') }}</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-6 md:grid-cols-4">
                        <a v-for="photo in car.photos" :key="photo.id" :href="`/storage/${photo.url}`" target="_blank" rel="noopener" class="group relative overflow-hidden rounded-lg">
                            <img :src="`/storage/${photo.url}`" :alt="photo.photo_type" class="h-32 w-full object-cover transition group-hover:scale-105" loading="lazy" />
                            <span class="absolute bottom-2 left-2 rounded bg-black/70 px-2 py-0.5 text-xs text-white">{{ photo.photo_type }}</span>
                        </a>
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
            </div>
        </div>

        <footer class="mt-12 border-t border-gray-200 bg-white py-6">
            <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
                &copy; {{ new Date().getFullYear() }} Importnex. {{ t('marketplace_show.footer_rights') }}
            </div>
        </footer>
    </div>
</template>
