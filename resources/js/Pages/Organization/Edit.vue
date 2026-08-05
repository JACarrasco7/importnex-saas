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
});

const form = useForm({
    name: props.organization.name,
    currency: props.organization.currency || 'EUR',
    locale: props.organization.locale || 'es',
    ai_provider: props.organization.ai_provider || '',
    ai_model: props.organization.ai_model || '',
    ai_api_key: '',
});

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
