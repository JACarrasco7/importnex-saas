<template>
    <div v-if="visibleResources.length > 0" class="overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100">
                <ExclamationTriangleIcon class="h-5 w-5 text-amber-600" />
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-amber-900">
                    You're approaching your plan limits
                </h3>
                <p class="mt-1 text-sm text-amber-700">
                    Upgrade to {{ recommendedPlan }} for more capacity across your inventory and CRM.
                </p>
                <div class="mt-3 space-y-2">
                    <div v-for="res in visibleResources" :key="res.key" class="flex items-center gap-3 text-xs">
                        <span class="w-24 font-semibold capitalize text-amber-900">{{ res.key }}:</span>
                        <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-amber-200">
                            <div :class="['h-full rounded-full transition-all', res.usage.percentage >= 90 ? 'bg-rose-500' : res.usage.percentage >= 70 ? 'bg-amber-500' : 'bg-emerald-500']" :style="{ width: res.usage.percentage + '%' }"></div>
                        </div>
                        <span class="w-24 text-right font-mono text-amber-900">{{ res.usage.current }} / {{ res.usage.limit }}</span>
                    </div>
                </div>
            </div>
            <Link :href="route('subscriptions.index')" class="inline-flex flex-shrink-0 items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">
                Upgrade
                <ArrowRightIcon class="h-3 w-3" />
            </Link>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ExclamationTriangleIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    usage: { type: Object, default: () => null },
    currentPlan: { type: String, default: 'starter' },
    threshold: { type: Number, default: 70 },
});

const visibleResources = computed(() => {
    if (!props.usage) return [];
    return Object.entries(props.usage)
        .filter(([_, u]) => u?.percentage >= props.threshold)
        .map(([key, usage]) => ({ key, usage }));
});

const recommendedPlan = computed(() => {
    const plan = props.currentPlan?.toLowerCase();
    if (plan === 'starter') return 'Professional';
    if (plan === 'pro' || plan === 'professional') return 'Enterprise';
    return 'a higher tier';
});
</script>
