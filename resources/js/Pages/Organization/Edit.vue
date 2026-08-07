<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ArrowLeftIcon,
    CheckIcon,
    KeyIcon,
    CpuChipIcon,
    MagnifyingGlassIcon,
    BellIcon,
    BoltIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    organization: Object,
    aiProviders: { type: Array, default: () => [] },
});

const alertTypes = [
    'car_request',
    'car_stale',
    'client_no_contact',
    'verification_failed',
    'verification_completed',
];

const initialPrefs = { ...(props.organization.notification_preferences || {}) };
for (const k of alertTypes) {
    if (!(k in initialPrefs)) initialPrefs[k] = true;
}

const form = useForm({
    name: props.organization.name,
    currency: props.organization.currency || 'EUR',
    locale: props.organization.locale || 'es',
    ai_provider: props.organization.ai_provider || '',
    ai_model: props.organization.ai_model || '',
    ai_api_key: '',
    notification_webhook_url: props.organization.notification_webhook_url || '',
    notification_webhook_types: props.organization.notification_webhook_types || [],
    notification_preferences: initialPrefs,
});

const allSelected = computed(() => alertTypes.every((k) => form.notification_preferences[k]));
const enableAll = () => { for (const k of alertTypes) form.notification_preferences[k] = true; };
const disableAll = () => { for (const k of alertTypes) form.notification_preferences[k] = false; };

const detecting = ref(false);
const detectedModels = ref([]);
const detectError = ref('');

const submit = () => form.patch(route('organization.update', props.organization.id), {
    preserveScroll: true,
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
        detectError.value = t('organization.pick_provider_first');
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
            detectError.value = resp.data?.error || t('organization.no_models_found');
        }
    } catch (e) {
        detectError.value = e.response?.data?.error || t('organization.detect_error');
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
                            {{ t('common.back') }}
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection :title="t('organization.section_general')">
                        <FormField :label="t('organization.label_org_name')" required>
                            <input v-model="form.name" type="text" required :class="inputClass" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </FormField>
                        <FormField :label="t('organization.label_currency')" :help="t('organization.help_currency')">
                            <select v-model="form.currency" :class="inputClass">
                                <option value="EUR">EUR (€)</option>
                                <option value="USD">USD ($)</option>
                                <option value="GBP">GBP (£)</option>
                            </select>
                            <p v-if="form.errors.currency" class="mt-1 text-sm text-red-600">{{ form.errors.currency }}</p>
                        </FormField>
                    </FormSection>

                    <FormSection :title="t('organization.section_ai')"
                                 :subtitle="t('organization.section_ai_desc')">
                        <div class="space-y-4">
                            <FormField
                                :label="t('organization.label_ai_provider')"
                                :help="t('organization.help_ai_provider')">
                                <select v-model="form.ai_provider" @change="onProviderChange" :class="inputClass">
                                    <option value="">{{ t('organization.disabled_option') }}</option>
                                    <option v-for="p in aiProviders" :key="p.key" :value="p.key">
                                        {{ p.label }} ({{ p.default_model }})
                                    </option>
                                </select>
                                <p v-if="form.errors.ai_provider" class="mt-1 text-sm text-red-600">{{ form.errors.ai_provider }}</p>
                            </FormField>

                            <FormField :label="t('organization.label_api_key')" :help="t('organization.help_api_key')">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <KeyIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <input
                                            v-model="form.ai_api_key"
                                            type="password"
                                            autocomplete="off"
                                            spellcheck="false"
                                            :placeholder="t('organization.api_key_placeholder')"
                                            :class="[inputClass, 'pl-10']" />
                                    </div>
                                    <button
                                        type="button"
                                        @click="detectModels"
                                        :disabled="detecting || !form.ai_provider"
                                        class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50">
                                        <MagnifyingGlassIcon class="h-4 w-4" />
                                        {{ detecting ? t('organization.detecting') : t('organization.detect_models') }}
                                    </button>
                                </div>
                                <p v-if="detectError" class="mt-1 text-sm text-red-600">{{ detectError }}</p>
                                <p v-if="form.errors.ai_api_key" class="mt-1 text-sm text-red-600">{{ form.errors.ai_api_key }}</p>
                            </FormField>

                            <FormField :label="t('organization.label_model_optional')" :help="t('organization.help_model')">
                                <div class="relative">
                                    <CpuChipIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <select v-if="detectedModels.length" v-model="form.ai_model" :class="[inputClass, 'pl-10']">
                                        <option value="">{{ t('organization.provider_default_option') }}</option>
                                        <option v-for="m in detectedModels" :key="m" :value="m">{{ m }}</option>
                                    </select>
                                    <input
                                        v-else
                                        v-model="form.ai_model"
                                        type="text"
                                        :placeholder="t('organization.model_placeholder')"
                                        :class="[inputClass, 'pl-10']" />
                                </div>
                                <p v-if="detectedModels.length" class="mt-1 text-xs text-emerald-700">
                                    {{ t('organization.models_detected', { count: detectedModels.length }) }}
                                </p>
                                <p v-if="form.errors.ai_model" class="mt-1 text-sm text-red-600">{{ form.errors.ai_model }}</p>
                            </FormField>

                            <div v-if="organization.ai_provider" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
                                {{ t('organization.currently_configured') }}
                                <strong>{{ organization.ai_provider }}</strong>
                                <span v-if="organization.ai_model"> · {{ organization.ai_model }}</span>
                                <span v-else> · {{ t('organization.default_model_chip') }}</span>
                                <span v-if="!organization.ai_api_key" class="ml-2 inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ t('organization.no_key_yet') }}</span>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection :title="t('organization.notifications.title', { default: 'Notificaciones' })"
                                 :subtitle="t('organization.notifications.subtitle', { default: 'Configura qué alertas recibes y por dónde (in-app, email, webhook).' })">
                        <!-- Webhook (N7) -->
                        <FormField
                            :label="t('organization.notifications.webhook_label', { default: 'Webhook URL (Slack / Discord / Teams)' })"
                            :help="t('organization.notifications.webhook_help', { default: 'Las alertas se env\u00edan como POST JSON. Usa https://hooks.slack.com/services/... o la URL equivalente de Discord/Teams.' })">
                            <div class="relative">
                                <BoltIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    v-model="form.notification_webhook_url"
                                    type="url"
                                    autocomplete="off"
                                    spellcheck="false"
                                    :placeholder="t('organization.notifications.webhook_placeholder', { default: 'https://hooks.slack.com/services/T0.../B0.../XXX' })"
                                    :class="[inputClass, 'pl-10']" />
                            </div>
                            <p v-if="form.errors.notification_webhook_url" class="mt-1 text-sm text-red-600">{{ form.errors.notification_webhook_url }}</p>
                        </FormField>

                        <FormField
                            :label="t('organization.notifications.prefs_label', { default: 'Tipos de alerta' })"
                            :help="t('organization.notifications.prefs_help', { default: 'Silencia los tipos que ya no te aportan valor. Las alertas silenciadas no aparecen en /alerts y no se env\u00edan al webhook.' })">
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                                <span class="text-xs text-gray-500">{{ t('organization.notifications.bulk_label', { default: 'Todos / Ninguno' }) }}</span>
                                <div class="flex gap-1">
                                    <button type="button" @click="enableAll" class="rounded-md px-2 py-1 text-xs font-semibold text-estoril-700 hover:bg-estoril-100">
                                        {{ t('organization.notifications.enable_all', { default: 'Activar' }) }}
                                    </button>
                                    <button type="button" @click="disableAll" class="rounded-md px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        {{ t('organization.notifications.disable_all', { default: 'Silenciar todo' }) }}
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 divide-y divide-gray-100 rounded-lg ring-1 ring-gray-200">
                                <label v-for="type in alertTypes" :key="type" class="flex cursor-pointer items-center justify-between px-4 py-3 hover:bg-gray-50">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ t(`alerts.alert_types.${type}`) }}</p>
                                        <p class="text-xs text-gray-500">{{ t(`organization.notifications.prefs.${type}`, { default: '' }) }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click.prevent="form.notification_preferences[type] = !form.notification_preferences[type]"
                                        :class="['relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-estoril-500 focus:ring-offset-2',
                                            form.notification_preferences[type] ? 'bg-estoril-600' : 'bg-gray-200']">
                                        <span :class="['inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            form.notification_preferences[type] ? 'translate-x-5' : 'translate-x-0']" />
                                    </button>
                                </label>
                            </div>
                        </FormField>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('organization.show', organization.id)" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ t('app.cancel') }}</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? t('organization.saving_changes') : t('organization.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
