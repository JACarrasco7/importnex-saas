<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { vMotion } from '@vueuse/motion';
import { TruckIcon, UsersIcon, MapIcon, ChartBarIcon, SparklesIcon, ArrowRightIcon, CheckIcon, PlusIcon, MinusIcon, MapPinIcon, ClipboardDocumentCheckIcon, CurrencyEuroIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    organizationName: { type: String, default: null },
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

const steps = [
    { icon: MapPinIcon, title: t('welcome.step1_title'), description: t('welcome.step1_desc') },
    { icon: ClipboardDocumentCheckIcon, title: t('welcome.step2_title'), description: t('welcome.step2_desc') },
    { icon: CurrencyEuroIcon, title: t('welcome.step3_title'), description: t('welcome.step3_desc') },
];

const testimonials = [
    { name: t('welcome.testimonial1_name'), role: t('welcome.testimonial1_role'), quote: t('welcome.testimonial1_quote'), avatar: 'CM' },
    { name: t('welcome.testimonial2_name'), role: t('welcome.testimonial2_role'), quote: t('welcome.testimonial2_quote'), avatar: 'LG' },
    { name: t('welcome.testimonial3_name'), role: t('welcome.testimonial3_role'), quote: t('welcome.testimonial3_quote'), avatar: 'RS' },
];

const brands = ['Audi', 'BMW', 'Mercedes-Benz', 'Volkswagen', 'Tesla', 'Porsche', 'Land Rover', 'Volvo'];

const faqs = [
    { question: t('welcome.faq1_q'), answer: t('welcome.faq1_a') },
    { question: t('welcome.faq2_q'), answer: t('welcome.faq2_a') },
    { question: t('welcome.faq3_q'), answer: t('welcome.faq3_a') },
    { question: t('welcome.faq4_q'), answer: t('welcome.faq4_a') },
    { question: t('welcome.faq5_q'), answer: t('welcome.faq5_a') },
];

const openFaq = ref(null);
const toggleFaq = (idx) => {
    openFaq.value = openFaq.value === idx ? null : idx;
};
</script>

<template>
    <Head :title="t('app.title')" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-estoril-50">
        <!-- Header -->
        <header class="absolute inset-x-0 top-0 z-10">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-estoril-600 to-estoril-800">
                        <TruckIcon class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-xl font-bold text-gray-900">{{ organizationName || t('app.title') }}</span>
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
                    <div
                        v-for="(feat, idx) in features"
                        :key="feat.title"
                        v-motion
                        :initial="{ opacity: 0, y: 20 }"
                        :enter="{ opacity: 1, y: 0, transition: { delay: idx * 100, duration: 400 } }"
                        class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-estoril-200 hover:-translate-y-1 cursor-pointer"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-estoril-50">
                            <component :is="feat.icon" class="h-5 w-5 text-estoril-600" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-gray-900">{{ feat.title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ feat.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="bg-slate-50/50 py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ t('welcome.how_it_works_title') }}</h2>
                    <p class="mt-4 text-lg text-gray-600">{{ t('welcome.how_it_works_subtitle') }}</p>
                </div>
                <div class="mt-16 grid gap-8 md:grid-cols-3">
                    <div v-for="(step, idx) in steps" :key="idx" v-motion :initial="{ opacity: 0, scale: 0.95 }" :enter="{ opacity: 1, scale: 1, transition: { delay: idx * 150, duration: 500 } }" class="relative">
                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-estoril-600 to-estoril-800 text-white font-bold">
                                    {{ idx + 1 }}
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-estoril-50">
                                    <component :is="step.icon" class="h-6 w-6 text-estoril-600" />
                                </div>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ step.title }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ step.description }}</p>
                        </div>
                        <div v-if="idx < steps.length - 1" class="absolute top-1/2 -right-4 hidden h-px w-8 bg-gray-300 md:block"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Social proof: logos + testimonials -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ t('welcome.brands_trust') }}</h2>
                </div>
                <div class="mt-12 grid grid-cols-2 gap-6 sm:grid-cols-4 lg:grid-cols-8">
                    <div v-for="brand in brands" :key="brand" class="flex items-center justify-center rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 text-sm font-bold text-gray-400">
                        {{ brand }}
                    </div>
                </div>

                <div class="mt-16 grid gap-6 md:grid-cols-3">
                    <div v-for="(testimonial, idx) in testimonials" :key="testimonial.name" v-motion :initial="{ opacity: 0, y: 30 }" :enter="{ opacity: 1, y: 0, transition: { delay: idx * 120, duration: 500 } }" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md transition">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-estoril-600 text-white font-semibold">
                                {{ testimonial.avatar }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ testimonial.name }}</p>
                                <p class="text-xs text-gray-500">{{ testimonial.role }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-gray-700 italic">"{{ testimonial.quote }}"</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="bg-slate-50/50 py-20">
            <div class="mx-auto max-w-3xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ t('welcome.faq_title') }}</h2>
                </div>
                <div class="mt-12 space-y-3">
                    <div v-for="(faq, idx) in faqs" :key="idx" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <button
                            @click="toggleFaq(idx)"
                            class="flex w-full items-center justify-between px-6 py-4 text-left transition hover:bg-gray-50"
                            :aria-expanded="openFaq === idx"
                        >
                            <span class="text-sm font-semibold text-gray-900">{{ faq.question }}</span>
                            <PlusIcon v-if="openFaq !== idx" class="h-5 w-5 text-gray-400 flex-shrink-0" />
                            <MinusIcon v-else class="h-5 w-5 text-estoril-600 flex-shrink-0" />
                        </button>
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-96 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-96 opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-if="openFaq === idx" class="overflow-hidden border-t border-gray-100 px-6 py-4 text-sm text-gray-600">
                                {{ faq.answer }}
                            </div>
                        </Transition>
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
