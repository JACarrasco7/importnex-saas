<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vista previa - Coches.net</h3>
            <span class="text-xs text-gray-500">Portal especializado</span>
        </div>

        <!-- Tarjeta principal -->
        <div class="bg-gray-50 rounded-lg p-3 mb-3">
            <!-- Imagen del coche -->
            <div class="aspect-video w-full rounded bg-gray-200 mb-2"></div>

            <!-- Título -->
            <h4 class="text-sm font-bold text-gray-900 mb-1">{{ form.title || 'Título del anuncio' }}</h4>

            <!-- Precio -->
            <div class="text-lg font-bold text-gray-900 mb-2">{{ formatCurrency(car?.purchase_price) }}</div>

            <!-- Descripción corta -->
            <p class="text-xs text-gray-600 mb-2 line-clamp-2">{{ form.description || 'Descripción del vehículo...' }}</p>

            <!-- Ficha técnica -->
            <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Año:</span>
                    <span class="font-medium">{{ car?.year }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Km:</span>
                    <span class="font-medium">{{ car?.mileage }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Motor:</span>
                    <span class="font-medium text-sm">{{ car?.engine }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Combustible:</span>
                    <span class="font-medium">Diesel</span>
                </div>
            </div>
        </div>

        <!-- Hashtags -->
        <div v-if="form.hashtags.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="tag in form.hashtags"
                    :key="tag"
                    class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded"
                >#{{ tag }}</span>
            </div>
        </div>

        <!-- Botón -->
        <div class="pt-3 border-t border-gray-100">
            <button class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                Ver detalles
            </button>
        </div>
    </div>
</template>

<script setup>
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
