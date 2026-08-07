<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BellIcon,
    CheckIcon,
    EyeIcon,
    TrashIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ClockIcon,
    ArrowPathIcon,
    XMarkIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';
import { useToast } from '@/Composables/useToast';

const { t } = useTranslations();
const { date, dateTime } = useFormat();
const toast = useToast();

const typeLabel = (type) => {
    const key = `alerts.alert_types.${type}`;
    const value = t(key);
    return value === key ? type : value;
};

const referenceLabel = (refType) => {
    const key = `alerts.reference_types.${refType}`;
    const value = t(key);
    if (value !== key) return value;
    return (refType || '').split('\\').pop() || refType;
};

const props = defineProps({
    alerts: Object,
    filters: Object,
    types: Array,
    typesAvailable: Object,
    counts: Object,
    allAlertTypes: Array,
    disabledAlertTypes: Array,
});

const groupByType = ref(false);
const collapsedGroups = ref(new Set());

const validFilters = ['pending', 'snoozed', 'resolved', 'all'];
const filter = ref(validFilters.includes(props.filters?.filter) ? props.filters.filter : 'pending');
const typeFilter = ref(props.filters?.type || '');

// Tipos silenciados via /organization/edit > Notifications (N8)
const disabledSet = computed(() => new Set(props.disabledAlertTypes || []));
const isMuted = (type) => disabledSet.value.has(type);
const allTypes = computed(() => props.allAlertTypes || []);

const togglePreference = (type) => {
    const currentlyEnabled = !isMuted(type);
    router.post(route('alerts.toggle-preference', type), { enabled: !currentlyEnabled }, { preserveScroll: true });
};

watch([filter, typeFilter], () => {
    router.get(
        route('alerts.index'),
        { filter: filter.value, type: typeFilter.value || undefined },
        { preserveState: true, preserveScroll: true },
    );
});

const alertToAct = ref(null);
const showResolve = ref(false);
const showDelete = ref(false);
const showSnooze = ref(false);

const askResolve = (alert) => { alertToAct.value = alert; showResolve.value = true; };
const confirmResolve = () => {
    if (!alertToAct.value) return;
    router.patch(route('alerts.mark-resolved', alertToAct.value.id), {}, {
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

const askSnooze = (alert) => { alertToAct.value = alert; showSnooze.value = true; };
const confirmSnooze = (hours) => {
    if (!alertToAct.value) return;
    router.post(route('alerts.snooze', alertToAct.value.id), { hours }, {
        preserveScroll: true,
        onSuccess: () => {
            showSnooze.value = false;
            alertToAct.value = null;
            toast.success(t('alerts.snoozed_until', { time: hours + 'h' }));
        },
    });
};

const unsnooze = (alert) => {
    router.delete(route('alerts.unsnooze', alert.id), { preserveScroll: true });
};

const retryVerification = (alert) => {
    if (!alert.reference_id) return;
    router.post(route('cars.verify-sync', alert.reference_id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Verification reintented'),
    });
};

const markAllRead = () => {
    router.post(route('alerts.mark-all-read'), {}, { preserveScroll: true });
};

const toggleGroup = (type) => {
    if (collapsedGroups.value.has(type)) {
        collapsedGroups.value.delete(type);
    } else {
        collapsedGroups.value.add(type);
    }
};

const groupedAlerts = computed(() => {
    if (!groupByType.value) return null;
    const groups = {};
    for (const alert of props.alerts.data || []) {
        const key = alert.alert_type || 'unknown';
        if (!groups[key]) groups[key] = [];
        groups[key].push(alert);
    }
    return groups;
});

const hasTypeFilter = computed(() => !!typeFilter.value);
const clearTypeFilter = () => { typeFilter.value = ''; };

const inlineActions = (alert) => {
    const actions = [];
    if (alert.alert_type === 'verification_failed' && alert.reference_id) {
        actions.push({
            key: 'retry',
            label: t('alerts.action_retry'),
            icon: ArrowPathIcon,
            color: 'text-amber-600 hover:bg-amber-50',
            handler: () => retryVerification(alert),
        });
    }
    if (alert.target_url) {
        actions.push({
            key: 'view',
            label: t('alerts.action_view'),
            icon: EyeIcon,
            color: 'text-estoril-600 hover:bg-estoril-50',
            href: alert.target_url,
        });
    }
    return actions;
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
                <PageHeader :title="t('alerts.title')" :subtitle="`${alerts.total || 0} ${filter === 'pending' ? t('alerts.subtitle_pending') : t('alerts.subtitle_all')}`">
                    <template #actions>
                        <button
                            v-if="alerts.total > 0 && filter === 'pending'"
                            @click="markAllRead"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-700"
                        >
                            <CheckIcon class="h-4 w-4" />
                            {{ t('alerts.mark_all_read') }}
                        </button>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700">
                            <input v-model="groupByType" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-estoril-600 focus:ring-estoril-500" />
                            {{ t('alerts.group_by_type') }}
                        </label>
                        <div class="inline-flex rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <button @click="filter = 'pending'" :class="['rounded-md px-3 py-1.5 text-sm font-semibold transition', filter === 'pending' ? 'bg-estoril-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700']">
                                {{ t('alerts.filter_pending') }}
                                <span v-if="counts?.pending" class="ml-1 text-xs opacity-75">({{ counts.pending }})</span>
                            </button>
                            <button @click="filter = 'snoozed'" :class="['rounded-md px-3 py-1.5 text-sm font-semibold transition', filter === 'snoozed' ? 'bg-amber-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700']">
                                <ClockIcon class="inline h-3.5 w-3.5 -mt-0.5" />
                                <span v-if="counts?.snoozed" class="ml-1 text-xs opacity-75">({{ counts.snoozed }})</span>
                            </button>
                            <button @click="filter = 'all'" :class="['rounded-md px-3 py-1.5 text-sm font-semibold transition', filter === 'all' ? 'bg-estoril-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700']">
                                {{ t('alerts.filter_all') }}
                            </button>
                        </div>
                    </template>
                </PageHeader>

                <div v-if="typesAvailable && Object.keys(typesAvailable).length > 0" class="flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <FunnelIcon class="h-4 w-4 text-gray-400" />
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ t('alerts.filter_by_type') }}:</span>
                    <button
                        @click="clearTypeFilter"
                        :class="['inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold transition',
                            !hasTypeFilter ? 'bg-estoril-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600']"
                    >
                        <XMarkIcon v-if="hasTypeFilter" class="h-3 w-3" />
                        {{ t('alerts.filter_clear') }}
                    </button>
                    <button
                        v-for="(count, type) in typesAvailable"
                        :key="type"
                        @click="typeFilter = typeFilter === type ? '' : type"
                        :class="['inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold transition',
                            typeFilter === type ? 'bg-estoril-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600']"
                    >
                        {{ typeLabel(type) }}
                        <span class="ml-1 rounded-full bg-white/20 px-1.5 text-[10px] font-bold">{{ count }}</span>
                    </button>
                </div>

                <!-- Tipos silenciados (N8):列出 los muted + botón reactivar -->
                <div v-if="allTypes.some(t => isMuted(t))" class="flex flex-wrap items-center gap-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900/40">
                    <BellIcon class="h-4 w-4 text-gray-400" />
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ t('alerts.muted_types', { default: 'Silenciados' }) }}:</span>
                    <button
                        v-for="type in allTypes.filter(t => isMuted(t))"
                        :key="type"
                        @click="togglePreference(type)"
                        class="inline-flex items-center gap-1 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-600 line-through hover:bg-estoril-100 hover:text-estoril-700 hover:no-underline dark:bg-gray-700 dark:text-gray-400"
                        :title="t('alerts.unmute_type', { default: 'Reactivar este tipo' })"
                    >
                        {{ typeLabel(type) }}
                        <span class="ml-1 text-[10px] no-underline">+</span>
                    </button>
                </div>

                <div v-if="filter === 'pending' && allTypes.length" class="flex flex-wrap items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs text-gray-500 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <span>{{ t('alerts.mute_type_help', { default: 'Silencia un tipo activo:' }) }}</span>
                    <button
                        v-for="type in allTypes.filter(t => !isMuted(t))"
                        :key="type"
                        @click="togglePreference(type)"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-gray-500 hover:bg-rose-100 hover:text-rose-700 dark:hover:bg-rose-900/30"
                        :title="t('alerts.mute_type', { default: 'Silenciar este tipo' })"
                    >
                        {{ typeLabel(type) }}
                        <span class="text-[10px]">🔕</span>
                    </button>
                </div>

                <template v-if="groupByType && groupedAlerts">
                    <div v-for="(groupAlerts, groupType) in groupedAlerts" :key="groupType" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <button @click="toggleGroup(groupType)" class="flex w-full items-center justify-between border-b border-gray-200 px-6 py-3 text-left hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <div class="flex items-center gap-3">
                                <ChevronDownIcon v-if="!collapsedGroups.has(groupType)" class="h-4 w-4 text-gray-400" />
                                <ChevronUpIcon v-else class="h-4 w-4 text-gray-400" />
                                <Badge variant="blue">{{ typeLabel(groupType) }}</Badge>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ groupAlerts.length }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ collapsedGroups.has(groupType) ? t('alerts.group_expand') : t('alerts.group_collapse') }}</span>
                        </button>
                        <div v-if="!collapsedGroups.has(groupType)" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="alert in groupAlerts" :key="alert.id" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-900/30">
                                    <BellIcon class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ referenceLabel(alert.reference_type) }} #{{ alert.reference_id }}</span>
                                        <span class="text-sm text-gray-500">— {{ alert.message }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400">{{ date(alert.created_at) }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button v-for="act in inlineActions(alert)" :key="act.key"
                                        @click="act.handler && act.handler()"
                                        :class="['rounded-md p-1.5', act.color]"
                                        :title="act.label">
                                        <component :is="act.icon" class="h-4 w-4" />
                                    </button>
                                    <button @click="askResolve(alert)" class="rounded-md bg-emerald-50 p-1.5 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50" title="Resolve">
                                        <CheckIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <div v-if="alerts.data.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="alert in alerts.data" :key="alert.id" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-900/30">
                                <BellIcon class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge variant="red">{{ typeLabel(alert.alert_type) }}</Badge>
                                    <span class="text-sm text-gray-500">#{{ alert.reference_id }}</span>
                                    <span v-if="alert.snoozed_until && new Date(alert.snoozed_until) > new Date()" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        <ClockIcon class="h-3 w-3" />
                                        {{ t('alerts.snoozed_until', { time: dateTime(alert.snoozed_until) }) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ alert.message }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ date(alert.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button v-for="act in inlineActions(alert)" :key="act.key"
                                    @click="act.handler && act.handler()"
                                    :class="['rounded-md p-1.5', act.color]"
                                    :title="act.label">
                                    <component :is="act.icon" class="h-4 w-4" />
                                </button>
                                <button v-if="alert.snoozed_until && new Date(alert.snoozed_until) > new Date()" @click="unsnooze(alert)" class="rounded-md p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30" :title="t('alerts.unsnooze')">
                                    <ClockIcon class="h-4 w-4" />
                                </button>
                                <button v-else @click="askSnooze(alert)" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" :title="t('alerts.snooze')">
                                    <ClockIcon class="h-4 w-4" />
                                </button>
                                <button v-if="!alert.resolved" @click="askResolve(alert)" class="rounded-md bg-emerald-50 p-1.5 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50" title="Resolve">
                                    <CheckIcon class="h-4 w-4" />
                                </button>
                                <Link v-if="alert.target_url" :href="alert.target_url" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600 dark:hover:bg-estoril-900/30" :title="t('alerts.view_resource')">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <button @click="askDelete(alert)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30" title="Delete">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else icon="🔔" :title="t('alerts.empty_title')" :description="t('alerts.empty_description')" />

                    <div v-if="alerts.links && alerts.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ t('common.showing') }} <span class="font-semibold">{{ alerts.from }}</span> {{ t('common.to') }} <span class="font-semibold">{{ alerts.to }}</span> {{ t('common.of') }} <span class="font-semibold">{{ alerts.total }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <component v-for="link in alerts.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
                                'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md px-2 text-sm',
                                link.active ? 'bg-estoril-600 text-white' : 'text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700',
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
            title="Resolve alert"
            :message="`Mark this alert as resolved? It will be archived from the pending list.`"
            confirm-text="Mark resolved"
            variant="info"
            @close="showResolve = false"
            @confirm="confirmResolve"
        />

        <ConfirmDialog
            :show="showDelete"
            title="Delete alert"
            message="This alert will be permanently removed."
            confirm-text="Delete"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />

        <div v-if="showSnooze" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showSnooze = false">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t('alerts.snooze_options.label') }}</h3>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button v-for="(label, hours) in { '1': t('alerts.snooze_options.1h'), '3': t('alerts.snooze_options.3h'), '24': t('alerts.snooze_options.24h'), '72': t('alerts.snooze_options.3d'), '168': t('alerts.snooze_options.7d') }" :key="hours" @click="confirmSnooze(Number(hours))" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-estoril-100 hover:text-estoril-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-estoril-900/30 dark:hover:text-estoril-300">
                        {{ label }}
                    </button>
                </div>
                <button @click="showSnooze = false" class="mt-4 w-full rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700">
                    Cancelar
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
