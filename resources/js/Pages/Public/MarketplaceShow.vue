<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    EyeIcon,
    CheckCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ShareIcon,
    ClipboardDocumentIcon,
    MapPinIcon,
    ChatBubbleLeftRightIcon,
    PhoneIcon,
    ShieldCheckIcon,
    CheckBadgeIcon,
    TruckIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';
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

const { t, locale } = useTranslations();
const { currency } = useFormat();

const fmt = (n) => {
    const num = Number(n);
    if (!Number.isFinite(num)) return '\u2014';
    const loc = locale.value === 'en' ? 'en-US' : 'es-ES';
    return new Intl.NumberFormat(loc, { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(num);
};

const fmtNumber = (n) => {
    const num = Number(n);
    if (!Number.isFinite(num)) return '\u2014';
    const loc = locale.value === 'en' ? 'en-US' : 'es-ES';
    return new Intl.NumberFormat(loc).format(num);
};

// Compartir: copia la URL publica al portapapeles.
const shareCopied = ref(false);
const shareError = ref('');
const shareUrl = computed(() => props.publicProfileUrl || (typeof window !== 'undefined' ? window.location.href : ''));
const copyShareUrl = async () => {
    shareError.value = '';
    if (! shareUrl.value) return;
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(shareUrl.value);
        } else if (typeof window !== 'undefined') {
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
        shareError.value = t('marketplace.share_error', { default: 'No se pudo copiar.' });
    }
};

// Galeria: indice de la foto activa.
const activePhotoIndex = ref(0);
const activePhoto = computed(() => (props.car?.photos || [])[activePhotoIndex.value] || null);
const totalPhotos = computed(() => (props.car?.photos || []).length);
const goPrev = () => {
    if (totalPhotos.value === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value - 1 + totalPhotos.value) % totalPhotos.value;
};
const goNext = () => {
    if (totalPhotos.value === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value + 1) % totalPhotos.value;
};

// Scroll para la barra CTA fija.
const scrollY = ref(0);
const onScroll = () => { scrollY.value = window.scrollY; };
onMounted(() => { window.addEventListener('scroll', onScroll, { passive: true }); });
onUnmounted(() => { window.removeEventListener('scroll', onScroll); });

// Linea de datos clave para el hero.
const heroData = computed(() => {
    const parts = [];
    if (props.car?.year) parts.push(String(props.car.year));
    if (props.car?.mileage) parts.push(fmtNumber(props.car.mileage) + ' km');
    if (props.car?.fuel) parts.push(props.car.fuel);
    if (props.car?.transmission) parts.push(props.car.transmission);
    return parts.join(' \u00b7 ');
});

// Origen (Alemania / Espana / verificado).
const originLabel = computed(() => {
    const pais = String(props.car?.pais_origen || '').toLowerCase();
    if (pais.includes('alem') || pais === 'de') {
        return t('dossier.origin_germany', { default: 'Importado desde Alemania' });
    }
    if (pais.includes('espa') || pais === 'es') {
        return t('dossier.origin_spain', { default: 'Localizado en Espana' });
    }
    return t('dossier.origin_verified', { default: 'Origen verificado' });
});

// Precio titular: total estimado si existe, si no purchase_price.
const heroPrice = computed(() => {
    const total = props.derived?.total_cost;
    if (total && total > 0) return fmt(total);
    if (props.car?.purchase_price) return fmt(props.car.purchase_price);
    return null;
});

// Rango de mercado (barra).
const marketRange = computed(() => {
    const min = Number(props.car?.market_min);
    const max = Number(props.car?.market_max);
    const avg = Number(props.car?.market_avg);
    const our = Number(props.derived?.total_cost || props.car?.purchase_price);
    if (!Number.isFinite(min) || !Number.isFinite(max) || max <= min) return null;
    let pos = null;
    if (Number.isFinite(our) && our > 0) {
        pos = ((our - min) / (max - min)) * 100;
        pos = Math.max(2, Math.min(98, pos));
    }
    return { min, max, avg: Number.isFinite(avg) ? avg : null, pos };
});

// Equipamiento.
const equipment = computed(() => {
    const eq = props.car?.equipment;
    if (!Array.isArray(eq)) return [];
    return eq.filter((e) => typeof e === 'string' && e.trim() !== '');
});

// Pasos del proceso (5 pasos genericos del servicio).
const processSteps = computed(() => [
    { when: t('dossier.step_week_0'), what: t('dossier.step0') },
    { when: t('dossier.step_week_1'), what: t('dossier.step1') },
    { when: t('dossier.step_weeks_2_3'), what: t('dossier.step2') },
    { when: t('dossier.step_weeks_3_4'), what: t('dossier.step3') },
    { when: t('dossier.step_week_4'), what: t('dossier.step4') },
]);

// Incluido / no incluido.
const includeItems = computed(() => [
    t('dossier.include_search'),
    t('dossier.include_negotiation'),
    t('dossier.include_transport'),
    t('dossier.include_itv'),
    t('dossier.include_history'),
    t('dossier.include_delivery'),
]);
const noIncludeItems = computed(() => [
    t('dossier.no_include_insurance'),
    t('dossier.no_include_tax'),
    t('dossier.no_include_maintenance'),
    t('dossier.no_include_warranty'),
]);

// Estado del semaforo (visible en la barra lateral del hero).
const stateLabels = {
    Located: 'Localizado',
    Valuing: 'En valoracion',
    Offered: 'Oferta enviada',
    Reserved: 'Reservado',
    Purchased: 'Comprado',
    In_transit: 'En transito',
    Processing: 'En tramites',
    Delivered: 'Entregado',
};
const stateLabel = computed(() => stateLabels[props.car?.status] || t('dossier.state_default', { default: 'Disponible para gestion' }));

// URL WhatsApp con texto predefinido.
const whatsappUrl = computed(() => {
    const text = t('dossier.whatsapp_text', { brand: props.car?.brand || '', model: props.car?.model || '' });
    return 'https://wa.me/34675701439?text=' + encodeURIComponent(text);
});

// Especificaciones tecnicas (grid limpio).
const specItems = computed(() => [
    { key: 'brand', label: t('marketplace_show.brand'), value: props.car?.brand },
    { key: 'model', label: t('marketplace_show.model'), value: props.car?.model },
    { key: 'version', label: t('dossier.version', { default: 'Version' }), value: props.car?.version },
    { key: 'year', label: t('marketplace_show.year'), value: props.car?.year },
    { key: 'mileage', label: t('marketplace_show.mileage'), value: props.car?.mileage ? fmtNumber(props.car.mileage) + ' km' : null },
    { key: 'fuel', label: t('marketplace_show.fuel'), value: props.car?.fuel },
    { key: 'transmission', label: t('marketplace_show.transmission'), value: props.car?.transmission },
    { key: 'cv', label: t('marketplace_show.power'), value: props.car?.cv ? props.car.cv + ' CV' : null },
    { key: 'color', label: t('marketplace_show.color'), value: props.car?.color },
    { key: 'doors', label: t('dossier.doors', { default: 'Puertas' }), value: props.car?.doors },
    { key: 'vin', label: t('marketplace_show.vin'), value: props.car?.vin, mono: true },
]);
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
                     contras y 9 research aspects NO se muestran al cliente.
                     Solo pros, sin "EN CONTRA", sin alertas internas.) -->
                <div v-if="car.pros?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('marketplace_show.section_pros_title', { default: 'Puntos a favor' }) }}</h3>
                    </div>
                    <ul class="space-y-2 p-6">
                        <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-estoril-200 bg-estoril-50 p-3">
                            <CheckCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-estoril-700" />
                            <p class="flex-1 text-sm text-gray-900">
                                {{ typeof pro === 'string' ? pro : pro.text }}
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Comparables de mercado: el listado interno (coches similares
                     con km/pais/enlace) NO se muestra al cliente (decision
                     12-oct-2026, mismo argumento que pros/cons: info interna).
                     La barra de rango + posicion sigue en la seccion "mercado"
                     mas abajo, que es lo que el dossier muestra. -->

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
