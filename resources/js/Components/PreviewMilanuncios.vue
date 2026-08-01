<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vista previa - Milanuncios</h3>
            <span class="text-xs text-gray-500">Portal de anuncios</span>
        </div>
        
        <!-- Logo/Logo de la marca -->
        <div class="mb-3 flex items-center gap-2">
            <div class="h-8 w-8 rounded bg-gray-200"></div>
            <h4 class="text-sm font-bold text-gray-900">{{ car?.brand }} {{ car?.model }}</h4>
        </div>
        
        <!-- Precio destacado -->
        <div class="mb-3">
            <span class="text-2xl font-bold text-indigo-600">{{ formatCurrency(car?.purchase_price) }}</span>
            <span class="ml-1 text-sm text-gray-500">Precio fijado</span>
        </div>
        
        <!-- Título -->
        <div class="mb-2">
            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Título</h5>
            <p class="text-sm text-gray-900 font-medium">{{ form.title || 'Título del anuncio' }}</p>
        </div>
        
        <!-- Descripción -->
        <div class="mb-3">
            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</h5>
            <p class="text-sm text-gray-700 leading-relaxed">{{ form.description || 'Descripción del vehículo...' }}</p>
        </div>
        
        <!-- Detalles técnicos -->
        <div class="mb-3">
            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Detalles</h5>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="bg-gray-100 px-2 py-1 rounded text-gray-700">{{ car?.year }}</span>
                <span class="bg-gray-100 px-2 py-1 rounded text-gray-700">{{ car?.mileage }} km</span>
                <span class="bg-gray-100 px-2 py-1 rounded text-gray-700">{{ car?.engine }}</span>
            </div>
        </div>
        
        <!-- Hashtags -->
        <div v-if="form.hashtags.length" class="mb-3">
            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Palabras clave</h5>
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="tag in form.hashtags"
                    :key="tag"
                    class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded"
                >#{{ tag }}</span>
            </div>
        </div>
        
        <!-- Botón de acción -->
        <div class="mt-4 pt-3 border-t border-gray-100">
            <button class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                <MessageSquareIcon class="h-4 w-4" />
                Contactar con el anuncio
            </button>
        </div>
    </div>
</template>

<script setup>
import { MessageSquareIcon } from '@heroicons/vue/24/outline';

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