<script setup>
import { useForm } from '@inertiajs/vue3';

/**
 * Formulario genérico de una fila del panel de mercado (Admin / Leads).
 * Hace PATCH a una ruta con route binding por id.
 *
 * Props:
 *  - routeName: nombre de la ruta (ej. 'mercado.admin.update')
 *  - routeParam: id del recurso
 *  - fields: [{ name, value, type: select|checkbox|textarea|input, options?, label?, placeholder? }]
 */
const props = defineProps({
    routeName: { type: String, required: true },
    routeParam: { type: [Number, String], required: true },
    fields: { type: Array, required: true },
});

const form = useForm(Object.fromEntries(props.fields.map((f) => [f.name, f.value])));

function submit() {
    form.patch(route(props.routeName, props.routeParam), { preserveScroll: true });
}
</script>

<template>
    <form @submit.prevent="submit" class="flex flex-col gap-1.5">
        <template v-for="f in fields" :key="f.name">
            <select
                v-if="f.type === 'select'"
                v-model="form[f.name]"
                class="rounded-md border-asphalt-300 text-xs"
            >
                <option v-for="o in f.options" :key="o" :value="o">{{ o }}</option>
            </select>

            <label
                v-else-if="f.type === 'checkbox'"
                class="inline-flex items-center gap-1.5 text-xs text-asphalt-600"
            >
                <input v-model="form[f.name]" type="checkbox" class="rounded border-asphalt-300 text-estoril-700">
                {{ f.label }}
            </label>

            <textarea
                v-else-if="f.type === 'textarea'"
                v-model="form[f.name]"
                rows="2"
                class="rounded-md border-asphalt-300 text-xs"
                :placeholder="f.placeholder"
            ></textarea>

            <input
                v-else
                v-model="form[f.name]"
                type="text"
                class="rounded-md border-asphalt-300 text-xs"
                :placeholder="f.placeholder"
            >
        </template>

        <button
            type="submit"
            :disabled="form.processing"
            class="self-start rounded-md bg-estoril-700 px-3 py-1 text-xs font-medium text-white disabled:opacity-50"
        >
            Guardar
        </button>
    </form>
</template>
