<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LockClosedIcon, KeyIcon, CheckIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-gray-900">{{ t('profile.section_update_password') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ t('profile.section_update_password_desc') }}</p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-5">
            <FormField :label="t('profile.field_current_password')" :error="form.errors.current_password">
                <div class="relative">
                    <KeyIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input ref="currentPasswordInput" v-model="form.current_password" type="password" autocomplete="current-password" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('profile.field_new_password')" :error="form.errors.password">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input ref="passwordInput" v-model="form.password" type="password" autocomplete="new-password" :class="inputClass" />
                </div>
            </FormField>

            <FormField :label="t('profile.field_confirm_password')" :error="form.errors.password_confirmation">
                <div class="relative">
                    <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="form.password_confirmation" type="password" autocomplete="new-password" :class="inputClass" />
                </div>
            </FormField>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                    <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <CheckIcon v-else class="h-4 w-4" />
                    {{ form.processing ? t('profile.updating') : t('profile.update_password_btn') }}
                </button>
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600">{{ t('profile.updated') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
