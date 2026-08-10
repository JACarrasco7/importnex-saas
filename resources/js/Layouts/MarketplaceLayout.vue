<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bars3Icon,
    XMarkIcon,
    TruckIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import LocaleSelector from '@/Components/LocaleSelector.vue';
import DarkModeToggle from '@/Components/DarkModeToggle.vue';
import WhatsAppFloat from '@/Components/WhatsAppFloat.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const page = usePage();

const mobileOpen = ref(false);

// Contexto del marketplace publico (compartido desde HandleInertiaRequests).
const marketplace = computed(() => page.props.marketplace ?? {});
const orgName = computed(() => marketplace.value.orgName || 'JJ Import Motors');
const requestHref = computed(
    () => marketplace.value.requestUrl || route('marketplace.index') + '#contacto',
);

const navLinks = computed(() => [
    { label: t('marketplace.nav_catalog', {}, 'Catálogo'), href: route('marketplace.index') },
    { label: t('marketplace.nav_how', {}, 'Cómo funciona'), href: route('marketplace.index') + '#how-it-works' },
    { label: t('marketplace.nav_contact', {}, 'Contacto'), href: route('marketplace.index') + '#contacto' },
]);

const close = () => { mobileOpen.value = false; };
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 antialiased transition-colors duration-300 dark:bg-asphalt-900 dark:text-white">
        <!-- Header sticky -->
        <header class="sticky top-0 z-30 border-b border-gray-200/70 bg-white/80 backdrop-blur-md dark:border-asphalt-700/60 dark:bg-asphalt-900/80">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <Link :href="route('marketplace.index')" class="flex items-center gap-2.5" @click="close">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-estoril-600 to-estoril-800 shadow-sm">
                        <TruckIcon class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">{{ orgName }}</span>
                </Link>

                <!-- Desktop nav -->
                <div class="hidden items-center gap-7 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        prefetch="hover"
                        class="text-sm font-medium text-gray-700 transition hover:text-estoril-700 dark:text-gray-200 dark:hover:text-estoril-300"
                    >
                        {{ link.label }}
                    </Link>
                </div>

                <!-- Right actions -->
                <div class="hidden items-center gap-3 md:flex">
                    <LocaleSelector />
                    <DarkModeToggle />
                    <Link
                        :href="requestHref"
                        class="inline-flex items-center gap-1 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-estoril-500"
                    >
                        {{ t('marketplace.nav_request', {}, 'Solicita tu vehículo') }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </Link>
                </div>

                <!-- Mobile burger -->
                <button
                    type="button"
                    class="md:hidden rounded-lg p-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-asphalt-800"
                    :aria-label="mobileOpen ? t('public.nav.close_menu', {}, 'Cerrar menú') : t('public.nav.open_menu', {}, 'Abrir menú')"
                    :aria-expanded="mobileOpen"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" />
                    <XMarkIcon v-else class="h-6 w-6" />
                </button>
            </nav>

            <!-- Mobile drawer -->
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="mobileOpen" class="border-t border-gray-200 bg-white px-4 py-4 dark:border-asphalt-700 dark:bg-asphalt-900 md:hidden">
                    <div class="flex flex-col gap-2">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-asphalt-800"
                            @click="close"
                        >
                            {{ link.label }}
                        </Link>
                        <div class="my-2 border-t border-gray-200 dark:border-asphalt-700" />
                        <Link
                            :href="requestHref"
                            class="rounded-lg bg-estoril-600 px-3 py-2 text-center text-sm font-semibold text-white"
                            @click="close"
                        >
                            {{ t('marketplace.nav_request', {}, 'Solicita tu vehículo') }}
                        </Link>
                        <div class="mt-2 flex items-center justify-between gap-2 px-3">
                            <LocaleSelector />
                            <DarkModeToggle />
                        </div>
                    </div>
                </div>
            </Transition>
        </header>

        <!-- Slot principal -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-gray-50/80 transition-colors duration-300 dark:border-asphalt-700 dark:bg-asphalt-800/50">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div class="col-span-2 md:col-span-1">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-estoril-600 to-estoril-800">
                                <TruckIcon class="h-5 w-5 text-white" />
                            </div>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ orgName }}</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ t('marketplace.footer_tagline', {}, 'Vehículos verificados importados desde Alemania, listos para ti.') }}
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            {{ t('marketplace.footer_catalog', {}, 'Catálogo') }}
                        </h3>
                        <ul class="mt-3 space-y-2 text-sm">
                            <li>
                                <Link :href="route('marketplace.index')" class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300">
                                    {{ t('marketplace.nav_catalog', {}, 'Catálogo') }}
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('marketplace.index') + '#how-it-works'" class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300">
                                    {{ t('marketplace.nav_how', {}, 'Cómo funciona') }}
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('marketplace.index') + '#contacto'" class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300">
                                    {{ t('marketplace.nav_contact', {}, 'Contacto') }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            {{ t('marketplace.footer_contact', {}, 'Contacto') }}
                        </h3>
                        <ul class="mt-3 space-y-2 text-sm">
                            <li>
                                <a href="mailto:hola@jjimportmotors.com" class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300">
                                    hola@jjimportmotors.com
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://wa.me/34600000000?text=Hola%20JJ%20Import%20Motors%2C%20me%20interesa%20m%C3%A1s%20informaci%C3%B3n"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300"
                                >
                                    WhatsApp
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            {{ t('marketplace.footer_legal', {}, 'Legal') }}
                        </h3>
                        <ul class="mt-3 space-y-2 text-sm">
                            <li>
                                <Link :href="route('marketplace.index') + '#legal'" class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300">
                                    {{ t('public.footer.privacy', 'Privacidad') }}
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('marketplace.index') + '#legal'" class="text-gray-600 hover:text-estoril-700 dark:text-gray-400 dark:hover:text-estoril-300">
                                    {{ t('public.footer.terms', 'Términos') }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 flex flex-col items-center justify-between gap-2 border-t border-gray-200 pt-6 text-xs text-gray-500 dark:border-asphalt-700 dark:text-gray-400 sm:flex-row">
                    <p>
                        © {{ new Date().getFullYear() }} {{ orgName }}. {{ t('public.footer.rights', 'Todos los derechos reservados.') }}
                    </p>
                    <Link :href="route('pricing')" class="hover:text-estoril-700 dark:hover:text-estoril-300">
                        {{ t('marketplace.footer_saas', {}, '¿Eres importador? Conoce la plataforma') }}
                    </Link>
                </div>
            </div>
        </footer>

        <WhatsAppFloat />
    </div>
</template>
