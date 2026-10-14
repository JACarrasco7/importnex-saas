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
    DocumentArrowDownIcon,
    ExclamationTriangleIcon,
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
    dossierUrl: { type: String, default: null },
    whatsappNumber: { type: String, default: '34675701439' },
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

// Link de WhatsApp pre-rellenado con un mensaje contextual sobre ESTE
// coche concreto: el cliente pregunta por este vehiculo y adjunta el link
// al perfil publico para que el comercial sepa de que coche hablan.
const whatsappLink = computed(() => {
    const phone = (props.whatsappNumber || '').replace(/\D/g, '');
    if (!phone || !props.car) return null;
    const brand = props.car.brand || '';
    const model = props.car.model || '';
    const version = props.car.version || '';
    const ref = props.publicProfileUrl || props.dossierUrl || '';
    const carLine = [brand, model, version].filter(Boolean).join(' ').trim();
    const isEs = (locale.value || 'es').startsWith('es');
    const text = isEs
        ? `Hola, estoy interesado en el ${carLine || 'coche'} que he visto en su web. ¿Me pueden dar más información?\n\n${ref}`
        : `Hi, I'm interested in the ${carLine || 'car'} I saw on your website. Could you give me more information?\n\n${ref}`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
});

// Linea de datos clave para el hero.
// La BD guarda valores como "Gasoline"/"Diesel"/"Hybrid"/"Electric"/"Manual"/"Automatic"
// (ingles, capital). Los mapeamos a las claves i18n ES/EN; si la clave
// no existe (valor raro tipo "Hibrido enchufable"), devolvemos el valor crudo.
const fuelLabel = computed(() => {
    const f = props.car?.fuel;
    if (!f) return null;
    const key = 'cars.fuel_options.' + f.toLowerCase();
    const translated = t(key, {}, null);
    // Si la traduccion no existe (devuelve la propia key o null),
    // pasamos el valor crudo como fallback.
    return translated && translated !== key ? translated : f;
});
const transmissionLabel = computed(() => {
    const tr = props.car?.transmission;
    if (!tr) return null;
    // Para transmission, si el valor es largo/descriptivo
    // (ej. "Automatico DSG de 7 marchas con levas"), no se traduce y se
    // devuelve tal cual. Solo intentamos i18n si es una clave simple.
    const key = 'cars.transmission_options.' + tr.toLowerCase();
    const translated = t(key, {}, null);
    return translated && translated !== key ? translated : tr;
});

const heroData = computed(() => {
    const parts = [];
    if (props.car?.year) parts.push(String(props.car.year));
    if (props.car?.mileage) parts.push(fmtNumber(props.car.mileage) + ' km');
    if (fuelLabel.value) parts.push(fuelLabel.value);
    if (transmissionLabel.value) parts.push(transmissionLabel.value);
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

// Precio titular: total estimado segun la regla de negocio
// (CostBreakdown::forCar). Antes usaba derived.total_cost que mezclaba
// el campo professional_fees (legacy) — daba un total 500 EUR por debajo
// del desglose visible. Ahora SIEMPRE usamos costBreakdown.total para
// que hero y desglose cuadren.
const heroPrice = computed(() => {
    const total = props.costBreakdown?.total;
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

// Estado del semaforo (visible en la barra lateral del hero).
// Solo el cliente lo ve asi; internamente el estado se llama "Located"
// (encontrado/disponible para gestion). El resto de estados ya no se
// muestran en la ficha publica (son admin-only), pero los dejamos
// mapeados por si el flujo cambia.
const stateLabels = {
    Located: 'Disponible',
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

// Especificaciones tecnicas (mismo orden que el dossier car-dossier.blade.php).
// Sin VIN, sin CO2, sin puertas â€” solo lo que el dossier muestra: Marca, Modelo,
// Version, Ano, Kilometros, Combustible, Cambio, Traccion.
const drivetrainLabel = computed(() => {
    const d = String(props.car?.drivetrain || '').toUpperCase();
    if (d === 'FWD') return t('dossier.drivetrain_fwd', { default: 'Delantera' });
    if (d === 'RWD') return t('dossier.drivetrain_rwd', { default: 'Trasera' });
    if (d === 'AWD' || d === '4WD') return t('dossier.drivetrain_awd', { default: 'Total (4x4)' });
    return null;
});

const specItems = computed(() => [
    { key: 'brand', label: t('marketplace_show.brand'), value: props.car?.brand },
    { key: 'model', label: t('marketplace_show.model'), value: props.car?.model },
    { key: 'version', label: t('marketplace_show.version'), value: props.car?.version },
    { key: 'year', label: t('marketplace_show.year'), value: props.car?.year },
    { key: 'mileage', label: t('marketplace_show.mileage'), value: props.car?.mileage ? fmtNumber(props.car.mileage) + ' km' : null },
    { key: 'fuel', label: t('marketplace_show.fuel'), value: fuelLabel.value },
    { key: 'transmission', label: t('marketplace_show.transmission'), value: transmissionLabel.value },
    { key: 'drivetrain', label: t('marketplace_show.drivetrain', { default: 'Tracción' }), value: drivetrainLabel.value },
]);
</script>

<template>
    <Head :title="`${car.brand} ${car.model} - ${t('public.marketplace_title')}`" />

    <MarketplaceLayout>
        <div class="min-h-screen bg-gradient-to-br from-platinum-100 via-white to-estoril-50 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800">

            <!-- Banner si el visitante llega por link de share /p/{token} -->
            <div v-if="isSharedProfile" class="mx-auto mt-4 max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-start gap-3 rounded-2xl border border-estoril-200 bg-estoril-50 p-4 dark:border-estoril-800 dark:bg-estoril-900/30">
                    <ShareIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                    <div>
                        <p class="text-sm font-semibold text-estoril-900 dark:text-estoril-100">
                            {{ t('marketplace.shared_profile_badge', { default: 'Perfil publico' }) }}
                        </p>
                        <p class="mt-1 text-sm text-estoril-800 dark:text-estoril-200">
                            {{ t('marketplace.shared_profile_desc', { default: 'Estas viendo el perfil publico de este coche, compartido por JJ Import Motors.' }) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">

                <!-- HERO -->
                <section class="overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-5 md:p-8">
                        <div class="md:col-span-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-estoril-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-estoril-700 ring-1 ring-estoril-200 dark:bg-estoril-900/30 dark:text-estoril-300 dark:ring-estoril-800">
                                    <ShieldCheckIcon class="h-3.5 w-3.5" />
                                    {{ t('dossier.badge_verified', { default: 'Verificado' }) }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800">
                                    {{ stateLabel }}
                                </span>
                                <span v-if="car.marketplace_views > 0" :title="t('marketplace.visits_count_tooltip')" class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-3 py-1 text-[11px] font-medium text-gray-600 ring-1 ring-gray-200 dark:bg-asphalt-700/50 dark:text-gray-300 dark:ring-asphalt-600">
                                    <EyeIcon class="h-3.5 w-3.5" />
                                    {{ fmtNumber(car.marketplace_views) }}
                                </span>
                            </div>
                            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                                {{ car.brand }}
                                <span class="block bg-gradient-to-br from-estoril-700 to-estoril-900 bg-clip-text text-transparent dark:from-estoril-300 dark:to-estoril-500">
                                    {{ car.model }}
                                </span>
                            </h1>
                            <p v-if="car.version" class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ car.version }}</p>
                            <p v-if="heroData" class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ heroData }}</p>
                            <div v-if="heroPrice" class="mt-5 inline-block rounded-2xl bg-gradient-to-br from-estoril-700 to-estoril-900 px-5 py-3 text-white shadow-lg">
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-estoril-100">
                                    {{ t('dossier.price_estimated', { default: 'Puesto en Huelva - aprox.' }) }}
                                </p>
                                <p class="mt-1 text-2xl font-extrabold tracking-tight">{{ heroPrice }}</p>
                            </div>
                            <div class="mt-5 flex flex-wrap gap-2">
                                <a :href="whatsappUrl" target="_blank" rel="noopener" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 sm:flex-none">
                                    <ChatBubbleLeftRightIcon class="h-4 w-4" />
                                    {{ t('dossier.cta_whatsapp', { default: 'Me interesa' }) }}
                                </a>
                                <a href="tel:+34675701439" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 sm:flex-none dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                                    <PhoneIcon class="h-4 w-4" />
                                    {{ t('dossier.cta_call', { default: 'Llamar' }) }}
                                </a>
                                <button v-if="publicProfileUrl" type="button" @click="copyShareUrl" class="inline-flex items-center justify-center gap-2 rounded-lg border border-estoril-200 bg-white px-4 py-2.5 text-sm font-semibold text-estoril-800 shadow-sm transition hover:bg-estoril-50 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-estoril-300 dark:hover:bg-asphalt-700" :aria-label="t('marketplace.share', { default: 'Compartir' })">
                                    <component :is="shareCopied ? ClipboardDocumentIcon : ShareIcon" class="h-4 w-4" />
                                    <span class="hidden sm:inline">{{ shareCopied ? t('marketplace.copied', { default: 'Copiado' }) : t('marketplace.share', { default: 'Compartir' }) }}</span>
                                </button>
                            </div>
                            <p v-if="shareError" class="mt-2 text-xs text-rose-600">{{ shareError }}</p>
                        </div>
                        <div class="md:col-span-3">
                            <div v-if="activePhoto" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-asphalt-200 to-gray-300 shadow-md">
                                <img :src="photoUrl(activePhoto)" :alt="`${car.brand} ${car.model}`" class="aspect-[4/3] w-full object-cover" />
                                <template v-if="totalPhotos > 1">
                                    <button type="button" @click="goPrev" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white shadow-sm transition hover:bg-black/70" :aria-label="t('marketplace_show.prev_photo', { default: 'Anterior' })">
                                        <ChevronLeftIcon class="h-5 w-5" />
                                    </button>
                                    <button type="button" @click="goNext" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white shadow-sm transition hover:bg-black-70" :aria-label="t('marketplace_show.next_photo', { default: 'Siguiente' })">
                                        <ChevronRightIcon class="h-5 w-5" />
                                    </button>
                                    <span class="absolute bottom-3 right-3 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold text-white">
                                        {{ activePhotoIndex + 1 }} / {{ totalPhotos }}
                                    </span>
                                </template>
                            </div>
                            <div v-else class="flex aspect-[4/3] w-full flex-col items-center justify-center rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 text-gray-400 shadow-md">
                                <span class="text-sm font-semibold uppercase tracking-wider">{{ car.brand }} {{ car.model }}</span>
                            </div>
                            <div v-if="totalPhotos > 1" class="mt-3 flex gap-2 overflow-x-auto">
                                <button v-for="(photo, idx) in car.photos" :key="photo.id || idx" type="button" @click="activePhotoIndex = idx" :class="['h-14 w-20 shrink-0 overflow-hidden rounded-md ring-2 transition', idx === activePhotoIndex ? 'ring-estoril-600' : 'ring-transparent hover:ring-gray-300']">
                                    <img :src="photoUrl(photo)" :alt="`miniatura ${idx + 1}`" class="h-full w-full object-cover" />
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- CTA contextual: WhatsApp sobre este coche + ver ficha completa -->
                <section class="flex flex-col items-start justify-between gap-3 rounded-2xl bg-gradient-to-br from-asphalt-900 to-estoril-900 p-5 text-white shadow-sm sm:flex-row sm:items-center">
                    <div>
                        <p class="text-base font-semibold">
                            {{ t('marketplace_show.cta_request_title', { default: '¿Te interesa este coche?' }) }}
                        </p>
                        <p class="mt-1 text-sm text-estoril-100">
                            {{ t('marketplace_show.cta_request_desc', { default: 'Escríbenos por WhatsApp y te contamos todo sobre esta unidad. Sin compromiso.' }) }}
                        </p>
                    </div>
                    <div class="flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row">
                        <a
                            v-if="dossierUrl"
                            :href="dossierUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-white/20"
                        >
                            <DocumentArrowDownIcon class="h-4 w-4" />
                            {{ t('marketplace_show.cta_full_dossier', { default: 'Ver ficha completa' }) }}
                        </a>
                        <a
                            v-if="whatsappLink"
                            :href="whatsappLink"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-estoril-800 shadow-sm transition hover:bg-estoril-50"
                        >
                            <ChatBubbleLeftRightIcon class="h-4 w-4" />
                            {{ t('marketplace_show.cta_whatsapp', { default: 'Hablar por WhatsApp' }) }}
                        </a>
                    </div>
                </section>

                <!-- TRUST BAR -->
                <section class="grid grid-cols-2 gap-3 rounded-2xl bg-white/60 p-4 ring-1 ring-gray-200 sm:grid-cols-4 dark:bg-asphalt-800/60 dark:ring-asphalt-700">
                    <div class="flex items-start gap-2">
                        <ShieldCheckIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ originLabel }}</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400">{{ t('dossier.trust_history', { default: 'Historial verificado' }) }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <CheckBadgeIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ t('dossier.trust_inspection', { default: 'Inspeccion 9 puntos' }) }}</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400">{{ t('dossier.trust_inspection_desc', { default: 'Comprobado por nuestro equipo' }) }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <TruckIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ t('dossier.trust_process', { default: 'Tramitacion completa' }) }}</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400">{{ t('dossier.trust_process_desc', { default: 'ITV, COC, DGT y gestoria' }) }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <BanknotesIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ t('dossier.trust_manage', { default: 'Gestion integral' }) }}</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400">{{ t('dossier.trust_manage_desc', { default: 'Compra, transporte y tramites' }) }}</p>
                        </div>
                    </div>
                </section>

                <!-- PRECIO -->
                <section v-if="costBreakdown && costBreakdown.has_data" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('dossier.section_price_title', { default: 'Cuanto te costaria, puesto en Huelva' }) }}</h3>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-amber-700 ring-1 ring-amber-200">
                            {{ t('marketplace_show.cost_approximate', { default: 'Aproximado' }) }}
                        </span>
                    </div>
                    <div class="space-y-3 p-6">
                        <div class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_purchase') }}</span>
                            <span class="font-mono text-sm font-semibold text-gray-900">{{ fmt(costBreakdown.car_price) }}</span>
                        </div>
                        <div v-if="costBreakdown.transport > 0" class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_transport') }}</span>
                            <span class="font-mono text-sm text-gray-600">~ {{ fmt(costBreakdown.transport) }}</span>
                        </div>
                        <div v-if="costBreakdown.processing > 0" class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_processing_grouped', { default: 'ITV + COC + DGT' }) }}</span>
                            <span class="font-mono text-sm text-gray-600">~ {{ fmt(costBreakdown.processing) }}</span>
                        </div>
                        <div v-if="costBreakdown.iedmt > 0" class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_iedmt', { default: 'IEDMT (estimado)' }) }}</span>
                            <span class="font-mono text-sm text-gray-600">~ {{ fmt(costBreakdown.iedmt) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-dashed border-gray-200 pb-2">
                            <div>
                                <span class="text-sm text-gray-700">{{ t('marketplace_show.cost_honorarios_label', { default: 'Honorarios JJ Import' }) }}</span>
                                <p class="text-[11px] text-gray-500">{{ t('marketplace_show.cost_honorarios_note', { default: '1.000 EUR (1.500 EUR si el coche es >= 30.000 EUR)' }) }}</p>
                            </div>
                            <span class="font-mono text-sm font-semibold text-estoril-800">{{ fmt(costBreakdown.honorarios) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 pt-2">
                            <span class="text-sm font-bold text-gray-900">{{ t('marketplace_show.cost_total_approx', { default: 'Total aproximado' }) }}</span>
                            <span class="font-mono text-lg font-extrabold text-estoril-800">~ {{ fmt(costBreakdown.total) }}</span>
                        </div>
                        <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-[11px] leading-relaxed text-gray-600">
                            {{ t('marketplace_show.cost_disclaimer', { default: 'Valoracion aproximada. NO son costes finales: los gastos reales pueden variar segun el vehiculo, el transporte y las tasas vigentes. Confirmamos por escrito antes de la reserva.' }) }}
                        </p>
                    </div>
                </section>

                <!-- INCLUIDO EN EL PRECIO (no mostramos lo NO incluido, ya se habla con el cliente) -->
                <section v-if="includeItems && includeItems.length" class="rounded-2xl bg-gradient-to-br from-emerald-50 to-white p-6 ring-1 ring-emerald-200 dark:from-emerald-900/20 dark:to-asphalt-800 dark:ring-emerald-800">
                    <h3 class="flex items-center gap-2 text-base font-bold text-emerald-700 dark:text-emerald-300">
                        <CheckCircleIcon class="h-5 w-5" />
                        {{ t('dossier.section_included', { default: 'Incluido en el precio' }) }}
                    </h3>
                    <ul class="mt-3 space-y-2">
                        <li v-for="(item, i) in includeItems" :key="i" class="flex items-start gap-2 text-sm text-gray-800 dark:text-gray-200">
                            <span class="mt-0.5 text-emerald-600 dark:text-emerald-400">&#10003;</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </section>

                <!-- MERCADO -->
                <section v-if="marketRange" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-estoril-700 dark:text-estoril-300">
                        {{ t('dossier.section_market_eyebrow', { default: 'El mercado espanol' }) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                        {{ t('dossier.section_market_title', { default: 'Como esta de precio' }) }}
                    </h2>
                    <div class="mt-6">
                        <div class="relative h-2 rounded-full bg-gradient-to-r from-emerald-400 via-gray-300 to-amber-400">
                            <div v-if="marketRange.pos !== null" class="absolute -top-2 h-6 w-1 rounded bg-gray-900 shadow-md dark:bg-white" :style="{ left: marketRange.pos + '%' }">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-estoril-700 px-2 py-0.5 text-[10px] font-bold text-white shadow">
                                    {{ t('dossier.this_unit', { default: 'Esta unidad' }) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-gray-600 dark:text-gray-400">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">{{ fmt(marketRange.min) }}</p>
                                <p>{{ t('dossier.cheapest', { default: 'el mas barato' }) }}</p>
                            </div>
                            <div v-if="marketRange.avg" class="text-center">
                                <p class="font-bold text-gray-900 dark:text-white">{{ fmt(marketRange.avg) }}</p>
                                <p>{{ t('dossier.market_avg', { default: 'precio medio' }) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 dark:text-white">{{ fmt(marketRange.max) }}</p>
                                <p>{{ t('dossier.most_expensive', { default: 'el mas caro' }) }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- PUNTOS A FAVOR -->
                <section v-if="car.pros && car.pros.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-estoril-700 dark:text-estoril-300">
                        {{ t('dossier.section_pros_eyebrow', { default: 'Puntos a favor' }) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                        {{ t('dossier.section_pros_title', { default: 'Lo mejor de esta unidad' }) }}
                    </h2>
                    <ul class="mt-4 space-y-2">
                        <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-estoril-200 bg-estoril-50 p-3 dark:border-estoril-800 dark:bg-asphalt-800">
                            <CheckCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                            <p class="flex-1 text-sm text-gray-900 dark:text-gray-200">
                                {{ typeof pro === 'string' ? pro : pro.text }}
                            </p>
                        </li>
                    </ul>
                </section>

                <!-- PUNTOS EN CONTRA (transparencia: el cliente decide con info completa) -->
                <section v-if="car.cons && car.cons.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-rose-600 dark:text-rose-400">
                        {{ t('marketplace_show.section_cons_eyebrow', { default: 'Lo que debes saber' }) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                        {{ t('marketplace_show.section_cons_title', { default: 'Puntos en contra' }) }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('marketplace_show.section_cons_desc', { default: 'Cosas que debes tener en cuenta antes de decidir. La información completa te ayuda a elegir mejor.' }) }}
                    </p>
                    <ul class="mt-4 space-y-2">
                        <li v-for="(con, i) in car.cons" :key="i" class="flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3 dark:border-rose-800/50 dark:bg-rose-900/20">
                            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400" />
                            <p class="flex-1 text-sm text-gray-900 dark:text-gray-200">
                                {{ typeof con === 'string' ? con : con.text }}
                            </p>
                        </li>
                    </ul>
                </section>

                <!-- FICHA TECNICA -->
                <section v-if="specItems.some((s) => s.value)" id="detalles" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-estoril-700 dark:text-estoril-300">
                        {{ t('dossier.section_specs_eyebrow', { default: 'Ficha tecnica' }) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                        {{ t('dossier.section_specs_title', { default: 'Detalles del vehiculo' }) }}
                    </h2>
                    <dl class="mt-6 grid grid-cols-1 gap-x-8 sm:grid-cols-2">
                        <div v-for="spec in specItems.filter((s) => s.value)" :key="spec.key" class="flex items-baseline justify-between gap-3 border-b border-dashed border-gray-200 py-2 dark:border-asphalt-700">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ spec.label }}</dt>
                            <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ spec.value }}</dd>
                        </div>
                    </dl>
                </section>

                <!-- EQUIPAMIENTO -->
                <section v-if="equipment.length > 0" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-estoril-700 dark:text-estoril-300">
                        {{ t('dossier.section_equipment_eyebrow', { default: 'Equipamiento' }) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                        {{ t('dossier.section_equipment_title', { default: 'Extras y equipamiento' }) }}
                    </h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span v-for="(item, i) in equipment" :key="i" class="inline-flex items-center rounded-full bg-estoril-50 px-3 py-1.5 text-xs font-medium text-estoril-800 ring-1 ring-estoril-200 dark:bg-estoril-900/30 dark:text-estoril-200 dark:ring-estoril-800">
                            {{ item }}
                        </span>
                    </div>
                </section>

                <!-- UBICACION -->
                <section v-if="car.city" class="flex items-center gap-2 rounded-2xl bg-white px-6 py-4 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <MapPinIcon class="h-5 w-5 text-estoril-700 dark:text-estoril-300" />
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">{{ t('dossier.location_label', { default: 'Localizado en' }) }}</span>
                        {{ car.city }}
                    </p>
                </section>

                <!-- COMO FUNCIONA -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-estoril-700 dark:text-estoril-300">
                        {{ t('dossier.section_process_eyebrow', { default: 'El proceso' }) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                        {{ t('dossier.section_process_title', { default: 'Como funciona' }) }}
                    </h2>
                    <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <li v-for="(paso, i) in processSteps" :key="i" class="rounded-xl border-t-2 border-estoril-600 bg-gray-50 p-4 dark:bg-asphalt-700/40">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-estoril-700 dark:text-estoril-300">{{ paso.when }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ paso.what }}</p>
                        </li>
                    </ol>
                </section>

                <!-- COMO TRABAJAMOS -->
                <section class="rounded-2xl border border-amber-200 bg-amber-50/50 p-6 dark:border-amber-800/50 dark:bg-amber-900/10 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-amber-700 dark:text-amber-300">
                        {{ t('dossier.section_legal_eyebrow', { default: 'Como trabajamos' }) }}
                    </p>
                    <h2 class="mt-2 text-xl font-extrabold text-gray-900 dark:text-white sm:text-2xl">
                        {{ t('dossier.section_legal_title', { default: 'Somos gestores, no vendedores' }) }}
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                        {{ t('dossier.legal_body', { default: 'JJ Import Motors no es el vendedor ni el propietario del vehiculo. Gestionamos la busqueda, la verificacion y la importacion, con honorarios acordados de antemano. No ofrecemos garantia sobre el vehiculo: cualquier garantia o responsabilidad corresponde al vendedor, segun la ley que le sea aplicable.' }) }}
                    </p>
                </section>

                <!-- COCHES RELACIONADOS -->
                <section v-if="relatedCars && relatedCars.length > 0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('marketplace.related_cars', { default: 'Otros coches similares' }) }}
                    </h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link v-for="rcar in relatedCars" :key="rcar.id" :href="route('marketplace.show', rcar.id)" class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-estoril-300 dark:bg-asphalt-800 dark:ring-asphalt-700">
                            <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-asphalt-200 to-gray-300">
                                <img v-if="photoUrl(rcar.photos ? rcar.photos[0] : null)" :src="photoUrl(rcar.photos ? rcar.photos[0] : null)" :alt="`${rcar.brand} ${rcar.model}`" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                <div v-else class="flex h-full w-full items-center justify-center text-gray-400">
                                    <span class="text-3xl">&#128663;</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ rcar.brand }} {{ rcar.model }}</p>
                                <p v-if="rcar.year" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ rcar.year }} · {{ rcar.mileage ? fmtNumber(rcar.mileage) + ' km' : '—' }}</p>
                                <p v-if="rcar.city" class="truncate text-[11px] text-gray-500 dark:text-gray-400">📍 {{ rcar.city }}</p>
                                <p class="mt-1 text-sm font-bold text-estoril-700 dark:text-estoril-300">{{ fmt(rcar.purchase_price) }}</p>
                            </div>
                        </Link>
                    </div>
                </section>

            </div>

            <!-- BARRA CTA FIJA -->
            <div v-if="scrollY > 400" class="fixed bottom-0 left-0 right-0 z-40 flex gap-2 border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur shadow-lg dark:border-asphalt-700 dark:bg-asphalt-900/95">
                <a :href="whatsappUrl" target="_blank" rel="noopener" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                    <ChatBubbleLeftRightIcon class="h-4 w-4" />
                    {{ t('dossier.cta_whatsapp_short', { default: 'WhatsApp' }) }}
                </a>
                <a href="tel:+34675701439" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                    <PhoneIcon class="h-4 w-4" />
                    {{ t('dossier.cta_call_short', { default: 'Llamar' }) }}
                </a>
            </div>
        </div>
    </MarketplaceLayout>
</template>
