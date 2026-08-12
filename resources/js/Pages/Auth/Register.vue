<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserIcon, EnvelopeIcon, LockClosedIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm text-gray-900 shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white';
</script>

<template>
    <Head :title="t('auth.register')" />

    <GuestLayout>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('auth.register') }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ t('auth.register_subtitle') }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField :label="t('auth.name')" :error="form.errors.name">
                <div class="relative">
                    <UserIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.name" type="text" required autofocus autocomplete="name" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('auth.email')" :error="form.errors.email">
                <div class="relative">
                    <EnvelopeIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.email" type="email" required autocomplete="username" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('auth.password')" :error="form.errors.password">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.password" type="password" required autocomplete="new-password" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('auth.confirm_password')" :error="form.errors.password_confirmation">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" :class="inputClass" />
                </div>
            </FormField>

            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                <ArrowRightIcon class="h-4 w-4" />
                {{ form.processing ? t('app.loading') : t('auth.register') }}
            </button>

            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                {{ t('auth.have_account') }}
                <Link :href="route('login')" class="font-semibold text-estoril-600 hover:text-estoril-500 dark:text-estoril-300">{{ t('auth.login') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>
