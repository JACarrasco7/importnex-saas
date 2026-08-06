<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, WhenVisible } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon, UserIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import Skeleton from '@/Components/Skeleton.vue';
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
                        <Link :href="route('clients.create')" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PlusIcon class="h-4 w-4" />
                            {{ t('clients.add') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <div class="p-4">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">{{ t('cars.search') }}</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input v-model="search" type="text" :placeholder="t('cars.filter_clients_placeholder')" class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-700 dark:text-white dark:placeholder-gray-500" />
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ t('cars.total_label') }}</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                    </div>
                    <div class="rounded-xl bg-estoril-50 p-4 shadow-sm ring-1 ring-estoril-200 dark:bg-estoril-900/20 dark:ring-estoril-800">
                        <div class="text-xs font-semibold uppercase tracking-wider text-estoril-700 dark:text-estoril-400">{{ t('cars.active_label') }}</div>
                        <div class="mt-1 text-2xl font-bold text-estoril-600 dark:text-estoril-400">{{ stats.active }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200 dark:bg-amber-900/20 dark:ring-amber-800">
                        <div class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">{{ t('cars.leads') }}</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.leads }}</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-asphalt-700">
                    <nav class="-mb-px flex gap-8 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="currentTab = tab.id"
                            :class="[
                                'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-semibold transition-colors',
                                currentTab === tab.id
                                    ? 'border-estoril-600 text-estoril-600 dark:text-estoril-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                            ]"
                        >
                            {{ tab.label }}
                            <span v-if="tab.count > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-asphalt-700 dark:text-gray-300">{{ tab.count }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Cards Grid -->
                <WhenVisible data="clients">
                    <template #fallback>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Skeleton v-for="i in 6" :key="i" class="h-40" />
                        </div>
                    </template>

                <div v-if="props.clients && filteredClients.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="client in filteredClients"
                        :key="client.id"
                        class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-gray-300 dark:bg-asphalt-800 dark:ring-asphalt-700 dark:hover:ring-asphalt-600"
                    >
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('clients.show', client.id)" class="block">
                                        <h3 class="text-base font-semibold text-gray-900 group-hover:text-estoril-600">
                                            {{ client.name }}
                                        </h3>
                                        <p v-if="client.looking_for" class="mt-1 text-sm text-gray-500 truncate">{{ client.looking_for }}</p>
                                    </Link>
                                </div>
                                <Badge :variant="statusVariant(client.status)" class="shrink-0">{{ statusLabel(t, client.status) }}</Badge>
                            </div>

                            <div class="mt-4 space-y-2 text-sm">
                                <div v-if="client.contact_info" class="flex items-center gap-2 text-gray-600">
                                    <UserIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span class="truncate">{{ client.contact_info }}</span>
                                </div>
                            </div>

                            <div v-if="client.budget_min || client.budget_max" class="mt-4 border-t border-gray-100 pt-3">
                                <span class="text-xs text-gray-500 uppercase tracking-wider">Presupuesto</span>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ currency(client.budget_min) }} – {{ currency(client.budget_max) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex border-t border-gray-100 bg-gray-50 px-5 py-3">
                            <div class="flex-1" />
                            <div class="flex items-center gap-1">
                                <Link :href="route('clients.show', client.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600" :title="t('common.view')">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link :href="route('clients.edit', client.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" :title="t('cars.edit')">
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button type="button" @click="askDelete(client)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" :title="t('cars.remove')">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                    <EmptyState
                        v-else-if="props.clients"
                        icon="👥"
                        :title="t('cars.no_clients_found')"
                        :description="t('cars.no_clients_found_desc')"
                        :primary-action="{ text: t('cars.add_first_client'), route: route('clients.create') }"
                        :secondary-action="{ text: t('cars.view_marketplace', 'Ver Marketplace'), route: route('marketplace.index') }"
                    />
                </WhenVisible>
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            :title="t('cars.delete_client')"
            :message="t('cars.confirm_delete_client', { name: clientToDelete?.name })"
            :confirm-text="t('cars.remove')"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
