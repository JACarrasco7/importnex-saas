<script setup>
import { ref } from 'vue';
import {
    CheckCircleIcon,
    MinusCircleIcon,
    ChevronDownIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const { date } = useFormat();

const props = defineProps({
    car: { type: Object, required: true },
    derived: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['toggle-checklist']);

const expandedSections = ref({});
const toggleSection = (key) => { expandedSections.value[key] = !expandedSections.value[key]; };

const priorityVariant = (p) => ({
    critical: 'danger', important: 'warning', minor: 'neutral',
}[p] || 'neutral');

const onToggle = (item) => emit('toggle-checklist', item);
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.section_checklist') }}</h3>
        </div>

        <!-- Milestones progress -->
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <div class="mb-2 flex items-center justify-between">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700">{{ t('marketplace_show.milestones') }}</h4>
                <span class="text-sm font-mono font-semibold text-gray-900">
                    {{ derived?.milestones_progress?.completed || 0 }} / {{ derived?.milestones_progress?.total || 0 }}
                </span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div class="h-full bg-estoril-500 transition-all"
                    :style="{ width: derived?.milestones_progress?.total ? ((derived.milestones_progress.completed / derived.milestones_progress.total) * 100) + '%' : '0%' }" />
            </div>
            <ul class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <li v-for="m in car.checklists?.filter(c => c.kind === 'milestone') || []" :key="m.id"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3">
                    <button @click="onToggle(m)" type="button" class="shrink-0">
                        <CheckCircleIcon v-if="m.completed" class="h-5 w-5 text-green-600" />
                        <MinusCircleIcon v-else class="h-5 w-5 text-gray-400" />
                    </button>
                    <div class="flex-1">
                        <p class="text-sm font-medium" :class="m.completed ? 'text-gray-500 line-through' : 'text-gray-900'">
                            {{ m.item_key.replace(/_/g, ' ') }}
                        </p>
                        <p v-if="m.completed_at" class="text-xs text-gray-500">{{ date(m.completed_at) }}</p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Inspections -->
        <div class="px-6 py-4">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                    Inspección ({{ derived?.inspections_progress?.completed || 0 }} / {{ derived?.inspections_progress?.total || 0 }})
                </h4>
                <span class="text-xs text-gray-500">{{ t('cars.apply_what_relevant') }}</span>
            </div>
            <div class="space-y-2">
                <div v-for="section in derived?.inspections_by_section || []" :key="section.section" class="rounded-lg border border-gray-200">
                    <button @click="toggleSection(section.section)" type="button" class="flex w-full items-center justify-between px-4 py-2 hover:bg-gray-50">
                        <span class="flex items-center gap-2">
                            <component :is="expandedSections[section.section] ? ChevronDownIcon : ChevronRightIcon" class="h-4 w-4 text-gray-400" />
                            <span class="text-sm font-semibold text-gray-900">{{ section.section }}</span>
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ section.items.filter(i => i.completed).length }} / {{ section.items.length }}
                        </span>
                    </button>
                    <ul v-if="expandedSections[section.section]" class="divide-y divide-gray-100 border-t border-gray-200">
                        <li v-for="item in section.items" :key="item.id" class="flex items-start gap-3 px-4 py-2 hover:bg-gray-50">
                            <button @click="onToggle(item)" type="button" class="mt-0.5 shrink-0">
                                <CheckCircleIcon v-if="item.completed" class="h-5 w-5 text-green-600" />
                                <MinusCircleIcon v-else class="h-5 w-5 text-gray-400" />
                            </button>
                            <div class="flex-1">
                                <p class="text-sm" :class="item.completed ? 'text-gray-500 line-through' : 'text-gray-900'">
                                    {{ item.item_key.replace(/_/g, ' ') }}
                                </p>
                                <Badge v-if="item.priority" :variant="priorityVariant(item.priority)" size="sm">
                                    {{ item.priority }}
                                </Badge>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
