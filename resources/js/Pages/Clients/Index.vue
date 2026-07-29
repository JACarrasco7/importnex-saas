<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon, ChevronLeftIcon, ChevronRightIcon, EnvelopeIcon, PhoneIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    clients: Object,
    statuses: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const showDelete = ref(false);
const clientToDelete = ref(null);

const { currency, statusVariant } = useFormat();

watch([search, statusFilter], () => {
    router.get(
        route('clients.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
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
    <Head title="Clients" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Clients</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Clients" :subtitle="`${clients.total || 0} in your CRM`">
                    <template #actions>
                        <Link :href="route('clients.create')" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="h-4 w-4" />
                            New Client
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="search" type="text" placeholder="Name, contact..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Status</label>
                            <select v-model="statusFilter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All statuses</option>
                                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div v-if="clients.data?.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Looking for</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Budget</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <Link :href="route('clients.show', client.id)" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ client.name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4 text-sm text-gray-600">
                                            <span v-if="client.email" class="inline-flex items-center gap-1">
                                                <EnvelopeIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ client.email }}
                                            </span>
                                            <span v-if="client.phone" class="inline-flex items-center gap-1">
                                                <PhoneIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ client.phone }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ client.looking_for || '—' }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                        <span v-if="client.budget_min || client.budget_max">
                                            {{ currency(client.budget_min) }} – {{ currency(client.budget_max) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="statusVariant(client.status)">{{ client.status }}</Badge>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
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
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else icon="👥" title="No clients yet" description="Start building your customer base by adding your first client." action-text="Add your first client" :action-route="route('clients.create')" />

                    <!-- Pagination -->
                    <div v-if="clients.links && clients.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-3">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-semibold">{{ clients.from }}</span> to <span class="font-semibold">{{ clients.to }}</span> of <span class="font-semibold">{{ clients.total }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <component v-for="link in clients.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
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
            title="Delete client"
            :message="`Are you sure you want to delete ${clientToDelete?.name}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
