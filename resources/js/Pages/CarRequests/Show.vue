<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import {
    UserIcon, EnvelopeIcon, PhoneIcon, CalendarIcon, TruckIcon,
    CheckCircleIcon, ClockIcon, XCircleIcon, ArrowLeftIcon
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t, locale } = useTranslations();

const props = defineProps({
    carRequest: Object,
});

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
        pending: t('car_requests.status.pending'),
        contacted: t('car_requests.status.contacted'),
        in_progress: t('car_requests.status.in_progress'),
        completed: t('car_requests.status.completed'),
        cancelled: t('car_requests.status.cancelled'),
    };
    return map[status] || status;
};

const updateStatus = (newStatus) => {
    router.patch(route('car-requests.update-status', props.carRequest.id), {
        status: newStatus,
    });
};

const localeForDates = locale.value === 'en' ? 'en-US' : 'es-ES';
const createdAt = new Date(props.carRequest.created_at).toLocaleDateString(localeForDates, {
    day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
});
</script>

<template>
    <Head :title="t('car_requests.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('car_requests.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Link
                    :href="route('car-requests.index')"
                    class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    {{ t('car_requests.back_to_list') }}
                </Link>

                <PageHeader
                    :title="carRequest.name"
                    :subtitle="`${t('car_requests.request_from')} ${createdAt}`"
                >
                    <template #actions>
                        <Badge :variant="statusVariant(carRequest.status)" size="md">
                            {{ statusLabel(carRequest.status) }}
                        </Badge>
                    </template>
                </PageHeader>

                <!-- Status Actions -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">{{ t('car_requests.change_status') }}</h3>
                    </div>
                    <div class="flex flex-wrap gap-2 p-4">
                        <button
                            v-if="carRequest.status !== 'pending'"
                            @click="updateStatus('pending')"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100"
                        >
                            <ClockIcon class="h-4 w-4" />
                            {{ t('car_requests.status.pending') }}
                        </button>
                        <button
                            v-if="carRequest.status !== 'contacted'"
                            @click="updateStatus('contacted')"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                        >
                            <PhoneIcon class="h-4 w-4" />
                            {{ t('car_requests.status.contacted') }}
                        </button>
                        <button
                            v-if="carRequest.status !== 'in_progress'"
                            @click="updateStatus('in_progress')"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-50 px-4 py-2 text-sm font-semibold text-estoril-700 hover:bg-estoril-100"
                        >
                            <TruckIcon class="h-4 w-4" />
                            {{ t('car_requests.status.in_progress') }}
                        </button>
                        <button
                            v-if="carRequest.status !== 'completed'"
                            @click="updateStatus('completed')"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
                        >
                            <CheckCircleIcon class="h-4 w-4" />
                            {{ t('car_requests.status.completed') }}
                        </button>
                        <button
                            v-if="carRequest.status !== 'cancelled'"
                            @click="updateStatus('cancelled')"
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100"
                        >
                            <XCircleIcon class="h-4 w-4" />
                            {{ t('car_requests.status.cancelled') }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Contact Info -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">{{ t('car_requests.contact_info') }}</h3>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="flex items-center gap-3">
                                <UserIcon class="h-5 w-5 text-gray-400" />
                                <div>
                                    <div class="text-xs text-gray-500">{{ t('car_requests.field_name') }}</div>
                                    <div class="font-medium text-gray-900">{{ carRequest.name }}</div>
                                </div>
                            </div>
                            <div v-if="carRequest.email" class="flex items-center gap-3">
                                <EnvelopeIcon class="h-5 w-5 text-gray-400" />
                                <div>
                                    <div class="text-xs text-gray-500">{{ t('car_requests.field_email') }}</div>
                                    <a :href="`mailto:${carRequest.email}`" class="font-medium text-estoril-600 hover:text-estoril-500">{{ carRequest.email }}</a>
                                </div>
                            </div>
                            <div v-if="carRequest.phone" class="flex items-center gap-3">
                                <PhoneIcon class="h-5 w-5 text-gray-400" />
                                <div>
                                    <div class="text-xs text-gray-500">{{ t('car_requests.field_phone') }}</div>
                                    <a :href="`tel:${carRequest.phone}`" class="font-medium text-estoril-600 hover:text-estoril-500">{{ carRequest.phone }}</a>
                                </div>
                            </div>
                            <div v-if="carRequest.client" class="flex items-center gap-3 rounded-lg bg-emerald-50 p-3">
                                <CheckCircleIcon class="h-5 w-5 text-emerald-600" />
                                <div>
                                    <div class="text-xs text-emerald-700">{{ t('car_requests.existing_client') }}</div>
                                    <Link :href="route('clients.show', carRequest.client.id)" class="font-medium text-emerald-900 hover:text-emerald-700">
                                        {{ carRequest.client.name }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Car Preferences -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">{{ t('car_requests.car_preferences') }}</h3>
                        </div>
                        <div class="space-y-4 p-6">
                            <div v-if="carRequest.brand" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_brand') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.brand }}</span>
                            </div>
                            <div v-if="carRequest.model" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_model') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.model }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_year') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.year_range }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_budget') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.budget_range }}</span>
                            </div>
                            <div v-if="carRequest.mileage_max" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_mileage_max') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.mileage_max.toLocaleString() }} {{ t('car_requests.km') }}</span>
                            </div>
                            <div v-if="carRequest.power_min || carRequest.power_max" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Potencia (CV)</span>
                                <span class="font-medium text-gray-900">{{ carRequest.power_min || '0' }} - {{ carRequest.power_max || '∞' }}</span>
                            </div>
                            <div v-if="carRequest.engine_type" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('cars.engine_type') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.engine_type }}</span>
                            </div>
                            <div v-if="carRequest.fuel" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_fuel') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.fuel }}</span>
                            </div>
                            <div v-if="carRequest.transmission" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_transmission') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.transmission }}</span>
                            </div>
                            <div v-if="carRequest.body_type" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_body_type') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.body_type }}</span>
                            </div>
                            <div v-if="carRequest.doors" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_doors') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.doors }}</span>
                            </div>
                            <div v-if="carRequest.seats" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_seats') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.seats }}</span>
                            </div>
                            <div v-if="carRequest.color" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">{{ t('car_requests.field_color') }}</span>
                                <span class="font-medium text-gray-900">{{ carRequest.color }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div v-if="carRequest.requirements || carRequest.notes" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">{{ t('car_requests.additional_info') }}</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div v-if="carRequest.requirements">
                            <div class="mb-1 text-sm font-medium text-gray-700">{{ t('car_requests.field_requirements') }}</div>
                            <p class="whitespace-pre-wrap text-sm text-gray-900">{{ carRequest.requirements }}</p>
                        </div>
                        <div v-if="carRequest.notes">
                            <div class="mb-1 text-sm font-medium text-gray-700">{{ t('car_requests.field_notes') }}</div>
                            <p class="whitespace-pre-wrap text-sm text-gray-900">{{ carRequest.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
