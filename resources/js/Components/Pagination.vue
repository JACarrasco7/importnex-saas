<template>
    <div v-if="pagination && pagination.links && pagination.last_page > 1" class="flex items-center justify-between rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-200">
        <div class="text-sm text-gray-700">
            {{ t('common.showing') }} <span class="font-semibold">{{ pagination.from }}</span> {{ t('common.to') }} <span class="font-semibold">{{ pagination.to }}</span> {{ t('common.of') }} <span class="font-semibold">{{ pagination.total }}</span>
        </div>
        <div class="flex items-center gap-1">
            <component v-for="link in pagination.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
                'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md px-2 text-sm',
                link.active ? 'bg-estoril-600 text-white' : 'text-gray-700 hover:bg-gray-100',
                !link.url ? 'cursor-not-allowed opacity-50' : '',
            ]">
                <ChevronLeftIcon v-if="link.label.includes('Previous')" class="h-4 w-4" />
                <ChevronRightIcon v-else-if="link.label.includes('Next')" class="h-4 w-4" />
                <span v-else v-html="link.label"></span>
            </component>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

defineProps({
    pagination: { type: Object, required: true },
});

const { t } = useTranslations();
</script>
