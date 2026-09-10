<script setup>
import {
    ShareIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    show: { type: Boolean, default: false },
    email: { type: String, default: '' },
    expectedDeliveryDate: { type: String, default: '' },
    errors: { type: Object, default: () => ({}) },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:show',
    'update:email',
    'update:expectedDeliveryDate',
    'submit',
]);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-asphalt-900/60 p-4" @click.self="emit('update:show', false)">
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900">
                            <ShareIcon class="h-5 w-5 text-estoril-600" />
                            {{ t('cars.tracking.modal_title') }}
                        </h3>
                        <button type="button" @click="emit('update:show', false)" class="text-gray-400 hover:text-gray-600">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="emit('submit')" class="space-y-4 p-6">
                        <p class="text-sm text-gray-600">{{ t('cars.tracking.modal_help') }}</p>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.tracking.email_label') }}</label>
                            <input
                                :value="email"
                                @input="(e) => emit('update:email', e.target.value)"
                                type="email"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                                :placeholder="t('cars.tracking.email_placeholder')"
                            />
                            <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.tracking.eta_label') }}</label>
                            <input
                                :value="expectedDeliveryDate"
                                @input="(e) => emit('update:expectedDeliveryDate', e.target.value)"
                                type="date"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                            />
                        </div>
                        <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                            <button type="button" @click="emit('update:show', false)" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50">
                                {{ t('common.cancel') }}
                            </button>
                            <button type="submit" :disabled="processing" class="inline-flex items-center gap-1 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500 disabled:opacity-50">
                                {{ t('cars.tracking.generate') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
