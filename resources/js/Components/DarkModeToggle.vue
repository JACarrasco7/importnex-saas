<template>
    <button
        type="button"
        @click="toggle"
        :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-asphalt-200/70 bg-white text-asphalt-600 shadow-sm transition hover:bg-asphalt-50 hover:text-asphalt-900 active:scale-95 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-asphalt-200 dark:hover:bg-asphalt-700 dark:hover:text-white"
    >
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="rotate-90 opacity-0 scale-50"
            enter-to-class="rotate-0 opacity-100 scale-100"
            leave-active-class="absolute inset-0 transition-all duration-150 ease-in"
            leave-from-class="rotate-0 opacity-100 scale-100"
            leave-to-class="-rotate-90 opacity-0 scale-50"
            mode="out-in"
        >
            <SunIcon v-if="isDark" class="h-5 w-5 dark:text-asphalt-200" aria-hidden="true" />
            <MoonIcon v-else class="h-5 w-5 dark:text-asphalt-200" aria-hidden="true" />
        </Transition>
    </button>
</template>

<script setup>
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline';
import { useDarkMode } from '@/Composables/useDarkMode';

const { isDark, toggle } = useDarkMode();
</script>
