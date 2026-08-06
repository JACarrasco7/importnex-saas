<template>
    <div
        :class="[
            'skeleton block',
            rounded ? 'rounded-md' : '',
            sizes[size],
        ]"
        :style="customStyle"
        aria-hidden="true"
    />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    rounded: { type: Boolean, default: true },
    width: { type: [String, Number], default: null },
    height: { type: [String, Number], default: null },
});

const sizes = {
    xs: 'h-2',
    sm: 'h-3',
    md: 'h-4',
    lg: 'h-6',
    xl: 'h-10',
};

const customStyle = computed(() => {
    const s = {};
    if (props.width) s.width = typeof props.width === 'number' ? `${props.width}px` : props.width;
    if (props.height) s.height = typeof props.height === 'number' ? `${props.height}px` : props.height;
    return s;
});
</script>
