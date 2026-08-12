<script setup>
import { ref, computed } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    ChevronDownIcon,
    ArrowRightOnRectangleIcon,
    UserCircleIcon,
    BuildingOfficeIcon,
    Squares2X2Icon,
    TruckIcon,
    MapIcon,
    BanknotesIcon,
    CalendarDaysIcon,
    UsersIcon,
    PhoneIcon,
    EnvelopeIcon,
    CreditCardIcon,
    HomeIcon,
    ClipboardDocumentListIcon,
    SparklesIcon,
    BookOpenIcon,
    MegaphoneIcon,
    ShoppingBagIcon,
    ArrowTopRightOnSquareIcon,
    GlobeAltIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import UpgradeBanner from '@/Components/UpgradeBanner.vue';
import DunningBanner from '@/Components/DunningBanner.vue';
import LocaleSelector from '@/Components/LocaleSelector.vue';
import SidebarGroup from '@/Components/SidebarGroup.vue';
import NotificationToaster from '@/Components/NotificationToaster.vue';
import DarkModeToggle from '@/Components/DarkModeToggle.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';
import { useNotificationPolling } from '@/Composables/useNotificationPolling';

const { t } = useTranslations();

const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const page = usePage();

onClickOutside(userMenuRef, () => {
    userMenuOpen.value = false;
});

const pendingAlerts = computed(() => page.props?.pending_alerts_count ?? 0);
const pendingCarRequests = computed(() => page.props?.pending_car_requests_count ?? 0);

// Polling reactivo: el badge se actualiza solo, y emite toasts in-app.
// Usa /alerts/pending.json (endpoint ligero, sin paginacion).
const {
    count: liveAlertCount,
    newCount: newAlertCount,
    toasts: alertToasts,
    markSeen: markAlertsSeen,
    dismissToast: dismissAlertToast,
} = useNotificationPolling({ intervalMs: 30000 });

// Sincronizar el badge del topbar: usa el valor live si difiere del prop
// (ej: tras navegar a otra pagina sin full reload)
const visibleAlertCount = computed(() => Math.max(liveAlertCount.value, pendingAlerts.value));
const userName = computed(() => page.props?.auth?.user?.name || 'User');
const userEmail = computed(() => page.props?.auth?.user?.email || '');
const user = computed(() => page.props?.auth?.user);
const { initials } = useFormat();

const isActive = (routeName) => {
    return route().current(routeName + '*') || route().current(routeName);
};

const navGroups = computed(() => [
    {
        title: t('nav.overview'),
        icon: HomeIcon,
        items: [{ name: t('nav.dashboard'), route: 'dashboard', icon: HomeIcon }],
    },
    {
        title: t('nav.inventory'),
        icon: TruckIcon,
        items: [
            { name: t('nav.cars'), route: 'cars.index', icon: TruckIcon },
            { name: t('nav.kanban'), route: 'cars.kanban', icon: Squares2X2Icon },
            { name: t('nav.map'), route: 'cars.map', icon: MapIcon },
            { name: t('nav.finance'), route: 'finance.index', icon: BanknotesIcon },
            { name: t('nav.trips'), route: 'trips.index', icon: CalendarDaysIcon },
            { name: t('nav.kpis'), route: 'kpis.index', icon: ChartBarIcon },
            { name: t('nav.marketplace'), route: 'marketplace.index', icon: ShoppingBagIcon, external: true },
        ],
    },
    {
        title: t('nav.crm'),
        icon: UsersIcon,
        items: [
            { name: t('nav.clients'), route: 'clients.index', icon: UsersIcon },
            { name: t('nav.contacts'), route: 'contacts.index', icon: PhoneIcon },
            {
                name: t('nav.requests'),
                route: 'car-requests.index',
                icon: ClipboardDocumentListIcon,
                badge: pendingCarRequests.value,
                badgeColor: 'bg-estoril-500',
            },
            { name: t('nav.templates'), route: 'message-templates.index', icon: EnvelopeIcon },
            {
                name: t('nav.alerts'),
                route: 'alerts.index',
                icon: BellIcon,
                badge: pendingAlerts.value,
                badgeColor: 'bg-rose-500',
            },
        ],
    },
    {
        title: t('nav.marketing'),
        icon: MegaphoneIcon,
        items: [
            { name: t('nav.marketing_center'), route: 'marketing.index', icon: MegaphoneIcon },
            { name: t('nav.cars'), route: 'cars.index', icon: TruckIcon },
        ],
    },
    // Guía oculta: la ruta 'guide.index' aún no existe en el repo
    // y Ziggy lanza errores en el sidebar. Reactivar cuando exista el módulo.
    // {
    //     title: t('nav.guide'),
    //     icon: BookOpenIcon,
    //     items: [
    //         { name: t('nav.guide'), route: 'guide.index', icon: BookOpenIcon },
    //     ],
    // },
    {
        title: t('nav.account'),
        icon: BuildingOfficeIcon,
        items: [
            { name: t('nav.ai_chat'), route: 'ai.chat', icon: SparklesIcon },
            { name: t('nav.plan'), route: 'subscriptions.index', icon: CreditCardIcon },
            { name: t('nav.billing'), route: 'billing.index', icon: BanknotesIcon },
            { name: t('nav.organization'), route: 'organization.show', icon: BuildingOfficeIcon, param: user.value?.organization_id },
        ],
    },
]);
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-asphalt-900">
        <!-- Mobile sidebar backdrop -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="['fixed inset-y-0 left-0 z-50 w-64 transform bg-white shadow-xl ring-1 ring-gray-200 transition-transform dark:bg-asphalt-800 dark:ring-asphalt-700 lg:translate-x-0', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
            <div class="flex h-16 items-center justify-between px-6 border-b border-gray-200 dark:border-asphalt-700">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-auto fill-current text-estoril-600" />
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Importnex</span>
                </Link>
                <button @click="sidebarOpen = false" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex h-[calc(100vh-4rem)] flex-col overflow-y-auto px-3 py-4">
                <SidebarGroup
                    v-for="group in navGroups"
                    :key="group.title"
                    :title="group.title"
                    :icon="group.icon"
                    :items="group.items"
                />

                <div class="mt-auto rounded-xl bg-linear-to-br from-estoril-600 to-estoril-800 p-4 text-white">
                    <p class="text-sm font-semibold">{{ t('nav.need_more_features') }}</p>
                    <p class="mt-1 text-xs text-estoril-100">{{ t('nav.upgrade_message') }}</p>
                    <Link :href="route('subscriptions.index')" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-estoril-700 hover:bg-estoril-50">
                        {{ t('nav.view_plans') }}
                    </Link>
                </div>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white/80 px-4 backdrop-blur dark:border-asphalt-700 dark:bg-asphalt-900/80 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden">
                        <Bars3Icon class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        @click="$dispatch('command-palette:open')"
                        class="hidden md:inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-500 transition hover:bg-gray-100 dark:border-asphalt-700 dark:bg-asphalt-800 dark:text-asphalt-400 dark:hover:bg-asphalt-700"
                        :aria-label="t('common.search') ?? 'Buscar'"
                    >
                        <span class="text-xs">🔍</span>
                        <span>Buscar...</span>
                        <kbd class="ml-2 rounded border border-gray-300 bg-white px-1.5 py-0.5 text-[10px] font-mono dark:border-asphalt-600 dark:bg-asphalt-900">⌘K</kbd>
                    </button>
                    <div v-if="$slots.header" class="hidden sm:block">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <DarkModeToggle />
                    <LocaleSelector class="hidden sm:block" />
                    <Link
                        :href="route('alerts.index')"
                        @click="markAlertsSeen()"
                        class="relative inline-flex items-center justify-center rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-estoril-300"
                        :aria-label="t('alerts.title')"
                    >
                        <BellIcon class="h-5 w-5" :class="newAlertCount > 0 ? 'animate-wiggle' : ''" />
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 scale-50"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-50"
                        >
                            <span
                                v-if="visibleAlertCount > 0"
                                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white"
                                :class="newAlertCount > 0 ? 'animate-pop-in ring-rose-300' : ''"
                            >
                                {{ visibleAlertCount > 99 ? '99+' : visibleAlertCount }}
                            </span>
                        </Transition>
                    </Link>

                    <div class="relative" ref="userMenuRef">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 rounded-full p-1 text-sm hover:bg-gray-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-linear-to-br from-estoril-600 to-estoril-800 text-xs font-semibold text-white">
                                {{ initials(userName) }}
                            </span>
                            <span class="hidden md:block text-sm font-medium text-gray-700">{{ userName }}</span>
                            <ChevronDownIcon class="hidden h-4 w-4 text-gray-400 md:block" />
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900">{{ userName }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ userEmail }}</p>
                                </div>
                                <div class="py-1">
                                    <Link :href="route('profile.edit')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <UserCircleIcon class="h-4 w-4" />
                                        Profile
                                    </Link>
                                    <Link v-if="user?.organization_id" :href="route('organization.show', user.organization_id)" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <BuildingOfficeIcon class="h-4 w-4" />
                                        Organization
                                    </Link>
                                </div>
                                <div class="border-t border-gray-100 py-1">
                                    <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <ArrowRightOnRectangleIcon class="h-4 w-4" />
                                        Log out
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>

            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="space-y-2 px-4 pt-4 sm:px-6 lg:px-8">
                <FlashMessage v-if="$page.props.flash?.success" type="success" :message="$page.props.flash.success" />
                <FlashMessage v-if="$page.props.flash?.error" type="error" :message="$page.props.flash.error" />
            </div>

            <!-- Dunning banner (payment failed) - shown above plan limit banner -->
            <div v-if="$page.props.payment_failed || $page.props.payment_failed_at" class="px-4 pt-4 sm:px-6 lg:px-8">
                <DunningBanner
                    :payment-failed="!!$page.props.payment_failed"
                    :payment-failed-at="$page.props.payment_failed_at"
                />
            </div>

            <!-- Plan limit upgrade banner -->
            <div v-if="$page.props.planUsage" class="px-4 pt-4 sm:px-6 lg:px-8">
                <UpgradeBanner :usage="$page.props.planUsage" :current-plan="$page.props.currentPlan?.name" />
            </div>

            <!-- Page content -->
            <main>
                <slot />
            </main>
        </div>
    </div>

    <!-- Floating AI chat widget is mounted globally via resources/js/aiChatLauncher.js
         (mounted by app.js). Putting it inline here caused Vite to tree-shake
         the import in production builds. -->

    <!-- Toaster global de alertas (polling reactivo, no requiere WebSockets) -->
    <NotificationToaster :toasts="alertToasts" :on-dismiss="dismissAlertToast" />
</template>


