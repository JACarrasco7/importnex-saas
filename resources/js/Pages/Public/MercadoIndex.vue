<script setup>
import { router, useForm } from '@inertiajs/vue3';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    models: { type: Object, required: true },
    categorias: { type: Array, required: true },
    segmentos: { type: Array, required: true },
    tiposCliente: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const veredictoLabel = { verde: 'Oportunidad', amarillo: 'Interés', rojo: 'Descartado' };
const veredictoBadge = {
    verde: 'bg-emerald-100 text-emerald-700',
    amarillo: 'bg-amber-100 text-amber-700',
    rojo: 'bg-red-100 text-red-600',
};
const mercadoLabel = { DE: 'Desde Alemania', ES: 'En España', paridad: 'Paridad ES/DE' };

const filters = computed(() => props.filters);

function applyFilter(key, value) {
    router.get(route('mercado.index'), { ...filters.value, [key]: value }, {
        preserveState: true,
        replace: true,
    });
}

/* #2 — Lead capture */
const leadForm = useForm({ nombre: '', contacto: '', presupuesto: '', mensaje: '' });
const leadModel = ref(null);
function openLead(m) { leadModel.value = m; leadForm.reset(); }
function submitLead() {
    leadForm.post(route('mercado.interest', leadModel.value.id), {
        preserveScroll: true,
        onSuccess: () => { leadModel.value = null; },
    });
}

/* #3 — Calculadora de coste */
const costeModel = ref(null);
const costeData = ref(null);
const costeLoading = ref(false);
async function openCoste(m) {
    costeModel.value = m;
    costeData.value = null;
    costeLoading.value = true;
    try {
        const res = await fetch(route('mercado.coste', m.id));
        costeData.value = (await res.json()).coste;
    } finally {
        costeLoading.value = false;
    }
}

/* #8 — Comparador (hasta 3) */
const compareIds = ref([]);
const showCompare = ref(false);
function toggleCompare(id) {
    const i = compareIds.value.indexOf(id);
    if (i >= 0) compareIds.value.splice(i, 1);
    else if (compareIds.value.length < 3) compareIds.value.push(id);
}
const compareModels = computed(() =>
    props.models.data.filter((m) => compareIds.value.includes(m.id)),
);
function openCompare() { if (compareIds.value.length >= 2) showCompare.value = true; }
</script>

<template>
    <MarketplaceLayout>
        <div class="bg-estoril-700">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ $t('mercado.title') }}
                </h1>
                <p class="mt-3 max-w-2xl text-estoril-100">
                    {{ $t('mercado.subtitle') }}
                </p>
                <a
                    :href="route('marketplace.index')"
                    class="mt-6 inline-block rounded-lg bg-white px-4 py-2 text-sm font-semibold text-estoril-700 transition hover:bg-estoril-50"
                >
                    {{ $t('mercado.cta_marketplace') }} →
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Filtros -->
            <div class="mb-8 flex flex-wrap items-center gap-3">
                <button
                    v-for="cat in categorias"
                    :key="cat"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                    :class="filters.categoria === cat
                        ? 'bg-estoril-700 text-white'
                        : 'bg-asphalt-100 text-asphalt-700 hover:bg-asphalt-200'"
                    @click="applyFilter('categoria', filters.categoria === cat ? null : cat)"
                >
                    {{ cat.replace('_', ' ') }}
                </button>

                <select
                    class="rounded-full border-asphalt-300 bg-white px-4 py-1.5 text-sm text-asphalt-700"
                    :value="filters.segmento ?? ''"
                    @change="applyFilter('segmento', $event.target.value || null)"
                >
                    <option value="">{{ $t('mercado.filtro_segmento') }}</option>
                    <option v-for="s in segmentos" :key="s" :value="s">{{ s }}</option>
                </select>

                <select
                    class="rounded-full border-asphalt-300 bg-white px-4 py-1.5 text-sm text-asphalt-700"
                    :value="filters.tipo_cliente ?? ''"
                    @change="applyFilter('tipo_cliente', $event.target.value || null)"
                >
                    <option value="">{{ $t('mercado.filtro_cliente') }}</option>
                    <option v-for="t in tiposCliente" :key="t" :value="t">{{ t.replace(/_/g, ' ') }}</option>
                </select>

                <label class="ml-auto inline-flex cursor-pointer items-center gap-2 text-sm text-asphalt-600">
                    <input
                        type="checkbox"
                        class="rounded border-asphalt-300 text-estoril-700 focus:ring-estoril-500"
                        :checked="Boolean(filters.con_negocio)"
                        @change="applyFilter('con_negocio', $event.target.checked ? 1 : null)"
                    >
                    {{ $t('mercado.con_margen') }}
                </label>
                <button
                    v-if="compareIds.length >= 2"
                    class="rounded-full bg-estoril-700 px-4 py-1.5 text-sm font-medium text-white"
                    @click="openCompare"
                >
                    {{ $t('mercado.comparar') }} ({{ compareIds.length }})
                </button>
            </div>

            <!-- Grid de modelos -->
            <div v-if="models.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="m in models.data"
                    :key="m.id"
                    class="flex flex-col rounded-xl border border-asphalt-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                >
                    <img v-if="m.foto_url" :src="m.foto_url" :alt="m.modelo" class="mb-3 h-36 w-full rounded-lg object-cover">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="rounded border-asphalt-300 text-estoril-700"
                                :checked="compareIds.includes(m.id)"
                                @change="toggleCompare(m.id)"
                            >
                            <h3 class="text-lg font-semibold text-asphalt-900">{{ m.modelo }}</h3>
                        </label>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span v-if="m.segmento" class="rounded-full bg-asphalt-100 px-2 py-0.5 text-[11px] text-asphalt-600">{{ m.segmento }} · {{ m.rango_precio }}</span>
                            <span v-if="m.tipo_cliente" class="rounded-full bg-estoril-50 px-2 py-0.5 text-[11px] text-estoril-700">{{ m.tipo_cliente.replace(/_/g, ' ') }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="veredictoBadge[m.veredicto]">
                                {{ veredictoLabel[m.veredicto] }}
                            </span>
                        </div>
                    </div>
                    <p v-if="m.version" class="text-sm text-asphalt-500">{{ m.version }}</p>

                    <dl class="mt-4 space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-asphalt-500">Precio de origen</dt>
                            <dd class="font-semibold text-asphalt-900">
                                {{ m.precio_desde_de ? m.precio_desde_de.toLocaleString('es-ES') + ' €' : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-asphalt-500">Mediana ES</dt>
                            <dd>{{ m.mediana_es ? m.mediana_es.toLocaleString('es-ES') + ' €' : '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-asphalt-500">Hueco de mercado</dt>
                            <dd class="font-medium" :class="m.hueco_pct >= 10 ? 'text-emerald-600' : 'text-asphalt-600'">
                                {{ m.hueco_pct !== null ? m.hueco_pct.toFixed(1) + ' %' : '—' }}
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-4 rounded-lg bg-estoril-50 px-3 py-2 text-xs font-medium text-estoril-700">
                        {{ mercadoLabel[m.mejor_mercado] }} · {{ m.mejor_mercado }}
                    </p>

                    <div class="mt-4 flex gap-2">
                        <button
                            class="flex-1 rounded-lg bg-estoril-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-estoril-600"
                            @click="openLead(m)"
                        >
                            {{ $t('mercado.me_interesa') }}
                        </button>
                        <button
                            class="rounded-lg border border-asphalt-200 px-3 py-2 text-sm font-medium text-asphalt-700 transition hover:bg-asphalt-50"
                            @click="openCoste(m)"
                        >
                            {{ $t('mercado.puesto_en_huelva') }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-xl border border-dashed border-asphalt-300 py-16 text-center text-asphalt-500">
                {{ $t('mercado.sin_modelos') }}
            </div>

            <!-- Paginación -->
            <div v-if="models.links && models.links.length > 3" class="mt-8 flex justify-center gap-1">
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

        <!-- Modal #2 Lead -->
        <div v-if="leadModel" class="fixed inset-0 z-50 flex items-center justify-center bg-asphalt-900/60 p-4" @click.self="leadModel = null">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-asphalt-900">{{ $t('mercado.lead_title') }} {{ leadModel.modelo }}</h3>
                <p class="mt-1 text-sm text-asphalt-500">{{ $t('mercado.lead_sub') }}</p>
                <form class="mt-4 space-y-3" @submit.prevent="submitLead">
                    <input v-model="leadForm.nombre" type="text" :placeholder="$t('mercado.lead_nombre')"
                        class="w-full rounded-lg border-asphalt-300 text-sm">
                    <input v-model="leadForm.contacto" type="text" :placeholder="$t('mercado.lead_contacto')" required
                        class="w-full rounded-lg border-asphalt-300 text-sm">
                    <input v-model="leadForm.presupuesto" type="number" min="0" :placeholder="$t('mercado.lead_presupuesto')"
                        class="w-full rounded-lg border-asphalt-300 text-sm">
                    <textarea v-model="leadForm.mensaje" rows="2" :placeholder="$t('mercado.lead_mensaje')"
                        class="w-full rounded-lg border-asphalt-300 text-sm"></textarea>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="leadForm.processing"
                            class="flex-1 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {{ $t('mercado.enviar') }}
                        </button>
                        <button type="button" class="rounded-lg border border-asphalt-200 px-4 py-2 text-sm text-asphalt-600" @click="leadModel = null">
                            {{ $t('mercado.cancelar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal #3 Coste -->
        <div v-if="costeModel" class="fixed inset-0 z-50 flex items-center justify-center bg-asphalt-900/60 p-4" @click.self="costeModel = null">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-asphalt-900">{{ $t('mercado.coste_title') }} {{ costeModel.modelo }}</h3>
                <p v-if="costeLoading" class="mt-4 text-sm text-asphalt-500">{{ $t('mercado.calculando') }}</p>
                <dl v-else-if="costeData" class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-asphalt-500">Precio de origen</dt><dd>{{ costeData.precio_origen.toLocaleString('es-ES') }} €</dd></div>
                    <div class="flex justify-between"><dt class="text-asphalt-500">Transporte</dt><dd>{{ costeData.transporte.toLocaleString('es-ES') }} €</dd></div>
                    <div class="flex justify-between"><dt class="text-asphalt-500">Ausfuhr + seguro</dt><dd>{{ costeData.ausfuhr.toLocaleString('es-ES') }} €</dd></div>
                    <div class="flex justify-between"><dt class="text-asphalt-500">ITV importación</dt><dd>{{ costeData.itv.toLocaleString('es-ES') }} €</dd></div>
                    <div class="flex justify-between"><dt class="text-asphalt-500">IEDMT (est.)</dt><dd>{{ costeData.iedmt.toLocaleString('es-ES') }} €</dd></div>
                    <div class="flex justify-between"><dt class="text-asphalt-500">Honorarios (M2)</dt><dd>{{ costeData.honorarios.toLocaleString('es-ES') }} €</dd></div>
                    <div class="flex justify-between border-t border-asphalt-100 pt-2 font-semibold text-asphalt-900">
                        <dt>Total estimado</dt><dd class="text-estoril-700">{{ costeData.total.toLocaleString('es-ES') }} €</dd>
                    </div>
                </dl>
                <p v-else class="mt-4 text-sm text-asphalt-500">Sin datos de coste para esta referencia.</p>
                <button class="mt-4 w-full rounded-lg border border-asphalt-200 px-4 py-2 text-sm text-asphalt-600" @click="costeModel = null">Cerrar</button>
            </div>
        </div>

        <!-- Modal #8 Comparador -->
        <div v-if="showCompare" class="fixed inset-0 z-50 flex items-center justify-center bg-asphalt-900/60 p-4" @click.self="showCompare = false">
            <div class="w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-asphalt-100 px-6 py-4">
                    <h3 class="text-lg font-semibold text-asphalt-900">Comparativa</h3>
                    <button class="text-asphalt-500 hover:text-asphalt-700" @click="showCompare = false">✕</button>
                </div>
                <div class="overflow-x-auto p-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-asphalt-500">
                                <th class="pb-2 pr-4"></th>
                                <th v-for="m in compareModels" :key="m.id" class="pb-2 pr-4 font-semibold text-asphalt-900">{{ m.modelo }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-asphalt-100">
                            <tr><td class="py-2 pr-4 text-asphalt-500">Versión</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4">{{ m.version || '—' }}</td></tr>
                            <tr><td class="py-2 pr-4 text-asphalt-500">Precio origen</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4">{{ m.precio_desde_de?.toLocaleString('es-ES') }} €</td></tr>
                            <tr><td class="py-2 pr-4 text-asphalt-500">Mediana ES</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4">{{ m.mediana_es?.toLocaleString('es-ES') }} €</td></tr>
                            <tr><td class="py-2 pr-4 text-asphalt-500">Hueco</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4 font-medium" :class="m.hueco_pct >= 10 ? 'text-emerald-600' : ''">{{ m.hueco_pct?.toFixed(1) }}%</td></tr>
                            <tr><td class="py-2 pr-4 text-asphalt-500">Neto</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4">{{ m.hueco_neto_pct?.toFixed(1) }}%</td></tr>
                            <tr><td class="py-2 pr-4 text-asphalt-500">Segmento</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4">{{ m.segmento }} · {{ m.rango_precio }}</td></tr>
                            <tr><td class="py-2 pr-4 text-asphalt-500">Cliente</td><td v-for="m in compareModels" :key="m.id" class="py-2 pr-4">{{ m.tipo_cliente?.replace(/_/g, ' ') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MarketplaceLayout>
</template>
