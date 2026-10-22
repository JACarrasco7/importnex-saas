<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChatBubbleLeftEllipsisIcon, ExclamationTriangleIcon, MagnifyingGlassIcon, BanknotesIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, required: true },
});

const { t } = useTranslations();
const { currency, fromNow } = useFormat();

const search = ref(props.filters.search || '');
const tabs = computed(() => [
    { id: 'attention', label: t('orders.index.tab_attention'), count: props.counts.attention },
    { id: 'active', label: t('orders.index.tab_active'), count: props.counts.active },
    { id: 'closed', label: t('orders.index.tab_closed'), count: props.counts.closed },
    { id: 'all', label: t('orders.index.tab_all'), count: props.counts.all },
]);

const visit = (params) => router.get(route('import-orders.index'), { tab: props.filters.tab, search: search.value, ...params }, { preserveState: true, preserveScroll: true, replace: true });

let timer = null;
watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(() => visit({ page: 1 }), 350);
});
</script>

<template>
    <Head :title="t('orders.index.title')" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('orders.index.title')" :subtitle="t('orders.index.subtitle')" />

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <nav class="flex flex-wrap gap-1 rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700" :aria-label="t('orders.index.tabs_aria')">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            :class="['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-semibold transition', filters.tab === tab.id ? 'bg-estoril-700 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-asphalt-700']"
                            :aria-current="filters.tab === tab.id ? 'page' : undefined"
                            @click="visit({ tab: tab.id, page: 1 })"
                        >
                            {{ tab.label }}
                            <span :class="['rounded-full px-1.5 text-xs', filters.tab === tab.id ? 'bg-white/20' : 'bg-gray-100 dark:bg-asphalt-700']">{{ tab.count }}</span>
                        </button>
                    </nav>
                    <div class="relative sm:w-72">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                        <input v-model="search" type="search" :placeholder="t('orders.index.search')" :aria-label="t('orders.index.search')" class="block w-full rounded-lg border-gray-300 py-2 pl-9 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-gray-100" />
                    </div>
                </div>

                <EmptyState v-if="!orders.data.length" :title="t('orders.index.empty_title')" :description="t('orders.index.empty_text')" />

                <ul v-else class="divide-y divide-gray-100 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:divide-asphalt-700 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <li v-for="order in orders.data" :key="order.id">
                        <Link :href="route('import-orders.show', order.id)" class="flex flex-col gap-2 px-5 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-asphalt-700/50">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-estoril-700 dark:text-estoril-300">{{ order.reference }}</span>
                                    <Badge :variant="order.state_variant" size="sm">{{ order.state_label }}</Badge>
                                    <Badge v-if="order.cancellation_requested" variant="danger" size="sm">
                                        <ExclamationTriangleIcon class="h-3 w-3" aria-hidden="true" />{{ t('orders.index.cancel_requested') }}
                                    </Badge>
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ order.client_name || '—' }} · {{ order.vehicle }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('orders.index.updated', { when: fromNow(order.updated_at) }) }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3 text-xs text-gray-600 dark:text-gray-300">
                                <span v-if="order.unread_messages" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-1 font-semibold text-rose-700" :title="t('orders.index.unread')">
                                    <ChatBubbleLeftEllipsisIcon class="h-4 w-4" aria-hidden="true" />{{ order.unread_messages }}
                                </span>
                                <span v-if="order.pending_approvals" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 font-semibold text-amber-700" :title="t('orders.index.pending_approvals')">
                                    <ClipboardDocumentCheckIcon class="h-4 w-4" aria-hidden="true" />{{ order.pending_approvals }}
                                </span>
                                <span v-if="order.pending_payments" class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 font-semibold dark:bg-asphalt-700" :title="t('orders.index.pending_payments')">
                                    <BanknotesIcon class="h-4 w-4" aria-hidden="true" />{{ order.pending_payments }}
                                </span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ currency(order.fee_amount) }}</span>
                            </div>
                        </Link>
                    </li>
                </ul>

                <Pagination :pagination="orders" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
