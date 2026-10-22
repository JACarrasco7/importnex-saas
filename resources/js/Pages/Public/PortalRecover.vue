<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const page = usePage();
const flash = computed(() => page.props.flash || {});

const form = useForm({ email: '' });
const submit = () => form.post(route('portal.recover'), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <Head :title="t('portal.recover.title')" />

    <div class="flex min-h-screen items-center justify-center bg-asphalt-50 px-4 py-12">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-sm ring-1 ring-asphalt-100 sm:p-8">
            <img src="/images/jj-import/logo-insignia.png" alt="JJ Import Motors" class="mx-auto h-12 w-auto" />
            <h1 class="mt-5 text-center text-xl font-bold text-asphalt-900">{{ t('portal.recover.title') }}</h1>
            <p class="mt-2 text-center text-sm text-asphalt-600">{{ t('portal.recover.subtitle') }}</p>

            <div v-if="flash.success" class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">{{ flash.success }}</div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label for="recover-email" class="mb-1 block text-sm font-medium text-asphalt-700">{{ t('portal.recover.email') }}</label>
                    <input id="recover-email" v-model="form.email" type="email" autocomplete="email" required class="block w-full rounded-xl border-asphalt-200 px-4 py-3 text-base shadow-sm focus:border-estoril-500 focus:ring-estoril-500" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                </div>
                <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center rounded-xl bg-estoril-700 px-6 py-3 text-base font-semibold text-white hover:bg-estoril-600 disabled:opacity-50">
                    {{ t('portal.recover.submit') }}
                </button>
            </form>
        </div>
    </div>
</template>
