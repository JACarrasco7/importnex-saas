<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeftIcon, CheckIcon, BanknotesIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    car: Object,
    clients: Array,
});

const form = useForm({
    brand: props.car.brand,
    model: props.car.model,
    version: props.car.version,
    year: props.car.year,
    mileage: props.car.mileage,
    fuel: props.car.fuel,
    transmission: props.car.transmission,
    cv: props.car.cv,
    co2: props.car.co2,
    color: props.car.color,
    purchase_price: props.car.purchase_price,
    new_price: props.car.new_price,
    transport: props.car.transport,
    itv_fee: props.car.itv_fee,
    coc_fee: props.car.coc_fee,
    dgt_fees: props.car.dgt_fees,
    professional_fees: props.car.professional_fees,
    deposit: props.car.deposit,
    vin: props.car.vin,
    city: props.car.city,
    status: props.car.status,
    traffic_light: props.car.traffic_light,
    client_id: props.car.client_id,
    notes: props.car.notes,
});

const { currency } = useFormat();

const totalCost = computed(() =>
    (Number(form.purchase_price) || 0) +
    (Number(form.transport) || 0) +
    (Number(form.itv_fee) || 0) +
    (Number(form.coc_fee) || 0) +
    (Number(form.dgt_fees) || 0) +
    (Number(form.professional_fees) || 0)
);

const submit = () => form.patch(route('cars.update', props.car.id));

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <Head :title="`Edit: ${car.brand} ${car.model}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit: {{ car.brand }} {{ car.model }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="`Edit ${car.brand} ${car.model}`" :subtitle="`VIN ${car.vin || '—'}`">
                    <template #actions>
                        <Link :href="route('cars.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                    </template>
                </PageHeader>

                <form @submit.prevent="submit" class="space-y-6">
                    <FormSection title="Vehicle data">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FormField label="Brand" required><input v-model="form.brand" type="text" required :class="inputClass" /></FormField>
                            <FormField label="Model" required><input v-model="form.model" type="text" required :class="inputClass" /></FormField>
                            <FormField label="Version"><input v-model="form.version" type="text" :class="inputClass" /></FormField>
                            <FormField label="Year (MM/YYYY)" required><input v-model="form.year" type="text" pattern="\d{2}/\d{4}" required :class="inputClass" /></FormField>
                            <FormField label="VIN"><input v-model="form.vin" type="text" :class="[inputClass, 'font-mono']" /></FormField>
                            <FormField label="Color"><input v-model="form.color" type="text" :class="inputClass" /></FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Technical specs">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <FormField label="Mileage (km)"><input v-model="form.mileage" type="number" :class="inputClass" /></FormField>
                            <FormField label="Power (CV)"><input v-model="form.cv" type="number" :class="inputClass" /></FormField>
                            <FormField label="CO₂ (g/km)"><input v-model="form.co2" type="number" :class="inputClass" /></FormField>
                            <FormField label="Fuel" required>
                                <select v-model="form.fuel" required :class="inputClass">
                                    <option>Diesel</option><option>Gasoline</option><option>Hybrid</option><option>Electric</option>
                                </select>
                            </FormField>
                            <FormField label="Transmission" required>
                                <select v-model="form.transmission" required :class="inputClass">
                                    <option>Manual</option><option>Automatic</option>
                                </select>
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Pricing & costs">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <FormField label="Purchase price" required><input v-model="form.purchase_price" type="number" step="0.01" required :class="inputClass" /></FormField>
                            <FormField label="New price"><input v-model="form.new_price" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="Transport"><input v-model="form.transport" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="ITV fee"><input v-model="form.itv_fee" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="COC fee"><input v-model="form.coc_fee" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="DGT fees"><input v-model="form.dgt_fees" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="Professional fees"><input v-model="form.professional_fees" type="number" step="0.01" :class="inputClass" /></FormField>
                            <FormField label="Deposit"><input v-model="form.deposit" type="number" step="0.01" :class="inputClass" /></FormField>
                        </div>
                        <div class="mt-4 flex items-center gap-2 rounded-lg bg-estoril-50 px-4 py-3 text-sm">
                            <BanknotesIcon class="h-5 w-5 text-estoril-600" />
                            <span class="font-medium text-estoril-900">Estimated total cost:</span>
                            <span class="font-bold text-estoril-700">{{ currency(totalCost) }}</span>
                        </div>
                    </FormSection>

                    <FormSection title="Status & location">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FormField label="Status" required>
                                <select v-model="form.status" required :class="inputClass">
                                    <option>Located</option><option>Valuing</option><option>Offered</option><option>Reserved</option>
                                    <option>Purchased</option><option>In_transit</option><option>Processing</option>
                                    <option>Pending review</option><option>Verifying</option>
                                    <option>Delivered</option><option>Discarded</option>
                                </select>
                            </FormField>
                            <FormField label="Traffic light" required>
                                <select v-model="form.traffic_light" required :class="inputClass">
                                    <option value="green">Green</option><option value="amber">Amber</option><option value="red">Red</option><option value="neutral">Neutral</option>
                                </select>
                            </FormField>
                            <FormField label="City"><input v-model="form.city" type="text" :class="inputClass" /></FormField>
                            <FormField label="Client" v-if="clients?.length">
                                <select v-model="form.client_id" :class="inputClass">
                                    <option :value="null">— No client —</option>
                                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Notes">
                        <FormField label="Internal notes"><textarea v-model="form.notes" rows="3" :class="inputClass" /></FormField>
                    </FormSection>

                    <div class="flex items-center justify-end gap-3 rounded-2xl bg-gray-50 px-6 py-4 ring-1 ring-gray-200">
                        <Link :href="route('cars.show', car.id)" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" />
                            {{ form.processing ? 'Updating...' : 'Update car' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
