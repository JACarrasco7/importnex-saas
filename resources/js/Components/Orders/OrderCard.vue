<script setup>
defineProps({
    id: { type: String, default: null },
    title: { type: String, required: true },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <section :id="id" class="scroll-mt-24 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
        <header class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 dark:border-asphalt-700">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                <component :is="icon" v-if="icon" class="h-4 w-4 text-estoril-600 dark:text-estoril-300" aria-hidden="true" />
                {{ title }}
            </h3>
            <div class="flex items-center gap-2"><slot name="actions" /></div>
        </header>
        <div class="p-5"><slot /></div>
    </section>
</template>
