<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    SparklesIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    LinkIcon,
} from '@heroicons/vue/24/outline';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const { currency, date } = useFormat();

const props = defineProps({
    car: { type: Object, required: true },
    derived: { type: Object, default: () => ({}) },
});

const verdictVariant = (v) => ({
    Buy: 'success',
    'Buy if price drops': 'warning',
    Discard: 'danger',
}[v] || 'neutral');

const confidenceVariant = (c) => ({
    high: 'success', medium: 'warning', low: 'danger',
}[c] || 'neutral');

const ratingVariant = (r) => ({
    favorable: 'success', neutral: 'neutral', unfavorable: 'danger',
}[r] || 'neutral');

const ratingIcon = (r) => ({
    favorable: CheckCircleIcon,
    neutral: MinusCircleIcon,
    unfavorable: XCircleIcon,
}[r] || MinusCircleIcon);

const researchAspectLabels = {
    common_issues: t('marketplace_show.aspect_common_issues'),
    recalls: t('marketplace_show.aspect_recalls'),
    market_price: t('marketplace_show.aspect_market_price'),
    reliability: t('marketplace_show.aspect_reliability'),
    spain_homologation: t('marketplace_show.aspect_spain_homologation'),
    dgt_label: t('marketplace_show.aspect_dgt_label'),
    insurance_estimate: t('marketplace_show.aspect_insurance_estimate'),
    parts_maintenance: t('marketplace_show.aspect_parts_maintenance'),
    unit_specific: t('marketplace_show.aspect_unit_specific'),
};

const aspects = computed(() => {
    const research = props.car.research || {};
    const gaps = props.derived?.research_gaps || [];
    return Object.entries(researchAspectLabels).map(([key, label]) => {
        const entry = research[key] || {};
        return { key, label, ...entry, missing: gaps.includes(key) };
    });
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('cars.section_investigacion') }}</h3>
            <span v-if="derived?.research_gaps?.length" class="text-xs font-medium text-amber-700">
                {{ derived.research_gaps.length }} aspecto{{ derived.research_gaps.length === 1 ? '' : 's' }} pendiente{{ derived.research_gaps.length === 1 ? '' : 's' }}
            </span>
        </div>

        <!-- Verdict -->
        <div v-if="car.verdict" class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <Badge :variant="verdictVariant(car.verdict)" dot size="lg">{{ car.verdict }}</Badge>
                <Badge v-if="car.verdict_confidence" :variant="confidenceVariant(car.verdict_confidence)">
                    {{ t('marketplace_show.confidence') }}: {{ car.verdict_confidence }}
                </Badge>
                <span v-if="car.verdict_at" class="text-xs text-gray-500">
                    {{ date(car.verdict_at) }}
                </span>
            </div>
            <p v-if="car.verdict_reasoning" class="mt-3 text-sm text-gray-700">{{ car.verdict_reasoning }}</p>
            <p v-if="car.verdict_changes" class="mt-2 text-xs italic text-gray-600">
                <span class="font-semibold not-italic text-gray-700">{{ t('marketplace_show.what_would_change') }}:</span>
                {{ car.verdict_changes }}
            </p>
        </div>

        <div v-if="!car.verdict && !derived?.research_gaps?.length" class="px-6 py-8 text-center text-sm text-gray-500">
            No hay valoración todavía. {{ t('marketplace_show.import_report_hint') }}
        </div>

        <!-- Análisis IA -->
        <div v-if="car.ai_analysis_json" class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.ai_analysis_title') }}</h4>
                <Link :href="route('cars.verify.show', car.id)" class="inline-flex items-center gap-1 text-xs font-semibold text-estoril-600 hover:text-estoril-500">
                    <SparklesIcon class="h-3 w-3" />
                    {{ t('cars.ai_suggestions') }}
                </Link>
            </div>

            <div v-if="car.ai_analysis_json.valuation" class="mb-3 rounded-lg border border-gray-200 bg-white p-3">
                <h5 class="text-xs font-semibold uppercase tracking-wider text-estoril-700">{{ t('cars.ai_valuation') }}</h5>
                <p class="mt-1 text-sm text-gray-700">{{ car.ai_analysis_json.valuation }}</p>
            </div>

            <div v-if="car.ai_analysis_json.recommendation" class="mb-3 rounded-lg border border-gray-200 bg-white p-3">
                <h5 class="text-xs font-semibold uppercase tracking-wider text-estoril-700">{{ t('cars.ai_recommendation') }}</h5>
                <p class="mt-1 text-sm text-gray-700">{{ car.ai_analysis_json.recommendation }}</p>
            </div>

            <div v-if="car.ai_analysis_json.market_avg || car.ai_analysis_json.market_min || car.ai_analysis_json.market_max" class="mb-3 grid grid-cols-3 gap-2">
                <div v-if="car.ai_analysis_json.market_min" class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.minimum') }}</div>
                    <div class="font-mono text-sm font-semibold text-gray-900">{{ currency(car.ai_analysis_json.market_min) }}</div>
                </div>
                <div v-if="car.ai_analysis_json.market_avg" class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.avg') }}</div>
                    <div class="font-mono text-sm font-semibold text-gray-900">{{ currency(car.ai_analysis_json.market_avg) }}</div>
                </div>
                <div v-if="car.ai_analysis_json.market_max" class="rounded-lg bg-gray-50 p-3 text-center">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ t('marketplace_show.maximum') }}</div>
                    <div class="font-mono text-sm font-semibold text-gray-900">{{ currency(car.ai_analysis_json.market_max) }}</div>
                </div>
            </div>

            <div v-if="car.ai_analysis_json.red_flags?.length" class="mb-3">
                <h5 class="text-xs font-semibold uppercase tracking-wider text-rose-700">{{ t('cars.ai_red_flags') }}</h5>
                <ul class="mt-1 space-y-1.5">
                    <li v-for="(flag, i) in car.ai_analysis_json.red_flags" :key="i" class="flex items-start gap-2 rounded-lg bg-rose-50 p-2 text-sm text-rose-900">
                        <XCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
                        {{ flag }}
                    </li>
                </ul>
            </div>

            <div v-if="car.ai_analysis_json.tips?.length">
                <h5 class="text-xs font-semibold uppercase tracking-wider text-estoril-700">{{ t('cars.ai_tips') }}</h5>
                <ul class="mt-1 space-y-1.5">
                    <li v-for="(tip, i) in car.ai_analysis_json.tips" :key="i" class="flex items-start gap-2 rounded-lg bg-estoril-50 p-2 text-sm text-estoril-900">
                        <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-estoril-600" />
                        {{ tip }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Balance pros / cons -->
        <div v-if="(car.pros?.length || car.cons?.length)" class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
            <div>
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-green-700">{{ t('cars.pros_label', { count: car.pros?.length || 0 }) }}</h4>
                <ul v-if="car.pros?.length" class="space-y-2">
                    <li v-for="(pro, i) in car.pros" :key="i" class="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 p-3">
                        <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ pro.text }}</p>
                            <span class="mt-1 inline-block text-xs font-medium uppercase text-green-700">{{ pro.weight }}</span>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-gray-500">{{ t('cars.no_pros') }}</p>
            </div>
            <div>
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-red-700">{{ t('cars.cons_label', { count: car.cons?.length || 0 }) }}</h4>
                <ul v-if="car.cons?.length" class="space-y-2">
                    <li v-for="(con, i) in car.cons" :key="i" class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3">
                        <XCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-red-600" />
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ con.text }}</p>
                            <span class="mt-1 inline-block text-xs font-medium uppercase text-red-700">{{ con.weight }}</span>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-gray-500">{{ t('cars.no_cons') }}</p>
            </div>
        </div>

        <!-- 9 research aspects -->
        <div class="border-t border-gray-200 px-6 py-4">
            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.research_aspects') }}</h4>
            <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <li v-for="aspect in aspects" :key="aspect.key" class="rounded-lg border p-3"
                    :class="aspect.missing ? 'border-dashed border-gray-300 bg-gray-50' : 'border-gray-200 bg-white'">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium text-gray-900">{{ aspect.label }}</p>
                        <component v-if="!aspect.missing" :is="ratingIcon(aspect.rating)" class="h-4 w-4 shrink-0"
                            :class="{
                                'text-green-600': aspect.rating === 'favorable',
                                'text-red-600': aspect.rating === 'unfavorable',
                                'text-gray-500': aspect.rating === 'neutral' || !aspect.rating,
                            }" />
                    </div>
                    <p v-if="aspect.missing" class="mt-2 text-xs italic text-gray-500">{{ t('cars.not_researched_yet') }}</p>
                    <template v-else>
                        <p v-if="aspect.finding" class="mt-2 text-sm text-gray-700">{{ aspect.finding }}</p>
                        <a v-if="aspect.source" :href="aspect.source" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs text-estoril-600 hover:text-estoril-500">
                            <LinkIcon class="h-3 w-3" />
                            {{ t('marketplace_show.source') }}
                        </a>
                        <span v-if="aspect.date" class="ml-2 text-xs text-gray-400">{{ aspect.date }}</span>
                    </template>
                </li>
            </ul>
        </div>
    </div>
</template>
