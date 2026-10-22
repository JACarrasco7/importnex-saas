<script setup>
defineProps({
    id: { type: String, default: null },
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <section :id="id" class="scroll-mt-20 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-asphalt-100 sm:p-6">
        <header class="mb-4 flex items-start gap-3">
            <span v-if="icon" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-estoril-50 text-estoril-700">
                <component :is="icon" class="h-5 w-5" aria-hidden="true" />
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-asphalt-900 sm:text-lg">{{ title }}</h2>
                <p v-if="subtitle" class="mt-0.5 text-sm text-asphalt-500">{{ subtitle }}</p>
            </div>
        </header>
        <slot />
    </section>
</template>
