<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vista previa - Facebook</h3>
            <span class="text-xs text-gray-500">Publicación</span>
        </div>
        
        <!-- Header del post -->
        <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-full bg-gray-200"></div>
            <div class="flex-1">
                <div class="text-sm font-semibold text-gray-900">JJ Import Motors</div>
                <div class="text-xs text-gray-500">Hace 2 horas</div>
            </div>
        </div>
        
        <!-- Imagen del post -->
        <div class="mb-3">
            <div class="aspect-video w-full rounded-lg bg-gray-200"></div>
        </div>
        
        <!-- Título -->
        <div class="mb-2">
            <h4 class="text-sm font-bold text-gray-900 mb-1">{{ form.title || 'Título del anuncio' }}</h4>
            <p class="text-xs text-gray-700">{{ form.description || 'Descripción del vehículo...' }}</p>
        </div>
        
        <!-- Hashtags -->
        <div v-if="form.hashtags.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="tag in form.hashtags.slice(0, 4)"
                    :key="tag"
                    class="text-xs text-blue-600 font-semibold"
                >#{{ tag }}</span>
            </div>
        </div>
        
        <!-- Acciones -->
        <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
            <button class="flex items-center gap-1 text-xs text-gray-500 hover:text-blue-600">
                <ThumbUpIcon class="h-4 w-4" />
                Me gusta
            </button>
            <button class="flex items-center gap-1 text-xs text-gray-500 hover:text-blue-600">
                <ChatBubbleBottomCenterIcon class="h-4 w-4" />
                Comentar
            </button>
            <button class="flex items-center gap-1 text-xs text-gray-500 hover:text-blue-600">
                <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                Compartir
            </button>
        </div>
    </div>
</template>

<script setup>
import {
    ThumbUpIcon,
    ChatBubbleBottomCenterIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    car: Object,
    form: Object,
});
</script>