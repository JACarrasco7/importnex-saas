<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    MegaphoneIcon,
    CheckCircleIcon,
    XCircleIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    cars: Array,
    stats: Object,
});

const CHANNELS = [
    { key: 'milanuncios', label: 'Milanuncios', icon: '🛒' },
    { key: 'coches_net', label: 'Coches.net', icon: '🚗' },
    { key: 'wallapop', label: 'Wallapop', icon: '📱' },
    { key: 'tiktok', label: 'TikTok', icon: '🎵' },
    { key: 'instagram', label: 'Instagram', icon: '📷' },
    { key: 'facebook', label: 'Facebook', icon: '📘' },
];

function channelLabel(channel) {
    return CHANNELS.find(c => c.key === channel)?.label || channel;
}

function channelIcon(channel) {
    return CHANNELS.find(c => c.key === channel)?.icon || '📄';
}

function getStatusBadge(content) {
    if (!content) return { variant: 'gray', text: 'Sin anuncio' };
    if (content.status === 'published') return { variant: 'success', text: 'Publicado' };
    return { variant: 'warning', text: 'Borrador' };
}
</script>

<template>
    <Head :title="t('nav.marketing')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('nav.marketing') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('marketing.title')" :subtitle="t('marketing.subtitle')">
                    <template #actions>
                        <Link :href="route('dashboard')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Dashboard
                        </Link>
                    </template>
                </PageHeader>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div class="text-2xl font-bold text-estoril-600">{{ stats.total_cars }}</div>
                        <div class="text-xs text-gray-500">Coches registrados</div>
                    </div>
                    <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div class="text-2xl font-bold text-emerald-600">{{ stats.with_content }}</div>
                        <div class="text-xs text-gray-500">Con anuncios</div>
                    </div>
                    <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.published }}</div>
                        <div class="text-xs text-gray-500">Publicados</div>
                    </div>
                    <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div class="text-2xl font-bold text-amber-600">{{ stats.drafts }}</div>
                        <div class="text-xs text-gray-500">Borradores</div>
                    </div>
                </div>

                <!-- Cars Table -->
                <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3">Coche</th>
                                <th class="px-4 py-3">Precio</th>
                                <th class="px-4 py-3">Canales</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="car in cars"
                                :key="car.id"
                                class="border-b border-gray-100 hover:bg-gray-50"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ car.brand }} {{ car.model }}</div>
                                    <div class="text-xs text-gray-500">{{ car.year }} · {{ car.mileage }} km</div>
                                </td>
                                <td class="px-4 py-3">{{ car.purchase_price ? `€${car.purchase_price.toLocaleString()}` : '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="content in car.marketingContents"
                                            :key="content.id"
                                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs"
                                        >
                                            {{ channelIcon(content.channel) }} {{ channelLabel(content.channel) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="content in car.marketingContents"
                                            :key="content.id"
                                            :variant="content.status === 'published' ? 'success' : 'warning'"
                                        >
                                            {{ content.status === 'published' ? 'Publicado' : 'Borrador' }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('cars.marketing', car.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500"
                                    >
                                        <MegaphoneIcon class="h-3 w-3" />
                                        Marketing
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
