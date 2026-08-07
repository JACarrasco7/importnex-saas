<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
        appear
    >
        <div
            v-if="visible"
            class="overflow-hidden rounded-2xl border border-amber-300 bg-linear-to-r from-amber-50 to-orange-50 p-4 shadow-sm dark:border-amber-800 dark:from-amber-950/40 dark:to-orange-950/40"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/60">
                    <RocketLaunchIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        {{ title }}
                    </h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        {{ description }}
                    </p>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-amber-200 dark:bg-amber-900/40">
                        <div
                            class="h-full rounded-full bg-amber-500 transition-all duration-500"
                            :style="{ width: percentage + '%' }"
                        />
                    </div>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                        {{ usage }} / {{ limit }} ({{ Math.round(percentage) }}%)
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        @click="dismiss"
                        class="rounded-lg p-2 text-amber-700 hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-900/40"
                        :aria-label="t('common.dismiss', 'Cerrar')"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                    <Link
                        :href="route('subscriptions.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-asphalt-900"
                    >
                        <ArrowRightIcon class="h-4 w-4" />
                        {{ t('upgrade.upgrade_now', 'Mejorar plan') }}
                    </Link>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { RocketLaunchIcon, XMarkIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    feature: { type: String, required: true },
    usage: { type: Number, required: true },
    limit: { type: Number, required: true },
    threshold: { type: Number, default: 0.9 },
    featureLabel: { type: String, default: null },
});

const { t } = useTranslations();

const visible = ref(false);

const percentage = computed(() => {
    if (props.limit <= 0) return 0;
    return Math.min(100, (props.usage / props.limit) * 100);
});

const title = computed(() => {
    const label = props.featureLabel || props.feature;
    return t('upgrade.reached_limit', `Estás cerca del límite de ${label}`);
});

const description = computed(() => {
    const label = props.featureLabel || props.feature;
    return t('upgrade.description', `Mejora tu plan para añadir más ${label} y desbloquear funciones avanzadas.`);
});

const dismissKey = computed(() => `upgrade-${props.feature}-dismissed`);

const dismiss = () => {
    localStorage.setItem(dismissKey.value, Date.now().toString());
    visible.value = false;
};

onMounted(() => {
    if (props.limit <= 0 || props.usage < props.threshold * props.limit) {
        return;
    }
    const dismissed = localStorage.getItem(dismissKey.value);
    if (dismissed && Date.now() - Number(dismissed) < 7 * 24 * 60 * 60 * 1000) {
        return;
    }
    visible.value = true;
});
</script>
