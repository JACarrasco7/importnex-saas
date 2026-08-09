<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { TruckIcon, CheckCircleIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    organization: Object,
});

const { t } = useTranslations();

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 15 }, (_, i) => currentYear - i);

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
    power_min: '',
    power_max: '',
    engine_type: '',
    fuel: '',
    transmission: '',
    body_type: '',
    doors: '',
    seats: '',
    color: '',
    requirements: '',
    notes: '',
    website: '', // honeypot field
});

const fuelTypes = ['Diesel', 'Gasolina', 'Híbrido', 'Híbrido enchufable', 'Eléctrico', 'Gas'];
const transmissions = ['Manual', 'Automático'];
const bodyTypes = ['Berlina', 'SUV', 'Compacto', 'Monovolumen', 'Coupe', 'Cabrio', 'Pickup', 'Familiar'];
const engineTypes = ['3 cilindros', '4 cilindros', '5 cilindros', '6 cilindros', '8 cilindros', 'Eléctrico'];
const colors = ['Negro', 'Blanco', 'Gris', 'Plata', 'Azul', 'Rojo', 'Beige', 'Marrón', 'Verde'];

const submitting = ref(false);

const submit = () => {
    if (submitting.value) return;
    submitting.value = true;
    form.post(route('public.car-request.store', props.organization.slug), {
        preserveState: false,
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="t('car_request_form.title')" />

    <PublicLayout>
        <div class="bg-linear-to-br from-estoril-50 via-white to-platinum-100 transition-colors duration-300 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800">
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
                    {{ t('car_request_form.subtitle') }}
                </p>
            </div>

            <!-- Form -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                <div class="border-b border-gray-200 bg-gray-50 px-8 py-6 dark:border-asphalt-700 dark:bg-asphalt-800">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-estoril-100 p-2 dark:bg-estoril-900/40">
                            <TruckIcon class="h-6 w-6 text-estoril-700 dark:text-estoril-300" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ t('car_request_form.section_prefs') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('car_request_form.section_required_help') }}</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-8 px-8 py-8">
                    <!-- Contact Info -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_contact') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_name') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_name')"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">{{ t('car_request_form.field_email') }}</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_email')"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_phone') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_phone')"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Car Preferences -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_car') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_brand') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.brand"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_brand')"
                                />
                                <p v-if="form.errors.brand" class="mt-1 text-sm text-rose-600">{{ form.errors.brand }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_model') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.model"
                                    type="text"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_model')"
                                />
                                <p v-if="form.errors.model" class="mt-1 text-sm text-rose-600">{{ form.errors.model }}</p>
                            </div>
                        </div>

                        <!-- Year Range -->
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_year_min') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.year_min" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                                </select>
                                <p v-if="form.errors.year_min" class="mt-1 text-sm text-rose-600">{{ form.errors.year_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_year_max') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.year_max" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                                </select>
                                <p v-if="form.errors.year_max" class="mt-1 text-sm text-rose-600">{{ form.errors.year_max }}</p>
                            </div>
                        </div>

                        <!-- Budget Range -->
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_budget_min') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.budget_min"
                                    type="number"
                                    min="0"
                                    step="500"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_budget')"
                                />
                                <p v-if="form.errors.budget_min" class="mt-1 text-sm text-rose-600">{{ form.errors.budget_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_budget_max') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.budget_max"
                                    type="number"
                                    min="0"
                                    step="500"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_budget_max')"
                                />
                                <p v-if="form.errors.budget_max" class="mt-1 text-sm text-rose-600">{{ form.errors.budget_max }}</p>
                            </div>
                        </div>

                        <!-- Mileage -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                {{ t('car_request_form.field_mileage_max') }}
                                <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <input
                                v-model="form.mileage_max"
                                type="number"
                                min="0"
                                step="10000"
                                required
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                :placeholder="t('car_request_form.placeholder_mileage')"
                            />
                            <p v-if="form.errors.mileage_max" class="mt-1 text-sm text-rose-600">{{ form.errors.mileage_max }}</p>
                        </div>
                    </div>

                    <!-- Technical Preferences -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_tech') }}</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_fuel') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.fuel" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="fuel in fuelTypes" :key="fuel" :value="fuel">{{ fuel }}</option>
                                </select>
                                <p v-if="form.errors.fuel" class="mt-1 text-sm text-rose-600">{{ form.errors.fuel }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_body_type') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.body_type" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="type in bodyTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                                <p v-if="form.errors.body_type" class="mt-1 text-sm text-rose-600">{{ form.errors.body_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_transmission') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.transmission" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="trans in transmissions" :key="trans" :value="trans">{{ trans }}</option>
                                </select>
                                <p v-if="form.errors.transmission" class="mt-1 text-sm text-rose-600">{{ form.errors.transmission }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">{{ t('car_request_form.field_engine_type') }}</label>
                                <select v-model="form.engine_type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.any_option') }}</option>
                                    <option v-for="engine in engineTypes" :key="engine" :value="engine">{{ engine }}</option>
                                </select>
                                <p v-if="form.errors.engine_type" class="mt-1 text-sm text-rose-600">{{ form.errors.engine_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_power_min') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.power_min"
                                    type="number"
                                    min="50"
                                    max="2000"
                                    step="10"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_power')"
                                />
                                <p v-if="form.errors.power_min" class="mt-1 text-sm text-rose-600">{{ form.errors.power_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_power_max') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    v-model="form.power_max"
                                    type="number"
                                    min="50"
                                    max="2000"
                                    step="10"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_power_max')"
                                />
                                <p v-if="form.errors.power_max" class="mt-1 text-sm text-rose-600">{{ form.errors.power_max }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('car_request_form.field_doors') }}</label>
                                <select v-model="form.doors" class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.any_option') }}</option>
                                    <option :value="3">3</option>
                                    <option :value="5">5</option>
                                </select>
                                <p v-if="form.errors.doors" class="mt-1 text-sm text-rose-600">{{ form.errors.doors }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_seats') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.seats" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option :value="5">5</option>
                                    <option :value="7">7</option>
                                    <option :value="9">9</option>
                                </select>
                                <p v-if="form.errors.seats" class="mt-1 text-sm text-rose-600">{{ form.errors.seats }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('car_request_form.field_color') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <select v-model="form.color" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white">
                                    <option value="">{{ t('car_request_form.select_option') }}</option>
                                    <option v-for="color in colors" :key="color" :value="color">{{ color }}</option>
                                </select>
                                <p v-if="form.errors.color" class="mt-1 text-sm text-rose-600">{{ form.errors.color }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ t('car_request_form.section_additional') }}</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                    {{ t('car_request_form.field_requirements') }}
                                    <span class="ml-0.5 text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <textarea
                                    v-model="form.requirements"
                                    rows="3"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_requirements')"
                                ></textarea>
                                <p v-if="form.errors.requirements" class="mt-1 text-sm text-rose-600">{{ form.errors.requirements }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">{{ t('car_request_form.field_notes') }}</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-estoril-600 focus:ring-estoril-600 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-white"
                                    :placeholder="t('car_request_form.placeholder_notes')"
                                ></textarea>
                                <p v-if="form.errors.notes" class="mt-1 text-sm text-rose-600">{{ form.errors.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6 dark:border-asphalt-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ t('car_request_form.privacy') }}
                        </p>
                        <button
                            type="submit"
                            :disabled="form.processing || submitting"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-estoril-800 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing || submitting" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <CheckCircleIcon v-else class="h-5 w-5" />
                            <span v-if="form.processing || submitting">{{ t('car_request_form.sending') }}</span>
                            <span v-else>{{ t('car_request_form.submit') }}</span>
                        </button>
                    </div>

                    <!-- Honeypot anti-spam field (hidden from real users) -->
                    <div class="absolute -left-[9999px] opacity-0" aria-hidden="true">
                        <label for="website">Website (do not fill)</label>
                        <input
                            id="website"
                            v-model="form.website"
                            type="text"
                            tabindex="-1"
                            autocomplete="off"
                        />
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>&copy; {{ new Date().getFullYear() }} {{ organization.name }}. {{ t('car_request_form.rights') }}</p>
            </div>
        </div>

        <!-- Floating download button for JJ Import Motors folleto -->
        <div class="fixed bottom-6 right-6 z-40">
            <a
                href="/jj-import-folleto.pdf"
                download="JJ_Import_Motors_Folleto.pdf"
                class="flex items-center gap-2 rounded-full bg-estoril-700 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-estoril-800 transition-colors"
            >
                <DocumentArrowDownIcon class="h-5 w-5" />
                <span class="hidden sm:inline">Folleto</span>
            </a>
        </div>
        </div>
    </PublicLayout>
</template>
