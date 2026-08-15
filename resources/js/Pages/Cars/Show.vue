<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    PencilIcon,
    SparklesIcon,
    MegaphoneIcon,
    TrashIcon,
    DocumentIcon,
    ArrowDownTrayIcon,
    EyeIcon,
    UserCircleIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    LinkIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MapaLeaflet from '@/Components/MapaLeaflet.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    car: Object,
    derived: Object,
});

const uploadProgress = ref(false);
const showDeletePhoto = ref(false);
const showDeleteDoc = ref(false);
const photoToDelete = ref(null);
const docToDelete = ref(null);

// Tabs de sección (para no hacer scroll gigante)
const sections = [
    { id: 'resumen', label: t('cars.section_resumen') },
    { id: 'investigacion', label: t('cars.section_investigacion') },
    { id: 'mercado', label: t('cars.section_mercado') },
    { id: 'checklist', label: 'Checklist' },
    { id: 'documentos', label: t('cars.documents') },
    { id: 'fotos', label: t('cars.photos_title') },
    { id: 'gastos', label: t('cars.expenses_vs_estimated') },
];
const activeSection = ref('resumen');

// Lightbox de fotos
const lightboxIndex = ref(-1);
const lightboxPhotos = computed(() => props.car.photos || []);
const openLightbox = (index) => { lightboxIndex.value = index; };
const closeLightbox = () => { lightboxIndex.value = -1; };
const nextLightbox = () => {
    if (lightboxPhotos.value.length === 0) return;
    lightboxIndex.value = (lightboxIndex.value + 1) % lightboxPhotos.value.length;
};
const prevLightbox = () => {
    if (lightboxPhotos.value.length === 0) return;
    lightboxIndex.value = (lightboxIndex.value - 1 + lightboxPhotos.value.length) % lightboxPhotos.value.length;
};

const photoForm = useForm({ photo_type: 'exterior', photos: [] });
const docForm = useForm({ doc_type: 'invoice', doc_key: '', name: '', documents: [] });

const { currency, date, statusLabel, statusVariant, trafficLightVariant } = useFormat();

const submitPhotos = () => {
    uploadProgress.value = true;
    photoForm.post(route('cars.photos.store', props.car.id), {
        onSuccess: () => {
            photoForm.reset('photos');
            uploadProgress.value = false;
        },
        onError: () => (uploadProgress.value = false),
    });
};

const submitDocuments = () => {
    uploadProgress.value = true;
    docForm.post(route('cars.documents.store', props.car.id), {
        onSuccess: () => {
            docForm.reset('documents', 'name');
            uploadProgress.value = false;
        },
        onError: () => (uploadProgress.value = false),
    });
};

const handlePhotoFiles = (event) => { photoForm.photos = event.target.files; };
const handleDocFiles = (event) => { docForm.documents = event.target.files; };

const askDeletePhoto = (photo) => { photoToDelete.value = photo; showDeletePhoto.value = true; };
const confirmDeletePhoto = () => {
    if (!photoToDelete.value) return;
    useForm({}).delete(route('cars.photos.destroy', [props.car.id, photoToDelete.value.id]), {
        onSuccess: () => { showDeletePhoto.value = false; photoToDelete.value = null; },
    });
};

const askDeleteDoc = (doc) => { docToDelete.value = doc; showDeleteDoc.value = true; };
const confirmDeleteDoc = () => {
    if (!docToDelete.value) return;
    useForm({}).delete(route('cars.documents.destroy', [props.car.id, docToDelete.value.id]), {
        onSuccess: () => { showDeleteDoc.value = false; docToDelete.value = null; },
    });
};

const costItems = [
    { key: 'purchase_price', label: t('cars.cost_items.purchase_price') },
    { key: 'new_price', label: t('cars.cost_items.new_price') },
    { key: 'transport', label: t('cars.cost_items.transport') },
    { key: 'itv_fee', label: t('cars.cost_items.itv_fee') },
    { key: 'coc_fee', label: t('cars.cost_items.coc_fee') },
    { key: 'dgt_fees', label: t('cars.cost_items.dgt_fees') },
    { key: 'professional_fees', label: t('cars.cost_items.professional_fees') },
    { key: 'deposit', label: t('cars.cost_items.deposit') },
];

const specItems = [
    { key: 'brand', label: t('cars.spec_items.brand') },
    { key: 'model', label: t('cars.spec_items.model') },
    { key: 'year', label: t('cars.spec_items.year') },
    { key: 'mileage', label: t('cars.spec_items.mileage'), suffix: ' km' },
    { key: 'fuel', label: t('cars.spec_items.fuel') },
    { key: 'transmission', label: t('cars.spec_items.transmission') },
    { key: 'cv', label: t('cars.spec_items.power'), suffix: ' CV' },
    { key: 'co2', label: 'CO₂', suffix: ' g/km' },
    { key: 'color', label: t('cars.spec_items.color') },
    { key: 'vin', label: 'VIN' },
];

// Enriched valuation helpers
const verdictVariant = (v) => {
    switch (v) {
        case 'Buy': return 'success';
        case 'Buy if price drops': return 'warning';
        case 'Discard': return 'danger';
        default: return 'neutral';
    }
};
const confidenceVariant = (c) => ({
    high: 'success', medium: 'warning', low: 'danger',
}[c] || 'neutral');
const ratingVariant = (r) => ({
    favorable: 'success', neutral: 'neutral', unfavorable: 'danger',
}[r] || 'neutral');
const ratingIcon = (r) => ({
    favorable: CheckCircleIcon, neutral: MinusCircleIcon, unfavorable: XCircleIcon,
}[r] || MinusCircleIcon);

const researchAspectLabels = {
    common_issues: 'Problemas habituales',
    recalls: 'Llamadas a revisión',
    market_price: 'Precio de mercado',
    reliability: 'Fiabilidad',
    spain_homologation: 'Homologación en España',
    dgt_label: 'Etiqueta ambiental DGT',
    insurance_estimate: 'Estimación de seguro',
    parts_maintenance: 'Piezas y mantenimiento',
    unit_specific: 'Específico de la unidad',
};

const aspects = computed(() => {
    const research = props.car.research || {};
    const gaps = props.derived?.research_gaps || [];
    return Object.entries(researchAspectLabels).map(([key, label]) => {
        const entry = research[key] || {};
        return { key, label, ...entry, missing: gaps.includes(key) };
    });
});

const marketPosition = computed(() => {
    const stats = props.derived?.comparables_stats || {};
    const total = props.derived?.total_cost || 0;
    if (!stats.avg || !total) return null;
    const ratio = total / stats.avg;
    if (ratio <= 1.00) return { label: 'Por debajo del mercado', variant: 'success', ratio };
    if (ratio <= 1.05) return { label: 'En mercado', variant: 'warning', ratio };
    return { label: 'Por encima del mercado', variant: 'danger', ratio };
});

const expandedSections = ref({});
const toggleSection = (key) => { expandedSections.value[key] = !expandedSections.value[key]; };

const priorityVariant = (p) => ({
    critical: 'danger', important: 'warning', minor: 'neutral',
}[p] || 'neutral');

const docStatusVariant = (s) => ({
    pending: 'neutral', ordered: 'warning', received: 'success', not_applicable: 'neutral',
}[s] || 'neutral');

const toggleMilestone = (item) => {
    useForm({ completed: !item.completed }).post(route('cars.checklists.toggle', [props.car.id, item.id]), {
        preserveScroll: true,
    });
};
const toggleInspection = (item) => {
    useForm({ completed: !item.completed }).post(route('cars.checklists.toggle', [props.car.id, item.id]), {
        preserveScroll: true,
    });
};

const onDocKeyChange = () => {
    // Auto-fill name from selected doc_key for convenience
    const def = props.derived?.documents_by_group
        ?.flatMap((g) => g.items)
        ?.find((d) => d.doc_key === docForm.doc_key);
    if (def && !docForm.name) {
        docForm.name = def.name;
    }
};
</script>

<template>
    <Head :title="t('cars.marketplace_brand_model', { brand: car.brand, model: car.model })" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ car.brand }} {{ car.model }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('cars.marketplace_brand_model', { brand: car.brand, model: car.model })" :subtitle="`VIN ${car.vin || '—'}`">
                    <template #actions>
                        <Link :href="route('cars.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                        <Link v-if="['Located', 'Valuing'].includes(car.status)" :href="route('cars.verify.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <SparklesIcon class="h-4 w-4" />
                            {{ t('cars.verify_with_ai') }}
                        </Link>
                        <a :href="route('cars.ficha', car.id)" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-600">
                            <DocumentIcon class="h-4 w-4" />
                            {{ t('cars.client_sheet') }}
                        </a>
                        <a :href="route('cars.informe-interno', car.id)" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-asphalt-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-asphalt-800">
                            <DocumentIcon class="h-4 w-4" />
                            {{ t('cars.internal_report') }}
                        </a>
                        <Link :href="route('cars.marketing', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-600">
                            <MegaphoneIcon class="h-4 w-4" />
                            {{ t('cars.marketing') }}
                        </Link>
                        <Link :href="route('cars.edit', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PencilIcon class="h-4 w-4" />
                            {{ t('cars.edit_action') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Status bar -->
                <div class="flex flex-wrap items-center gap-3">
                    <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                    <Badge :variant="statusVariant(car.status)">{{ statusLabel(t, car.status) }}</Badge>
                    <span v-if="car.year" class="text-sm text-gray-500">{{ car.year }}</span>
                </div>

                <!-- Section tabs -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <nav class="flex gap-1 p-1.5">
                        <button
                            v-for="sec in sections"
                            :key="sec.id"
                            type="button"
                            @click="activeSection = sec.id"
                            :class="[
                                'whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition',
                                activeSection === sec.id
                                    ? 'bg-estoril-600 text-white shadow'
                                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                            ]"
                        >
                            {{ sec.label }}
                        </button>
                    </nav>
                </div>

                <!-- IEDMT estimation warning (permanent, per plan) -->
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-amber-600" />
                    <div class="text-sm text-amber-900">
                        <p class="font-semibold">{{ t('cars.iedmt_estimate') }}</p>
                        <p class="mt-1 text-amber-800">
                            {{ t('cars.iedmt_explanation') }}
                            <span class="font-mono font-semibold">{{ currency(derived?.iedmt) }}</span>
                            &middot; {{ t('cars.total_cost_label') }}:
                            <span class="font-mono font-semibold">{{ currency(derived?.total_cost) }}</span>
                        </p>
                    </div>
                </div>

                <!-- Location -->
                <div v-if="activeSection === 'resumen' && car.lat && car.lng" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.location') }}</h3>
                    </div>
                    <div class="p-6">
                        <MapaLeaflet :lat="car.lat" :lng="car.lng" :marker-text="t('cars.car_full_name', { brand: car.brand, model: car.model, year: car.city || '' })" height="300px" />
                    </div>
                </div>

                <!-- Technical specs -->
                <div v-show="activeSection === 'resumen'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.section_tech_sheet') }}</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                        <div v-for="spec in specItems" :key="spec.key">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ spec.label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900" :class="{ 'font-mono text-xs': spec.key === 'vin' }">{{ car[spec.key] ?? '—' }}{{ spec.suffix || '' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Costs -->
                <div v-show="activeSection === 'resumen'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.costs') }}</h3>
                        <span class="text-sm text-gray-500">{{ t('cars.total_all_included') }} <span class="font-mono font-semibold text-gray-900">{{ currency(derived?.total_cost) }}</span></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                        <div v-for="cost in costItems" :key="cost.key">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ cost.label }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ currency(car[cost.key]) }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Descripción del anuncio (original + traducción) -->
                <div v-if="activeSection === 'resumen' && (car.description || car.original_description)" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.description') }}</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div v-if="car.original_description">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.original_text') }}</h4>
                            <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{{ car.original_description }}</p>
                        </div>
                        <div v-if="car.description && car.description !== car.original_description">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.translated_text') }}</h4>
                            <p class="mt-2 text-sm text-gray-900 whitespace-pre-wrap">{{ car.description }}</p>
                        </div>
                        <p v-else-if="!car.original_description" class="text-sm text-gray-500">{{ t('cars.description') }}</p>
                    </div>
                </div>

                <!-- Equipamiento del anuncio (lista COMPLETA del JSON) -->
                <div v-if="activeSection === 'resumen' && car.equipment?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.equipment') }}</h3>
                    </div>
                    <div class="p-6">
                        <ul class="flex flex-wrap gap-2">
                            <li v-for="(item, i) in car.equipment" :key="i"
                                class="rounded-full bg-estoril-50 px-3 py-1 text-sm text-estoril-700 ring-1 ring-estoril-100">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ╔ INVESTIGATION ══════════════════════════════════════════════════════╗ -->
                <div v-show="activeSection === 'investigacion'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Investigación</h3>
                        <span v-if="derived?.research_gaps?.length" class="text-xs font-medium text-amber-700">
                            {{ derived.research_gaps.length }} aspecto{{ derived.research_gaps.length === 1 ? '' : 's' }} pendiente{{ derived.research_gaps.length === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <!-- Verdict -->
                    <div v-if="car.verdict" class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge :variant="verdictVariant(car.verdict)" dot size="lg">{{ car.verdict }}</Badge>
                            <Badge v-if="car.verdict_confidence" :variant="confidenceVariant(car.verdict_confidence)">
                                Confianza: {{ car.verdict_confidence }}
                            </Badge>
                            <span v-if="car.verdict_at" class="text-xs text-gray-500">
                                {{ date(car.verdict_at) }}
                            </span>
                        </div>
                        <p v-if="car.verdict_reasoning" class="mt-3 text-sm text-gray-700">{{ car.verdict_reasoning }}</p>
                        <p v-if="car.verdict_changes" class="mt-2 text-xs italic text-gray-600">
                            <span class="font-semibold not-italic text-gray-700">Qué cambiaría esto:</span>
                            {{ car.verdict_changes }}
                        </p>
                    </div>

                    <div v-if="!car.verdict && !derived?.research_gaps?.length" class="px-6 py-8 text-center text-sm text-gray-500">
                        No hay valoración todavía. Importa un informe del chat o ejecuta el verificador de IA.
                    </div>

                    <!-- Balance pros / cons -->
                    <div v-if="(car.pros?.length || car.cons?.length)" class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-green-700">{{ t('cars.pros_label', { count: car.pros?.length || 0 }) }}</h4>
                            <ul v-if="car.pros?.length" class="space-y-2">
                                <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 p-3">
                                    <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ pro.text }}</p>
                                        <span class="mt-1 inline-block text-xs font-medium uppercase text-green-700">{{ pro.weight }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-sm italic text-gray-500">{{ t('cars.no_pros') }}</p>
                        </div>
                        <div>
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-red-700">{{ t('cars.cons_label', { count: car.cons?.length || 0 }) }}</h4>
                            <ul v-if="car.cons?.length" class="space-y-2">
                                <li v-for="(con, i) in car.cons" :key="i" class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3">
                                    <XCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-red-600" />
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ con.text }}</p>
                                        <span class="mt-1 inline-block text-xs font-medium uppercase text-red-700">{{ con.weight }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-sm italic text-gray-500">{{ t('cars.no_cons') }}</p>
                        </div>
                    </div>

                    <!-- 9 research aspects -->
                    <div class="border-t border-gray-200 px-6 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.research_aspects') }}</h4>
                        <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <li v-for="aspect in aspects" :key="aspect.key" class="rounded-lg border p-3"
                                :class="aspect.missing ? 'border-dashed border-gray-300 bg-gray-50' : 'border-gray-200 bg-white'">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900">{{ aspect.label }}</p>
                                    <component v-if="!aspect.missing" :is="ratingIcon(aspect.rating)" class="h-4 w-4 shrink-0"
                                        :class="{
                                            'text-green-600': aspect.rating === 'favorable',
                                            'text-red-600': aspect.rating === 'unfavorable',
                                            'text-gray-500': aspect.rating === 'neutral' || !aspect.rating,
                                        }" />
                                </div>
                                <p v-if="aspect.missing" class="mt-2 text-xs italic text-gray-500">{{ t('cars.not_researched_yet') }}</p>
                                <template v-else>
                                    <p v-if="aspect.finding" class="mt-2 text-sm text-gray-700">{{ aspect.finding }}</p>
                                    <a v-if="aspect.source" :href="aspect.source" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs text-estoril-600 hover:text-estoril-500">
                                        <LinkIcon class="h-3 w-3" />
                                        Fuente
                                    </a>
                                    <span v-if="aspect.date" class="ml-2 text-xs text-gray-400">{{ aspect.date }}</span>
                                </template>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ╔ MARKET ═══════════════════════════════════════════════════════════╗ -->
                <div v-if="activeSection === 'mercado' && (car.market_avg || derived?.comparables_stats?.count)" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.market_comparables') }}</h3>
                        <Badge v-if="marketPosition" :variant="marketPosition.variant">
                            {{ marketPosition.label }} ({{ (marketPosition.ratio * 100).toFixed(1) }}%)
                        </Badge>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Media</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_avg) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Mínimo</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_min) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Máximo</dt>
                            <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_max) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ahorro estimado</dt>
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
                                        <span v-if="comp.country"> · {{ comp.country || comp.pais }}</span>
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

                <!-- ╔ CHECKLIST ════════════════════════════════════════════════════════╗ -->
                <div v-show="activeSection === 'checklist'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Checklist</h3>
                    </div>

                    <!-- Milestones progress -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700">Hitos</h4>
                            <span class="text-sm font-mono font-semibold text-gray-900">
                                {{ derived?.milestones_progress?.completed || 0 }} / {{ derived?.milestones_progress?.total || 0 }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full bg-estoril-500 transition-all"
                                :style="{ width: derived?.milestones_progress?.total ? ((derived.milestones_progress.completed / derived.milestones_progress.total) * 100) + '%' : '0%' }" />
                        </div>
                        <ul class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <li v-for="m in car.checklists?.filter(c => c.kind === 'milestone') || []" :key="m.id"
                                class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3">
                                <button @click="toggleMilestone(m)" type="button" class="shrink-0">
                                    <CheckCircleIcon v-if="m.completed" class="h-5 w-5 text-green-600" />
                                    <MinusCircleIcon v-else class="h-5 w-5 text-gray-400" />
                                </button>
                                <div class="flex-1">
                                    <p class="text-sm font-medium" :class="m.completed ? 'text-gray-500 line-through' : 'text-gray-900'">
                                        {{ m.item_key.replace(/_/g, ' ') }}
                                    </p>
                                    <p v-if="m.completed_at" class="text-xs text-gray-500">{{ date(m.completed_at) }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Inspections -->
                    <div class="px-6 py-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                                Inspección ({{ derived?.inspections_progress?.completed || 0 }} / {{ derived?.inspections_progress?.total || 0 }})
                            </h4>
                            <span class="text-xs text-gray-500">{{ t('cars.apply_what_relevant') }}</span>
                        </div>
                        <div class="space-y-2">
                            <div v-for="section in derived?.inspections_by_section || []" :key="section.section" class="rounded-lg border border-gray-200">
                                <button @click="toggleSection(section.section)" type="button" class="flex w-full items-center justify-between px-4 py-2 hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <component :is="expandedSections[section.section] ? ChevronDownIcon : ChevronRightIcon" class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm font-semibold text-gray-900">{{ section.section }}</span>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ section.items.filter(i => i.completed).length }} / {{ section.items.length }}
                                    </span>
                                </button>
                                <ul v-if="expandedSections[section.section]" class="divide-y divide-gray-100 border-t border-gray-200">
                                    <li v-for="item in section.items" :key="item.id" class="flex items-start gap-3 px-4 py-2 hover:bg-gray-50">
                                        <button @click="toggleInspection(item)" type="button" class="mt-0.5 shrink-0">
                                            <CheckCircleIcon v-if="item.completed" class="h-5 w-5 text-green-600" />
                                            <MinusCircleIcon v-else class="h-5 w-5 text-gray-400" />
                                        </button>
                                        <div class="flex-1">
                                            <p class="text-sm" :class="item.completed ? 'text-gray-500 line-through' : 'text-gray-900'">
                                                {{ item.item_key.replace(/_/g, ' ') }}
                                            </p>
                                            <Badge v-if="item.priority" :variant="priorityVariant(item.priority)" size="sm">
                                                {{ item.priority }}
                                            </Badge>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ╔ DOCUMENTS ════════════════════════════════════════════════════════╗ -->
                <div v-show="activeSection === 'documentos'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.documents') }}</h3>
                        <span class="text-sm text-gray-500">{{ car.documents?.length || 0 }} {{ t('cars.files') }}</span>
                    </div>
                    <div class="p-6 space-y-4">
                        <form @submit.prevent="submitDocuments" class="grid grid-cols-1 gap-3 rounded-xl bg-gray-50 p-4 sm:grid-cols-4">
                            <select v-model="docForm.doc_key" @change="onDocKeyChange" class="rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500">
                                <option value="">{{ t('cars.document_type_placeholder') }}</option>
                                <optgroup v-for="g in derived?.documents_by_group || []" :key="g.group" :label="g.label">
                                    <option v-for="d in g.items" :key="d.id" :value="d.doc_key">{{ d.name }}</option>
                                </optgroup>
                            </select>
                            <input v-model="docForm.name" type="text" :placeholder="t('cars.doc_name_placeholder')" class="rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500" />
                            <input type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx" @change="handleDocFiles" class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-estoril-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-estoril-700 hover:file:bg-estoril-100" />
                            <button type="submit" :disabled="!docForm.documents.length || uploadProgress" class="inline-flex items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500 disabled:opacity-50">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                {{ uploadProgress ? t('cars.uploading') : t('cars.upload') }}
                            </button>
                        </form>

                        <div v-for="g in derived?.documents_by_group || []" :key="g.group" class="space-y-2">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ g.label }}</h4>
                            <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                                <li v-for="doc in g.items" :key="doc.id" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <DocumentIcon class="h-8 w-8 shrink-0 text-gray-400" />
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-gray-900 truncate">{{ doc.name }}</p>
                                            <p class="text-xs text-gray-500">{{ doc.doc_key }} · {{ doc.doc_type }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="docStatusVariant(doc.status)" size="sm">{{ statusLabel(t, doc.status) }}</Badge>
                                        <a v-if="doc.url" :href="`/storage/${doc.url}`" target="_blank" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                            <EyeIcon class="h-3 w-3" />
                                            View
                                        </a>
                                        <button v-if="doc.url" @click="askDeleteDoc(doc)" class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            <TrashIcon class="h-3 w-3" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Photos -->
                <div v-show="activeSection === 'fotos'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Photos</h3>
                        <span class="text-sm text-gray-500">{{ car.photos?.length || 0 }} files</span>
                    </div>
                    <div class="p-6 space-y-4">
                        <form @submit.prevent="submitPhotos" class="grid grid-cols-1 gap-3 rounded-xl bg-gray-50 p-4 sm:grid-cols-[1fr_2fr_auto]">
                            <select v-model="photoForm.photo_type" class="block rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500">
                                <option value="exterior">Exterior</option>
                                <option value="interior">Interior</option>
                                <option value="engine">Engine</option>
                                <option value="defect">Defect</option>
                                <option value="document">Document</option>
                            </select>
                            <input type="file" multiple accept="image/*" @change="handlePhotoFiles" class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-estoril-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-estoril-700 hover:file:bg-estoril-100" />
                            <button type="submit" :disabled="!photoForm.photos.length || uploadProgress" class="inline-flex items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500 disabled:opacity-50">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                {{ uploadProgress ? 'Uploading...' : 'Upload' }}
                            </button>
                        </form>

                        <div v-if="car.photos?.length" class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            <div v-for="(photo, idx) in car.photos" :key="photo.id" class="group relative overflow-hidden rounded-lg">
                                <button type="button" @click="openLightbox(idx)" class="block h-full w-full">
                                    <img :src="`/storage/${photo.url}`" :alt="photo.photo_type" class="h-32 w-full cursor-zoom-in object-cover" loading="lazy" />
                                </button>
                                <!-- Delete en la esquina superior derecha, sin overlay -->
                                <button type="button" @click.stop="askDeletePhoto(photo)" class="absolute right-1.5 top-1.5 rounded-md bg-rose-600/90 p-1.5 text-white opacity-0 shadow transition hover:bg-rose-600 group-hover:opacity-100" :title="t('common.delete')">
                                    <TrashIcon class="h-3.5 w-3.5" />
                                </button>
                                <span class="absolute bottom-2 left-2 rounded bg-black/70 px-2 py-0.5 text-xs text-white">{{ photo.photo_type }}</span>
                            </div>
                        </div>
                        <p v-else class="py-6 text-center text-sm text-gray-500">{{ t('cars.no_photos_yet') }}</p>
                    </div>
                </div>

                <!-- Lightbox de fotos -->
                <Teleport to="body">
                    <div v-if="lightboxIndex >= 0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" @click="closeLightbox">
                        <button type="button" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" @click.stop="closeLightbox">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                        <button v-if="lightboxPhotos.length > 1" type="button" class="absolute left-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" @click.stop="prevLightbox">‹</button>
                        <img v-if="lightboxPhotos[lightboxIndex]" :src="`/storage/${lightboxPhotos[lightboxIndex].url}`" :alt="lightboxPhotos[lightboxIndex].photo_type" class="max-h-[85vh] max-w-full rounded-lg object-contain shadow-2xl" @click.stop />
                        <button v-if="lightboxPhotos.length > 1" type="button" class="absolute right-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" @click.stop="nextLightbox">›</button>
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-4 py-1.5 text-sm text-white">
                            {{ lightboxIndex + 1 }} / {{ lightboxPhotos.length }}
                        </div>
                    </div>
                </Teleport>

                <!-- Solicitudes de clientes compatibles (matching por marca/modelo) -->
                <div v-if="activeSection === 'resumen' && derived?.matching_requests?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.matching_requests') }}</h3>
                        <span class="text-xs text-gray-500">{{ derived.matching_requests.length }}</span>
                    </div>
                    <ul class="divide-y divide-gray-200">
                        <li v-for="req in derived.matching_requests" :key="req.id" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-gray-50">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ req.name || t('cars.matching_no_name') }}</p>
                                <p class="text-xs text-gray-500">
                                    <span v-if="req.brand">{{ req.brand }}{{ req.model ? ' ' + req.model : '' }} · </span>
                                    <span v-if="req.budget_max">{{ t('cars.matching_budget') }} {{ currency(req.budget_max) }} · </span>
                                    <span>{{ statusLabel(t, req.status) }}</span>
                                </p>
                            </div>
                            <Link
                                :href="route('cars.match-request', { car: car.id, carRequest: req.id })"
                                method="post"
                                as="button"
                                class="shrink-0 rounded-lg bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500"
                            >
                                {{ t('cars.matching_link') }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Assigned Client -->
                <div v-if="car.client" class="overflow-hidden rounded-2xl bg-linear-to-br from-blue-50 to-estoril-50 shadow-sm ring-1 ring-blue-200">
                    <div class="border-b border-blue-200 px-6 py-4 flex items-center gap-2">
                        <UserCircleIcon class="h-5 w-5 text-blue-600" />
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.assigned_client') }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ car.client.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Contact</dt>
                            <dd class="mt-1 text-sm text-gray-700">{{ car.client.contact_info || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</dt>
                            <dd class="mt-1"><Badge :variant="statusVariant(car.client.status)">{{ statusLabel(t, car.client.status) }}</Badge></dd>
                        </div>
                        <div class="flex items-end">
                            <Link :href="route('clients.show', car.client.id)" class="inline-flex items-center gap-1 text-sm font-semibold text-estoril-600 hover:text-estoril-500">
                                View client →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="car.notes" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Notes</h3>
                    </div>
                    <div class="p-6">
                        <pre class="whitespace-pre-wrap font-sans text-sm text-gray-700">{{ car.notes }}</pre>
                    </div>
                </div>

                <!-- Expenses -->
                <div v-show="activeSection === 'gastos'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.expenses_vs_estimated') }}</h3>
                    </div>
                    <div v-if="car.expenses?.length" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Concept</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Estimated</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actual</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Diff</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="exp in car.expenses" :key="exp.id">
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ exp.concept }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-sm text-gray-700">{{ currency(exp.estimated) }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-sm text-gray-900">{{ currency(exp.actual) }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-sm" :class="(exp.actual - exp.estimated) > 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ currency(exp.actual - exp.estimated) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="p-6 text-center text-sm text-gray-500">{{ t('cars.no_expenses_logged') }}</p>
                </div>
            </div>
        </div>

        <ConfirmDialog :show="showDeletePhoto" :title="t('cars.delete_photo')" :message="t('cars.delete_photo_msg')" :confirm-text="t('common.delete')" :cancel-text="t('common.cancel')" @confirm="confirmDeletePhoto" @close="showDeletePhoto = false" @cancel="showDeletePhoto = false" />
        <ConfirmDialog :show="showDeleteDoc" :title="t('cars.delete_document')" :message="t('cars.delete_document_msg')" :confirm-text="t('common.delete')" :cancel-text="t('common.cancel')" @confirm="confirmDeleteDoc" @close="showDeleteDoc = false" @cancel="showDeleteDoc = false" />
    </AuthenticatedLayout>
</template>
