<template>
    <div class="card-premium p-5 space-y-3" aria-hidden="true">
        <div class="flex items-center gap-3">
            <Skeleton size="lg" width="40px" height="40px" :rounded="true" />
            <div class="flex-1 space-y-2">
                <Skeleton size="sm" width="60%" />
                <Skeleton size="xs" width="40%" />
            </div>
        </div>
        <Skeleton size="md" width="100%" />
        <Skeleton size="md" width="85%" />
        <div class="flex gap-2 pt-2">
            <Skeleton size="sm" width="80px" height="32px" />
            <Skeleton size="sm" width="80px" height="32px" />
        </div>
    </div>
</template>

<script setup>
import Skeleton from '@/Components/Skeleton.vue';
</script>
