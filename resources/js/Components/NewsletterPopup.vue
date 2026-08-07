<script setup>
import { ref } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const show = ref(false);

const close = () => {
    show.value = false;
    localStorage.setItem('newsletter-popup-dismissed', Date.now().toString());
};

onMounted(() => {
    // Mostrar solo si no se ha cerrado en los últimos 30 días
    const dismissed = localStorage.getItem('newsletter-popup-dismissed');
    if (!dismissed || (Date.now() - parseInt(dismissed)) > 30 * 24 * 60 * 60 * 1000) {
        // Mostrar después de 30 segundos en la página
        setTimeout(() => {
            show.value = true;
        }, 30000);
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50" @click="close" />

                <!-- Modal -->
                <div class="relative max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Close button -->
                    <button
                        @click="close"
                        class="absolute top-4 right-4 p-2 rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <XMarkIcon class="h-5 w-5 text-gray-500" />
                    </button>

                    <!-- Content -->
                    <div class="p-8">
                        <div class="text-center">
                            <!-- Icon -->
                            <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-estoril-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-estoril-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                ¿Te gustaría recibir ofertas exclusivas?
                            </h3>

                            <p class="text-gray-600 mb-6">
                                Suscríbete a nuestra newsletter y recibe las mejores ofertas de importación de vehículos directamente en tu email.
                            </p>

                            <!-- Form -->
                            <form @submit.prevent="close" class="space-y-4">
                                <input
                                    type="email"
                                    placeholder="Tu email"
                                    required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-estoril-500 focus:ring-estoril-500"
                                />

                                <button
                                    type="submit"
                                    class="w-full bg-estoril-600 text-white py-3 rounded-lg font-semibold hover:bg-estoril-500 transition-colors"
                                >
                                    Suscribirme gratis
                                </button>
                            </form>

                            <!-- Legal text -->
                            <p class="mt-4 text-xs text-gray-500">
                                Al suscribirte aceptas nuestra política de privacidad. Puedes darte de baja en cualquier momento.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>