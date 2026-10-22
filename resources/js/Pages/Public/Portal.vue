<script setup>
import { computed } from 'vue';
import { Head, usePage, usePoll } from '@inertiajs/vue3';
import { CalendarDaysIcon, CheckBadgeIcon, ExclamationTriangleIcon, MapIcon, ScaleIcon } from '@heroicons/vue/24/outline';
import PortalSection from '@/Components/Portal/PortalSection.vue';
import PortalPhases from '@/Components/Portal/PortalPhases.vue';
import PortalTodo from '@/Components/Portal/PortalTodo.vue';
import PortalContract from '@/Components/Portal/PortalContract.vue';
import PortalApprovalCard from '@/Components/Portal/PortalApprovalCard.vue';
import PortalPayments from '@/Components/Portal/PortalPayments.vue';
import PortalProgress from '@/Components/Portal/PortalProgress.vue';
import PortalFeed from '@/Components/Portal/PortalFeed.vue';
import PortalContact from '@/Components/Portal/PortalContact.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    token: { type: String, required: true },
    order: { type: Object, required: true },
    client: { type: Object, required: true },
    vehicle: { type: Object, default: null },
    contract: { type: Object, default: null },
    payments: { type: Array, default: () => [] },
    bank: { type: Object, required: true },
    approvals: { type: Array, default: () => [] },
    milestones: { type: Array, default: () => [] },
    inspection: { type: Object, default: null },
    gallery: { type: Array, default: () => [] },
    feed: { type: Array, default: () => [] },
    contact: { type: Object, required: true },
    orgBrand: { type: Object, default: () => ({}) },
});

const { t } = useTranslations();
const { date, number } = useFormat();
const page = usePage();

// Novedades sin recargar: refresco ligero cada 30 s (se pausa con la pestaña oculta).
usePoll(30000, { only: ['order', 'contract', 'payments', 'approvals', 'milestones', 'inspection', 'gallery', 'feed'] });

const flash = computed(() => page.props.flash || {});
const cover = computed(() => props.vehicle?.photos?.[0] || null);
const vehicleName = computed(() => (props.vehicle ? `${props.vehicle.brand} ${props.vehicle.model}` : t('portal.hero.no_vehicle')));
const specs = computed(() => {
    if (!props.vehicle) return [];
    return [props.vehicle.version, props.vehicle.year, props.vehicle.mileage ? `${number(props.vehicle.mileage)} km` : null, props.vehicle.fuel]
        .filter(Boolean);
});
const showPayments = computed(() => props.payments.length > 0 && props.contract?.status === 'signed');
const showProgress = computed(() => props.order.is_tracking_visible && (props.milestones.length || props.inspection || props.gallery.length));
</script>

<template>
    <Head :title="t('portal.meta_title', { vehicle: vehicleName })" />

    <div class="min-h-screen bg-asphalt-50/60 pb-16">
        <header class="sticky top-0 z-20 border-b border-asphalt-100 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                <a href="/" class="flex items-center gap-2">
                    <img v-if="orgBrand.logo_url" :src="orgBrand.logo_url" :alt="orgBrand.name" class="h-9 w-auto" />
                    <span class="text-sm font-semibold text-asphalt-700" :class="{ 'hidden sm:inline': orgBrand.logo_url }">{{ orgBrand.name }}</span>
                </a>
                <span class="rounded-full bg-estoril-50 px-3 py-1 font-mono text-xs font-semibold text-estoril-700">{{ order.reference }}</span>
            </div>
        </header>

        <main class="mx-auto max-w-3xl space-y-4 px-4 pt-4 sm:space-y-5 sm:pt-6">
            <div v-if="flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" role="status" aria-live="polite">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-rose-200" role="alert">
                <ExclamationTriangleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                {{ flash.error }}
            </div>

            <!-- Hero -->
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-asphalt-100">
                <div class="relative aspect-video bg-asphalt-900">
                    <img v-if="cover" :src="cover.url" :alt="vehicleName" class="absolute inset-0 h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-linear-to-t from-asphalt-900/85 via-asphalt-900/20 to-transparent" />
                    <div class="absolute inset-x-0 bottom-0 p-5">
                        <p class="text-sm text-platinum-100">{{ t('portal.hero.hello', { name: client.first_name }) }}</p>
                        <h1 class="text-2xl font-bold text-white sm:text-3xl">{{ vehicleName }}</h1>
                        <p v-if="specs.length" class="mt-1 text-sm text-platinum-100">{{ specs.join(' · ') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <span :class="['inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold', order.is_cancelled ? 'bg-rose-50 text-rose-700' : 'bg-estoril-50 text-estoril-800']">
                        <CheckBadgeIcon class="h-4 w-4" aria-hidden="true" />
                        {{ order.state_label }}
                    </span>
                    <span v-if="order.expected_delivery_date" class="inline-flex items-center gap-1.5 text-sm text-asphalt-600">
                        <CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />
                        {{ t('portal.hero.eta', { date: date(order.expected_delivery_date) }) }}
                    </span>
                    <span v-if="vehicle?.origin" class="inline-flex items-center gap-1.5 text-sm text-asphalt-600">
                        <MapIcon class="h-4 w-4" aria-hidden="true" />
                        {{ t('portal.hero.origin', { country: vehicle.origin }) }}
                    </span>
                </div>
            </section>

            <PortalTodo :contract="contract" :payments="payments" :approvals="approvals" :closed="order.is_closed" :state="order.state" />

            <PortalSection :title="t('portal.phases.title')" :icon="ScaleIcon">
                <PortalPhases :phases="order.phases" :cancelled="order.is_cancelled" />
            </PortalSection>

            <PortalContract v-if="contract" :token="token" :contract="contract" :masked-email="client.masked_email" />

            <PortalSection v-if="approvals.length" id="aprobaciones" :title="t('portal.approvals.title')" :subtitle="t('portal.approvals.subtitle')" :icon="CheckBadgeIcon">
                <div class="space-y-3">
                    <PortalApprovalCard v-for="approval in approvals" :key="approval.id" :token="token" :approval="approval" />
                </div>
            </PortalSection>

            <PortalPayments v-if="showPayments" :payments="payments" :bank="bank" :contact-whatsapp="contact.whatsapp_url" />

            <PortalProgress v-if="showProgress" :milestones="milestones" :inspection="inspection" :gallery="gallery" />

            <PortalFeed :token="token" :feed="feed" :closed="order.is_closed" />

            <PortalContact
                :token="token"
                :contact="contact"
                :can-request-cancellation="order.can_request_cancellation"
                :cancellation-requested-at="order.cancellation_requested_at"
            />

            <p class="px-2 text-center text-xs text-asphalt-400">{{ t('portal.footer') }}</p>
        </main>
    </div>
</template>
