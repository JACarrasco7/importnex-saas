<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ArrowLeftIcon,
    SparklesIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ChartBarIcon,
    LightBulbIcon,
    FlagIcon,
    XMarkIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    car: Object,
});

const { trafficLightVariant, currency: formatCurrency, date: formatShortDate, statusLabel } = useFormat();
const { t } = useTranslations();

const aiAnalysis = computed(() => props.car.ai_analysis_json || null);
const showModal = ref(false);
const submitting = ref(false);
const errorMsg = ref('');

// Order matters: deterministic ordering in the modal.
// Labels vienen de i18n (cars.verify_fields.<key>) para respetar el locale.
const FIELDS = [
    { key: 'valuation', type: 'long' },
    { key: 'description', type: 'long' },
    { key: 'purchase_price', type: 'number', format: 'currency' },
    { key: 'manual_tax_base', type: 'number', format: 'currency' },
    { key: 'verdict', type: 'short', enum: ['Buy', 'Buy if price drops', 'Doubtful', 'Discard'] },
    { key: 'verdict_confidence', type: 'short', enum: ['high', 'medium', 'low'] },
    { key: 'verdict_reasoning', type: 'long' },
    { key: 'market_avg', type: 'number', format: 'currency' },
    { key: 'market_min', type: 'number', format: 'currency' },
    { key: 'market_max', type: 'number', format: 'currency' },
    { key: 'estimated_saving', type: 'number', format: 'currency' },
    { key: 'pros', type: 'array' },
    { key: 'cons', type: 'array' },
    { key: 'tips', type: 'array' },
    { key: 'red_flags', type: 'array' },
].map(f => ({ ...f, label: t('cars.verify_fields.' + f.key, f.key) }));

// `checked` is the user's choice per field, defaults to true when the field
// has a proposed value AND the car has no current value (so the AI is the only
// source). When the user already has something, default unchecked — they have
// to opt-in.
const checked = ref({});
const overrides = ref({}); // typed overrides (rare, but supported)

function initSelection() {
    if (!aiAnalysis.value) {
        checked.value = {};
        return;
    }
    const next = {};
    for (const def of FIELDS) {
        const proposed = aiAnalysis.value[def.key];
        const hasProposal = proposed !== null && proposed !== undefined && proposed !== '' && !(Array.isArray(proposed) && proposed.length === 0);
        if (!hasProposal) continue;
        const current = props.car[def.key];
        const hasCurrent = current !== null && current !== undefined && current !== '' && !(Array.isArray(current) && current.length === 0);
        next[def.key] = !hasCurrent; // default: apply only when nothing exists yet
    }
    checked.value = next;
    overrides.value = {};
}

function openModal() {
    initSelection();
    errorMsg.value = '';
    showModal.value = true;
}

function toggleAll(value) {
    for (const def of FIELDS) {
        const proposed = aiAnalysis.value?.[def.key];
        if (proposed === null || proposed === undefined || proposed === '' || (Array.isArray(proposed) && proposed.length === 0)) {
            checked.value[def.key] = false;
            continue;
        }
        checked.value[def.key] = value;
    }
}

function preview(def, value) {
    return value;
}

function formatDef(def, value) {
    if (value === null || value === undefined || value === '') return '—';
    if (def.format === 'currency' && typeof value === 'number') return formatCurrency(value);
    if (Array.isArray(value)) return value.join(' · ');
    return String(value);
}

const selectedFields = computed(() => Object.keys(checked.value).filter(k => checked.value[k]));
const selectedCount = computed(() => selectedFields.value.length);

function apply() {
    if (selectedCount.value === 0) {
        errorMsg.value = 'Selecciona al menos un campo para aplicar.';
        return;
    }
    submitting.value = true;
    errorMsg.value = '';
    router.post(
        route('cars.verify.apply', props.car.id),
        { fields: selectedFields.value },
        {
            preserveScroll: true,
            onFinish: () => { submitting.value = false; },
            onError: (e) => { errorMsg.value = e?.message || e?.error || 'Error aplicando las sugerencias.'; submitting.value = false; },
            onSuccess: () => { showModal.value = false; },
        },
    );
}
</script>

<template>
    <Head :title="t('cars.verification_title', { brand: car.brand, model: car.model })" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('cars.verification_title', { brand: car.brand, model: car.model }) }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('cars.marketplace_brand_model', { brand: car.brand, model: car.model })" :subtitle="`${car.year} · ${t('cars.current_status')}: ${statusLabel(t, car.status)}`">
                    <template #actions>
                        <Link :href="route('cars.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Status banners -->
                <div v-if="car.ai_verified_at && aiAnalysis" class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-start gap-3">
                        <CheckCircleIcon class="h-5 w-5 shrink-0 text-emerald-600" />
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-emerald-900">{{ t('cars.verification_done_title') }}</p>
                            <p class="mt-0.5 text-xs text-emerald-700">
                                {{ t('cars.verification_done_desc') }} · {{ t('cars.verification_done_at') }} {{ car.ai_verified_at ? formatShortDate(car.ai_verified_at) : '—' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else-if="car.status === 'Verifying'" class="overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-3">
                        <InformationCircleIcon class="h-5 w-5 shrink-0 text-blue-600" />
                        <p class="text-sm text-blue-900">{{ t('cars.verification_in_progress') }}</p>
                    </div>
                </div>
                <div v-else class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <div class="flex items-start gap-3">
                        <SparklesIcon class="h-5 w-5 shrink-0 text-estoril-600" />
                        <div>
                            <p class="text-sm text-gray-900">{{ t('cars.not_verified_yet') }}</p>
                            <form method="POST" :action="route('cars.verify-sync', car.id)" class="mt-4">
                                <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                                    <SparklesIcon class="h-4 w-4" />
                                    {{ t('cars.verify_with_ai') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Investigación de la IA (completa, desde ai_analysis_json) -->
                <template v-if="aiAnalysis">
                    <!-- Veredicto -->
                    <div v-if="aiAnalysis.verdict || aiAnalysis.traffic_light" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.ai_investigation') }}</h3>
                            <span v-if="aiAnalysis.provider || aiAnalysis.model" class="text-xs text-gray-400">
                                {{ aiAnalysis.provider || 'IA' }}{{ aiAnalysis.model ? ' · ' + aiAnalysis.model : '' }}
                            </span>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <Badge v-if="aiAnalysis.traffic_light" :variant="trafficLightVariant(aiAnalysis.traffic_light)" size="lg">
                                    {{ t('cars.verify_fields.traffic_light', 'Semáforo') }}: {{ aiAnalysis.traffic_light }}
                                </Badge>
                                <Badge v-if="aiAnalysis.verdict" variant="indigo" size="lg">{{ aiAnalysis.verdict }}</Badge>
                                <span v-if="aiAnalysis.verdict_confidence" class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                    {{ t('cars.verify_fields.verdict_confidence', 'Confianza') }}: {{ aiAnalysis.verdict_confidence }}
                                </span>
                            </div>
                            <p v-if="aiAnalysis.verdict_reasoning" class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                                {{ aiAnalysis.verdict_reasoning }}
                            </p>
                        </div>
                    </div>

                    <!-- Mercado + ahorro -->
                    <div v-if="aiAnalysis.market_avg || aiAnalysis.market_min || aiAnalysis.market_max || aiAnalysis.estimated_saving" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div v-if="aiAnalysis.market_avg" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.verify_fields.market_avg', 'Media mercado') }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">{{ formatCurrency(aiAnalysis.market_avg) }}</p>
                        </div>
                        <div v-if="aiAnalysis.market_min" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.verify_fields.market_min', 'Mínimo') }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">{{ formatCurrency(aiAnalysis.market_min) }}</p>
                        </div>
                        <div v-if="aiAnalysis.market_max" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.verify_fields.market_max', 'Máximo') }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">{{ formatCurrency(aiAnalysis.market_max) }}</p>
                        </div>
                        <div v-if="aiAnalysis.estimated_saving" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.verify_fields.estimated_saving', 'Ahorro estimado') }}</p>
                            <p class="mt-1 text-lg font-bold text-emerald-600">{{ formatCurrency(aiAnalysis.estimated_saving) }}</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <FormSection v-if="aiAnalysis.valuation" :title="t('cars.verify_fields.valuation', 'Valoración')">
                            <div class="flex items-start gap-3">
                                <ChartBarIcon class="h-5 w-5 shrink-0 text-estoril-600" />
                                <p class="text-sm text-gray-900">{{ aiAnalysis.valuation }}</p>
                            </div>
                        </FormSection>

                        <FormSection v-if="aiAnalysis.recommendation" :title="t('cars.verify_fields.recommendation', 'Recomendación')">
                            <div class="flex items-start gap-3">
                                <LightBulbIcon class="h-5 w-5 shrink-0 text-estoril-600" />
                                <p class="text-sm text-gray-900">{{ aiAnalysis.recommendation }}</p>
                            </div>
                        </FormSection>

                        <div v-if="aiAnalysis.pros?.length || aiAnalysis.cons?.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormSection v-if="aiAnalysis.pros?.length" :title="t('cars.pros_label')">
                                <ul class="space-y-2">
                                    <li v-for="item in aiAnalysis.pros" :key="item" class="flex items-start gap-2 rounded-lg bg-emerald-50 p-3">
                                        <CheckCircleIcon class="h-4 w-4 shrink-0 text-emerald-600" />
                                        <span class="text-sm text-emerald-900">{{ item }}</span>
                                    </li>
                                </ul>
                            </FormSection>
                            <FormSection v-if="aiAnalysis.cons?.length" :title="t('cars.cons_label')">
                                <ul class="space-y-2">
                                    <li v-for="item in aiAnalysis.cons" :key="item" class="flex items-start gap-2 rounded-lg bg-rose-50 p-3">
                                        <XMarkIcon class="h-4 w-4 shrink-0 text-rose-600" />
                                        <span class="text-sm text-rose-900">{{ item }}</span>
                                    </li>
                                </ul>
                            </FormSection>
                        </div>

                        <FormSection v-if="aiAnalysis.red_flags?.length" :title="t('cars.red_flags_label')">
                            <ul class="space-y-2">
                                <li v-for="flag in aiAnalysis.red_flags" :key="flag" class="flex items-start gap-2 rounded-lg bg-rose-50 p-3">
                                    <FlagIcon class="h-4 w-4 shrink-0 text-rose-600" />
                                    <span class="text-sm text-rose-900">{{ flag }}</span>
                                </li>
                            </ul>
                        </FormSection>

                        <FormSection v-if="aiAnalysis.tips?.length" :title="t('cars.tips_label')">
                            <ul class="space-y-2">
                                <li v-for="tip in aiAnalysis.tips" :key="tip" class="flex items-start gap-2 rounded-lg bg-emerald-50 p-3">
                                    <CheckCircleIcon class="h-4 w-4 shrink-0 text-emerald-600" />
                                    <span class="text-sm text-emerald-900">{{ tip }}</span>
                                </li>
                            </ul>
                        </FormSection>
                    </div>
                </template>

                <!-- Apply modal trigger + re-verify (siempre visibles con análisis) -->
                <div v-if="aiAnalysis" class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        @click="openModal"
                        class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                        <SparklesIcon class="h-4 w-4" />
                        {{ t('cars.review_apply') }}
                    </button>
                    <form method="POST" :action="route('cars.verify-sync', car.id)" class="inline-flex">
                        <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-estoril-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                            <SparklesIcon class="h-4 w-4" />
                            {{ t('cars.verify_again') }}
                        </button>
                    </form>
                    <span class="text-xs text-gray-500">{{ Object.keys(aiAnalysis).length - 5 }} {{ t('cars.additional_fields') }} · {{ t('cars.nothing_changes') }}</span>
                </div>
            </div>
        </div>

        <!-- Modal: per-field apply -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            </Transition>

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-8" @click.self="showModal = false">
                    <div class="w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200 flex flex-col">
                        <header class="flex items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.ai_suggestions') }}</h3>
                                <p class="text-xs text-gray-500">Select which fields to apply. Untouched fields stay as they are.</p>
                            </div>
                            <button @click="showModal = false" type="button" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </header>

                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-6 py-2.5 text-xs text-gray-600">
                            <span>{{ selectedCount }} selected</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleAll(true)" class="rounded border border-gray-300 px-2.5 py-1 font-semibold hover:bg-gray-100">{{ t('cars.select_all_with_proposal') }}</button>
                                <button type="button" @click="toggleAll(false)" class="rounded border border-gray-300 px-2.5 py-1 font-semibold hover:bg-gray-100">Clear</button>
                            </div>
                        </div>

                        <div v-if="errorMsg" class="mx-6 mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 ring-1 ring-red-200">
                            {{ errorMsg }}
                        </div>

                        <div class="flex-1 overflow-y-auto px-6 py-4">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th class="w-10 py-2">Apply</th>
                                        <th class="w-1/4 py-2">Field</th>
                                        <th class="w-1/3 py-2">Current value</th>
                                        <th class="w-1/3 py-2">AI proposal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="def in FIELDS" :key="def.key">
                                        <tr v-if="aiAnalysis && aiAnalysis[def.key] !== null && aiAnalysis[def.key] !== undefined && aiAnalysis[def.key] !== '' && !(Array.isArray(aiAnalysis[def.key]) && aiAnalysis[def.key].length === 0)" class="border-b border-gray-100 align-top">
                                            <td class="py-3 pr-2">
                                                <input
                                                    type="checkbox"
                                                    v-model="checked[def.key]"
                                                    class="h-4 w-4 rounded border-gray-300 text-estoril-600 focus:ring-estoril-500" />
                                            </td>
                                            <td class="py-3 pr-3">
                                                <div class="font-medium text-gray-900">{{ def.label }}</div>
                                                <div class="text-xs text-gray-500 font-mono">{{ def.key }}</div>
                                            </td>
                                            <td class="py-3 pr-3">
                                                <div :class="['rounded-md p-2 text-xs ring-1 ring-inset', car[def.key] && (Array.isArray(car[def.key]) ? car[def.key].length > 0 : true) ? 'bg-amber-50 text-amber-900 ring-amber-200' : 'bg-gray-50 text-gray-500 ring-gray-200']">
                                                    <template v-if="def.type === 'array'">
                                                        <ul v-if="Array.isArray(car[def.key]) && car[def.key].length > 0" class="list-disc space-y-1 pl-4">
                                                            <li v-for="(item, i) in car[def.key]" :key="i">{{ item }}</li>
                                                        </ul>
                                                        <span v-else>— empty —</span>
                                                    </template>
                                                    <template v-else>
                                                        {{ formatDef(def, car[def.key]) }}
                                                    </template>
                                                </div>
                                                <p v-if="car[def.key] && (Array.isArray(car[def.key]) ? car[def.key].length > 0 : true)" class="mt-1 text-[11px] text-amber-700">⚠ You already have a value here. Tick to overwrite.</p>
                                            </td>
                                            <td class="py-3 pr-3">
                                                <div class="rounded-md bg-emerald-50 p-2 text-xs text-emerald-900 ring-1 ring-emerald-200">
                                                    <template v-if="def.type === 'array'">
                                                        <ul class="list-disc space-y-1 pl-4">
                                                            <li v-for="(item, i) in aiAnalysis[def.key]" :key="i">{{ item }}</li>
                                                        </ul>
                                                    </template>
                                                    <template v-else>
                                                        {{ formatDef(def, aiAnalysis[def.key]) }}
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <footer class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <button type="button" @click="showModal = false" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="apply"
                                :disabled="submitting || selectedCount === 0"
                                class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                                <CheckIcon class="h-4 w-4" />
                                {{ submitting ? 'Applying…' : `Apply ${selectedCount} field${selectedCount === 1 ? '' : 's'}` }}
                            </button>
                        </footer>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
