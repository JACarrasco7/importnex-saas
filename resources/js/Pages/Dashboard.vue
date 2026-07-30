<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    TruckIcon,
    UsersIcon,
    PhoneIcon,
    BellAlertIcon,
    ChartBarIcon,
    MapIcon,
    Squares2X2Icon,
    BanknotesIcon,
    CalendarDaysIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    stats: Object,
    recentCars: Array,
    trafficLights: Object,
    organization: Object,
});

const { currency, statusVariant, trafficLightVariant } = useFormat();

const lightKeys = ['green', 'amber', 'red', 'neutral'];
const totalTraffic = (props.trafficLights?.green || 0) + (props.trafficLights?.amber || 0) + (props.trafficLights?.red || 0) + (props.trafficLights?.neutral || 0);

const quickLinks = [
    { label: t('nav.cars'), description: t('nav.inventory'), route: 'cars.index', icon: TruckIcon, color: 'indigo' },
    { label: t('nav.kanban'), description: t('nav.map'), route: 'cars.kanban', icon: Squares2XIcon, color: 'purple' },
    { label: t('nav.map'), description: t('nav.finance'), route: 'cars.map', icon: MapIcon, color: 'sky' },
    { label: t('nav.finance'), description: t('nav.trips'), route: 'finance.index', icon: BanknotesIcon, color: 'emerald' },
    { label: t('nav.trips'), description: t('nav.crm'), route: 'trips.index', icon: CalendarDaysIcon, color: 'amber' },
    { label: t('nav.clients'), description: t('nav.crm'), route: 'clients.index', icon: UsersIcon, color: 'blue' },
    { label: t('nav.contacts'), description: t('nav.alerts'), route: 'contacts.index', icon: PhoneIcon, color: 'rose' },
    { label: t('nav.alerts'), description: t('nav.plan'), route: 'alerts.index', icon: BellAlertIcon, color: 'red' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ organization?.name || 'Dashboard' }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    :title="`Welcome back, ${organization?.name || 'there'}`"
                    subtitle="Here's what's happening with your inventory today."
                >
                    <template #actions>
                        <Link :href="route('cars.create')" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <TruckIcon class="h-4 w-4" />
                            New car
                        </Link>
                    </template>
                </PageHeader>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Total Cars" :value="stats.cars_total" :hint="`${stats.cars_active} active · ${stats.cars_for_sale} for sale`" :icon="TruckIcon" color="indigo" />
                    <StatCard label="Clients" :value="stats.clients_total" :hint="`${stats.clients_active} active`" :icon="UsersIcon" color="blue" />
                    <StatCard label="Network" :value="stats.contacts_total" hint="Dealers & transport" :icon="PhoneIcon" color="purple" />
                    <StatCard label="Pending Alerts" :value="stats.alerts_pending" hint="Requires attention" :icon="BellAlertIcon" color="rose" />
                </div>

                <!-- Traffic Lights + Total Cost -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="lg:col-span-2 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Traffic Light Distribution</h3>
                                <p class="text-sm text-gray-500">Health of your active inventory</p>
                            </div>
                            <ChartBarIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <div class="grid grid-cols-4 divide-x divide-gray-200">
                            <div v-for="key in lightKeys" :key="key" class="p-6 text-center">
                                <div class="mx-auto mb-3 h-10 w-10 rounded-full ring-4" :class="{
                                    'bg-emerald-500 ring-emerald-100': key === 'green',
                                    'bg-amber-500 ring-amber-100': key === 'amber',
                                    'bg-rose-500 ring-rose-100': key === 'red',
                                    'bg-gray-400 ring-gray-100': key === 'neutral',
                                }"></div>
                                <div class="text-2xl font-bold text-gray-900">{{ trafficLights[key] || 0 }}</div>
                                <div class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-500">{{ key }}</div>
                                <div v-if="totalTraffic > 0" class="mt-1 text-xs text-gray-400">
                                    {{ Math.round((trafficLights[key] / totalTraffic) * 100) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 p-6 text-white shadow-lg">
                        <BanknotesIcon class="absolute right-4 top-4 h-12 w-12 text-white/20" />
                        <p class="text-sm font-medium text-indigo-100">Total invested</p>
                        <p class="mt-2 text-4xl font-bold tracking-tight">{{ currency(stats.total_cars_cost) }}</p>
                        <p class="mt-2 text-xs text-indigo-100">In purchased vehicles</p>
                        <div class="mt-6 border-t border-white/20 pt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-100">{{ stats.cars_purchased }} purchased</span>
                                <span class="font-semibold">{{ currency(stats.total_cars_cost / Math.max(stats.cars_purchased, 1)) }} avg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Cars -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Recent Cars</h3>
                            <p class="text-sm text-gray-500">Latest 5 added to inventory</p>
                        </div>
                        <Link :href="route('cars.index')" class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                            View all
                            <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                    <div v-if="recentCars.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Health</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Purchase</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="car in recentCars" :key="car.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <Link :href="route('cars.show', car.id)" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ car.brand }} {{ car.model }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ car.year }}</td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="statusVariant(car.status)">{{ car.status }}</Badge>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ currency(car.purchase_price) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else icon="🚗" title="No cars yet" description="Start by adding your first vehicle to the inventory." action-text="Add car" :action-route="route('cars.create')" />
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Quick access</h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.label"
                            :href="route(link.route)"
                            class="group flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-indigo-300"
                        >
                            <div :class="['flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl', `bg-${link.color}-50 group-hover:bg-${link.color}-100`]">
                                <component :is="link.icon" class="h-5 w-5" :class="`text-${link.color}-600`" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ link.label }}</p>
                                <p class="text-xs text-gray-500">{{ link.description }}</p>
                            </div>
                            <ArrowRightIcon class="h-4 w-4 text-gray-400 transition group-hover:translate-x-1 group-hover:text-indigo-600" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
