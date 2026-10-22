<script setup>
import { CheckCircleIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';
import PortalSection from '@/Components/Portal/PortalSection.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

defineProps({
    milestones: { type: Array, default: () => [] },
    inspection: { type: Object, default: null },
    gallery: { type: Array, default: () => [] },
});

const { t } = useTranslations();
const { date } = useFormat();
</script>

<template>
    <PortalSection id="progreso" :title="t('portal.progress.title')" :icon="ClipboardDocumentCheckIcon">
        <div v-if="inspection" class="mb-5">
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-asphalt-900">{{ t('portal.progress.inspection') }}</span>
                <span class="text-asphalt-600">{{ t('portal.progress.inspection_count', { done: inspection.completed, total: inspection.total }) }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-asphalt-100">
                <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${inspection.percent}%` }" />
            </div>
        </div>

        <ul v-if="milestones.length" class="space-y-2">
            <li v-for="m in milestones" :key="m.key" class="flex items-center justify-between gap-3 text-sm">
                <span :class="['flex items-center gap-2', m.completed ? 'text-asphalt-900' : 'text-asphalt-400']">
                    <CheckCircleIcon :class="['h-5 w-5', m.completed ? 'text-emerald-600' : 'text-asphalt-300']" aria-hidden="true" />
                    {{ m.name }}
                </span>
                <span class="text-xs text-asphalt-500">{{ m.completed_at ? date(m.completed_at) : t('portal.progress.pending') }}</span>
            </li>
        </ul>

        <div v-if="gallery.length" class="mt-5">
            <h3 class="mb-2 text-sm font-semibold text-asphalt-900">{{ t('portal.progress.gallery') }}</h3>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                <a v-for="item in gallery" :key="item.id" :href="item.url" target="_blank" rel="noopener" class="group block overflow-hidden rounded-lg bg-asphalt-100 ring-1 ring-asphalt-200" :title="item.caption || item.name">
                    <img v-if="item.is_image" :src="item.url" :alt="item.caption || item.name" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105" />
                    <span v-else class="flex aspect-square items-center justify-center p-2 text-center text-xs text-asphalt-600">{{ item.name }}</span>
                </a>
            </div>
        </div>
    </PortalSection>
</template>
