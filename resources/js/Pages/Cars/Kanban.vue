<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { TruckIcon, Squares2X2Icon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    columns: Array,
    totalCars: Number,
    totalValue: Number,
});

const dragging = ref(null);
const dragOverColumn = ref(null);

const { currency, statusVariant, trafficLightVariant } = useFormat();
const { t } = useTranslations();

const startDrag = (car) => { dragging.value = car; };
const onDragOver = (event, column) => { event.preventDefault(); dragOverColumn.value = column.key; };
const onDragLeave = () => { dragOverColumn.value = null; };
const onDrop = (column) => {
    if (!dragging.value || dragging.value.status === column.key) {
        dragging.value = null;
        dragOverColumn.value = null;
        return;
    }
    router.post(route('cars.kanban.move', dragging.value.id), { status: column.key }, {
        preserveScroll: true,
        onSuccess: () => { dragging.value = null; dragOverColumn.value = null; },
    });
};
</script>

<template>
    <Head :title="t('nav.kanban')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('nav.kanban') }}</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('nav.kanban')" :subtitle="t('app.kanban_subtitle')">
                    <template #actions>
                        <Link :href="route('cars.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <Squares2X2Icon class="h-4 w-4" />
                            List view
                        </Link>
                        <span class="rounded-lg bg-white px-4 py-2 text-sm text-gray-700 shadow-sm ring-1 ring-gray-200">
                            <strong>{{ totalCars }}</strong> cars · <strong>{{ currency(totalValue) }}</strong>
                        </span>
                    </template>
                </PageHeader>

                <div class="flex gap-4 overflow-x-auto pb-4">
                    <div v-for="column in columns" :key="column.key"
                        @dragover="onDragOver($event, column)"
                        @dragleave="onDragLeave"
                        @drop="onDrop(column)"
                        :class="['flex-shrink-0 w-72 rounded-2xl p-3 transition', dragOverColumn === column.key ? 'bg-indigo-100 ring-2 ring-indigo-400' : 'bg-gray-100']">
                        <div class="flex items-center justify-between px-2 py-2">
                            <h3 class="text-sm font-semibold text-gray-700">{{ column.label }}</h3>
                            <span class="rounded-full bg-gray-300 px-2 py-0.5 text-xs font-semibold text-gray-700">
                                {{ column.cars.length }}
                            </span>
                        </div>

                        <div class="space-y-2 min-h-[200px]">
                            <div v-for="car in column.cars" :key="car.id"
                                draggable="true"
                                @dragstart="startDrag(car)"
                                class="cursor-move rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-200 hover:shadow-md">
                                <div class="flex items-start justify-between">
                                    <Link :href="route('cars.show', car.id)" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ car.brand }} {{ car.model }}
                                    </Link>
                                    <Badge :variant="trafficLightVariant(car.traffic_light)" dot />
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ car.year }}</p>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-gray-900">{{ currency(car.purchase_price) }}</span>
                                    <Link :href="route('cars.show', car.id)" class="text-gray-400 hover:text-indigo-600">
                                        <ChevronRightIcon class="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                            <div v-if="!column.cars.length" class="rounded-lg border-2 border-dashed border-gray-300 p-4 text-center text-xs text-gray-400">
                                Drop cars here
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
