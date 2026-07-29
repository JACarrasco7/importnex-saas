<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, SparklesIcon, ExclamationTriangleIcon, CheckCircleIcon, InformationCircleIcon, ChartBarIcon, LightBulbIcon, FlagIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    car: Object,
});

const { trafficLightVariant } = useFormat();
</script>

<template>
    <Head :title="`Verify ${car.brand} ${car.model}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verify: {{ car.brand }} {{ car.model }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="`${car.brand} ${car.model}`" :subtitle="`${car.year} · Current status: ${car.status}`">
                    <template #actions>
                        <Link :href="route('cars.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                    </template>
                </PageHeader>

                <!-- Status info -->
                <div v-if="car.status === 'Pending review'" class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-start gap-3">
                        <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 text-amber-600" />
                        <p class="text-sm text-amber-900">AI verification completed. Review the suggestions below and decide whether to apply them.</p>
                    </div>
                </div>
                <div v-else-if="car.status === 'Verifying'" class="overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-3">
                        <InformationCircleIcon class="h-5 w-5 flex-shrink-0 text-blue-600" />
                        <p class="text-sm text-blue-900">Verification in progress. Refresh this page in a moment.</p>
                    </div>
                </div>
                <div v-else class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <div class="flex items-start gap-3">
                        <SparklesIcon class="h-5 w-5 flex-shrink-0 text-indigo-600" />
                        <div>
                            <p class="text-sm text-gray-900">This car has not been verified yet. Send it to Claude for analysis.</p>
                            <form method="POST" :action="route('cars.verify-sync', car.id)" class="mt-4">
                                <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    <SparklesIcon class="h-4 w-4" />
                                    Verify with Claude AI
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- AI Suggestions -->
                <div v-if="car.valuation || car.recommendation" class="space-y-6">
                    <FormSection v-if="car.valuation" title="Valuation">
                        <div class="flex items-start gap-3">
                            <ChartBarIcon class="h-5 w-5 flex-shrink-0 text-indigo-600" />
                            <p class="text-sm text-gray-900">{{ car.valuation }}</p>
                        </div>
                    </FormSection>

                    <FormSection v-if="car.recommendation" title="Recommendation">
                        <div class="flex items-start gap-3">
                            <LightBulbIcon class="h-5 w-5 flex-shrink-0 text-purple-600" />
                            <p class="text-sm text-gray-900">{{ car.recommendation }}</p>
                        </div>
                    </FormSection>

                    <FormSection v-if="car.red_flags?.length" title="Red flags">
                        <ul class="space-y-2">
                            <li v-for="flag in car.red_flags" :key="flag" class="flex items-start gap-2 rounded-lg bg-rose-50 p-3">
                                <FlagIcon class="h-4 w-4 flex-shrink-0 text-rose-600" />
                                <span class="text-sm text-rose-900">{{ flag }}</span>
                            </li>
                        </ul>
                    </FormSection>

                    <FormSection v-if="car.tips?.length" title="Tips">
                        <ul class="space-y-2">
                            <li v-for="tip in car.tips" :key="tip" class="flex items-start gap-2 rounded-lg bg-emerald-50 p-3">
                                <CheckCircleIcon class="h-4 w-4 flex-shrink-0 text-emerald-600" />
                                <span class="text-sm text-emerald-900">{{ tip }}</span>
                            </li>
                        </ul>
                    </FormSection>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
