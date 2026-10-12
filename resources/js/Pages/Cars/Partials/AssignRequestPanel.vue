<script setup>
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const { t } = useTranslations();
const { currency } = useFormat();

const props = defineProps({
    derived: { type: Object, default: () => ({}) },
    linking: { type: Boolean, default: false },
});

const emit = defineEmits(['link-selected-request']);

const selectedRequestId = ref('');

const onLink = () => {
    if (!selectedRequestId.value) return;
    emit('link-selected-request', Number(selectedRequestId.value));
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.select_request_title') }}</h3>
            <span class="text-xs text-gray-500">{{ derived.selectable_requests?.length || 0 }}</span>
        </div>
        <div class="space-y-3 p-4">
            <select
                v-model="selectedRequestId"
                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-estoril-500 focus:ring-estoril-500"
            >
                <option value="">{{ t('cars.select_request_placeholder') }}</option>
                <option v-for="req in derived.selectable_requests || []" :key="req.id" :value="req.id">
                    {{ req.name || t('cars.matching_no_name') }}{{ req.brand ? ' · ' + req.brand + (req.model ? ' ' + req.model : '') : '' }}{{ req.budget_max ? ' · ' + currency(req.budget_max) : '' }}
                </option>
            </select>
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-gray-500">{{ t('cars.select_request_help') }}</p>
                <button
                    type="button"
                    :disabled="!selectedRequestId || linking"
                    class="shrink-0 rounded-lg bg-estoril-600 px-4 py-2 text-xs font-semibold text-white hover:bg-estoril-500 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="onLink"
                >
                    <span v-if="linking">…</span>
                    <span v-else>{{ t('cars.matching_link') }}</span>
                </button>
            </div>
            <slot name="messages" />
        </div>
    </div>
</template>
