<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowTopRightOnSquareIcon,
    ExclamationTriangleIcon,
    GlobeAltIcon,
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

const props = defineProps({
    car: { type: Object, required: true },
    derived: { type: Object, default: () => ({}) },
});

/**
 * §marketplace (25-sep-2026) — toggle de publicación desde la ficha.
 *
 * El estado lo calcula el backend (`Car::marketplaceStatus()`) con las mismas
 * 4 condiciones que aplica `PublicMarketplaceController@index`, así que aquí
 * solo se pinta. Si el coche está marcado pero le falta alguna condición, se
 * listan las pendientes con su explicación (antes el operador marcaba el
 * toggle y no entendía por qué no aparecía en la web pública).
 */
const toggling = ref(false);

const marketplaceVisible = computed(() => props.derived?.marketplace_status?.visible === true);

const pendingChecks = computed(() =>
    (props.derived?.marketplace_status?.checks ?? []).filter((c) => !c.ok)
);

const toggleMarketplace = () => {
    toggling.value = true;
    router.patch(
        route('cars.toggle-marketplace', props.car.id),
        { is_marketplace: !props.car.is_marketplace },
        { preserveScroll: true, onFinish: () => (toggling.value = false) }
    );
};
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

        <!-- §marketplace — publicar en la web pública desde la ficha -->
        <div
            class="rounded-2xl border p-4 transition-colors"
            :class="[
                marketplaceVisible
                    ? 'border-emerald-200 bg-emerald-50'
                    : car.is_marketplace
                        ? 'border-amber-200 bg-amber-50'
                        : 'border-gray-200 bg-gray-50',
            ]"
            data-test="marketplace-toggle-card"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                        <GlobeAltIcon class="h-4 w-4 shrink-0 text-estoril-600" />
                        {{ t('cars.publish_marketplace') }}
                        <Badge v-if="marketplaceVisible" variant="estoril" size="sm">🌐 {{ t('cars.marketplace_live') }}</Badge>
                    </p>
                    <p
                        class="mt-0.5 text-xs"
                        :class="marketplaceVisible ? 'text-emerald-800' : car.is_marketplace ? 'text-amber-800' : 'text-gray-600'"
                    >
                        <template v-if="marketplaceVisible">
                            {{ t('cars.marketplace_visible_desc') }}
                        </template>
                        <template v-else-if="car.is_marketplace">
                            {{ t('cars.marketplace_pending_desc') }}
                        </template>
                        <template v-else>
                            {{ t('cars.publish_marketplace_desc') }}
                        </template>
                    </p>
                </div>

                <button
                    type="button"
                    role="switch"
                    :aria-checked="car.is_marketplace"
                    :aria-label="t('cars.publish_marketplace')"
                    :disabled="toggling"
                    @click="toggleMarketplace"
                    :class="car.is_marketplace ? 'bg-estoril-700' : 'bg-gray-300'"
                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-estoril-500 focus:ring-offset-2 disabled:opacity-50"
                    data-test="marketplace-toggle"
                >
                    <span :class="car.is_marketplace ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                </button>
            </div>

            <!-- Condiciones pendientes (solo si está marcado pero no cumple todo) -->
            <ul v-if="car.is_marketplace && !marketplaceVisible" class="mt-3 space-y-1.5" data-test="marketplace-pending">
                <li v-for="check in pendingChecks" :key="check.key" class="flex items-start gap-2 text-xs text-amber-900">
                    <ExclamationTriangleIcon class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600" />
                    <span>
                        <span class="font-semibold">{{ t(`cars.marketplace_check_${check.key}_label`) }}:</span>
                        {{ t(`cars.marketplace_check_${check.key}_hint`) }}
                    </span>
                </li>
            </ul>

            <a v-if="marketplaceVisible"
               :href="route('marketplace.show', car.id)"
               target="_blank"
               rel="noopener noreferrer"
               class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-estoril-700 hover:text-estoril-900"
               data-test="marketplace-public-link">
                <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
                {{ t('cars.marketplace_see_public') }}
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
