<script setup>
import { ref, computed } from 'vue';
import { LinkIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const { currency } = useFormat();

const props = defineProps({
    car: { type: Object, required: true },
    derived: { type: Object, default: () => ({}) },
});

const marketPosition = computed(() => {
    const stats = props.derived?.comparables_stats || {};
    const total = props.derived?.total_cost || 0;
    if (!stats.avg || !total) return null;
    const ratio = total / stats.avg;
    if (ratio <= 1.00) return { label: t('marketplace_show.market_below'), variant: 'success', ratio };
    if (ratio <= 1.05) return { label: t('marketplace_show.market_at'), variant: 'warning', ratio };
    return { label: t('marketplace_show.market_above'), variant: 'danger', ratio };
});

const marketCountry = ref('all');
const statsByCountry = computed(() => props.derived?.comparables_stats_by_country || {
    es: { avg: null, min: null, max: null, count: 0, items: [] },
    de: { avg: null, min: null, max: null, count: 0, items: [] },
    mixto: { avg: null, min: null, max: null, count: 0, items: [] },
    unknown: { avg: null, min: null, max: null, count: 0, items: [] },
});
const visibleComparables = computed(() => {
    const all = props.car.comparables_list || [];
    if (marketCountry.value === 'all') return all;
    const bucket = statsByCountry.value[marketCountry.value];
    if (!bucket || !bucket.items || !bucket.items.length) return all;
    const seen = new Set();
    bucket.items.forEach((it) => {
        const k = it.url || it.title || '';
        if (k) seen.add(k);
    });
    return all.filter((c) => {
        const k = c.url || c.title || '';
        return seen.has(k);
    });
});
const marketAvailableCountries = computed(() => {
    const c = statsByCountry.value;
    const out = [];
    if (c.es?.count) out.push({ key: 'es', label: 'España', count: c.es.count, flag: '🇪🇸' });
    if (c.de?.count) out.push({ key: 'de', label: 'Alemania', count: c.de.count, flag: '🇩🇪' });
    if (c.mixto?.count) out.push({ key: 'mixto', label: 'Mixto', count: c.mixto.count, flag: '🌍' });
    if (c.unknown?.count) out.push({ key: 'unknown', label: 'Sin país', count: c.unknown.count, flag: '❓' });
    return out;
});
const busquedasPorPais = computed(() => props.derived?.busquedas_por_pais || { DE: [], ES: [], otros: [] });
const busquedasPaisFlat = computed(() => {
    const out = [];
    for (const [pais, items] of Object.entries(busquedasPorPais.value)) {
        for (const b of items || []) {
            out.push({
                portal: b.portal,
                url: b.url,
                descripcion: b.descripcion,
                flag: pais === 'DE' ? '🇩🇪' : pais === 'ES' ? '🇪🇸' : '🌍',
            });
        }
    }
    return out;
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.market_comparables') }}</h3>
            <Badge v-if="marketPosition" :variant="marketPosition.variant">
                {{ marketPosition.label }} ({{ (marketPosition.ratio * 100).toFixed(1) }}%)
            </Badge>
        </div>

        <!-- Chips de país -->
        <div v-if="marketAvailableCountries.length > 1" class="flex flex-wrap gap-2 border-b border-gray-200 px-6 py-3 bg-gray-50">
            <button type="button"
                    @click="marketCountry = 'all'"
                    :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition',
                             marketCountry === 'all' ? 'bg-estoril-600 text-white shadow' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-100']">
                🌐 Todos ({{ derived?.comparables_stats?.count || 0 }})
            </button>
            <button v-for="c in marketAvailableCountries" :key="c.key"
                    type="button"
                    @click="marketCountry = c.key"
                    :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition',
                             marketCountry === c.key ? 'bg-estoril-600 text-white shadow' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-100']">
                {{ c.flag }} {{ c.label }} ({{ c.count }})
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.avg') }}</dt>
                <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_avg) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.minimum') }}</dt>
                <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_min) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.maximum') }}</dt>
                <dd class="mt-1 font-mono text-lg font-semibold text-gray-900">{{ currency(car.market_max) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.estimated_saving') }}</dt>
                <dd class="mt-1 font-mono text-lg font-semibold text-green-700">{{ currency(car.estimated_saving) }}</dd>
            </div>
        </div>

        <!-- Sub-stats por país -->
        <div v-if="marketAvailableCountries.length > 1" class="border-t border-gray-200 px-6 py-4">
            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.market_breakdown_by_country') }}</h4>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div v-for="c in marketAvailableCountries" :key="c.key"
                     class="rounded-lg border border-gray-200 p-3"
                     :class="marketCountry === c.key ? 'bg-estoril-50 ring-1 ring-estoril-200' : 'bg-white'">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-900">{{ c.flag }} {{ c.label }}</p>
                        <span class="text-xs text-gray-500">{{ c.count }} {{ c.count === 1 ? 'anuncio' : 'anuncios' }}</span>
                    </div>
                    <dl class="mt-2 space-y-1 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <dt>Promedio</dt>
                            <dd class="font-mono font-semibold text-gray-900">{{ currency(statsByCountry[c.key]?.avg) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Mínimo</dt>
                            <dd class="font-mono text-gray-900">{{ currency(statsByCountry[c.key]?.min) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Máximo</dt>
                            <dd class="font-mono text-gray-900">{{ currency(statsByCountry[c.key]?.max) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Búsquedas realizadas -->
        <div v-if="busquedasPaisFlat.length > 0" class="border-t border-gray-200 px-6 py-4">
            <h4 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <LinkIcon class="h-4 w-4" />
                {{ t('cars.market_searches_performed') || 'Búsquedas realizadas' }}
            </h4>
            <p class="mb-3 text-xs text-gray-500">
                {{ t('cars.market_searches_help') || 'URLs que se usaron para investigar este coche. Pínchalas para ver los resultados en cada portal.' }}
            </p>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <a v-for="(b, idx) in busquedasPaisFlat" :key="idx"
                   :href="b.url" target="_blank" rel="noopener"
                   class="group flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:border-estoril-300 hover:bg-estoril-50">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="text-base" aria-hidden="true">{{ b.flag }}</span>
                        <span class="flex flex-col min-w-0">
                            <span class="truncate text-sm font-semibold text-gray-900">{{ b.portal }}</span>
                            <span class="truncate text-xs text-gray-500">{{ b.descripcion }}</span>
                        </span>
                    </span>
                    <LinkIcon class="h-4 w-4 flex-shrink-0 text-gray-400 group-hover:text-estoril-600" />
                </a>
            </div>
        </div>

        <!-- Lista de comparables -->
        <div v-if="visibleComparables?.length" class="border-t border-gray-200 px-6 py-4">
            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                Comparables
                <span v-if="marketCountry !== 'all'" class="ml-1 text-gray-400">
                    ({{ marketAvailableCountries.find(c => c.key === marketCountry)?.label }})
                </span>
                <span class="ml-1 text-gray-400">({{ visibleComparables.length }})</span>
            </h4>
            <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                <li v-for="(comp, i) in visibleComparables" :key="i" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">{{ comp.title || comp.t }}</p>
                        <p class="text-xs text-gray-500">
                            <span v-if="comp.km">{{ comp.km.toLocaleString() }} km</span>
                            <span v-if="comp.country || comp.pais">
                                · <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 font-mono text-[10px] font-semibold uppercase">
                                    {{ comp.country || comp.pais }}
                                </span>
                            </span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm font-semibold text-gray-900">{{ currency(comp.price ?? comp.p) }}</span>
                        <a v-if="comp.url || comp.u" :href="comp.url || comp.u" target="_blank" rel="noopener" class="text-estoril-600 hover:text-estoril-500">
                            <LinkIcon class="h-4 w-4" />
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
