<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, TruckIcon, CheckIcon, LinkIcon, SparklesIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    clients: Array,
});

const form = useForm({
    brand: '',
    model: '',
    version: '',
    year: '',
    mileage: 0,
    fuel: 'Diesel',
    transmission: 'Manual',
    cv: 0,
    co2: 0,
    consumption: '',
    owners: 1,
    doors: '5',
    seats: 5,
    color: '',
    purchase_price: 0,
    new_price: 0,
    transport: 0,
    itv_fee: 0,
    coc_fee: 0,
    dgt_fees: 0,
    professional_fees: 0,
    deposit: 0,
    vin: '',
    vat_scenario: 'margin',
    city: '',
    lat: null,
    lng: null,
    status: 'Located',
    traffic_light: 'neutral',
    description: '',
    notes: '',
    url_link: '',
    client_id: null,
});

// Scraping state
const scrapeUrl = ref('');
const isScraping = ref(false);
const scrapeError = ref(null);
const scrapeSuccess = ref(null);
const lastProvider = ref(null);
const scrapedPhotos = ref([]);

const submit = () => form.post(route('cars.store'));

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
const labelClass = 'block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1';

// Friendly hostname detection for the URL preview badge.
const detectedHost = computed(() => {
    try {
        return new URL(scrapeUrl.value.trim()).hostname.replace(/^www\./, '');
    } catch {
        return null;
    }
});

const supportedHosts = [
    'mobile.de', 'autoscout24.de', 'autoscout24.es',
    'coches.com', 'milanuncios.com', 'wallapop.com',
    'coches.net', 'autovit.ro', 'olx.ro',
];
// Expose to template (Vue 3 auto-exposes top-level consts in <script setup>)
defineExpose({ supportedHosts });
const isHostSupported = computed(() => {
    if (!detectedHost.value) return null;
    return supportedHosts.some(h => detectedHost.value === h || detectedHost.value.endsWith('.' + h));
});

// Map a year like 2020 into the MM/YYYY format the form expects.
const formatYear = (year) => {
    if (!year || typeof year !== 'number' || year < 1990 || year > 2030) return '';
    return `01/${year}`;
};

const statusOptions = ['Located', 'Valuing', 'Offered', 'Reserved', 'Purchased', 'In_transit', 'Processing', 'Pending_review', 'Verifying', 'Delivered', 'Discarded'];

const scrapeFromUrl = async () => {
    scrapeError.value = null;
    scrapeSuccess.value = null;
    scrapedPhotos.value = [];

    const url = scrapeUrl.value.trim();
    if (!url) {
        scrapeError.value = 'Introduce una URL.';
        return;
    }

    isScraping.value = true;
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(route('cars.scrape-url'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ url }),
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            scrapeError.value = payload.error || `Error ${response.status}`;
            return;
        }

        const data = payload.data || {};
        const filled = [];

        if (data.brand)         { form.brand = data.brand; filled.push('brand'); }
        if (data.model)         { form.model = data.model; filled.push('model'); }
        if (data.version)       { form.version = data.version; filled.push('version'); }
        if (data.year)          { form.year = formatYear(data.year); filled.push('year'); }
        if (data.mileage != null){ form.mileage = data.mileage; filled.push('mileage'); }
        if (data.fuel)          { form.fuel = data.fuel; filled.push('fuel'); }
        if (data.transmission)  { form.transmission = data.transmission; filled.push('transmission'); }
        if (data.cv != null)    { form.cv = data.cv; filled.push('cv'); }
        if (data.co2 != null)   { form.co2 = data.co2; filled.push('co2'); }
        if (data.color)         { form.color = data.color; filled.push('color'); }
        if (data.purchase_price != null) { form.purchase_price = data.purchase_price; filled.push('purchase_price'); }
        if (data.city)          { form.city = data.city; filled.push('city'); }
        if (data.description)   { form.description = data.description; filled.push('description'); }
        if (url)                { form.url_link = url; filled.push('url_link'); }

        lastProvider.value = payload.provider;
        scrapedPhotos.value = payload.photos || [];

        const photosMsg = scrapedPhotos.value.length > 0
            ? ` + ${scrapedPhotos.value.length} fotos detectadas`
            : '';
        scrapeSuccess.value = payload.cached
            ? `Datos extraídos de caché (${filled.length} campos${photosMsg}). Proveedor: ${payload.provider}`
            : `${filled.length} campos rellenados${photosMsg}. Proveedor: ${payload.provider}`;
    } catch (err) {
        scrapeError.value = err?.message || 'Error inesperado al extraer datos.';
    } finally {
        isScraping.value = false;
    }
};
</script>

<template>
    <Head :title="t('cars.add')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('cars.add') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('cars.add_title')" :subtitle="t('cars.add_subtitle_full')" icon-class="h-6 w-6">
                    <template #actions>
                        <Link :href="route('cars.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection :title="t('cars.import_from_url')" :description="t('cars.import_from_url_desc')">
                        <div class="space-y-3">
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <LinkIcon class="h-4 w-4" />
                                    </span>
                                    <input
                                        v-model="scrapeUrl"
                                        type="url"
                                        placeholder="https://mobile.de/..."
                                        :disabled="isScraping"
                                        class="block w-full rounded-lg border-gray-300 pl-9 pr-20 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 disabled:bg-gray-100"
                                        @keydown.enter.prevent="scrapeFromUrl"
                                    />
                                    <span
                                        v-if="detectedHost"
                                        class="pointer-events-none absolute inset-y-0 right-2 flex items-center"
                                    >
                                        <span
                                            v-if="isHostSupported === true"
                                            class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                                        >✓ {{ detectedHost }}</span>
                                        <span
                                            v-else-if="isHostSupported === false"
                                            class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700"
                                        >⚠ {{ detectedHost }}</span>
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="scrapeFromUrl"
                                    :disabled="isScraping || !scrapeUrl.trim()"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:cursor-not-allowed disabled:bg-gray-300"
                                >
                                    <svg v-if="isScraping" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <SparklesIcon v-else class="h-4 w-4" />
                                    {{ isScraping ? 'Extrayendo...' : 'Auto-fill' }}
                                </button>
                            </div>

                            <div v-if="detectedHost && isHostSupported === false" class="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                Este sitio no está en la lista de soportados. El intento puede fallar.
                                Portales válidos: {{ supportedHosts.join(', ') }}.
                            </div>

                            <div v-if="scrapeError" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                                {{ scrapeError }}
                            </div>
                            <div v-if="scrapeSuccess" class="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">
                                {{ scrapeSuccess }}
                            </div>

                            <!-- Photo preview gallery -->
                            <div v-if="scrapedPhotos.length > 0" class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <div class="mb-2 flex items-center gap-2 text-xs font-medium text-gray-700">
                                    <PhotoIcon class="h-4 w-4" />
                                    {{ scrapedPhotos.length }} {{ scrapedPhotos.length === 1 ? 'foto detectada' : 'fotos detectadas' }}
                                </div>
                                <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8">
                                    <a
                                        v-for="(photo, idx) in scrapedPhotos"
                                        :key="idx"
                                        :href="photo"
                                        target="_blank"
                                        rel="noopener"
                                        class="block aspect-square overflow-hidden rounded-md border border-gray-200 bg-white hover:opacity-80"
                                    >
                                        <img :src="photo" :alt="`Foto ${idx + 1}`" class="h-full w-full object-cover" loading="lazy" />
                                    </a>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    Las fotos están disponibles como URLs. Se descargarán al guardar el coche.
                                </p>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection :title="t('cars.vehicle_identification')" :description="t('cars.vehicle_identification_desc')">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FormField label="Brand" required :error="form.errors.brand">
                                <input v-model="form.brand" type="text" required :class="inputClass" />
                            </FormField>
                            <FormField label="Model" required :error="form.errors.model">
                                <input v-model="form.model" type="text" required :class="inputClass" />
                            </FormField>
                            <FormField label="Version" :error="form.errors.version">
                                <input v-model="form.version" type="text" :class="inputClass" />
                            </FormField>
                            <FormField label="Year (MM/YYYY)" required hint="e.g. 07/2020">
                                <input v-model="form.year" type="text" pattern="\d{2}/\d{4}" required :class="inputClass" placeholder="07/2020" />
                            </FormField>
                            <FormField label="VIN" :error="form.errors.vin">
                                <input v-model="form.vin" type="text" :class="[inputClass, 'font-mono']" />
                            </FormField>
                            <FormField label="Color">
                                <input v-model="form.color" type="text" :class="inputClass" />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Technical specs" description="Engine, transmission, dimensions">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <FormField label="Mileage (km)">
                                <input v-model="form.mileage" type="number" :class="inputClass" />
                            </FormField>
                            <FormField label="Power (CV)">
                                <input v-model="form.cv" type="number" :class="inputClass" />
                            </FormField>
                            <FormField label="CO₂ (g/km)">
                                <input v-model="form.co2" type="number" :class="inputClass" />
                            </FormField>
                            <FormField label="Consumption">
                                <input v-model="form.consumption" type="text" :class="inputClass" placeholder="L/100km" />
                            </FormField>
                            <FormField label="Fuel" required>
                                <select v-model="form.fuel" required :class="inputClass">
                                    <option>Diesel</option>
                                    <option>Gasoline</option>
                                    <option>Hybrid</option>
                                    <option>Electric</option>
                                </select>
                            </FormField>
                            <FormField label="Transmission" required>
                                <select v-model="form.transmission" required :class="inputClass">
                                    <option>Manual</option>
                                    <option>Automatic</option>
                                </select>
                            </FormField>
                            <FormField label="Owners">
                                <input v-model="form.owners" type="number" :class="inputClass" />
                            </FormField>
                            <FormField label="Doors">
                                <select v-model="form.doors" :class="inputClass">
                                    <option>3</option><option>4</option><option>5</option>
                                </select>
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Pricing & costs" description="Purchase, fees and taxes">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <FormField label="Purchase price" required hint="EUR">
                                <input v-model="form.purchase_price" type="number" step="0.01" required :class="inputClass" />
                            </FormField>
                            <FormField label="New price" hint="EUR">
                                <input v-model="form.new_price" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                            <FormField label="Transport" hint="EUR">
                                <input v-model="form.transport" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                            <FormField label="ITV fee" hint="EUR">
                                <input v-model="form.itv_fee" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                            <FormField label="COC fee" hint="EUR">
                                <input v-model="form.coc_fee" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                            <FormField label="DGT fees" hint="EUR">
                                <input v-model="form.dgt_fees" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                            <FormField label="Professional fees" hint="EUR">
                                <input v-model="form.professional_fees" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                            <FormField label="Deposit" hint="EUR">
                                <input v-model="form.deposit" type="number" step="0.01" :class="inputClass" />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection :title="t('cars.location_status')">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FormField label="City">
                                <input v-model="form.city" type="text" :class="inputClass" />
                            </FormField>
                            <FormField label="VAT scenario">
                                <select v-model="form.vat_scenario" :class="inputClass">
                                    <option value="margin">Margin</option>
                                    <option value="standard">Standard</option>
                                </select>
                            </FormField>
                            <FormField label="Status">
                                <select v-model="form.status" :class="inputClass">
                                    <option v-for="s in statusOptions" :key="s" :value="s">{{ t('cars.status.' + s) }}</option>
                                </select>
                            </FormField>
                            <FormField :label="t('cars.traffic_light')">
                                <select v-model="form.traffic_light" :class="inputClass">
                                    <option value="green">{{ t('cars.light.green') }}</option>
                                    <option value="amber">{{ t('cars.light.amber') }}</option>
                                    <option value="red">{{ t('cars.light.red') }}</option>
                                    <option value="neutral">{{ t('cars.light.neutral') }}</option>
                                </select>
                            </FormField>
                            <FormField :label="t('cars.client')" v-if="clients?.length">
                                <select v-model="form.client_id" :class="inputClass">
                                    <option :value="null">— {{ t('cars.no_client') }} —</option>
                                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection :title="t('cars.description')" :description="t('cars.description_desc')">
                        <FormField :label="t('cars.notes')">
                            <textarea v-model="form.notes" rows="3" :class="inputClass" />
                        </FormField>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('cars.index')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ t('common.cancel') }}</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Create car' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
