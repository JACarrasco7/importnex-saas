<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircleIcon, TruckIcon, PhoneIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    organization: Object,
});
</script>

<template>
    <Head title="Solicitud enviada" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200">
                <div class="px-8 py-12 text-center">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100">
                        <CheckCircleIcon class="h-12 w-12 text-emerald-600" />
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900">
                        ¡Solicitud recibida!
                    </h1>
                    <p class="mt-4 text-lg text-gray-600">
                        Hemos recibido tu solicitud. Nuestro equipo analizará tus preferencias y te contactará pronto con las mejores opciones.
                    </p>

                    <div v-if="organization.logo" class="mx-auto mt-8 h-16 w-16 overflow-hidden rounded-xl bg-white shadow-md">
                        <img :src="organization.logo" :alt="organization.name" class="h-full w-full object-cover" />
                    </div>
                    <p class="mt-4 text-sm font-semibold uppercase tracking-wider text-gray-500">
                        {{ organization.name }}
                    </p>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-8 py-6">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900">
                        ¿Qué pasa ahora?
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-indigo-100 p-2">
                                <TruckIcon class="h-5 w-5 text-indigo-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Revisamos tu solicitud</p>
                                <p class="text-sm text-gray-500">Nuestro equipo analizará tus preferencias en detalle.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-indigo-100 p-2">
                                <PhoneIcon class="h-5 w-5 text-indigo-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Te contactamos</p>
                                <p class="text-sm text-gray-500">Nos pondremos en contacto contigo en breve con opciones.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-indigo-100 p-2">
                                <EnvelopeIcon class="h-5 w-5 text-indigo-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Recibes propuestas</p>
                                <p class="text-sm text-gray-500">Te enviaremos las mejores opciones según tu presupuesto.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-8 py-6 text-center">
                    <Link
                        :href="route('public.car-request.index', organization.slug)"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Enviar otra solicitud
                    </Link>
                </div>
            </div>

            <div class="mt-8 text-center text-sm text-gray-500">
                <p>&copy; 2026 {{ organization.name }}. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</template>
