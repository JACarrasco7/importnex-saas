<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BellIcon, CheckIcon, EyeIcon, TrashIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const { t } = useTranslations();
const { date } = useFormat();

const props = defineProps({
    alerts: Object,
    filters: Object,
});

const filter = ref(props.filters?.filter || 'pending');

watch(filter, () => {
    router.get(route('alerts.index'), { filter: filter.value }, { preserveState: true, preserveScroll: true });
});

const askResolve = (alert) => { alertToAct.value = alert; showResolve.value = true; };
const confirmResolve = () => {
    if (!alertToAct.value) return;
    router.post(route('alerts.mark-resolved', alertToAct.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { showResolve.value = false; alertToAct.value = null; },
    });
};

const askDelete = (alert) => { alertToAct.value = alert; showDelete.value = true; };
const confirmDelete = () => {
    if (!alertToAct.value) return;
    router.delete(route('alerts.destroy', alertToAct.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDelete.value = false; alertToAct.value = null; },
    });
};
</script>

<template>
    <Head :title="t('alerts.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('alerts.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('alerts.title')" :subtitle="t('alerts.subtitle_count', { count: alerts.total || 0 })">
                    <template #actions>
                        <div class="inline-flex rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
                            <button @click="filter = 'pending'" :class="['rounded-md px-3 py-1.5 text-sm font-semibold transition', filter === 'pending' ? 'bg-estoril-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50']">{{ t('common.pending') }}</button>
                            <button @click="filter = 'all'" :class="['rounded-md px-3 py-1.5 text-sm font-semibold transition', filter === 'all' ? 'bg-estoril-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50']">{{ t('common.all') }}</button>
                        </div>
                    </template>
                </PageHeader>

                <!-- List -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div v-if="alerts.data.length > 0" class="divide-y divide-gray-200">
                        <div v-for="alert in alerts.data" :key="alert.id" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-rose-50">
                                <BellIcon class="h-5 w-5 text-rose-600" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <Badge variant="red">{{ alert.alert_type }}</Badge>
                                    <span class="text-sm text-gray-500">{{ alert.reference_type }} #{{ alert.reference_id }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-900">{{ alert.message }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ date(alert.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button v-if="!alert.resolved" @click="askResolve(alert)" class="rounded-md bg-emerald-50 p-1.5 text-emerald-600 hover:bg-emerald-100" :title="t('alerts.resolve')">
                                    <CheckIcon class="h-4 w-4" />
                                </button>
                                <Link :href="route('alerts.show', alert.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600" :title="t('common.view')">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <button @click="askDelete(alert)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" :title="t('common.delete')">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else icon="🔔" :title="t('alerts.all_clear')" :description="t('alerts.all_clear_desc')" />

                    <!-- Pagination -->
                    <div v-if="alerts.links && alerts.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-3">
                        <div class="text-sm text-gray-700">
                            {{ t('common.showing') }} <span class="font-semibold">{{ alerts.from }}</span> {{ t('common.to') }} <span class="font-semibold">{{ alerts.to }}</span> {{ t('common.of') }} <span class="font-semibold">{{ alerts.total }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <component v-for="link in alerts.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
                                'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md px-2 text-sm',
                                link.active ? 'bg-estoril-600 text-white' : 'text-gray-700 hover:bg-white',
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
            :show="showResolve"
            :title="t('alerts.resolve_alert')"
            :message="t('alerts.resolve_alert_msg')"
            :confirm-text="t('alerts.mark_resolved')"
            variant="info"
            @close="showResolve = false"
            @confirm="confirmResolve"
        />

        <ConfirmDialog
            :show="showDelete"
            :title="t('alerts.delete_alert')"
            :message="t('alerts.delete_alert_msg')"
            :confirm-text="t('common.delete')"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
