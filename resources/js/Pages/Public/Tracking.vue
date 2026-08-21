<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    CheckCircleIcon,
    ClockIcon,
    TruckIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    CurrencyEuroIcon,
    IdentificationIcon,
    EnvelopeIcon,
    PhoneIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    car: { type: Object, required: true },
    manager: { type: Object, default: null },
    contact: { type: Object, default: () => ({}) },
});

const { t } = useTranslations();
const { date } = useFormat();

const heroPhoto = computed(() => {
    const cover = props.car.photos?.find((p) => p.is_cover);
    return cover || props.car.photos?.[0] || null;
});

const statusKey = computed(() => {
    return (props.car.status || '').replace(/ /g, '_');
});

const statusLabel = computed(() => {
    // Frontend tiene la clave cars.status.* mapeada en i18n.
    return t('cars.status.' + statusKey.value) || props.car.status_label || props.car.status;
});

const completedCount = computed(() =>
    props.car.milestones?.filter((m) => m.completed).length ?? 0,
);
const totalMilestones = computed(() => props.car.milestones?.length ?? 0);

const progressPercent = computed(() =>
    totalMilestones.value > 0
        ? Math.round((completedCount.value / totalMilestones.value) * 100)
        : 0,
);

const iconForMilestone = (key) => {
    switch (key) {
        case 'deposit_paid':
            return CurrencyEuroIcon;
        case 'transport_contracted':
            return TruckIcon;
        case 'coc_ordered':
            return DocumentTextIcon;
        case 'itv_passed':
            return ShieldCheckIcon;
        case 'iedmt_paid':
            return IdentificationIcon;
        case 'registered':
            return CheckCircleIcon;
        default:
            return ClockIcon;
    }
};

const expectedDate = computed(() =>
    props.car.expected_delivery_date ? date(props.car.expected_delivery_date) : null,
);
</script>

<template>
    <Head :title="t('public.tracking.title', { brand: car.brand, model: car.model })" />

    <div class="min-h-screen bg-gradient-to-br from-asphalt-50 via-white to-estoril-50/30">
        <header class="border-b border-estoril-100/40 bg-white/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <img
                        :src="'/img/logo-jj-import-motors.png'"
                        :alt="'JJ Import Motors'"
                        class="h-10 w-auto"
                    />
                    <span class="hidden text-sm font-medium text-asphalt-600 sm:inline">
                        JJ Import Motors · Huelva
                    </span>
                </div>
                <span
                    class="rounded-full bg-estoril-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-estoril-700"
                >
                    {{ statusLabel }}
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-10 sm:py-14">
            <!-- Hero -->
            <section class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-estoril-100">
                <div class="grid grid-cols-1 md:grid-cols-5">
                    <div
                        class="relative bg-asphalt-900 md:col-span-2"
                        style="min-height: 280px;"
                    >
                        <img
                            v-if="heroPhoto"
                            :src="heroPhoto.url"
                            :alt="car.brand + ' ' + car.model"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="absolute inset-0 flex items-center justify-center text-asphalt-400"
                        >
                            {{ t('public.tracking.no_photo') }}
                        </div>
                    </div>
                    <div class="p-8 md:col-span-3">
                        <p class="text-sm font-medium uppercase tracking-wide text-estoril-600">
                            {{ t('public.tracking.subtitle') }}
                        </p>
                        <h1
                            class="mt-2 text-3xl font-bold text-asphalt-900 sm:text-4xl"
                        >
                            {{ car.brand }} {{ car.model }}
                        </h1>
                        <p
                            v-if="car.version"
                            class="mt-1 text-base text-asphalt-600"
                        >
                            {{ car.version }}
                        </p>

                        <dl
                            class="mt-6 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3"
                        >
                            <div v-if="car.year">
                                <dt class="text-asphalt-500">{{ t('public.tracking.year') }}</dt>
                                <dd class="font-semibold text-asphalt-900">{{ car.year }}</dd>
                            </div>
                            <div v-if="car.color">
                                <dt class="text-asphalt-500">{{ t('public.tracking.color') }}</dt>
                                <dd class="font-semibold text-asphalt-900">{{ car.color }}</dd>
                            </div>
                            <div v-if="car.pais_origen">
                                <dt class="text-asphalt-500">
                                    {{ t('public.tracking.origin') }}
                                </dt>
                                <dd class="font-semibold text-asphalt-900">{{ car.pais_origen }}</dd>
                            </div>
                            <div v-if="expectedDate">
                                <dt class="text-asphalt-500">
                                    {{ t('public.tracking.eta') }}
                                </dt>
                                <dd class="font-semibold text-asphalt-900">{{ expectedDate }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Next step -->
            <section
                v-if="car.next_step_description"
                class="mt-8 rounded-2xl bg-gradient-to-br from-estoril-600 to-asphalt-900 p-8 text-white shadow-lg"
            >
                <div class="flex items-start gap-4">
                    <ClockIcon class="mt-1 h-8 w-8 flex-none text-estoril-200" />
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-estoril-200">
                            {{ t('public.tracking.next_step') }}
                        </p>
                        <p class="mt-2 text-xl font-medium leading-snug">
                            {{ car.next_step_description }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Timeline -->
            <section class="mt-10 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-asphalt-100">
                <header class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-asphalt-900">
                        {{ t('public.tracking.timeline_title') }}
                    </h2>
                    <span class="rounded-full bg-asphalt-50 px-3 py-1 text-sm font-semibold text-asphalt-700">
                        {{ completedCount }} / {{ totalMilestones }}
                    </span>
                </header>

                <ol class="space-y-5">
                    <li
                        v-for="(m, idx) in car.milestones"
                        :key="m.key"
                        class="flex items-start gap-4"
                    >
                        <div class="relative flex flex-col items-center">
                            <div
                                :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-full ring-2',
                                    m.completed
                                        ? 'bg-estoril-600 text-white ring-estoril-600'
                                        : 'bg-white text-asphalt-400 ring-asphalt-200',
                                ]"
                            >
                                <component :is="iconForMilestone(m.key)" class="h-5 w-5" />
                            </div>
                            <span
                                v-if="idx < car.milestones.length - 1"
                                class="mt-2 h-full w-px flex-1 bg-asphalt-200"
                                aria-hidden="true"
                            />
                        </div>
                        <div class="flex-1 pb-2">
                            <p
                                :class="[
                                    'font-semibold',
                                    m.completed ? 'text-asphalt-900' : 'text-asphalt-500',
                                ]"
                            >
                                {{ m.name }}
                            </p>
                            <p
                                v-if="m.completed_at"
                                class="mt-1 text-sm text-asphalt-500"
                            >
                                {{ date(m.completed_at) }}
                            </p>
                            <p
                                v-else
                                class="mt-1 text-sm italic text-asphalt-400"
                            >
                                {{ t('public.tracking.pending') }}
                            </p>
                        </div>
                    </li>
                </ol>
            </section>

            <!-- Inspections -->
            <section
                v-if="car.inspections_total"
                class="mt-8 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-asphalt-100"
            >
                <header class="mb-6 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xl font-bold text-asphalt-900">
                        <ChartBarIcon class="h-6 w-6 text-estoril-600" />
                        {{ t('public.tracking.inspections_title') }}
                    </h2>
                    <span class="text-3xl font-bold text-estoril-700">
                        {{ car.inspections_percent }}%
                    </span>
                </header>
                <p class="text-sm text-asphalt-600">
                    {{ t('public.tracking.inspections_summary', {
                        completed: car.inspections_completed,
                        total: car.inspections_total,
                    }) }}
                </p>

                <ul class="mt-5 space-y-3">
                    <li
                        v-for="s in car.inspections_sections"
                        :key="s.key"
                    >
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-asphalt-700">{{ s.name }}</span>
                            <span class="text-asphalt-500">
                                {{ s.completed }} / {{ s.total }} · {{ s.percent }}%
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-asphalt-100">
                            <div
                                class="h-full rounded-full bg-estoril-600 transition-all"
                                :style="{ width: s.percent + '%' }"
                            />
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Manager -->
            <section
                v-if="manager"
                class="mt-8 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-asphalt-100"
            >
                <h2 class="text-xl font-bold text-asphalt-900">
                    {{ t('public.tracking.manager_title') }}
                </h2>
                <div class="mt-4 flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-estoril-100 text-xl font-bold text-estoril-700"
                    >
                        {{ manager.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-asphalt-900">{{ manager.name }}</p>
                        <p class="text-sm text-asphalt-500">{{ manager.schedule }}</p>
                        <a
                            v-if="manager.email"
                            :href="'mailto:' + manager.email"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-estoril-700 hover:text-estoril-800"
                        >
                            <EnvelopeIcon class="h-4 w-4" />
                            {{ manager.email }}
                        </a>
                    </div>
                </div>
            </section>

            <!-- Footer contact -->
            <footer class="mt-12 text-center text-sm text-asphalt-500">
                <p>{{ t('public.tracking.footer_help') }}</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                    <a
                        v-if="contact.phone"
                        :href="'tel:' + contact.phone.replace(/[^+\d]/g, '')"
                        class="inline-flex items-center gap-2 hover:text-estoril-700"
                    >
                        <PhoneIcon class="h-4 w-4" />
                        {{ contact.phone }}
                    </a>
                    <a
                        v-if="contact.email"
                        :href="'mailto:' + contact.email"
                        class="inline-flex items-center gap-2 hover:text-estoril-700"
                    >
                        <EnvelopeIcon class="h-4 w-4" />
                        {{ contact.email }}
                    </a>
                </div>
            </footer>
        </main>
    </div>
</template>