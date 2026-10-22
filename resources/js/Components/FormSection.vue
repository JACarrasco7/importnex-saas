<template>
    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">
                <slot name="title">{{ title }}</slot>
            </h3>
            <p v-if="description || subtitle || $slots.description" class="mt-1 text-sm text-gray-500">
                <slot name="description">{{ description || subtitle }}</slot>
            </p>
        </div>
        <div class="p-6">
            <slot />
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    // Alias: muchas páginas pasan `subtitle` (antes se ignoraba en silencio).
    subtitle: { type: String, default: '' },
});
</script>
