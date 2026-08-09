<template>
    <div>
        <label v-if="label" :for="id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>
        <slot />
        <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    id: { type: String, default: () => `f-${Math.random().toString(36).slice(2, 9)}` },
});

const id = computed(() => props.id);
</script>
