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
} from '@heroicons/vue/24/outline';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import UpgradeBanner from '@/Components/UpgradeBanner.vue';
import { useFormat } from '@/Composables/useFormat';

const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const page = usePage();

onClickOutside(userMenuRef, () => {
    userMenuOpen.value = false;
});

const pendingAlerts = computed(() => page.props?.pending_alerts_count ?? 0);
const userName = computed(() => page.props?.auth?.user?.name || 'User');
const userEmail = computed(() => page.props?.auth?.user?.email || '');
const user = computed(() => page.props?.auth?.user);
const { initials } = useFormat();

const isActive = (routeName) => {
    if (routeName === 'dashboard') return route().current('dashboard');
    return route().current(routeName + '*') || route().current(routeName);
};

const navGroups = [
    {
        title: 'Overview',
        items: [{ name: 'Dashboard', route: 'dashboard', icon: HomeIcon }],
    },
    {
        title: 'Inventory',
        items: [
            { name: 'Cars', route: 'cars.index', icon: TruckIcon, badge: null },
            { name: 'Kanban', route: 'cars.kanban', icon: Squares2X2Icon },
            { name: 'Map', route: 'cars.map', icon: MapIcon },
            { name: 'Finance', route: 'finance.index', icon: BanknotesIcon },
            { name: 'Trips', route: 'trips.index', icon: CalendarDaysIcon },
        ],
    },
    {
        title: 'CRM',
        items: [
            { name: 'Clients', route: 'clients.index', icon: UsersIcon },
            { name: 'Contacts', route: 'contacts.index', icon: PhoneIcon },
            { name: 'Templates', route: 'message-templates.index', icon: EnvelopeIcon },
            { name: 'Alerts', route: 'alerts.index', icon: BellIcon, badge: 'alerts' },
        ],
    },
    {
        title: 'Account',
        items: [
            { name: 'Plan', route: 'subscriptions.index', icon: CreditCardIcon },
            { name: 'Billing', route: 'billing.index', icon: BanknotesIcon },
            { name: 'Organization', route: 'organization.show', icon: BuildingOfficeIcon, param: user.value?.organization_id },
        ],
    },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile sidebar backdrop -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="['fixed inset-y-0 left-0 z-50 w-64 transform bg-white shadow-xl ring-1 ring-gray-200 transition-transform lg:translate-x-0', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
            <div class="flex h-16 items-center justify-between px-6 border-b border-gray-200">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-auto fill-current text-indigo-600" />
                    <span class="text-lg font-bold text-gray-900">Importnex</span>
                </Link>
                <button @click="sidebarOpen = false" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex h-[calc(100vh-4rem)] flex-col overflow-y-auto px-3 py-4">
                <div v-for="group in navGroups" :key="group.title" class="mb-4">
                    <h3 class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ group.title }}</h3>
                    <div class="space-y-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.name"
                            :href="route(item.route, item.param)"
                            :class="[
                                'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                isActive(item.route) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900',
                            ]"
                        >
                            <component :is="item.icon" :class="['h-5 w-5 flex-shrink-0', isActive(item.route) ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600']" />
                            <span class="flex-1">{{ item.name }}</span>
                            <span v-if="item.badge === 'alerts' && pendingAlerts > 0" class="inline-flex items-center justify-center rounded-full bg-rose-500 px-2 py-0.5 text-xs font-semibold text-white">
                                {{ pendingAlerts }}
                            </span>
                        </Link>
                    </div>
                </div>

                <div class="mt-auto rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 p-4 text-white">
                    <p class="text-sm font-semibold">Need more features?</p>
                    <p class="mt-1 text-xs text-indigo-100">Upgrade your plan to unlock unlimited cars and contacts.</p>
                    <Link :href="route('subscriptions.index')" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                        View plans
                    </Link>
                </div>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden">
                        <Bars3Icon class="h-5 w-5" />
                    </button>
                    <div v-if="$slots.header" class="hidden sm:block">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('alerts.index')" class="relative rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                        <BellIcon class="h-5 w-5" />
                        <span v-if="pendingAlerts > 0" class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                    </Link>

                    <div class="relative" ref="userMenuRef">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 rounded-full p-1 text-sm hover:bg-gray-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-xs font-semibold text-white">
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
                                    <Link :href="route('organization.show', user?.organization_id)" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
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
</template>


