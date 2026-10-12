<script setup>
import {
    GlobeEuropeAfricaIcon,
    LanguageIcon,
} from '@heroicons/vue/24/outline';
import MapaLeaflet from '@/Components/MapaLeaflet.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const { currency } = useFormat();

defineProps({
    car: { type: Object, required: true },
    specItems: { type: Array, required: true },
    costItems: { type: Array, required: true },
    derived: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="space-y-6">
        <!-- Location -->
        <div v-if="car.lat && car.lng" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.location') }}</h3>
            </div>
            <div class="p-6">
                <MapaLeaflet :lat="car.lat" :lng="car.lng" :marker-text="t('cars.car_full_name', { brand: car.brand, model: car.model, year: car.city || '' })" height="300px" />
            </div>
        </div>

        <!-- Technical specs -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.section_tech_sheet') }}</h3>
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                <div v-for="spec in specItems" :key="spec.key">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ spec.label }}</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900" :class="{ 'font-mono text-xs': spec.key === 'vin' }">{{ car[spec.key] ?? '—' }}{{ spec.suffix || '' }}</dd>
                </div>
            </div>
        </div>

        <!-- Costs -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.costs') }}</h3>
                <span class="text-sm text-gray-500">{{ t('cars.total_all_included') }} <span class="font-mono font-semibold text-gray-900">{{ currency(derived?.total_cost) }}</span></span>
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 md:grid-cols-4">
                <div v-for="cost in costItems" :key="cost.key">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ cost.label }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ currency(car[cost.key]) }}</dd>
                </div>
            </div>
        </div>

        <!-- Descripción del anuncio (original + traducción SIEMPRE si existen) -->
        <div v-if="car.description || car.original_description" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.description') }}</h3>
                <span v-if="car.original_description && car.description" class="rounded-full bg-estoril-50 px-2.5 py-0.5 text-[11px] font-medium text-estoril-600">{{ t('cars.description_bilingual') }}</span>
            </div>
            <div class="space-y-5 p-6">
                <div v-if="car.original_description">
                    <h4 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <GlobeEuropeAfricaIcon class="h-3.5 w-3.5 text-gray-400" />
                        {{ t('cars.original_text') }}
                    </h4>
                    <p class="mt-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 whitespace-pre-wrap">{{ car.original_description }}</p>
                </div>
                <div v-if="car.description">
                    <h4 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <LanguageIcon class="h-3.5 w-3.5 text-gray-400" />
                        {{ t('cars.translated_text') }}
                    </h4>
                    <p class="mt-2 rounded-lg bg-estoril-50/50 p-3 text-sm text-gray-900 whitespace-pre-wrap">{{ car.description }}</p>
                </div>
                <p v-else-if="!car.original_description" class="text-sm text-gray-500">{{ t('cars.description') }}</p>
            </div>
        </div>

        <!-- Equipamiento del anuncio (lista COMPLETA del JSON) -->
        <div v-if="car.equipment?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">{{ t('cars.equipment') }}</h3>
            </div>
            <div class="p-6">
                <ul class="flex flex-wrap gap-2">
                    <li v-for="(item, i) in car.equipment" :key="i"
                        class="rounded-full bg-estoril-50 px-3 py-1 text-sm text-estoril-700 ring-1 ring-estoril-100">
                        {{ item }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
