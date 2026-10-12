<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    porCategoria: { type: Array, required: true },
    porSegmento: { type: Array, required: true },
    topOportunidades: { type: Array, required: true },
    evolucion: { type: Array, required: true },
});

const badge = {
    verde: 'bg-emerald-100 text-emerald-700',
    amarillo: 'bg-amber-100 text-amber-700',
    rojo: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-asphalt-900">Reportes de mercado</h1>
                    <p class="text-sm text-asphalt-500">Hueco por segmento, mejores oportunidades y evolución</p>
                </div>
                <a href="#" class="rounded-md border border-asphalt-200 px-3 py-2 text-sm text-asphalt-600 hover:bg-asphalt-50"
                    @click.prevent="router.get(route('mercado.admin'))">← Mapa</a>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Hueco por categoría -->
                <div class="rounded-xl border border-asphalt-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-asphalt-900">Hueco medio por categoría</h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-asphalt-100">
                            <tr v-for="r in porCategoria" :key="r.categoria">
                                <td class="py-2 text-asphalt-700">{{ r.categoria }}</td>
                                <td class="py-2 text-right font-medium" :class="r.hueco_medio >= 10 ? 'text-emerald-600' : 'text-asphalt-600'">{{ r.hueco_medio }}%</td>
                                <td class="py-2 text-right text-asphalt-500">{{ r.n }} · {{ r.verdes }} 🟢</td>
                            </tr>
                            <tr v-if="!porCategoria.length"><td class="py-6 text-center text-asphalt-500">Sin datos</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Hueco por segmento -->
                <div class="rounded-xl border border-asphalt-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-asphalt-900">Hueco medio por segmento</h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-asphalt-100">
                            <tr v-for="r in porSegmento" :key="r.segmento">
                                <td class="py-2 text-asphalt-700">{{ r.segmento }}</td>
                                <td class="py-2 text-right font-medium" :class="r.hueco_medio >= 10 ? 'text-emerald-600' : 'text-asphalt-600'">{{ r.hueco_medio }}%</td>
                                <td class="py-2 text-right text-asphalt-500">{{ r.n }} modelos</td>
                            </tr>
                            <tr v-if="!porSegmento.length"><td class="py-6 text-center text-asphalt-500">Sin datos</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top oportunidades -->
            <div class="mt-6 rounded-xl border border-asphalt-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-asphalt-900">Top 10 oportunidades (chollos)</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-asphalt-500">
                            <th class="pb-2">Modelo</th>
                            <th class="pb-2">Verdicto</th>
                            <th class="pb-2">Vendibilidad</th>
                            <th class="pb-2 text-right">Precio origen</th>
                            <th class="pb-2 text-right">Neto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-asphalt-100">
                        <tr v-for="m in topOportunidades" :key="m.id">
                            <td class="py-2 font-medium text-asphalt-900">{{ m.modelo }} <span v-if="m.version" class="text-asphalt-500">· {{ m.version }}</span></td>
                            <td class="py-2"><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="badge[m.veredicto]">{{ m.veredicto }}</span></td>
                            <td class="py-2 text-asphalt-700">{{ m.vendibilidad ?? '—' }}</td>
                            <td class="py-2 text-right text-asphalt-700">{{ m.precio_desde_de?.toLocaleString('es-ES') }} €</td>
                            <td class="py-2 text-right font-medium text-emerald-600">{{ m.hueco_neto_pct?.toFixed(1) }}%</td>
                        </tr>
                        <tr v-if="!topOportunidades.length"><td colspan="5" class="py-6 text-center text-asphalt-500">Sin oportunidades marcadas todavía.</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Evolución -->
            <div class="mt-6 rounded-xl border border-asphalt-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-asphalt-900">Evolución del hueco medio (histórico)</h3>
                <div v-if="evolucion.length" class="flex flex-wrap gap-2">
                    <div v-for="e in evolucion" :key="e.fecha" class="rounded-lg bg-estoril-50 px-3 py-2 text-center">
                        <div class="text-xs text-estoril-600">{{ e.fecha }}</div>
                        <div class="text-lg font-bold text-estoril-700">{{ e.hueco_medio }}%</div>
                        <div class="text-[10px] text-asphalt-500">{{ e.n }} mediciones</div>
                    </div>
                </div>
                <p v-else class="py-4 text-center text-sm text-asphalt-500">
                    Aún sin histórico — se genera cuando se importa el mapa (market:import).
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
