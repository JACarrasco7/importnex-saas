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
    contact: Object,
    clients: Array,
});

const form = useForm({
    name: props.contact.name,
    phone: props.contact.phone ?? '',
    email: props.contact.email ?? '',
    city: props.contact.city ?? '',
    client_id: props.contact.client_id ?? null,
    tags: Array.isArray(props.contact.tags) ? props.contact.tags.join(', ') : '',
    notes: props.contact.notes ?? '',
});

const submit = () => form.put(route('contacts.update', props.contact.id));

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <Head :title="t('contacts.edit')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('contacts.edit') }}: {{ contact.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="`${t('contacts.edit')} ${contact.name}`" :subtitle="t('contacts.edit_subtitle')">
                    <template #actions>
                        <Link :href="route('contacts.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection title="Contact info">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormField label="Name" required><input v-model="form.name" type="text" required :class="inputClass" /></FormField>
                            <FormField label="Phone"><input v-model="form.phone" type="tel" :class="inputClass" /></FormField>
                            <FormField label="Email"><input v-model="form.email" type="email" :class="inputClass" /></FormField>
                            <FormField label="City"><input v-model="form.city" type="text" :class="inputClass" /></FormField>
                            <FormField label="Client" v-if="clients?.length">
                                <select v-model="form.client_id" :class="inputClass">
                                    <option :value="null">— No client —</option>
                                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Tags & notes">
                        <FormField label="Tags" hint="Comma separated">
                            <input v-model="form.tags" type="text" :class="inputClass" />
                        </FormField>
                        <FormField label="Notes">
                            <textarea v-model="form.notes" rows="3" :class="inputClass" />
                        </FormField>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('contacts.index')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Cancelar</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? 'Updating...' : 'Update contact' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
