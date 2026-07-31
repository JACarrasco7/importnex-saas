<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, LockClosedIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head :title="t('auth.reset_password')" />

    <GuestLayout>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">{{ t('auth.reset_password') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ t('auth.choose_new_password') }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField :label="t('auth.email')" :error="form.errors.email">
                <div class="relative">
                    <EnvelopeIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.email" type="email" required autofocus autocomplete="username" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('auth.new_password')" :error="form.errors.password">
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

            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <ArrowRightIcon class="h-4 w-4" />
                {{ form.processing ? t('app.loading') : t('auth.reset_password') }}
            </button>
        </form>
    </GuestLayout>
</template>
