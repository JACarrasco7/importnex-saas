<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, CheckIcon, TruckIcon, UsersIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    plan: String,
    planData: Object,
});

const { currency } = useFormat();
</script>

<template>
    <Head :title="`Plan ${planData.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Plan {{ planData.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="`${planData.name} plan`" :subtitle="planData.description">
                    <template #actions>
                        <Link :href="route('subscriptions.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                    </template>
                </PageHeader>

                <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 p-6 text-white shadow-lg">
                    <p class="text-sm font-medium text-indigo-100">Monthly price</p>
                    <p class="mt-2 text-5xl font-bold tracking-tight">{{ currency(planData.price) }}</p>
                    <p class="mt-2 text-sm text-indigo-100">Billed monthly</p>
                </div>

                <FormSection title="What's included">
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                                <TruckIcon class="h-5 w-5 text-emerald-600" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ planData.cars_limit }} cars</p>
                                <p class="text-xs text-gray-500">Track up to this many vehicles</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                                <UsersIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ planData.clients_limit }} clients</p>
                                <p class="text-xs text-gray-500">Manage your CRM up to this limit</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                                <CheckIcon class="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">All features</p>
                                <p class="text-xs text-gray-500">Kanban, map, AI verification, finance & alerts</p>
                            </div>
                        </li>
                    </ul>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
