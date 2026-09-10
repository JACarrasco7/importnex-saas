<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    PencilIcon,
    SparklesIcon,
    MegaphoneIcon,
    LinkIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const { statusLabel, statusVariant, trafficLightVariant } = useFormat();

defineProps({
    car: { type: Object, required: true },
    derived: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="space-y-6">
        <PageHeader :title="t('cars.marketplace_brand_model', { brand: car.brand, model: car.model })" :subtitle="`VIN ${car.vin || '—'}`">
            <template #actions>
                <Link :href="route('cars.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <ArrowLeftIcon class="h-4 w-4" />
                    {{ t('common.back') }}
                </Link>
                <Link :href="route('cars.verify.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                    <SparklesIcon class="h-4 w-4" />
                    {{ t('cars.verify_with_ai') }}
                </Link>
                <Link :href="route('cars.marketing', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-600">
                    <MegaphoneIcon class="h-4 w-4" />
                    {{ t('cars.marketing') }}
                </Link>
                <Link :href="route('cars.edit', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                    <PencilIcon class="h-4 w-4" />
                    {{ t('cars.edit_action') }}
                </Link>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-3">
            <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
            <Badge :variant="statusVariant(car.status)">{{ statusLabel(t, car.status) }}</Badge>
            <span v-if="car.year" class="text-sm text-gray-500">{{ car.year }}</span>
            <a v-if="car.url_link"
               :href="car.url_link"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 rounded-lg bg-estoril-50 px-3 py-1.5 text-xs font-medium text-estoril-700 ring-1 ring-estoril-200 hover:bg-estoril-100">
                <LinkIcon class="h-3.5 w-3.5" />
                {{ t('cars.original_listing') }}
            </a>
        </div>

        <div v-if="car.client" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-center gap-3">
                <UserCircleIcon class="h-8 w-8 text-emerald-600" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-900">
                        {{ t('cars.linked_to_client') }} {{ car.client.name }}
                    </p>
                    <p class="text-xs text-emerald-700">
                        <template v-if="derived?.linked_request">
                            {{ t('cars.linked_request') }} #{{ derived.linked_request.id }}
                            · <Badge :variant="statusVariant(derived.linked_request.status)" size="sm">{{ t('car_requests.status.' + derived.linked_request.status, derived.linked_request.status) }}</Badge>
                        </template>
                        <template v-else>
                            {{ t('cars.no_request_linked') }}
                        </template>
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 gap-2">
                <Link :href="route('clients.show', car.client.id)" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                    {{ t('cars.view_client') }} →
                </Link>
                <Link v-if="derived?.linked_request" :href="route('car-requests.show', derived.linked_request.id)" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-200 hover:bg-emerald-100">
                    {{ t('cars.view_request') }} →
                </Link>
            </div>
        </div>
    </div>
</template>
