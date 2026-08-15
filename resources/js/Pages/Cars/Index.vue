<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, Squares2X2Icon, PencilIcon, EyeIcon, TrashIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    cars: Object,
    statuses: Array,
    lights: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const lightFilter = ref(props.filters?.traffic_light || '');
const currentTab = ref(props.filters?.status || 'all');
const showDelete = ref(false);
const carToDelete = ref(null);

const { currency, statusLabel, statusVariant, trafficLightVariant } = useFormat();
const { t } = useTranslations();

const tabs = computed(() => {
    // Order statuses by typical workflow
    const statusOrder = ['Located', 'Valuing', 'Offered', 'Reserved', 'Purchased', 'In_transit', 'Processing', 'Pending review', 'Verifying', 'Delivered', 'Discarded'];
    const ordered = statusOrder.filter(s => props.statuses.includes(s));
    const remaining = props.statuses.filter(s => !ordered.includes(s));
    return [
        { id: 'all', label: 'All', count: props.cars.total },
        ...ordered.map(status => ({
            id: status,
        ...ordered.map(status => ({
            id: status,
            label: t(`cars.status.${status}`),
            count: props.cars.data?.filter(c => c.status === status).length || 0
        })),
        ...remaining.map(status => ({
            id: status,
            label: t(`cars.status.${status}`),
            count: props.cars.data?.filter(c => c.status === status).length || 0
        }))
    ];
});

const stats = computed(() => {
    const cars = props.cars.data || [];
    return {
        green: cars.filter(c => c.traffic_light === 'green').length,
        amber: cars.filter(c => c.traffic_light === 'amber').length,
        red: cars.filter(c => c.traffic_light === 'red').length,
        totalValue: cars.reduce((sum, c) => sum + (c.purchase_price || 0), 0),
    };
});

const filteredCars = computed(() => {
    let result = props.cars.data || [];
    if (currentTab.value !== 'all') {
        result = result.filter(c => c.status === currentTab.value);
    }
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(c =>
            (c.brand && c.brand.toLowerCase().includes(term)) ||
            (c.model && c.model.toLowerCase().includes(term)) ||
            (c.vin && c.vin.toLowerCase().includes(term))
        );
    }
    if (lightFilter.value) {
        result = result.filter(c => c.traffic_light === lightFilter.value);
    }
    return result;
});



watch([search, lightFilter], () => {
    router.get(
        route('cars.index'),
        {
            search: search.value || undefined,
            status: currentTab.value === 'all' ? undefined : currentTab.value,
            traffic_light: lightFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
});

const askDelete = (car) => {
    carToDelete.value = car;
    showDelete.value = true;
};
const confirmDelete = () => {
    if (!carToDelete.value) return;
    router.delete(route('cars.destroy', carToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
            carToDelete.value = null;
        },
    });
};


</script>

<template>
    <Head :title="t('cars.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('cars.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('nav.inventory')" :subtitle="t('app.inventory_count', { count: cars.total || 0 })">
                    <template #actions>
                        <Link :href="route('cars.kanban')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <Squares2X2Icon class="h-4 w-4" />
                            Kanban
                        </Link>
                        <Link :href="route('cars.import-valuation.create')" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            <SparklesIcon class="h-4 w-4" />
                            Subir ZIP
                        </Link>
                        <Link :href="route('cars.create')" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PlusIcon class="h-4 w-4" />
                            {{ t('cars.add') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">{{ t('app.search') }}</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="search" type="text" :placeholder="t('cars.vin') + ', ' + t('cars.make') + '...'" class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-estoril-500 focus:ring-estoril-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Traffic light</label>
                            <select v-model="lightFilter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500">
                                <option value="">{{ t('common.all') }}</option>
                                <option v-for="light in lights" :key="light" :value="light">{{ t('cars.light.' + light) }}</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-2">
                            <p class="text-xs text-gray-500">
                                Upload vehicles via manual form or ZIP package from AI tools.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Value</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ currency(stats.totalValue) }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 shadow-sm ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:ring-emerald-800">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ t('cars.light.green') }}</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.green }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200 dark:bg-amber-900/20 dark:ring-amber-800">
                        <div class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">{{ t('cars.light.amber') }}</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.amber }}</div>
                    </div>
                    <div class="rounded-xl bg-red-50 p-4 shadow-sm ring-1 ring-red-200 dark:bg-red-900/20 dark:ring-red-800">
                        <div class="text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400">{{ t('cars.light.red') }}</div>
                        <div class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.red }}</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-8 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="currentTab = tab.id"
                            :class="[
                                'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-semibold transition-colors',
                                currentTab === tab.id
                                    ? 'border-estoril-600 text-estoril-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ]"
                        >
                            {{ tab.label }}
                            <span v-if="tab.count > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ tab.count }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Cards Grid -->
                <div v-if="filteredCars.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <div
                        v-for="car in filteredCars"
                        :key="car.id"
                        class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-gray-300"
                    >
                        <Link :href="route('cars.show', car.id)" class="block">
                            <div v-if="car.photos && car.photos.length > 0" class="aspect-video overflow-hidden bg-gray-100">
                                <img :src="car.photos[0]" :alt="`${car.brand} ${car.model}`" class="h-full w-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                            </div>
                            <div v-else class="flex aspect-square items-center justify-center bg-gray-100">
                                <span class="text-4xl">🚗</span>
                            </div>
                        </Link>

                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('cars.show', car.id)" class="block">
                                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-estoril-600">
                                            {{ car.brand }} {{ car.model }}
                                        </h3>
                                        <p v-if="car.version" class="mt-0.5 text-xs text-gray-500 truncate">{{ car.version }}</p>
                                    </Link>
                                </div>
                                <Badge :variant="trafficLightVariant(car.traffic_light)" dot class="shrink-0">{{ car.traffic_light }}</Badge>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider text-gray-500">Year</span>
                                    <p class="font-medium text-gray-900">{{ car.year }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider text-gray-500">Mileage</span>
                                    <p class="font-medium text-gray-900">{{ (car.mileage / 1000).toFixed(0) }}k km</p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2 border-t border-gray-100 pt-3">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider text-gray-500">Price</span>
                                    <p class="text-base font-bold text-gray-900">{{ currency(car.purchase_price) }}</p>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-1">
                                    <Badge v-if="car.is_marketplace" variant="estoril" size="sm">🌐 Marketplace</Badge>
                                    <Badge :variant="statusVariant(car.status)" size="sm">{{ car.status }}</Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex border-t border-gray-100 bg-gray-50 px-4 py-2">
                            <div class="flex-1" />
                            <div class="flex items-center gap-1">
                                <Link :href="route('cars.show', car.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600" title="View">
                                    <EyeIcon class="h-3.5 w-3.5" />
                                </Link>
                                <Link :href="route('cars.edit', car.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="Edit">
                                    <PencilIcon class="h-3.5 w-3.5" />
                                </Link>
                                <button type="button" @click="askDelete(car)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                    <TrashIcon class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState v-else icon="🚗" title="No cars found" description="Try adjusting your filters or add your first vehicle to the inventory." action-text="Add your first car" :action-route="route('cars.create')" />
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            title="Delete car"
            :message="`Are you sure you want to delete ${carToDelete?.brand} ${carToDelete?.model}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
