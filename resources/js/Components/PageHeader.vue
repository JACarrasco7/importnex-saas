<template>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 v-if="title || $slots.title" class="text-2xl font-bold tracking-tight text-gray-900">
                <slot name="title">{{ title }}</slot>
            </h1>
            <p v-if="subtitle || $slots.subtitle" class="mt-1 text-sm text-gray-500">
                <slot name="subtitle">{{ subtitle }}</slot>
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});
</script>
