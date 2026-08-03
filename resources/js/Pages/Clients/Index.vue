<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon, UserIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    clients: Object,
    statuses: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const currentTab = ref(props.filters?.status || 'all');
const showDelete = ref(false);
const clientToDelete = ref(null);

const { currency, statusVariant } = useFormat();
const { t } = useTranslations();

const tabs = computed(() => [
    { id: 'all', label: 'All', count: props.clients.total },
    ...props.statuses.map(status => ({
        id: status,
        label: status,
        count: props.clients.data?.filter(c => c.status === status).length || 0
    }))
]);

const filteredClients = computed(() => {
    let result = props.clients.data || [];
    if (currentTab.value !== 'all') {
        result = result.filter(c => c.status === currentTab.value);
    }
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(c =>
            (c.name && c.name.toLowerCase().includes(term)) ||
            (c.contact_info && c.contact_info.toLowerCase().includes(term))
        );
    }
    return result;
});

const stats = computed(() => {
    const clients = props.clients.data || [];
    return {
        total: clients.length,
        active: clients.filter(c => c.status === 'active').length,
        leads: clients.filter(c => c.status === 'lead').length,
    };
});

watch([search], () => {
    router.get(
        route('clients.index'),
        {
            search: search.value || undefined,
            status: currentTab.value === 'all' ? undefined : currentTab.value,
        },
        { preserveState: true, preserveScroll: true }
    );
});

const askDelete = (client) => {
    clientToDelete.value = client;
    showDelete.value = true;
};

const confirmDelete = () => {
    if (!clientToDelete.value) return;
    router.delete(route('clients.destroy', clientToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
            clientToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="t('clients.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('clients.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('clients.title')" :subtitle="t('clients.subtitle', { count: clients.total || 0 })">
                    <template #actions>
                        <Link :href="route('clients.create')" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="h-4 w-4" />
                            {{ t('clients.add') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="p-4">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input v-model="search" type="text" placeholder="Name, contact info..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-4 shadow-sm ring-1 ring-indigo-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Active</div>
                        <div class="mt-1 text-2xl font-bold text-indigo-600">{{ stats.active }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-amber-700">Leads</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600">{{ stats.leads }}</div>
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
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ]"
                        >
                            {{ tab.label }}
                            <span v-if="tab.count > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ tab.count }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Cards Grid -->
                <div v-if="filteredClients.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="client in filteredClients"
                        :key="client.id"
                        class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-gray-300"
                    >
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('clients.show', client.id)" class="block">
                                        <h3 class="text-base font-semibold text-gray-900 group-hover:text-indigo-600">
                                            {{ client.name }}
                                        </h3>
                                        <p v-if="client.looking_for" class="mt-1 text-sm text-gray-500 truncate">{{ client.looking_for }}</p>
                                    </Link>
                                </div>
                                <Badge :variant="statusVariant(client.status)" class="shrink-0">{{ client.status }}</Badge>
                            </div>

                            <div class="mt-4 space-y-2 text-sm">
                                <div v-if="client.contact_info" class="flex items-center gap-2 text-gray-600">
                                    <UserIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span class="truncate">{{ client.contact_info }}</span>
                                </div>
                            </div>

                            <div v-if="client.budget_min || client.budget_max" class="mt-4 border-t border-gray-100 pt-3">
                                <span class="text-xs text-gray-500 uppercase tracking-wider">Budget</span>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ currency(client.budget_min) }} – {{ currency(client.budget_max) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex border-t border-gray-100 bg-gray-50 px-5 py-3">
                            <div class="flex-1" />
                            <div class="flex items-center gap-1">
                                <Link :href="route('clients.show', client.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600" title="View">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link :href="route('clients.edit', client.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="Edit">
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button type="button" @click="askDelete(client)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState v-else icon="👥" title="No clients found" description="Try adjusting your filters or add your first client to the CRM." action-text="Add your first client" :action-route="route('clients.create')" />
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            title="Delete client"
            :message="`Are you sure you want to delete ${clientToDelete?.name}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
