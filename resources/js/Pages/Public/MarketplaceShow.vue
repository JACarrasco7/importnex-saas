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

const props = defineProps({
    car: Object,
    derived: Object,
});

const { currency, date, trafficLightVariant, verdictVariant, confidenceVariant } = useFormat();

const costItems = [
    { key: 'purchase_price', label: 'Purchase price' },
    { key: 'new_price', label: 'New price' },
    { key: 'transport', label: 'Transport' },
    { key: 'itv_fee', label: 'ITV fee' },
    { key: 'coc_fee', label: 'COC fee' },
    { key: 'dgt_fees', label: 'DGT fees' },
    { key: 'professional_fees', label: 'Professional fees' },
    { key: 'deposit', label: 'Deposit' },
];

const specItems = [
    { key: 'brand', label: 'Brand' },
    { key: 'model', label: 'Model' },
    { key: 'year', label: 'Year' },
    { key: 'mileage', label: 'Mileage', suffix: ' km' },
    { key: 'fuel', label: 'Fuel' },
    { key: 'transmission', label: 'Transmission' },
    { key: 'cv', label: 'Power', suffix: ' CV' },
    { key: 'co2', label: 'CO2', suffix: ' g/km' },
    { key: 'color', label: 'Color' },
    { key: 'vin', label: 'VIN' },
];

const ratingIcon = (r) => ({
    favorable: CheckCircleIcon, neutral: MinusCircleIcon, unfavorable: XCircleIcon,
}[r] || MinusCircleIcon);

const researchAspectLabels = {
    common_issues: 'Common issues',
    recalls: 'Recalls',
    market_price: 'Market price',
    reliability: 'Reliability',
    spain_homologation: 'Spain homologation',
    dgt_label: 'DGT environmental label',
    insurance_estimate: 'Insurance estimate',
    parts_maintenance: 'Parts & maintenance',
    unit_specific: 'Unit-specific',
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
    if (ratio <= 1.00) return { label: 'Below market', variant: 'success', ratio };
    if (ratio <= 1.05) return { label: 'At market', variant: 'warning', ratio };
    return { label: 'Above market', variant: 'danger', ratio };
});
</script>

<template>
    <Head :title="`${car.brand} ${car.model} - Marketplace`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50">
        <!-- Public header -->
        <header class="border-b border-gray-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link :href="route('marketplace.index')" class="flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900">
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to Marketplace
                </Link>
                <a :href="route('login')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Sign in</a>
            </div>
        </header>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="border-b border-gray-200 pb-4">
                    <h1 class="text-3xl font-bold text-gray-900">{{ car.brand }} {{ car.model }}</h1>
                    <p class="mt-1 text-sm text-gray-500">VIN {{ car.vin || 'N/A' }}</p>
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
                        <p class="font-semibold">IEDMT is an estimate.</p>
                        <p class="mt-1 text-amber-800">
                            Hacienda calculates the registration tax on its official tables of market value, not on the price you paid.
                            Current estimate:
                            <span class="font-mono font-semibold">{{ currency(derived?.iedmt) }}</span>
                            &middot; Total all-in:
                            <span class="font-mono font-semibold">{{ currency(derived?.total_cost) }}</span>
                        </p>
                    </div>
                </div>

                <!-- Location -->
                <div v-if="car.lat && car.lng" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Location</h3>
                    </div>
                    <div class="p-6">
                        <MapaLeaflet :lat="car.lat" :lng="car.lng" :marker-text="`${car.brand} ${car.model} - ${car.city || ''}`" height="300px" />
                    </div>
                </div>

                <!-- Technical specs -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Technical specs</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                        <div v-for="spec in specItems" :key="spec.key">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ spec.label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900" :class="{ 'font-mono text-xs': spec.key === 'vin' }">{{ car[spec.key] || 'N/A' }}{{ spec.suffix || '' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Costs -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Costs</h3>
                        <span class="text-sm text-gray-500">Total all-in: <span class="font-mono font-semibold text-gray-900">{{ currency(derived?.total_cost) }}</span></span>
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
                        <h3 class="text-base font-semibold text-gray-900">Investigation</h3>
                        <span v-if="derived?.research_gaps?.length" class="text-xs font-medium text-amber-700">
                            {{ derived.research_gaps.length }} aspect{{ derived.research_gaps.length === 1 ? '' : 's' }} pending
                        </span>
                    </div>

                    <!-- Verdict -->
                    <div v-if="car.verdict" class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge :variant="verdictVariant(car.verdict)" dot size="lg">{{ car.verdict }}</Badge>
                            <Badge v-if="car.verdict_confidence" :variant="confidenceVariant(car.verdict_confidence)">
                                Confidence: {{ car.verdict_confidence }}
                            </Badge>
                            <span v-if="car.verdict_at" class="text-xs text-gray-500">
                                {{ date(car.verdict_at) }}
                            </span>
                        </div>
                        <p v-if="car.verdict_reasoning" class="mt-3 text-sm text-gray-700">{{ car.verdict_reasoning }}</p>
                        <p v-if="car.verdict_changes" class="mt-2 text-xs italic text-gray-600">
                            <span class="font-semibold not-italic text-gray-700">What would change this:</span>
                            {{ car.verdict_changes }}
                        </p>
                    </div>

                    <div v-if="!car.verdict && !derived?.research_gaps?.length" class="px-6 py-8 text-center text-sm text-gray-500">
                        No valuation yet available.
                    </div>

                    <!-- Balance pros / cons -->
                    <div v-if="(car.pros?.length || car.cons?.length)" class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-green-700">In favor ({{ car.pros?.length || 0 }})</h4>
                            <ul v-if="car.pros?.length" class="space-y-2">
                                <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 p-3">
                                    <CheckCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" />
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ pro.text }}</p>
                                        <span class="mt-1 inline-block text-xs font-medium uppercase text-green-700">{{ pro.weight }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-sm italic text-gray-500">No points in favor.</p>
                        </div>
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-red-700">Against ({{ car.cons?.length || 0 }})</h4>
                            <ul v-if="car.cons?.length" class="space-y-2">
                                <li v-for="(con, i) in car.cons" :key="i" class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3">
                                    <XCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-600" />
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ con.text }}</p>
                                        <span class="mt-1 inline-block text-xs font-medium uppercase text-red-700">{{ con.weight }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-sm italic text-gray-500">No points against.</p>
                        </div>
                    </div>

                    <!-- 9 research aspects -->
                    <div class="border-t border-gray-200 px-6 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">9 research aspects</h4>
                        <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <li v-for="aspect in aspects" :key="aspect.key" class="rounded-lg border p-3"
                                :class="aspect.missing ? 'border-dashed border-gray-300 bg-gray-50' : 'border-gray-200 bg-white'">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900">{{ aspect.label }}</p>
                                    <component v-if="!aspect.missing" :is="ratingIcon(aspect.rating)" class="h-4 w-4 flex-shrink-0"
                                        :class="{
                                            'text-green-600': aspect.rating === 'favorable',
                                            'text-red-600': aspect.rating === 'unfavorable',
                                            'text-gray-500': aspect.rating === 'neutral' || !aspect.rating,
                                        }" />
                                </div>
                                <p v-if="aspect.missing" class="mt-2 text-xs italic text-gray-500">Not yet investigated.</p>
                                <template v-else>
                                    <p v-if="aspect.finding" class="mt-2 text-sm text-gray-700">{{ aspect.finding }}</p>
                                    <a v-if="aspect.source" :href="aspect.source" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-500">
                                        <LinkIcon class="h-3 w-3" />
                                        Source
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
                        <h3 class="text-base font-semibold text-gray-900">Market comparables</h3>
                        <Badge v-if="marketPosition" :variant="marketPosition.variant">
                            {{ marketPosition.label }} ({{ (marketPosition.ratio * 100).toFixed(1) }}%)
                        </Badge>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Average</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_avg) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Minimum</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_min) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Maximum</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_max) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estimated saving</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-green-700">{{ currency(car.estimated_saving) }}</dd>
                        </div>
                    </div>

                    <div v-if="car.comparables_list?.length" class="border-t border-gray-200 px-6 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Comparables ({{ car.comparables_list.length }})</h4>
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
                                    <a v-if="comp.url || comp.u" :href="comp.url || comp.u" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-500">
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
                        <h3 class="text-base font-semibold text-gray-900">Import progress</h3>
                    </div>
                    <div class="bg-gray-50 px-6 py-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700">Milestones</h4>
                            <span class="text-sm font-mono font-semibold text-gray-900">
                                {{ derived?.milestones_progress?.completed || 0 }} / {{ derived?.milestones_progress?.total || 0 }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full bg-indigo-500 transition-all"
                                :style="{ width: derived?.milestones_progress?.total ? ((derived.milestones_progress.completed / derived.milestones_progress.total) * 100) + '%' : '0%' }" />
                        </div>
                        <p class="mt-3 text-sm text-gray-600">
                            {{ derived?.inspections_progress?.completed || 0 }} / {{ derived?.inspections_progress?.total || 0 }} inspection items completed.
                        </p>
                    </div>
                </div>

                <!-- Photos (read-only gallery) -->
                <div v-if="car.photos?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Photos</h3>
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
                        <h3 class="text-base font-semibold text-gray-900">Notes</h3>
                    </div>
                    <div class="p-6">
                        <pre class="whitespace-pre-wrap font-sans text-sm text-gray-700">{{ car.notes }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-12 border-t border-gray-200 bg-white py-6">
            <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
                &copy; {{ new Date().getFullYear() }} Importnex. All rights reserved.
            </div>
        </footer>
    </div>
</template>
