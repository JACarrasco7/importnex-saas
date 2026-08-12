<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BuildingOfficeIcon,
    UserGroupIcon,
    CreditCardIcon,
    CheckCircleIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    XMarkIcon,
    SparklesIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    progress: Object,
    stepData: Object,
    user: Object,
});

const currentStep = computed(() => props.progress?.current_step || 1);
const isLastStep = computed(() => currentStep.value >= 4);
const progressPercent = computed(() => props.progress?.progress || 0);

const loading = ref(false);

const skipOnboarding = () => {
    if (confirm(t('onboarding.skip_confirm', '¿Seguro que quieres saltar el onboarding? Puedes completarlo más tarde desde el dashboard.'))) {
        loading.value = true;
        router.post(route('onboarding.skip'), {}, {
            onFinish: () => {
                loading.value = false;
            },
        });
    }
};

const advanceStep = () => {
    loading.value = true;

    router.post(route('onboarding.update'), {
        step: currentStep.value,
    }, {
        onFinish: () => {
            loading.value = false;
        },
    });
};

const stepIcon = (step) => {
    switch (step) {
        case 1:
            return BuildingOfficeIcon;
        case 2:
            return TruckIcon;
        case 3:
            return UserGroupIcon;
        case 4:
            return CreditCardIcon;
        default:
            return CheckCircleIcon;
    }
};

const stepTitle = (step) => {
    switch (step) {
        case 1:
            return 'Organización creada';
        case 2:
            return 'Primer vehículo añadido';
        case 3:
            return 'Equipo invitado';
        case 4:
            return 'Plan seleccionado';
        default:
            return 'Completado';
    }
};

const goToDashboard = () => {
    router.visit(route('dashboard'));
};

const goToCarsIndex = () => {
    router.visit(route('cars.index'));
};
</script>

<template>
    <Head :title="t('onboarding.title', 'Bienvenido a JJ Import Motors')" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-linear-to-br from-estoril-50 via-white to-platinum-100 dark:from-asphalt-900 dark:via-asphalt-900 dark:to-asphalt-800 py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ stepData?.title }}
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {{ stepData?.subtitle }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        :title="t('onboarding.skip', 'Saltar onboarding')"
                        @click="skipOnboarding"
                    >
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>{{ t('onboarding.step', 'Paso') }} {{ currentStep }} de 4</span>
                        <span>{{ progressPercent }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-asphalt-700">
                        <div
                            class="h-full bg-linear-to-r from-estoril-600 to-estoril-800 transition-all duration-500 ease-out"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </div>
                </div>

                <!-- Steps Timeline -->
                <div class="mb-12">
                    <div class="flex items-center justify-between">
                        <template v-for="step in 4" :key="step">
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 transition-all"
                                    :class="{
                                        'border-estoril-600 bg-estoril-600 text-white': currentStep > step || (currentStep === step && progressPercent > 75),
                                        'border-estoril-600 bg-white text-estoril-600 dark:bg-asphalt-800 dark:text-estoril-300': currentStep === step,
                                        'border-gray-300 bg-gray-50 text-gray-400 dark:border-asphalt-600 dark:bg-asphalt-800 dark:text-gray-500': currentStep < step,
                                    }"
                                >
                                    <component :is="stepIcon(step)" class="h-6 w-6" v-if="currentStep >= step" />
                                    <span v-else class="text-sm font-medium">{{ step }}</span>
                                </div>
                                <span
                                    class="mt-2 text-xs font-medium"
                                    :class="{
                                        'text-estoril-600': currentStep >= step,
                                        'text-gray-400': currentStep < step,
                                    }"
                                >
                                    {{ stepTitle(step) }}
                                </span>
                            </div>
                            <div
                                v-if="step < 4"
                                class="flex-1 h-0.5 mx-4 transition-all"
                                :class="{
                                    'bg-estoril-600': currentStep > step,
                                    'bg-gray-200': currentStep <= step,
                                }"
                            />
                        </template>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="bg-white rounded-2xl shadow-lg p-8 dark:bg-asphalt-800">
                    <!-- Step 1: Organization -->
                    <div v-if="currentStep === 1" class="text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-estoril-100">
                            <BuildingOfficeIcon class="h-10 w-10 text-estoril-600" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ t('onboarding.step1_done_title') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ t('onboarding.step1_done_desc', { name: progress?.organization?.name }) }}
                        </p>
                        <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <CheckCircleIcon class="h-5 w-5 text-green-600" />
                            <span>{{ t('onboarding.org_created') }}</span>
                        </div>
                    </div>

                    <!-- Step 2: First Vehicle -->
                    <div v-else-if="currentStep === 2" class="text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full" :class="stepData?.carsCount > 0 ? 'bg-green-100' : 'bg-estoril-100'">
                            <CarIcon class="h-10 w-10" :class="stepData?.carsCount > 0 ? 'text-green-600' : 'text-estoril-600'" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ stepData?.carsCount > 0 ? t('onboarding.step2_title_done') : t('onboarding.step2_title_empty') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ stepData?.subtitle }}
                        </p>
                        <div v-if="stepData?.carsCount > 0" class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
                            <CheckCircleIcon class="h-5 w-5 text-green-600" />
                            <span>{{ t('onboarding.step2_count_desc', { count: stepData.carsCount }) }}</span>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-6 py-3 text-sm font-semibold text-white hover:bg-estoril-500 transition-colors"
                            @click="goToCarsIndex"
                        >
                            <TruckIcon class="h-5 w-5" />
                            {{ t('onboarding.go_to_vehicles') }}
                        </button>
                    </div>

                    <!-- Step 3: Team Invite -->
                    <div v-else-if="currentStep === 3" class="text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-estoril-100">
                            <UserGroupIcon class="h-10 w-10 text-estoril-600" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            Invita a tu equipo
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Trabaja en equipo añadiendo colaboradores a <span class="font-semibold">{{ stepData?.orgName }}</span>.
                        </p>
                        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600 dark:bg-asphalt-700 dark:text-gray-400 mb-6">
                            <p>💡 <strong>Tip:</strong> Puedes añadir colaboradores desde Configuración > Equipo en cualquier momento.</p>
                        </div>
                        <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <SparklesIcon class="h-5 w-5 text-estoril-600" />
                            <span>{{ t('onboarding.optional_step') }}</span>
                        </div>
                    </div>

                    <!-- Step 4: Plan Selection -->
                    <div v-else-if="currentStep === 4" class="text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-estoril-100">
                            <CreditCardIcon class="h-10 w-10 text-estoril-600" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ t('onboarding.select_plan_title') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ t('onboarding.select_plan_desc', { plan: stepData?.currentPlan || 'trial' }) }}
                        </p>
                        <div class="bg-linear-to-r from-estoril-50 to-platinum-50 rounded-lg p-4 text-sm dark:from-asphalt-700 dark:to-asphalt-800 mb-6">
                            <p class="text-gray-700 dark:text-gray-300" v-html="t('onboarding.trial_banner')"></p>
                        </div>
                        <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <SparklesIcon class="h-5 w-5 text-estoril-600" />
                            <span>{{ t('onboarding.change_anytime') }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6 dark:border-asphalt-700">
                        <button
                            v-if="currentStep > 1 && currentStep < 5"
                            type="button"
                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors text-sm font-medium"
                            @click="router.get(route('onboarding.index'))"
                        >
                            <ArrowLeftIcon class="h-4 w-4 inline mr-1" />
                            Atrás
                        </button>
                        <div v-else></div>

                        <button
                            v-if="!isLastStep"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-6 py-3 text-sm font-semibold text-white hover:bg-estoril-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loading || !stepData?.canAdvance"
                            @click="advanceStep"
                        >
                            {{ currentStep === 1 ? 'Continuar' : 'Siguiente' }}
                            <ArrowRightIcon class="h-4 w-4" />
                        </button>
                        <button
                            v-else
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-6 py-3 text-sm font-semibold text-white hover:bg-estoril-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loading"
                            @click="advanceStep"
                        >
                            Completar
                            <CheckCircleIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
