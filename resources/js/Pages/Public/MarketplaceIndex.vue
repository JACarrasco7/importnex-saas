<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, EyeIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    cars: Object,
    verdicts: Array,
    lights: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const verdictFilter = ref(props.filters?.verdict || '');
const lightFilter = ref(props.filters?.traffic_light || '');
const minPrice = ref(props.filters?.min_price || '');
const maxPrice = ref(props.filters?.max_price || '');
const yearMin = ref(props.filters?.year_min || '');
const yearMax = ref(props.filters?.year_max || '');

const { currency, trafficLightVariant, verdictVariant } = useFormat();

const tabs = computed(() => {
    // Order verdicts by preference
    const verdictOrder = ['Buy', 'Buy if price drops', 'Doubtful', 'Discard'];
    const ordered = verdictOrder.filter(v => props.verdicts.includes(v));
    const remaining = props.verdicts.filter(v => !ordered.includes(v));
    return [
        { id: 'all', label: 'All', count: props.cars.total },
        ...ordered.map(verdict => ({
            id: verdict,
            label: verdict,
            count: props.cars.data?.filter(c => c.verdict === verdict).length || 0
        })),
        ...remaining.map(verdict => ({
            id: verdict,
            label: verdict,
            count: props.cars.data?.filter(c => c.verdict === verdict).length || 0
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
    if (verdictFilter.value && verdictFilter.value !== 'all') {
        result = result.filter(c => c.verdict === verdictFilter.value);
    }
    if (lightFilter.value && lightFilter.value !== '') {
        result = result.filter(c => c.traffic_light === lightFilter.value);
    }
    if (minPrice.value) {
        const min = parseFloat(minPrice.value);
        if (!isNaN(min)) {
            result = result.filter(c => (c.purchase_price || 0) >= min);
        }
    }
    if (maxPrice.value) {
        const max = parseFloat(maxPrice.value);
        if (!isNaN(max)) {
            result = result.filter(c => (c.purchase_price || 0) <= max);
        }
    }
    if (yearMin.value) {
        const ymin = parseInt(yearMin.value);
        if (!isNaN(ymin)) {
            result = result.filter(c => {
                const year = parseInt(c.year?.slice(-4) || 0);
                return !isNaN(year) && year >= ymin;
            });
        }
    }
    if (yearMax.value) {
        const ymax = parseInt(yearMax.value);
        if (!isNaN(ymax)) {
            result = result.filter(c => {
                const year = parseInt(c.year?.slice(-4) || 0);
                return !isNaN(year) && year <= ymax;
            });
        }
    }
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(c =>
            (c.brand && c.brand.toLowerCase().includes(term)) ||
            (c.model && c.model.toLowerCase().includes(term)) ||
            (c.vin && c.vin.toLowerCase().includes(term))
        );
    }
    return result;
});
</script>

<template>
    <Head title="Marketplace — Verified Cars" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50">
        <!-- Public header -->
        <header class="border-b border-gray-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link :href="route('marketplace.index')" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600">
                        <ShieldCheckIcon class="h-5 w-5 text-white" />
                    </span>
                    <span class="text-lg font-bold text-gray-900">Verified Cars</span>
                </Link>
                <a :href="route('login')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Sign in</a>
            </div>
        </header>

        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Verified Cars Marketplace</h1>
                    <p class="mt-2 text-base text-gray-600">{{ cars.total || 0 }} cars investigated, valued and ready to buy.</p>
                </div>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="search" type="text" placeholder="Brand, model, VIN..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Verdict</label>
                            <select v-model="verdictFilter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="all">All verdicts</option>
                                <option v-for="verdict in verdicts" :key="verdict" :value="verdict">{{ verdict }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Traffic light</label>
                            <select v-model="lightFilter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All lights</option>
                                <option v-for="light in lights" :key="light" :value="light">{{ light }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Min price (€)</label>
                            <input v-model.number="minPrice" type="number" min="0" step="500" placeholder="Min" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Max price (€)</label>
                            <input v-model.number="maxPrice" type="number" min="0" step="500" placeholder="Max" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Year</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input v-model.number="yearMin" type="number" min="1990" max="2027" placeholder="From" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <input v-model.number="yearMax" type="number" min="1990" max="2027" placeholder="To" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Value</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ currency(stats.totalValue) }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 shadow-sm ring-1 ring-emerald-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Green Light</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.green }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-amber-700">Amber Light</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600">{{ stats.amber }}</div>
                    </div>
                    <div class="rounded-xl bg-red-50 p-4 shadow-sm ring-1 ring-red-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-red-700">Red Light</div>
                        <div class="mt-1 text-2xl font-bold text-red-600">{{ stats.red }}</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-8 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="verdictFilter = tab.id"
                            :class="[
                                'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-semibold transition-colors',
                                verdictFilter === tab.id
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ]"
                        >
                            {{ tab.label }}
                            <span v-if="tab.count > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ tab.count }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Cars Grid -->
                <div v-if="filteredCars.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <div
                        v-for="car in filteredCars"
                        :key="car.id"
                        class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-gray-300"
                    >
                        <Link :href="route('marketplace.show', car.id)" class="block">
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
                                    <Link :href="route('marketplace.show', car.id)" class="block">
                                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">
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

                            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider text-gray-500">Price</span>
                                    <p class="text-base font-bold text-gray-900">{{ currency(car.purchase_price) }}</p>
                                </div>
                                <Badge :variant="verdictVariant(car.verdict)" size="sm">{{ car.verdict }}</Badge>
                            </div>
                        </div>

                        <div class="flex border-t border-gray-100 bg-gray-50 px-4 py-2">
                            <div class="flex-1" />
                            <div class="flex items-center gap-1">
                                <Link :href="route('marketplace.show', car.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600" title="View">
                                    <EyeIcon class="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState v-else icon="🚗" title="No cars found" description="Try adjusting your filters or check back later for new additions." />
            </div>
        </div>

        <!-- Public footer -->
        <footer class="mt-12 border-t border-gray-200 bg-white py-6">
            <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
                &copy; {{ new Date().getFullYear() }} Importnex. All rights reserved.
            </div>
        </footer>
    </div>
</template>