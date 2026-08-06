<template>
    <div aria-live="polite" class="pointer-events-none fixed inset-0 z-[100] flex flex-col items-end justify-end gap-3 p-4 sm:p-6">
        <TransitionGroup
            tag="div"
            class="flex w-full flex-col items-end gap-3"
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition-all duration-200 ease-in absolute"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-x-4"
        >
            <Toast
                v-for="t in toasts"
                :key="t.id"
                :type="t.type"
                :title="t.title"
                :dismissible="t.dismissible"
                @close="dismiss(t.id)"
            >
                {{ t.message }}
            </Toast>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { useToast } from '@/Composables/useToast';
import Toast from '@/Components/Toast.vue';

const { toasts, dismiss } = useToast();
</script>
