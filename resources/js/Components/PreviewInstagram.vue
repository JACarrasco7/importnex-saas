<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vista previa - Instagram</h3>
            <span class="text-xs text-gray-500">Post carrusel</span>
        </div>

        <!-- Carrusel de fotos -->
        <div class="grid grid-cols-3 gap-1 mb-3">
            <div
                v-for="i in 3"
                :key="i"
                class="aspect-square rounded bg-gray-200 flex items-center justify-center"
            >
                <CameraIcon class="h-6 w-6 text-gray-400" />
            </div>
        </div>

        <!-- Título -->
        <div class="mb-2">
            <h4 class="text-sm font-bold text-gray-900 mb-1">{{ form.title || 'Título del post' }}</h4>
            <p class="text-xs text-gray-600">{{ form.description || 'Descripción del vehículo...' }}</p>
        </div>

        <!-- Hashtags -->
        <div v-if="form.hashtags.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="tag in form.hashtags.slice(0, 4)"
                    :key="tag"
                    class="text-xs text-pink-600 font-semibold"
                >#{{ tag }}</span>
            </div>
        </div>

        <!-- Tips de foto -->
        <div v-if="form.photo_tips.length" class="mb-3">
            <h5 class="text-xs font-semibold text-gray-500 mb-1">Tips para fotos</h5>
            <ul class="text-xs text-gray-600 space-y-1">
                <li v-for="(tip, i) in form.photo_tips.slice(0, 2)" :key="i" class="flex items-start gap-1">
                    <CameraIcon class="h-3 w-3 flex-shrink-0 mt-0.5" />
                    {{ tip }}
                </li>
            </ul>
        </div>

        <!-- Botón -->
        <div class="pt-3 border-t border-gray-100">
            <button class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-pink-500 to-red-500 px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                <HeartIcon class="h-4 w-4" />
                Guardar
            </button>
        </div>
    </div>
</template>

<script setup>
import { CameraIcon, HeartIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    car: Object,
    form: Object,
});
</script>
