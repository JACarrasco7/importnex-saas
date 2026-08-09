<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircleIcon, TruckIcon, PhoneIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    organization: Object,
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('car_request_success.title')" />

    <div class="min-h-screen bg-linear-to-br from-estoril-50 via-white to-platinum-100 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800">
        <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                <div class="px-8 py-12 text-center">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-estoril-100 dark:bg-estoril-900/40">
                        <CheckCircleIcon class="h-12 w-12 text-estoril-700 dark:text-estoril-300" />
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ t('car_request_success.heading') }}
                    </h1>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                        {{ t('car_request_success.description') }}
                    </p>

                    <div v-if="organization.logo" class="mx-auto mt-8 h-16 w-16 overflow-hidden rounded-xl bg-white shadow-md dark:bg-asphalt-900">
                        <img :src="organization.logo" :alt="organization.name" class="h-full w-full object-cover" />
                    </div>
                    <p class="mt-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ organization.name }}
                    </p>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-8 py-6 dark:border-asphalt-700 dark:bg-asphalt-800">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                        {{ t('car_request_success.what_now') }}
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-estoril-100 p-2 dark:bg-estoril-900/40">
                                <TruckIcon class="h-5 w-5 text-estoril-700 dark:text-estoril-300" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ t('car_request_success.step1_title') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('car_request_success.step1_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-estoril-100 p-2 dark:bg-estoril-900/40">
                                <PhoneIcon class="h-5 w-5 text-estoril-700 dark:text-estoril-300" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ t('car_request_success.step2_title') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('car_request_success.step2_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-estoril-100 p-2 dark:bg-estoril-900/40">
                                <EnvelopeIcon class="h-5 w-5 text-estoril-700 dark:text-estoril-300" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ t('car_request_success.step3_title') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('car_request_success.step3_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-8 py-6 text-center dark:border-asphalt-700">
                    <Link
                        :href="route('public.car-request.index', organization.slug)"
                        class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-800"
                    >
                        {{ t('car_request_success.send_another') }}
                    </Link>
                </div>
            </div>

            <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>&copy; {{ new Date().getFullYear() }} {{ organization.name }}. {{ t('car_request_success.rights') }}</p>
            </div>
        </div>
    </div>
</template>
