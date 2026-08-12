<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckBadgeIcon,
    ClockIcon,
    ScaleIcon,
    ExclamationTriangleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    periodo: String,
    filtros: Object,
    marcas: Array,
    paises: Array,
    kpis: Object,
    tendencia: Array,
    cierres: Array,
});

function buildHref(extra) {
    const params = { periodo: props.periodo };
    if (props.filtros?.brand) params.brand = props.filtros.brand;
    if (props.filtros?.pais) params.pais = props.filtros.pais;
    return route('kpis.index', { ...params, ...extra });
}

function applyFilters(key, value) {
    const newFiltros = { ...(props.filtros || {}) };
    if (value) {
        newFiltros[key] = value;
    } else {
        delete newFiltros[key];
    }
    router.get(route('kpis.index'), { periodo: props.periodo, ...newFiltros }, { preserveState: false, replace: true });
}

function clearFilters() {
    router.get(route('kpis.index'), { periodo: props.periodo }, { preserveState: false, replace: true });
}

const hasFilters = computed(() => props.filtros?.brand || props.filtros?.pais);

const { currency } = useFormat();

const [year, month] = props.periodo.split('-');
const mesActual = new Date().getFullYear() === Number(year) && new Date().getMonth() === Number(month) - 1;

const periodoLabel = computed(() => {
    const names = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return `${names[Number(month) - 1]} ${year}`;
});

const prevPeriodo = computed(() => {
    const d = new Date(Number(year), Number(month) - 2, 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});

const nextPeriodo = computed(() => {
    const d = new Date(Number(year), Number(month), 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});

function kpiMeta(kpi) {
    if (kpi.valor === null) return { state: 'neutral', color: 'bg-gray-100', text: 'text-gray-600', ring: 'ring-gray-200' };
    const ok = kpi.tipo === 'menor_mejor' ? kpi.valor <= kpi.objetivo : kpi.valor >= kpi.objetivo;
    const near = kpi.tipo === 'menor_mejor' ? kpi.valor <= kpi.objetivo * 1.5 : kpi.valor >= kpi.objetivo * 0.75;
    if (ok) return { state: 'ok', color: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-200' };
    if (near) return { state: 'warn', color: 'bg-amber-50', text: 'text-amber-700', ring: 'ring-amber-200' };
    return { state: 'bad', color: 'bg-rose-50', text: 'text-rose-700', ring: 'ring-rose-200' };
}

const kpiCards = computed(() => [
    { key: 'precision_veredictos', label: t('kpis.card_precision_veredictos'), icon: CheckBadgeIcon, suffix: '%', ...props.kpis.precision_veredictos },
    { key: 'tiempo_hasta_venta', label: t('kpis.card_tiempo_hasta_venta'), icon: ClockIcon, suffix: 'd', ...props.kpis.tiempo_hasta_venta },
    { key: 'desviacion_precio', label: t('kpis.card_desviacion_precio'), icon: ScaleIcon, suffix: '%', ...props.kpis.desviacion_precio },
    { key: 'tasa_falsos_positivos', label: t('kpis.card_tasa_falsos_positivos'), icon: ExclamationTriangleIcon, suffix: '%', ...props.kpis.tasa_falsos_positivos },
]);

const tendenciaMax = computed(() => Math.max(1, ...(props.tendencia || []).map((t) => Number(t.precision) || 0)));

const estadoVenta = (c) => {
    if (c.estado === 'vendido') return { key: 'vendido', label: t('kpis.estado_vendido'), cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200' };
    if (c.estado === 'no_vendido') return { key: 'no_vendido', label: t('kpis.estado_no_vendido'), cls: 'bg-rose-50 text-rose-700 ring-rose-200' };
    return { key: 'pendiente', label: t('kpis.estado_pendiente'), cls: 'bg-amber-50 text-amber-700 ring-amber-200' };
};
</script>

<template>
    <Head :title="t('kpis.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('kpis.heading') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <PageHeader
                        :title="t('kpis.page_title')"
                        :subtitle="t('kpis.subtitle')"
                    />

                    <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm ring-1 ring-gray-200">
                        <Link :href="buildHref({ periodo: prevPeriodo })" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900" :aria-label="t('kpis.prev_month')">
                            <ChevronLeftIcon class="h-5 w-5" />
                        </Link>
                        <span class="min-w-32 text-center text-sm font-semibold text-gray-900">{{ periodoLabel }}</span>
                        <Link
                            :href="buildHref({ periodo: nextPeriodo })"
                            class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                            :aria-label="t('kpis.next_month')"
                            :class="mesActual ? 'pointer-events-none opacity-40' : ''"
                        >
                            <ChevronRightIcon class="h-5 w-5" />
                        </Link>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ t('kpis.filter_brand') }}</label>
                        <select
                            :value="filtros?.brand || ''"
                            @change="(e) => applyFilters('brand', e.target.value || null)"
                            class="w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-estoril-500"
                        >
                            <option value="">{{ t('kpis.filter_brand_all') }}</option>
                            <option v-for="m in marcas" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ t('kpis.filter_country') }}</label>
                        <select
                            :value="filtros?.pais || ''"
                            @change="(e) => applyFilters('pais', e.target.value || null)"
                            class="w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-estoril-500"
                        >
                            <option value="">{{ t('kpis.filter_country_all') }}</option>
                            <option v-for="p in paises" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>
                    <button
                        v-if="hasFilters"
                        @click="clearFilters"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        {{ t('kpis.clear_filters') }}
                    </button>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="kpi in kpiCards" :key="kpi.key" class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <div class="flex items-center gap-3">
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', kpiMeta(kpi).color]">
                                <component :is="kpi.icon" class="h-5 w-5" :class="kpiMeta(kpi).text" />
                            </div>
                            <p class="text-sm font-semibold text-gray-900">{{ kpi.label }}</p>
                        </div>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span v-if="kpi.valor !== null" class="text-3xl font-bold" :class="kpiMeta(kpi).text">{{ kpi.valor }}</span>
                            <span v-else class="text-3xl font-bold text-gray-300">—</span>
                            <span class="text-sm font-medium" :class="kpiMeta(kpi).text">{{ kpi.suffix }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ kpi.detalle }}</p>
                        <div v-if="kpi.valor !== null" class="mt-3 flex items-center gap-1.5">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1" :class="kpiMeta(kpi).color + ' ' + kpiMeta(kpi).text + ' ' + kpiMeta(kpi).ring">
                                {{ t('kpis.goal') }} {{ kpi.objetivo }}{{ kpi.suffix }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tendencia precisión últimos 6 meses -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('kpis.trend_title') }}</h3>
                    </div>
                    <div v-if="tendencia.length" class="p-6">
                        <div class="flex items-end justify-between gap-2">
                            <div v-for="row in tendencia" :key="row.periodo" class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex w-full flex-col items-center justify-end" style="height: 160px">
                                    <span class="mb-1 text-xs font-semibold text-gray-600">
                                        {{ row.precision !== null ? row.precision + '%' : '—' }}
                                    </span>
                                    <div
                                        class="w-full rounded-t-lg"
                                        :style="{ height: (row.precision !== null ? (row.precision / tendenciaMax) * 140 : 4) + 'px', backgroundColor: row.precision === null ? '#e5e7eb' : (row.precision >= 80 ? '#10b981' : row.precision >= 60 ? '#f59e0b' : '#f43f5e') }"
                                        :title="t('kpis.trend_period_tooltip', { periodo: row.periodo, precision: row.precision !== null ? row.precision + '%' : t('kpis.trend_period_no_data'), volumen: row.volumen })"
                                    ></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ row.periodo }}</span>
                                <span class="text-[10px] text-gray-400">{{ row.volumen }} {{ t('kpis.trend_cierres_suffix') }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-10 text-center text-sm text-gray-500">{{ t('kpis.trend_no_data') }}</div>
                </div>

                <!-- Tabla de cierres -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('kpis.cierres_title') }}</h3>
                        <span class="text-sm text-gray-500">{{ t('kpis.cierres_count', { count: cierres.length }) }}</span>
                    </div>

                    <div v-if="cierres.length" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_coche') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_brand_model') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_platform') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_fecha_investigacion') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_veredicto') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_precio_objetivo') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_precio_final') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_desviacion') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('kpis.col_estado') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="c in cierres" :key="c.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        <Link v-if="c.car_id" :href="route('cars.show', c.car_id)" class="font-medium text-estoril-700 hover:underline">#{{ c.car_id }}</Link>
                                        <span v-else-if="c.coche_id" class="text-gray-500">{{ c.coche_id }}</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        <span v-if="c.brand">{{ c.brand }} <span v-if="c.model" class="text-gray-400">{{ c.model }}</span></span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ c.plataforma || '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ c.fecha_investigacion }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ c.veredicto }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-600">{{ c.precio_objetivo !== null ? currency(c.precio_objetivo) : '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-600">{{ c.precio_final !== null ? currency(c.precio_final) : '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium" :class="c.desviacion_pct !== null ? (c.desviacion_pct >= 0 ? 'text-emerald-600' : 'text-rose-600') : 'text-gray-400'">
                                        {{ c.desviacion_pct !== null ? (c.desviacion_pct > 0 ? '+' : '') + c.desviacion_pct + '%' : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1" :class="estadoVenta(c).cls">
                                            {{ estadoVenta(c).label }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-10 text-center text-sm text-gray-500">
                        {{ t('kpis.empty_cierres') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
