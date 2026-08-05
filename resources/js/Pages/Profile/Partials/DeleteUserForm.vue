<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { TrashIcon, ExclamationTriangleIcon, LockClosedIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};

const inputClass = 'block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500';
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-gray-900">{{ t('profile.section_delete_account') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ t('profile.section_delete_account_desc') }}</p>
        </header>

        <button type="button" @click="confirmUserDeletion" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500">
            <TrashIcon class="h-4 w-4" />
            {{ t('profile.delete_account_btn') }}
        </button>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-rose-50">
                                    <ExclamationTriangleIcon class="h-6 w-6 text-rose-600" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ t('profile.delete_account_confirm_title') }}</h3>
                                    <p class="mt-2 text-sm text-gray-600">{{ t('profile.delete_account_confirm_desc') }}</p>
                                </div>
                            </div>

                            <form @submit.prevent="deleteUser" class="mt-6 space-y-4">
                                <FormField :label="t('profile.password_label')" :error="form.errors.password">
                                    <div class="relative">
                                        <LockClosedIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <input ref="passwordInput" v-model="form.password" type="password" autocomplete="current-password" :class="inputClass" />
                                    </div>
                                </FormField>

                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="closeModal" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        <XMarkIcon class="h-4 w-4" />
                                        {{ t('profile.cancel') }}
                                    </button>
                                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:opacity-50">
                                        <TrashIcon class="h-4 w-4" />
                                        {{ form.processing ? t('profile.deleting') : t('profile.delete_account_final') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>
