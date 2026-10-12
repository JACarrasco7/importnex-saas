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
    ShareIcon,
    ClipboardDocumentIcon,
} from '@heroicons/vue/24/outline';
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
    publicProfileUrl: { type: String, default: null },
    isSharedProfile: { type: Boolean, default: false },
    costBreakdown: { type: Object, default: () => ({ car_price: 0, transport: 0, processing: 0, iedmt: 0, honorarios: 1000, total: 0, has_data: false }) },
});

const { t } = useTranslations();
const { currency, date } = useFormat();

// Compartir: copia la URL pública al portapapeles y muestra confirmación.
const shareCopied = ref(false);
const shareError = ref('');
const shareUrl = computed(() => props.publicProfileUrl || (typeof window !== 'undefined' ? window.location.href : ''));

const copyShareUrl = async () => {
    shareError.value = '';
    if (! shareUrl.value) {
        return;
    }
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(shareUrl.value);
        } else if (typeof window !== 'undefined') {
            // Fallback para navegadores sin clipboard API.
            const ta = document.createElement('textarea');
            ta.value = shareUrl.value;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        shareCopied.value = true;
        setTimeout(() => { shareCopied.value = false; }, 2000);
    } catch (e) {
        shareError.value = t('marketplace.share_error', { default: 'No se pudo copiar. Selecciona y copia manualmente.' });
    }
};

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

// El desglose "aproximado" con honorarios JJ Import se calcula en PHP
// (App\Support\CostBreakdown) y llega como prop `costBreakdown`.
// Reglas: 1.000 EUR por defecto; 1.500 EUR si el coche >= 30.000 EUR (12-oct-2026).
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

                    <!-- Banner si el visitante llega por link de share /p/{token} -->
                    <div v-if="isSharedProfile" class="flex items-start gap-3 rounded-2xl border border-estoril-200 bg-estoril-50 p-4 dark:border-estoril-800 dark:bg-estoril-900/30">
                        <ShareIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                        <div>
                            <p class="text-sm font-semibold text-estoril-900 dark:text-estoril-100">
                                {{ t('marketplace.shared_profile_badge', { default: 'Perfil público' }) }}
                            </p>
                            <p class="mt-1 text-sm text-estoril-800 dark:text-estoril-200">
                                {{ t('marketplace.shared_profile_desc', { default: 'Estás viendo el perfil público de este coche, compartido por JJ Import Motors.' }) }}
                            </p>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="border-b border-gray-200 pb-4 dark:border-asphalt-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ car.brand }} {{ car.model }}</h1>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ t('marketplace_show.vin_label') }} {{ car.vin || t('marketplace.not_available') }}</p>
                            </div>
                            <!-- Botón Compartir: copia la URL pública /p/{token} al portapapeles -->
                            <button
                                v-if="publicProfileUrl"
                                type="button"
                                @click="copyShareUrl"
                                class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-estoril-200 bg-white px-4 py-2 text-sm font-semibold text-estoril-800 shadow-sm transition hover:bg-estoril-50 dark:border-asphalt-700 dark:bg-asphalt-800 dark:text-estoril-300 dark:hover:bg-asphalt-700"
                                :aria-label="t('marketplace.share', { default: 'Compartir' })"
                            >
                                <component :is="shareCopied ? ClipboardDocumentIcon : ShareIcon" class="h-4 w-4" />
                                <span>{{ shareCopied ? t('marketplace.copied', { default: 'Enlace copiado' }) : t('marketplace.share', { default: 'Compartir' }) }}</span>
                            </button>
                        </div>
                        <p v-if="shareError" class="mt-2 text-xs text-rose-600">{{ shareError }}</p>
                    </div>
                <!-- Status bar (badges internos ocultos para cliente final;
                     las vistas admin los siguen mostrando en Cars/Index.) -->
                <div class="flex flex-wrap items-center gap-3">
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

                <!-- Location: el mapa Leaflet se quitó de la ficha publica
                     (decision 12-oct-2026: solo se muestra en /tracking). -->

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

                <!-- Costs: desglose APROXIMADO con honorarios JJ Import
                     (decision 12-oct-2026: 1k base, 1.5k si el coche >= 30k).
                     Todos los importes son aproximados, NO costes finales. -->
                <div v-if="costBreakdown?.has_data" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_costs') }}</h3>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-amber-700 ring-1 ring-amber-200">
                            {{ t('marketplace_show.cost_approximate', { default: 'Aproximado' }) }}
                        </span>
                    </div>
                    <div class="space-y-3 p-6">
                        <!-- Coche -->
                        <div class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_purchase') }}</span>
                            <span class="font-mono text-sm font-semibold text-gray-900">{{ currency(costBreakdown.car_price) }}</span>
                        </div>
                        <!-- Gastos (transporte, ITV, COC, DGT, IEDMT) — todos con ~ -->
                        <div v-if="costBreakdown.transport > 0" class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_transport') }}</span>
                            <span class="font-mono text-sm text-gray-600">~ {{ currency(costBreakdown.transport) }}</span>
                        </div>
                        <div v-if="costBreakdown.processing > 0" class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_processing_grouped', { default: 'ITV + COC + DGT' }) }}</span>
                            <span class="font-mono text-sm text-gray-600">~ {{ currency(costBreakdown.processing) }}</span>
                        </div>
                        <div v-if="costBreakdown.iedmt > 0" class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_iedmt', { default: 'IEDMT (estimado)' }) }}</span>
                            <span class="font-mono text-sm text-gray-600">~ {{ currency(costBreakdown.iedmt) }}</span>
                        </div>
                        <!-- Honorarios JJ Import — 1k base, 1.5k si coche >= 30k -->
                        <div class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <div>
                                <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_honorarios_label', { default: 'Honorarios JJ Import' }) }}</span>
                                <p class="text-[11px] text-gray-500">{{ t('marketplace_show.cost_honorarios_note', { default: '1.000 \u20AC (1.500 \u20AC si el coche es >= 30.000 \u20AC)' }) }}</p>
                            </div>
                            <span class="font-mono text-sm font-semibold text-estoril-800">{{ currency(costBreakdown.honorarios) }}</span>
                        </div>
                        <!-- Total aproximado -->
                        <div class="flex items-baseline justify-between gap-4 pt-2">
                            <span class="text-sm font-bold text-gray-900">{{ t('marketplace_show.cost_total_approx', { default: 'Total aproximado' }) }}</span>
                            <span class="font-mono text-lg font-extrabold text-estoril-800">~ {{ currency(costBreakdown.total) }}</span>
                        </div>
                        <!-- Disclaimer -->
                        <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-[11px] leading-relaxed text-gray-600">
                            {{ t('marketplace_show.cost_disclaimer', { default: 'Valoracion aproximada. NO son costes finales: los gastos reales pueden variar segun el vehiculo, el transporte y las tasas vigentes. Confirmamos por escrito antes de la reserva.' }) }}
                        </p>
                    </div>
                </div>

                <!-- Puntos a favor (decision 12-oct-2026: la valoracion,
                     contras y 9 research aspects NO se muestran al cliente) -->
                <div v-if="car.pros?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_pros_title', { default: 'Puntos a favor' }) }}</h3>
                    </div>

                    <!-- Verdict: la valoracion textual NO se muestra al cliente
                         (decision 12-oct-2026). El badge Buy/Discard es interno
                         del equipo; el texto de valoracion tambien. El cliente
                         solo ve lo que aparece en las secciones externas. -->
                    <div v-if="derived?.research_gaps?.length" class="px-6 py-4 text-sm text-amber-700">
                        {{ derived.research_gaps.length === 1
                            ? t('marketplace_show.aspects_pending', { count: derived.research_gaps.length })
                            : t('marketplace_show.aspects_pending_plural', { count: derived.research_gaps.length }) }}
                    </div>

                    <!-- Solo pros (decision 12-oct-2026: al cliente solo lo bueno) -->
                    <div v-if="car.pros?.length" class="p-6">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-estoril-800">{{ t('marketplace_show.in_favor', { count: car.pros?.length || 0 }) }}</h4>
                        <ul class="space-y-2">
                            <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-estoril-200 bg-estoril-50 p-3">
                                <CheckCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-estoril-700" />
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900">{{ pro.text }}</p>
                                    <span class="mt-1 inline-block text-xs font-medium uppercase text-estoril-800">{{ pro.weight }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <p v-else-if="!car.pros?.length" class="p-6 text-sm italic text-gray-500">{{ t('marketplace_show.no_points_in_favor') }}</p>
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

                <!-- Import progress y notes: secciones internas (admin), no se
                     muestran al cliente (decision 12-oct-2026). -->

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
