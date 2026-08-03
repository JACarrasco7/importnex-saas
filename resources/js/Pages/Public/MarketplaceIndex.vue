<script setup>
import { ref, computed } from 'vue';
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
    SparklesIcon,
    DocumentArrowDownIcon,
    MapPinIcon,
    CalendarDaysIcon,
    BoltIcon,
} from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    cars: Object,
    verdicts: Array,
    filters: Object,
});

const { t, locale } = useTranslations();

const search = ref(props.filters?.search || '');
const verdictFilter = ref(props.filters?.verdict || 'all');
const minPrice = ref(props.filters?.min_price || '');
const maxPrice = ref(props.filters?.max_price || '');
const mileageFilter = ref(props.filters?.mileage || '');

const { currency, verdictVariant } = useFormat();

const filteredCars = computed(() => {
    let result = props.cars.data || [];
    if (verdictFilter.value && verdictFilter.value !== 'all') {
        result = result.filter(c => c.verdict === verdictFilter.value);
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
</script>

<template>
    <Head :title="t('marketplace.title')" />

    <div class="min-h-screen bg-white">
        <!-- Public header -->
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <Link :href="route('marketplace.index')" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-estoril-600 to-estoril-800 shadow-sm">
                        <ShieldCheckIcon class="h-5 w-5 text-white" />
                    </span>
                    <div>
                        <p class="text-base font-bold leading-tight text-gray-900">{{ t('marketplace.brand') }}</p>
                        <p class="text-[11px] leading-tight text-gray-500">by Importnex</p>
                    </div>
                </Link>
                <nav class="flex items-center gap-3">
                    <a :href="locale === 'es' ? '#catalogo' : '#catalog'" class="hidden text-sm font-medium text-gray-700 hover:text-gray-900 sm:inline">{{ t('marketplace.section_catalog') }}</a>
                    <a :href="locale === 'es' ? '#como-funciona' : '#how-it-works'" class="hidden text-sm font-medium text-gray-700 hover:text-gray-900 sm:inline">{{ t('marketplace.section_how_it_works') }}</a>
                    <a href="#contacto" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                        <PhoneIcon class="h-4 w-4" />
                        {{ t('marketplace.cta_contact') }}
                    </a>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section class="relative overflow-hidden bg-gradient-to-br from-estoril-50 via-white to-platinum-100">
            <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-estoril-200/40 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-platinum-300/40 blur-3xl"></div>
            <div class="absolute left-1/2 top-1/3 h-72 w-72 -translate-x-1/2 rounded-full bg-estoril-100/70 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-estoril-100 px-4 py-1.5 text-sm font-semibold text-estoril-800 ring-1 ring-estoril-200">
                        <SparklesIcon class="h-4 w-4" />
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
                        <div class="rounded-2xl bg-white/70 px-4 py-4 shadow-sm ring-1 ring-gray-200 backdrop-blur">
                            <p class="text-3xl font-extrabold text-estoril-800">9</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-600">{{ t('marketplace.stat_points') }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/70 px-4 py-4 shadow-sm ring-1 ring-gray-200 backdrop-blur">
                            <p class="text-3xl font-extrabold text-estoril-800">100%</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-600">{{ t('marketplace.stat_price') }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/70 px-4 py-4 shadow-sm ring-1 ring-gray-200 backdrop-blur">
                            <p class="text-3xl font-extrabold text-estoril-800">🇪🇸</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-600">{{ t('marketplace.stat_import') }}</p>
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
                    <div v-for="item in howItWorks" :key="item.step" class="relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <span class="absolute -top-4 left-6 flex h-8 w-8 items-center justify-center rounded-full bg-estoril-700 text-sm font-bold text-white shadow-sm">
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
                    <a href="#contacto" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        <PhoneIcon class="h-4 w-4" />
                        {{ t('marketplace.cta_request_notify') }}
                    </a>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 gap-3 rounded-2xl bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-3">
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
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_budget') }}</label>
                        <div class="flex gap-2">
                            <input v-model.number="minPrice" type="number" min="0" step="500" :placeholder="t('marketplace.filter_budget_min')" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600" />
                            <input v-model.number="maxPrice" type="number" min="0" step="500" :placeholder="t('marketplace.filter_budget_max')" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ t('marketplace.filter_mileage') }}</label>
                        <div class="flex gap-2">
                            <input v-model.number="mileageFilter" type="number" min="0" step="1000" :placeholder="t('marketplace.filter_mileage_placeholder')" class="block w-full rounded-lg border-gray-200 text-sm focus:border-estoril-600 focus:ring-estoril-600" />
                        </div>
                    </div>
                </div>

                <!-- Tabs (verdict quick filter) -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="v in [{id:'all',label:t('marketplace.tab_all')},{id:'Buy',label:t('marketplace.tab_recommended')},{id:'Buy if price drops',label:t('marketplace.tab_price_drop')}]"
                        :key="v.id"
                        @click="verdictFilter = v.id"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            verdictFilter === v.id
                                ? 'bg-gray-900 text-white shadow-sm'
                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50',
                        ]"
                    >
                        {{ v.label }}
                    </button>
                </div>

                <!-- Cars grid -->
                <div v-if="filteredCars.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="car in filteredCars"
                        :key="car.id"
                        :href="route('marketplace.show', car.id)"
                        class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-estoril-300"
                    >
                        <!-- Photo -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-asphalt-200 to-gray-300">
                            <img
                                v-if="car.photos && car.photos.length > 0"
                                :src="car.photos[0].startsWith('http') ? car.photos[0] : `/storage/${car.photos[0]}`"
                                :alt="`${car.brand} ${car.model}`"
                                class="h-full w-full object-cover transition duration-700 group-hover:scale-110"
                                loading="lazy"
                            />
                            <div v-else class="flex h-full items-center justify-center text-6xl text-gray-400">🚗</div>
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-asphalt-900/60 via-transparent to-transparent opacity-50 transition group-hover:opacity-80"></div>

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
                    <a href="#contacto" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-800">
                        <PhoneIcon class="h-4 w-4" />
                        {{ t('marketplace.cta_request_notify') }}
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA / Contacto -->
        <section id="contacto" class="bg-gradient-to-br from-asphalt-900 to-estoril-900 py-16">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ t('marketplace.section_contact') }}
                </h2>
                <p class="mx-auto mt-3 max-w-2xl text-lg text-gray-300">
                    {{ t('marketplace.section_contact_desc') }}
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a href="https://wa.me/34675701439" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-estoril-500">
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
                    Importnex &middot; JJ Import Motors &middot; Servicio llave en mano Alemania → España
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-gray-50 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-estoril-600 to-estoril-800">
                            <ShieldCheckIcon class="h-4 w-4 text-white" />
                        </span>
                        <span class="text-sm font-semibold text-gray-900">{{ t('marketplace.brand') }}</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ t('marketplace.footer_copy').replace(':year', new Date().getFullYear()) }}
                    </p>
                </div>
            </div>
        </footer>

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
    </div>
</template>
