<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vista previa - TikTok</h3>
            <span class="text-xs text-gray-500">Video corto</span>
        </div>

        <!-- Video mockup -->
        <div class="relative mb-3">
            <div class="aspect-video w-full rounded-lg bg-linear-to-br from-estoril-900 to-pink-800 flex items-center justify-center">
                <PlayIcon class="h-12 w-12 text-white opacity-50" />
            </div>
            <div class="absolute bottom-3 right-3 flex items-center gap-1 bg-black/70 px-2 py-1 rounded">
                <span class="text-xs text-white font-bold">0:15</span>
                <PlayIcon class="h-3 w-3 text-white" />
            </div>
        </div>

        <!-- Título -->
        <div class="mb-2">
            <h4 class="text-sm font-bold text-gray-900 mb-1">{{ form.title || 'Título del video' }}</h4>
            <p class="text-xs text-gray-600">{{ form.description || 'Descripción del video...' }}</p>
        </div>

        <!-- Hashtags -->
        <div v-if="form.hashtags.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="tag in form.hashtags.slice(0, 3)"
                    :key="tag"
                    class="text-xs text-estoril-600 font-semibold"
                >#{{ tag }}</span>
            </div>
        </div>

        <!-- Tips de foto -->
        <div v-if="form.photo_tips.length" class="mb-3">
            <h5 class="text-xs font-semibold text-gray-500 mb-1">Tips para el video</h5>
            <ul class="text-xs text-gray-600 space-y-1">
                <li v-for="(tip, i) in form.photo_tips.slice(0, 2)" :key="i" class="flex items-start gap-1">
                    <CameraIcon class="h-3 w-3 shrink-0 mt-0.5" />
                    {{ tip }}
                </li>
            </ul>
        </div>

        <!-- Botón -->
        <div class="pt-3 border-t border-gray-100">
            <button class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500">
                Ver más
            </button>
        </div>
    </div>
</template>

<script setup>
import { CameraIcon, PlayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    car: Object,
    form: Object,
});

const formatCurrency = (value) => {
    if (!value) return '0 €';
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
    }).format(value);
};
</script>
