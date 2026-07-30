<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, Squares2X2Icon, ArrowUpTrayIcon, PencilIcon, EyeIcon, TrashIcon, ChevronLeftIcon, ChevronRightIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    cars: Object,
    statuses: Array,
    lights: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const lightFilter = ref(props.filters?.traffic_light || '');
const showDelete = ref(false);
const carToDelete = ref(null);

const { currency, statusVariant, trafficLightVariant } = useFormat();

const importForm = useForm({ file: null });
const importFile = (event) => {
    importForm.file = event.target.files[0];
};
const submitImport = () => {
    if (!importForm.file) return;
    importForm.post(route('cars.import'), {
        forceFormData: true,
        onSuccess: () => importForm.reset('file'),
    });
};

watch([search, statusFilter, lightFilter], () => {
    router.get(
        route('cars.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
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
    <Head title="Cars" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Cars</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Inventory" :subtitle="`${cars.total || 0} cars in your fleet`">
                    <template #actions>
                        <Link :href="route('cars.kanban')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <Squares2X2Icon class="h-4 w-4" />
                            Kanban
                        </Link>
                        <Link :href="route('cars.import-valuation.create')" class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500">
                            <SparklesIcon class="h-4 w-4" />
                            Importar informe
                        </Link>
                        <Link :href="route('cars.create')" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="h-4 w-4" />
                            New Car
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="search" type="text" placeholder="Brand, model, VIN..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Status</label>
                            <select v-model="statusFilter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All statuses</option>
                                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Traffic light</label>
                            <select v-model="lightFilter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All lights</option>
                                <option v-for="light in lights" :key="light" :value="light">{{ light }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Import</label>
                            <form @submit.prevent="submitImport" class="flex gap-2">
                                <label class="flex-1 cursor-pointer">
                                    <span class="sr-only">Choose file</span>
                                    <input type="file" accept=".csv,.xlsx,.xls" @change="importFile" class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" />
                                </label>
                                <button v-if="importForm.file" type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                                    <ArrowUpTrayIcon class="h-3 w-3" />
                                    Upload
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div v-if="cars.data?.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Year</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Mileage</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Health</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="car in cars.data" :key="car.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <Link :href="route('cars.show', car.id)" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ car.brand }} {{ car.model }}
                                        </Link>
                                        <div v-if="car.version" class="text-xs text-gray-500">{{ car.version }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ car.year }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-500">{{ car.mileage?.toLocaleString() }} km</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ currency(car.purchase_price) }}</td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="statusVariant(car.status)">{{ car.status }}</Badge>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <Link :href="route('cars.show', car.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600" title="View">
                                                <EyeIcon class="h-4 w-4" />
                                            </Link>
                                            <Link :href="route('cars.edit', car.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="Edit">
                                                <PencilIcon class="h-4 w-4" />
                                            </Link>
                                            <button type="button" @click="askDelete(car)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else icon="🚗" title="No cars registered yet" description="Start by adding your first vehicle to the inventory." action-text="Add your first car" :action-route="route('cars.create')" />

                    <!-- Pagination -->
                    <div v-if="cars.links && cars.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-3">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-semibold">{{ cars.from }}</span> to <span class="font-semibold">{{ cars.to }}</span> of <span class="font-semibold">{{ cars.total }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <component v-for="link in cars.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
                                'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md px-2 text-sm',
                                link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-white',
                                !link.url ? 'cursor-not-allowed opacity-50' : '',
                            ]">
                                <ChevronLeftIcon v-if="link.label.includes('Previous')" class="h-4 w-4" />
                                <ChevronRightIcon v-else-if="link.label.includes('Next')" class="h-4 w-4" />
                                <span v-else v-html="link.label"></span>
                            </component>
                        </div>
                    </div>
                </div>
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
