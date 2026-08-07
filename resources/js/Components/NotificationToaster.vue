<script setup>
import { Link } from '@inertiajs/vue3';
import { BellIcon, CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    toasts: { type: Array, default: () => [] },
    onDismiss: { type: Function, default: () => {} },
});

const { t } = useTranslations();

const typeIcon = (type) => BellIcon;
const variantClass = (type) => {
    switch (type) {
        case 'car_request':
            return 'border-estoril-300 bg-estoril-50 text-estoril-900';
        case 'verification_failed':
        case 'document_expired':
            return 'border-rose-300 bg-rose-50 text-rose-900';
        case 'verification_completed':
            return 'border-emerald-300 bg-emerald-50 text-emerald-900';
        case 'client_no_contact':
        case 'car_stale':
            return 'border-amber-300 bg-amber-50 text-amber-900';
        default:
            return 'border-gray-300 bg-white text-gray-900';
    }
};

const typeLabel = (type) => {
    const key = `alerts.alert_types.${type}`;
    const v = t(key);
    return v === key ? type : v;
};
</script>

<template>
    <!-- Contenedor fijo abajo derecha, stack vertical -->
    <div
        aria-live="polite"
        class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-2"
    >
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-x-4 scale-95"
            enter-to-class="opacity-100 translate-x-0 scale-100"
            leave-active-class="transition duration-150 ease-in absolute"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95 translate-x-4"
        >
            <div
                v-for="toast in toasts"
                :key="toast.toastId"
                :class="['pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg ring-1 ring-black/5', variantClass(toast.alert_type)]"
                role="status"
            >
                <component :is="typeIcon(toast.alert_type)" class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-75">
                        {{ typeLabel(toast.alert_type) }}
                    </p>
                    <p class="mt-1 text-sm font-medium leading-snug">
                        {{ toast.message }}
                    </p>
                    <div v-if="toast.target_url" class="mt-2">
                        <Link
                            :href="toast.target_url"
                            class="inline-flex items-center gap-1 text-xs font-semibold underline-offset-2 hover:underline"
                            @click="onDismiss(toast.toastId)"
                        >
                            {{ t('alerts.view_resource') }}
                        </Link>
                    </div>
                </div>
                <button
                    type="button"
                    @click="onDismiss(toast.toastId)"
                    class="shrink-0 rounded p-1 opacity-60 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :aria-label="t('common.close')"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
