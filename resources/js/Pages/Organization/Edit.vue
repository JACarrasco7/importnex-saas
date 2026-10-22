<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeftIcon, CheckIcon, KeyIcon, CpuChipIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    organization: Object,
    aiProviders: { type: Array, default: () => [] },
    aiKeyConfigured: { type: Boolean, default: false },
    webBaseUrl: { type: String, default: '' },
    siteUrl: { type: String, default: null },
    siteBlocked: { type: Boolean, default: false },
});

const companyFields = [
    { key: 'legal_name', label: 'Razón social', help: 'Nombre legal que aparece en contratos y aviso legal.' },
    { key: 'tax_id', label: 'CIF / NIF' },
    { key: 'address', label: 'Dirección fiscal' },
    { key: 'city', label: 'Ciudad', help: 'Se usa en «Puesto en …» y en la jurisdicción del contrato.' },
    { key: 'phone', label: 'Teléfono' },
    { key: 'phone_2', label: 'Teléfono 2' },
    { key: 'whatsapp', label: 'WhatsApp', help: 'Si lo dejas vacío se usa el teléfono principal.' },
    { key: 'email', label: 'Email de contacto', type: 'email' },
    { key: 'website', label: 'Web' },
    { key: 'fee_label', label: 'Honorarios (texto)', help: 'Ej.: «1.500 €». Aparece en el folleto.' },
    { key: 'fee_base', label: 'Honorarios base (€)', type: 'number', help: 'Lo que cobras por encargo. Sale en el desglose orientativo de tu web. Vacío = 1.000 €.' },
    { key: 'fee_premium', label: 'Honorarios coches caros (€)', type: 'number', help: 'Desde el precio de abajo. Vacío = igual que el base (o 1.500 € si tampoco hay base).' },
    { key: 'fee_threshold', label: 'Desde qué precio (€)', type: 'number', help: 'Vacío = 30.000 €.' },
    { key: 'iban', label: 'IBAN para transferencias' },
    { key: 'iban_holder', label: 'Titular de la cuenta' },
    { key: 'iban_bank', label: 'Banco' },
];

const company = props.organization.company || {};

const form = useForm({
    name: props.organization.name,
    is_public: !!props.organization.is_public,
    slug: props.organization.slug || '',
    company: Object.fromEntries(companyFields.map((f) => [f.key, company[f.key] || ''])),
    logo: null,
    ai_provider: props.organization.ai_provider || '',
    ai_model: props.organization.ai_model || '',
    ai_api_key: '',
});

const detecting = ref(false);
const detectedModels = ref([]);
const detectError = ref('');

const submit = () => form
    .transform((data) => ({ ...data, _method: 'patch', is_public: data.is_public ? 1 : 0, logo: data.logo || undefined }))
    .post(route('organization.update', props.organization.id), {
        preserveScroll: true,
        forceFormData: true,
    });

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';

function defaultModel(provider) {
    return props.aiProviders.find(p => p.key === provider)?.default_model ?? '';
}

function onProviderChange() {
    form.ai_model = defaultModel(form.ai_provider);
    detectedModels.value = [];
    detectError.value = '';
}

async function detectModels() {
    detectError.value = '';
    detectedModels.value = [];

    if (!form.ai_provider) {
        detectError.value = 'Elige un proveedor primero.';
        return;
    }

    detecting.value = true;
    try {
        const resp = await window.axios.post(route('organization.ai-models', props.organization.id), {
            provider: form.ai_provider,
            api_key: form.ai_api_key || null,
        });
        if (resp.data?.success && resp.data.models?.length) {
            detectedModels.value = resp.data.models;
            if (!detectedModels.value.includes(form.ai_model)) {
                form.ai_model = detectedModels.value.includes(defaultModel(form.ai_provider))
                    ? defaultModel(form.ai_provider)
                    : detectedModels.value[0];
            }
        } else {
            detectError.value = resp.data?.error || 'No se encontraron modelos.';
        }
    } catch (e) {
        detectError.value = e.response?.data?.error || 'Error detectando modelos. Revisa la API key.';
    } finally {
        detecting.value = false;
    }
}
</script>

<template>
    <Head :title="t('organization.edit')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('organization.edit') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('organization.edit')" :subtitle="t('organization.edit_subtitle')">
                    <template #actions>
                        <Link :href="route('organization.show', organization.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back', {}, 'Volver') }}
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection title="General">
                        <FormField label="Nombre comercial" required>
                            <input v-model="form.name" type="text" required :class="inputClass" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </FormField>
                    </FormSection>

                    <FormSection title="Datos de empresa"
                                 subtitle="Aparecen en contratos, portal del cliente, emails, fichas públicas y aviso legal. Sin CIF y dirección el contrato sale incompleto.">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField v-for="field in companyFields" :key="field.key" :label="field.label" :help="field.help">
                                <input v-model="form.company[field.key]" :type="field.type || 'text'" :class="inputClass" />
                                <p v-if="form.errors[`company.${field.key}`]" class="mt-1 text-sm text-red-600">{{ form.errors[`company.${field.key}`] }}</p>
                            </FormField>
                            <FormField label="Logo" help="PNG, JPG, WEBP o SVG. Máx. 2 MB.">
                                <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="block w-full text-sm" @input="form.logo = $event.target.files[0]" />
                                <p v-if="form.errors.logo" class="mt-1 text-sm text-red-600">{{ form.errors.logo }}</p>
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection id="web-publica" title="Web pública"
                                 subtitle="Tu web de coches con tu marca: catálogo, fichas, comparador y formulario de solicitud. Solo salen los coches que marques como publicados.">
                        <div class="space-y-4">
                            <label class="flex items-start gap-3">
                                <input v-model="form.is_public" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-estoril-600 focus:ring-estoril-500" />
                                <span class="text-sm text-gray-700">
                                    <span class="font-semibold text-gray-900">Publicar mi web</span><br />
                                    Si la desactivas, tu catálogo y tu formulario dejan de verse.
                                </span>
                            </label>
                            <FormField label="Dirección de tu web" help="Minúsculas, números y guiones. Si la cambias, los enlaces antiguos dejan de funcionar.">
                                <div class="flex items-center rounded-lg shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-estoril-500">
                                    <span class="select-none whitespace-nowrap pl-3 text-sm text-gray-500">{{ webBaseUrl }}/</span>
                                    <input v-model="form.slug" type="text" maxlength="60" autocomplete="off" spellcheck="false" class="block w-full border-0 bg-transparent py-2 pl-0.5 text-sm focus:ring-0" />
                                </div>
                                <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                            </FormField>
                            <p v-if="siteBlocked" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                                Tu web está activada pero no se ve porque la prueba ha terminado. Elige un plan para volver a publicarla.
                            </p>
                            <a v-else-if="siteUrl" :href="siteUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm font-semibold text-estoril-700 hover:text-estoril-900">
                                Ver mi web →
                            </a>
                        </div>
                    </FormSection>

                    <FormSection title="Inteligencia artificial"
                                 subtitle="Cada empresa usa su propia clave de IA y paga el consumo directamente al proveedor. Recomendamos Claude (Anthropic).">
                        <div class="space-y-4">
                            <FormField
                                label="Proveedor de IA"
                                help="Qué proveedor ejecuta las tareas de IA de tu empresa.">
                                <select v-model="form.ai_provider" @change="onProviderChange" :class="inputClass">
                                    <option value="">— Desactivado —</option>
                                    <option v-for="p in aiProviders" :key="p.key" :value="p.key">
                                        {{ p.label }} ({{ p.default_model }}){{ p.key === 'anthropic' ? ' · recomendado' : '' }}
                                    </option>
                                </select>
                                <p v-if="form.errors.ai_provider" class="mt-1 text-sm text-red-600">{{ form.errors.ai_provider }}</p>
                            </FormField>

                            <FormField label="Clave de API" help="Se guarda cifrada. Déjala vacía para mantener la actual.">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <KeyIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <input
                                            v-model="form.ai_api_key"
                                            type="password"
                                            autocomplete="off"
                                            spellcheck="false"
                                            :placeholder="t('organization.paste_key')"
                                            :class="[inputClass, 'pl-10']" />
                                    </div>
                                    <button
                                        type="button"
                                        @click="detectModels"
                                        :disabled="detecting || !form.ai_provider"
                                        class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50">
                                        <MagnifyingGlassIcon class="h-4 w-4" />
                                        {{ detecting ? 'Detectando…' : 'Detectar modelos' }}
                                    </button>
                                </div>
                                <p v-if="detectError" class="mt-1 text-sm text-red-600">{{ detectError }}</p>
                                <p v-if="form.errors.ai_api_key" class="mt-1 text-sm text-red-600">{{ form.errors.ai_api_key }}</p>
                            </FormField>

                            <FormField label="Modelo (opcional)" help="Vacío = modelo por defecto del proveedor.">
                                <div class="relative">
                                    <CpuChipIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <select v-if="detectedModels.length" v-model="form.ai_model" :class="[inputClass, 'pl-10']">
                                        <option value="">— Por defecto del proveedor —</option>
                                        <option v-for="m in detectedModels" :key="m" :value="m">{{ m }}</option>
                                    </select>
                                    <input
                                        v-else
                                        v-model="form.ai_model"
                                        type="text"
                                        placeholder="p. ej. gpt-4o-mini, claude-3-5-sonnet-latest, gemini-1.5-flash, deepseek-chat…"
                                        :class="[inputClass, 'pl-10']" />
                                </div>
                                <p v-if="detectedModels.length" class="mt-1 text-xs text-emerald-700">
                                    {{ detectedModels.length }} modelos detectados con tu API key.
                                </p>
                                <p v-if="form.errors.ai_model" class="mt-1 text-sm text-red-600">{{ form.errors.ai_model }}</p>
                            </FormField>

                            <div v-if="organization.ai_provider" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
                                Configurado:
                                <strong>{{ organization.ai_provider }}</strong>
                                <span v-if="organization.ai_model"> · {{ organization.ai_model }}</span>
                                <span v-else> · modelo por defecto</span>
                                <span v-if="!aiKeyConfigured" class="ml-2 inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ t('organization.no_key_yet') }}</span>
                            </div>
                        </div>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('organization.show', organization.id)" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ t('common.cancel') }}</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
