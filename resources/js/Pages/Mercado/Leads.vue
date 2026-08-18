<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MarketRowForm from './MarketRowForm.vue';

const props = defineProps({
    leads: { type: Object, required: true },
    estados: { type: Array, required: true },
    filters: { type: Object, required: true },
});

function applyFilter(key, value) {
    router.get(route('mercado.admin.leads'), { ...props.filters, [key]: value }, {
        preserveState: true,
        replace: true,
    });
}

const estadoBadge = {
    nuevo: 'bg-emerald-100 text-emerald-700',
    contactado: 'bg-blue-100 text-blue-700',
    cerrado: 'bg-asphalt-100 text-asphalt-600',
    perdido: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-asphalt-900">Leads del catálogo</h1>
                    <p class="text-sm text-asphalt-500">Interesados en el catálogo bajo pedido (origen: /mercado)</p>
                </div>
                <select
                    class="rounded-md border-asphalt-300 text-sm"
                    :value="filters.estado ?? ''"
                    @change="applyFilter('estado', $event.target.value || null)"
                >
                    <option value="">Todos los estados</option>
                    <option v-for="e in estados" :key="e" :value="e">{{ e }}</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-xl border border-asphalt-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-asphalt-200 text-sm">
                    <thead class="bg-asphalt-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-asphalt-500">
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Modelo</th>
                            <th class="px-4 py-3">Contacto</th>
                            <th class="px-4 py-3">Presupuesto</th>
                            <th class="px-4 py-3">Mensaje</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-asphalt-100">
                        <tr v-for="l in leads.data" :key="l.id" class="hover:bg-asphalt-50/50">
                            <td class="px-4 py-3 text-asphalt-500">{{ new Date(l.created_at).toLocaleDateString('es-ES') }}</td>
                            <td class="px-4 py-3 font-medium text-asphalt-900">{{ l.market_model?.modelo }}</td>
                            <td class="px-4 py-3">
                                <div v-if="l.nombre" class="text-asphalt-900">{{ l.nombre }}</div>
                                <div class="text-asphalt-600">{{ l.contacto }}</div>
                            </td>
                            <td class="px-4 py-3 text-asphalt-700">{{ l.presupuesto ? Number(l.presupuesto).toLocaleString('es-ES') + ' €' : '—' }}</td>
                            <td class="max-w-xs px-4 py-3 text-asphalt-600">{{ l.mensaje || '—' }}</td>
                            <td class="px-4 py-3">
                                <MarketRowForm
                                    :route-name="'mercado.admin.lead.update'"
                                    :route-param="l.id"
                                    :fields="[
                                        { name: 'estado', value: l.estado, type: 'select', options: estados },
                                        { name: 'nota', value: l.nota ?? '', type: 'input', placeholder: 'Nota interna...' },
                                    ]"
                                />
                            </td>
                        </tr>
                        <tr v-if="!leads.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-asphalt-500">Sin leads todavía.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="leads.links && leads.links.length > 3" class="mt-6 flex justify-center gap-1">
                <template v-for="link in leads.links" :key="link.label">
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
