<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, BellIcon, CheckCircleIcon, ClockIcon, EyeIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    alert: Object,
});

const { datetime } = useFormat();

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
</script>

<template>
    <Head :title="t('alerts.details')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('alerts.details') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('alerts.details')" :subtitle="`${typeLabel(alert.alert_type)} · ${referenceLabel(alert.reference_type)} #${alert.reference_id}`">
                    <template #actions>
                        <Link v-if="alert.target_url" :href="alert.target_url" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <EyeIcon class="h-4 w-4" />
                            {{ t('alerts.view_resource') }}
                        </Link>
                        <Link :href="route('alerts.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                    </template>
                </PageHeader>

                <FormSection :title="t('alerts.information')">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50">
                                <BellIcon class="h-5 w-5 text-rose-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('alerts.type') }}</dt>
                                <dd class="mt-1"><Badge variant="red">{{ typeLabel(alert.alert_type) }}</Badge></dd>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', alert.resolved ? 'bg-emerald-50' : 'bg-amber-50']">
                                <component :is="alert.resolved ? CheckCircleIcon : ClockIcon" :class="['h-5 w-5', alert.resolved ? 'text-emerald-600' : 'text-amber-600']" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('alerts.status') }}</dt>
                                <dd class="mt-1"><Badge :variant="alert.resolved ? 'green' : 'amber'">{{ alert.resolved ? t('alerts.resolved') : t('alerts.pending') }}</Badge></dd>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('alerts.message') }}</dt>
                            <dd class="mt-2 rounded-lg bg-gray-50 p-4 text-sm text-gray-900">{{ alert.message }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('alerts.created') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ datetime(alert.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('alerts.reference') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ referenceLabel(alert.reference_type) }} #{{ alert.reference_id }}</dd>
                        </div>
                    </div>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
