<script setup>
/**
 * FloatingActionGrid
 *
 * A reusable container for 1+ floating action buttons (FABs).
 * Stacks them vertically (default) or horizontally, anchored to a corner.
 *
 * Slots:
 *   - default: one or more <button> / <a> elements
 *
 * Props:
 *   - position: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left' (default: bottom-right)
 *   - orientation: 'vertical' (default) | 'horizontal'
 *   - spacing: tailwind spacing class between buttons (default: 'gap-3')
 *   - offset: tailwind class for inset (default: 'bottom-6 right-6')
 *   - zIndex: tailwind z-index class (default: 'z-50')
 */
defineProps({
    position: {
        type: String,
        default: 'bottom-right',
        validator: (v) => ['bottom-right', 'bottom-left', 'top-right', 'top-left'].includes(v),
    },
    orientation: {
        type: String,
        default: 'vertical',
        validator: (v) => ['vertical', 'horizontal'].includes(v),
    },
    spacing: { type: String, default: 'gap-3' },
    offset: { type: String, default: 'bottom-6 right-6' },
    zIndex: { type: String, default: 'z-50' },
});

// Map position to tailwind inset classes
const positionMap = {
    'bottom-right': 'bottom-6 right-6',
    'bottom-left': 'bottom-6 left-6',
    'top-right': 'top-6 right-6',
    'top-left': 'top-6 left-6',
};
</script>

<template>
    <div
        :class="[
            'fixed flex pointer-events-none',
            positionMap[position] ?? 'bottom-6 right-6',
            orientation === 'horizontal' ? 'flex-row' : 'flex-col-reverse',
            spacing,
            zIndex,
        ]"
    >
        <!-- Re-enable pointer events only on the children -->
        <div class="contents [&>*]:pointer-events-auto">
            <slot />
        </div>
    </div>
</template>