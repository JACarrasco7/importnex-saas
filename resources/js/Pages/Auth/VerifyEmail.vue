<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, ArrowPathIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({ status: String });

const form = useForm({});
const submit = () => form.post(route('verification.send'));

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head :title="t('auth.verify_email')" />

    <GuestLayout>
        <div class="mb-8">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-estoril-50">
                <EnvelopeIcon class="h-6 w-6 text-estoril-600" />
            </div>
            <h2 class="text-center text-2xl font-bold text-gray-900">{{ t('auth.verify_title') }}</h2>
            <p class="mt-2 text-center text-sm text-gray-600">{{ t('auth.verify_desc') }}</p>
        </div>

        <div v-if="verificationLinkSent" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
            {{ t('auth.verification_link_sent') }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                <ArrowPathIcon class="h-4 w-4" />
                {{ form.processing ? t('auth.sending') : t('auth.resend_verification') }}
            </button>

            <Link :href="route('logout')" method="post" as="button" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50">
                <ArrowRightOnRectangleIcon class="h-4 w-4" />
                {{ t('auth.logout') }}
            </Link>
        </form>
    </GuestLayout>
</template>
