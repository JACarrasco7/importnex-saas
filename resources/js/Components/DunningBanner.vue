<template>
    <div
        v-if="visible"
        class="overflow-hidden rounded-2xl border border-rose-300 bg-linear-to-r from-rose-50 to-red-50 p-4 shadow-sm dark:border-rose-800 dark:from-rose-950/40 dark:to-red-950/40"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-900/60">
                <ExclamationTriangleIcon class="h-5 w-5 text-rose-600 dark:text-rose-300" />
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-rose-900 dark:text-rose-200">
                    {{ t('dunning.title', 'No pudimos procesar tu último pago') }}
                </h3>
                <p class="mt-1 text-sm text-rose-700 dark:text-rose-300">
                    {{ t('dunning.description', 'Tu suscripción sigue activa temporalmente, pero reducirán las funciones si no lo actualizas.') }}
                </p>
                <p v-if="paymentFailedAt" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ t('dunning.failed_at', 'Último intento fallido:') }}
                    <time :datetime="paymentFailedAt">{{ formattedDate }}</time>
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Link
                    :href="route('billing.portal')"
                    class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-asphalt-900"
                >
                    <CreditCardIcon class="h-4 w-4" />
                    {{ t('dunning.update_payment', 'Actualizar método de pago') }}
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ExclamationTriangleIcon, CreditCardIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    paymentFailed: { type: Boolean, default: false },
    paymentFailedAt: { type: String, default: null },
    graceDays: { type: Number, default: 7 },
});

const { t } = useTranslations();

const visible = computed(() => props.paymentFailed === true);

const formattedDate = computed(() => {
    if (!props.paymentFailedAt) return '';
    try {
        return new Date(props.paymentFailedAt).toLocaleString('es-ES', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return props.paymentFailedAt;
    }
});
</script>
