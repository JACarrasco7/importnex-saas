<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    client: Object,
});

const form = useForm({
    name: props.client.name,
    contact_info: props.client.contact_info,
    looking_for: props.client.looking_for,
    budget_min: props.client.budget_min,
    budget_max: props.client.budget_max,
    status: props.client.status,
    notes: props.client.notes,
});

const submit = () => form.patch(route('clients.update', props.client.id));

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <Head :title="t('clients.edit_title', { name: client.name })" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('clients.edit_title', { name: client.name }) }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('clients.edit_page_title', { name: client.name })" :subtitle="t('clients.edit_subtitle')">
                    <template #actions>
                        <Link :href="route('clients.show', client.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection :title="t('clients.section_contact')">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormField :label="t('clients.field_name')" required><input v-model="form.name" type="text" required :class="inputClass" /></FormField>
                            <FormField :label="t('clients.field_contact_info')"><input v-model="form.contact_info" type="text" :class="inputClass" placeholder="+34 600 123 456" /></FormField>
                        </div>
                    </FormSection>

                    <FormSection :title="t('clients.section_purchase_intent')">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormField :label="t('clients.field_looking_for')">
                                <input v-model="form.looking_for" type="text" :class="inputClass" />
                            </FormField>
                            <FormField :label="t('clients.field_status')" required>
                                <select v-model="form.status" required :class="inputClass">
                                    <option v-for="s in ['New','Briefing','Quote sent','Negotiating','Order signed','In process','Delivered']" :key="s" :value="s">{{ t('clients.status.' + s) }}</option>
                                </select>
                            </FormField>
                            <FormField :label="t('clients.field_budget_min')"><input v-model="form.budget_min" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField :label="t('clients.field_budget_max')"><input v-model="form.budget_max" type="number" step="0.01" :class="inputClass" /></FormField>
                        </div>
                    </FormSection>

                    <FormSection :title="t('clients.section_notes')">
                        <FormField :label="t('clients.field_internal_notes')"><textarea v-model="form.notes" rows="3" :class="inputClass" /></FormField>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('clients.show', client.id)" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ t('cars.cancel') }}</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? t('cars.updating') : t('cars.update_client') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
