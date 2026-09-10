<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    TrashIcon,
    DocumentIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    EyeIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    LinkIcon,
    GlobeEuropeAfricaIcon,
    ShareIcon,
    ClipboardDocumentIcon,
    ArrowPathIcon,
    DocumentCheckIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import HeaderBar from '@/Pages/Cars/Partials/HeaderBar.vue';
import OverviewPanel from '@/Pages/Cars/Partials/OverviewPanel.vue';
import InvestigationPanel from '@/Pages/Cars/Partials/InvestigationPanel.vue';
import MarketPanel from '@/Pages/Cars/Partials/MarketPanel.vue';
import ChecklistPanel from '@/Pages/Cars/Partials/ChecklistPanel.vue';
import AssignRequestPanel from '@/Pages/Cars/Partials/AssignRequestPanel.vue';
import NotesPanel from '@/Pages/Cars/Partials/NotesPanel.vue';
import ExpensesPanel from '@/Pages/Cars/Partials/ExpensesPanel.vue';
import PhotosPanel from '@/Pages/Cars/Partials/PhotosPanel.vue';
import ShareTrackingModal from '@/Pages/Cars/Modals/ShareTrackingModal.vue';
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

// Vincular coche ↔ solicitud compatible (seleccionada por el admin)
const linkingId = ref(null);
const linkError = ref(null);
const linkSuccess = ref(null);
const linkForm = useForm({});
const linkSelectedRequest = (reqId) => {
    if (!reqId || linkingId.value !== null) return;
    linkingId.value = reqId;
    linkError.value = null;
    linkSuccess.value = null;
    linkForm.post(route('cars.match-request', { car: props.car.id, carRequest: reqId }), {
        preserveScroll: true,
        onSuccess: (page) => {
            linkSuccess.value = t('cars.matching_link_success');
            linkError.value = null;
            linkingId.value = null;
        },
        onError: (errors) => {
            const first = errors && Object.keys(errors)[0];
            linkError.value = first ? errors[first] : t('cars.matching_link_error_generic');
            linkingId.value = null;
        },
        onFinish: () => {
            linkingId.value = null;
        },
    });
};

// Compartir seguimiento público con el cliente
const showTrackingModal = ref(false);
const trackingForm = useForm({
    email: '',
    expected_delivery_date: props.car.expected_delivery_date || '',
});
const openTrackingModal = () => {
    trackingForm.email = props.derived?.tracking?.shared_with_email
        || clientEmailFromContact(props.car?.client)
        || '';
    showTrackingModal.value = true;
};
const submitTracking = () => {
    trackingForm.post(route('cars.share-tracking', props.car.id), {
        onSuccess: () => { showTrackingModal.value = false; },
    });
};
const revokeTracking = () => {
    if (!confirm(t('cars.tracking.confirm_revoke'))) return;
    useForm({}).delete(route('cars.revoke-tracking', props.car.id));
};
const regenerateTracking = () => {
    if (!confirm(t('cars.tracking.confirm_regenerate'))) return;
    useForm({}).post(route('cars.regenerate-tracking', props.car.id));
};
const copyTrackingUrl = async () => {
    const url = props.derived?.tracking?.url;
    if (!url) return;
    try {
        await navigator.clipboard.writeText(url);
    } catch (e) {
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }
};

const createContract = () => {
    if (!props.car.client) return;
    useForm({}).post(route('cars.contract.create', props.car.id));
};
const copyContractUrl = async () => {
    const url = props.derived?.contract?.public_url;
    if (!url) return;
    try {
        await navigator.clipboard.writeText(url);
    } catch (e) {
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }
};

// ── Public link (dossier / informe completo para el cliente) ────────────
const publicLinkForm = useForm({});
const creatingPublicLink = ref(false);
const publicLinkCopied = ref(false);
const activePublicLink = computed(() => props.derived?.public_link?.active || null);
const lastPublicLink = computed(() => props.derived?.public_link?.last || null);
const generatePublicLink = () => {
    creatingPublicLink.value = true;
    publicLinkForm.post(route('cars.create-public-link', props.car.id), {
        preserveScroll: true,
        onFinish: () => { creatingPublicLink.value = false; },
    });
};
const revokePublicLink = (linkId) => {
    if (!confirm(t('cars.public_link.confirm_revoke'))) return;
    useForm({}).delete(route('cars.revoke-public-link', [props.car.id, linkId]), { preserveScroll: true });
};
const copyPublicLink = async () => {
    const url = activePublicLink.value?.url || lastPublicLink.value?.url;
    if (!url) return;
    try {
        await navigator.clipboard.writeText(url);
    } catch (e) {
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }
    publicLinkCopied.value = true;
    setTimeout(() => (publicLinkCopied.value = false), 1800);
};

// Tabs de sección (para no hacer scroll gigante)
const sections = [
    { id: 'resumen', label: t('cars.section_resumen') },
    { id: 'investigacion', label: t('cars.section_investigacion') },
    { id: 'mercado', label: t('cars.section_mercado') },
    { id: 'checklist', label: t('cars.section_checklist') },
    { id: 'documentos', label: t('cars.documents') },
    { id: 'fotos', label: t('cars.photos_title') },
    { id: 'gastos', label: t('cars.expenses_vs_estimated') },
];
const activeSection = ref('resumen');

const photoForm = useForm({ photo_type: 'exterior', photos: [] });
const docForm = useForm({ doc_type: 'invoice', doc_key: '', name: '', documents: [] });

const { currency, date, statusLabel, statusVariant, trafficLightVariant } = useFormat();

// El formulario público guarda contact_info como JSON {email, phone};
// los clientes creados a mano usan texto libre. Mostramos formateado.
const clientContactDisplay = computed(() => {
    const raw = props.car?.client?.contact_info;
    if (!raw) return '—';
    if (typeof raw === 'object') {
        return [raw.email, raw.phone].filter(Boolean).join(' · ');
    }
    try {
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed === 'object') {
            return [parsed.email, parsed.phone].filter(Boolean).join(' · ') || raw;
        }
    } catch (e) {
        // no es JSON → texto plano
    }
    return raw;
});

// Extrae el email del cliente desde contact_info (JSON del form público o texto).
const clientEmailFromContact = (client) => {
    const raw = client?.contact_info;
    if (!raw) return '';
    if (typeof raw === 'object') return raw.email || '';
    try {
        const parsed = JSON.parse(raw);
        return parsed?.email || '';
    } catch (e) {
        return /[\w.+-]+@[\w-]+\.[\w.]+/.test(raw) ? raw.match(/[\w.+-]+@[\w-]+\.[\w.]+/)[0] : '';
    }
};

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

const docStatusVariant = (s) => ({
    pending: 'neutral', ordered: 'warning', received: 'success', not_applicable: 'neutral',
}[s] || 'neutral');

const toggleChecklist = (item) => {
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
                <HeaderBar :car="car" :derived="derived" />

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

                <!-- IEDMT estimation warning (siempre visible, no depende de activeSection) -->
                <div v-if="derived?.iedmt > 0" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
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

                <!-- Overview (Resumen): Location + Specs + Costs + Description + Equipment -->
                <OverviewPanel v-show="activeSection === 'resumen'" :car="car" :spec-items="specItems" :cost-items="costItems" :derived="derived" />

                <!-- ╔ INVESTIGATION ══════════════════════════════════════════════════════╗ -->
                <InvestigationPanel v-show="activeSection === 'investigacion'" :car="car" :derived="derived" />

                <!-- ╔ MARKET ═══════════════════════════════════════════════════════════╗ -->
                <MarketPanel v-if="activeSection === 'mercado' && (car.market_avg || derived?.comparables_stats?.count)" :car="car" :derived="derived" />

                <!-- ╔ CHECKLIST ════════════════════════════════════════════════════════╗ -->
                <ChecklistPanel v-show="activeSection === 'checklist'" :car="car" :derived="derived" @toggle-checklist="toggleChecklist" />

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

                        <!-- PDFs generables por Laravel (esqueletos del ZIP → Blade + Browsershot) -->
                        <div v-if="derived?.laravel_pdfs?.length" class="rounded-xl border border-gray-200">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-2 flex items-center justify-between">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-600">{{ t('cars.pdfs_generated') }}</h4>
                                <span class="text-[10px] text-gray-400">{{ t('cars.pdfs_laravel_note') }}</span>
                            </div>
                            <ul class="divide-y divide-gray-200">
                                <li v-for="pdf in derived.laravel_pdfs" :key="pdf.key" class="flex items-center justify-between gap-3 px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <DocumentIcon class="h-6 w-6 shrink-0 text-estoril-600" />
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900">{{ pdf.label }}</p>
                                            <p v-if="!pdf.available" class="text-xs text-amber-700">{{ t('cars.pdfs_need_skeleton') }}</p>
                                        </div>
                                    </div>
                                    <a
                                        v-if="pdf.available"
                                        :href="pdf.route"
                                        target="_blank"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-md bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500"
                                    >
                                        <ArrowDownTrayIcon class="h-3 w-3" />
                                        {{ t('cars.pdfs_download') }}
                                    </a>
                                    <span v-else class="shrink-0 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400">{{ t('cars.pdfs_not_available') }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Compartir con cliente: dossier público (informe completo) -->
                        <div class="rounded-xl border border-estoril-200 bg-estoril-50/50">
                            <div class="border-b border-estoril-200 bg-white px-4 py-3 flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-estoril-700 flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                                        {{ t('cars.public_link.title') }}
                                    </h4>
                                    <p class="text-xs text-gray-600 mt-1 max-w-2xl">{{ t('cars.public_link.help') }}</p>
                                </div>
                            </div>
                            <div class="px-4 py-3">
                                <div v-if="activePublicLink" class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-semibold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        {{ t('cars.public_link.active') }}
                                    </span>
                                    <span v-if="activePublicLink.views_count > 0" class="text-xs text-gray-500">
                                        {{ t('cars.public_link.views', { n: activePublicLink.views_count }) }}
                                    </span>
                                    <code class="flex-1 min-w-0 truncate rounded bg-white px-2.5 py-1.5 text-xs text-gray-700 ring-1 ring-gray-200 font-mono">
                                        {{ activePublicLink.url }}
                                    </code>
                                    <button type="button" @click="copyPublicLink" class="inline-flex items-center gap-1 rounded-md bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500">
                                        {{ publicLinkCopied ? t('cars.public_link.copied') : t('cars.public_link.copy') }}
                                    </button>
                                    <a :href="activePublicLink.url" target="_blank" class="inline-flex items-center gap-1 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-estoril-700 ring-1 ring-estoril-200 hover:bg-estoril-50">
                                        {{ t('cars.public_link.open') }}
                                    </a>
                                    <button type="button" @click="revokePublicLink(activePublicLink.id)" class="inline-flex items-center gap-1 rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-100">
                                        {{ t('cars.public_link.revoke') }}
                                    </button>
                                </div>
                                <div v-else-if="lastPublicLink && lastPublicLink.revoked_at" class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-200 px-2.5 py-1 text-[11px] font-semibold text-gray-700">
                                        {{ t('cars.public_link.revoked') }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ lastPublicLink.views_count }} visitas antes de revocar</span>
                                    <button type="button" @click="generatePublicLink" :disabled="creatingPublicLink" class="ml-auto inline-flex items-center gap-1 rounded-md bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500 disabled:opacity-50">
                                        {{ t('cars.public_link.share') }}
                                    </button>
                                </div>
                                <button v-else type="button" @click="generatePublicLink" :disabled="creatingPublicLink" class="inline-flex items-center gap-1.5 rounded-md bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                                    {{ t('cars.public_link.share') }}
                                </button>
                            </div>
                        </div>

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
                                            {{ t('common.view') }}
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

                <!-- Photos + Lightbox -->
                <PhotosPanel v-show="activeSection === 'fotos'" :car="car" :photo-type="photoForm.photo_type" :photo-files="photoForm.photos" :upload-progress="uploadProgress" @update:photo-type="(v) => photoForm.photo_type = v" @update:photo-files="(v) => photoForm.photos = v" @submit-photos="submitPhotos" @ask-delete-photo="askDeletePhoto" />

                <!-- Vincular solicitud compatible -->
                <AssignRequestPanel v-if="activeSection === 'resumen' && !car.client && derived?.selectable_requests?.length" :derived="derived" :linking="linkingId !== null" @link-selected-request="linkSelectedRequest">
                    <template #messages>
                        <p v-if="linkError" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                            <strong class="block">{{ t('cars.matching_link_error_title') }}</strong>
                            {{ linkError }}
                        </p>
                        <p v-if="linkSuccess" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                            {{ linkSuccess }}
                        </p>
                    </template>
                </AssignRequestPanel>

                <!-- Assigned Client -->
                <div v-if="car.client" class="overflow-hidden rounded-2xl bg-linear-to-br from-blue-50 to-estoril-50 shadow-sm ring-1 ring-blue-200">
                    <div class="border-b border-blue-200 px-6 py-4 flex items-center gap-2">
                        <UserCircleIcon class="h-5 w-5 text-blue-600" />
                        <h3 class="text-base font-semibold text-gray-900">{{ t('cars.assigned_client') }}</h3>
                        <Badge v-if="derived?.linked_request" variant="green" class="ml-2">{{ t('cars.client_linked') }}</Badge>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white shadow-sm ring-2 ring-blue-200">
                                    <UserCircleIcon class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-base font-bold text-gray-900">{{ car.client.name }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ t('cars.client_id') }} #{{ car.client.id }}</span>
                                        <Badge :variant="statusVariant(car.client.status)">{{ statusLabel(t, car.client.status) }}</Badge>
                                    </div>
                                </div>
                            </div>
                            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.client_contact') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-800">{{ clientContactDisplay }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.client_looking_for') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-800">{{ car.client.looking_for || '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.client_budget') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-800">
                                        <template v-if="car.client.budget_min || car.client.budget_max">
                                            {{ currency(car.client.budget_min || 0) }} – {{ currency(car.client.budget_max || 0) }}
                                        </template>
                                        <template v-else>—</template>
                                    </dd>
                                </div>
                            </dl>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <Link :href="route('clients.show', car.client.id)" class="inline-flex items-center gap-1 rounded-lg bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500">
                                    {{ t('cars.view_client') }} →
                                </Link>
                                <Link :href="route('clients.edit', car.client.id)" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-estoril-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                                    {{ t('cars.edit_client') }}
                                </Link>
                            </div>
                        </div>

                        <!-- Solicitud vinculada -->
                        <div v-if="derived?.linked_request" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <DocumentTextIcon class="h-4 w-4 text-estoril-500" />
                                    {{ t('cars.linked_request') }}
                                </h4>
                                <span class="text-xs text-gray-400">{{ t('cars.linked_request_since') }} {{ derived.linked_request.created_at ? date(derived.linked_request.created_at) : '—' }}</span>
                            </div>
                            <dl class="space-y-3">
                                <div v-if="derived.linked_request.name" class="flex items-start justify-between gap-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.linked_request_name') }}</dt>
                                    <dd class="text-sm text-gray-800 text-right">{{ derived.linked_request.name }}</dd>
                                </div>
                                <div v-if="derived.linked_request.brand" class="flex items-start justify-between gap-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.linked_request_vehicle') }}</dt>
                                    <dd class="text-sm text-gray-800 text-right">{{ derived.linked_request.brand }}{{ derived.linked_request.model ? ' ' + derived.linked_request.model : '' }}</dd>
                                </div>
                                <div v-if="derived.linked_request.budget_min || derived.linked_request.budget_max" class="flex items-start justify-between gap-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.linked_request_budget') }}</dt>
                                    <dd class="text-sm text-gray-800 text-right">
                                        {{ currency(derived.linked_request.budget_min || 0) }} – {{ currency(derived.linked_request.budget_max || 0) }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.linked_request_status') }}</dt>
                                    <dd class="text-sm"><Badge :variant="statusVariant(derived.linked_request.status)">{{ t('car_requests.status.' + derived.linked_request.status, derived.linked_request.status) }}</Badge></dd>
                                </div>
                                <div v-if="derived.linked_request.notes" class="border-t border-gray-100 pt-2">
                                    <dt class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.linked_request_notes') }}</dt>
                                    <dd class="text-xs text-gray-600 whitespace-pre-wrap">{{ derived.linked_request.notes }}</dd>
                                </div>
                            </dl>
                            <div class="mt-4 flex justify-end">
                                <Link :href="route('car-requests.show', derived.linked_request.id)" class="inline-flex items-center gap-1 text-xs font-semibold text-estoril-600 hover:text-estoril-500">
                                    {{ t('cars.view_request') }} →
                                </Link>
                            </div>
                        </div>

                        <!-- Compartir seguimiento con el cliente -->
                        <div class="rounded-xl bg-gradient-to-br from-estoril-50 to-white p-5 shadow-sm ring-1 ring-estoril-100">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <ShareIcon class="h-4 w-4 text-estoril-600" />
                                    {{ t('cars.tracking.title') }}
                                </h4>
                                <Badge v-if="derived?.tracking?.is_shared" variant="success" size="sm">{{ t('cars.tracking.shared') }}</Badge>
                                <Badge v-else size="sm">{{ t('cars.tracking.not_shared') }}</Badge>
                            </div>
                            <p class="text-xs text-gray-600">{{ t('cars.tracking.help') }}</p>

                            <div v-if="derived?.tracking?.is_shared && derived?.tracking?.url" class="mt-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <input
                                        :value="derived.tracking.url"
                                        readonly
                                        class="flex-1 truncate rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700"
                                        @focus="$event.target.select()"
                                    />
                                    <button
                                        type="button"
                                        @click="copyTrackingUrl"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-estoril-600 px-3 py-2 text-xs font-semibold text-white hover:bg-estoril-500"
                                    >
                                        <ClipboardDocumentIcon class="h-3 w-3" />
                                        {{ t('common.copy') }}
                                    </button>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-gray-500">
                                    <span>{{ t('cars.tracking.views', { n: derived.tracking.views || 0 }) }}</span>
                                    <span v-if="derived.tracking.shared_at">
                                        {{ t('cars.tracking.shared_since') }} {{ date(derived.tracking.shared_at) }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <a
                                        :href="derived.tracking.url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500"
                                    >
                                        <GlobeEuropeAfricaIcon class="h-3 w-3" />
                                        {{ t('cars.tracking.open') }}
                                    </a>
                                    <button
                                        type="button"
                                        @click="openTrackingModal"
                                        class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-estoril-700 shadow-sm ring-1 ring-estoril-200 hover:bg-estoril-50"
                                    >
                                        {{ t('cars.tracking.edit_email') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="regenerateTracking"
                                        class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50"
                                    >
                                        <ArrowPathIcon class="h-3 w-3" />
                                        {{ t('cars.tracking.regenerate') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="revokeTracking"
                                        class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                    >
                                        <XCircleIcon class="h-3 w-3" />
                                        {{ t('cars.tracking.revoke') }}
                                    </button>
                                </div>
                            </div>

                            <div v-else class="mt-3">
                                <button
                                    type="button"
                                    @click="openTrackingModal"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500"
                                >
                                    <ShareIcon class="h-4 w-4" />
                                    {{ t('cars.tracking.share_with_client') }}
                                </button>
                            </div>
                        </div>

                        <!-- Contrato de prestación de servicios -->
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-asphalt-100">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <DocumentCheckIcon class="h-4 w-4 text-estoril-600" />
                                    {{ t('cars.contract.title') }}
                                </h4>
                                <Badge v-if="derived?.contract?.accepted_at" variant="success" size="sm">{{ t('cars.contract.signed') }}</Badge>
                                <Badge v-else-if="derived?.contract?.public_url" size="sm">{{ t('cars.contract.pending') }}</Badge>
                                <Badge v-else size="sm">{{ t('cars.contract.none') }}</Badge>
                            </div>
                            <p class="text-xs text-gray-600">{{ t('cars.contract.help') }}</p>

                            <div v-if="derived?.contract?.public_url" class="mt-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <input
                                        :value="derived.contract.public_url"
                                        readonly
                                        class="flex-1 truncate rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700"
                                        @focus="$event.target.select()"
                                    />
                                    <button
                                        type="button"
                                        @click="copyContractUrl"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-asphalt-700 px-3 py-2 text-xs font-semibold text-white hover:bg-asphalt-600"
                                    >
                                        <ClipboardDocumentIcon class="h-3 w-3" />
                                        {{ t('common.copy') }}
                                    </button>
                                </div>
                                <p v-if="derived.contract.accepted_at" class="text-[10px] text-emerald-700">
                                    {{ t('cars.contract.signed_at', { when: date(derived.contract.accepted_at) }) }}
                                </p>
                                <div class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50/60 p-3">
                                    <img
                                        v-if="derived.contract.qr_svg"
                                        :src="derived.contract.qr_svg"
                                        alt="QR contrato"
                                        class="h-24 w-24 shrink-0 rounded bg-white p-1 shadow-sm"
                                    />
                                    <p class="text-[11px] leading-relaxed text-gray-600">
                                        {{ t('cars.contract.scan_qr') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <a :href="derived.contract.public_url" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-estoril-700 shadow-sm ring-1 ring-estoril-200 hover:bg-estoril-50">
                                        {{ t('cars.contract.open') }}
                                    </a>
                                    <a v-if="derived.contract.accepted_at" :href="derived.contract.pdf_url" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                                        <ArrowDownTrayIcon class="h-3 w-3" />
                                        {{ t('cars.contract.download_pdf') }}
                                    </a>
                                </div>
                            </div>

                            <div v-else class="mt-3">
                                <button
                                    type="button"
                                    @click="createContract"
                                    :disabled="!car.client"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-asphalt-700 px-4 py-2 text-sm font-semibold text-white hover:bg-asphalt-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    :title="!car.client ? t('cars.contract.need_client') : ''"
                                >
                                    <DocumentCheckIcon class="h-4 w-4" />
                                    {{ t('cars.contract.generate') }}
                                </button>
                                <p v-if="!car.client" class="mt-2 text-[10px] text-amber-700">
                                    {{ t('cars.contract.need_client') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <NotesPanel :notes="car.notes" />

                <!-- Expenses -->
                <ExpensesPanel v-show="activeSection === 'gastos'" :expenses="car.expenses || []" />
            </div>
        </div>

        <ConfirmDialog :show="showDeletePhoto" :title="t('cars.delete_photo')" :message="t('cars.delete_photo_msg')" :confirm-text="t('common.delete')" :cancel-text="t('common.cancel')" @confirm="confirmDeletePhoto" @close="showDeletePhoto = false" @cancel="showDeletePhoto = false" />
        <ConfirmDialog :show="showDeleteDoc" :title="t('cars.delete_document')" :message="t('cars.delete_document_msg')" :confirm-text="t('common.delete')" :cancel-text="t('common.cancel')" @confirm="confirmDeleteDoc" @close="showDeleteDoc = false" @cancel="showDeleteDoc = false" />

        <!-- Modal de compartir seguimiento con el cliente -->
        <ShareTrackingModal :show="showTrackingModal" :email="trackingForm.email" :expected-delivery-date="trackingForm.expected_delivery_date" :errors="trackingForm.errors || {}" :processing="trackingForm.processing" @update:show="(v) => showTrackingModal = v" @update:email="(v) => trackingForm.email = v" @update:expected-delivery-date="(v) => trackingForm.expected_delivery_date = v" @submit="submitTracking" />
    </AuthenticatedLayout>
</template>
