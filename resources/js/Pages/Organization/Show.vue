<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { PencilIcon, BuildingOfficeIcon, CreditCardIcon, CalendarIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    organization: Object,
});

const { date } = useFormat();
</script>

<template>
    <Head title="Organization" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Organization</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="organization.name" subtitle="Organization details">
                    <template #actions>
                        <Link :href="route('organization.edit', organization.id)" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PencilIcon class="h-4 w-4" />
                            Edit
                        </Link>
                    </template>
                </PageHeader>

                <FormSection title="Details">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                                <BuildingOfficeIcon class="h-5 w-5 text-indigo-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</dt>
                                <dd class="font-medium text-gray-900">{{ organization.name }}</dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                                <CreditCardIcon class="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Plan</dt>
                                <dd class="font-medium text-gray-900">{{ organization.plan || 'starter' }}</dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                                <CalendarIcon class="h-5 w-5 text-amber-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Trial ends</dt>
                                <dd class="font-medium text-gray-900">{{ organization.trial_ends_at ? date(organization.trial_ends_at) : 'No trial' }}</dd>
                            </div>
                        </div>
                    </div>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
