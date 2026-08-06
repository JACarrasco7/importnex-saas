<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    BuildingOfficeIcon,
    TruckIcon,
    UserGroupIcon,
    CreditCardIcon,
    CheckCircleIcon,
    ChevronRightIcon,
    XMarkIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    progress: {
        type: Object,
        default: () => null,
    },
});

const steps = computed(() => [
    {
        id: 1,
        title: t('onboarding.steps.organization', 'Crear organización'),
        description: t('onboarding.steps.organization_desc', 'Configura tu empresa para empezar'),
        completed: props.progress?.step_organization_created ?? false,
        icon: BuildingOfficeIcon,
        action: null, // Ya completado
    },
    {
        id: 2,
        title: t('onboarding.steps.first_vehicle', 'Añadir primer vehículo'),
        description: t('onboarding.steps.first_vehicle_desc', 'Importa o crea tu primer coche'),
        completed: props.progress?.step_first_vehicle_added ?? false,
        icon: TruckIcon,
        action: route('cars.index'),
    },
    {
        id: 3,
        title: t('onboarding.steps.team', 'Invitar al equipo'),
        description: t('onboarding.steps.team_desc', 'Añade colaboradores a tu equipo'),
        completed: props.progress?.step_team_invited ?? false,
        icon: UserGroupIcon,
        action: route('users.index'), // Asumiendo que existe esta ruta
    },
    {
        id: 4,
        title: t('onboarding.steps.plan', 'Seleccionar plan'),
        description: t('onboarding.steps.plan_desc', 'Elige el plan que mejor se adapte'),
        completed: props.progress?.step_plan_selected ?? false,
        icon: CreditCardIcon,
        action: route('subscriptions.index'),
    },
]);

const completedCount = computed(() => steps.value.filter(s => s.completed).length);
const progressPercent = computed(() => props.progress?.progress ?? 0);
const isCompleted = computed(() => props.progress?.is_completed ?? false);

const skipOnboarding = () => {
    if (confirm(t('onboarding.skip_confirm', '¿Seguro que quieres saltar el onboarding?'))) {
        router.post(route('onboarding.skip'));
    }
};
</script>

<template>
    <div v-if="progress && !isCompleted" class="bg-gradient-to-br from-estoril-50 to-platinum-50 rounded-xl border border-estoril-200 p-6 dark:from-asphalt-800 dark:to-asphalt-700 dark:border-asphalt-600">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-estoril-100">
                        <SparklesIcon class="h-5 w-5 text-estoril-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            {{ t('onboarding.checklist_title', 'Completa tu configuración') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ t('onboarding.checklist_subtitle', '4 pasos para empezar a usar JJ Import Motors') }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    :title="t('onboarding.skip', 'Saltar')"
                    @click="skipOnboarding"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- Progress Bar -->
            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                    <span>{{ completedCount }} / 4 {{ t('onboarding.completed', 'completados') }}</span>
                    <span>{{ progressPercent }}%</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-asphalt-700">
                    <div
                        class="h-full bg-gradient-to-r from-estoril-600 to-estoril-800 transition-all duration-500 ease-out"
                        :style="{ width: `${progressPercent}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- Steps List -->
        <ul class="space-y-3">
            <li
                v-for="step in steps"
                :key="step.id"
                class="flex items-center justify-between rounded-lg border transition-all"
                :class="{
                    'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20': step.completed,
                    'border-gray-200 bg-white dark:border-asphalt-600 dark:bg-asphalt-800': !step.completed,
                }"
            >
                <div class="flex items-center gap-3 p-3">
                    <div
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-green-100': step.completed,
                            'bg-estoril-100': !step.completed,
                        }"
                    >
                        <component
                            :is="step.completed ? CheckCircleIcon : step.icon"
                            class="h-5 w-5"
                            :class="{
                                'text-green-600': step.completed,
                                'text-estoril-600': !step.completed,
                            }"
                        />
                    </div>
                    <div>
                        <p
                            class="text-sm font-medium"
                            :class="{
                                'text-gray-900 dark:text-white': !step.completed,
                                'text-gray-700 dark:text-gray-300 line-through': step.completed,
                            }"
                        >
                            {{ step.title }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ step.description }}
                        </p>
                    </div>
                </div>

                <div v-if="!step.completed && step.action" class="pr-3">
                    <Link
                        :href="step.action"
                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors hover:bg-gray-100 dark:hover:bg-asphalt-700"
                        :class="{
                            'text-estoril-700': !step.completed,
                        }"
                    >
                        {{ t('onboarding.complete_step', 'Completar') }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </Link>
                </div>
            </li>
        </ul>

        <!-- CTA -->
        <div v-if="!isCompleted && progress?.current_step > 1" class="mt-4 pt-4 border-t border-gray-200 dark:border-asphalt-600">
            <Link
                :href="route('onboarding.index')"
                class="flex items-center justify-center gap-2 w-full rounded-lg bg-estoril-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-estoril-500 transition-colors"
            >
                {{ t('onboarding.continue_wizard', 'Continuar asistente') }}
                <ChevronRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>