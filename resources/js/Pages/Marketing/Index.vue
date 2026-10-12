<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    MegaphoneIcon,
    CheckCircleIcon,
    XCircleIcon,
    SparklesIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
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

function sourceTag(content) {
    if (content?.source === 'zip') return 'ZIP';
    if (content?.source === 'ai') return 'IA';
    return null;
}

// v2: un coche puede tener hasta 22 filas (3 redes × 6 piezas + 4 portales).
// Agrupamos por canal para mostrar un chip por canal (no 22 repetidos):
// estado = publicado si TODAS sus piezas lo están.
function channelsOf(car) {
    const list = car.marketing_contents || [];
    const byChannel = new Map();
    for (const c of list) {
        const existing = byChannel.get(c.channel);
        if (!existing) {
            byChannel.set(c.channel, { channel: c.channel, count: 1, published: c.status === 'published' ? 1 : 0, source: c.source });
        } else {
            existing.count++;
            if (c.status === 'published') existing.published++;
        }
    }
    return [...byChannel.values()].map(ch => ({
        ...ch,
        icon: channelIcon(ch.channel),
        allPublished: ch.published === ch.count,
    }));
}

// Resumen de estado global del coche: Publicado / Borrador / Parcial (n/N).
function channelSummary(car) {
    const list = car.marketing_contents || [];
    if (!list.length) return { variant: 'gray', text: 'Sin anuncio' };
    const published = list.filter(c => c.status === 'published').length;
    if (published === list.length) return { variant: 'success', text: `Publicado (${published}/${list.length})` };
    if (published === 0) return { variant: 'warning', text: `Borrador (${list.length})` };
    return { variant: 'warning', text: `Parcial (${published}/${list.length})` };
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
                <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
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
                    <div class="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div class="text-2xl font-bold text-asphalt-700">{{ stats.from_zip }}</div>
                        <div class="text-xs text-gray-500">Del ZIP (Claude)</div>
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
                                v-for="car in cars.data"
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
                                            v-for="ch in channelsOf(car)"
                                            :key="ch.channel"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                                            :class="ch.allPublished ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100'"
                                            :title="`${ch.count} piezas · ${ch.published}/${ch.count} publicadas`"
                                        >
                                            {{ ch.icon }} {{ ch.label }}
                                            <span v-if="ch.count > 1" class="font-semibold opacity-60">×{{ ch.count }}</span>
                                            <span v-if="sourceTag(ch)" class="font-semibold text-gray-400">{{ sourceTag(ch) }}</span>
                                        </span>
                                        <span v-if="!channelsOf(car).length" class="text-xs text-gray-400">—</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="channelSummary(car).variant">
                                        {{ channelSummary(car).text }}
                                    </Badge>
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
                            <tr v-if="!cars.data.length">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">{{ t('common.no_results') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div v-if="cars.links && cars.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="text-sm text-gray-700">
                            {{ t('common.showing') }} <span class="font-semibold">{{ cars.from }}</span> {{ t('common.to') }} <span class="font-semibold">{{ cars.to }}</span> {{ t('common.of') }} <span class="font-semibold">{{ cars.total }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <component v-for="link in cars.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
                                'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm',
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
    </AuthenticatedLayout>
</template>
