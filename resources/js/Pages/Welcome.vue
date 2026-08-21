<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    TruckIcon,
    UsersIcon,
    MapIcon,
    ChartBarIcon,
    SparklesIcon,
    ArrowRightIcon,
    CheckIcon,
    BoltIcon,
    ShieldCheckIcon,
    ArrowTrendingUpIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const stats = [
    { value: '9', label: t('welcome.stat_modules') },
    { value: '1', label: t('welcome.stat_marketplace') },
    { value: 'EUR', label: t('welcome.stat_stripe') },
];

const trustSources = [
    'mobile.de',
    'autoscout24',
    'wallapop',
    'coches.com',
    'AutoUncle',
    'kleinanzeigen',
];

const testimonials = [
    {
        initials: 'CM',
        name: 'Carlos M.',
        role: 'Dealer · Madrid',
        quote: 'Tengo 200 coches en mi catálogo y por fin todo en un sitio. Importnex me cambió los Excel y las hojas de cálculo.',
    },
    {
        initials: 'LG',
        name: 'Laura G.',
        role: 'Corredor · Barcelona',
        quote: 'La verificación IA me ahorra 3 horas por vehículo. Vale cada céntimo del plan Professional.',
    },
    {
        initials: 'RS',
        name: 'Roberto S.',
        role: 'Dealer · Valencia',
        quote: 'Las alertas automáticas me han salvado operaciones que se me habrían escapado. Integración con Stripe, además, es top.',
    },
];

const howWorksSteps = [
    { icon: UsersIcon, title: 'welcome.how_works_step1_title', desc: 'welcome.how_works_step1_desc' },
    { icon: TruckIcon, title: 'welcome.how_works_step2_title', desc: 'welcome.how_works_step2_desc' },
    { icon: ChartBarIcon, title: 'welcome.how_works_step3_title', desc: 'welcome.how_works_step3_desc' },
];

const pricingStarter = [
    'welcome.pricing_starter_f1',
    'welcome.pricing_starter_f2',
    'welcome.pricing_starter_f3',
];

const pricingPro = [
    'welcome.pricing_pro_f1',
    'welcome.pricing_pro_f2',
    'welcome.pricing_pro_f3',
    'welcome.pricing_pro_f4',
];

const pricingEnt = [
    'welcome.pricing_ent_f1',
    'welcome.pricing_ent_f2',
    'welcome.pricing_ent_f3',
];

const faqItems = [
    { q: 'welcome.faq_saas_q1', a: 'welcome.faq_saas_a1' },
    { q: 'welcome.faq_saas_q2', a: 'welcome.faq_saas_a2' },
    { q: 'welcome.faq_saas_q3', a: 'welcome.faq_saas_a3' },
    { q: 'welcome.faq_saas_q4', a: 'welcome.faq_saas_a4' },
];
</script>

<template>
    <Head :title="t('app.title')" />

    <div class="min-h-screen overflow-hidden bg-white text-asphalt-800 antialiased dark:bg-asphalt-900 dark:text-asphalt-100">
        <!-- ============== HEADER ============== -->
        <header class="absolute inset-x-0 top-0 z-30">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-estoril-600 to-estoril-800 shadow-sm shadow-estoril-700/30 ring-1 ring-estoril-700/40">
                        <TruckIcon class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-xl font-bold text-asphalt-900 dark:text-white">{{ t('app.title') }}</span>
                </Link>
                <div class="flex items-center gap-2 sm:gap-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-700"
                    >
                        {{ t('nav.dashboard') }}
                    </Link>
                    <template v-else>
                        <Link :href="route('pricing')" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-asphalt-700 hover:text-estoril-700 lg:inline-flex dark:text-asphalt-200 dark:hover:text-estoril-300">
                            {{ t('welcome.nav_pricing') }}
                        </Link>
                        <Link :href="route('login')" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-asphalt-700 hover:text-estoril-700 sm:inline-flex dark:text-asphalt-200 dark:hover:text-estoril-300">
                            {{ t('auth.login') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-700"
                        >
                            {{ t('auth.register') }}
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- ============== HERO ============== -->
        <section class="relative isolate overflow-hidden pb-20 pt-32 lg:pb-28 lg:pt-40">
            <!-- Mesh + grid -->
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_left,#eef1fa_0%,#ffffff_45%,#f6f6f7_100%)] dark:bg-[radial-gradient(ellipse_at_top_left,#1e2649_0%,#11142a_50%,#0a0d1c_100%)]"></div>
            <div aria-hidden="true" class="absolute inset-0 -z-10 [background-image:linear-gradient(to_right,rgba(26,48,109,0.06)_1px,transparent_1px),linear-gradient(to_bottom,rgba(26,48,109,0.06)_1px,transparent_1px)] [background-size:32px_32px] [mask-image:radial-gradient(ellipse_at_center,black_40%,transparent_75%)] dark:[background-image:linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)]"></div>

            <!-- Orbes Aurora -->
            <div aria-hidden="true" class="absolute -top-40 -right-32 -z-10 h-[28rem] w-[28rem] rounded-full bg-estoril-300/40 blur-3xl"></div>
            <div aria-hidden="true" class="absolute -bottom-32 -left-32 -z-10 h-[24rem] w-[24rem] rounded-full bg-platinum-400/40 blur-3xl"></div>
            <div aria-hidden="true" class="absolute top-1/3 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 rounded-full bg-estoril-200/30 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-7xl grid-cols-1 gap-16 px-6 lg:grid-cols-12 lg:items-center lg:gap-10">
                <!-- Texto -->
                <div class="text-center lg:col-span-7 lg:text-left">
                    <!-- Badge SaaS con pulso -->
                    <div class="inline-flex items-center gap-2 rounded-full border border-estoril-200 bg-white/70 px-3 py-1.5 text-sm font-semibold text-estoril-700 backdrop-blur-sm dark:border-estoril-700/50 dark:bg-asphalt-800/60 dark:text-estoril-300">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-estoril-500 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-estoril-600"></span>
                        </span>
                        <span>{{ t('welcome.hero_badge_saas') }}</span>
                    </div>

                    <!-- Titular -->
                    <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-asphalt-900 sm:text-5xl lg:text-6xl dark:text-white">
                        <span class="block">{{ t('welcome.run_business') }}</span>
                        <span class="relative mt-1 inline-block bg-gradient-to-r from-estoril-600 via-estoril-500 to-estoril-400 bg-clip-text text-transparent">
                            {{ t('welcome.on_autopilot') }}
                            <svg aria-hidden="true" class="absolute -bottom-1 left-0 w-full text-estoril-400 dark:text-estoril-500" viewBox="0 0 300 12" fill="none">
                                <path d="M2 9c50-7 100-7 150 0s100 7 148 0" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            </svg>
                        </span>
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-lg text-asphalt-600 lg:mx-0 dark:text-asphalt-300">
                        {{ t('welcome.importnex_description') }}
                    </p>

                    <!-- CTAs -->
                    <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row lg:justify-start">
                        <Link
                            :href="route('register')"
                            class="group inline-flex items-center justify-center gap-2 rounded-lg bg-estoril-700 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-estoril-700/20 ring-1 ring-estoril-800/30 hover:bg-estoril-800 hover:shadow-xl hover:shadow-estoril-700/30"
                        >
                            {{ t('welcome.cta_try_free') }}
                            <ArrowRightIcon class="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                        <Link
                            :href="route('marketplace.index')"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/80 px-6 py-3 text-base font-semibold text-asphalt-800 shadow-sm ring-1 ring-asphalt-200 backdrop-blur-sm hover:bg-white hover:ring-estoril-300 dark:bg-asphalt-800/60 dark:text-white dark:ring-asphalt-700 dark:hover:bg-asphalt-800/80"
                        >
                            <BoltIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-400" />
                            {{ t('welcome.cta_view_example') }}
                        </Link>
                    </div>

                    <!-- Métricas inline -->
                    <div class="mt-10 grid grid-cols-3 gap-6 rounded-2xl border border-asphalt-200/60 bg-white/60 p-5 backdrop-blur-sm sm:max-w-xl dark:border-asphalt-700/60 dark:bg-asphalt-800/40">
                        <div v-for="s in stats" :key="s.label" class="text-center">
                            <p class="text-2xl font-bold text-estoril-700 sm:text-3xl dark:text-estoril-300">{{ s.value }}</p>
                            <p class="mt-1 text-xs text-asphalt-600 sm:text-sm dark:text-asphalt-400">{{ s.label }}</p>
                        </div>
                    </div>

                    <!-- Trust-badge (multi-tenant / privacy-first) -->
                    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-asphalt-600 sm:max-w-xl dark:text-asphalt-400">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            {{ t('welcome.hero_trust_iso') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-estoril-600 dark:text-estoril-400" viewBox="0 0 20 20" fill="currentColor"><path d="M13 7H7v6h6V7z"/><path fill-rule="evenodd" d="M7 2a1 1 0 00-1 1v1H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H8V3a1 1 0 00-1-1zm0 5h6v6H7V7z" clip-rule="evenodd" /></svg>
                            {{ t('welcome.hero_trust_cancel') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-estoril-600 dark:text-estoril-400" viewBox="0 0 20 20" fill="currentColor"><path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L10 9.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                            {{ t('welcome.hero_trust_mail') }}
                        </span>
                    </div>
                </div>

                <!-- Mockup: CRM/ficha de inventario genérico del SaaS -->
                <div class="relative lg:col-span-5">
                    <!-- Tarjeta stack: chat IA -->
                    <div class="absolute -left-6 -top-6 z-20 hidden w-64 rotate-[-3deg] rounded-xl bg-white p-4 shadow-xl ring-1 ring-asphalt-200 sm:block dark:bg-asphalt-800 dark:ring-asphalt-700">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-estoril-100 dark:bg-estoril-900/50">
                                <SparklesIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-300" />
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-semibold text-asphalt-900 dark:text-white">Importnex AI</p>
                                <p class="text-[10px] text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.mockup_ia_status') }}</p>
                            </div>
                            <span class="ml-auto flex h-2 w-2">
                                <span class="absolute h-2 w-2 animate-ping rounded-full bg-green-500 opacity-75"></span>
                                <span class="relative h-2 w-2 rounded-full bg-green-500"></span>
                            </span>
                        </div>
                        <p class="mt-3 rounded-lg bg-asphalt-50 p-2 text-xs text-asphalt-700 dark:bg-asphalt-900/50 dark:text-asphalt-300">
                            "{{ t('welcome.mockup_ia_msg') }}"
                        </p>
                    </div>

                    <!-- Mockup principal: panel de resumen del negocio (sin billing SaaS) -->
                    <div class="relative overflow-hidden rounded-2xl bg-white shadow-2xl shadow-asphalt-900/10 ring-1 ring-asphalt-200 dark:bg-asphalt-800 dark:ring-asphalt-700 dark:shadow-asphalt-950/50">
                        <!-- header -->
                        <div class="flex items-center justify-between border-b border-asphalt-200/60 px-5 py-3 dark:border-asphalt-700/60">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-estoril-600 to-estoril-800">
                                    <TruckIcon class="h-4 w-4 text-white" />
                                </div>
                                <span class="text-sm font-semibold text-asphalt-900 dark:text-white">{{ t('welcome.mockup_dash_title') }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> {{ t('welcome.mockup_dash_live') }}
                            </span>
                        </div>

                        <!-- Cuerpo: KPIs del negocio -->
                        <div class="space-y-3 p-5">
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-lg bg-estoril-50 p-3 dark:bg-estoril-900/30">
                                    <p class="text-[10px] uppercase tracking-wider text-estoril-700 dark:text-estoril-300">{{ t('welcome.mockup_dash_kpi_cars') }}</p>
                                    <p class="mt-1 text-xl font-bold text-estoril-800 dark:text-estoril-200">142</p>
                                </div>
                                <div class="rounded-lg bg-platinum-100 p-3 dark:bg-asphalt-700/60">
                                    <p class="text-[10px] uppercase tracking-wider text-asphalt-600 dark:text-asphalt-300">{{ t('welcome.mockup_dash_kpi_clients') }}</p>
                                    <p class="mt-1 text-xl font-bold text-asphalt-900 dark:text-white">38</p>
                                </div>
                                <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                                    <p class="text-[10px] uppercase tracking-wider text-amber-700 dark:text-amber-300">{{ t('welcome.mockup_dash_kpi_deals') }}</p>
                                    <p class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-200">12</p>
                                </div>
                            </div>

                            <!-- Gráfico margen/ingresos -->
                            <div class="rounded-lg border border-asphalt-200/60 bg-white p-4 dark:border-asphalt-700/60 dark:bg-asphalt-900/40">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.mockup_dash_chart') }}</p>
                                        <p class="text-lg font-semibold text-asphalt-900 dark:text-white">€284.500</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                        <ArrowTrendingUpIcon class="h-3 w-3" />+24%
                                    </span>
                                </div>
                                <svg viewBox="0 0 200 50" class="mt-3 h-12 w-full">
                                    <defs>
                                        <linearGradient id="spark" x1="0" x2="1">
                                            <stop offset="0" stop-color="#1A306D" />
                                            <stop offset="1" stop-color="#5c73bd" />
                                        </linearGradient>
                                    </defs>
                                    <polyline fill="none" stroke="url(#spark)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="0,38 18,30 36,33 54,22 72,25 90,15 108,18 126,10 144,14 162,6 180,9 200,3" />
                                    <polyline fill="url(#spark)" fill-opacity="0.12" stroke="none" points="0,38 18,30 36,33 54,22 72,25 90,15 108,18 126,10 144,14 162,6 180,9 200,3 200,50 0,50" />
                                </svg>
                            </div>

                            <!-- Tracking con el cliente: solicitudes recientes -->
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.mockup_dash_requests') }}</p>
                                <div class="mt-2 space-y-1.5">
                                    <div class="flex items-center justify-between rounded-lg border border-asphalt-200/60 bg-white p-2.5 dark:border-asphalt-700/60 dark:bg-asphalt-900/40">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-estoril-500 to-estoril-700 text-[10px] font-bold text-white">MG</div>
                                            <div>
                                                <p class="text-xs font-semibold text-asphalt-900 dark:text-white">María G. · BMW X3</p>
                                                <p class="text-[10px] text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.mockup_dash_req_pending') }}</p>
                                            </div>
                                        </div>
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ t('welcome.mockup_dash_tag_pending') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-lg border border-asphalt-200/60 bg-white p-2.5 dark:border-asphalt-700/60 dark:bg-asphalt-900/40">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-asphalt-600 to-asphalt-800 text-[10px] font-bold text-white">CM</div>
                                            <div>
                                                <p class="text-xs font-semibold text-asphalt-900 dark:text-white">Carlos M. · Audi Q5</p>
                                                <p class="text-[10px] text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.mockup_dash_req_progress') }}</p>
                                            </div>
                                        </div>
                                        <span class="rounded bg-estoril-100 px-1.5 py-0.5 text-[10px] font-semibold text-estoril-700 dark:bg-estoril-900/40 dark:text-estoril-300">{{ t('welcome.mockup_dash_tag_progress') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta stack: mapa de flota / ubicación de la organización -->
                    <div class="absolute -right-4 -bottom-6 z-20 hidden w-56 rotate-[3deg] rounded-xl bg-white p-3 shadow-xl ring-1 ring-asphalt-200 sm:block dark:bg-asphalt-800 dark:ring-asphalt-700">
                        <div class="flex items-center gap-2">
                            <MapIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-300" />
                            <p class="text-xs font-semibold text-asphalt-900 dark:text-white">{{ t('welcome.mockup_saas_map_title') }}</p>
                        </div>
                        <div class="mt-2 grid grid-cols-3 gap-1.5 text-[10px]">
                            <div class="flex flex-col items-center rounded-md bg-estoril-50 p-1.5 dark:bg-estoril-900/40">
                                <span class="h-2 w-2 rounded-full bg-estoril-600"></span>
                                <span class="mt-1 text-[10px] font-semibold text-estoril-700 dark:text-estoril-300">{{ t('welcome.mockup_saas_sedes') }}</span>
                            </div>
                            <div class="flex flex-col items-center rounded-md bg-amber-50 p-1.5 dark:bg-amber-900/30">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                <span class="mt-1 text-[10px] font-semibold text-amber-700 dark:text-amber-300">{{ t('welcome.mockup_saas_stock') }}</span>
                            </div>
                            <div class="flex flex-col items-center rounded-md bg-green-50 p-1.5 dark:bg-green-900/30">
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                <span class="mt-1 text-[10px] font-semibold text-green-700 dark:text-green-300">{{ t('welcome.mockup_saas_live') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== TRUST BAR ============== -->
        <section class="border-y border-asphalt-200/60 bg-white/60 py-8 backdrop-blur-sm dark:border-asphalt-700/60 dark:bg-asphalt-800/40">
            <div class="mx-auto max-w-7xl px-6">
                <p class="text-center text-xs font-semibold uppercase tracking-widest text-asphalt-500 dark:text-asphalt-400">
                    {{ t('welcome.trust_compatible_with') }}
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-4">
                    <div v-for="src in trustSources" :key="src" class="text-lg font-bold tracking-tight text-asphalt-400 transition hover:text-asphalt-700 dark:text-asphalt-500 dark:hover:text-asphalt-200">
                        {{ src }}
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== FEATURES ============== -->
        <section class="relative py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-estoril-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-estoril-700 dark:bg-estoril-900/40 dark:text-estoril-300">
                        <SparklesIcon class="h-3 w-3" />{{ t('welcome.features_tag') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-asphalt-900 sm:text-4xl dark:text-white">
                        {{ t('welcome.everything_you_need') }}
                    </h2>
                    <p class="mt-4 text-lg text-asphalt-600 dark:text-asphalt-300">
                        {{ t('welcome.built_for') }}
                    </p>
                </div>

                <div class="mt-16 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <!-- === MOCKUP: Inventario genérico del CRM (sin importar origen) === -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-estoril-700 to-estoril-900 p-6 shadow-lg shadow-estoril-900/20 ring-1 ring-estoril-800 dark:shadow-estoril-950/50">
                        <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-estoril-400/20 blur-2xl transition group-hover:bg-estoril-400/40"></div>
                        <div class="relative space-y-3">
                            <!-- Header -->
                            <div class="flex items-center justify-between rounded-lg bg-white/10 px-3 py-2 text-xs text-estoril-100 backdrop-blur-sm">
                                <span class="flex items-center gap-2">
                                    <TruckIcon class="h-3.5 w-3.5" /> {{ t('welcome.feat_inventory_tag') }}
                                </span>
                                <span class="rounded-full bg-estoril-400/30 px-2 py-0.5 text-[10px] font-semibold">142</span>
                            </div>
                            <!-- Filtros active -->
                            <div class="flex flex-wrap gap-1.5 text-[10px]">
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-white">Todo</span>
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-white">Lead caliente</span>
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-white">Reservado</span>
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-white">Publicado</span>
                            </div>
                            <!-- Cards hallazgos -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between rounded bg-white/10 px-3 py-2 text-[11px] text-estoril-100">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-10 items-center justify-center rounded bg-gradient-to-br from-estoril-400 to-estoril-600 text-[9px] font-bold text-white">#001</span>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">BMW 320d M-Sport</span>
                                            <span class="text-[9px] text-estoril-200">{{ t('welcome.mockup_saas_status') }}</span>
                                        </div>
                                    </div>
                                    <span class="rounded bg-green-400/30 px-1.5 text-[9px] font-semibold">{{ t('welcome.mockup_saas_published') }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded bg-white/10 px-3 py-2 text-[11px] text-estoril-100">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-10 items-center justify-center rounded bg-gradient-to-br from-amber-400 to-amber-600 text-[9px] font-bold text-white">#002</span>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">Audi A4 Avant</span>
                                            <span class="text-[9px] text-estoril-200">{{ t('welcome.mockup_saas_reserved') }}</span>
                                        </div>
                                    </div>
                                    <span class="rounded bg-amber-400/30 px-1.5 text-[9px] font-semibold">{{ t('welcome.mockup_saas_status_pending') }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded bg-white/10 px-3 py-2 text-[11px] text-estoril-100">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-10 items-center justify-center rounded bg-gradient-to-br from-rose-400 to-rose-600 text-[9px] font-bold text-white">#003</span>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">VW Golf GTI</span>
                                            <span class="text-[9px] text-estoril-200">{{ t('welcome.mockup_saas_lead') }}</span>
                                        </div>
                                    </div>
                                    <span class="rounded bg-rose-400/30 px-1.5 text-[9px] font-semibold">{{ t('welcome.mockup_saas_hot') }}</span>
                                </div>
                            </div>
                        </div>
                        <h3 class="relative mt-5 text-lg font-semibold text-white">{{ t('welcome.feat_inventory_title') }}</h3>
                        <p class="relative mt-1.5 text-sm text-estoril-100">{{ t('welcome.feat_inventory_desc') }}</p>
                    </div>

                    <!-- === ICON: CRM === -->
                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-asphalt-200 transition hover:-translate-y-1 hover:shadow-md hover:ring-estoril-300 dark:bg-asphalt-800/60 dark:ring-asphalt-700 dark:hover:ring-estoril-600/50">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-estoril-50 ring-1 ring-estoril-200/60 dark:bg-estoril-900/40 dark:ring-estoril-700/40">
                            <UsersIcon class="h-6 w-6 text-estoril-700 dark:text-estoril-300" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-asphalt-900 dark:text-white">{{ t('nav.crm') }}</h3>
                        <p class="mt-1.5 text-sm text-asphalt-600 dark:text-asphalt-300">{{ t('welcome.feat_crm_desc') }}</p>
                        <ul class="mt-4 space-y-1.5 text-sm text-asphalt-700 dark:text-asphalt-300">
                            <li class="flex items-center gap-2"><CheckIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-400" />{{ t('welcome.feat_crm_check1') }}</li>
                            <li class="flex items-center gap-2"><CheckIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-400" />{{ t('welcome.feat_crm_check2') }}</li>
                            <li class="flex items-center gap-2"><CheckIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-400" />{{ t('welcome.feat_crm_check3') }}</li>
                        </ul>
                    </div>

                    <!-- === MOCKUP: Mapa de flota del cliente SaaS (sin ruta logística) === -->
                    <div class="group relative overflow-hidden rounded-2xl bg-asphalt-50 p-6 shadow-sm ring-1 ring-asphalt-200 dark:bg-asphalt-800 dark:ring-asphalt-700">
                        <div class="relative h-44 overflow-hidden rounded-lg bg-gradient-to-br from-amber-50 via-white to-estoril-50 dark:from-asphalt-700 dark:via-asphalt-800 dark:to-asphalt-900">
                            <svg aria-hidden="true" class="absolute inset-0 h-full w-full opacity-70 dark:opacity-50" viewBox="0 0 240 130">
                                <path d="M0 30 Q60 10 120 40 T 240 30" stroke="#1A306D" stroke-opacity="0.25" stroke-width="1" fill="none" />
                                <path d="M0 80 Q80 100 160 70 T 240 90" stroke="#1A306D" stroke-opacity="0.25" stroke-width="1" fill="none" />
                                <path d="M40 0 L60 130" stroke="#1A306D" stroke-opacity="0.15" stroke-width="1" fill="none" />
                                <path d="M180 0 L200 130" stroke="#1A306D" stroke-opacity="0.15" stroke-width="1" fill="none" />
                                <circle cx="60" cy="55" r="6" fill="#3a4f9e" fill-opacity="0.15" />
                                <circle cx="170" cy="95" r="8" fill="#22c55e" fill-opacity="0.2" />
                                <circle cx="120" cy="40" r="8" fill="#f59e0b" fill-opacity="0.2" />
                            </svg>
                            <div class="absolute left-[22%] top-[40%]">
                                <span class="relative flex h-3.5 w-3.5">
                                    <span class="absolute h-3.5 w-3.5 animate-ping rounded-full bg-estoril-500 opacity-70"></span>
                                    <span class="relative h-3.5 w-3.5 rounded-full bg-estoril-700 ring-2 ring-white dark:ring-asphalt-800"></span>
                                </span>
                            </div>
                            <div class="absolute left-[47%] top-[28%]">
                                <span class="h-3 w-3 rounded-full bg-amber-500 ring-2 ring-white dark:ring-asphalt-800"></span>
                            </div>
                            <div class="absolute left-[70%] top-[70%]">
                                <span class="relative flex h-4 w-4">
                                    <span class="absolute h-4 w-4 animate-ping rounded-full bg-green-500 opacity-70"></span>
                                    <span class="relative h-4 w-4 rounded-full bg-green-600 ring-2 ring-white dark:ring-asphalt-800"></span>
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 text-[10px] text-asphalt-600 dark:text-asphalt-400">
                            <span class="flex items-center gap-1 rounded bg-estoril-50 px-1.5 py-0.5 dark:bg-estoril-900/40">
                                <span class="h-2 w-2 rounded-full bg-estoril-600"></span>
                                <span class="font-semibold text-estoril-700 dark:text-estoril-300">{{ t('welcome.mockup_saas_map_legend_stock') }}</span>
                            </span>
                            <span class="flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 dark:bg-amber-900/40">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                <span class="text-amber-700 dark:text-amber-300">{{ t('welcome.mockup_saas_map_legend_lead') }}</span>
                            </span>
                            <span class="flex items-center gap-1 rounded bg-green-50 px-1.5 py-0.5 dark:bg-green-900/40">
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                <span class="text-green-700 dark:text-green-300">{{ t('welcome.mockup_saas_map_legend_sold') }}</span>
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <MapIcon class="h-5 w-5 text-estoril-700 dark:text-estoril-300" />
                            <h3 class="text-lg font-semibold text-asphalt-900 dark:text-white">{{ t('nav.map') }}</h3>
                        </div>
                        <p class="mt-1.5 text-sm text-asphalt-600 dark:text-asphalt-300">{{ t('welcome.feat_map_desc') }}</p>
                    </div>

                    <!-- === ICON: Finance (Coste estimado vs real) === -->
                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-asphalt-200 transition hover:-translate-y-1 hover:shadow-md hover:ring-estoril-300 dark:bg-asphalt-800/60 dark:ring-asphalt-700 dark:hover:ring-estoril-600/50">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-estoril-50 ring-1 ring-estoril-200/60 dark:bg-estoril-900/40 dark:ring-estoril-700/40">
                            <ChartBarIcon class="h-6 w-6 text-estoril-700 dark:text-estoril-300" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-asphalt-900 dark:text-white">{{ t('welcome.feat_finance_title') }}</h3>
                        <p class="mt-1.5 text-sm text-asphalt-600 dark:text-asphalt-300">{{ t('welcome.feat_finance_desc') }}</p>
                        <div class="mt-4 space-y-1 text-[11px] text-asphalt-700 dark:text-asphalt-300">
                            <div class="flex items-center justify-between">
                                <span>{{ t('welcome.feat_finance_item_transport') }}</span>
                                <span class="rounded bg-asphalt-100 px-1.5 py-0.5 font-semibold dark:bg-asphalt-900/40">€1.250 / €1.180</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>{{ t('welcome.feat_finance_item_itv') }}</span>
                                <span class="rounded bg-asphalt-100 px-1.5 py-0.5 font-semibold dark:bg-asphalt-900/40">€520 / €520</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>{{ t('welcome.feat_finance_item_fees') }}</span>
                                <span class="rounded bg-asphalt-100 px-1.5 py-0.5 font-semibold dark:bg-asphalt-900/40">€1.450 / €1.450</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between border-t border-asphalt-200/60 pt-2 dark:border-asphalt-700/60">
                                <span class="text-xs font-semibold">{{ t('welcome.feat_finance_total') }}</span>
                                <span class="inline-flex items-center gap-1 rounded bg-green-100 px-1.5 py-0.5 text-xs font-bold text-green-700 dark:bg-green-900/30 dark:text-green-300">−€70</span>
                            </div>
                        </div>
                    </div>

                    <!-- === MOCKUP: IA (análisis cualitativo, honesto) === -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-platinum-100 to-platinum-200 p-6 shadow-sm ring-1 ring-platinum-300 dark:from-asphalt-700 dark:to-asphalt-800 dark:ring-asphalt-600">
                        <div class="rounded-lg bg-white/70 p-3 shadow-sm backdrop-blur dark:bg-asphalt-900/70">
                            <div class="flex items-center gap-2 border-b border-asphalt-200/60 pb-2 dark:border-asphalt-700/60">
                                <SparklesIcon class="h-4 w-4 text-estoril-600 dark:text-estoril-300" />
                                <span class="text-xs font-semibold text-asphalt-900 dark:text-white">Importnex AI</span>
                                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            </div>
                            <div class="mt-2 space-y-1.5 text-[11px] text-asphalt-700 dark:text-asphalt-300">
                                <p class="rounded bg-amber-50 p-1.5 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                    <span class="font-semibold">{{ t('welcome.mockup_saas_ia_alert_redflag') }}</span> kilometraje alto vs año
                                </p>
                                <p class="rounded bg-green-50 p-1.5 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    {{ t('welcome.mockup_saas_ia_tip') }} ITV próxima
                                </p>
                                <p class="rounded bg-asphalt-100 p-1.5 dark:bg-asphalt-800">
                                    <span class="font-semibold">{{ t('welcome.feat_ai_qualitative_label') }}</span> green
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <SparklesIcon class="h-5 w-5 text-estoril-700 dark:text-estoril-300" />
                            <h3 class="text-lg font-semibold text-asphalt-900 dark:text-white">{{ t('welcome.feat_ai_title') }}</h3>
                        </div>
                        <p class="mt-1.5 text-sm text-asphalt-700 dark:text-asphalt-300">{{ t('welcome.feat_ai_desc') }}</p>
                        <!-- Honest disclaimer pill -->
                        <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                            {{ t('welcome.feat_ai_disclaimer') }}
                        </div>
                    </div>

                    <!-- === ICON: Alerts === -->
                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-asphalt-200 transition hover:-translate-y-1 hover:shadow-md hover:ring-estoril-300 dark:bg-asphalt-800/60 dark:ring-asphalt-700 dark:hover:ring-estoril-600/50">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-estoril-50 ring-1 ring-estoril-200/60 dark:bg-estoril-900/40 dark:ring-estoril-700/40">
                            <ShieldCheckIcon class="h-6 w-6 text-estoril-700 dark:text-estoril-300" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-asphalt-900 dark:text-white">{{ t('welcome.feat_alerts_title') }}</h3>
                        <p class="mt-1.5 text-sm text-asphalt-600 dark:text-asphalt-300">{{ t('welcome.feat_alerts_desc') }}</p>
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                {{ t('welcome.mockup_saas_alert_vence') }}
                            </div>
                            <div class="flex items-center gap-2 rounded-lg bg-estoril-50 px-3 py-2 text-xs text-estoril-800 dark:bg-estoril-900/40 dark:text-estoril-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-estoril-500"></span>
                                {{ t('welcome.mockup_saas_alert_lead') }}
                            </div>
                        </div>
                    </div>

                    <!-- (CRM duplicado eliminado — el grid queda en 6 cards: Inventario, CRM, Mapa, Finanzas, IA, Alertas) -->
                </div>
            </div>
        </section>

        <!-- ============== CÓMO FUNCIONA (3 pasos del software) ============== -->
        <section class="relative py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-estoril-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-estoril-700 dark:bg-estoril-900/40 dark:text-estoril-300">
                        <BoltIcon class="h-3 w-3" />{{ t('welcome.how_works_tag') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-asphalt-900 sm:text-4xl dark:text-white">
                        {{ t('welcome.how_works_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-asphalt-600 dark:text-asphalt-300">
                        {{ t('welcome.how_works_subtitle') }}
                    </p>
                </div>

                <ol class="relative mt-14 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div aria-hidden="true" class="absolute left-0 right-0 top-7 hidden h-px bg-gradient-to-r from-transparent via-asphalt-300 to-transparent dark:via-asphalt-700 md:block"></div>
                    <li v-for="(step, i) in howWorksSteps" :key="step.title" class="group relative flex flex-col items-center text-center">
                        <span class="relative z-10 flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-md ring-2 ring-estoril-200 dark:bg-asphalt-800 dark:ring-estoril-700">
                            <component :is="step.icon" class="h-6 w-6 text-estoril-700 dark:text-estoril-300" />
                            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-estoril-700 text-[10px] font-bold text-white">{{ i + 1 }}</span>
                        </span>
                        <h3 class="mt-4 text-base font-semibold text-asphalt-900 dark:text-white">{{ t(step.title) }}</h3>
                        <p class="mt-1.5 text-sm text-asphalt-600 dark:text-asphalt-400">{{ t(step.desc) }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- ============== PRECIOS (planes SaaS) ============== -->
        <section class="relative py-20" id="precios">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-estoril-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-estoril-700 dark:bg-estoril-900/40 dark:text-estoril-300">
                        <ChartBarIcon class="h-3 w-3" />{{ t('welcome.pricing_tag') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-asphalt-900 sm:text-4xl dark:text-white">
                        {{ t('welcome.pricing_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-asphalt-600 dark:text-asphalt-300">
                        {{ t('welcome.pricing_subtitle') }}
                    </p>
                </div>

                <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Starter -->
                    <div class="relative flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-asphalt-200 dark:bg-asphalt-800/60 dark:ring-asphalt-700">
                        <h3 class="text-lg font-semibold text-asphalt-900 dark:text-white">Starter</h3>
                        <p class="mt-1 text-xs text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.pricing_starter_desc') }}</p>
                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-asphalt-900 dark:text-white">29€</span>
                            <span class="text-sm text-asphalt-500 dark:text-asphalt-400">/{{ t('welcome.pricing_month') }}</span>
                        </p>
                        <ul class="mt-6 flex-1 space-y-2 text-sm text-asphalt-700 dark:text-asphalt-300">
                            <li v-for="f in pricingStarter" :key="f" class="flex items-center gap-2"><CheckIcon class="h-4 w-4 shrink-0 text-estoril-600 dark:text-estoril-400" />{{ t(f) }}</li>
                        </ul>
                        <Link :href="route('register')" class="mt-6 inline-flex items-center justify-center rounded-lg border border-estoril-300 px-4 py-2 text-sm font-semibold text-estoril-700 hover:bg-estoril-50 dark:border-estoril-700 dark:text-estoril-300 dark:hover:bg-estoril-900/30">
                            {{ t('welcome.pricing_cta_start') }}
                        </Link>
                    </div>

                    <!-- Professional (destacado) -->
                    <div class="relative flex flex-col rounded-2xl bg-gradient-to-br from-estoril-700 to-estoril-900 p-6 shadow-lg shadow-estoril-900/20 ring-2 ring-estoril-500 dark:shadow-estoril-950/50">
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-amber-400 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-950">{{ t('welcome.pricing_popular') }}</span>
                        <h3 class="text-lg font-semibold text-white">Professional</h3>
                        <p class="mt-1 text-xs text-estoril-200">{{ t('welcome.pricing_pro_desc') }}</p>
                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-white">99€</span>
                            <span class="text-sm text-estoril-200">/{{ t('welcome.pricing_month') }}</span>
                        </p>
                        <ul class="mt-6 flex-1 space-y-2 text-sm text-estoril-100">
                            <li v-for="f in pricingPro" :key="f" class="flex items-center gap-2"><CheckIcon class="h-4 w-4 shrink-0 text-estoril-300" />{{ t(f) }}</li>
                        </ul>
                        <Link :href="route('register')" class="mt-6 inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-estoril-700 shadow hover:bg-estoril-50">
                            {{ t('welcome.pricing_cta_start') }}
                        </Link>
                    </div>

                    <!-- Enterprise -->
                    <div class="relative flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-asphalt-200 dark:bg-asphalt-800/60 dark:ring-asphalt-700">
                        <h3 class="text-lg font-semibold text-asphalt-900 dark:text-white">Enterprise</h3>
                        <p class="mt-1 text-xs text-asphalt-500 dark:text-asphalt-400">{{ t('welcome.pricing_ent_desc') }}</p>
                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-asphalt-900 dark:text-white">299€</span>
                            <span class="text-sm text-asphalt-500 dark:text-asphalt-400">/{{ t('welcome.pricing_month') }}</span>
                        </p>
                        <ul class="mt-6 flex-1 space-y-2 text-sm text-asphalt-700 dark:text-asphalt-300">
                            <li v-for="f in pricingEnt" :key="f" class="flex items-center gap-2"><CheckIcon class="h-4 w-4 shrink-0 text-estoril-600 dark:text-estoril-400" />{{ t(f) }}</li>
                        </ul>
                        <Link :href="route('register')" class="mt-6 inline-flex items-center justify-center rounded-lg border border-estoril-300 px-4 py-2 text-sm font-semibold text-estoril-700 hover:bg-estoril-50 dark:border-estoril-700 dark:text-estoril-300 dark:hover:bg-estoril-900/30">
                            {{ t('welcome.pricing_cta_contact') }}
                        </Link>
                    </div>
                </div>

                <p class="mt-8 text-center text-sm text-asphalt-500 dark:text-asphalt-400">
                    {{ t('welcome.pricing_trial_hint') }}
                    <Link :href="route('pricing')" class="font-semibold text-estoril-600 hover:text-estoril-700 dark:text-estoril-300">{{ t('welcome.pricing_view_all') }}</Link>
                </p>
            </div>
        </section>

        <!-- ============== FAQ ============== -->
        <section class="relative py-20">
            <div class="mx-auto max-w-3xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-estoril-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-estoril-700 dark:bg-estoril-900/40 dark:text-estoril-300">
                        <ShieldCheckIcon class="h-3 w-3" />{{ t('welcome.faq_tag') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-asphalt-900 sm:text-4xl dark:text-white">
                        {{ t('welcome.faq_title') }}
                    </h2>
                </div>

                <div class="mt-12 divide-y divide-asphalt-200 dark:divide-asphalt-700">
                    <div v-for="(item, i) in faqItems" :key="item.q" class="py-5">
                        <h3 class="text-base font-semibold text-asphalt-900 dark:text-white">{{ t(item.q) }}</h3>
                        <p class="mt-2 text-sm text-asphalt-600 dark:text-asphalt-300">{{ t(item.a) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== TESTIMONIOS SaaS ============== -->
        <section class="py-16">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div v-for="tt in testimonials" :key="tt.role" class="rounded-2xl border border-asphalt-200 bg-white p-6 shadow-sm dark:border-asphalt-700 dark:bg-asphalt-800/60">
                        <p class="text-sm leading-relaxed text-asphalt-700 dark:text-asphalt-200">"{{ tt.quote }}"</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-estoril-600 to-estoril-800 text-sm font-bold text-white">{{ tt.initials }}</div>
                            <div>
                                <p class="text-sm font-semibold text-asphalt-900 dark:text-white">{{ tt.name }}</p>
                                <p class="text-xs text-asphalt-500 dark:text-asphalt-400">{{ tt.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== CTA FINAL ============== -->
        <section class="py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-estoril-800 via-estoril-900 to-asphalt-900 px-8 py-16 shadow-2xl shadow-estoril-900/30 sm:px-12">
                    <div aria-hidden="true" class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-estoril-400/20 blur-3xl"></div>
                    <div aria-hidden="true" class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-platinum-400/20 blur-3xl"></div>
                    <div aria-hidden="true" class="absolute inset-0 [background-image:linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)] [background-size:40px_40px]"></div>

                    <div class="relative grid items-center gap-10 lg:grid-cols-2">
                        <div>
                            <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                                {{ t('welcome.ready_to_scale') }}
                            </h2>
                            <p class="mt-4 text-lg text-estoril-100">
                                {{ t('welcome.free_trial') }}
                            </p>
                            <div class="mt-8 flex flex-col items-start gap-3 sm:flex-row">
                                <Link :href="route('register')" class="group inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-estoril-700 shadow hover:bg-estoril-50">
                                    {{ t('welcome.cta_try_free') }}
                                    <ArrowRightIcon class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                                </Link>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-white">
                            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                                <p class="text-3xl font-bold">142</p>
                                <p class="mt-1 text-xs text-estoril-200">{{ t('welcome.cta_metric_vehicles') }}</p>
                            </div>
                            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                                <p class="text-3xl font-bold">38</p>
                                <p class="mt-1 text-xs text-estoril-200">{{ t('welcome.cta_metric_leads') }}</p>
                            </div>
                            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                                <p class="text-3xl font-bold">€18k</p>
                                <p class="mt-1 text-xs text-estoril-200">{{ t('welcome.cta_metric_margin') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== FOOTER ============== -->
        <footer class="border-t border-asphalt-200/60 bg-white/60 py-8 backdrop-blur-sm dark:border-asphalt-700/60 dark:bg-asphalt-800/40">
            <div class="mx-auto max-w-7xl px-6 text-center text-sm text-asphalt-500 dark:text-asphalt-400">
                © {{ new Date().getFullYear() }} {{ t('app.title') }}. {{ t('welcome.made_for') }}
            </div>
        </footer>
    </div>
</template>
