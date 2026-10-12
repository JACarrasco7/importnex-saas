<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import {
    TruckIcon,
    CheckCircleIcon,
    DocumentArrowDownIcon,
    InformationCircleIcon,
    ExclamationCircleIcon,
    ArrowPathIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    organization: Object,
});

const { t } = useTranslations();

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 15 }, (_, i) => currentYear - i);

// Clave de localStorage por organización para no mezclar borradores
// entre distintos tenants (poco probable en la práctica pero correcto).
const draftKey = computed(() => `car-request-draft:${props.organization?.slug ?? 'unknown'}`);

// Si la URL trae `?car=ID&brand=...&model=...` (enviado desde una ficha
// de marketplace), mostramos la marca/modelo arriba del formulario para
// que el cliente sepa sobre qué coche pregunta y pre-rellenamos los
// campos brand/model. Usamos query params (no API) para no acoplar el
// form publico a endpoints de lectura. (ver #3)
const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const contextCarId = computed(() => urlParams?.get('car') ?? null);
const contextCar = computed(() => {
    if (! contextCarId.value) return null;
    return {
        id: contextCarId.value,
        brand: urlParams?.get('brand') ?? '',
        model: urlParams?.get('model') ?? '',
        version: urlParams?.get('version') ?? '',
        year: urlParams?.get('year') ?? '',
        href: urlParams?.get('href') ?? null,
    };
});

// Pre-rellenar brand/model desde el contexto si el borrador está vacío.
if (contextCar.value) {
    // Lo hacemos en onMounted para no pisar un draft existente.
}

function clearContextCar() {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        ['car', 'brand', 'model', 'version', 'year', 'href'].forEach((p) => url.searchParams.delete(p));
        window.history.replaceState({}, '', url);
    }
}

// Campos obligatorios que alimentan la barra de progreso. Coinciden con
// los validadores `required` del backend (PublicCarRequestController::store).
const REQUIRED_FIELDS = [
    'name', 'phone', 'brand', 'model', 'year_min', 'year_max',
    'budget_min', 'budget_max', 'mileage_max', 'power_min', 'power_max',
    'fuel', 'body_type',
];

const initialData = () => ({
    name: '',
    email: '',
    phone: '',
    brand: '',
    model: '',
    year_min: '',
    year_max: '',
    budget_min: '',
    budget_max: '',
    mileage_max: '',
    power_min: '',
    power_max: '',
    fuel: '',
    transmission: '',
    body_type: '',
    doors: '',
    seats: '',
    color: '',
    requirements: '',
    notes: '',
    website: '', // honeypot
});

const form = useForm(initialData());

const fuelTypes = computed(() => {
    const obj = t('cars.fuel_options');
    if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
        return Object.entries(obj).map(([value, label]) => ({ value, label }));
    }
    return (obj || []).map((label) => ({ value: label, label }));
});
const transmissions = computed(() => {
    const obj = t('cars.transmission_options');
    if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
        return Object.entries(obj).map(([value, label]) => ({ value, label }));
    }
    return (obj || []).map((label) => ({ value: label, label }));
});
const bodyTypes = computed(() => t('cars.body_type_options'));
const colors = computed(() => t('cars.color_options'));

// ── LocalStorage: persistencia de borrador ──────────────────────────
// Si el usuario rellena parte del form y vuelve, queremos que conserve
// los datos. Borramos al enviar con éxito o cuando el usuario pulse
// "Empezar de cero".
const showDraftBanner = ref(false);
const discardDraft = () => {
    try { localStorage.removeItem(draftKey.value); } catch {}
    form.defaults(initialData()).reset();
    showDraftBanner.value = false;
};

onMounted(() => {
    try {
        const raw = localStorage.getItem(draftKey.value);
        if (raw) {
            const data = JSON.parse(raw);
            // Solo restauramos si hay al menos un campo con valor y el
            // borrador tiene menos de 7 días (no queremos basura antigua).
            const hasAny = Object.values(data).some((v) => v && v !== '');
            if (hasAny) {
                Object.assign(form, data);
                showDraftBanner.value = true;
            }
        }
    } catch (e) {
        // Si falla (storage lleno, JSON corrupto), seguimos sin borrador.
    }
});

// Autosave: cada cambio persiste en localStorage (sin honeypot).
watch(form, (val) => {
    if (typeof localStorage === 'undefined') return;
    try {
        const { website, ...rest } = val;
        localStorage.setItem(draftKey.value, JSON.stringify(rest));
    } catch (e) {
        // Storage lleno o sin permisos: ignoramos silenciosamente.
    }
}, { deep: true });

// Pre-rellenar brand/model desde el contexto (?car=ID&brand=BMW&model=320d)
// si el form está vacío. Si hay draft, respetamos lo escrito por el usuario.
if (contextCar.value) {
    const ctx = contextCar.value;
    if (! form.brand && ctx.brand) form.brand = ctx.brand;
    if (! form.model && ctx.model) form.model = ctx.model;
}

// ── Progress indicator ──────────────────────────────────────────────
const progress = computed(() => {
    const filled = REQUIRED_FIELDS.filter((f) => {
        const v = form[f];
        return v !== null && v !== undefined && v !== '';
    }).length;
    return { done: filled, total: REQUIRED_FIELDS.length };
});
const progressPercent = computed(() =>
    Math.round((progress.value.done / progress.value.total) * 100)
);

// ── Error summary ───────────────────────────────────────────────────
const errorList = computed(() => {
    const list = [];
    for (const [field, message] of Object.entries(form.errors ?? {})) {
        if (message) {
            list.push({ field, message });
        }
    }
    return list;
});

// ── Char counters ───────────────────────────────────────────────────
const charCount = (field, max) => ({
    count: (form[field] || '').length,
    max,
});

// ── Submit ──────────────────────────────────────────────────────────
const submitting = ref(false);
const submit = () => {
    if (submitting.value) return;
    submitting.value = true;
    form.post(route('public.car-request.store', props.organization.slug), {
        preserveState: false,
        onSuccess: () => {
            // Borramos el borrador al enviar con éxito.
            try { localStorage.removeItem(draftKey.value); } catch {}
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="t('car_request_form.title')" />

    <MarketplaceLayout>
        <div class="bg-linear-to-br from-estoril-50 via-white to-platinum-100 transition-colors duration-300 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800">
            <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Banner: contexto del coche (cuando vienen de una ficha) -->
            <div
                v-if="contextCar"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-estoril-200 bg-estoril-50 p-4 dark:border-estoril-800 dark:bg-estoril-900/30"
                role="status"
            >
                <TruckIcon class="h-5 w-5 shrink-0 text-estoril-700 dark:text-estoril-300" />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-estoril-900 dark:text-estoril-100">
                        {{ t('car_request_form.context_title', { default: 'Sobre este coche' }) }}
                    </p>
                    <p class="mt-1 text-sm text-estoril-800 dark:text-estoril-200">
                        <strong>{{ contextCar.brand }} {{ contextCar.model }}</strong>
                        <span v-if="contextCar.version"> · {{ contextCar.version }}</span>
                        <span v-if="contextCar.year"> · {{ contextCar.year }}</span>
                    </p>
                </div>
                <Link
                    v-if="contextCar.href"
                    :href="contextCar.href"
                    class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-estoril-700 ring-1 ring-estoril-200 transition hover:bg-estoril-100 dark:bg-asphalt-800 dark:text-estoril-300 dark:ring-estoril-700 dark:hover:bg-asphalt-700"
                >
                    {{ t('car_request_form.context_back_to_car', { default: 'Ver ficha completa' }) }}
                </Link>
                <button
                    type="button"
                    @click="clearContextCar"
                    :aria-label="t('car_request_form.context_dismiss', { default: 'Quitar contexto' })"
                    class="rounded-md p-1 text-estoril-700 transition hover:bg-estoril-100 dark:text-estoril-300 dark:hover:bg-estoril-900/40"
                >
                    <XCircleIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- Header -->
            <div class="mb-10 text-center">
                <div v-if="organization.logo" class="mx-auto mb-6 h-20 w-20 overflow-hidden rounded-2xl bg-white shadow-lg">
                    <img :src="organization.logo" :alt="organization.name" class="h-full w-full object-cover" />
                </div>
                <h1 class="text-4xl font-bold text-gray-900">
                    {{ organization.name }}
                </h1>
                <p class="mt-3 text-lg text-gray-600">
                    {{ t('car_request_form.subtitle') }}
                </p>
            </div>

            <!-- Progress indicator (campos obligatorios) -->
            <div
                class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700"
                role="status"
                :aria-label="t('car_request_form.progress_title')"
            >
                <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>{{ t('car_request_form.progress_title') }}</span>
                    <span :class="progressPercent === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400'">
                        {{ progressPercent === 100
                            ? t('car_request_form.progress_complete')
                            : t('car_request_form.progress_count', { done: progress.done, total: progress.total }) }}
                    </span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-asphalt-700">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="progressPercent === 100 ? 'bg-emerald-500' : 'bg-estoril-600'"
                        :style="{ width: progressPercent + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Banner: borrador recuperado de localStorage -->
            <div
                v-if="showDraftBanner"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30"
                role="status"
            >
                <ArrowPathIcon class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-300" />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                        {{ t('car_request_form.draft_restored_title') }}
                    </p>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                        {{ t('car_request_form.draft_restored_help') }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="discardDraft"
                    class="rounded-md px-2 py-1 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 dark:text-amber-100 dark:hover:bg-amber-900/40"
                >
                    {{ t('car_request_form.draft_discard') }}
                </button>
            </div>

            <!-- Error summary (sticky arriba cuando hay errores) -->
            <div
                v-if="errorList.length > 0"
                class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800 dark:bg-rose-900/30"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <ExclamationCircleIcon class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-300" />
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-rose-900 dark:text-rose-100">
                            {{ t('car_request_form.error_summary_title', { count: errorList.length }) }}
                        </p>
                        <p class="mt-1 text-sm text-rose-800 dark:text-rose-200">
                            {{ t('car_request_form.error_summary_help') }}
                        </p>
                        <ul class="mt-2 list-inside list-disc text-sm text-rose-800 dark:text-rose-200">
                            <li v-for="err in errorList" :key="err.field">{{ err.message }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                <div class="border-b border-gray-200 bg-gray-50 px-8 py-6 dark:border-asphalt-700 dark:bg-asphalt-800">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-estoril-100 p-2 dark:bg-estoril-900/40">
                            <TruckIcon class="h-6 w-6 text-estoril-700 dark:text-estoril-300" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ t('car_request_form.section_prefs') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('car_request_form.section_required_help') }}</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-8 px-8 py-8">
                    <!-- Contact Info -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_contact') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_name') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_name')"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">{{ t('car_request_form.field_email') }}</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_email')"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_phone') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_phone')"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Car Preferences -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_car') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_brand') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.brand"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_brand')"
                                />
                                <p v-if="form.errors.brand" class="mt-1 text-sm text-rose-600">{{ form.errors.brand }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_model') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.model"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_model')"
                                />
                                <p v-if="form.errors.model" class="mt-1 text-sm text-rose-600">{{ form.errors.model }}</p>
                            </div>
                        </div>

                        <!-- Year Range -->
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_year_min') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.year_min" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                                </select>
                                <p v-if="form.errors.year_min" class="mt-1 text-sm text-rose-600">{{ form.errors.year_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_year_max') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.year_max" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                                </select>
                                <p v-if="form.errors.year_max" class="mt-1 text-sm text-rose-600">{{ form.errors.year_max }}</p>
                            </div>
                        </div>

                        <!-- Budget Range -->
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_budget_min') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.budget_min"
                                    type="number"
                                    min="0"
                                    step="500"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_budget')"
                                />
                                <p v-if="form.errors.budget_min" class="mt-1 text-sm text-rose-600">{{ form.errors.budget_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_budget_max') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.budget_max"
                                    type="number"
                                    min="0"
                                    step="500"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_budget_max')"
                                />
                                <p v-if="form.errors.budget_max" class="mt-1 text-sm text-rose-600">{{ form.errors.budget_max }}</p>
                            </div>
                        </div>

                        <!-- Mileage -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                {{ t('car_request_form.field_mileage_max') }}
                                <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                v-model="form.mileage_max"
                                type="number"
                                min="0"
                                step="10000"
                                required
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                :placeholder="t('car_request_form.placeholder_mileage')"
                            />
                            <p v-if="form.errors.mileage_max" class="mt-1 text-sm text-rose-600">{{ form.errors.mileage_max }}</p>
                        </div>
                    </div>

                    <!-- Technical Preferences -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_tech') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="mb-2 flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <span>{{ t('car_request_form.field_fuel') }}</span>
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                    <span class="group relative ml-1 inline-flex">
                                        <InformationCircleIcon class="h-4 w-4 cursor-help text-gray-400 transition-colors group-hover:text-estoril-600 dark:text-gray-500 dark:group-hover:text-estoril-400" />
                                        <span
                                            role="tooltip"
                                            class="pointer-events-none invisible absolute left-1/2 top-full z-10 mt-2 w-64 -translate-x-1/2 rounded-lg bg-asphalt-900 px-3 py-2 text-xs leading-relaxed text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:visible group-hover:opacity-100 dark:bg-asphalt-700"
                                        >
                                            <strong class="block pb-1">{{ t('car_request_form.fuel_help_title') }}</strong>
                                            {{ t('car_request_form.fuel_help_body') }}
                                        </span>
                                    </span>
                                </label>
                                <select v-model="form.fuel" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="fuel in fuelTypes" :key="fuel.value" :value="fuel.value">{{ fuel.label }}</option>
                                </select>
                                <p v-if="form.errors.fuel" class="mt-1 text-sm text-rose-600">{{ form.errors.fuel }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_body_type') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.body_type" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="type in bodyTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                                <p v-if="form.errors.body_type" class="mt-1 text-sm text-rose-600">{{ form.errors.body_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_transmission') }}
                                </label>
                                <select v-model="form.transmission" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.any_option') }}</option>
                                    <option v-for="trans in transmissions" :key="trans.value" :value="trans.value">{{ trans.label }}</option>
                                </select>
                                <p v-if="form.errors.transmission" class="mt-1 text-sm text-rose-600">{{ form.errors.transmission }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_power_min') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.power_min"
                                    type="number"
                                    min="50"
                                    max="2000"
                                    step="10"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_power')"
                                />
                                <p v-if="form.errors.power_min" class="mt-1 text-sm text-rose-600">{{ form.errors.power_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_power_max') }}
                                    <span class="ml-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.power_max"
                                    type="number"
                                    min="50"
                                    max="2000"
                                    step="10"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_power_max')"
                                />
                                <p v-if="form.errors.power_max" class="mt-1 text-sm text-rose-600">{{ form.errors.power_max }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('car_request_form.field_doors') }}</label>
                                <select v-model="form.doors" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.any_option') }}</option>
                                    <option :value="3">3</option>
                                    <option :value="5">5</option>
                                </select>
                                <p v-if="form.errors.doors" class="mt-1 text-sm text-rose-600">{{ form.errors.doors }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_seats') }}
                                </label>
                                <select v-model="form.seats" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.any_option') }}</option>
                                    <option :value="5">5</option>
                                    <option :value="7">7</option>
                                    <option :value="9">9</option>
                                </select>
                                <p v-if="form.errors.seats" class="mt-1 text-sm text-rose-600">{{ form.errors.seats }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_color') }}
                                </label>
                                <select v-model="form.color" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.any_option') }}</option>
                                    <option v-for="color in colors" :key="color" :value="color">{{ color }}</option>
                                </select>
                                <p v-if="form.errors.color" class="mt-1 text-sm text-rose-600">{{ form.errors.color }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_additional') }}</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_requirements') }}
                                </label>
                                <textarea
                                    v-model="form.requirements"
                                    rows="3"
                                    maxlength="2000"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_requirements')"
                                ></textarea>
                                <div class="mt-1 flex items-center justify-between">
                                    <p v-if="form.errors.requirements" class="text-sm text-rose-600">{{ form.errors.requirements }}</p>
                                    <span v-else></span>
                                    <p class="text-xs text-gray-400">{{ t('car_request_form.char_counter', charCount('requirements', 2000)) }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">{{ t('car_request_form.field_notes') }}</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    maxlength="2000"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_notes')"
                                ></textarea>
                                <div class="mt-1 flex items-center justify-between">
                                    <p v-if="form.errors.notes" class="text-sm text-rose-600">{{ form.errors.notes }}</p>
                                    <span v-else></span>
                                    <p class="text-xs text-gray-400">{{ t('car_request_form.char_counter', charCount('notes', 2000)) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6 dark:border-asphalt-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ t('car_request_form.privacy') }}
                        </p>
                        <button
                            type="submit"
                            :disabled="form.processing || submitting"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-estoril-800 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing || submitting" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <CheckCircleIcon v-else class="h-5 w-5" />
                            <span v-if="form.processing || submitting">{{ t('car_request_form.sending') }}</span>
                            <span v-else>{{ t('car_request_form.submit') }}</span>
                        </button>
                    </div>

                    <!-- Honeypot anti-spam field (hidden from real users) -->
                    <div class="absolute -left-[9999px] top-0 h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
                        <label for="website">{{ t('car_request_form.honeypot_label') }}</label>
                        <input
                            id="website"
                            name="website"
                            v-model="form.website"
                            type="text"
                            tabindex="-1"
                            autocomplete="new-password"
                        />
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>&copy; {{ new Date().getFullYear() }} {{ organization.name }}. {{ t('car_request_form.rights') }}</p>
            </div>
        </div>

        <!-- Floating download button for JJ Import Motors folleto -->
        <div class="fixed bottom-6 right-6 z-40">
            <a
                href="/jj-import-folleto.pdf"
                download="JJ_Import_Motors_Folleto.pdf"
                class="flex items-center gap-2 rounded-full bg-estoril-700 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-estoril-800 transition-colors"
            >
                <DocumentArrowDownIcon class="h-5 w-5" />
                <span class="hidden sm:inline">{{ t('car_request_form.brochure_label') }}</span>
            </a>
        </div>
        </div>
    </MarketplaceLayout>
</template>
