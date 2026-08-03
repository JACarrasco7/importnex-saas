<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { TruckIcon, CheckCircleIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    organization: Object,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    brand: '',
    model: '',
    year_min: '',
    year_max: '',
    budget_min: '',
    budget_max: '',
    mileage_max: '',
    fuel: '',
    transmission: '',
    body_type: '',
    doors: '',
    seats: '',
    color: '',
    requirements: '',
    notes: '',
});

const fuelTypes = ['Diesel', 'Gasolina', 'Híbrido', 'Eléctrico', 'Gas'];
const transmissions = ['Manual', 'Automático'];
const bodyTypes = ['Berlina', 'SUV', 'Compacto', 'Monovolumen', 'Coupe', 'Cabrio', 'Pickup'];
const colors = ['Negro', 'Blanco', 'Gris', 'Plata', 'Azul', 'Rojo', 'Beige', 'Marrón'];

const submit = () => {
    form.post(route('public.car-request.store', props.organization.slug), {
        preserveState: false,
    });
};
</script>

<template>
    <Head title="Buscar mi coche perfecto" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-10 text-center">
                <div v-if="organization.logo" class="mx-auto mb-6 h-20 w-20 overflow-hidden rounded-2xl bg-white shadow-lg">
                    <img :src="organization.logo" :alt="organization.name" class="h-full w-full object-cover" />
                </div>
                <h1 class="text-4xl font-bold text-gray-900">
                    {{ organization.name }}
                </h1>
                <p class="mt-3 text-lg text-gray-600">
                    Encuentramos tu coche perfecto.
                </p>
            </div>

            <!-- Form -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200">
                <div class="border-b border-gray-200 bg-gray-50 px-8 py-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-indigo-100 p-2">
                            <TruckIcon class="h-6 w-6 text-indigo-600" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Tus preferencias</h2>
                            <p class="text-sm text-gray-500">Todos los campos marcados con * son obligatorios</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-8 px-8 py-8">
                    <!-- Contact Info -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900">Tus datos</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre completo *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Tu nombre"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="tu@email.com"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="+34 600 000 000"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Car Preferences -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900">El coche que buscas</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                                <input
                                    v-model="form.brand"
                                    type="text"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="BMW, Mercedes, Audi..."
                                />
                                <p v-if="form.errors.brand" class="mt-1 text-sm text-rose-600">{{ form.errors.brand }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                                <input
                                    v-model="form.model"
                                    type="text"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Serie 3, Clase C, A4..."
                                />
                                <p v-if="form.errors.model" class="mt-1 text-sm text-rose-600">{{ form.errors.model }}</p>
                            </div>
                        </div>

                        <!-- Year Range -->
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Año mínimo</label>
                                <select v-model="form.year_min" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option v-for="year in 2027" :key="year" :value="2028 - year">{{ 2028 - year }}</option>
                                </select>
                                <p v-if="form.errors.year_min" class="mt-1 text-sm text-rose-600">{{ form.errors.year_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Año máximo</label>
                                <select v-model="form.year_max" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option v-for="year in 2027" :key="year" :value="2028 - year">{{ 2028 - year }}</option>
                                </select>
                                <p v-if="form.errors.year_max" class="mt-1 text-sm text-rose-600">{{ form.errors.year_max }}</p>
                            </div>
                        </div>

                        <!-- Budget Range -->
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Presupuesto mínimo (€)</label>
                                <input
                                    v-model="form.budget_min"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="10000"
                                />
                                <p v-if="form.errors.budget_min" class="mt-1 text-sm text-rose-600">{{ form.errors.budget_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Presupuesto máximo (€)</label>
                                <input
                                    v-model="form.budget_max"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="30000"
                                />
                                <p v-if="form.errors.budget_max" class="mt-1 text-sm text-rose-600">{{ form.errors.budget_max }}</p>
                            </div>
                        </div>

                        <!-- Mileage -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kilometraje máximo</label>
                            <input
                                v-model="form.mileage_max"
                                type="number"
                                min="0"
                                step="10000"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="100000 km"
                            />
                            <p v-if="form.errors.mileage_max" class="mt-1 text-sm text-rose-600">{{ form.errors.mileage_max }}</p>
                        </div>
                    </div>

                    <!-- Technical Preferences -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900">Preferencias técnicas</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Combustible</label>
                                <select v-model="form.fuel" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option v-for="fuel in fuelTypes" :key="fuel" :value="fuel">{{ fuel }}</option>
                                </select>
                                <p v-if="form.errors.fuel" class="mt-1 text-sm text-rose-600">{{ form.errors.fuel }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cambio</label>
                                <select v-model="form.transmission" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option v-for="trans in transmissions" :key="trans" :value="trans">{{ trans }}</option>
                                </select>
                                <p v-if="form.errors.transmission" class="mt-1 text-sm text-rose-600">{{ form.errors.transmission }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Carrocería</label>
                                <select v-model="form.body_type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option v-for="type in bodyTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                                <p v-if="form.errors.body_type" class="mt-1 text-sm text-rose-600">{{ form.errors.body_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Puertas</label>
                                <select v-model="form.doors" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option :value="3">3</option>
                                    <option :value="5">5</option>
                                </select>
                                <p v-if="form.errors.doors" class="mt-1 text-sm text-rose-600">{{ form.errors.doors }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Plazas</label>
                                <select v-model="form.seats" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option :value="5">5</option>
                                    <option :value="7">7</option>
                                    <option :value="9">9</option>
                                </select>
                                <p v-if="form.errors.seats" class="mt-1 text-sm text-rose-600">{{ form.errors.seats }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                                <select v-model="form.color" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Cualquiera</option>
                                    <option v-for="color in colors" :key="color" :value="color">{{ color }}</option>
                                </select>
                                <p v-if="form.errors.color" class="mt-1 text-sm text-rose-600">{{ form.errors.color }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900">Información adicional</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Requisitos obligatorios</label>
                                <textarea
                                    v-model="form.requirements"
                                    rows="3"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="GPS, cámara de aparcamiento, asientos de cuero, etc..."
                                ></textarea>
                                <p v-if="form.errors.requirements" class="mt-1 text-sm text-rose-600">{{ form.errors.requirements }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notas adicionales</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Cualquier otra información que consideres relevante..."
                                ></textarea>
                                <p v-if="form.errors.notes" class="mt-1 text-sm text-rose-600">{{ form.errors.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6">
                        <p class="text-sm text-gray-500">
                            Tus datos serán utilizados solo para contactarte sobre tu solicitud.
                        </p>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <CheckCircleIcon v-if="!form.processing" class="h-5 w-5" />
                            <span v-if="form.processing">Enviando...</span>
                            <span v-else>Enviar solicitud</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>&copy; 2026 {{ organization.name }}. Todos los derechos reservados.</p>
            </div>
        </div>

        <!-- Floating download button for JJ Import Motors folleto -->
        <div class="fixed bottom-6 right-6 z-40">
            <a
                :href="route('jj-import.folleto')"
                target="_blank"
                class="flex items-center gap-2 rounded-full bg-cyan-600 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-cyan-700 transition-colors"
            >
                <DocumentArrowDownIcon class="h-5 w-5" />
                <span class="hidden sm:inline">Folleto</span>
            </a>
        </div>
    </div>
</template>
