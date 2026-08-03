<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { TruckIcon, UsersIcon, MapIcon, ChartBarIcon, SparklesIcon, ArrowRightIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const features = [
    { icon: TruckIcon, title: t('welcome.inventory_management'), description: t('welcome.track_cars') },
    { icon: UsersIcon, title: t('nav.crm'), description: t('welcome.manage_leads') },
    { icon: MapIcon, title: t('nav.map'), description: t('welcome.map_desc') },
    { icon: ChartBarIcon, title: t('welcome.finance'), description: t('welcome.finance_desc') },
    { icon: SparklesIcon, title: t('welcome.ai_verification'), description: t('welcome.ai_desc') },
    { icon: CheckIcon, title: t('nav.alerts'), description: t('welcome.alerts_desc') },
];

const stats = [
    { value: '80%', label: t('welcome.spreadsheet_savings') },
    { value: '3x', label: t('welcome.turnover') },
    { value: '24/7', label: t('welcome.ai_assistance') },
];
</script>

<template>
    <Head :title="t('app.title')" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-estoril-50">
        <!-- Header -->
        <header class="absolute inset-x-0 top-0 z-10">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-estoril-600 to-estoril-800">
                        <TruckIcon class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-xl font-bold text-gray-900">{{ t('app.title') }}</span>
                </Link>
                <div class="flex items-center gap-3">
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                        {{ t('nav.dashboard') }}
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ t('auth.login') }}</Link>
                        <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            {{ t('auth.register') }}
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden pt-32 pb-20">
            <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-estoril-200/40 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-estoril-200/40 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-6 text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-estoril-100 px-4 py-1.5 text-sm font-semibold text-estoril-700">
                    <SparklesIcon class="h-4 w-4" />
                    {{ t('welcome.ai_powered') }}
                </span>
                <h1 class="mt-8 text-5xl font-bold tracking-tight text-gray-900 sm:text-6xl lg:text-7xl">
                    {{ t('welcome.run_business') }}
                    <span class="block bg-gradient-to-r from-estoril-600 to-estoril-600 bg-clip-text text-transparent">{{ t('welcome.on_autopilot') }}</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
                    {{ t('welcome.importnex_description') }}
                </p>
                <div class="mt-10 flex items-center justify-center gap-4">
                    <Link :href="route('marketplace.index')" class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-estoril-800">
                        {{ t('welcome.view_marketplace') }}
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                    <Link :href="route('register')" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-estoril-500">
                        {{ t('welcome.start_trial') }}
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                    <Link :href="route('login')" class="text-base font-semibold text-gray-700 hover:text-gray-900">
                        {{ t('auth.login') }}
                    </Link>
                </div>

                <div class="mt-16 grid grid-cols-3 gap-8 sm:max-w-2xl mx-auto">
                    <div v-for="s in stats" :key="s.label" class="text-center">
                        <p class="text-3xl font-bold text-estoril-600">{{ s.value }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ s.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ t('welcome.everything_you_need') }}</h2>
                    <p class="mt-4 text-lg text-gray-600">{{ t('welcome.built_for') }}</p>
                </div>
                <div class="mt-16 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="feat in features" :key="feat.title" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-estoril-200">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-estoril-50">
                            <component :is="feat.icon" class="h-5 w-5 text-estoril-600" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-gray-900">{{ feat.title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ feat.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20">
            <div class="mx-auto max-w-5xl px-6">
                <div class="overflow-hidden rounded-3xl bg-gradient-to-r from-estoril-700 to-estoril-900 px-8 py-12 text-center shadow-2xl sm:px-12">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ t('welcome.ready_to_scale') }}</h2>
                    <p class="mt-4 text-lg text-estoril-100">{{ t('welcome.free_trial') }}</p>
                    <Link :href="route('register')" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-estoril-700 shadow hover:bg-estoril-50">
                        {{ t('welcome.start_trial') }}
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </section>

        <footer class="border-t border-gray-200 bg-white py-8">
            <div class="mx-auto max-w-7xl px-6 text-center text-sm text-gray-500">
                © {{ new Date().getFullYear() }} {{ t('app.title') }}. {{ t('welcome.made_for') }}
            </div>
        </footer>
    </div>
</template>
