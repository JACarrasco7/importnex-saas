<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';

const form = useForm({
    name: '',
    contact_info: '',
    looking_for: '',
    budget_min: 0,
    budget_max: 0,
    status: 'New',
    notes: '',
});

const submit = () => form.post(route('clients.store'));

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="New Client" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">New Client</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="New client" subtitle="Add a new customer to your CRM">
                    <template #actions>
                        <Link :href="route('clients.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection title="Contact info">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormField label="Name" required><input v-model="form.name" type="text" required :class="inputClass" /></FormField>
                            <FormField label="Contact info"><input v-model="form.contact_info" type="text" :class="inputClass" placeholder="+34 600 123 456" /></FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Purchase intent">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormField label="Looking for" hint="e.g. SUV automático, diésel, &lt;80k km">
                                <input v-model="form.looking_for" type="text" :class="inputClass" />
                            </FormField>
                            <FormField label="Status" required>
                                <select v-model="form.status" required :class="inputClass">
                                    <option>New</option><option>Briefing</option><option>Quote sent</option>
                                    <option>Negotiating</option><option>Order signed</option><option>In process</option><option>Delivered</option>
                                </select>
                            </FormField>
                            <FormField label="Budget min (€)"><input v-model="form.budget_min" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="Budget max (€)"><input v-model="form.budget_max" type="number" step="0.01" :class="inputClass" /></FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Notes">
                        <FormField label="Internal notes"><textarea v-model="form.notes" rows="3" :class="inputClass" /></FormField>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('clients.index')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Create client' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
