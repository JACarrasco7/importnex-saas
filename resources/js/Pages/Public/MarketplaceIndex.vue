<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon,
    EyeIcon,
    ShieldCheckIcon,
    CheckBadgeIcon,
    TruckIcon,
    ChartBarIcon,
    PhoneIcon,
    EnvelopeIcon,
    StarIcon,
    ClockIcon,
    BanknotesIcon,
    ArrowRightIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    cars: Object,
    verdicts: Array,
    lights: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const verdictFilter = ref(props.filters?.verdict || 'all');
const lightFilter = ref(props.filters?.traffic_light || '');
const minPrice = ref(props.filters?.min_price || '');
const maxPrice = ref(props.filters?.max_price || '');

const { currency, trafficLightVariant, verdictVariant } = useFormat();

const filteredCars = computed(() => {
    let result = props.cars.data || [];
    if (verdictFilter.value && verdictFilter.value !== 'all') {
        result = result.filter(c => c.verdict === verdictFilter.value);
    }
    if (lightFilter.value && lightFilter.value !== '') {
        result = result.filter(c => c.traffic_light === lightFilter.value);
    }
    if (minPrice.value) {
        const min = parseFloat(minPrice.value);
        if (!isNaN(min)) {
            result = result.filter(c => (c.purchase_price || 0) >= min);
        }
    }
    if (maxPrice.value) {
        const max = parseFloat(maxPrice.value);
        if (!isNaN(max)) {
            result = result.filter(c => (c.purchase_price || 0) <= max);
        }
    }
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(c =>
            (c.brand && c.brand.toLowerCase().includes(term)) ||
            (c.model && c.model.toLowerCase().includes(term))
        );
    }
    return result;
});

// Trust signals
const trustItems = [
    { icon: ShieldCheckIcon, label: 'Coches verificados', description: 'Cada coche pasa 9 controles antes de salir al mercado.' },
    { icon: ChartBarIcon, label: 'Precio investigado', description: 'Comparado con el mercado para que pagues lo justo.' },
    { icon: TruckIcon, label: 'Importacion llave en mano', description: 'Transporte, ITV y matriculacion incluidos.' },
];

// How it works
const howItWorks = [
    { step: '1', title: 'Elige tu coche', description: 'Filtra por marca, presupuesto y semaforo.' },
    { step: '2', title: 'Recibe el informe', description: 'Te enviamos el informe tecnico completo.' },
    { step: '3', title: 'Lo importamos por ti', description: 'Tramitacion, transporte y matriculacion incluidos.' },
];
</script>

<template>
    <Head title="Verified Cars - Marketplace" />

    <div class="min-h-screen bg-white">
        <!-- Public header -->
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <Link :href="route('marketplace.index')" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-sm">
                        <ShieldCheckIcon class="h-5 w-5 text-white" />
                    </span>
                    <div>
                        <p class="text-base font-bold leading-tight text-gray-900">Verified Cars</p>
                        <p class="text-[11px] leading-tight text-gray-500">by Importnex</p>
                    </div>
                </Link>
                <nav class="flex items-center gap-3">
                    <a href="#catalogo" class="hidden text-sm font-medium text-gray-700 hover:text-gray-900 sm:inline">Catalogo</a>
                    <a href="#como-funciona" class="hidden text-sm font-medium text-gray-700 hover:text-gray-900 sm:inline">Como funciona</a>
                    <a href="#contacto" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                        <PhoneIcon class="h-4 w-4" />
                        Contactar
                    </a>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50">
            <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-emerald-200/30 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-teal-200/30 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-semibold text-emerald-700">
                        <SparklesIcon class="h-4 w-4" />
                        Coches investigados desde Alemania
                    </span>
                    <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        Tu proximo coche,
                        <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">ya verificado</span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600">
                        Solo coches con informe tecnico completo: 9 puntos de investigacion, comparables de mercado y tramites de importacion resueltos.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="#catalogo" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-emerald-700">
                            Ver coches disponibles
                            <ArrowRightIcon class="h-4 w-4" />
                        </a>
                        <a href="#contacto" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                            <PhoneIcon class="h-4 w-4" />
                            Habla con un asesor
                        </a>
                    </div>

                    <!-- Trust signals -->
                    <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div v-for="item in trustItems" :key="item.label" class="rounded-2xl bg-white/70 p-5 text-left shadow-sm ring-1 ring-gray-200 backdrop-blur">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100">
                                <component :is="item.icon" class="h-5 w-5 text-emerald-600" />
                            </div>
                            <p class="mt-3 text-sm font-semibold text-gray-900">{{ item.label }}</p>
                            <p class="mt-1 text-xs text-gray-600">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="como-funciona" class="border-y border-gray-200 bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Como funciona</h2>
                    <p class="mt-3 text-base text-gray-600">De la eleccion a la entrega, en 3 pasos.</p>
                </div>
                <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div v-for="item in howItWorks" :key="item.step" class="relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <span class="absolute -top-4 left-6 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white shadow-sm">
                            {{ item.step }}
                        </span>
                        <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CATALOG -->
        <section id="catalogo" class="py-16">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Catalogo</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ cars.total || 0 }} coches disponibles, todos con informe tecnico completo.</p>
                    </div>
                    <a href="#contacto" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        <PhoneIcon class="h-4 w-4" />
                        No encuentras el tuyo?
                    </a>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 gap-3 rounded-2xl bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">Buscar</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Marca, modelo, VIN..."
                                class="block w-full rounded-lg border-gray-200 pl-9 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">Semaforo</label>
                        <select v-model="lightFilter" class="block w-full rounded-lg border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Todos</option>
                            <option v-for="light in lights" :key="light" :value="light">
                                {{ light === 'green' ? '🟢' : light === 'amber' ? '🟡' : light === 'red' ? '🔴' : '⚪' }} {{ light }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">Presupuesto</label>
                        <div class="flex gap-2">
                            <input v-model.number="minPrice" type="number" min="0" step="500" placeholder="Min €" class="block w-full rounded-lg border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                            <input v-model.number="maxPrice" type="number" min="0" step="500" placeholder="Max €" class="block w-full rounded-lg border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>
                    </div>
                </div>

                <!-- Tabs (verdict quick filter) -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="v in [{id:'all',label:'Todos'},{id:'Buy',label:'Recomendados'},{id:'Buy if price drops',label:'Si baja precio'},{id:'Doubtful',label:'Dudosos'},{id:'Discard',label:'Descartados'}]"
                        :key="v.id"
                        @click="verdictFilter = v.id"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            verdictFilter === v.id
                                ? 'bg-gray-900 text-white shadow-sm'
                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50',
                        ]"
                    >
                        {{ v.label }}
                    </button>
                </div>

                <!-- Cars grid -->
                <div v-if="filteredCars.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="car in filteredCars"
                        :key="car.id"
                        :href="route('marketplace.show', car.id)"
                        class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:shadow-lg hover:ring-emerald-300"
                    >
                        <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                            <img
                                v-if="car.photos && car.photos.length > 0"
                                :src="car.photos[0].startsWith('http') ? car.photos[0] : `/storage/${car.photos[0]}`"
                                :alt="`${car.brand} ${car.model}`"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div v-else class="flex h-full items-center justify-center text-6xl text-gray-400">🚗</div>
                            <div class="absolute top-3 left-3">
                                <Badge :variant="trafficLightVariant(car.traffic_light)" dot>{{ car.traffic_light }}</Badge>
                            </div>
                            <div class="absolute top-3 right-3">
                                <Badge :variant="verdictVariant(car.verdict)" size="sm">{{ car.verdict }}</Badge>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-lg font-semibold text-gray-900 group-hover:text-emerald-700">
                                        {{ car.brand }} {{ car.model }}
                                    </h3>
                                    <p v-if="car.version" class="truncate text-sm text-gray-500">{{ car.version }}</p>
                                </div>
                            </div>
                            <dl class="mt-4 grid grid-cols-3 gap-3 text-xs">
                                <div>
                                    <dt class="text-gray-500">Ano</dt>
                                    <dd class="font-semibold text-gray-900">{{ car.year }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Km</dt>
                                    <dd class="font-semibold text-gray-900">{{ car.mileage ? (car.mileage / 1000).toFixed(0) + 'k' : 'N/D' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Combust.</dt>
                                    <dd class="truncate font-semibold text-gray-900">{{ car.fuel || 'N/D' }}</dd>
                                </div>
                            </dl>
                            <div class="mt-5 flex items-end justify-between border-t border-gray-100 pt-4">
                                <div>
                                    <p class="text-xs text-gray-500">Precio desde</p>
                                    <p class="text-2xl font-extrabold text-gray-900">{{ currency(car.purchase_price) }}</p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    Ver informe
                                    <ArrowRightIcon class="h-3 w-3" />
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty state -->
                <div v-else class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                        <SparklesIcon class="h-8 w-8 text-emerald-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">No hay coches disponibles ahora mismo</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-gray-600">
                        Estamos buscando activamente nuevos coches para ti. Cuentanos que buscas y te avisaremos cuando aparezca uno que encaje.
                    </p>
                    <a href="#contacto" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        <PhoneIcon class="h-4 w-4" />
                        Pedir que te avisen
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA / Contacto -->
        <section id="contacto" class="bg-gradient-to-br from-gray-900 to-emerald-900 py-16">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    No has encontrado tu coche?
                </h2>
                <p class="mx-auto mt-3 max-w-2xl text-lg text-gray-300">
                    Cuentanos que buscas. Rastreamos el mercado aleman cada semana y te enviamos opciones que encajan contigo.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a href="https://wa.me/34675701439" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-emerald-400">
                        <PhoneIcon class="h-5 w-5" />
                        WhatsApp: 675 70 14 39
                    </a>
                    <a href="tel:+34675701439" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <PhoneIcon class="h-5 w-5" />
                        Llamar ahora
                    </a>
                    <a href="mailto:jjimportmotors@gmail.com" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <EnvelopeIcon class="h-5 w-5" />
                        Email
                    </a>
                </div>
                <p class="mt-8 text-sm text-gray-400">
                    Importnex &middot; JJ Import Motors &middot; Servicio llave en mano Alemania → España
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-gray-50 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600">
                            <ShieldCheckIcon class="h-4 w-4 text-white" />
                        </span>
                        <span class="text-sm font-semibold text-gray-900">Verified Cars by Importnex</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        &copy; {{ new Date().getFullYear() }} Importnex. Coches investigados, valorados y listos para entregar.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>