<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({ status: String });

const form = useForm({ email: '' });

const submit = () => form.post(route('password.email'));

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm text-gray-900 shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white';
</script>

<template>
    <Head :title="t('auth.forgot_password')" />

    <GuestLayout>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('auth.forgot_password') }}</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ t('auth.forgot_password_desc') }}</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField :label="t('auth.email')" :error="form.errors.email">
                <div class="relative">
                    <EnvelopeIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.email" type="email" required autofocus autocomplete="username" :class="inputClass" />
                </div>
            </FormField>

            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                <ArrowRightIcon class="h-4 w-4" />
                {{ form.processing ? t('app.loading') : t('auth.send_reset_link') }}
            </button>

            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                {{ t('auth.no_account') }}
                <Link :href="route('login')" class="font-semibold text-estoril-600 hover:text-estoril-500 dark:text-estoril-300">{{ t('auth.login') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>
