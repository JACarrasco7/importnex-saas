<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { UserIcon, EnvelopeIcon, CheckIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-gray-900">{{ t('profile.section_profile_info') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ t('profile.section_profile_info_desc') }}</p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-5">
            <FormField :label="t('profile.name_label')" :error="form.errors.name">
                <div class="relative">
                    <UserIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.name" type="text" required autocomplete="name" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('profile.email_label')" :error="form.errors.email">
                <div class="relative">
                    <EnvelopeIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.email" type="email" required autocomplete="username" :class="inputClass" />
                </div>
            </FormField>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                <div class="flex items-start gap-2">
                    <ExclamationCircleIcon class="h-5 w-5 flex-shrink-0 text-amber-600" />
                    <p class="text-sm text-amber-900">
                        {{ t('profile.email_unverified') }}
                        <Link :href="route('verification.send')" method="post" as="button" class="font-semibold text-amber-700 underline hover:text-amber-800">
                            {{ t('profile.resend_verification') }}
                        </Link>
                    </p>
                </div>
            </div>

            <div v-show="status === 'verification-link-sent'" class="rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                {{ t('profile.verification_link_sent') }}
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                    <CheckIcon class="h-4 w-4" />
                    {{ form.processing ? t('profile.saving') : t('profile.save') }}
                </button>
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600">{{ t('profile.saved') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
