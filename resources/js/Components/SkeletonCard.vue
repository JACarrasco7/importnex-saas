<script setup>
import Skeleton from '@/Components/Skeleton.vue';

defineProps({
    showImage: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700" aria-hidden="true">
        <!-- Image skeleton -->
        <div v-if="showImage" class="aspect-video overflow-hidden bg-gray-100 dark:bg-asphalt-700">
            <Skeleton width="100%" height="100%" :rounded="false" />
        </div>

        <!-- Content skeleton -->
        <div class="p-4 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 space-y-2">
                    <Skeleton size="sm" width="70%" />
                    <Skeleton size="xs" width="50%" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Skeleton size="xs" width="60px" height="20px" :rounded="true" />
                <Skeleton size="xs" width="50px" height="20px" :rounded="true" />
            </div>

            <div class="space-y-1">
                <Skeleton size="xs" width="40%" />
                <Skeleton size="md" width="100%" />
            </div>

            <!-- Action buttons skeleton -->
            <div class="flex gap-2 pt-2">
                <Skeleton size="sm" width="80px" height="32px" :rounded="true" />
                <Skeleton size="sm" width="80px" height="32px" :rounded="true" />
            </div>
        </div>
    </div>
</template>
