<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon,
    EyeIcon,
    ShieldCheckIcon,
    CheckBadgeIcon,
    TruckIcon,
    ChartBarIcon,
    PhoneIcon,
    EnvelopeIcon,
    StarIcon,
    ClockIcon,
    BanknotesIcon,
    ArrowRightIcon,
    ArrowUpIcon,
    SparklesIcon,
    DocumentArrowDownIcon,
    MapPinIcon,
    CalendarDaysIcon,
    BoltIcon,
    XMarkIcon,
    GiftIcon,
} from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import WishlistButton from '@/Components/WishlistButton.vue';
import CompareBar from '@/Components/CompareBar.vue';
import LazyImage from '@/Components/LazyImage.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    cars: Object,
    verdicts: Array,
    filters: Object,
    requestUrl: String,
    filterBounds: { type: Object, default: () => ({ price: { min: 0, max: 9999999 }, year: { min: 1900, max: new Date().getFullYear() + 1 } }) },
    filterOptions: { type: Object, default: () => ({ fuels: [], transmissions: [], doors: [], colors: [] }) },
});

const { t, locale } = useTranslations();

// Bounds para validación HTML5 (vienen del backend, item 1)
const priceMin = computed(() => props.filterBounds?.price?.min ?? 0);
const priceMax = computed(() => props.filterBounds?.price?.max ?? 9999999);
const yearMin = computed(() => props.filterBounds?.year?.min ?? 1900);
const yearMax = computed(() => props.filterBounds?.year?.max ?? new Date().getFullYear() + 1);

// Enlace al formulario público de solicitud (fallback: sección de contacto)
const requestHref = computed(() => props.requestUrl || '#contacto');

const search = ref(props.filters?.search || '');
const brandFilter = ref(props.filters?.brand || '');
const dealFilter = ref(props.filters?.deal === '1' || props.filters?.deal === 'true');
const minPrice = ref(props.filters?.min_price || '');
const maxPrice = ref(props.filters?.max_price || '');
const mileageFilter = ref(props.filters?.mileage || '');
const fuelFilter = ref(props.filters?.fuel || '');
const transmissionFilter = ref(props.filters?.transmission || '');
const doorsFilter = ref(props.filters?.doors || '');
const colorFilter = ref(props.filters?.color || '');

// Marketplace-3.10: Sincronizar filtros con URL (compartible)
const syncToUrl = () => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (brandFilter.value) params.set('brand', brandFilter.value);
    if (dealFilter.value) params.set('deal', '1');
    if (minPrice.value) params.set('min_price', minPrice.value);
    if (maxPrice.value) params.set('max_price', maxPrice.value);
    if (mileageFilter.value) params.set('mileage', mileageFilter.value);
    if (fuelFilter.value) params.set('fuel', fuelFilter.value);
    if (transmissionFilter.value) params.set('transmission', transmissionFilter.value);
    if (doorsFilter.value) params.set('doors', doorsFilter.value);
    if (colorFilter.value) params.set('color', colorFilter.value);

    const qs = params.toString();
    const url = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
    window.history.replaceState({}, '', url);
};

const resetFilters = () => {
    search.value = '';
    brandFilter.value = '';
    dealFilter.value = false;
    minPrice.value = '';
    maxPrice.value = '';
    mileageFilter.value = '';
    fuelFilter.value = '';
    transmissionFilter.value = '';
    doorsFilter.value = '';
    colorFilter.value = '';
    syncToUrl();
};

const { currency, verdictVariant, trafficLightVariant } = useFormat();

// Watchers para sincronizar URL en cada cambio
import { watch } from 'vue';
watch([search, brandFilter, dealFilter, minPrice, maxPrice, mileageFilter, fuelFilter, transmissionFilter, doorsFilter, colorFilter], () => {
    syncToUrl();
}, { flush: 'post' });

// Marcas disponibles en el catálogo actual
const brands = computed(() => {
    const set = new Set();
    (props.cars.data || []).forEach(c => { if (c.brand) set.add(c.brand); });
    return [...set].sort();
});

const filteredCars = computed(() => {
    let result = props.cars.data || [];
    if (brandFilter.value) {
        result = result.filter(c => c.brand === brandFilter.value);
    }
    if (dealFilter.value) {
        result = result.filter(c => (c.estimated_saving || 0) > 0);
    }
    if (minPrice.value) {
        const min = parseFloat(minPrice.value);
        if (!isNaN(min)) {
            result = result.filter(c => (c.purchase_price || 0) >= min);
        }
    }
    if (maxPrice.value) {
        const max = parseFloat(maxPrice.value);
        if (!isNaN(max)) {
            result = result.filter(c => (c.purchase_price || 0) <= max);
        }
    }
    if (mileageFilter.value) {
        const mileage = parseFloat(mileageFilter.value);
        if (!isNaN(mileage)) {
            result = result.filter(c => (c.mileage || 0) <= mileage);
        }
    }
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(c =>
            (c.brand && c.brand.toLowerCase().includes(term)) ||
            (c.model && c.model.toLowerCase().includes(term))
        );
    }
    // Filtros extendidos (Marketplace item 2)
    if (fuelFilter.value) {
        result = result.filter(c => c.fuel === fuelFilter.value);
    }
    if (transmissionFilter.value) {
        result = result.filter(c => c.transmission === transmissionFilter.value);
    }
    if (doorsFilter.value !== '' && doorsFilter.value !== null) {
        result = result.filter(c => Number(c.doors) === Number(doorsFilter.value));
    }
    if (colorFilter.value) {
        result = result.filter(c => c.color === colorFilter.value);
    }
    return result;
});

// Trust signals
const trustItems = computed(() => [
    { icon: ShieldCheckIcon, label: t('marketplace.trust_verified'), description: t('marketplace.trust_verified_desc') },
    { icon: ChartBarIcon, label: t('marketplace.trust_investigated'), description: t('marketplace.trust_investigated_desc') },
    { icon: TruckIcon, label: t('marketplace.trust_import'), description: t('marketplace.trust_import_desc') },
]);

// How it works
const howItWorks = computed(() => [
    { step: '1', title: t('marketplace.step1_title'), description: t('marketplace.step1_desc') },
    { step: '2', title: t('marketplace.step2_title'), description: t('marketplace.step2_desc') },
    { step: '3', title: t('marketplace.step3_title'), description: t('marketplace.step3_desc') },
]);

// Marketplace item 3: sticky filter bar — detecta cuando la barra entra en scroll
const scrolledPastHero = ref(false);
const onScroll = () => { scrolledPastHero.value = window.scrollY > 380; };
onMounted(() => { window.addEventListener('scroll', onScroll, { passive: true }); });
onUnmounted(() => { window.removeEventListener('scroll', onScroll); });

const scrollToTop = () => {
    if (typeof window === 'undefined') return;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Marketplace item 12: Newsletter popup suave con lead magnet.
// Trigger: 30s en pagina o 50% scroll. Visible solo una vez por localStorage.
const showNewsletter = ref(false);
const newsletterEmail = ref('');
const newsletterSubmitted = ref(false);
const newsletterError = ref('');
const triggerNewsletter = () => {
    if (typeof window === 'undefined') return;
    if (localStorage.getItem('mc-newsletter-seen')) return;
    localStorage.setItem('mc-newsletter-seen', '1');
    showNewsletter.value = true;
};
const closeNewsletter = () => { showNewsletter.value = false; };
const submitNewsletter = async () => {
    if (!newsletterEmail.value || !newsletterEmail.value.includes('@')) {
        newsletterError.value = 'Email inválido';
        return;
    }
    newsletterError.value = '';
    try {
        const r = await window.axios.post('/newsletter/subscribe', {
            email: newsletterEmail.value,
            locale,
            source: 'marketplace_popup',
        });
        if (r.data?.success) {
            newsletterSubmitted.value = true;
            setTimeout(() => { showNewsletter.value = false; }, 2500);
        }
    } catch (e) {
        newsletterError.value = e.response?.data?.message || 'Error';
    }
};
onMounted(() => {
    let scrollTriggered = false;
    const onScroll50 = () => {
        if (scrollTriggered) return;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        if (max > 0 && window.scrollY / max >= 0.5) {
            scrollTriggered = true;
            triggerNewsletter();
        }
    };
    window.addEventListener('scroll', onScroll50, { passive: true });
    setTimeout(() => { if (!scrollTriggered) triggerNewsletter(); }, 30000);
});
</script>

<template>
    <Head :title="t('marketplace.title')" />

    <PublicLayout>
        <!-- HERO -->
        <section class="relative overflow-hidden bg-linear-to-br from-estoril-100 via-estoril-50 to-platinum-200">
            <div class="absolute -top-40 -right-40 h-[28rem] w-[28rem] rounded-full bg-estoril-300/60 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-[28rem] w-[28rem] rounded-full bg-platinum-400/50 blur-3xl"></div>
            <div class="absolute left-1/2 top-1/4 h-80 w-80 -translate-x-1/2 rounded-full bg-estoril-500/20 blur-3xl"></div>
            <div class="absolute left-10 top-24 h-24 w-24 rounded-full bg-estoril-400/40 blur-2xl"></div>
            <div class="absolute right-16 bottom-24 h-32 w-32 rounded-full bg-asphalt-300/30 blur-2xl"></div>
            <div class="absolute left-1/3 top-10 h-16 w-16 rounded-full bg-platinum-300/50 blur-xl"></div>

            <!-- Ola decorativa inferior -->
            <div class="pointer-events-none absolute bottom-0 left-0 right-0">
                <svg class="h-12 w-full sm:h-16" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0,40 C240,80 480,0 720,30 C960,60 1200,10 1440,40 L1440,80 L0,80 Z" fill="#ffffff" fill-opacity="0.7"></path>
                    <path d="M0,60 C260,90 520,20 760,50 C1000,80 1240,40 1440,60 L1440,80 L0,80 Z" fill="#1A306D" fill-opacity="0.12"></path>
                </svg>
            </div>

            <!-- Rejilla decorativa sutil -->
            <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, #1A306D 1px, transparent 1px); background-size: 22px 22px;"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-1.5 text-sm font-semibold text-estoril-800 shadow-sm ring-1 ring-estoril-200 backdrop-blur">
                        <SparklesIcon class="h-4 w-4 text-estoril-600" />
                        {{ t('marketplace.tagline') }}
                    </span>
                    <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        {{ t('marketplace.hero_title') }}
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-gray-600">
                        {{ t('marketplace.description') }}
                    </p>
                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="#catalogo" class="inline-flex items-center gap-2 rounded-xl bg-estoril-700 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-estoril-700/20 transition hover:-translate-y-0.5 hover:bg-estoril-800 hover:shadow-xl">
                            {{ t('marketplace.cta_primary') }}
                            <ArrowRightIcon class="h-4 w-4" />
                        </a>
                        <a href="#contacto" class="inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50 hover:ring-estoril-300">
                            <PhoneIcon class="h-4 w-4" />
                            {{ t('marketplace.cta_secondary') }}
                        </a>
                    </div>

                    <!-- Stats strip -->
                    <div class="mt-12 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-4 text-left shadow-sm ring-1 ring-estoril-200 backdrop-blur">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-estoril-600 shadow-md shadow-estoril-600/30">
                                <ShieldCheckIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-extrabold text-estoril-800">9</p>
                                <p class="text-xs font-medium text-gray-600">{{ t('marketplace.stat_points') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-4 text-left shadow-sm ring-1 ring-estoril-200 backdrop-blur">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-estoril-700 shadow-md shadow-estoril-700/30">
                                <ChartBarIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-extrabold text-estoril-800">100%</p>
                                <p class="text-xs font-medium text-gray-600">{{ t('marketplace.stat_price') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-4 text-left shadow-sm ring-1 ring-estoril-200 backdrop-blur">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-asphalt-700 shadow-md shadow-asphalt-700/30">
                                <TruckIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-extrabold text-estoril-800">🇪🇸</p>
                                <p class="text-xs font-medium text-gray-600">{{ t('marketplace.stat_import') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section :id="locale === 'es' ? 'como-funciona' : 'how-it-works'" class="border-y border-gray-200 bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ t('marketplace.section_how_it_works') }}</h2>
                    <p class="mt-3 text-base text-gray-600">{{ t('marketplace.section_how_it_works_desc') }}</p>
                </div>
                <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div v-for="item in howItWorks" :key="item.step" class="relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md">
                        <span class="absolute -top-4 left-6 flex h-9 w-9 items-center justify-center rounded-full bg-linear-to-br from-estoril-600 to-estoril-800 text-sm font-bold text-white shadow-md shadow-estoril-700/30">
                            {{ item.step }}
                        </span>
                        <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CATALOG -->
        <section :id="locale === 'es' ? 'catalogo' : 'catalog'" class="py-16">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ t('marketplace.section_catalog') }}</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ t('marketplace.catalog_desc').replace(':count', cars.total || 0) }}</p>
                    </div>
                    <a :href="requestHref" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        <PhoneIcon class="h-4 w-4" />
                        {{ t('marketplace.cta_request_notify') }}
                    </a>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 gap-3 rounded-2xl bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_search') }}</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="t('marketplace.filter_search_placeholder')"
                                class="block w-full rounded-lg border-gray-200 pl-9 text-sm focus:border-estoril-600 focus:ring-estoril-600"
                            />
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_budget') }}</label>
                        <div class="flex gap-2">
                            <input v-model.number="minPrice" type="number" :min="priceMin" :max="priceMax" step="500" :placeholder="t('marketplace.filter_budget_min')" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600" />
                            <input v-model.number="maxPrice" type="number" :min="priceMin" :max="priceMax" step="500" :placeholder="t('marketplace.filter_budget_max')" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600" />
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_mileage') }}</label>
                        <div class="flex gap-2">
                            <input v-model.number="mileageFilter" type="number" :min="0" :max="priceMax" step="1000" :placeholder="t('marketplace.filter_mileage_placeholder')" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600" />
                        </div>
                    </div>

                    <!-- Marketplace item 2: filtros extendidos -->
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_fuel', 'Combustible') }}</label>
                        <select v-model="fuelFilter" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600">
                            <option value="">{{ t('marketplace.filter_all', 'Todos') }}</option>
                            <option v-for="f in props.filterOptions.fuels" :key="f" :value="f">{{ f }}</option>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_transmission', 'Cambio') }}</label>
                        <select v-model="transmissionFilter" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600">
                            <option value="">{{ t('marketplace.filter_all', 'Todos') }}</option>
                            <option v-for="tr in props.filterOptions.transmissions" :key="tr" :value="tr">{{ tr }}</option>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_doors', 'Puertas') }}</label>
                        <select v-model="doorsFilter" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600">
                            <option value="">{{ t('marketplace.filter_all', 'Todos') }}</option>
                            <option v-for="d in props.filterOptions.doors" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_color', 'Color') }}</label>
                        <select v-model="colorFilter" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600">
                            <option value="">{{ t('marketplace.filter_all', 'Todos') }}</option>
                            <option v-for="c in props.filterOptions.colors" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>
                </div>

                <!-- Quick filters: marcas + ofertas -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="brandFilter = ''"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            brandFilter === ''
                                ? 'bg-estoril-700 text-white shadow-sm'
                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-estoril-50 hover:text-estoril-700',
                        ]"
                    >
                        {{ t('marketplace.filter_all_brands') }}
                    </button>
                    <button
                        v-for="b in brands"
                        :key="b"
                        @click="brandFilter = brandFilter === b ? '' : b"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            brandFilter === b
                                ? 'bg-estoril-700 text-white shadow-sm'
                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-estoril-50 hover:text-estoril-700',
                        ]"
                    >
                        {{ b }}
                    </button>
                    <span class="mx-1 hidden h-5 w-px bg-gray-300 sm:block"></span>
                    <button
                        @click="dealFilter = !dealFilter"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-medium transition',
                            dealFilter
                                ? 'bg-amber-500 text-white shadow-sm'
                                : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100',
                        ]"
                    >
                        <SparklesIcon class="h-4 w-4" />
                        {{ t('marketplace.filter_good_deal') }}
                    </button>
                </div>

                <!-- Cars grid -->
                <div v-if="filteredCars.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="car in filteredCars"
                        :key="car.id"
                        class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-estoril-300"
                    >
                        <Link :href="route('marketplace.show', car.id)" class="flex flex-1 flex-col">
                        <!-- Photo (LazyImage for performance) -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-linear-to-br from-asphalt-200 to-gray-300">
                            <LazyImage
                                v-if="car.photos && car.photos.length > 0"
                                :src="car.photos[0].startsWith('http') ? car.photos[0] : `/storage/${car.photos[0]}`"
                                :alt="t('cars.marketplace_brand_model', { brand: car.brand, model: car.model })"
                                ratio="aspect-[16/10]"
                                fit="object-cover group-hover:scale-110 transition duration-700"
                            />
                            <div v-else class="flex h-full items-center justify-center text-6xl text-gray-400">🚗</div>
                            <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-asphalt-900/60 via-transparent to-transparent opacity-50 transition group-hover:opacity-80"></div>

                            <!-- Badges -->
                            <div class="absolute left-3 top-3">
                                <Badge :variant="verdictVariant(car.verdict)" size="sm">{{ car.verdict }}</Badge>
                            </div>
                            <div class="absolute right-3 top-3">
                                <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                            </div>

                            <!-- Savings + location -->
                            <div v-if="car.estimated_saving > 0" class="absolute bottom-3 left-3">
                                <span class="inline-flex items-center rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-estoril-700 shadow-sm backdrop-blur">
                                    {{ t('marketplace.saving_badge').replace(':amount', currency(car.estimated_saving)) }}
                                </span>
                            </div>
                            <div v-if="car.city" class="absolute bottom-3 right-3">
                                <span class="inline-flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur">
                                    <MapPinIcon class="h-3 w-3" />
                                    {{ car.city }}
                                </span>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-lg font-bold text-gray-900 transition group-hover:text-estoril-800">
                                        {{ car.brand }} {{ car.model }}
                                    </h3>
                                    <p v-if="car.version" class="truncate text-sm text-gray-500">{{ car.version }}</p>
                                </div>
                            </div>

                            <dl class="mt-4 grid grid-cols-3 gap-2">
                                <div class="rounded-lg bg-gray-50 px-2 py-2 text-center">
                                    <dt class="flex items-center justify-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        <CalendarDaysIcon class="h-3 w-3" /> {{ t('marketplace.specs_year') }}
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-gray-900">{{ car.year }}</dd>
                                </div>
                                <div class="rounded-lg bg-gray-50 px-2 py-2 text-center">
                                    <dt class="flex items-center justify-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        <BoltIcon class="h-3 w-3" /> {{ t('marketplace.specs_km') }}
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-gray-900">{{ car.mileage ? (car.mileage / 1000).toFixed(0) + 'k' : t('marketplace.not_available') }}</dd>
                                </div>
                                <div class="rounded-lg bg-gray-50 px-2 py-2 text-center">
                                    <dt class="flex items-center justify-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        {{ t('marketplace.specs_fuel') }}
                                    </dt>
                                    <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900">{{ car.fuel || t('marketplace.not_available') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex items-end justify-between gap-3 border-t border-gray-100 pt-4">
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">{{ t('marketplace.price_from') }}</p>
                                    <p class="truncate text-2xl font-extrabold tracking-tight text-estoril-800">{{ currency(car.purchase_price) }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-estoril-700 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition group-hover:bg-estoril-800">
                                    {{ t('marketplace.view_report') }}
                                    <ArrowRightIcon class="h-3 w-3" />
                                </span>
                            </div>
                        </div>
                        </Link>
                        <!-- Wishlist + Compare (fuera del Link para no anidar) -->
                        <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-4 py-2">
                            <WishlistButton :car="car" />
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-estoril-100">
                        <SparklesIcon class="h-8 w-8 text-estoril-700" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ t('marketplace.no_cars_title') }}</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-gray-600">
                        {{ t('marketplace.no_cars_desc') }}
                    </p>
                    <a :href="requestHref" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-800">
                        <PhoneIcon class="h-4 w-4" />
                        {{ t('marketplace.cta_request_notify') }}
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA / Contacto -->
        <section id="contacto" class="bg-linear-to-br from-asphalt-900 to-estoril-900 py-16">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ t('marketplace.section_contact') }}
                </h2>
                <p class="mx-auto mt-3 max-w-2xl text-lg text-gray-300">
                    {{ t('marketplace.section_contact_desc') }}
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a :href="requestHref" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-estoril-500">
                        <EnvelopeIcon class="h-5 w-5" />
                        {{ t('marketplace.cta_request_form') }}
                    </a>
                    <a href="https://wa.me/34675701439" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <PhoneIcon class="h-5 w-5" />
                        {{ t('marketplace.cta_whatsapp') }}
                    </a>
                    <a href="tel:+34675701439" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <PhoneIcon class="h-5 w-5" />
                        {{ t('marketplace.cta_call') }}
                    </a>
                    <a href="mailto:jjimportmotors@gmail.com" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <EnvelopeIcon class="h-5 w-5" />
                        {{ t('marketplace.cta_email') }}
                    </a>
                </div>
                <p class="mt-8 text-sm text-gray-400">
                    {{ t('marketplace.footer_line') }}
                </p>
            </div>
        </section>

        <!-- Floating download button for JJ Import Motors folleto -->
        <div class="fixed bottom-6 right-6 z-40">
            <a
                href="/jj-import-folleto.pdf"
                download="JJ_Import_Motors_Folleto.pdf"
                class="flex items-center gap-2 rounded-full bg-estoril-700 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-estoril-800 transition-colors"
            >
                <DocumentArrowDownIcon class="h-5 w-5" />
                <span class="hidden sm:inline">{{ t('marketplace.folleto') }}</span>
            </a>
        </div>

        <!-- Marketplace item 3: Sticky filter bar (visible al hacer scroll) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2">
            <div
                v-if="scrolledPastHero"
                data-testid="sticky-filter-bar"
                class="fixed left-0 right-0 top-0 z-30 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/80">
                <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2 sm:px-6 lg:px-8">
                    <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="t('marketplace.search_placeholder_short', { default: 'Buscar marca o modelo…' })"
                        class="flex-1 rounded-md border-0 bg-transparent text-sm focus:outline-none focus:ring-0"
                    />
                    <select v-model="brandFilter" class="rounded-md border-0 bg-transparent text-sm">
                        <option value="">{{ t('marketplace.filter_all_brands') }}</option>
                        <option v-for="b in brands" :key="b" :value="b">{{ b }}</option>
                    </select>
                    <span class="hidden text-sm text-gray-500 sm:inline">
                        {{ filteredCars.length }} / {{ cars.data?.length || 0 }}
                    </span>
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-gray-700">
                        <input v-model="dealFilter" type="checkbox" class="rounded border-gray-300 text-estoril-600" />
                        {{ t('marketplace.filter_deals_only', { default: 'Ofertas' }) }}
                    </label>
                    <button
                        v-if="search || brandFilter || dealFilter || minPrice || maxPrice || mileageFilter || fuelFilter || transmissionFilter || doorsFilter || colorFilter"
                        @click="resetFilters"
                        class="rounded-md px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                    >
                        Limpiar
                    </button>
                    <button
                        @click="scrollToTop"
                        type="button"
                        data-testid="sticky-filter-scroll-top"
                        class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-estoril-700"
                        :aria-label="t('marketplace.back_to_top', { default: 'Volver arriba' })"
                        :title="t('marketplace.back_to_top', { default: 'Volver arriba' })">
                        <ArrowUpIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Marketplace item 12: Newsletter popup suave con lead magnet -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4">
                <div
                    v-if="showNewsletter"
                    class="fixed bottom-4 right-4 z-40 w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-gray-200"
                    role="dialog"
                    aria-modal="false">
                    <button
                        type="button"
                        @click="closeNewsletter"
                        class="absolute right-2 top-2 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        :aria-label="t('marketplace.newsletter_close', { default: 'Cerrar' })">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-estoril-100 text-estoril-700">
                            <GiftIcon class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ t('marketplace.newsletter_title', { default: '5 veh\u00edculos verificados al mes en tu inbox' }) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ t('marketplace.newsletter_desc', { default: 'Recibe solo los coches con veredicto Buy o Buy if price drops. Sin spam, baja cuando quieras.' }) }}
                            </p>
                            <form v-if="!newsletterSubmitted" @submit.prevent="submitNewsletter" class="mt-3 flex flex-col gap-1">
                                <div class="flex gap-2">
                                    <input
                                        v-model="newsletterEmail"
                                        type="email"
                                        required
                                        :placeholder="t('marketplace.newsletter_email_placeholder', { default: 'tu@email.com' })"
                                        class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500" />
                                    <button type="submit" class="rounded-md bg-estoril-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-estoril-500">
                                        {{ t('marketplace.newsletter_cta', { default: 'Suscribirme' }) }}
                                    </button>
                                </div>
                                <p v-if="newsletterError" class="text-[11px] text-rose-600">{{ newsletterError }}</p>
                            </form>
                            <p v-else class="mt-3 text-xs font-semibold text-emerald-700">
                                ✓ {{ t('marketplace.newsletter_thanks', { default: '¡Listo! Te avisamos.' }) }}
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Compare bar (Marketplace-3.8) -->
        <CompareBar />
    </PublicLayout>
</template>
