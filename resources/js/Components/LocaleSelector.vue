<script setup>
import { usePage, Inertia } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const isSaving = ref(false);

const locale = computed(() => page.props.locale || 'en');

const isEnglish = computed(() => locale.value === 'en');
const isSpanish = computed(() => locale.value === 'es');

async function changeLocale(newLocale) {
    if (isSaving.value || locale.value === newLocale) return;

    isSaving.value = true;

    try {
        // If user is logged in, update via API
        if (page.props.auth?.user) {
            await Inertia.put(route('locale.update'), {
                locale: newLocale,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    // Locale will be updated by Inertia after page reload
                },
            });
        } else {
            // For guests, just update localStorage
            localStorage.setItem('locale', newLocale);
            // Update page prop for immediate UI update
            page.props.locale = newLocale;
        }
    } catch (error) {
        console.error('Error updating locale:', error);
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-800 rounded-full p-1">
        <button
            :disabled="isSaving || locale !== 'en'"
            @click="changeLocale('en')"
            class="px-3 py-1 text-sm font-medium rounded-full transition-colors"
            :class="{
                'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow': locale === 'en',
                'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100': locale !== 'en',
                'opacity-50 cursor-not-allowed': isSaving || (locale !== 'en' && isSaving),
            }"
        >
            EN
        </button>
        <span class="text-gray-400 dark:text-gray-500">|</span>
        <button
            :disabled="isSaving || locale !== 'es'"
            @click="changeLocale('es')"
            class="px-3 py-1 text-sm font-medium rounded-full transition-colors"
            :class="{
                'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow': locale === 'es',
                'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100': locale !== 'es',
                'opacity-50 cursor-not-allowed': isSaving || (locale !== 'es' && isSaving),
            }"
        >
            ES
        </button>
    </div>
</template>
