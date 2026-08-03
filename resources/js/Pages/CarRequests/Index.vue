<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon,
    CheckCircleIcon, ClockIcon, XCircleIcon, UserIcon, EnvelopeIcon, PhoneIcon, LinkIcon
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    requests: Object,
    stats: Object,
    filters: Object,
});

const page = usePage();
const organization = page.props.auth?.user?.organization;

const search = ref(props.filters?.search || '');
const currentTab = ref(props.filters?.status || 'all');
const showDelete = ref(false);
const requestToDelete = ref(null);

const tabs = computed(() => [
    { id: 'all', label: 'Todas', count: props.stats.total },
    { id: 'pending', label: 'Pendientes', count: props.stats.pending },
    { id: 'contacted', label: 'Contactados', count: props.stats.contacted },
    { id: 'in_progress', label: 'En proceso', count: props.stats.in_progress },
    { id: 'completed', label: 'Completadas', count: props.stats.completed },
]);

const statusVariant = (status) => {
    const map = {
        pending: 'amber',
        contacted: 'blue',
        in_progress: 'indigo',
        completed: 'green',
        cancelled: 'gray',
    };
    return map[status] || 'gray';
};

const statusLabel = (status) => {
    const map = {
        pending: 'Pendiente',
        contacted: 'Contactado',
        in_progress: 'En proceso',
        completed: 'Completada',
        cancelled: 'Cancelada',
    };
    return map[status] || status;
};

const filteredRequests = computed(() => {
    let result = props.requests.data || [];
    if (currentTab.value !== 'all') {
        result = result.filter(r => r.status === currentTab.value);
    }
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(r =>
            (r.name && r.name.toLowerCase().includes(term)) ||
            (r.email && r.email.toLowerCase().includes(term)) ||
            (r.phone && r.phone.includes(term)) ||
            (r.brand && r.brand.toLowerCase().includes(term)) ||
            (r.model && r.model.toLowerCase().includes(term))
        );
    }
    return result;
});

watch([search], () => {
    router.get(
        route('car-requests.index'),
        {
            search: search.value || undefined,
            status: currentTab.value === 'all' ? undefined : currentTab.value,
        },
        { preserveState: true, preserveScroll: true }
    );
});

const askDelete = (request) => {
    requestToDelete.value = request;
    showDelete.value = true;
};

const confirmDelete = () => {
    if (!requestToDelete.value) return;
    router.delete(route('car-requests.destroy', requestToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
            requestToDelete.value = null;
        },
    });
};

const updateStatus = (request, newStatus) => {
    router.patch(route('car-requests.update-status', request.id), {
        status: newStatus,
    }, {
        preserveScroll: true,
    });
};

const copyPublicUrl = () => {
    if (!organization?.slug) return;
    const url = window.location.origin + `/request/${organization.slug}`;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url);
    }
};
</script>

<template>
    <Head title="Solicitudes de coches" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Solicitudes</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Solicitudes de coches" :subtitle="`${stats.total} solicitudes en total`">
                    <template #actions>
                        <button
                            @click="copyPublicUrl"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500"
                        >
                            <LinkIcon class="h-4 w-4" />
                            Copiar enlace público
                        </button>
                    </template>
                </PageHeader>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-amber-700">Pendientes</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600">{{ stats.pending }}</div>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4 shadow-sm ring-1 ring-blue-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-blue-700">Contactados</div>
                        <div class="mt-1 text-2xl font-bold text-blue-600">{{ stats.contacted }}</div>
                    </div>
                    <div class="rounded-xl bg-estoril-50 p-4 shadow-sm ring-1 ring-estoril-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-estoril-700">En proceso</div>
                        <div class="mt-1 text-2xl font-bold text-estoril-600">{{ stats.in_progress }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 shadow-sm ring-1 ring-emerald-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Completadas</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.completed }}</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="p-4">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Buscar</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input v-model="search" type="text" placeholder="Nombre, email, marca..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-estoril-500 focus:ring-estoril-500" />
                        </div>
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
                <div v-if="filteredRequests.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="request in filteredRequests"
                        :key="request.id"
                        class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-gray-300"
                    >
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('car-requests.show', request.id)" class="block">
                                        <h3 class="text-base font-semibold text-gray-900 group-hover:text-estoril-600">
                                            {{ request.name }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            <span v-if="request.brand">{{ request.brand }}</span>
                                            <span v-if="request.model"> {{ request.model }}</span>
                                            <span v-if="!request.brand && !request.model">Sin preferencia de marca</span>
                                        </p>
                                    </Link>
                                </div>
                                <Badge :variant="statusVariant(request.status)" class="shrink-0">{{ statusLabel(request.status) }}</Badge>
                            </div>

                            <div class="mt-4 space-y-2 text-sm">
                                <div v-if="request.email" class="flex items-center gap-2 text-gray-600">
                                    <EnvelopeIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span class="truncate">{{ request.email }}</span>
                                </div>
                                <div v-if="request.phone" class="flex items-center gap-2 text-gray-600">
                                    <PhoneIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span>{{ request.phone }}</span>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                                <div v-if="request.budget_min || request.budget_max">
                                    <span class="text-xs text-gray-500 uppercase tracking-wider">Presupuesto</span>
                                    <p class="text-sm font-semibold text-gray-900">{{ request.budget_range }}</p>
                                </div>
                                <div v-if="request.year_min || request.year_max">
                                    <span class="text-xs text-gray-500 uppercase tracking-wider">Año</span>
                                    <p class="text-sm font-semibold text-gray-900">{{ request.year_range }}</p>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="mt-4 flex gap-2">
                                <button
                                    v-if="request.status === 'pending'"
                                    @click="updateStatus(request, 'contacted')"
                                    class="flex-1 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                >
                                    Marcar contactado
                                </button>
                                <button
                                    v-if="request.status === 'contacted'"
                                    @click="updateStatus(request, 'in_progress')"
                                    class="flex-1 rounded-lg bg-estoril-50 px-3 py-2 text-xs font-semibold text-estoril-700 hover:bg-estoril-100"
                                >
                                    Marcar en proceso
                                </button>
                                <button
                                    v-if="request.status === 'in_progress'"
                                    @click="updateStatus(request, 'completed')"
                                    class="flex-1 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                >
                                    Marcar completada
                                </button>
                            </div>
                        </div>

                        <div class="flex border-t border-gray-100 bg-gray-50 px-5 py-3">
                            <div class="flex-1" />
                            <div class="flex items-center gap-1">
                                <Link :href="route('car-requests.show', request.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600" title="Ver">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <button type="button" @click="askDelete(request)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Eliminar">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState v-else icon="📋" title="No hay solicitudes" description="Cuando alguien envíe una solicitud desde tu formulario público, aparecerá aquí." />
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            title="Eliminar solicitud"
            :message="`¿Estás seguro de que quieres eliminar la solicitud de ${requestToDelete?.name}?`"
            confirm-text="Eliminar"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
