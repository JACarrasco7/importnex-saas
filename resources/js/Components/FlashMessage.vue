<script setup>
defineProps({
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
    },
    message: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div
        v-if="message"
        :class="[
            'border-l-4 p-4 rounded',
            type === 'success' && 'bg-green-50 border-green-400',
            type === 'error' && 'bg-red-50 border-red-400',
            type === 'warning' && 'bg-yellow-50 border-yellow-400',
            type === 'info' && 'bg-blue-50 border-blue-400',
        ]"
        role="alert"
    >
        <p
            :class="[
                'text-sm',
                type === 'success' && 'text-green-700',
                type === 'error' && 'text-red-700',
                type === 'warning' && 'text-yellow-700',
                type === 'info' && 'text-blue-700',
            ]"
        >
            {{ message }}
        </p>
    </div>
</template>
