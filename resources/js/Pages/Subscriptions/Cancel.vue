<template>
    <Head :title="t('subscription.cancel_title', 'Antes de cancelar')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                {{ t('subscription.cancel_title', 'Antes de cancelar') }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                    <div class="px-6 py-8 sm:px-10">
                        <div class="mx-auto mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                            <HeartIcon class="h-6 w-6 text-amber-600 dark:text-amber-300" />
                        </div>
                        <h1 class="text-center text-2xl font-bold text-gray-900 dark:text-white">
                            {{ t('subscription.cancel_headline', '¿Seguro que quieres cancelar?') }}
                        </h1>
                        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ t('subscription.cancel_sub', 'Sería una pena perderte. Esto es lo que pasaría si cancelas:') }}
                        </p>

                        <div class="mt-8 space-y-6">
                            <section>
                                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <ClockIcon class="h-4 w-4" />
                                    {{ t('subscription.cancel_immediate', 'En los próximos 7 días') }}
                                </h2>
                                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <li class="flex items-start gap-2">
                                        <CheckIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-500" />
                                        <span>{{ t('subscription.cancel_keep_access', 'Mantienes acceso completo a todas las funciones') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <CheckIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-500" />
                                        <span>{{ t('subscription.cancel_no_charge', 'No se realizarán más cargos a tu tarjeta') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <CheckIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-500" />
                                        <span>{{ t('subscription.cancel_reactivate_anytime', 'Puedes reactivar en cualquier momento') }}</span>
                                    </li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <CalendarIcon class="h-4 w-4" />
                                    {{ t('subscription.cancel_after_grace', 'Después de 7 días') }}
                                </h2>
                                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <li class="flex items-start gap-2">
                                        <XMarkIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-rose-500" />
                                        <span>{{ t('subscription.cancel_lose_features', 'Pierdes acceso a funciones premium (verificación AI, integraciones)') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <XMarkIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-rose-500" />
                                        <span>{{ t('subscription.cancel_lose_limits', 'Los límites vuelven al plan Free (3 coches, 1 usuario)') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <InformationCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500" />
                                        <span>{{ t('subscription.cancel_data_safe', 'Tus datos se conservan 30 días por si decides volver') }}</span>
                                    </li>
                                </ul>
                            </section>

                            <div v-if="reasons.length" class="rounded-xl bg-amber-50 p-4 dark:bg-amber-950/40">
                                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                    {{ t('subscription.cancel_help_us', '¿Hay algo que podamos mejorar?') }}
                                </p>
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                    {{ t('subscription.cancel_feedback', 'Tu feedback nos ayuda. Si cancelas, te preguntaremos el motivo.') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-10 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Link
                                :href="route('subscriptions.index')"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-asphalt-600 dark:bg-asphalt-700 dark:text-gray-200 dark:hover:bg-asphalt-600"
                            >
                                <ArrowLeftIcon class="h-4 w-4" />
                                {{ t('subscription.cancel_keep', 'Mantener mi suscripción') }}
                            </Link>
                            <Link
                                :href="route('billing.portal')"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-500"
                            >
                                <XCircleIcon class="h-4 w-4" />
                                {{ t('subscription.cancel_confirm', 'Confirmar cancelación') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    HeartIcon,
    ClockIcon,
    CalendarIcon,
    CheckIcon,
    XMarkIcon,
    InformationCircleIcon,
    ArrowLeftIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

defineProps({
    subscription: { type: Object, default: null },
});

// Razones comunes para cancelar (futuro: mostrar encuesta)
const reasons = [
    'too_expensive',
    'not_using',
    'missing_features',
    'switching_competitor',
];

const { t } = useTranslations();
</script>
