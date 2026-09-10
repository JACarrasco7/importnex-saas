<script setup>
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    notes: { type: String, default: '' },
});
</script>

<template>
    <div v-if="notes" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.notes') }}</h3>
        </div>
        <div class="p-6">
            <pre class="whitespace-pre-wrap font-sans text-sm text-gray-700">{{ notes }}</pre>
        </div>
    </div>
</template>
