<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, LockClosedIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="Log in" />

    <GuestLayout>
        <div v-if="status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
            {{ status }}
        </div>

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
            <p class="mt-1 text-sm text-gray-600">Log in to your Importnex account</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Email" :error="form.errors.email">
                <div class="relative">
                    <EnvelopeIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.email" type="email" required autocomplete="username" :class="inputClass" />
                </div>
            </FormField>

            <FormField label="Password" :error="form.errors.password">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.password" type="password" required autocomplete="current-password" :class="inputClass" />
                </div>
            </FormField>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                    <span class="text-sm text-gray-700">Remember me</span>
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                    Forgot password?
                </Link>
            </div>

            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <ArrowRightIcon class="h-4 w-4" />
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>

            <p class="text-center text-sm text-gray-600">
                Don't have an account?
                <Link :href="route('register')" class="font-semibold text-indigo-600 hover:text-indigo-500">Create one</Link>
            </p>
        </form>
    </GuestLayout>
</template>
