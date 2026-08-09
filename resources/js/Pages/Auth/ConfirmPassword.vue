<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { LockClosedIcon, ShieldCheckIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const form = useForm({ password: '' });

const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm text-gray-900 shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white';
</script>

<template>
    <Head :title="t('auth.confirm_password')" />

    <GuestLayout>
        <div class="mb-8">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/30">
                <ShieldCheckIcon class="h-6 w-6 text-amber-600 dark:text-amber-300" />
            </div>
            <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white">{{ t('auth.confirm_password') }}</h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">{{ t('auth.confirm_password_desc') }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField :label="t('auth.password')" :error="form.errors.password">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.password" type="password" required autocomplete="current-password" autofocus :class="inputClass" />
                </div>
            </FormField>

            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                <ArrowRightIcon class="h-4 w-4" />
                {{ form.processing ? t('app.loading') : t('auth.confirm') }}
            </button>
        </form>
    </GuestLayout>
</template>
