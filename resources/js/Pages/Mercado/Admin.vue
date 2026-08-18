<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MarketRowForm from './MarketRowForm.vue';

const props = defineProps({
    models: { type: Object, required: true },
    kpis: { type: Object, required: true },
    categorias: { type: Array, required: true },
    segmentos: { type: Array, required: true },
    tiposCliente: { type: Array, required: true },
    veredictos: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const badge = {
    verde: 'bg-emerald-100 text-emerald-700',
    amarillo: 'bg-amber-100 text-amber-700',
    rojo: 'bg-red-100 text-red-600',
};

function applyFilter(key, value) {
    router.get(route('mercado.admin'), { ...props.filters, [key]: value }, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-asphalt-900">Mapa de mercado</h1>
                    <p class="text-sm text-asphalt-500">
                        {{ models.total }} modelos · administra nota, veredicto y oportunidades
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="#" class="rounded-md bg-estoril-700 px-3 py-2 text-sm font-medium text-white" @click.prevent="router.get(route('mercado.admin.leads'))">
                        Leads ({{ kpis.leads_nuevos }} nuevos)
                    </a>
                    <label class="inline-flex items-center gap-2 text-sm text-asphalt-600">
                        <input
                            type="checkbox"
                            class="rounded border-asphalt-300 text-estoril-700"
                            :checked="Boolean(filters.caducados)"
                            @change="applyFilter('caducados', $event.target.checked ? 1 : null)"
                        >
                        Solo caducados
                    </label>
                    <select
                        class="rounded-md border-asphalt-300 text-sm"
                        :value="filters.categoria ?? ''"
                        @change="applyFilter('categoria', $event.target.value || null)"
                    >
                        <option value="">Todas las categorías</option>
                        <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                    <select
                        class="rounded-md border-asphalt-300 text-sm"
                        :value="filters.segmento ?? ''"
                        @change="applyFilter('segmento', $event.target.value || null)"
                    >
                        <option value="">Todos los segmentos</option>
                        <option v-for="s in segmentos" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <select
                        class="rounded-md border-asphalt-300 text-sm"
                        :value="filters.tipo_cliente ?? ''"
                        @change="applyFilter('tipo_cliente', $event.target.value || null)"
                    >
                        <option value="">Para qué cliente</option>
                        <option v-for="t in tiposCliente" :key="t" :value="t">{{ t.replace(/_/g, ' ') }}</option>
                    </select>
                </div>
            </div>

            <!-- #9 KPIs -->
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">Total</div><div class="text-lg font-bold text-asphalt-900">{{ kpis.total }}</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">🟢 Verdes</div><div class="text-lg font-bold text-emerald-600">{{ kpis.verdes }}</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">🟡 Amarillos</div><div class="text-lg font-bold text-amber-500">{{ kpis.amarillos }}</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">🔴 Rojos</div><div class="text-lg font-bold text-red-500">{{ kpis.rojos }}</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">★ Oportunidades</div><div class="text-lg font-bold text-estoril-700">{{ kpis.oportunidades }}</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">Hueco medio</div><div class="text-lg font-bold text-asphalt-900">{{ kpis.hueco_medio }}%</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">Caducados</div><div class="text-lg font-bold" :class="kpis.caducados ? 'text-red-500' : 'text-asphalt-900'">{{ kpis.caducados }}</div></div>
                <div class="rounded-xl border border-asphalt-200 bg-white p-3 shadow-sm"><div class="text-xs text-asphalt-500">Leads</div><div class="text-lg font-bold text-asphalt-900">{{ kpis.leads }} <span v-if="kpis.leads_nuevos" class="text-xs font-medium text-emerald-600">({{ kpis.leads_nuevos }} nuevos)</span></div></div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-asphalt-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-asphalt-200 text-sm">
                    <thead class="bg-asphalt-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-asphalt-500">
                            <th class="px-4 py-3">Modelo</th>
                            <th class="px-4 py-3">Categoría</th>
                            <th class="px-4 py-3">Segmento</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Mediana DE / ES</th>
                            <th class="px-4 py-3">Hueco</th>
                            <th class="px-4 py-3">Neto</th>
                            <th class="px-4 py-3">Mercado</th>
                            <th class="px-4 py-3">Veredicto</th>
                            <th class="px-4 py-3">Fuente</th>
                            <th class="px-4 py-3">Tend.</th>
                            <th class="px-4 py-3">Vend.</th>
                            <th class="px-4 py-3">Publicar</th>
                            <th class="px-4 py-3">Oportunidad</th>
                            <th class="px-4 py-3">Nota</th>
                            <th class="px-4 py-3">Refrescar</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-asphalt-100">
                        <tr v-for="m in models.data" :key="m.id" class="align-top hover:bg-asphalt-50/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-asphalt-900">{{ m.modelo }}</div>
                                <div v-if="m.version" class="text-xs text-asphalt-500">{{ m.version }}</div>
                                <div class="text-xs text-asphalt-400">{{ m.slug }}</div>
                            </td>
                            <td class="px-4 py-3 text-asphalt-600">{{ m.categoria }}</td>
                            <td class="px-4 py-3 text-asphalt-600">{{ m.segmento || '—' }}<span v-if="m.rango_precio" class="text-xs text-asphalt-400"> · {{ m.rango_precio }}</span></td>
                            <td class="px-4 py-3 text-asphalt-600">{{ m.tipo_cliente ? m.tipo_cliente.replace(/_/g, ' ') : '—' }}</td>
                            <td class="px-4 py-3 text-asphalt-700">
                                {{ m.mediana_de?.toLocaleString('es-ES') }} / {{ m.mediana_es?.toLocaleString('es-ES') }}
                            </td>
                            <td class="px-4 py-3 font-medium" :class="m.hueco_pct >= 10 ? 'text-emerald-600' : 'text-asphalt-600'">
                                {{ m.hueco_pct !== null ? m.hueco_pct.toFixed(1) + '%' : '—' }}
                            </td>
                            <td class="px-4 py-3" :class="m.hueco_neto_pct > 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ m.hueco_neto_pct !== null ? m.hueco_neto_pct.toFixed(1) + '%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-asphalt-600">{{ m.mejor_mercado }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="badge[m.veredicto]">
                                    {{ m.veredicto }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="m.veredicto_fuente === 'humano'" class="rounded-full bg-asphalt-700 px-2 py-0.5 text-[10px] font-semibold text-white" title="Corregido por humano">humano</span>
                                <span v-else class="text-xs text-asphalt-400">IA</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span v-if="m.tendencia" :class="m.tendencia.delta_hueco > 0 ? 'text-emerald-600' : 'text-red-500'">
                                    {{ m.tendencia.delta_hueco > 0 ? '▲' : '▼' }} {{ Math.abs(m.tendencia.delta_hueco).toFixed(1) }}
                                </span>
                                <span v-else class="text-asphalt-300">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-estoril-50 px-2 py-0.5 text-xs font-semibold text-estoril-700">{{ m.vendibilidad ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="m.publicar_en_catalogo" class="text-xs font-semibold text-emerald-600">Sí</span>
                                <span v-else class="text-xs text-asphalt-400">No</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="m.oportunidad" class="text-xs font-semibold text-emerald-600">★ Chollo</span>
                            </td>
                            <td class="max-w-xs px-4 py-3 text-asphalt-600">{{ m.nota || '—' }}</td>
                            <td class="px-4 py-3 text-xs" :class="m.refrescar_antes_de < (new Date().toISOString().slice(0,10)) ? 'font-semibold text-red-500' : 'text-asphalt-500'">
                                {{ m.refrescar_antes_de }}
                            </td>
                            <td class="px-4 py-3">
                                <MarketRowForm
                                    :route-name="'mercado.admin.update'"
                                    :route-param="m.id"
                                    :fields="[
                                        { name: 'veredicto', value: m.veredicto, type: 'select', options: veredictos },
                                        { name: 'oportunidad', value: Boolean(m.oportunidad), type: 'checkbox', label: 'Oportunidad' },
                                        { name: 'publicar_en_catalogo', value: Boolean(m.publicar_en_catalogo), type: 'checkbox', label: 'Publicar' },
                                        { name: 'nota', value: m.nota ?? '', type: 'textarea', placeholder: 'Nota...' },
                                    ]"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="models.links && models.links.length > 3" class="mt-6 flex justify-center gap-1">
                <template v-for="link in models.links" :key="link.label">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-md px-3 py-1.5 text-sm"
                        :class="link.active ? 'bg-estoril-700 text-white' : 'text-asphalt-600 hover:bg-asphalt-100'"
                    />
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
