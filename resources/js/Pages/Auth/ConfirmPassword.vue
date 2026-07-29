<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { LockClosedIcon, ShieldCheckIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';

const form = useForm({ password: '' });

const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="Confirm Password" />

    <GuestLayout>
        <div class="mb-8">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50">
                <ShieldCheckIcon class="h-6 w-6 text-amber-600" />
            </div>
            <h2 class="text-center text-2xl font-bold text-gray-900">Confirm your password</h2>
            <p class="mt-2 text-center text-sm text-gray-600">This is a secure area. Please confirm your password to continue.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Password" :error="form.errors.password">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.password" type="password" required autocomplete="current-password" autofocus :class="inputClass" />
                </div>
            </FormField>

            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <ArrowRightIcon class="h-4 w-4" />
                {{ form.processing ? 'Confirming...' : 'Confirm' }}
            </button>
        </form>
    </GuestLayout>
</template>
