<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vista previa - Wallapop</h3>
            <span class="text-xs text-gray-500">Marketplace móvil</span>
        </div>

        <!-- Tarjeta estilo Wallapop -->
        <div class="bg-gray-50 rounded-lg p-3 mb-3">
            <!-- Imagen -->
            <div class="aspect-video w-full rounded bg-gray-200 mb-2"></div>

            <!-- Título -->
            <h4 class="text-sm font-bold text-gray-900 mb-1">{{ form.title || 'Título del anuncio' }}</h4>

            <!-- Precio -->
            <div class="text-lg font-bold text-gray-900 mb-1">{{ formatCurrency(car?.purchase_price) }}</div>

            <!-- Ubicación -->
            <div class="text-xs text-gray-500 mb-2">📍 Madrid, España</div>

            <!-- Descripción -->
            <p class="text-xs text-gray-600 mb-2 line-clamp-2">{{ form.description || 'Descripción del vehículo...' }}</p>

            <!-- Badges -->
            <div class="flex flex-wrap gap-1 mb-2">
                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Usado</span>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">{{ car?.year }}</span>
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
            <button class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">
                <ChatBubbleBottomCenterIcon class="h-4 w-4" />
                Contactar
            </button>
        </div>
    </div>
</template>

<script setup>
import { CameraIcon, ChatBubbleBottomCenterIcon } from '@heroicons/vue/24/outline';

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
